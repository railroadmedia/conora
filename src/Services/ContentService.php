<?php

namespace Railroad\Railcontent\Services;

use Carbon\Carbon;
use Railroad\Railcontent\Decorators\Decorator;
use Railroad\Railcontent\Entities\ContentEntity;
use Railroad\Railcontent\Entities\ContentFilterResultsEntity;
use Railroad\Railcontent\Events\ContentCreated;
use Railroad\Railcontent\Events\ContentDeleted;
use Railroad\Railcontent\Events\ContentFieldUpdated;
use Railroad\Railcontent\Events\ContentSoftDeleted;
use Railroad\Railcontent\Events\ContentUpdated;
use Railroad\Railcontent\Events\HierarchyUpdated;
//use Railroad\Railcontent\Events\XPModified;
use Railroad\Railcontent\Helpers\CacheHelper;
use Railroad\Railcontent\Helpers\ContentHelper;
use Railroad\Railcontent\Repositories\CommentAssignmentRepository;
use Railroad\Railcontent\Repositories\CommentRepository;
use Railroad\Railcontent\Repositories\ContentDatumRepository;
use Railroad\Railcontent\Repositories\ContentFieldRepository;
use Railroad\Railcontent\Repositories\ContentHierarchyRepository;
use Railroad\Railcontent\Repositories\ContentPermissionRepository;
use Railroad\Railcontent\Repositories\ContentRepository;
use Railroad\Railcontent\Repositories\ContentVersionRepository;
use Railroad\Railcontent\Repositories\UserContentProgressRepository;
use Railroad\Railcontent\Support\Collection;

class ContentService
{
    /**
     * @var ContentRepository
     */
    private $contentRepository;

    /**
     * @var ContentVersionRepository
     */
    private $versionRepository;

    /**
     * @var ContentFieldRepository
     */
    private $fieldRepository;

    /**
     * @var ContentDatumRepository
     */
    private $datumRepository;

    /**
     * @var ContentHierarchyRepository
     */
    private $contentHierarchyRepository;

    /**
     * @var CommentRepository
     */
    private $commentRepository;

    /**
     * @var CommentAssignmentRepository
     */
    private $commentAssignationRepository;

    /**
     * @var ContentPermissionRepository
     */
    private $contentPermissionRepository;

    /**
     * @var UserContentProgressRepository
     */
    private $userContentProgressRepository;

    // all possible content statuses
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_ARCHIVED = 'archived';
    const STATUS_DELETED = 'deleted';

    private $idContentCache = [];

    /**
     * ContentService constructor.
     *
     * @param ContentRepository $contentRepository
     * @param ContentVersionRepository $versionRepository
     * @param ContentFieldRepository $fieldRepository
     * @param ContentDatumRepository $datumRepository
     * @param ContentHierarchyRepository $contentHierarchyRepository
     * @param ContentPermissionRepository $contentPermissionRepository
     * @param CommentRepository $commentRepository
     * @param CommentAssignmentRepository $commentAssignmentRepository
     * @param UserContentProgressRepository $userContentProgressRepository
     */
    public function __construct(
        ContentRepository $contentRepository,
        ContentVersionRepository $versionRepository,
        ContentFieldRepository $fieldRepository,
        ContentDatumRepository $datumRepository,
        ContentHierarchyRepository $contentHierarchyRepository,
        ContentPermissionRepository $contentPermissionRepository,
        CommentRepository $commentRepository,
        CommentAssignmentRepository $commentAssignmentRepository,
        UserContentProgressRepository $userContentProgressRepository
    ) {
        $this->contentRepository = $contentRepository;
        $this->versionRepository = $versionRepository;
        $this->fieldRepository = $fieldRepository;
        $this->datumRepository = $datumRepository;
        $this->contentHierarchyRepository = $contentHierarchyRepository;
        $this->contentPermissionRepository = $contentPermissionRepository;
        $this->commentRepository = $commentRepository;
        $this->commentAssignationRepository = $commentAssignmentRepository;
        $this->userContentProgressRepository = $userContentProgressRepository;
    }

    /**
     * Call the get by id method from repository and return the content
     *
     * @param integer $id
     * @return ContentEntity|array|null
     */
    public function getById($id)
    {
        $hash = 'contents_by_id_' . CacheHelper::getKey($id);

        if (isset($this->idContentCache[$hash])) {
            return $this->idContentCache[$hash];
        }

        $results = CacheHelper::getCachedResultsForKey($hash);

        if (!$results) {
            $results = CacheHelper::saveUserCache($hash, $this->contentRepository->getById($id), [$id]);
        }

        $this->idContentCache[$hash] = Decorator::decorate($results, 'content');

        return $this->idContentCache[$hash];
    }

    /**
     * Call the get by ids method from repository
     *
     * @param integer[] $ids
     * @return array|Collection|ContentEntity[]
     */
    public function getByIds($ids)
    {
        $hash = 'contents_by_ids_' . CacheHelper::getKey(...$ids);

        if (isset($this->idContentCache[$hash])) {
            return $this->idContentCache[$hash];
        }

        $results = CacheHelper::getCachedResultsForKey($hash);

        if (!$results) {
            $results = CacheHelper::saveUserCache($hash, $this->contentRepository->getByIds($ids));
        }

        $this->idContentCache[$hash] = Decorator::decorate($results, 'content');

        return $this->idContentCache[$hash];
    }

