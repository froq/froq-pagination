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

use froq\util\Util;

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
     * Properties.
     * @var array
     * @since 3.0
     */
    private $properties = [
        'start' => 0,
        'stop' => 10,
        'stopMax' => 1000,
        'stopDefault' => 10,
        'startKey' => 's',
        'stopKey' => 'ss',
        'totalPages' => null,
        'totalRecords' => null,
        // 'links' => [],
        // 'linksCenter' => [],
        'linksLimit' => 5,
        'linksTemplate' => [
            'page'  => 'Page',
            'first' => '&laquo;',  'prev' => '&lsaquo;',
            'next'  => '&rsaquo;', 'last' => '&raquo;',
        ],
        'linksClassName' => 'pager',
        'autorun' => true,
        'numerateFirstLast' => false,
    ];

    /**
     * Constructor.
     * @param array|null $properties
     */
    public function __construct(array $properties = null)
    {
        if ($properties != null) {
            $this->properties = array_merge($this->properties, $properties);
        }
    }

    /**
     * Set magic.
     * @param  string $name
     * @param  any    $value
     * @return void
     * @since  3.0
     */
    public function __set(string $name, $value)
    {
        $this->setProperty($name, $value);
    }

    /**
     * Get magic.
     * @param  string $name
     * @return any|null
     * @since  3.0
     */
    public function __get(string $name)
    {
        return $this->getProperty($name);
    }

    /**
     * Set property.
     * @param  string $name
     * @return any    $value
     * @return self
     * @since  3.0
     */
    public function setProperty(string $name, $value): self
    {
        if (strpos($name, '_')) { // camelize
            $name = preg_replace_callback('~_([a-z])~', function($match) {
                return ucfirst($match[1]);
            }, strtolower($name));
        }

        if (!array_key_exists($name, $this->properties)) {
            throw new PagerException("No properties found such '{$name}'");
        }

        static $intProperties = ['start', 'stop', 'stopMax', 'stopDefault', 'totalPages', 'totalRecords', 'linksLimit'];
        static $boolProperties = ['autorun', 'numerateFirstLast'];

        if (in_array($name, $intProperties)) {
            $value = (int) abs($value);
        } elseif (is_array($name, $boolProperties)) {
            $value = (bool) $value;
        }

        if ($name == 'stop' && $value > $this->properties['stopMax']) {
            $value = $this->properties['stopMax'];
        }

        $this->properties[$name] = $value;

        return $this;
    }

    /**
     * Get property.
     * @param  string $name
     * @return any|null
     * @since  3.0
     */
    public function getProperty(string $name)
    {
        if (strpos($name, '_')) { // camelize
            $name = preg_replace_callback('~_([a-z])~', function($match) {
                return ucfirst($match[1]);
            }, strtolower($name));
        }

        if (!array_key_exists($name, $this->properties)) {
            throw new PagerException("No property found such '{$name}'");
        }

        return $this->properties[$name];
    }

    /**
     * Get start stop.
     * @return array
     * @since  3.0
     */
    public function getStartStop(): array
    {
        return [$this->properties['start'], $this->properties['stop']];
    }

    /**
     * Run.
     * @param  int|null    $totalRecords
     * @param  string|null $startKey
     * @param  string|null $stopKey
     * @return array
     */
    public function run(int $totalRecords = null, string $startKey = null, string $stopKey = null): array
    {
        if ($totalRecords !== null) {
            $this->setProperty('totalRecords', $totalRecords);
        }

        $startKey && $this->setProperty('startKey', $startKey);
        $stopKey && $this->setProperty('stopKey', $stopKey);

        // get params manipulated by developer?
        if ($this->properties['autorun']) {
            $this->setProperty('start', $_GET[$this->properties['startKey']] ?? 0);
            $this->setProperty('stop', $_GET[$this->properties['stopKey']] ?? 0);
        }

        ['start' => $start, 'stop' => $stop, 'stopDefault' => $stopDefault,
            'totalRecords' => $totalRecords] = $this->properties;

        $stop = ($stop > 0) ? $stop : $stopDefault;
        $start = ($start > 1) ? ($start * $stop) - $stop : 0;

        $this->properties['stop'] = $stop;
        $this->properties['start'] = $start;
        if ($totalRecords > 0) {
            $this->setProperty('totalPages', ceil($totalRecords / $stop));
        }

        return $this->getStartStop();
    }

    /**
     * Generate links.
     * @param  int|null    $linksLimit
     * @param  string|null $ignoredKeys
     * @param  string|null $linksClassName
     * @return string
     */
    public function generateLinks(int $linksLimit = null, string $ignoredKeys = null,
        string $linksClassName = null): string
    {
        // only one page?
        $totalPages = $this->properties['totalPages'];
        if ($totalPages == 1) {
            return $this->template(['<a class="current current-one" rel="current" href="#">1</a>'],
                $linksClassName);
        }

        $links =@ (array) $this->properties['links'];
        if ($links != null) {
            return $this->template($links, $linksClassName);
        }

        ['linksTemplate' => $linksTemplate, 'start' => $start, 'stop' => $stop,
            'startKey' => $startKey] = $this->properties;

        $numerateFirstLast = $this->properties['numerateFirstLast'];
        if (!$numerateFirstLast) {
            $linksTemplate['first'] = 1;
            $linksTemplate['last']  = $totalPages;
        }

        $linksLimit = $linksLimit ?? $this->properties['linksLimit'];
        if ($linksLimit > $totalPages) {
            $linksLimit = $totalPages;
        }

        $url = $this->prepareCurrentUrl($ignoredKeys);
        $start = max(1, ($start / $stop) + 1);
        $stop = $start + $linksLimit;

        // calculate loop
        $sub = 1;
        $middle = ceil($linksLimit / 2);
        $middleSub = $middle - $sub;
        if ($start >= $middle) {
            $i = $start - $middleSub;
            $loop = $stop - $middleSub;
        } else {
            $i = $sub;
            $loop = $start == $middleSub ? $stop - $sub : $stop;
            if ($loop >= $linksLimit) {
                $diff = $loop - $linksLimit;
                $loop = $loop - $diff + $sub;
            }
        }

        // add first & prev links
        $prev = $start - 1;
        if ($prev >= 1) {
            $links[] = sprintf('<a class="first" rel="first" href="%s%s=1">%s</a>', $url, $startKey,
                $linksTemplate['first']);
            $links[] = sprintf('<a class="prev" rel="prev" href="%s%s=%s">%s</a>', $url, $startKey,
                $prev, $linksTemplate['prev']);
        }

        // add numbered links
        for ($i; $i < $loop; $i++) {
            if ($loop <= $totalPages) {
                if ($i == $start) {
                    $links[] = '<a class="current" rel="current" href="#">'. $i .'</a>';
                } else {
                    $relPrevNext = '';
                    if ($i == $start - 1) {
                        $relPrevNext = ' rel="prev"';
                    } elseif ($i == $start + 1) {
                        $relPrevNext = ' rel="next"';
                    }
                    $links[] = sprintf('<a%s href="%s%s=%s">%s</a>', $relPrevNext, $url, $startKey,
                        $i, $i);
                }
            } else {
                $j = $start;
                $extra = $totalPages - $start;
                if ($extra < $linksLimit) {
                    $j = $j - (($linksLimit - 1) - $extra);
                }

                for ($j; $j <= $totalPages; $j++) {
                    if ($j == $start) {
                        $links[] = '<a class="current" rel="current" href="#">'. $i .'</a>';
                    } else {
                        $links[] = sprintf('<a rel="next" href="%s%s=%s">%s</a>', $url, $startKey,
                            $j, $j);
                    }
                }
                break;
            }
        }

        // add next & last link
        $next = $start + 1;
        if ($start != $totalPages) {
            $links[] = sprintf('<a class="next" rel="next" href="%s%s=%s">%s</a>', $url, $startKey,
                $next, $linksTemplate['next']);
            $links[] = sprintf('<a class="last" rel="last" href="%s%s=%s">%s</a>', $url, $startKey,
                $totalPages, $linksTemplate['last']);
        }

        // store
        $this->properties['links'] = $links;

        return $this->template($links, $linksClassName);
    }

    /**
     * Generate links center.
     * @param  string|null $ignoredKeys
     * @param  string      $linksClassName
     * @return string
     */
    public function generateLinksCenter(string $ignoredKeys = null, $linksClassName = null): string
    {
        // only one page?
        $totalPages = $this->properties['totalPages'];
        if ($totalPages == 1) {
            return $this->template(['<a class="current current-one" rel="current" href="#">1</a>'],
                $linksClassName);
        }

        $links =@ (array) $this->properties['linksCenter'];
        if ($links != null) {
            return $this->template($links, $linksClassName);
        }

        ['linksTemplate' => $linksTemplate, 'start' => $start, 'stop' => $stop,
            'startKey' => $startKey] = $this->properties;

        $url = $this->prepareCurrentUrl($ignoredKeys);
        $start = max(1, ($start / $stop) + 1);

        // add first & prev links
        $prev = $start - 1;
        if ($prev >= 1) {
            $links[] = sprintf('<a class="first" rel="first" href="%s%s=1">%s</a>', $url, $startKey,
                $linksTemplate['first']);
            $links[] = sprintf('<a class="prev" rel="prev" href="%s%s=%s">%s</a>', $url, $startKey,
                $prev, $linksTemplate['prev']);
        }

        $links[] = sprintf('<a class="current" rel="current" href="#">%s %s</a>',
            $linksTemplate['page'], $start);

        // add next & last link
        $next = $start + 1;
        if ($start < $totalPages) {
            $links[] = sprintf('<a class="next" rel="next" href="%s%s=%s">%s</a>', $url, $startKey,
                $next, $linksTemplate['next']);
            $links[] = sprintf('<a class="last" rel="last" href="%s%s=%s">%s</a>', $url, $startKey,
                $totalPages, $linksTemplate['last']);
        }

        // store
        $this->properties['linksCenter'] = $links;

        return $this->template($links, $linksClassName);
    }

    /**
     * Template.
     * @param  array       $links
     * @param  string|null $linksClassName
     * @return string
     */
    private function template(array $links, string $linksClassName = null): string
    {
        $linksClassName = $linksClassName ?? $this->properties['linksClassName'];

        $tpl  = "<ul class=\"{$linksClassName}\">";
        foreach ($links as $link) {
            $tpl .= "<li>{$link}</li>";
        }
        $tpl .= "</ul>";

        return $tpl;
    }

    /**
     * Prepare current url.
     * @param  string|null $ignoredKeys
     * @return string
     */
    private function prepareCurrentUrl(string $ignoredKeys = null): string
    {
        $url = Util::getCurrentUrl(false);
        $urlQuery = $_SERVER['QUERY_STRING'] ?? '';

        if ($urlQuery != '') {
            parse_str($urlQuery, $query);
            $query = to_query_string($query, "{$this->properties['startKey']},{$ignoredKeys}");
            if ($query != '') {
                $query .= '&';
            }
            $url .= '?'. html_encode($query);
        } else {
            $url .= '?';
        }

        return $url;
    }
}
