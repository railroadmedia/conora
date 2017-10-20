<?php

namespace Railroad\Railcontent\Repositories;

use Carbon\Carbon;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Railroad\Railcontent\Services\ConfigService;

class ContentRepository extends RepositoryBase
{
    /**
     * If this is false content with any status will be pulled. If its an array, only content with those
     * statuses will be pulled.
     *
     * @var array|bool
     */
    public static $availableContentStatues = false;

    /**
     * If this is false content with any language will be pulled. If its an array, only content with those
     * languages will be pulled.
     *
     * @var array|bool
     */
    public static $includedLanguages = false;

    /**
     * Determines whether content with a published_on date in the future will be pulled or not.
     *
     * @var array|bool
     */
    public static $pullFutureContent = true;

    /**
     * @var PermissionRepository
     */
    private $permissionRepository;

    /**
     * @var FieldRepository
     */
    private $fieldRepository;

    /**
     * @var DatumRepository
     */
    private $datumRepository;

    /**
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * ContentRepository constructor.
     *
     * @param PermissionRepository $permissionRepository
     * @param FieldRepository $fieldRepository
     * @param DatumRepository $datumRepository
     */
    public function __construct(
        PermissionRepository $permissionRepository,
        FieldRepository $fieldRepository,
        DatumRepository $datumRepository,
        DatabaseManager $databaseManager
    )
    {
        parent::__construct();

        $this->permissionRepository = $permissionRepository;
        $this->fieldRepository = $fieldRepository;
        $this->datumRepository = $datumRepository;
        $this->databaseManager = $databaseManager;
    }

    /**
     * Call the get by id method from repository and return the category
     *
     * @param integer $id
     * @return array|null
     */
    public function getById($id)
    {
        return (array)$this->baseQuery()
            ->where([ConfigService::$tableContent.'.id' => $id])
            ->get()
            ->first();
    }

    /**
     * @param string $slug
     * @return array|null
     */
    public function getBySlug($slug)
    {
        // todo: write function
    }

