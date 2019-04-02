<?php

namespace Railroad\Railcontent\Services;

use Railroad\Railcontent\Contracts\UserProviderInterface;
use Railroad\Railcontent\Entities\Content;
use Railroad\Railcontent\Entities\ContentLikes;
use Railroad\Railcontent\Managers\RailcontentEntityManager;

class ContentLikeService
{

    /**
     * @var \Doctrine\Common\Persistence\ObjectRepository|\Doctrine\ORM\EntityRepository
     */
    private $contentLikeRepository;

    /**
     * @var RailcontentEntityManager
     */
    private $entityManager;

    /**
     * @var \Doctrine\Common\Persistence\ObjectRepository|\Doctrine\ORM\EntityRepository
     */
    private $contentRepository;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * ContentLikeService constructor.
     *
     * @param RailcontentEntityManager $entityManager
     * @param UserProviderInterface $userProvider
     */
    public function __construct(RailcontentEntityManager $entityManager, UserProviderInterface $userProvider)
    {
        $this->entityManager = $entityManager;
        $this->userProvider = $userProvider;

        $this->contentLikeRepository = $this->entityManager->getRepository(ContentLikes::class);
        $this->contentRepository = $this->entityManager->getRepository(Content::class);
    }

    /**
     * @param $id
     * @param $request
     * @return mixed
     */
    public function index($id, $request)
    {
        $qb = $this->getQb($id, $request);

        $query = $qb->getQuery();

        return $query->getResult('Railcontent');
    }

    /**
     * @param $id
     * @param $request
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getQb($id, $request)
    {
        $alias = 'l';
        $first = ($request->get('page', 1) - 1) * $request->get('limit', 10);
        $orderBy = $request->get('sort', '-created_on');
        if (strpos($orderBy, '_') !== false || strpos($orderBy, '-') !== false) {
            $orderBy = camel_case($orderBy);
        }
        $orderBy = $alias . '.' . $orderBy;

        $qb = $this->contentLikeRepository->createQueryBuilder($alias);

        $qb->where($alias . '.content IN (:id)')
            ->setParameter('id', $id)
            ->setMaxResults($request->get('limit', 10))
            ->setFirstResult($first)
            ->orderBy($orderBy, substr($request->get('sort', '-created_on'), 0, 1) !== '-' ? 'asc' : 'desc');

        return $qb;
    }

    /**
     * Returns array with content ids as the key and like count as the value.
     * [46236 => 5]
     *
     * @param $contentIds
     * @return array
     */
    public function countForContentIds($contentIds)
    {
        $qb =
            $this->contentRepository->createQueryBuilder('co')
                ->leftJoin('co.likes', 'c');

        $results =
            $qb->select('co.id, count(c.id) as nr')
                ->where(
                    $qb->expr()
                        ->in('co.id', ':contentIds')
                )
                ->setParameter('contentIds', $contentIds)
                ->groupBy('co.id')
                ->getQuery()
                ->getResult('Railcontent');

        return array_combine(array_column($results, 'id'), array_column($results, 'nr'));
    }

    /**
     * @param $contentId
     * @param $userId
     * @return array
     */
    public function like($contentId, $userId)
    {
        $user = $this->userProvider->getUserById($userId);
        $content = $this->contentRepository->find($contentId);

        $contentLikes = $this->contentLikeRepository->findBy(
            [
                'content' => $content,
                'user' => $user,
            ]
        );

        if (empty($contentLikes)) {
            $contentLikes = new ContentLikes();
            $contentLikes->setUser($user);

        }
        $contentLikes->setContent($content);

        $this->entityManager->persist($contentLikes);
        $this->entityManager->flush();

        $this->entityManager->getCache()
            ->evictEntity(Content::class, $contentId);

        return $contentLikes;
    }

    /**
     * @param $contentId
     * @param $userId
     * @return int
     */
    public function unlike($contentId, $userId)
    {
        $user = $this->userProvider->getUserById($userId);
        $content = $this->contentRepository->find($contentId);

        $contentLikes = $this->contentLikeRepository->findOneBy(
            [
                'content' => $content,
                'user' => $user,
            ]
        );

        $this->entityManager->remove($contentLikes);
        $this->entityManager->flush();

        $this->entityManager->getCache()
            ->evictEntity(Content::class, $contentId);

        return true;
    }
}