<?php
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-pager
 */
declare(strict_types=1);

namespace froq\pager;

use froq\common\{interface\Arrayable, trait\AttributeTrait};

/**
 * Pager.
 *
 * @package froq\pager
 * @object  froq\pager\Pager
 * @author  Kerem Güneş
 * @since   1.0
 */
final class Pager implements Arrayable, \Countable, \JsonSerializable
{
    use AttributeTrait;

    /** @var array */
    private static array $attributesDefault = [
        'start'             => 0,    // Offset.
        'stop'              => 10,   // Limit or per-page.
        'stopMax'           => 1000,
        'stopDefault'       => 10,
        'startKey'          => 's',  // GET param key of start.
        'stopKey'           => 'ss', // GET param key of stop.
        'totalPages'        => null,
        'totalRecords'      => null,
        'links'             => [],   // Generated links.
        'linksCenter'       => [],   // Generated links (center aligned).
        'linksLimit'        => 5,
        'linksTemplate'     => [
            'page'  => 'Page',
            'first' => '&laquo;',  'prev' => '&lsaquo;',
            'next'  => '&rsaquo;', 'last' => '&raquo;',
        ],
        'linksClassName'    => 'pager',
        'numerateFirstLast' => false,
        'autorun'           => true,
        'redirect'          => true,
        'argSep'            => '&',
    ];

    /**
     * Constructor.
     *
     * @param array|null $attributes
     */
    public function __construct(array $attributes = null)
    {
        // @todo: Change that stop/start stuff => limit/offset or page?
        if ($attributes) {
            $attributes = array_swap($attributes, 'page', 'start');
            $attributes = array_swap($attributes, 'limit', 'stop');
        }

        $this->setAttributes($attributes, self::$attributesDefault);
    }

    /**
     * @magic
     * @since 3.0
     */
    public function __set(string $name, mixed $value): void
    {
        if (in_array($name, ['limit', 'offset'], true)) {
            $name  = ($name == 'limit') ? 'stop' : 'start';
            $value = (int) $value;
        }

        $this->setAttribute($name, $value);
    }

    /**
     * @magic
     * @since 3.0
     */
    public function __get(string $name): mixed
    {
        if (in_array($name, ['limit', 'offset'], true)) {
            $name = ($name == 'limit') ? 'stop' : 'start';
            return (int) $this->getAttribute($name);
        }

        return $this->getAttribute($name);
    }

    /**
     * Get limit (stop alias).
     *
     * @return int
     */
    public function getLimit(): int
    {
        return (int) $this->getAttribute('stop');
    }

    /**
     * Get offset (start alias).
     *
     * @return int
     */
    public function getOffset(): int
    {
        return (int) $this->getAttribute('start');
    }

    /**
     * Current.
     *
     * @return int
     * @since  4.1
     */
    public function getCurrent(): int
    {
        return max(1, intval($this->start / $this->stop) + 1);
    }

    /**
     * Run.
     *
     * @param  int|null    $count
     * @param  int|null    $limit
     * @param  string|null $startKey
     * @param  string|null $stopKey
     * @return array<int>
     */
    public function run(int $count = null, int $limit = null, string $startKey = null, string $stopKey = null): array
    {
        if ($count !== null) {
            $this->totalRecords = abs($count);
        }

        // Update start/stop keys.
        $startKey && ($this->startKey = $startKey);
        $stopKey && ($this->stopKey = $stopKey);

        // These stuff may be given in constructor as well.
        $startValue = self::getStartParam($startKey) ?? $this->start;
        if ($limit === null) {
            $stopValue = self::getStopParam($stopKey) ?? $this->stop;
        } else {
            $stopValue = $limit; // Skip GET parameter.
        }

        // Get params may be manipulated by developer (setting autorun false).
        if ($this->autorun) {
            $this->start = abs((int) $startValue);
            $this->stop = abs((int) $stopValue);
        }

        $this->stop = ($this->stop > 0) ? $this->stop : $this->stopDefault;
        $this->start = ($this->start > 1) ? ($this->start * $this->stop) - $this->stop : 0;

        $this->totalPages = 1;
        if ($this->totalRecords > 1) {
            $this->totalPages = abs((int) ceil($this->totalRecords / $this->stop));
            // $this->totalPages = abs((int) ceil($this->totalRecords / 1.25)); // @nope
        }

        // Safety (if redirectable / redirect attribute is true).
        if ($startValue !== null && $this->redirect) {
            if ($startValue > $this->totalPages) {
                $this->redirect($this->query() . $this->startKey .'='. $this->totalPages, 307);
            } elseif ($startValue && strval($startValue)[0] == '-') {
                $this->redirect($this->query() . $this->startKey .'='. abs($startValue), 301);
            } elseif ($startValue === '' || $startValue === '0' || !ctype_digit((string) $startValue)) {
                $this->redirect(trim($this->query(), $this->argSep), 301);
            }
        }
        if ($stopValue !== null && $this->redirect) {
            if ($stopValue > $this->stopMax) {
                $this->redirect($this->query($this->stopKey) . $this->stopKey .'='. $this->stopMax, 307);
            } elseif ($stopValue && strval($stopValue)[0] == '-') {
                $this->redirect($this->query($this->stopKey) . $this->stopKey .'='. abs($stopValue), 301);
            } elseif ($stopValue === '' || $stopValue === '0' || !ctype_digit((string) $stopValue)) {
                $this->redirect(trim($this->query(), $this->argSep), 301);
            }
        }

        // Fix start/stop.
        if ($this->totalRecords == 1) {
            $this->start = 0;
            $this->stop = 1;
        }

        return [$this->stop, $this->start];
    }

