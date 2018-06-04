<?php

namespace Railroad\Railcontent\Decorators\UserProgress;

use Railroad\Railcontent\Decorators\DecoratorInterface;
use Railroad\Railcontent\Repositories\UserContentProgressRepository;
use Railroad\Railcontent\Services\UserContentProgressService;

class ContentUserProgressDecorator implements DecoratorInterface
{
    /**
     * @var UserContentProgressRepository
     */
    protected $userContentProgressRepository;

    /**
     * CommentLikesDecorator constructor.
     */
    public function __construct(UserContentProgressRepository $userContentProgressRepository)
    {
        $this->userContentProgressRepository = $userContentProgressRepository;
    }

    public function decorate($contents, $userId = null)
    {
        if (empty($userId) && !empty(auth()->id())) {
            $userId = auth()->id();
        }

        if (empty($userId)) {
            return $contents;
        }

        $contentIds = [];

        foreach ($contents as $content) {
            $contentIds[] = $content['id'];
        }

        if (!empty($contentIds)) {
            $contentProgressions =
                $this->userContentProgressRepository->getByUserIdAndWhereContentIdIn($userId, $contentIds);

            $contentProgressionsByContentId =
                array_combine(array_column($contentProgressions, 'content_id'), $contentProgressions);

            foreach ($contents as $index => $content) {
                if (!empty($contentProgressionsByContentId[$content['id']])) {
                    $contents[$index]['user_progress'][$userId] =
                        $contentProgressionsByContentId[$content['id']];

                    $contents[$index][UserContentProgressService::STATE_COMPLETED] =
                        $contentProgressionsByContentId[$content['id']]['state'] ==
                        UserContentProgressService::STATE_COMPLETED;

                    $contents[$index][UserContentProgressService::STATE_STARTED] =
                        $contentProgressionsByContentId[$content['id']]['state'] ==
                        UserContentProgressService::STATE_STARTED;
                } else {
                    $contents[$index]['user_progress'][$userId] = [];

                    $contents[$index][UserContentProgressService::STATE_COMPLETED] = false;
                    $contents[$index][UserContentProgressService::STATE_STARTED] = false;
                }
            }
        }

        return $contents;
    }
}