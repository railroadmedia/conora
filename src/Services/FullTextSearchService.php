<?php

namespace Railroad\Railcontent\Services;

use Railroad\Railcontent\Repositories\FullTextSearchRepository;

class FullTextSearchService
{
    /**
     * @var FullTextSearchRepository
     */
    protected $fullTextSearchRepository;

    /**
     * FullTextSearchService constructor.
     *
     * @param FullTextSearchRepository $fullTextSearchRepository
     */
    public function __construct(
        FullTextSearchRepository $fullTextSearchRepository
    ) {
        $this->fullTextSearchRepository = $fullTextSearchRepository;
    }

    /** Full text search by term
     *
     * @param string $term
     * @param int $page
     * @param int $limit
     * @param array $contentTypes
     * @param array $contentStatus
     * @param string $sort
     * @param null $dateTimeCutoff
     * @return array|null
     */
    public function search(
        $term,
        $page = 1,
        $limit = 10,
        $contentTypes = [],
        $contentStatuses = [],
        $sort = '-score',
        $dateTimeCutoff = null
    ) {
        $orderByDirection = substr($sort, 0, 1) !== '-' ? 'asc' : 'desc';
        $orderByColumn = trim($sort, '-');

        return [
            'results' => $this->fullTextSearchRepository->search(
                $term,
                $page,
                $limit,
                $contentTypes,
                $contentStatuses,
                $orderByColumn,
                $orderByDirection,
                $dateTimeCutoff
            ),
            'total_results' => $this->fullTextSearchRepository->countTotalResults(
                $term,
                $contentTypes,
                $contentStatuses,
                $dateTimeCutoff
            )
        ];
    }
}