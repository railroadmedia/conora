<?php

namespace Railroad\Railcontent\Repositories;

use Carbon\Carbon;
use Doctrine\ORM\EntityRepository;
use Railroad\Railcontent\Helpers\ContentHelper;
use Railroad\Railcontent\Services\ConfigService;
use Railroad\Railcontent\Support\Collection;
use Railroad\Resora\Decorators\Decorator;

class CommentRepository extends EntityRepository
{

    /** The value it's set in ContentPermissionMiddleware: if the user it's admin the value it's false, otherwise it's true.
     * If the value it' is false the comment with all his replies will be deleted.
     * If it's true the comment with the replies are only soft deleted (marked as deleted).
     *
     * @var bool
     */
    public static $softDelete = true;

    /**
     * If this is false comment for any content type will be pulled. If its defined, only comments for content with the
     * type will be pulled.
     *
     * @var string|bool
     */
    public static $availableContentType = false;

    /**
     * If this is false comment for any content will be pulled. If its defined, only comments for content with id
     *  will be pulled.
     *
     * @var integer|bool
     */
    public static $availableContentId = false;

    /**
     * If this is false comment for any content will be pulled. If its defined, only user comments will be pulled.
     *
     * @var integer|bool
     */
    public static $availableUserId = false;

    /**
     * If it's true all the comments (inclusive the comments marked as deleted) will be pulled.
     * If it's false, only the comments that are not marked as deleted will be pulled.
     *
     * @var bool
     */
    public static $pullSoftDeletedComments = false;

    /**
     * If not false only pull comments that have been assigned to this user id.
     *
     * @var integer|bool
     */
    public static $assignedToUserId = false;

    protected $page;
    protected $limit;
    protected $orderBy;
    protected $orderDirection;
    protected $orderTableName;
    protected $orderTable;

    protected function decorate($results)
    {
        return Decorator::decorate($results, 'comment');
    }

    /** Set the pagination parameters
     *
     * @param int $page
     * @param int $limit
     * @param string $orderByDirection
     * @param string $orderByColumn
     * @return $this
     */
    public function setData($page, $limit, $orderByDirection, $orderByColumn)
    {
        $this->page = $page;
        $this->limit = $limit;
        $this->orderBy = $orderByColumn;
        $this->orderDirection = $orderByDirection;

        $this->orderTableName =
            ($orderByColumn == 'like_count' ? ConfigService::$tableCommentLikes : ConfigService::$tableComments);

        $this->orderTable = ($orderByColumn == 'like_count' ? '' : ConfigService::$tableComments);

        return $this;
    }

    /** Based on softDelete we soft delete or permanently delete the comment with all his replies
     *
     * @param int $id
     * @return bool|int
     */
    public function deleteCommentReplies($id)
    {
        if ($this::$softDelete) {
            return $this->softDeleteReplies($id);
        }

        return $this->deleteReplies($id);
    }

    /** Mark comment and it's replies as deleted
     *
     * @param integer $id
     * @return bool
     */
    private function softDeleteReplies($id)
    {
        $replies = $this->findByParent($id);
        foreach ($replies as $reply) {
            $reply->setDeletedAt(Carbon::now());
            $this->getEntityManager()
                ->flush();
        }

        return true;
    }

    /** Delete comment and it's replies
     *
     * @param integer $id
     * @return bool
     */
    private function deleteReplies($id)
    {
        $replies = $this->findByParent($id);
        foreach ($replies as $reply) {
            $this->getEntityManager()
                ->remove($reply);
            $this->getEntityManager()
                ->flush();
        }
    }

    /**
     * @return bool
     */
    public function getSoftDelete()
    {
        return $this::$softDelete;
    }
}