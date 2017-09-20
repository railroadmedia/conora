<?php

namespace Railroad\Railcontent\Repositories;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Railroad\Railcontent\Requests\ContentIndexRequest;
use Railroad\Railcontent\Services\ConfigService;
use Railroad\Railcontent\Services\SearchInterface;

/**
 * Class ContentRepository
 *
 * @package Railroad\Railcontent\Repositories
 */
class ContentRepository extends RepositoryBase implements SearchInterface
{
    /**
     * Insert a new category in the database, recalculate position and regenerate tree
     *
     * @param string $slug
     * @param string $status
     * @param string $type
     * @param integer $position
     * @param integer $parentId
     * @param string|null $publishedOn
     * @return int
     */
    public function create($slug, $status, $type, $position, $parentId, $publishedOn)
    {
        $id = null;

        $contentId = $this->queryTable()->insertGetId(
            [
                'slug' => $slug,
                'status' => $status,
                'type' => $type,
                'position' => $position,
                'parent_id' => $parentId,
                'published_on' => $publishedOn,
                'created_on' => Carbon::now()->toDateTimeString(),
            ]
        );

        $this->reposition($contentId, $position);

        return $contentId;
    }

    /**
     * Update a category record, recalculate position, regenerate tree and return the category id
     *
     * @param $id
     * @param string $slug
     * @param string $status
     * @param string $type
     * @param integer $position
     * @param integer $parentId
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
     * @param int $id
     * @param bool $deleteChildren
     * @return int
     */
    public function delete($content, $deleteChildren = false)
    {
        $id = $content['id'];

        // unlink fields and data
        $this->unlinkField($id);
        $this->unlinkDatum($id);

        if($deleteChildren) {
            // unlink children content
            $this->unlinkChildren($id);
        }

        $delete = $this->queryTable()->where('id', $id)->delete();

        $this->otherChildrenRepositions($content['parent_id'], $id, 0);

        return $delete;
    }

    /**
     * Unlink all fields for a content id, or pass in the field id to delete a specific content field link
     *
     * @param $contentId
     * @param null $fieldId
     * @return int
     */
    public function unlinkField($contentId, $fieldId = null)
    {
        if(!is_null($fieldId)) {
            return $this->contentFieldsQuery()
                ->where('content_id', $contentId)
                ->where('field_id', $fieldId)
                ->delete();
        }

        return $this->contentFieldsQuery()->where('content_id', $contentId)->delete();
    }

    /**
     * Unlink all datum for a content id, or pass in the field id to delete a specific content datum link
     *
     * @param $contentId
     * @param null $datumId
     * @return int
     */
    public function unlinkDatum($contentId, $datumId = null)
    {
        if(!is_null($datumId)) {
            return $this->contentDataQuery()
                ->where('content_id', $contentId)
                ->where('datum_id', $datumId)
                ->delete();
        }

        return $this->contentDataQuery()->where('content_id', $contentId)->delete();
    }

    /**
     * @param $id
     * @return int
     */
    public function unlinkChildren($id)
    {
        return $this->queryTable()->where('parent_id', $id)->update(['parent_id' => null]);
    }

    /**
     * Insert a new record in railcontent_content_data
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
            ]);
    }

    /**
     * Insert a new record in railcontent_content_fields
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
            ]);
    }

    /**
     * Get the content and the linked datum from database
     * @param integer $datumId
     * @param integer $contentId
     */
    public function getLinkedDatum($datumId, $contentId)
    {
        $dataIdLabel = ConfigService::$tableData.'.id';

        return $this->contentDataQuery()
            ->leftJoin(ConfigService::$tableData, 'datum_id', '=', $dataIdLabel)
            ->where(
                [
                    'datum_id' => $datumId,
                    'content_id' => $contentId
                ]
            )->get()->first();
    }

    /**
     * Get the content and the associated field from database
     * @param integer $fieldId
     * @param integer $contentId
     */
    public function getLinkedField($fieldId, $contentId)
    {
        $fieldIdLabel = ConfigService::$tableFields.'.id';

        return $this->contentFieldsQuery()
            ->leftJoin(ConfigService::$tableFields, 'field_id', '=', $fieldIdLabel)
            ->where(
                [
                    'field_id' => $fieldId,
                    'content_id' => $contentId
                ]
            )->get()->first();
    }

    /**
     * Get the content and the associated field from database based on key
     * @param string $key
     * @param integer $contentId
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
    public function queryTable()
    {
        return parent::connection()->table(ConfigService::$tableContent);
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
                ConfigService::$tableContentPermissions, function($join) {
                return $join->on(ConfigService::$tableContentPermissions.'.content_id', ConfigService::$tableContent.'.id')
                    ->orOn(ConfigService::$tableContentPermissions.'.content_type', ConfigService::$tableContent.'.type');
            }
            )
            ->leftJoin(
                ConfigService::$tablePermissions,
                ConfigService::$tablePermissions.'.id',
                '=',
                ConfigService::$tableContentPermissions.'.required_permission_id'
            )
            ->groupBy([
                'allfieldsvalue.id',
                ConfigService::$tableContent.'.id',
                ConfigService::$tableData.'.id'
            ]);
    }

    /**
     * @return Builder
     */
    public function contentFieldsQuery()
    {
        return parent::connection()->table(ConfigService::$tableContentFields);
    }

    /**
     * @return Builder
     */
    public function contentDataQuery()
    {
        return parent::connection()->table(ConfigService::$tableContentData);
    }

    /**
     * @return Builder
     */
    public function contentVersionQuery()
    {
        return parent::connection()->table(ConfigService::$tableVersions);
    }

    /**
     * Update content position and call function that recalculate position for other children
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
     * Get all contents order by parent and position
     * @return array
     */
    public function getAllContents()
    {
        return $this->queryIndex()->orderBy('parent_id', 'asc')->orderBy('position', 'asc')->get()->toArray();
    }

    /**
     * Get a collection with the contents Ids, where the content it's linked
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
     * @param ContentIndexRequest $request
     * @return mixed
     */
    public function generateQuery()
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
                    //ConfigService::$tableContentFields . '.field_id as field_id',
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
            )->leftJoin(
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
            )->leftJoin(
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
            );
    }
}