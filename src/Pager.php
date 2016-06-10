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
     * Links limit max.
     * @var int
     */
    private $linksLimitMax = 9;

    /**
     * Links template.
     * @var array
     */
    private $linksTemplate = [
        'page'  => 'Page',
        'first' => '&laquo;',
        'prev'  => '&lsaquo;',
        'next'  => '&rsaquo;',
        'last'  => '&raquo;'
    ];

    /**
     * Links class name.
     * @var string
     */
    private $linksClassName = 'pager';

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
     * Get start.
     * @return int
     */
    final public function getStart(): int
    {
        return $this->start;
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
     * Get stop.
     * @return int
     */
    final public function getStop(): int
    {
        return $this->stop;
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
     * Get stop max.
     * @return int
     */
    final public function getStopMax(): int
    {
        return $this->stopMax;
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
     * Get start key.
     * @return string
     */
    final public function getStartKey(): string
    {
        return $this->startKey;
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
     * Get stop key.
     * @return string
     */
    final public function getStopKey(): string
    {
        return $this->stopKey;
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
     * Get total pages.
     * @return int|null
     */
    final public function getTotalPages()
    {
        return $this->totalPages;
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
     * Get total records.
     * @return int|null
     */
    final public function getTotalRecords()
    {
        return $this->totalRecords;
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
     * Get autorun.
     * @return bool
     */
    final public function getAutorun(): bool
    {
        return $this->autorun;
    }

    /**
     * Run.
     * @return array
     */
    final public function run(): array
    {
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
     * Get URL.
     * @param  string|null $keyIgnored
     * @return string
     */
    final public function getUrl(string $keyIgnored = null): string
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
    {}

    /**
     * Generate links center.
     * @param  string|null $keyIgnored
     * @param  string      $linksClassName
     * @return string
     */
    final public function generateLinksCenter(string $keyIgnored = null,
        $linksClassName = null): string
    {
        $url = $this->getUrl($keyIgnored);
        $start = (($start = (int) app()->request->params->get($this->startKey)) > 1) ? $start : 1;

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

        // return template
        return $this->template($this->links, $linksClassName);
    }
}
