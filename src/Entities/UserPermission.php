<?php

namespace Railroad\Railcontent\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Railroad\Railcontent\Contracts\UserInterface;

/**
 * @ORM\Entity(repositoryClass="Railroad\Railcontent\Repositories\UserPermissionsRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="railcontent_user_permissions")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 *
 */
class UserPermission
{
    use TimestampableEntity;
    /**
     * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer")
     * @var int
     */
    protected $id;

    /**
     * @var User
     *
     * @ORM\Column(type="user_id", name="user_id")
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Railroad\Railcontent\Entities\Permission")
     * @ORM\JoinColumn(name="permission_id", referencedColumnName="id")
     *
     */
    private $permission;

    /**
     * @ORM\Column(type="datetime", name="start_date")
     *
     */
    protected $startDate;

    /**
     * @ORM\Column(type="datetime", name="expiration_date", nullable=true)
     *
     * @var \DateTime
     */
    protected $expirationDate;

    /**
     * @return int
     */
    public function getId()
    : int
    {
        return $this->id;
    }

    /**
     * @return User|null
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param string $value
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    /**
     * @param $expirationDate
     */
    public function setExpirationDate($expirationDate)
    {
        $this->expirationDate = $expirationDate;
    }

    /**
     * @return mixed
     */
    public function getPermission()
    {
        return $this->permission;
    }

    /**
     * @param Permission $permission
     */
    public function setPermission(Permission $permission)
    {
        $this->permission = $permission;
    }
}