    /**
     * Generate links.
     *
     * @param  int|null    $linksLimit
     * @param  string|null $ignoredKeys
     * @param  string|null $linksClassName
     * @return string
     */
    public function generateLinks(int $linksLimit = null, string $ignoredKeys = null, string $linksClassName = null): string
    {
        $totalPages = $this->totalPages;

        // Called run()?
        if ($totalPages === null) {
            throw new PagerException('No pages to generate links, call run() first');
        }

        // Only one page?.
        if ($totalPages == 1) {
            return $this->template(['<a class="current" href="#">1</a>'], $linksClassName);
        }

        $links = (array) $this->links;
        if ($links) {
            return $this->template($links, $linksClassName);
        }

        $linksTemplate = $this->linksTemplate;
        if ($this->numerateFirstLast) {
            $linksTemplate['first'] = 1;
            $linksTemplate['last']  = $totalPages;
        }

        $linksLimit = $linksLimit ?? $this->linksLimit;
        if ($linksLimit > $totalPages) {
            $linksLimit = $totalPages;
        }

        $s      = $this->startKey;
        $query  = $this->query($ignoredKeys);
        $start  = $this->getCurrent();
        $stop   = $start + $linksLimit;

        $sub    = 1;
        $mid    = ceil($linksLimit / 2);
        $midsub = $mid - $sub;

        // Calculate loop.
        if ($start >= $mid) {
            $i    = $start - $midsub;
            $loop = $stop  - $midsub;
        } else {
            $i    = $sub;
            $loop = ($start == $midsub) ? $stop - $sub : $stop;
            if ($loop >= $linksLimit) {
                $diff = $loop - $linksLimit;
                $loop = $loop - $diff + $sub;
            }
        }

        // Add first & prev links.
        $prev = $start - 1;
        if ($prev >= 1) {
            $links[] = sprintf('<a class="first" rel="first" href="%s%s=1">%s</a>', $query, $s,
                $linksTemplate['first']);
            $links[] = sprintf('<a class="prev" rel="prev" href="%s%s=%s">%s</a>', $query, $s, $prev,
                $linksTemplate['prev']);
        }

        // Add numbered links.
        for ($i; $i < $loop; $i++) {
            if ($loop <= $totalPages) {
                if ($i == $start) {
                    $links[] = '<a class="current" href="#">'. $i .'</a>';
                } else {
                    $relPrevNext = '';
                    if ($i == $start - 1) {
                        $relPrevNext = ' rel="prev"';
                    } elseif ($i == $start + 1) {
                        $relPrevNext = ' rel="next"';
                    }
                    $links[] = sprintf('<a%s href="%s%s=%s">%s</a>', $relPrevNext, $query, $s, $i, $i);
                }
            } else {
                $j = $start;
                $extra = $totalPages - $start;
                if ($extra < $linksLimit) {
                    $j = $j - (($linksLimit - 1) - $extra);
                }

                for ($j; $j <= $totalPages; $j++) {
                    if ($j == $start) {
                        $links[] = '<a class="current" href="#">'. $j .'</a>';
                    } else {
                        $links[] = sprintf('<a rel="next" href="%s%s=%s">%s</a>', $query, $s, $j, $j);
                    }
                }
                break;
            }
        }

        // Add next & last link.
        $next = $start + 1;
        if ($start != $totalPages) {
            $links[] = sprintf('<a class="next" rel="next" href="%s%s=%s">%s</a>', $query, $s, $next,
                $linksTemplate['next']);
            $links[] = sprintf('<a class="last" rel="last" href="%s%s=%s">%s</a>', $query, $s, $totalPages,
                $linksTemplate['last']);
        }

        // Store.
        $this->links = $links;

        return $this->template($links, $linksClassName);
    }