    /**
     * Get all contents with specified type.
     *
     * @param string $type
     * @return array|Collection|ContentEntity[]
     */
    public function getAllByType($type)
    {
        $hash = 'contents_by_type_' . $type . '_' . CacheHelper::getKey($type);

        $results = CacheHelper::getCachedResultsForKey($hash);

        if (!$results) {
            $results = CacheHelper::saveUserCache($hash, $this->contentRepository->getByType($type));
        }

        return Decorator::decorate($results, 'content');
    }

    /**
     * Get all contents with specified status, field and type.
     *
     * @param array $types
     * @param string $status
     * @param string $fieldKey
     * @param string $fieldValue
     * @param string $fieldType
     * @param string $fieldComparisonOperator
     * @return array|Collection|ContentEntity[]
     */
    public function getWhereTypeInAndStatusAndField(
        array $types,
        $status,
        $fieldKey,
        $fieldValue,
        $fieldType,
        $fieldComparisonOperator = '='
    ) {
        $hash = 'contents_by_types_field_and_status_' . CacheHelper::getKey(
                $types,
                $status,
                $fieldKey,
                $fieldValue,
                $fieldType,
                $fieldComparisonOperator
            );

        $results = CacheHelper::getCachedResultsForKey($hash);

        if (!$results) {
            $results = CacheHelper::saveUserCache(
                $hash,
                $this->contentRepository->getWhereTypeInAndStatusAndField(
                    $types,
                    $status,
                    $fieldKey,
                    $fieldValue,
                    $fieldType,
                    $fieldComparisonOperator
                )
            );
        }

        return Decorator::decorate($results, 'content');
    }

    /**
     * Get ordered contents by type, status and published_on date.
     *
     * @param array $types
     * @param string $status
     * @param string $publishedOnValue
     * @param string $publishedOnComparisonOperator
     * @param string $orderByColumn
     * @param string $orderByDirection
     * @return array|Collection|ContentEntity[]
     */
    public function getWhereTypeInAndStatusAndPublishedOnOrdered(
        array $types,
        $status,
        $publishedOnValue,
        $publishedOnComparisonOperator = '=',
        $orderByColumn = 'published_on',
        $orderByDirection = 'desc'
    ) {
        return Decorator::decorate(
            $this->contentRepository->getWhereTypeInAndStatusAndPublishedOnOrdered(
                $types,
                $status,
                $publishedOnValue,
                $publishedOnComparisonOperator,
                $orderByColumn,
                $orderByDirection
            ),
            'content'
        );
    }

    /**
     * Get contents by slug and title.
     *
     * @param string $slug
     * @param string $type
     * @return array|Collection|ContentEntity[]
     */
    public function getBySlugAndType($slug, $type)
    {
        $hash = 'contents_by_slug_type_' . $type . '_' . CacheHelper::getKey($slug, $type);
        $results = CacheHelper::getCachedResultsForKey($hash);

        if (!$results) {
            $results = CacheHelper::saveUserCache($hash, $this->contentRepository->getBySlugAndType($slug, $type));
        }

        return Decorator::decorate($results, 'content');
    }

    /**
     * Get contents by userId, type and slug.
     *
     * @param integer $userId
     * @param string $type
     * @param string $slug
     * @return array|Collection|ContentEntity[]
     */
    public function getByUserIdTypeSlug($userId, $type, $slug)
    {
        $hash = 'contents_by_user_slug_type_' . $type . '_' . CacheHelper::getKey($userId, $type, $slug);
        $results = CacheHelper::getCachedResultsForKey($hash);

        if (!$results) {
            $results =
                CacheHelper::saveUserCache($hash, $this->contentRepository->getByUserIdTypeSlug($userId, $type, $slug));
        }

        return Decorator::decorate($results, 'content');
    }

    /**
     * Get contents based on parent id.
     *
     * @param integer $parentId
     * @param string $orderBy
     * @param string $orderByDirection
     * @return array|Collection|ContentEntity[]
     */
    public function getByParentId($parentId, $orderBy = 'child_position', $orderByDirection = 'asc')
    {
        $hash = 'contents_by_parent_id_' . CacheHelper::getKey($parentId, $orderBy, $orderByDirection);
        $results = CacheHelper::getCachedResultsForKey($hash);

        if (!$results) {
            $resultsDB = $this->contentRepository->getByParentId($parentId, $orderBy, $orderByDirection);
            $results =
                CacheHelper::saveUserCache($hash, $resultsDB, array_merge(array_pluck($resultsDB, 'id'), [$parentId]));
        }

        return Decorator::decorate($results, 'content');
    }

    /**
     * Get paginated contents by parent id.
     *
     * @param integer $parentId
     * @param string $orderBy
     * @param string $orderByDirection
     * @return array|Collection|ContentEntity[]
     */
    public function getByParentIdPaginated(
        $parentId,
        $limit = 10,
        $skip = 0,
        $orderBy = 'child_position',
        $orderByDirection = 'asc'
    ) {
        $hash =
            'contents_by_parent_id_paginated_' .
            CacheHelper::getKey($parentId, $limit, $skip, $orderBy, $orderByDirection);

        $results = CacheHelper::getCachedResultsForKey($hash);

        if (!$results) {
            $resultsDB = $this->contentRepository->getByParentIdPaginated(
                $parentId,
                $limit,
                $skip,
                $orderBy,
                $orderByDirection
            );
            $results =
                CacheHelper::saveUserCache($hash, $resultsDB, array_merge(array_pluck($resultsDB, 'id'), [$parentId]));
        }

        return Decorator::decorate($results, 'content');
    }

