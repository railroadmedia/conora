<?php

namespace Railroad\Railcontent\Services;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Railroad\Railcontent\Entities\Permission;
use Railroad\Railcontent\Entities\UserPermission;
use Railroad\Railcontent\Helpers\CacheHelper;

class UserPermissionsService
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var \Railroad\Railcontent\Repositories\UserPermissionsRepository
     */
    private $userPermissionsRepository;

    /**
     * UserPermissionsService constructor.
     *
     * @param EntityManager $entityManager
     */
    public function __construct(
        EntityManager $entityManager
    ) {
        $this->entityManager = $entityManager;

        $this->userPermissionsRepository = $this->entityManager->getRepository(UserPermission::class);
    }

    /**
     * Save user permission record in database
     *
     * @param integer $userId
     * @param integer $permissionId
     * @param date $startDate
     * @param date|null $expirationDate
     * @return array
     */
    public function updateOrCeate($attributes, $values)
    {
        if (array_key_exists('start_date', $values)) {
            $ttlEndDate = $values['start_date'];
            if (Carbon::parse($values['start_date'])
                ->lt(Carbon::now())

            ) {
                $ttlEndDate = $values['expiration_date'] ?? Carbon::now();
            }
            $this->setTTLOrDeleteUserCache($attributes['user_id'], $ttlEndDate);
        }
        $permission =
            $this->entityManager->getRepository(Permission::class)
                ->find($attributes['permission_id']);
        $userPermission = $this->userPermissionsRepository->findOneBy(
            [
                'userId' => $attributes['user_id'],
                'permission' => $permission,

            ]
        );
        if (!$userPermission) {
            $userPermission = new UserPermission();
            $userPermission->setUserId($attributes['user_id']);
            $userPermission->setPermission($permission);
        }

        $userPermission->setStartDate(Carbon::parse($values['start_date']));
        $userPermission->setExpirationDate(
            $values['expiration_date'] ? Carbon::parse($values['expiration_date']) : null
        );
        $this->entityManager->persist($userPermission);
        $this->entityManager->flush();

        return $userPermission;
    }

    /**
     * Call the method that delete the user permission, if the user permission exists in the database
     *
     * @param int $id
     * @return array|bool
     */
    public function delete($id)
    {
        $userPermission = $this->userPermissionsRepository->find($id);
        if (is_null($userPermission)) {
            return $userPermission;
        }

        //delete the cache for user
        CacheHelper::deleteCacheKeys(
            [
                Cache::store(ConfigService::$cacheDriver)
                    ->getPrefix() . 'userId_' . $userPermission->getUserId(),
            ]
        );
        $this->entityManager->remove($userPermission);
        $this->entityManager->flush();

        return true;
    }

    /**
     * Call the method from repository that pull user permissions and return an array with the results
     *
     * @param null|int $userId
     * @param bool $onlyActive
     * @return array
     */
    public function getUserPermissions($userId = null, $onlyActive = true)
    {
        $qb = $this->userPermissionsRepository->createQueryBuilder('up');

        if ($userId) {
            $qb->where('up.userId = :user')
                ->setParameter('user', $userId);
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
                    Carbon::now()
                        ->toDateTimeString()
                );
        }

        return $qb->getQuery()
            ->getResult();
    }

    /**
     * @param int $userId
     * @param int $permissionId
     * @return array
     */
    public function getUserPermissionIdByPermissionAndUser($userId, $permissionId)
    {
        $userPermission = $this->userPermissionsRepository->findOneBy(
            [
                'userId' => $userId,
                'permission' => $this->entityManager->getRepository(Permission::class)
                    ->find($permissionId),
            ]
        );
        return $userPermission;
    }

    /**
     * @param int $userId
     * @param int $permissionName
     * @return array
     */
    public function userHasPermissionName($userId, $permissionName)
    {
        $userPermission = $this->userPermissionsRepository->findOneBy(
            [
                'userId' => $userId,
                'permission' => $this->entityManager->getRepository(Permission::class)
                    ->findOneBy(['name' => $permissionName])
            ]
        );

        return $userPermission ? true : false;
    }

    /**
     * Delete user cache or set time to live based on user permission start date.
     * If the user permission should be active from current datetime we delete user cache keys
     * If the user permission should be active from a future datetime we set time to live for all user cache keys to
     * the activation datetime
     *
     * @param int $userId
     * @param string $startDate
     */
    private function setTTLOrDeleteUserCache($userId, $startDate)
    {
        if ($startDate ==
            Carbon::now()
                ->toDateTimeString()) {

            //should delete the cache for user
            CacheHelper::deleteCacheKeys(
                [
                    Cache::store(ConfigService::$cacheDriver)
                        ->getPrefix() . 'userId_' . $userId,
                ]
            );
        } else {
            $existingTTL = Redis::ttl(
                Cache::store(ConfigService::$cacheDriver)
                    ->getPrefix() . 'userId_' . $userId
            );

            if ((Carbon::parse($startDate)
                    ->gt(Carbon::now())) &&
                (($existingTTL == -2) ||
                    ($existingTTL >
                        Carbon::parse($startDate)
                            ->diffInSeconds(Carbon::now())))) {
                CacheHelper::setTimeToLiveForKey(
                    Cache::store(ConfigService::$cacheDriver)
                        ->getPrefix() . 'userId_' . $userId,
                    Carbon::parse($startDate)
                        ->diffInSeconds(Carbon::now())
                );
            }
        }
    }
}