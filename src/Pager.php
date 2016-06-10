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

    private $links = [];
    private $linksLimit = 5;
    private $linksLimitMax = 9;

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
     * Constructor.
     */
    final public function __construct()
    {}

    /**
     * Set start.
     * @param  int $start
     * @return void
     */
    final public function setStart(int $start)
    {
        $this->start = abs($start);
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
     * @return void
     */
    final public function setStop(int $stop)
    {
        $this->stop = abs($stop);
        if ($this->stop > $this->stopMax) {
            $this->stop = $this->stopMax;
        }
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
     * @return void
     */
    final public function setStopMax(int $stopMax)
    {
        $this->stopMax = $stopMax;
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
     * @return void
     */
    final public function setStartKey(string $startKey)
    {
        $this->startKey = $startKey;
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
     * @return void
     */
    final public function setStopKey(string $stopKey)
    {
        $this->stopKey = $stopKey;
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
     * @return void
     */
    final public function setTotalPages(int $totalPages)
    {
        $this->totalPages = abs($totalPages);
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
     * @return void
     */
    final public function setTotalRecords(int $totalRecords)
    {
        $this->totalRecords = abs($totalRecords);
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
     * @return void
     */
    final public function setLinksTemplate(array $linksTemplate)
    {
        $this->linksTemplate = array_merge($this->linksTemplate, $linksTemplate);
    }

    /**
     * Run.
     * @return void
     */
    final public function run()
    {
        $app = app();

        // $this->setStart((int) $app->request->params->get($this->startKey));
        // $this->setStop((int) $app->request->params->get($this->stopKey));

        $stop = ($this->stop > 0) ? $this->stop : $this->stopDefault;
        $start = ($this->start > 1) ? $this->start * $stop - $stop : 0;

        $this->stop = $stop;
        $this->start = $start;
        if ($this->totalRecords) {
            $this->totalPages = (int) ceil($this->totalRecords / $this->stop);
        }
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
}