    /**
     * Get ordered contents by parent id with specified type.
     *
     * @param integer $parentId
     * @param array $types
     * @param string $orderBy
     * @param string $orderByDirection
     * @return array|Collection|ContentEntity[]
     */
    public function getByParentIdWhereTypeIn(
        $parentId,
        $types,
        $orderBy = 'child_position',
        $orderByDirection = 'asc'
    ) {
        $hash = 'contents_by_parent_id_type_' . CacheHelper::getKey($parentId, $types, $orderBy, $orderByDirection);
        $results = CacheHelper::getCachedResultsForKey($hash);

        if (!$results) {
            $resultsDB = $this->contentRepository->getByParentIdWhereTypeIn(
                $parentId,
                $types,
                $orderBy,
                $orderByDirection
            );
            $results =
                CacheHelper::saveUserCache($hash, $resultsDB, array_merge(array_pluck($resultsDB, 'id'), [$parentId]));
        }

        return Decorator::decorate($results, 'content');
    }

    /**
     * Get ordered contents by parent id and type.
     *
     * @param integer $parentId
     * @param array $types
     * @param string $orderBy
     * @param string $orderByDirection
     * @return array|Collection|ContentEntity[]
     */
    public function getByParentIdWhereTypeInPaginated(
        $parentId,
        $types,
        $limit = 10,
        $skip = 0,
        $orderBy = 'child_position',
        $orderByDirection = 'asc'
    ) {
        $hash = 'contents_by_parent_id_type_in_' . CacheHelper::getKey(
                $parentId,
                $types,
                $limit,
                $skip,
                $orderBy,
                $orderByDirection
            );
        $results = CacheHelper::getCachedResultsForKey($hash);

        if (!$results) {
            $resultsDB = $this->contentRepository->getByParentIdWhereTypeInPaginated(
                $parentId,
                $types,
                $limit,
                $skip,
                $orderBy,
                $orderByDirection
            );
            $results =
                CacheHelper::saveUserCache($hash, $resultsDB, array_merge(array_pluck($resultsDB, 'id'), [$parentId]));
        }

        return Decorator::decorate($results, 'content');
    }

    /**
     * Count contents with specified type and parent id.
     *
     * @param integer $parentId
     * @param array $types
     * @return integer
     */
    public function countByParentIdWhereTypeIn(
        $parentId,
        $types
    ) {
        return $this->contentRepository->countByParentIdWhereTypeIn(
            $parentId,
            $types
        );
    }

    /**
     * Get ordered contents based on parent ids.
     *
     * @param array $parentIds
     * @param string $orderBy
     * @param string $orderByDirection
     * @return array|Collection|ContentEntity[]
     */
    public function getByParentIds(array $parentIds, $orderBy = 'child_position', $orderByDirection = 'asc')
    {
        $hash = 'contents_by_parent_ids_' . CacheHelper::getKey($parentIds, $orderBy, $orderByDirection);
        $results = CacheHelper::getCachedResultsForKey($hash);

        if (!$results) {
            $resultsDB = $this->contentRepository->getByParentIds($parentIds, $orderBy, $orderByDirection);
            $results =
                CacheHelper::saveUserCache($hash, $resultsDB, array_merge(array_pluck($resultsDB, 'id'), $parentIds));
        }

        return Decorator::decorate($results, 'content');
    }

    /**
     * Get contents by child and type.
     *
     * @param integer $childId
     * @param string $type
     * @return array|Collection|ContentEntity[]
     */
    public function getByChildIdWhereType($childId, $type)
    {
        $hash = 'contents_by_child_id_and_type_' . $type . '_' . CacheHelper::getKey($childId, $type);
        $results = CacheHelper::getCachedResultsForKey($hash);

        if (!$results) {
            $resultsDB = $this->contentRepository->getByChildIdWhereType($childId, $type);
            $results =
                CacheHelper::saveUserCache($hash, $resultsDB, array_merge(array_pluck($resultsDB, 'id'), [$childId]));
        }

        return Decorator::decorate($results, 'content');
    }

    /**
     * Get contents by child ids with specified type.
     *
     * @param array $childIds
     * @param string $type
     * @return array|Collection|ContentEntity[]
     */
    public function getByChildIdsWhereType(array $childIds, $type)
    {
        $hash = 'contents_by_child_ids_and_type_' . $type . '_' . CacheHelper::getKey($childIds, $type);
        $results = CacheHelper::getCachedResultsForKey($hash);

        if (!$results) {
            $resultsDB = $this->contentRepository->getByChildIdsWhereType($childIds, $type);
            $results =
                CacheHelper::saveUserCache($hash, $resultsDB, array_merge(array_pluck($resultsDB, 'id'), $childIds));
        }

        return Decorator::decorate($results, 'content');
    }

    /**
     * Get contents by child ids with specified type.
     *
     * @param array $childIds
     * @param string $type
     * @return array|Collection|ContentEntity[]
     */
    public function getByChildIdsWhereTypeForUrl(array $childIds, $type)
    {
        $hash = 'contents_by_child_ids_and_type_for_url_' . $type . '_' . CacheHelper::getKey($childIds, $type);
        $results = CacheHelper::getCachedResultsForKey($hash);

        if (!$results) {
            $resultsDB = $this->contentRepository->getByChildIdsWhereTypeForUrl($childIds, $type);
            $results =
                CacheHelper::saveUserCache($hash, $resultsDB, array_merge(array_pluck($resultsDB, 'id'), $childIds));
        }

        return Decorator::decorate($results, 'content');
    }

