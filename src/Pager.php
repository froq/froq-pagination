<?php
/**
 * Copyright (c) 2016 Kerem Güneş
 *     <k-gun@mail.com>
 *
 * GNU General Public License v3.0
 *     <http://www.gnu.org/licenses/gpl-3.0.txt>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Froq\Pager;

use Froq\Util\Traits\GetterTrait;

/**
 * @package    Froq
 * @subpackage Froq\Pager
 * @object     Froq\Pager\Pager
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class Pager
{
    /**
     * Getter.
     * @object Froq\Util\Traits\GetterTrait
     */
    use GetterTrait;

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
    private $numerateFirstLast = true;

    /**
     * Aautorun.
     * @var bool
     */
    private $autorun = true;

    /**
     * Constructor.
     */
    final public function __construct()
    {}

    /**
     * Set start.
     * @param  int $start
     * @return self
     */
    final public function setStart(int $start): self
    {
        $this->start = abs($start);

        return $this;
    }

    /**
     * Set stop.
     * @param  int $stop
     * @return self
     */
    final public function setStop(int $stop): self
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
    final public function setStopMax(int $stopMax): self
    {
        $this->stopMax = $stopMax;

        return $this;
    }

    /**
     * Set start key.
     * @param  string $startKey
     * @return self
     */
    final public function setStartKey(string $startKey): self
    {
        $this->startKey = $startKey;

        return $this;
    }

    /**
     * Set stop key.
     * @param  string $stopKey
     * @return self
     */
    final public function setStopKey(string $stopKey): self
    {
        $this->stopKey = $stopKey;

        return $this;
    }

    /**
     * Set total pages.
     * @param  int $totalPages
     * @return self
     */
    final public function setTotalPages(int $totalPages): self
    {
        $this->totalPages = abs($totalPages);

        return $this;
    }

    /**
     * Set total records.
     * @param  int $totalRecords
     * @return self
     */
    final public function setTotalRecords(int $totalRecords): self
    {
        $this->totalRecords = abs($totalRecords);

        return $this;
    }

    /**
     * Set links limit.
     * @param  int $linksLimit
     * @return self
     */
    final public function setLinksLimit(int $linksLimit): self
    {
        $this->linksLimit = $linksLimit;

        return $this;
    }

    /**
     * Set links template.
     * @param  array $linksTemplate
     * @return self
     */
    final public function setLinksTemplate(array $linksTemplate): self
    {
        $this->linksTemplate = array_merge($this->linksTemplate, $linksTemplate);

        return $this;
    }

    /**
     * Set links class name.
     * @param  string $linksClassName
     * @return self
     */
    final public function setLinksClassName(string $linksClassName): self
    {
        $this->linksClassName = $linksClassName;

        return $this;
    }

    /**
     * Set numarate first last.
     * @param  bool $numerateFirstLast
     * @return self
     */
    final public function setNumerateFirstLast(bool $numerateFirstLast): self
    {
        $this->numerateFirstLast = $numerateFirstLast;

        return $this;
    }

    /**
     * Set autorun.
     * @param  bool $autorun
     * @return self
     */
    final public function setAutorun(bool $autorun): self
    {
        $this->autorun = $autorun;

        return $this;
    }

    /**
     * Run.
     * @param  int|null $totalRecords
     * @return array
     */
    final public function run(int $totalRecords = null): array
    {
        if ($totalRecords !== null) {
            $this->setTotalRecords($totalRecords);
        }

        // get params manipulated by developer?
        if ($this->autorun) {
            $app = app();
            $this->setStart((int) $app->request->params->get($this->startKey));
            $this->setStop((int) $app->request->params->get($this->stopKey));
        }

        $stop = ($this->stop > 0) ? $this->stop : $this->stopDefault;
        $start = ($this->start > 1) ? $this->start * $stop - $stop : 0;

        $this->stop = $stop;
        $this->start = $start;
        if ($this->totalRecords) {
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
    final public function template(array $links, string $linksClassName = null): string
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
     * Prepare current URL.
     * @param  string|null $keyIgnored
     * @return string
     */
    final public function prepareCurrentUrl(string $keyIgnored = null): string
    {
        $app = app();

        $url = sprintf('%s://%s%s',
            $app->request->uri->getScheme(),
            $app->request->uri->getHost(),
            $app->request->uri->getPath()
        );

        $query = $app->request->uri->getQuery();
        if ($query) {
            parse_str($query, $query);
            $query = to_query_string($query, "{$this->startKey},{$keyIgnored}");
            if ($query) {
                $query .= '&';
            }
            $url .= '?'. $query;
        }

        return html_encode($url);
    }

    /**
     * Generate links.
     * @param  int|null    $linksLimit
     * @param  string|null $keyIgnored
     * @param  string|null $linksClassName
     * @return string
     */
    final public function generateLinks(int $linksLimit = null, string $keyIgnored = null,
        string $linksClassName = null): string
    {
        // only one page?
        if ($this->totalPages == 1) {
            return $this->template([
                '<a class="current current-one" rel="current" href="#">1</a>'
            ], $className);
        }

        if (!empty($this->links)) {
            return $this->template($this->links, $className);
        }

        // numarate first and last links?
        if (!$this->numerateFirstLast) {
            $this->linksTemplate['first'] = 1;
            $this->linksTemplate['last']  = $this->totalPages;
        }

        $linksLimit = $linksLimit ?? $this->linksLimit;
        if ($linksLimit > $this->totalPages) {
            $linksLimit = $this->totalPages;
        }

        $url = $this->prepareCurrentUrl($keyIgnored);
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
     * @param  string|null $keyIgnored
     * @param  string      $linksClassName
     * @return string
     */
    final public function generateLinksCenter(string $keyIgnored = null,
        $linksClassName = null): string
    {
        if (!empty($this->links)) {
            return $this->template($this->links, $linksClassName);
        }

        $url = $this->prepareCurrentUrl($keyIgnored);
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
