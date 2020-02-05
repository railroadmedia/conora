<?php

namespace Railroad\Railcontent\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Gedmo\Sortable\Entity\Repository\SortableRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(
 *     name="railcontent_content_hierarchy",
 *     indexes={
 *         @ORM\Index(name="railcontent_content_hierarchy_child_id_parent_id_unique", columns={"child_id","parent_id"}),
 *         @ORM\Index(name="railcontent_content_hierarchy_parent_id_index", columns={"parent_id"}),
 *         @ORM\Index(name="railcontent_content_hierarchy_child_id_index", columns={"child_id"}),
 *         @ORM\Index(name="railcontent_content_hierarchy_child_position_index", columns={"child_position"}),
 *         @ORM\Index(name="railcontent_content_hierarchy_created_on_index", columns={"created_on"})
 *     }
 * )
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 *
 */
class ContentHierarchy
{
    /**
     * @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer")
     * @var int
     */
    protected $id;

    /**
     * @Gedmo\SortablePosition()
     * @ORM\Column(type="integer")
     */
    protected $childPosition;

    /**
     * @Gedmo\SortableGroup()
     * @ORM\ManyToOne(targetEntity="Railroad\Railcontent\Entities\Content", inversedBy="parent")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     *
     */
    protected $parent;

    /**
     * @ORM\ManyToOne(targetEntity="Railroad\Railcontent\Entities\Content", inversedBy="child")
     * @ORM\JoinColumn(name="child_id", referencedColumnName="id")
     *
     */
    protected $child;

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
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param Content $parent
     */
    public function setParent(Content $parent)
    {
        $this->parent = $parent;

        $parent->addChild($this);
    }

    /**
     * @return mixed
     */
    public function getChild()
    {
        return $this->child;
    }

    /**
     * @param Content $child
     */
    public function setChild(Content $child)
    {
        $this->child = $child;

        $child->setParent($this);
    }

    /**
     * @return mixed
     */
    public function getChildPosition()
    {
        return $this->childPosition;
    }

    /**
     * @param $childPosition
     */
    public function setChildPosition($childPosition)
    {
        $this->childPosition = $childPosition;
    }
}