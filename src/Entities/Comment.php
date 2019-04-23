<?php

namespace Railroad\Railcontent\Entities;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Railroad\Railcontent\Contracts\UserInterface;
use Railroad\Railcontent\Entities\Traits\DecoratedFields;

/**
 * @ORM\Entity(repositoryClass="Railroad\Railcontent\Repositories\CommentRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="railcontent_comments")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 *
 */
class Comment
{
    use DecoratedFields;

    /**
     * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $comment;

    /**
     * @ORM\Column(type="string", nullable=true, name="temporary_display_name")
     * @var string
     */
    protected $temporaryDisplayName;

    /**
     * @var User
     *
     * @ORM\Column(type="user_id", name="user_id")
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Railroad\Railcontent\Entities\Content")
     * @ORM\JoinColumn(name="content_id", referencedColumnName="id")
     *
     */
    private $content;

    /**
     * @ORM\ManyToOne(targetEntity="Railroad\Railcontent\Entities\Comment", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     *
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="Railroad\Railcontent\Entities\Comment", mappedBy="parent")
     */
    private $children;

    /**
     * @ORM\OneToMany(targetEntity="Railroad\Railcontent\Entities\CommentLikes", mappedBy="comment")
     */
    private $likes;
    /**
     * @var DateTime $createdOn
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime",  name="created_on")
     */
    protected $createdOn;

    /**
     * @ORM\Column(type="datetime", name="deleted_at", nullable=true)
     * @var DateTime
     */
    protected $deletedAt;

    /**
     * Comment constructor.
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->likes = new ArrayCollection();
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
     * @return mixed
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param mixed $content
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * @return mixed
     */
    public function getTemporaryDisplayName()
    {
        return $this->temporaryDisplayName;
    }

    /**
     * @param mixed $content
     */
    public function setTemporaryDisplayName($temporaryDisplayName)
    {
        $this->temporaryDisplayName = $temporaryDisplayName;
    }

    /**
     * @param UserInterface $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return UserInterface
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param Content $content
     */
    public function setContent(?Content $content)
    {
        $this->content = $content;
    }

    /**
     * @return mixed
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param mixed $content
     */
    public function setParent(?Comment $parent)
    {
        if ($parent) {
            $parent->addChildren($this);
        }

        $this->parent = $parent;
    }

    /**
     * Returns createdOn.
     *
     * @return string
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }

    /**
     * Sets createdOn.
     *
     * @param  string $createdOn
     * @return $this
     */
    public function setCreatedOn($createdOn)
    {
        $this->createdOn = $createdOn;
    }

    /**
     * Sets deletedAt.
     *
     * @param  string $deletedAt
     * @return $this
     */
    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
    }

    /**
     * Returns deletedAt.
     *
     * @return string
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * @return ArrayCollection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param Comment $comment
     * @return Comment
     */
    public function addChildren(
        Comment $comment
    ) {
        $this->children[] = $comment;
        return $this;
    }

    /**
     * @param Comment $comment
     * @return Comment
     */
    public function removeChildren(
        Comment $comment
    ) {

        if ($this->children->contains(
            $comment
        )) {
            $this->children->removeElement($comment);

            if ($comment->getParent() === $this) {
                $comment->setParent(null);
            }
        }

        return $this;
    }

    /**
     * @param CommentLikes $likes
     */
    public function addLikes(CommentLikes $likes)
    {
        $this->likes[] = $likes;
    }

    /**
     * @return ArrayCollection
     */
    public function getLikes()
    {
        return $this->likes;
    }
}