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

namespace Froq\Pager;

use Froq\Util\Util;
use Froq\Util\Traits\GetTrait;

/**
 * @package    Froq
 * @subpackage Froq\Pager
 * @object     Froq\Pager\Pager
 * @author     Kerem Güneş <k-gun@mail.com>
 * @since      1.0
 */
final class Pager
{
    /**
     * Getter.
     * @object Froq\Util\Traits\GetTrait
     */
    use GetTrait;

    /**
     * Start.
     * @var int
     */
    private $start = 0;

    /**
     * Stop.
     * @var int
     */
    private $stop = 10;

    /**
     * Stop max.
     * @var int
     */
    private $stopMax = 1000;

    /**
     * Stop default.
     * @var int
     */
    private $stopDefault = 10;

    /**
     * Start key.
     * @var string
     */
    private $startKey = 's';

    /**
     * Stop key.
     * @var string
     */
    private $stopKey = 'ss';

    /**
     * Total pages.
     * @var int|null
     */
    private $totalPages;

    /**
     * Total records.
     * @var int|null
     */
    private $totalRecords;

    /**
     * Links.
     * @var array
     */
    private $links = [];

    /**
     * Links limit.
     * @var int
     */
    private $linksLimit = 5;

    /**
     * Links template.
     * @var array
     */
    private $linksTemplate = [
        'page'  => 'Page',
        'first' => '&laquo;',
        'prev'  => '&lsaquo;',
        'next'  => '&rsaquo;',
        'last'  => '&raquo;',
    ];

    /**
     * Links class name.
     * @var string
     */
    private $linksClassName = 'pager';

    /**
     * Numarate first last.
     * @var bool
     */
    private $numerateFirstLast = false;

    /**
     * Aautorun.
     * @var bool
     */
    private $autorun = true;

    /**
     * Constructor.
     */
    public function __construct()
    {}

    /**
     * Set start.
     * @param  int $start
     * @return self
     */
    public function setStart(int $start): self
    {
        $this->start = abs($start);

        return $this;
    }

    /**
     * Set stop.
     * @param  int $stop
     * @return self
     */
    public function setStop(int $stop): self
    {
        $this->stop = abs($stop);
        if ($this->stop > $this->stopMax) {
            $this->stop = $this->stopMax;
        }

        return $this;
    }

    /**
     * Set stop max.
     * @param  int $stopMax
     * @return self
     */
    public function setStopMax(int $stopMax): self
    {
        $this->stopMax = $stopMax;

        return $this;
    }

    /**
     * Set start key.
     * @param  string $startKey
     * @return self
     */
    public function setStartKey(string $startKey): self
    {
        $this->startKey = $startKey;

        return $this;
    }

    /**
     * Set stop key.
     * @param  string $stopKey
     * @return self
     */
    public function setStopKey(string $stopKey): self
    {
        $this->stopKey = $stopKey;

        return $this;
    }

    /**
     * Set total pages.
     * @param  int $totalPages
     * @return self
     */
    public function setTotalPages(int $totalPages): self
    {
        $this->totalPages = abs($totalPages);

        return $this;
    }

    /**
     * Set total records.
     * @param  int $totalRecords
     * @return self
     */
    public function setTotalRecords(int $totalRecords): self
    {
        $this->totalRecords = abs($totalRecords);

        return $this;
    }

    /**
     * Set links limit.
     * @param  int $linksLimit
     * @return self
     */
    public function setLinksLimit(int $linksLimit): self
    {
        $this->linksLimit = $linksLimit;

        return $this;
    }

    /**
     * Set links template.
     * @param  array $linksTemplate
     * @return self
     */
    public function setLinksTemplate(array $linksTemplate): self
    {
        $this->linksTemplate = array_merge($this->linksTemplate, $linksTemplate);

        return $this;
    }

    /**
     * Set links class name.
     * @param  string $linksClassName
     * @return self
     */
    public function setLinksClassName(string $linksClassName): self
    {
        $this->linksClassName = $linksClassName;

        return $this;
    }

    /**
     * Set numarate first last.
     * @param  bool $numerateFirstLast
     * @return self
     */
    public function setNumerateFirstLast(bool $numerateFirstLast): self
    {
        $this->numerateFirstLast = $numerateFirstLast;

        return $this;
    }

    /**
     * Set autorun.
     * @param  bool $autorun
     * @return self
     */
    public function setAutorun(bool $autorun): self
    {
        $this->autorun = $autorun;

        return $this;
    }

    /**
     * Run.
     * @param  int|null $totalRecords
     * @return array
     */
    public function run(int $totalRecords = null): array
    {
        if ($totalRecords !== null) {
            $this->setTotalRecords($totalRecords);
        }

        // get params manipulated by developer?
        if ($this->autorun) {
            $this->setStart((int) ($_GET[$this->startKey] ?? 0));
            $this->setStop((int) ($_GET[$this->stopKey] ?? 0));
        }

        $stop = ($this->stop > 0) ? $this->stop : $this->stopDefault;
        $start = ($this->start > 1) ? $this->start * $stop - $stop : 0;

        $this->stop = $stop;
        $this->start = $start;
        if ($this->totalRecords > 0) {
            $this->setTotalPages((int) ceil($this->totalRecords / $this->stop));
        }

        return [$this->start, $this->stop];
    }

