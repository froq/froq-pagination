<?php declare(strict_types=1);
/**
 * Copyright (c) 2015 · Kerem Güneş
 * Apache License 2.0 · http://github.com/froq/froq-pagination
 */
namespace froq\pagination;

use froq\common\interface\{Arrayable, Objectable};

/**
 * Paginator class.
 *
 * @package froq\pagination
 * @class   froq\pagination\Paginator
 * @author  Kerem Güneş
 * @since   7.0
 */
class Paginator implements Arrayable, Objectable, \JsonSerializable
{
    /** Default per-page. */
    public const PER_PAGE = 10;

    /** Maximum per-page. */
    public const PER_PAGE_MAX = 1000;

    /** Current page. */
    private int $page;

    /** Page limit. */
    private int $perPage;

    /** Page limit max. */
    private int $perPageMax = 1000;

    /** Previous page. */
    private ?int $prevPage = null;

    /** Next page. */
    private ?int $nextPage = null;

    /** Total pages. */
    private ?int $totalPages = null;

    /** Total records. */
    private ?int $totalRecords = null;

    /**
     * Constructor.
     *
     * @param int      $page
     * @param int|null $perPage
     * @param int|null $perPageMax
     */
    public function __construct(int $page = 1, int $perPage = self::PER_PAGE, int $perPageMax = self::PER_PAGE_MAX)
    {
        $this->setPage($page)
             ->setPerPage($perPage)
             ->setPerPageMax($perPageMax);
    }

    /**
     * Set page.
     *
     * @param  int $page
     * @return self
     */
    public function setPage(int $page): self
    {
        $this->page = max(1, abs($page));

        return $this;
    }

    /**
     * Get page.
     *
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * Set per-page.
     *
     * @param  int $perPage
     * @return self
     */
    public function setPerPage(int $perPage): self
    {
        $this->perPage = min($this->perPageMax, abs($perPage));

        return $this;
    }

    /**
     * Get per-page.
     *
     * @return int
     */
    public function getPerPage(): int
    {
        return $this->perPage;
    }

    /**
     * Set per-page max.
     *
     * @param  int $perPageMax
     * @return self
     */
    public function setPerPageMax(int $perPageMax): self
    {
        $this->perPageMax = abs($perPageMax);

        return $this;
    }

    /**
     * Get per-page max.
     *
     * @return int
     */
    public function getPerPageMax(): int
    {
        return $this->perPageMax;
    }

    /**
     * @alias getPage()
     */
    public function getCurrentPage()
    {
        return $this->getPage();
    }

    /**
     * Get previous page.
     *
     * @return int|null
     */
    public function getPrevPage(): int|null
    {
        return $this->prevPage;
    }

    /**
     * Get next page.
     *
     * @return int|null
     */
    public function getNextPage(): int|null
    {
        return $this->nextPage;
    }

    /**
     * Get total pages.
     *
     * @return int|null
     */
    public function getTotalPages(): int|null
    {
        return $this->totalPages;
    }

    /**
     * Get total records.
     *
     * @return int|null
     */
    public function getTotalRecords(): int|null
    {
        return $this->totalRecords;
    }

    /**
     * Paginate.
     *
     * @param  int $totalRecords
     * @return self
     */
    public function paginate(int $totalRecords): self
    {
        $this->totalPages   = 1;
        $this->totalRecords = $totalRecords;

        if ($this->totalRecords > 1) {
            $this->totalPages = (int) ceil($this->totalRecords / $this->perPage);
        }

        $this->prevPage = ($this->page - 1 >= 1) ? $this->page - 1 : null;
        $this->nextPage = ($this->page + 1 <= $this->totalPages) ? $this->page + 1 : null;

        return $this;
    }

    /**
     * @inheritDoc froq\common\interface\Arrayable
     */
    public function toArray(): array
    {
        return [
            'page'         => $this->page,       'perPage'      => $this->perPage,
            'prevPage'     => $this->prevPage,   'nextPage'     => $this->nextPage,
            'totalPages'   => $this->totalPages, 'totalRecords' => $this->totalRecords,
        ];
    }

    /**
     * @inheritDoc froq\common\interface\Objectable
     */
    public function toObject(): object
    {
        return (object) $this->toArray();
    }

    /**
     * @inheritDoc JsonSerializable
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