    /**
     * Generate links center.
     *
     * @param  string|null $page
     * @param  string|null $ignoredKeys
     * @param  string      $linksClassName
     * @return string
     */
    public function generateLinksCenter(string $page = null, string $ignoredKeys = null, $linksClassName = null): string
    {
        $totalPages = $this->totalPages;

        // Called run()?
        if ($totalPages === null) {
            throw new PagerException('No pages to generate links, call run() first');
        }

        // Only one page?.
        if ($totalPages == 1) {
            return $this->template(['<a class="current" href="#">1</a>'], $linksClassName, true);
        }

        $links = (array) $this->linksCenter;
        if ($links) {
            return $this->template($links, $linksClassName, true);
        }

        $linksTemplate = $this->linksTemplate;

        $s     = $this->startKey;
        $query = $this->query($ignoredKeys);
        $start = $this->getCurrent();

        // Add first & prev links.
        $prev = $start - 1;
        if ($prev >= 1) {
            $links[] = sprintf('<a class="first" rel="first" href="%s%s=1">%s</a>', $query, $s,
                $linksTemplate['first']);
            $links[] = sprintf('<a class="prev" rel="prev" href="%s%s=%s">%s</a>', $query, $s, $prev,
                $linksTemplate['prev']);
        }

        $links[] = sprintf('<a class="current" href="#">%s %s</a>', $page ?? $linksTemplate['page'], $start);

        // Add next & last link.
        $next = $start + 1;
        if ($start < $totalPages) {
            $links[] = sprintf('<a class="next" rel="next" href="%s%s=%s">%s</a>', $query, $s, $next,
                $linksTemplate['next']);
            $links[] = sprintf('<a class="last" rel="last" href="%s%s=%s">%s</a>', $query, $s, $totalPages,
                $linksTemplate['last']);
        }

        // Store.
        $this->linksCenter = $links;

        return $this->template($links, $linksClassName, true);
    }

    /**
     * Get start param from $_GET global.
     *
     * @param  string|null $key
     * @return int|null
     * @since  5.1
     */
    public static function getStartParam(string $key = null): int|null
    {
        $key ??= self::$attributesDefault['startKey'];

        return isset($_GET[$key]) ? intval($_GET[$key]) : null;
    }

    /**
     * Get stop param from $_GET global.
     *
     * @param  string|null $key
     * @return int|null
     * @since  5.1
     */
    public static function getStopParam(string $key = null): int|null
    {
        $key ??= self::$attributesDefault['stopKey'];

        return isset($_GET[$key]) ? intval($_GET[$key]) : null;
    }

    /**
     * Make a template with given links.
     *
     * @param  array       $links
     * @param  string|null $linksClassName
     * @param  bool        $center
     * @return string
     */
    private function template(array $links, string $linksClassName = null, bool $center = false): string
    {
        $linksClassName = $linksClassName ?? $this->linksClassName;
        if ($center) {
            $linksClassName .= ' center';
        }

        $ret = "<ul class=\"{$linksClassName}\">";
        foreach ($links as $link) {
            $ret .= "<li>{$link}</li>";
        }
        $ret .= "</ul>";

        return $ret;
    }

    /**
     * Escape input.
     *
     * @param  string $input
     * @return string
     * @since  4.0
     */
    private function escape(string $input): string
    {
        return str_replace(
            ["\0", "'", '"', '<', '>'],
            ['', '&#39;', '&#34;', '&lt;', '&gt;'],
            $input
        );
    }

    /**
     * Prepare query.
     *
     * @param  string|null $ignoredKeys
     * @return string
     */
    private function query(string $ignoredKeys = null): string
    {
        $temp  = explode('?', ($_SERVER['REQUEST_URI'] ?? ''), 2);
        $path  = trim($temp[0]);
        $query = trim($temp[1] ?? '');

        if ($query != '') {
            $query = http_parse_query_string($query);

            $keys = $this->startKey;
            if ($ignoredKeys != '') {
                $keys .= ',' . $ignoredKeys;
            }

            // Drop ignored keys.
            $query = array_unset($query, ...explode(',', $keys));

            $query = http_build_query_string($query);

            if ($query != '') {
                $query .= $this->argSep;
            }

            return $this->escape($path) .'?'. $this->escape($query);
        } else {
            return $this->escape($path) .'?';
        }
    }

    /**
     * Redirect.
     *
     * @param  string $to
     * @param  int    $code
     * @return void
     */
    private function redirect(string $to, int $code): void
    {
        $to = trim($to);

        // Comes from sugars/http.php.
        if (function_exists('redirect')) {
            redirect($to, $code);
        } elseif (!headers_sent()) {
            header('Location: '. $to, true, $code);

            $to = $this->escape($to);

            // Yes..
            die('Redirecting to <a href="'. $to .'">'. $to .'</a>');
        }
    }

    /**
     * @inheritDoc froq\common\interface\Arrayable
     * @since 4.1
     */
    public function toArray(bool $noEmpty = true): array
    {
        [$current, $totalPages, $totalRecords] = [
            $this->getCurrent(), $this->totalPages, $this->totalRecords
        ];

        if ($noEmpty && !$totalRecords) {
            $totalPages = 0;
        }

        return [
            'limit'        => $this->getLimit(),
            'offset'       => $this->getOffset(),
            'current'      => $current,
            'prev'         => ($current - 1 >= 1) ? $current - 1 : null,
            'next'         => ($current + 1 <= $totalPages) ? $current + 1 : null,
            'totalPages'   => $totalPages,
            'totalRecords' => $totalRecords,
        ];
    }

    /**
     * @inheritDoc Countable
     * @since 5.0
     */
    public function count(): int
    {
        return $this->totalRecords ? $this->totalPages : 0;
    }

    /**
     * @inheritDoc JsonSerializable
     * @since 5.0
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