    /**
     * Returns an array of lesson data arrays.
     *
     * You can switch the field pulling style between inclusive and exclusive by changing the $subLimitQuery
     * between whereIn and orWhereIn.
     *
     * @param $page
     * @param $limit
     * @param $orderBy
     * @param $orderDirection
     * @param array $types
     * @param array $requiredFields
     * @return array|null
     */
    public function getFiltered(
        $page,
        $limit,
        $orderBy,
        $orderDirection,
        array $types,
        array $requiredFields
    ) {
        // options:
        // style: drilldown
        // style: build up

        $subLimitQuery = $this->baseQuery(false)
            ->select(ConfigService::$tableContent . '.id as id')
            ->whereIn(ConfigService::$tableContent . '.type', $types)
            ->whereExists(
                function (Builder $builder) {
                    return $builder
                        ->select([ConfigService::$tableFields . '.id'])
                        ->from(ConfigService::$tableContentFields)
                        ->join(
                            ConfigService::$tableFields,
                            ConfigService::$tableFields . '.id',
                            '=',
                            ConfigService::$tableContentFields . '.field_id'
                        )
                        ->where(
                            [
                                ConfigService::$tableFields . '.key' => 'difficulty',
                                ConfigService::$tableFields . '.value' => 'beginner',
                                ConfigService::$tableContentFields .
                                '.content_id' => $this->databaseManager->raw(
                                    ConfigService::$tableContent . '.id'
                                )
                            ]
                        );

                }
            )
            ->whereExists(
                function (Builder $builder) {
                    return $builder
                        ->select([ConfigService::$tableFields . '.id'])
                        ->from(ConfigService::$tableContentFields)
                        ->join(
                            ConfigService::$tableFields,
                            ConfigService::$tableFields . '.id',
                            '=',
                            ConfigService::$tableContentFields . '.field_id'
                        )
                        ->where(
                            [
                                ConfigService::$tableFields . '.key' => 'topic',
                                ConfigService::$tableFields . '.value' => 'Latin',
                                ConfigService::$tableContentFields .
                                '.content_id' => $this->databaseManager->raw(
                                    ConfigService::$tableContent . '.id'
                                )
                            ]
                        );

                }
            )
            ->limit($limit)
            ->skip(($page - 1) * $limit);

        $subLimitQueryString = $subLimitQuery->toSql();

        $query = $this->queryTable()
            ->select(
                [
                    ConfigService::$tableContent . '.id as id',
                    ConfigService::$tableContent . '.slug as slug',
                    ConfigService::$tableContent . '.status as status',
                    ConfigService::$tableContent . '.type as type',
                    ConfigService::$tableContent . '.position as position',
                    ConfigService::$tableContent . '.parent_id as parent_id',
                    ConfigService::$tableContent . '.language as language',
                    ConfigService::$tableContent . '.published_on as published_on',
                    ConfigService::$tableContent . '.created_on as created_on',
                    ConfigService::$tableContent . '.archived_on as archived_on',
                    ConfigService::$tableContent . '.brand as brand',
                    ConfigService::$tableFields . '.id as field_id',
                    ConfigService::$tableFields . '.key as field_key',
                    ConfigService::$tableFields . '.value as field_value',
                    ConfigService::$tableFields . '.type as field_type',
                    ConfigService::$tableFields . '.position as field_position',
                    ConfigService::$tableData . '.id as datum_id',
                    ConfigService::$tableData . '.key as datum_key',
                    ConfigService::$tableData . '.value as datum_value',
                    ConfigService::$tableData . '.position as datum_position',
                ]
            )
            ->leftJoin(
                ConfigService::$tableContentFields,
                ConfigService::$tableContentFields . '.content_id',
                '=',
                ConfigService::$tableContent . '.id'
            )
            ->leftJoin(
                ConfigService::$tableFields,
                ConfigService::$tableFields . '.id',
                '=',
                ConfigService::$tableContentFields . '.field_id'
            )
            ->leftJoin(
                ConfigService::$tableContentData,
                ConfigService::$tableContentData . '.content_id',
                '=',
                ConfigService::$tableContent . '.id'
            )
            ->leftJoin(
                ConfigService::$tableData,
                ConfigService::$tableData . '.id',
                '=',
                ConfigService::$tableContentData . '.datum_id'
            )
            ->join(
                $this->databaseManager->raw('(' . $subLimitQueryString . ') inner_content'),
                function (JoinClause $joinClause) {
                    $joinClause->on(ConfigService::$tableContent . '.id', '=', 'inner_content.id');
                }
            )
            ->addBinding($subLimitQuery->getBindings())
            ->orderBy($orderBy, $orderDirection);

        return $this->parseBaseQueryRows($query->get()->toArray());
//
//        // close
//        $query = $this->queryTable()
//            ->select(
//                $this->databaseManager->raw(
//                    '
//                    railcontent_content.id,
//                    railcontent_fields.key,
//                    (CASE WHEN railcontent_fields.key = \'difficulty\'
//                THEN railcontent_fields.value END) AS
//                 \'difficulty\',
//                    COUNT((CASE WHEN railcontent_fields.key = \'difficulty\'
//                THEN railcontent_fields.value END)) AS
//                 \'difficulty_count\',
//                    (CASE WHEN railcontent_fields.key = \'topic\'
//                THEN railcontent_fields.value END) AS
//                 \'topic\',
//                    (CASE WHEN railcontent_fields.key = \'topic_count\'
//                THEN railcontent_fields.value END) AS
//                 \'topic_count\'
//
//                    '
//                )
//            )
//            ->join(
//                ConfigService::$tableContentFields,
//                ConfigService::$tableContentFields . '.content_id',
//                '=',
//                ConfigService::$tableContent . '.id'
//            )
//            ->join(
//                ConfigService::$tableFields,
//                ConfigService::$tableFields . '.id',
//                '=',
//                ConfigService::$tableContentFields . '.field_id'
//            )
//            ->having('difficulty_count', '<', 0)
//            ->groupBy(
//                ConfigService::$tableContentFields . '.id',
//                ConfigService::$tableContent . '.id',
//                ConfigService::$tableFields . '.key',
//                ConfigService::$tableFields . '.value'
//            );
//
//        echo($query->toSql());
//        dd($query->get()->toArray());
//
//        // lets try selects
//
//        $query = $this->queryTable()
//            ->join(
//                ConfigService::$tableContentFields,
//                ConfigService::$tableContentFields . '.content_id',
//                '=',
//                ConfigService::$tableContent . '.id'
//            );
//
//        foreach ($requiredFields as $fieldKey => $fieldValues) {
//            $dynamicTableName = $fieldKey . '_field_table';
//
//            $query = $query->join(
//                ConfigService::$tableFields . ' as ' . $dynamicTableName,
//                function (JoinClause $joinClause) use ($fieldKey, $fieldValues, $dynamicTableName) {
//                    $joinClause->on(
//                        $this->databaseManager->raw($dynamicTableName . '.id'),
//                        '=',
//                        ConfigService::$tableContentFields . '.field_id'
//                    )
//                        ->where($dynamicTableName . '.key', '=', $fieldKey)
//                        ->where($dynamicTableName . '.value', '=', $fieldValues[0]);
//                }
//            );
//        }
//
//        echo($query->toSql());
//        dd($query->get()->toArray());
//
//        // we must use a sub query here since the main query is full of joined rows that throw off the
//        // limit and skip
//        $subQuery = $this->baseQuery(false)
//            ->select(
//                [
//                    ConfigService::$tableContent . '.id as id',
//                    ConfigService::$tableContentFields . '.field_id as content_field_id',
//                ]
//            )
//            ->join(
//                ConfigService::$tableContentFields,
//                ConfigService::$tableContentFields . '.content_id',
//                '=',
//                ConfigService::$tableContent . '.id'
//            )
//            ->whereIn(ConfigService::$tableContent . '.type', $types);
////            ->limit($limit)
////            ->skip(($page - 1) * $limit);
//
//        $count = 0;
//
//        $subQuery = $subQuery->where(
//            function (Builder $builder) use ($requiredFields) {
//                foreach ($requiredFields as $fieldKey => $fieldValues) {
//                    $builder->where(
//                        function (Builder $builder) use ($fieldKey, $fieldValues) {
//                            return $builder->orWhereIn(
//                                ConfigService::$tableContentFields . '.field_id',
//                                function (Builder $builder) use ($fieldKey, $fieldValues) {
//                                    return $builder->select('id')
//                                        ->from(ConfigService::$tableFields)
//                                        ->where(
//                                            ['key' => $fieldKey, 'value' => $fieldValues[0]]
//                                        );
//                                }
//                            );
//                        }
//                    );
//                }
//            }
//        );
//
//        echo($subQuery->toSql());
//        dd($subQuery->get()->toArray());
//
//        $query = $this->baseQuery()
//            ->join(
//                $this->databaseManager->raw('(' . $subQueryString . ') inner_content'),
//                function (JoinClause $joinClause) {
//                    $joinClause->on(ConfigService::$tableContent . '.id', '=', 'inner_content.id');
//                }
//            )
//            ->where(['field_key' => 'topic', ['field_value' => 'tuning']])
//            ->addBinding($types)
//            ->orderBy($orderBy, $orderDirection);
//
//        return $this->parseBaseQueryRows($query->get()->toArray());
    }

