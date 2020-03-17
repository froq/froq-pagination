<?php
/**
 * MIT License <https://opensource.org/licenses/mit>
 *
 * Copyright (c) 2015 Kerem Güneş
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
declare(strict_types=1);

namespace froq\pager;

use froq\common\traits\AttributeTrait;
use froq\util\Util;
use froq\pager\PagerException;

/**
 * Pager.
 * @package froq\pager
 * @object  froq\pager\Pager
 * @author  Kerem Güneş <k-gun@mail.com>
 * @since   1.0
 */
final class Pager
{
    /**
     * Attribute trait.
     *
     * @see froq\common\traits\AttributeTrait
     * @since 4.0
     */
    use AttributeTrait;

    /**
     * Attributes default.
     * @var array
     */
    private static array $attributesDefault = [
        'start'             => 0,
        'stop'              => 10, // Limit or per-page.
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
        'argSep'            => '',
    ];


    /**
     * Constructor.
     * @param array|null $attributes
     */
    public function __construct(array $attributes = null)
    {
        $attributes['argSep'] ??= ini_get('arg_separator.output') ?: '&';

        $attributes = array_replace_recursive(self::$attributesDefault, $attributes);

        foreach ($attributes as $name => $value) {
            $this->setAttribute($name, $value);
        }
    }

    /**
     * Set.
     * @param  string $name
     * @param  any    $value
     * @return void
     * @since  3.0
     */
    public function __set($name, $value)
    {
        if (in_array($name, ['limit', 'offset'])) {
            $name = ($name == 'limit') ? 'stop' : 'start';
        }

        $this->setAttribute($name, $value);
    }

    /**
     * Get.
     * @param  string $name
     * @return any|null
     * @since  3.0
     */
    public function __get($name)
    {
        if (in_array($name, ['limit', 'offset'])) {
            $name = ($name == 'limit') ? 'stop' : 'start';
        }

        return $this->getAttribute($name);
    }

    /**
     * Get limit (stop alias).
     * @return int
     */
    public function getLimit(): int
    {
        return $this->getAttribute('stop');
    }

    /**
     * Get offset (start alias).
     * @return int
     */
    public function getOffset(): int
    {
        return $this->getAttribute('start');
    }

    /**
     * Run.
     * @param  int|null    $totalRecords
     * @param  int|null    $limit
     * @param  string|null $startKey
     * @param  string|null $stopKey
     * @return array<int>
     */
    public function run(int $totalRecords = null, int $limit = null, string $startKey = null,
        string $stopKey = null): array
    {
        if ($totalRecords !== null) {
            $this->totalRecords = abs($totalRecords);
        }

        $startKey && $this->startKey = $startKey;
        $stopKey && $this->stopKey = $stopKey;

        $startValue = $_GET[$this->startKey] ?? null;
        if ($limit === null) {
            $stopValue = $_GET[$this->stopKey] ?? null;
        } else {
            $stopValue = $limit; // Skip GET parameter.
        }

        // Get params may be manipulated by developer (setting autorun false).
        if ($this->autorun) {
            $this->start = abs($startValue);
            $this->stop = abs($stopValue);
        }

        $this->stop = ($this->stop > 0) ? $this->stop : $this->stopDefault;
        $this->start = ($this->start > 1) ? ($this->start * $this->stop) - $this->stop : 0;

        $this->totalPages = 1;
        if ($this->totalRecords > 1) {
            $this->totalPages = abs((int) ceil($this->totalRecords / $this->stop));
        }

        // Safety.
        if ($startValue !== null) {
            if ($startValue > $this->totalPages) {
                $this->redirect($this->prepareQuery() . $this->startKey .'='. $this->totalPages, 307);
            } elseif ($startValue && strval($startValue)[0] == '-') {
                $this->redirect($this->prepareQuery() . $this->startKey .'='. abs($startValue), 301);
            } elseif ($startValue === '' || $startValue === '0' || !ctype_digit(strval($startValue))) {
                $this->redirect(trim($this->prepareQuery(), $this->argSep), 301);
            }
        }
        if ($stopValue !== null) {
            if ($stopValue > $this->stopMax) {
                $this->redirect($this->prepareQuery($this->stopKey) . $this->stopKey .'='. $this->stopMax, 307);
            } elseif ($stopValue && strval($stopValue)[0] == '-') {
                $this->redirect($this->prepareQuery($this->stopKey) . $this->stopKey .'='. abs($stopValue), 301);
            } elseif ($stopValue === '' || $stopValue === '0' || !ctype_digit(strval($stopValue))) {
                $this->redirect(trim($this->prepareQuery(), $this->argSep), 301);
            }
        }

        // Fix start/stop.
        if ($this->totalRecords == 1) {
            $this->stop = 1;
            $this->start = 0;
        }

        return [$this->stop, $this->start];
    }

    /**
     * Generate links.
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
        if ($links != null) {
            return $this->template($links, $linksClassName);
        }

        $linksTemplate = $this->linksTemplate;
        $numerateFirstLast = $this->numerateFirstLast;
        if ($numerateFirstLast) {
            $linksTemplate['first'] = 1;
            $linksTemplate['last'] = $totalPages;
        }

        $linksLimit = $linksLimit ?? $this->linksLimit;
        if ($linksLimit > $totalPages) {
            $linksLimit = $totalPages;
        }

        $s = $this->startKey;
        $query = $this->prepareQuery($ignoredKeys);
        $start = max(1, ($this->start / $this->stop) + 1);
        $stop = $start + $linksLimit;

        // Calculate loop.
        $sub = 1;
        $middle = ceil($linksLimit / 2);
        $middleSub = $middle - $sub;
        if ($start >= $middle) {
            $i = $start - $middleSub;
            $loop = $stop - $middleSub;
        } else {
            $i = $sub;
            $loop = ($start == $middleSub) ? $stop - $sub : $stop;
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
        if ($links != null) {
            return $this->template($links, $linksClassName, true);
        }

        $linksTemplate = $this->linksTemplate;

        $s = $this->startKey;
        $query = $this->prepareQuery($ignoredKeys);
        $start = max(1, ($this->start / $this->stop) + 1);

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
     * Template.
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

        $tpl  = "<ul class=\"{$linksClassName}\">";
        foreach ($links as $link) {
            $tpl .= "<li>{$link}</li>";
        }
        $tpl .= "</ul>";

        return $tpl;
    }

    /**
     * Prepare query.
     * @param  string|null $ignoredKeys
     * @return string
     */
    private function prepareQuery(string $ignoredKeys = null): string
    {
        $tmp = explode('?', $_SERVER['REQUEST_URI'] ?? '', 2);
        $path = $tmp[0];
        $query = trim($tmp[1] ?? '');

        if ($query != '') {
            $query = Util::buildQueryString(
                Util::parseQueryString($query, true),
                true,
                join(',', [$this->startKey, $ignoredKeys])
            );

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
     * Escape.
     * @param  string $input
     * @return string
     * @since  4.0
     */
    private function escape(string $input): string
    {
        if (function_exists('html_encode')) {
            return html_encode($input);
        }
        return str_replace(["'", '"', '<', '>'], ['&#39;', '&#34;', '&lt;', '&gt;'], $input);
    }
}
