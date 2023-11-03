<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-pagination
 */
namespace froq\pagination;

use froq\common\trait\OptionTrait;

/**
 * HTML Paginator class with for generating pagination links.
 *
 * @package froq\pagination
 * @class   froq\pagination\Paginator
 * @author  Kerem Güneş
 * @since   1.0, 7.0
 */
class HtmlPaginator extends Paginator
{
    use OptionTrait;

    /** Default options. */
    private static array $optionsDefault = [
        'startKey'          => 's',  // GET param key of start.
        'stopKey'           => 'ss', // GET param key of stop.
        'linkLimit'         => 5,
        'linkTemplate'      => [
            'page'  => 'Page',
            'first' => '&laquo;',  'last' => '&raquo;',
            'next'  => '&rsaquo;', 'prev' => '&lsaquo;',
        ],
        'linkClass'         => 'pagination',
        'numerateFirstLast' => false,
        'argumentSeparator' => '&',
    ];

    /**
     * Constructor.
     *
     * @param int        $page
     * @param int|null   $perPage
     * @param int|null   $perPageMax
     * @param array|null $options
     */
    public function __construct(int $page = 1, int $perPage = self::PER_PAGE, int $perPageMax = self::PER_PAGE_MAX,
        array $options = null)
    {
        parent::__construct($page, $perPage, $perPageMax);

        $this->setOptions($options, self::$optionsDefault);
    }

    /**
     * Generate links (eg: « ‹ 1 2 3 4 5 › »).
     *
     * @param  int|null    $limit
     * @param  string|null $ignoredKeys
     * @return string
     */
    public function generateLinks(int $limit = null, string $ignoredKeys = null): string
    {
        $totalPages = $this->getTotalPages();
        if ($totalPages <= 1) {
            return $this->renderTemplate(['<a class="current" href="#">1</a>']);
        }

        $limit ??= (int) $this->options['linkLimit'];
        if ($limit > $totalPages) {
            $limit = $totalPages;
        }

        $template = (array) $this->options['linkTemplate'];
        if ($this->options['numerateFirstLast']) {
            $template['first'] = 1;
            $template['last']  = $totalPages;
        }

        $uri    = $this->prepareUri($ignoredKeys);
        $skey   = $this->options['startKey'];
        $templ  = $template;

        $start  = $this->getCurrentPage();
        $stop   = $start + $limit;

        $sub    = 1;
        $mid    = (int) ceil($limit / 2);
        $midsub = $mid - $sub;

        // Calculate loop.
        if ($start >= $mid) {
            $i    = $start - $midsub;
            $loop = $stop  - $midsub;
        } else {
            $i    = $sub;
            $loop = ($start === $midsub) ? $stop - $sub : $stop;
            if ($loop >= $limit) {
                $diff = $loop - $limit;
                $loop = $loop - $diff + $sub;
            }
        }

        // First & prev.
        $prev = $start - 1;
        if ($prev >= 1) {
            $links[] = sprintf('<a class="first" rel="first" href="%s%s=1">%s</a>', $uri, $skey, $templ['first']);
            $links[] = sprintf('<a class="prev" rel="prev" href="%s%s=%s">%s</a>', $uri, $skey, $prev, $templ['prev']);
        }

        // Add numbered links.
        for ($i; $i < $loop; $i++) {
            if ($loop <= $totalPages) {
                if ($i === $start) {
                    $links[] = sprintf('<a class="current" href="#">%s</a>', $i);
                } else {
                    $relPrevNext = '';
                    if ($i === $start - 1) {
                        $relPrevNext = ' rel="prev"';
                    } elseif ($i === $start + 1) {
                        $relPrevNext = ' rel="next"';
                    }
                    $links[] = sprintf('<a%s href="%s%s=%s">%s</a>', $relPrevNext, $uri, $skey, $i, $i);
                }
            } else {
                $j = $start;
                $extra = $totalPages - $start;
                if ($extra < $limit) {
                    $j = $j - (($limit - 1) - $extra);
                }

                for ($j; $j <= $totalPages; $j++) {
                    if ($j === $start) {
                        $links[] = sprintf('<a class="current" href="#">%s</a>', $j);
                    } else {
                        $links[] = sprintf('<a rel="next" href="%s%s=%s">%s</a>', $uri, $skey, $j, $j);
                    }
                }
                break;
            }
        }

        // Next & last.
        $next = $start + 1;
        if ($start !== $totalPages) {
            $links[] = sprintf('<a class="next" rel="next" href="%s%s=%s">%s</a>', $uri, $skey, $next, $templ['next']);
            $links[] = sprintf('<a class="last" rel="last" href="%s%s=%s">%s</a>', $uri, $skey, $totalPages, $templ['last']);
        }

        return $this->renderTemplate($links);
    }

