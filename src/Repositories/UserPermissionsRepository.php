<?php

namespace Railroad\Railcontent\Repositories;

use Carbon\Carbon;
use Doctrine\ORM\EntityRepository;
use Railroad\Railcontent\Contracts\UserProviderInterface;
use Railroad\Railcontent\Repositories\Traits\RailcontentCustomQueryBuilder;

class UserPermissionsRepository extends EntityRepository
{

    use RailcontentCustomQueryBuilder;

    /** Pull the user permissions record
     *
     * @param integer|null $userId
     * @param boolean $onlyActive
     * @return array
     */
    public function getUserPermissions($userId, $onlyActive)
    {
        $qb = $this->createQueryBuilder('up');

        if ($userId) {
            $user = app()->make(UserProviderInterface::class)->getUserById($userId);
            $qb->where('up.user = :user')
                ->setParameter('user', $user)
            ->orderByColumn('up','expirationDate', 'asc');
        }

        if ($onlyActive) {
            $qb->andWhere(
                $qb->expr()
                    ->orX(
                        $qb->expr()
                            ->isNull('up.expirationDate'),
                        $qb->expr()
                            ->gte('up.expirationDate', ':expirationDate')
                    )
            )
                ->setParameter(
                    'expirationDate',
                    'CURRENT_TIMESTAMP()'
                );
        }

        return $qb->getQuery()
            ->setCacheable(true)
            ->setCacheRegion('userPermissions')
            ->getResult();
    }

    /**
     * @param $user
     * @param $permission
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function userPermission($user, $permission)
    {
        $alias = 'up';
        $qb = $this->createQueryBuilder($alias);
        $qb->where($alias.'.user = :user')
            ->andWhere($alias.'.permission = :permission')
            ->setParameter('user', $user)
            ->setParameter('permission', $permission);

        return $qb->getQuery()
            ->getOneOrNullResult('Railcontent');

    }
}