    /**
     * Get contents by child id where parent type met the criteria.
     *
     * @param integer $childId
     * @param array $types
     * @return array|Collection|ContentEntity[]
     */
    public function getByChildIdWhereParentTypeIn($childId, array $types)
    {
        $hash = 'contents_by_child_ids_and_parent_types_' . CacheHelper::getKey($childId, $types);
        $results = CacheHelper::getCachedResultsForKey($hash);

        if (is_null($results)) {
            $resultsDB = $this->contentRepository->getByChildIdWhereParentTypeIn($childId, $types);
            $results =
                CacheHelper::saveUserCache($hash, $resultsDB, array_merge(array_pluck($resultsDB, 'id'), [$childId]));
        }

        return Decorator::decorate($results, 'content');
    }

    /**
     * Get paginated contents by type and user progress state.
     *
     * @param string $type
     * @param integer $userId
     * @param string $state
     * @param int $limit
     * @param int $skip
     * @return array|Collection|ContentEntity[]
     */
    public function getPaginatedByTypeUserProgressState($type, $userId, $state, $limit = 25, $skip = 0)
    {
        $hash = 'contents_paginated_by_type_' . $type . '_and_user_progress_' . $userId . '_' . CacheHelper::getKey(
                $type,
                $userId,
                $state,
                $limit,
                $skip
            );
        $results = CacheHelper::getCachedResultsForKey($hash);

        if (!$results) {
            $results = CacheHelper::saveUserCache(
                $hash,
                $this->contentRepository->getPaginatedByTypeUserProgressState(
                    $type,
                    $userId,
                    $state,
                    $limit,
                    $skip
                )
            );
        }

        return Decorator::decorate($results, 'content');
    }

    /**
     * Get paginated contents by types and user progress state.
     *
     * @param array $types
     * @param integer $userId
     * @param string $state
     * @param int $limit
     * @param int $skip
     * @return array|Collection|ContentEntity[]
     */
    public function getPaginatedByTypesUserProgressState(
        array $types,
        $userId,
        $state,
        $limit = 25,
        $skip = 0
    ) {
        $hash = 'contents_paginated_by_types_and_user_progress_' . $userId . '_' . CacheHelper::getKey(
                $types,
                $userId,
                $state,
                $limit,
                $skip
            );
        $results = CacheHelper::getCachedResultsForKey($hash);

        if (!$results) {
            $results = CacheHelper::saveUserCache(
                $hash,
                $this->contentRepository->getPaginatedByTypesUserProgressState(
                    $types,
                    $userId,
                    $state,
                    $limit,
                    $skip
                )
            );
        }

        return Decorator::decorate($results, 'content');
    }

    /**
     * Get recent paginated contents by types and user progress state.
     *
     * @param array $types
     * @param integer $userId
     * @param string $state
     * @param int $limit
     * @param int $skip
     * @return array|Collection|ContentEntity[]
     */
    public function getPaginatedByTypesRecentUserProgressState(
        array $types,
        $userId,
        $state,
        $limit = 25,
        $skip = 0
    ) {
        $hash = 'contents_paginated_by_types_and_user_progress_' . $userId . '_' . CacheHelper::getKey(
                $types,
                $userId,
                $state,
                $limit,
                $skip
            );
        $results = CacheHelper::getCachedResultsForKey($hash);

        if (!$results) {
            $results = CacheHelper::saveUserCache(
                $hash,
                $this->contentRepository->getPaginatedByTypesRecentUserProgressState(
                    $types,
                    $userId,
                    $state,
                    $limit,
                    $skip
                )
            );
        }

        return Decorator::decorate($results, 'content');
    }

    /**
     * Count recent contents with user progress state and type.
     *
     * @param array $types
     * @param integer $userId
     * @param string $state
     * @param int $limit
     * @param int $skip
     * @return array|Collection|ContentEntity[]
     */
    public function countByTypesRecentUserProgressState(
        array $types,
        $userId,
        $state
    ) {
        return $this->contentRepository->countByTypesUserProgressState(
            $types,
            $userId,
            $state
        );
    }

    /**
     * Get neighbouring siblings by type.
     *
     * @param string $type
     * @param string $columnName
     * @param string $columnValue
     * @param int $siblingPairLimit
     * @param string $orderColumn
     * @param string $orderDirection
     * @return array|ContentEntity|Collection
     */
    public function getTypeNeighbouringSiblings(
        $type,
        $columnName,
        $columnValue,
        $siblingPairLimit = 1,
        $orderColumn = 'published_on',
        $orderDirection = 'desc'
    ) {
        $hash = 'contents_type_neighbouring_siblings_' . CacheHelper::getKey(
                $type,
                $columnName,
                $columnValue,
                $siblingPairLimit,
                $orderColumn,
                $orderDirection
            );

        // $5 sez we can remove this
        $this->contentRepository->getTypeNeighbouringSiblings(
            $type,
            $columnName,
            $columnValue,
            $siblingPairLimit,
            $orderColumn,
            $orderDirection
        );
        $results = CacheHelper::getCachedResultsForKey($hash);

        if (!$results) {
            $results = CacheHelper::saveUserCache(
                $hash,
                $this->contentRepository->getTypeNeighbouringSiblings(
                    $type,
                    $columnName,
                    $columnValue,
                    $siblingPairLimit,
                    $orderColumn,
                    $orderDirection
                )
            );
        }

        $results['before'] = Decorator::decorate($results['before'], 'content');
        $results['after'] = Decorator::decorate($results['after'], 'content');

        return $results;
    }

