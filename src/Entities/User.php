<?php

namespace Railroad\Railcontent\Entities;

use Railroad\Doctrine\Contracts\UserEntityInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="users")
 */
class User implements UserEntityInterface
{
    /**
     * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $email;

    /**
     * User constructor.
     *
     * @param int $id
     * @param string $email
     */
    public function __construct(int $id, $email)
    {
        $this->id = $id;
        $this->email = $email;
    }

    /**
     * @return int
     */
    public function getId()
    : int
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    : void {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getEmail()
    : string
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    : void {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        /*
        method needed by UnitOfWork
        https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/cookbook/custom-mapping-types.html
        */
        return (string)$this->getId();
    }
}