    /**
     * Generate centered links (eg: « ‹ Page 3 › »).
     *
     * @param  string|null $ignoredKeys
     * @return string
     */
    public function generateCenteredLinks(string $ignoredKeys = null): string
    {
        $totalPages = $this->getTotalPages();
        if ($totalPages <= 1) {
            return $this->renderTemplate([
                sprintf(
                    '<a class="current" href="#">%s %s</a>',
                    $this->options['linkTemplate']['page'], 1
                )
            ], true);
        }

        $template = (array) $this->options['linkTemplate'];
        if ($this->options['numerateFirstLast']) {
            $template['first'] = 1;
            $template['last']  = $totalPages;
        }

        $uri   = $this->prepareUri($ignoredKeys);
        $skey  = $this->options['startKey'];
        $templ = $template;

        $start = $this->getCurrentPage();

        // First & prev.
        $prev = $start - 1;
        if ($prev >= 1) {
            $links[] = sprintf('<a class="first" rel="first" href="%s%s=1">%s</a>', $uri, $skey, $templ['first']);
            $links[] = sprintf('<a class="prev" rel="prev" href="%s%s=%s">%s</a>', $uri, $skey, $prev, $templ['prev']);
        }

        // Current.
        $links[] = sprintf('<a class="current" href="#">%s %s</a>', $templ['page'], $start);

        // Next & last.
        $next = $start + 1;
        if ($start < $totalPages) {
            $links[] = sprintf('<a class="next" rel="next" href="%s%s=%s">%s</a>', $uri, $skey, $next, $templ['next']);
            $links[] = sprintf('<a class="last" rel="last" href="%s%s=%s">%s</a>', $uri, $skey, $totalPages, $templ['last']);
        }

        return $this->renderTemplate($links, true);
    }

    /**
     * Render generated links in template.
     */
    private function renderTemplate(array $links, bool $center = false): string
    {
        $class = $this->options['linkClass'];
        if ($center) {
            $class .= ' center';
        }

        $ret = "<ul class=\"{$class}\">";
        foreach ($links as $link) {
            $ret .= "<li>{$link}</li>";
        }
        $ret .= "</ul>";

        return $ret;
    }

    /**
     * Prepare URI for templating.
     */
    private function prepareUri(string $ignoredKeys = null): string
    {
        $temp  = explode('?', $_SERVER['REQUEST_URI'] ?? '', 2);
        $path  = trim($temp[0]);
        $query = trim($temp[1] ?? '');

        if ($query !== '') {
            $query = http_parse_query_string($query);

            $keys = $this->options['startKey'];
            if ($ignoredKeys !== null) {
                $keys .= ',' . $ignoredKeys;
            }

            // Drop ignored keys.
            $query = array_unset($query, ...explode(',', $keys));

            $query = http_build_query_string($query);

            if ($query !== '') {
                $query .= $this->options['argumentSeparator'];
            }

            return $this->escape($path) . '?' . $this->escape($query);
        }

        return $this->escape($path) . '?';
    }

    /**
     * Escape path / query.
     */
    private function escape(string $input): string
    {
        return str_replace(
            ["\0", "'", '"', '<', '>'],
            ['', '&#39;', '&#34;', '&lt;', '&gt;'],
            $input
        );
    }
}