    /**
     * Count contents by type and user progress state.
     *
     * @param array $types
     * @param integer $userId
     * @param string $state
     * @return integer
     */
    public function countByTypesUserProgressState(array $types, $userId, $state)
    {
        $results = $this->contentRepository->countByTypesUserProgressState(
            $types,
            $userId,
            $state
        );

        return $results;
    }

    /**
     * Get filtered contents.
     * Returns:
     * ['results' => $lessons, 'total_results' => $totalLessonsAfterFiltering]
     *
     * @param int $page
     * @param int $limit
     * @param string $orderByAndDirection
     * @param array $includedTypes
     * @param array $slugHierarchy
     * @param array $requiredParentIds
     * @param array $requiredFields
     * @param array $includedFields
     * @param array $requiredUserStates
     * @param array $includedUserStates
     * @param boolean $pullFilterFields
     * @param bool $getFutureContentOnly
     * @param bool $pullPagination
     * @return ContentFilterResultsEntity
     */
    public function getFiltered(
        $page,
        $limit,
        $orderByAndDirection = '-published_on',
        array $includedTypes = [],
        array $slugHierarchy = [],
        array $requiredParentIds = [],
        array $requiredFields = [],
        array $includedFields = [],
        array $requiredUserStates = [],
        array $includedUserStates = [],
        $pullFilterFields = true,
        $getFutureContentOnly = false,
        $pullPagination = true
    ) {
        $results = null;
        if ($limit == 'null') {
            $limit = -1;
        }

        $orderByDirection = substr($orderByAndDirection, 0, 1) !== '-' ? 'asc' : 'desc';
        $orderByColumn = trim($orderByAndDirection, '-');

        $hash = 'contents_results_' . CacheHelper::getKey(
                $page,
                $limit,
                $orderByColumn,
                $orderByDirection,
                implode(' ', array_values($includedTypes) ?? ''),
                implode(' ', array_values($slugHierarchy) ?? ''),
                implode(' ', array_values($requiredParentIds) ?? ''),
                implode(' ', array_values($requiredFields) ?? ''),
                implode(' ', array_values($includedFields) ?? ''),
                implode(' ', array_values($requiredUserStates) ?? ''),
                implode(' ', array_values($includedUserStates) ?? ''),
                ContentRepository::$bypassPermissions
            );

        $cache = CacheHelper::getCachedResultsForKey($hash);

        if ($cache) {
            $results = new ContentFilterResultsEntity($cache);
        }

        if (!$results) {
            $filter = $this->contentRepository->startFilter(
                $page,
                $limit,
                $orderByColumn,
                $orderByDirection,
                $includedTypes,
                $slugHierarchy,
                $requiredParentIds,
                $getFutureContentOnly
            );

            foreach ($requiredFields as $requiredField) {
                $filter->requireField(
                    ...
                    (is_array($requiredField) ? $requiredField : explode(',', $requiredField))
                );
            }

            foreach ($includedFields as $includedField) {
                $filter->includeField(
                    ...
                    (is_array($includedField) ? $includedField : explode(',', $includedField))
                );
            }

            foreach ($requiredUserStates as $requiredUserState) {
                $filter->requireUserStates(
                    ...
                    is_array($requiredUserState) ? $requiredUserState : explode(',', $requiredUserState)
                );
            }

            foreach ($includedUserStates as $includedUserState) {
                $filter->includeUserStates(
                    ...
                    is_array($includedUserState) ? $includedUserState : explode(',', $includedUserState)
                );
            }

            $resultsDB = new ContentFilterResultsEntity(
                [
                    'results' => $filter->retrieveFilter(),
                    'total_results' => $pullPagination ? $filter->countFilter() : 0,
                    'filter_options' => $pullFilterFields ? $filter->getFilterFields() : [],
                ]
            );

            $results = CacheHelper::saveUserCache($hash, $resultsDB, array_pluck($resultsDB['results'], 'id'));
            $results = new ContentFilterResultsEntity($results);
        }

        return Decorator::decorate($results, 'content');
    }