    /**
     * Template.
     * @param  array       $links
     * @param  string|null $linksClassName
     * @return string
     */
    public function template(array $links, string $linksClassName = null): string
    {
        $linksClassName = $linksClassName ?? $this->linksClassName;

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
    public function prepareCurrentUrl(string $ignoredKeys = null): string
    {
        $url = Util::getCurrentUrl(false);
        $urlQuery = $_SERVER['QUERY_STRING'] ?? '';

        if ($urlQuery != '') {
            parse_str($urlQuery, $query);
            $query = to_query_string($query, "{$this->startKey},{$ignoredKeys}");
            if ($query != '') {
                $query .= '&';
            }
            $url .= '?'. html_encode($query);
        } else {
            $url .= '?';
        }

        return $url;
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
        if ($this->totalPages == 1) {
            return $this->template(['<a class="current current-one" rel="current" href="#">1</a>'],
                $className);
        }

        if (!empty($this->links)) {
            return $this->template($this->links, $className);
        }

        // numarate first and last links?
        if ($this->numerateFirstLast) {
            $this->linksTemplate['first'] = 1;
            $this->linksTemplate['last']  = $this->totalPages;
        }

        $linksLimit = $linksLimit ?? $this->linksLimit;
        if ($linksLimit > $this->totalPages) {
            $linksLimit = $this->totalPages;
        }

        $url = $this->prepareCurrentUrl($ignoredKeys);
        $start = max(1, $this->start / $this->stop + 1);
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
            $this->links[] = sprintf('<a class="first" rel="first" href="%s%s=1">%s</a>',
                $url, $this->startKey, $this->linksTemplate['first']);
            $this->links[] = sprintf('<a class="prev" rel="prev" href="%s%s=%s">%s</a>',
                $url, $this->startKey, $prev, $this->linksTemplate['prev']);
        }

        // add numbered links
        for ($i; $i < $loop; $i++) {
            if ($loop <= $this->totalPages) {
                if ($i == $start) {
                    $this->links[] = '<a class="current" rel="current" href="#">'. $i .'</a>';
                } else {
                    $relPrevNext = '';
                    if ($i == $start - 1) {
                        $relPrevNext = ' rel="prev"';
                    } elseif ($i == $start + 1) {
                        $relPrevNext = ' rel="next"';
                    }
                    $this->links[] = sprintf('<a%s href="%s%s=%s">%s</a>',
                        $relPrevNext, $url, $this->startKey, $i, $i);
                }
            } else {
                $j = $start;
                $extra = $this->totalPages - $start;
                if ($extra < $linksLimit) {
                    $j = $j - (($linksLimit - 1) - $extra);
                }

                for ($j; $j <= $this->totalPages; $j++) {
                    if ($j == $start) {
                        $this->links[] = '<a class="current" rel="current" href="#">'. $i .'</a>';
                    } else {
                        $this->links[] = sprintf('<a rel="next" href="%s%s=%s">%s</a>',
                            $url, $this->startKey, $j, $j);
                    }
                }
                break;
            }
        }

        // add next & last link
        $next = $start + 1;
        if ($start != $this->totalPages) {
            $this->links[] = sprintf('<a class="next" rel="next" href="%s%s=%s">%s</a>',
                $url, $this->startKey, $next, $this->linksTemplate['next']);
            $this->links[] = sprintf('<a class="last" rel="last" href="%s%s=%s">%s</a>',
                $url, $this->startKey, $this->totalPages, $this->linksTemplate['last']);
        }

        return $this->template($this->links, $linksClassName);
    }

    /**
     * Generate links center.
     * @param  string|null $ignoredKeys
     * @param  string      $linksClassName
     * @return string
     */
    public function generateLinksCenter(string $ignoredKeys = null, $linksClassName = null): string
    {
        if (!empty($this->links)) {
            return $this->template($this->links, $linksClassName);
        }

        $url = $this->prepareCurrentUrl($ignoredKeys);
        $start = max(1, $this->start / $this->stop + 1);

        // add first & prev links
        $prev = $start - 1;
        if ($prev >= 1) {
            $this->links[] = sprintf('<a class="first" rel="first" href="%s%s=1">%s</a>',
                $url, $this->startKey, $this->linksTemplate['first']);
            $this->links[] = sprintf('<a class="prev" rel="prev" href="%s%s=%s">%s</a>',
                $url, $this->startKey, $prev, $this->linksTemplate['prev']);
        }

        $this->links[] = sprintf('<a class="current" rel="current" href="#">%s %s</a>',
            $this->linksTemplate['page'], $start);

        // add next & last link
        $next = $start + 1;
        if ($start < $this->totalPages) {
            $this->links[] = sprintf('<a class="next" rel="next" href="%s%s=%s">%s</a>',
                $url, $this->startKey, $next, $this->linksTemplate['next']);
            $this->links[] = sprintf('<a class="last" rel="last" href="%s%s=%s">%s</a>',
                $url, $this->startKey, $this->totalPages, $this->linksTemplate['last']);
        }

        return $this->template($this->links, $linksClassName);
    }
}