    /**
     * Insert a new content in the database, save the content slug in the translation table and recalculate position
     *
     * @param string $slug
     * @param string $status
     * @param string $type
     * @param integer $position
     * @param string $language |null
     * @param integer|null $parentId
     * @param string|null $publishedOn
     * @return int
     */
    public function create($slug, $status, $type, $position, $language, $parentId, $publishedOn)
    {
        $contentId = $this->queryTable()->insertGetId(
            [
                'slug' => $slug,
                'status' => $status,
                'type' => $type,
                'brand' => ConfigService::$brand,
                'position' => $position,
                'language' => $language,
                'parent_id' => $parentId,
                'published_on' => $publishedOn,
                'created_on' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->reposition($contentId, $position);

        return $contentId;
    }

    /**
     * Update content position and call function that recalculate position for other children
     *
     * @param int $contentId
     * @param int $position
     */
    public function reposition($contentId, $position)
    {
        $parentContentId = $this->queryTable()->where('id', $contentId)->first(['parent_id'])['parent_id']
            ?? null;
        $childContentCount = $this->queryTable()->where('parent_id', $parentContentId)->count();

        if($position < 1) {
            $position = 1;
        } elseif($position > $childContentCount) {
            $position = $childContentCount;
        }

        $this->transaction(
            function() use ($contentId, $position, $parentContentId) {
                $this->queryTable()
                    ->where('id', $contentId)
                    ->update(
                        ['position' => $position]
                    );

                $this->otherChildrenRepositions($parentContentId, $contentId, $position);
            }
        );
    }

    /** Update position for other categories with the same parent id
     *
     * @param integer $parentCategoryId
     * @param integer $categoryId
     * @param integer $position
     */
    function otherChildrenRepositions($parentContentId, $contentId, $position)
    {
        $childContent =
            $this->queryTable()
                ->where('parent_id', $parentContentId)
                ->where('id', '<>', $contentId)
                ->orderBy('position')
                ->get()
                ->toArray();

        $start = 1;

        foreach($childContent as $child) {
            if($start == $position) {
                $start++;
            }

            $this->queryTable()
                ->where('id', $child['id'])
                ->update(
                    ['position' => $start]
                );
            $start++;
        }
    }

    /**
     * Update a content record, recalculate position and return the content id
     *
     * @param $id
     * @param string $slug
     * @param string $status
     * @param string $type
     * @param integer $position
     * @param string|null $language
     * @param integer|null $parentId
     * @param string|null $publishedOn
     * @param string|null $archivedOn
     * @return int $categoryId
     */
    public function update(
        $id,
        $slug,
        $status,
        $type,
        $position,
        $language,
        $parentId,
        $publishedOn,
        $archivedOn
    )
    {
        $this->queryTable()->where('id', $id)->update(
            [
                'slug' => $slug,
                'status' => $status,
                'type' => $type,
                'position' => $position,
                'language' => $language,
                'parent_id' => $parentId,
                'published_on' => $publishedOn,
                'created_on' => Carbon::now()->toDateTimeString(),
                'archived_on' => $archivedOn,
            ]
        );

        $this->reposition($id, $position);

        return $id;
    }

    /**
     * Unlink content's fields, content's datum and content's children,
     * delete the content and reposition the content children.
     *
     * @param int $id
     * @param bool $deleteChildren
     * @return int
     */
    public function delete($id, $deleteChildren = false)
    {

        $this->unlinkFields($id);
        $this->unlinkData($id);

        if($deleteChildren) {
            $this->unlinkChildren($id);
        }

        $delete = $this->queryTable()->where('id', $id)->delete();

        // todo: get parent id for this content id and replace null with it
        $this->otherChildrenRepositions(null, $id, 0);

        return $delete;
    }

    /**
     * Unlink all fields for a content id.
     *
     * @param $contentId
     * @return int
     */
    public function unlinkFields($contentId)
    {
        return $this->contentFieldsQuery()->where('content_id', $contentId)->delete();
    }

    /**
     * @return Builder
     */
    public function contentFieldsQuery()
    {
        return parent::connection()->table(ConfigService::$tableContentFields);
    }

    /**
     * Unlink all datum for a content id.
     *
     * @param $contentId
     * @return int
     */
    public function unlinkData($contentId)
    {
        return $this->contentDataQuery()->where('content_id', $contentId)->delete();
    }

    /**
     * @return Builder
     */
    public function contentDataQuery()
    {
        return parent::connection()->table(ConfigService::$tableContentData);
    }

    /**
     * Unlink content children.
     *
     * @param integer $id
     * @return integer
     */
    public function unlinkChildren($id)
    {
        return $this->queryTable()->where('parent_id', $id)->update(['parent_id' => null]);
    }

    /**
     * Delete a specific content field link
     *
     * @param $contentId
     * @param null $fieldId
     * @return int
     */
    public function unlinkField($contentId, $fieldId)
    {
        return $this->contentFieldsQuery()
            ->where('content_id', $contentId)
            ->where('field_id', $fieldId)
            ->delete();
    }

    /**
     * Delete a specific content datum link
     *
     * @param $contentId
     * @param null $datumId
     * @return int
     */
    public function unlinkDatum($contentId, $datumId)
    {
        return $this->contentDataQuery()
            ->where('content_id', $contentId)
            ->where('datum_id', $datumId)
            ->delete();
    }

    /**
     * Insert a new record in railcontent_content_data
     *
     * @param integer $contentId
     * @param integer $datumId
     * @return int
     */
    public function linkDatum($contentId, $datumId)
    {
        return $this->contentDataQuery()->insertGetId(
            [
                'content_id' => $contentId,
                'datum_id' => $datumId
            ]
        );
    }

    /**
     * Insert a new record in railcontent_content_fields
     *
     * @param integer $contentId
     * @param integer $fieldId
     * @return int
     */
    public function linkField($contentId, $fieldId)
    {
        return $this->contentFieldsQuery()->insertGetId(
            [
                'content_id' => $contentId,
                'field_id' => $fieldId
            ]
        );
    }

    /**
     * Get the content and the linked datum from database
     *
     * @param integer $datumId
     * @param integer $contentId
     * @return mixed
     */
    public function getLinkedDatum($datumId, $contentId)
    {
        $dataIdLabel = ConfigService::$tableData.'.id';

        return $this->contentDataQuery()
            ->leftJoin(ConfigService::$tableData, 'datum_id', '=', $dataIdLabel)
            ->select(
                ConfigService::$tableContentData.'.*',
                ConfigService::$tableData.'.*'
            )
            ->where(
                [
                    'datum_id' => $datumId,
                    'content_id' => $contentId
                ]
            )
            ->get()
            ->first();
    }

    /**
     * Get the content and the associated field from database
     *
     * @param integer $fieldId
     * @param integer $contentId
     * @return mixed
     */
    public function getLinkedField($fieldId, $contentId)
    {
        $fieldIdLabel = ConfigService::$tableFields.'.id';

        return
            $this->contentFieldsQuery()
                ->leftJoin(ConfigService::$tableFields, 'field_id', '=', $fieldIdLabel)
                ->select(
                    ConfigService::$tableContentFields.'.*',
                    ConfigService::$tableFields.'.*'
                )
                ->where(
                    [
                        'field_id' => $fieldId,
                        'content_id' => $contentId
                    ]
                )
                ->get()
                ->first();
    }

    /**
     * Get the content and the associated field from database based on key
     *
     * @param string $key
     * @param integer $contentId
     * @return mixed
     */
    public function getContentLinkedFieldByKey($key, $contentId)
    {
        $fieldIdLabel = ConfigService::$tableFields.'.id';

        return $this->contentFieldsQuery()
            ->leftJoin(ConfigService::$tableFields, 'field_id', '=', $fieldIdLabel)
            ->where(
                [
                    'key' => $key,
                    'content_id' => $contentId
                ]
            )->get()->first();
    }

    /**
     * @return Builder
     */
    public function queryIndex()
    {
        return $this->queryTable()
            ->select(
                [
                    ConfigService::$tableContent.'.id as id',
                    ConfigService::$tableContent.'.slug as slug',
                    ConfigService::$tableContent.'.status as status',
                    ConfigService::$tableContent.'.type as type',
                    ConfigService::$tableContent.'.position as position',
                    ConfigService::$tableContent.'.parent_id as parent_id',
                    ConfigService::$tableContent.'.published_on as published_on',
                    ConfigService::$tableContent.'.created_on as created_on',
                    ConfigService::$tableContent.'.archived_on as archived_on',
                    ConfigService::$tableContent.'.brand as brand',
                    'allfieldsvalue.id as field_id',
                    'allfieldsvalue.key as field_key',
                    'allfieldsvalue.value as field_value',
                    'allfieldsvalue.type as field_type',
                    'allfieldsvalue.position as field_position',
                    ConfigService::$tableData.'.id as datum_id',
                    ConfigService::$tableData.'.key as datum_key',
                    ConfigService::$tableData.'.value as datum_value',
                    ConfigService::$tableData.'.position as datum_position',

                ]
            )
            ->leftJoin(
                ConfigService::$tableContentData,
                ConfigService::$tableContentData.'.content_id',
                '=',
                ConfigService::$tableContent.'.id'
            )
            ->leftJoin(
                ConfigService::$tableData,
                ConfigService::$tableData.'.id',
                '=',
                ConfigService::$tableContentData.'.datum_id'
            )
            ->leftJoin(
                ConfigService::$tableContentFields.' as allcontentfields',
                'allcontentfields.content_id',
                '=',
                ConfigService::$tableContent.'.id'
            )
            ->leftJoin(
                ConfigService::$tableFields.' as allfieldsvalue',
                'allfieldsvalue.id',
                '=',
                'allcontentfields.field_id'
            )
            ->leftJoin(
                ConfigService::$tableContentPermissions,
                function($join) {
                    return $join->on(
                        ConfigService::$tableContentPermissions.'.content_id',
                        ConfigService::$tableContent.'.id'
                    )
                        ->orOn(
                            ConfigService::$tableContentPermissions.'.content_type',
                            ConfigService::$tableContent.'.type'
                        );
                }
            )
            ->leftJoin(
                ConfigService::$tablePermissions,
                ConfigService::$tablePermissions.'.id',
                '=',
                ConfigService::$tableContentPermissions.'.required_permission_id'
            )
            ->groupBy(
                [
                    'allfieldsvalue.id',
                    ConfigService::$tableContent.'.id',
                    ConfigService::$tableData.'.id'
                ]
            );
    }

    /**
     * @return Builder
     */
    public function contentVersionQuery()
    {
        return parent::connection()->table(ConfigService::$tableVersions);
    }

    /**
     * Get a collection with the contents Ids, where the content it's linked
     *
     * @param integer $contentId
     * @return \Illuminate\Support\Collection
     */
    public function linkedWithContent($contentId)
    {
        $fieldIdLabel = ConfigService::$tableFields.'.id';

        return $this->contentFieldsQuery()
            ->select('content_id')
            ->leftJoin(ConfigService::$tableFields, 'field_id', '=', $fieldIdLabel)
            ->where(
                [
                    'value' => $contentId,
                    'type' => 'content_id'
                ]
            )->get();
    }

    /**
     * @return Builder
     */
    public function queryTable()
    {
        return $this->connection()->table(ConfigService::$tableContent);
    }

    /** Generate the Query Builder
     *
     * @param bool $includeJoins
     * @return Builder
     */
    public function baseQuery($includeJoins = true)
    {
        $selects = [
            ConfigService::$tableContent.'.id as id',
            ConfigService::$tableContent.'.slug as slug',
            ConfigService::$tableContent.'.status as status',
            ConfigService::$tableContent.'.type as type',
            ConfigService::$tableContent.'.position as position',
            ConfigService::$tableContent.'.parent_id as parent_id',
            ConfigService::$tableContent.'.language as language',
            ConfigService::$tableContent.'.published_on as published_on',
            ConfigService::$tableContent.'.created_on as created_on',
            ConfigService::$tableContent.'.archived_on as archived_on',
            ConfigService::$tableContent.'.brand as brand',
        ];

        if($includeJoins) {
            $selects = array_merge(
                $selects,
                [
                    ConfigService::$tableFields.'.id as field_id',
                    ConfigService::$tableFields.'.key as field_key',
                    ConfigService::$tableFields.'.value as field_value',
                    ConfigService::$tableFields.'.type as field_type',
                    ConfigService::$tableFields.'.position as field_position',

                    ConfigService::$tableData.'.id as datum_id',
                    ConfigService::$tableData.'.value as datum_value',
                    ConfigService::$tableData.'.key as datum_key',
                    ConfigService::$tableData.'.position as datum_position',
                ]
            );
        }

        $query = $this->queryTable()
            ->select($selects);

        if($includeJoins) {
            $query = $this->fieldRepository->attachFieldsToContentQuery($query);
            $query = $this->datumRepository->attachDatumToContentQuery($query);
            $query = $this->permissionRepository->restrictContentQueryByPermissions($query);
        }

        if(is_array(self::$availableContentStatues)) {
            $query = $query->whereIn('status', self::$availableContentStatues);
        }

        if(is_array(self::$includedLanguages)) {
            $query = $query->whereIn('language', self::$includedLanguages);
        }

        if(!self::$pullFutureContent) {
            $query = $query->where('published_on', '<', Carbon::now()->toDateTimeString());
        }

        return $query;
    }

    /**
     * @param array $rows
     */
    private function parseBaseQueryRows(array $rows)
    {
        $contents = [];
        $fields = [];
        $data = [];

        foreach($rows as $row) {
            $content = [
                'id' => $row['id'],
                'slug' => $row['slug'],
                'status' => $row['status'],
                'type' => $row['type'],
                'position' => $row['position'],
                'parent_id' => $row['parent_id'],
                'language' => $row['language'],
                'published_on' => $row['published_on'],
                'created_on' => $row['created_on'],
                'archived_on' => $row['archived_on'],
                'brand' => $row['brand'],
            ];

            $contents[$row['id']] = $content;

            $contents[$row['id']] =
                array_map("unserialize", array_unique(array_map("serialize", $contents[$row['id']])));

            if(!empty($row['field_id'])) {
                $field = [
                    'id' => $row['field_id'],
                    'key' => $row['field_key'],
                    'value' => $row['field_value'],
                    'type' => $row['field_type'],
                    'position' => $row['field_position'],
                ];

                $fields[$row['id']][] = $field;

                $fields[$row['id']] =
                    array_map("unserialize", array_unique(array_map("serialize", $fields[$row['id']])));
            }

            if(!empty($row['datum_id'])) {
                $datum = [
                    'id' => $row['datum_id'],
                    'key' => $row['datum_key'],
                    'value' => $row['datum_value'],
                    'position' => $row['datum_position'],
                ];

                $data[$row['id']][] = $datum;

                $data[$row['id']] =
                    array_map("unserialize", array_unique(array_map("serialize", $data[$row['id']])));
            }

        }

        foreach($contents as $contentId => $content) {
            $contents[$contentId]['fields'] = $fields[$contentId] ?? null;
            $contents[$contentId]['data'] = $data[$contentId] ?? null;
        }

        return $contents;
    }
}