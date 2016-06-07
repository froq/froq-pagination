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

/**
 * @package    Froq
 * @subpackage Froq\Pager
 * @object     Froq\Pager\Pager
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class Pager
{
    private $start = 0;
    private $stop = 10;
    private $stopMax = 1000;
    private $stopDefault = 10;
    private $startKey = 's';
    private $stopKey = 'ss';
    private $totalPages;
    private $totalRecords;

    final public function __construct()
    {}

    final public function setStart(int $start)
    {
        $this->start = abs($start);
    }
    final public function getStart(): int
    {
        return $this->start;
    }

    final public function setStop(int $stop)
    {
        $this->stop = abs($stop);
        if ($this->stop > $this->stopMax) {
            $this->stop = $this->stopMax;
        }
    }
    final public function getStop(): int
    {
        return $this->stop;
    }

    final public function setStopMax(int $stopMax)
    {
        $this->stopMax = abs($stopMax);
    }

    final public function setStartKey(string $startKey)
    {
        $this->startKey = $startKey;
    }
    final public function getStartKey(): string
    {
        return $this->startKey;
    }

    final public function setStopKey(string $stopKey)
    {
        $this->stopKey = $stopKey;
    }
    final public function getStopKey(): string
    {
        return $this->stopKey;
    }

    final public function setTotalPages(int $totalPages)
    {
        $this->totalPages = abs($totalPages);
    }

    final public function setTotalRecords(int $totalRecords)
    {
        $this->totalRecords = abs($totalRecords);
    }

    final public function run()
    {
        $app = app();

        $this->setStart((int) $app->request->params->get($this->startKey));
        $this->setStop((int) $app->request->params->get($this->stopKey));

        $stop = ($this->stop > 0) ? $this->stop : $this->stopDefault;
        $start = ($this->start > 1) ? $this->start * $stop - $stop : 0;

        $this->stop = $stop;
        $this->start = $start;
        if ($this->totalRecords) {
            $this->totalPages = (int) ceil($this->totalRecords / $this->stop);
        }

        pre($this);
    }
}