    /**
     * Call the create method from ContentRepository and return the new created content
     *
     * @param string $slug
     * @param string $type
     * @param string $status
     * @param string|null $language
     * @param string|null $brand
     * @param int|null $userId
     * @param string|null $publishedOn
     * @param int|null $parentId
     * @param int $sort
     * @return array|ContentEntity
     */
    public function create(
        $slug,
        $type,
        $status,
        $language,
        $brand,
        $userId,
        $publishedOn,
        $parentId = null,
        $sort = 0
    ) {
        $slug = ContentHelper::slugify($slug);

        $id = $this->contentRepository->create(
            [
                'slug' => $slug,
                'type' => $type,
                'sort' => $sort,
                'status' => $status ?? self::STATUS_DRAFT,
                'language' => $language ?? ConfigService::$defaultLanguage,
                'brand' => $brand ?? ConfigService::$brand,
                'total_xp' => $this->getDefaultXP($type, 0),
                'user_id' => $userId,
                'published_on' => $publishedOn,
                'created_on' => Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        //save the link with parent if the parent id exist on the request
        if ($parentId) {
            $this->contentHierarchyRepository->updateOrCreateChildToParentLink(
                $parentId,
                $id,
                null
            );

            event(new HierarchyUpdated($parentId));
        }

        CacheHelper::deleteUserFields(null, 'contents');

        event(new ContentCreated($id));

        return $this->getById($id);
    }

    /**
     * Update and return the updated content.
     *
     * @param integer $id
     * @param array $data
     * @return array|ContentEntity
     */
    public function update($id, array $data)
    {
        $content = $this->contentRepository->getById($id);
        if (empty($content)) {
            return null;
        }

        $this->contentRepository->update($id, $data);

        event(new ContentUpdated($id, $content, $data));

        CacheHelper::deleteCache('content_' . $id);

        if (array_key_exists('status', $data)) {
            CacheHelper::deleteUserFields(null, 'contents');
        }

        return $this->getById($id);
    }

    /**
     * Call the delete method from repository and returns true if the content was deleted
     *
     * @param $id
     * @return bool|null - if the content not exist
     */
    public function delete($id)
    {
        $content = $this->contentRepository->getById($id);

        if (empty($content)) {
            return null;
        }
        event(new ContentDeleted($id));

        CacheHelper::deleteCache('content_' . $id);

        return $this->contentRepository->delete($id);
    }

    /**
     * Delete data related with the specified content id.
     *
     * @param integer $contentId
     */
    public function deleteContentRelated($contentId)
    {
        //delete the link with the parent and reposition other siblings
        $this->contentHierarchyRepository->deleteChildParentLinks($contentId);

        //delete the content children
        $this->contentHierarchyRepository->deleteParentChildLinks($contentId);

        //delete the content fields
        $this->fieldRepository->deleteByContentId($contentId);

        //delete the content datum
        $this->datumRepository->deleteByContentId($contentId);

        //delete the links with the permissions
        $this->contentPermissionRepository->deleteByContentId($contentId);

        //delete the content comments, replies and assignation
        $comments = $this->commentRepository->getByContentId($contentId);

        $this->commentAssignationRepository->deleteCommentAssignations(array_pluck($comments, 'id'));

        $this->commentRepository->deleteByContentId($contentId);

        //delete content playlists
        $this->userContentProgressRepository->deleteByContentId($contentId);
    }

    /**
     * @param $userId
     * @param array $contents
     * @param null $singlePlaylistSlug
     * @return array|Collection|ContentEntity[]
     */
    public function attachChildrenToContents($userId, $contents, $singlePlaylistSlug = null)
    {
        $isArray = !isset($contents['id']);

        if (!$isArray) {
            $contents = [$contents];
        }

        $userPlaylistContents = $this->contentRepository->getByUserIdWhereChildIdIn(
            $userId,
            array_column($contents, 'id'),
            $singlePlaylistSlug
        );

        foreach ($contents as $index => $content) {
            $contents[$index]['user_playlists'][$userId] = [];
            foreach ($userPlaylistContents as $userPlaylistContent) {
                if ($userPlaylistContent['parent_id'] == $content['id']) {
                    $contents[$index]['user_playlists'][$userId][] = $userPlaylistContent;
                }
            }
        }

        if ($isArray) {
            return $contents;
        } else {
            return reset($contents);
        }
    }

    /**
     * Call the update method from repository to mark the content as deleted and returns true if the content was updated
     *
     * @param $id
     * @return bool|null - if the content not exist
     */
    public function softDelete($id)
    {
        $content = $this->getById($id);

        if (empty($content)) {
            return null;
        }

        event(new ContentSoftDeleted($id));

        CacheHelper::deleteCache('content_' . $id);

        return $this->contentRepository->softDelete([$id]);
    }

    /**
     * Soft delete the children for specified content id.
     *
     * @param int $id
     * @return int
     */
    public function softDeleteContentChildren($id)
    {
        $children = $this->contentHierarchyRepository->getByParentIds([$id]);

        //delete parent content cache
        CacheHelper::deleteCache('content_' . $id);

        return $this->contentRepository->softDelete(array_pluck($children, 'child_id'));
    }

    /**
     * Get contents by field value and types.
     *
     * @param array $contentTypes
     * @param string $contentFieldKey
     * @param array $contentFieldValues
     * @return mixed
     */
    public function getByContentFieldValuesForTypes(
        array $contentTypes,
        $contentFieldKey,
        array $contentFieldValues = []
    ) {
        $hash = 'contents_by_types_and_field_value_' . CacheHelper::getKey(
                $contentTypes,
                $contentFieldKey,
                $contentFieldValues
            );
        $results = CacheHelper::getCachedResultsForKey($hash);

        if (!$results) {
            $results = CacheHelper::saveUserCache(
                $hash,
                $this->contentRepository->getByContentFieldValuesForTypes(
                    $contentTypes,
                    $contentFieldKey,
                    $contentFieldValues
                )
            );
        }

        return Decorator::decorate($results, 'content');
    }

    /**
     * @param null $brand
     * @param bool $includeSemesterPackLessons
     * @return Collection|ContentEntity[]
     */
    public function getContentForCalendar($brand = null, $includeSemesterPackLessons = true)
    {
        $liveEventsTypes = array_merge(
            (array)config('railcontent.showTypes', []),
            (array)config('railcontent.liveContentTypes', [])
        );
        $contentReleasesTypes = array_merge(
            (array)config('railcontent.showTypes', []),
            (array)config('railcontent.contentReleaseContentTypes', [])
        );

        if ($includeSemesterPackLessons) {
            $parents = [];
            $idsOfChildrenOfSelectSemesterPacks = [];
            $culledSemesterPackLessons = [];
        }

        $brand = $brand ?? ConfigService::$brand;

        $compareFunc = function ($a, $b) {
            return Carbon::parse($a['published_on']) >= Carbon::parse($b['published_on']);
        };

        $oldStatuses = ContentRepository::$availableContentStatues;
        $oldFutureContent = ContentRepository::$pullFutureContent;

        ContentRepository::$availableContentStatues = [
            ContentService::STATUS_PUBLISHED,
            ContentService::STATUS_SCHEDULED,
        ];

        ContentRepository::$pullFutureContent = true;

        if ($includeSemesterPackLessons) {
            $semesterPacksToGet = config('railcontent.semester-pack-schedule-labels.' . $brand, []);
        }

        if (empty($liveEventsTypes) && empty($contentReleasesTypes) && empty($semesterPacksToGet)) {
            return new Collection();
        }

        if ($includeSemesterPackLessons) {
            foreach ($semesterPacksToGet ?? [] as $slug => $kebabCaseLabel) {
                $parents[] = $this->getSemesterPackParent($slug);
            }
        }

        $liveEvents = $this->getWhereTypeInAndStatusAndPublishedOnOrdered(
            $liveEventsTypes,
            ContentService::STATUS_SCHEDULED,
            Carbon::now()
                ->toDateTimeString(),
            '>'
        );

        $contentReleases = $this->getWhereTypeInAndStatusAndPublishedOnOrdered(
            $contentReleasesTypes,
            ContentService::STATUS_PUBLISHED,
            Carbon::now()
                ->toDateTimeString(),
            '>'
        );

        /*
         * -------------------------------------------------------------------------------------------------------------
         *
         * The following sections are fairly (and therefore maybe *overly*) complex (aka messy).
         * These comment will explain the sections. They are labels as they represent a singluar concern and are thus
         * best addressed with single explanatory comment-block.
         *
         *      Jonathan, January 2019
         *
         * -------------------------------------------------------------------------------------------------------------
         *
         * Section One:
         *
         *      Get just ids of semester-packs-lessons, keyed by their parents' slug.
         *
         *      We could also get the lesson objects themselves here, but that's not important. What really matters is
         *      that we know which lessons belong in select semester-packs, which we get here.
         *
         *      We key them by slug, because in Section Three we'll need the slug to set a "user-friendly label".
         *
         * Section Two:
         *
         *      Get information about which semester-pack-lessons are eligible to be be shown in the schedule based on
         *      status (published rather than draft or deleted), and release date (no reason to show already-released
         *      content).
         *
         *      We also get the actual lesson objects here, but that's not as important because we could do that
         *      anywhere really.
         *
         * Section Three:
         *
         *      Now we combine these two pieces of information to have a set of only the relevant semester-pack-lessons.
         *
         *      We set the 'type' to a value determined by the parent-slug (see Section One above) because these are all
         *      semester-pack-lessons, and the schedule page displays each items' content as a label. If we didn't do
         *      this here, then the label showing would be "SEMESTER-PACK-LESSON". That wouldn't do at all.
         */

        if ($includeSemesterPackLessons) {
            /*
             * Section One
             */
            foreach ($parents ?? [] as $parent) {
                foreach (
                    $this->getByParentIdWhereTypeIn($parent['id'], ['semester-pack-lesson'])
                        ->all() as $lesson
                ) {
                    $idsOfChildrenOfSelectSemesterPacks[$parent['slug']][] = $lesson['id'];
                }
            }

            /*
             * Section Two
             */
            $semesterPackLessons = $this->getWhereTypeInAndStatusAndPublishedOnOrdered(
                ['semester-pack-lesson'],
                ContentService::STATUS_PUBLISHED,
                Carbon::now()
                    ->toDateTimeString(),
                '>'
            );

            /*
             * Section Three
             */

            $culledSemesterPackLessons = new Collection();

            foreach ($semesterPackLessons as $lesson) {
                foreach ($idsOfChildrenOfSelectSemesterPacks ?? [] as $parentSlug => $setOfIds) {
                    if (in_array($lesson['id'], $setOfIds)) {
                        $labels = config('railcontent.semester-pack-schedule-labels.' . $brand);
                        if (array_key_exists($parentSlug, $labels)) {
                            $result =
                                $this->getByChildIdWhereParentTypeIn($lesson['id'], ['semester-pack'])
                                    ->first();
                            $lesson['parent_id'] = $result['id'];
                            //$culledSemesterPackLessons[$labels[$parentSlug]] = $lesson;
                            $culledSemesterPackLessons[] = $lesson;
                        }
                    }
                }
            }
        }

        $scheduleEvents =
            $liveEvents->merge($contentReleases)
                ->sort($compareFunc)
                ->values();

        if ($includeSemesterPackLessons) {
            $culledSemesterPackLessons = collect($culledSemesterPackLessons ?? []);
            $scheduleEvents =
                $scheduleEvents->merge($culledSemesterPackLessons)
                    ->sort($compareFunc)
                    ->values();
        } else {
            $scheduleEvents =
                $scheduleEvents->sort($compareFunc)
                    ->values();
        }

        ContentRepository::$availableContentStatues = $oldStatuses;
        ContentRepository::$pullFutureContent = $oldFutureContent;

        if (empty($scheduleEvents)) {
            return new Collection();
        }

        foreach ($scheduleEvents as $key => $content) {
            $contentBrand = $content['brand'];
            $incorrectBrand = $contentBrand !== $brand;
            if ($incorrectBrand) {
                $keysToUnset[] = $key;
            }
        }

        foreach ($keysToUnset ?? [] as $key) {
            unset($scheduleEvents[$key]);
        }

        return $scheduleEvents;
    }

    /**
     * @param $parentSlug
     * @return mixed
     */
    public function getSemesterPackParent($parentSlug)
    {
        $before = ContentRepository::$availableContentStatues;

        if (empty(ContentRepository::$availableContentStatues)) {
            ContentRepository::$availableContentStatues = [
                ContentService::STATUS_PUBLISHED,
                ContentService::STATUS_SCHEDULED,
            ];
        }

        $setWithThis = array_merge(
            ContentRepository::$availableContentStatues,
            [ContentService::STATUS_DRAFT]
        );

        // include drafts because packs might not be published, but we still want to get their *lessons*
        ContentRepository::$availableContentStatues = $setWithThis;

        $result = $this->getBySlugAndType($parentSlug, 'semester-pack');
        $result = $result->first();

        ContentRepository::$availableContentStatues = $before;

        return $result;
    }

    /**
     * @param array $types
     * @param string $groupBy
     * @return mixed
     */
    public function countByTypes(array $types, $groupBy = '')
    {
        $hash = 'count_by_types_' . CacheHelper::getKey(implode($types), $groupBy);

        if (isset($this->idContentCache[$hash])) {
            return $this->idContentCache[$hash];
        }

        $results = CacheHelper::getCachedResultsForKey($hash);

        if (!$results) {
            $results = CacheHelper::saveUserCache($hash, $this->contentRepository->countByTypes($types, $groupBy));
        }

        $this->idContentCache[$hash] = $results;

        return $this->idContentCache[$hash];
    }

    /**
     * @param $userId
     * @param $state
     * @return array
     */
    public function getFiltersForUserProgressState($userId, $state)
    {
        return $this->contentRepository->getFiltersUserProgressState($userId, $state);
    }

    public function getByChildId($childId)
    {
        $hash = 'contents_by_child_id_' . '_' . CacheHelper::getKey($childId);
        $results = CacheHelper::getCachedResultsForKey($hash);

        if (!$results) {
            $resultsDB = $this->contentRepository->getByChildId($childId);
            $results =
                CacheHelper::saveUserCache($hash, $resultsDB, array_merge(array_pluck($resultsDB, 'id'), [$childId]));
        }

        return Decorator::decorate($results, 'content');
    }

    /**
     * @param $contentId
     * @return array|ContentEntity
     */
    public function calculateTotalXp($contentId)
    {
        $content = $this->getById($contentId);
        $children = $this->getByParentId($content['id']);

        $childrenTotalXP = 0;
        foreach ($children as $child) {
            $childrenTotalXP += $child->fetch(
                'total_xp',
                $this->getDefaultXP($child['type'], $child->fetch('fields.difficulty', 0))
            );
        }

        $contentTotalXp = $content->fetch(
                'fields.xp',
                $this->getDefaultXP($content['type'], $content->fetch('fields.difficulty', 0))
            ) + $childrenTotalXP;

        return $this->update($contentId, ['total_xp' => $contentTotalXp]);
    }

    /**
     * @param $contentIds
     * @return array
     */
    public function calculateTotalXpForContents($contentIds)
    {
        $contentTotalXp = [];

        $children =
            $this->getByParentIds($contentIds)
                ->groupBy('parent_id');

        $childrenTotalXP = array_fill_keys(array_keys($children->toArray()), 0);

        foreach ($children as $parentId => $child) {
            foreach ($child as $childO) {
                $childDifficulty = ContentHelper::getFieldValue([$childO['id']], 'difficulty');
                $childrenTotalXP[$parentId] += $childO['total_xp']
                    ??
                    $this->getDefaultXP($childO['type'], $childDifficulty ?? 0);
            }
        }

        $parentsCollection = new Collection($this->contentRepository->getByIds($contentIds));
        $parents =
            $parentsCollection->keyBy('id')
                ->toArray();

        foreach ($parents as $id => $content) {
            $contentDifficulty = ContentHelper::getFieldValue([$content['id']], 'difficulty');
            $childrenXp = $childrenTotalXP[$id] ?? 0;
            $contentTotalXp[$id] =
                ($content['xp'] ?? $this->getDefaultXP($content['type'], $contentDifficulty ?? 0)) + $childrenXp;
        }

        return $contentTotalXp;
    }

    public function getDefaultXP($type, $difficulty)
    {
        if ($type == 'pack') {
            $defaultXp = config('xp_ranks.pack_content_completed');
        } elseif ($type == 'pack_bundle') {
            $defaultXp = config('xp_ranks.pack_bundle_content_completed');
        } elseif ($type == 'learning_path') {
            $defaultXp = config('xp_ranks.learning_path_content_completed');
        } elseif ($type == 'course') {
            $defaultXp = config('xp_ranks.course_content_completed');
        } elseif ($type == 'assignment') {
            $defaultXp = config('xp_ranks.assignment_content_completed');
        } else {
            $defaultXp = config('xp_ranks.difficulty_xp_map')[$difficulty] ?? config('xp_ranks.difficulty_xp_map.all');
        }

        return $defaultXp;
    }

}