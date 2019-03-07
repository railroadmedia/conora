<?php

namespace Railroad\Railcontent\Entities\Traits;

use Doctrine\Common\Collections\ArrayCollection;
use Railroad\Railcontent\Entities\ContentData;
use Railroad\Railcontent\Entities\ContentExercise;
use Railroad\Railcontent\Entities\ContentInstructor;
use Railroad\Railcontent\Entities\ContentKey;
use Railroad\Railcontent\Entities\ContentKeyPitchType;
use Railroad\Railcontent\Entities\ContentPlaylist;
use Railroad\Railcontent\Entities\ContentSbtBpm;
use Railroad\Railcontent\Entities\ContentSbtExerciseNumber;
use Railroad\Railcontent\Entities\ContentTag;
use Railroad\Railcontent\Entities\ContentTopic;

trait ContentFieldsAssociations
{
    /**
     * @ORM\OneToMany(targetEntity="ContentExercise", mappedBy="content", cascade={"persist"})
     */
    protected $exercise;

    /**
     * @ORM\OneToOne(targetEntity="ContentInstructor",mappedBy="content", cascade={"persist"})
     */
    protected $instructor;

    /**
     * @ORM\OneToMany(targetEntity="ContentVimeoVideo", mappedBy="content")
     */
    protected $vimeoVideo;

    /**
     * @ORM\OneToMany(targetEntity="Railroad\Railcontent\Entities\ContentTopic", mappedBy="content",
     *     cascade={"persist","remove"})
     */
    private $topic;

    /**
     * @ORM\OneToMany(targetEntity="Railroad\Railcontent\Entities\ContentTag", mappedBy="content",
     *     cascade={"persist","remove"})
     */
    private $tag;

    /**
     * @ORM\OneToMany(targetEntity="Railroad\Railcontent\Entities\ContentKey", mappedBy="content",
     *     cascade={"persist","remove"})
     */
    private $key;

    /**
     * @ORM\OneToMany(targetEntity="Railroad\Railcontent\Entities\ContentKeyPitchType", mappedBy="content",
     *     cascade={"persist","remove"})
     */
    private $keyPitchType;

    /**
     * @ORM\OneToMany(targetEntity="Railroad\Railcontent\Entities\ContentSbtBpm", mappedBy="content",
     *     cascade={"persist","remove"})
     */
    private $sbtBpm;

    /**
     * @ORM\OneToMany(targetEntity="Railroad\Railcontent\Entities\ContentSbtExerciseNumber", mappedBy="content",
     *     cascade={"persist","remove"})
     */
    private $sbtExerciseNumber;

    /**
     * @ORM\OneToMany(targetEntity="Railroad\Railcontent\Entities\ContentPlaylist", mappedBy="content",
     *     cascade={"persist","remove"})
     */
    private $playlist;

    /**
     * @return Content|null
     */
    public function getExercise()
    {
        return $this->exercise;
    }

    /**
     * @param ContentExercise $exercise
     * @return $this|void
     */
    public function addExercise(ContentExercise $exercise)
    {
        if ($this->exercise->contains($exercise)) {
            // Do nothing if its already part of our collection
            return;
        }

        $predictate = function ($element) use ($exercise) {
            return $element->getExercise() === $exercise->getExercise();
        };
        $exist = $this->exercise->filter($predictate);

        if ($exist->isEmpty()) {
            $this->exercise->add($exercise);
        } else {
            $exercises = $exist->first();
            if ($exercises->getPosition() == $exercise->getPosition()) {
                return $this;
            }

            $key = $exist->key();
            if ($exercise->getPosition()) {
                $this->getExercise()
                    ->get($key)
                    ->setPosition($exercise->getPosition());
            }
        }

        return $this;
    }

    /**
     * @param ContentExercise $contentExercise
     */
    public function removeExercise(ContentExercise $contentExercise)
    {
        // If does not exist in the collection, then we don't need to do anything
        if (!$this->exercise->contains($contentExercise)) {
            return;
        }

        $this->exercise->removeElement($contentExercise);
    }

    /**
     * @return mixed
     */
    public function getInstructor()
    {
        return $this->instructor;
    }

    /**
     * @param ContentInstructor $instructor
     * @return $this
     */
    public function addInstructor($instructor)
    {
        $this->instructor = $instructor;

        return $this;
    }

    /**
     * @param ContentInstructor $instructor
     * @return $this
     */
    public function setInstructor($instructor)
    {
        $this->instructor = $instructor;

        return $this;
    }

    /**
     * @param ContentInstructor $contentInstructor
     */
    public function removeInstructor(ContentInstructor $contentInstructor)
    {
        // If does not exist in the collection, then we don't need to do anything
        if (!$this->instructor->contains($contentInstructor)) {
            return;
        }

        $this->instructor->removeElement($contentInstructor);
    }

    /**
     * @return Content|null
     */
    public function getVimeoVideo()
    {
        return $this->vimeoVideo;
    }

    /**
     * @return Content|null
     */
    public function getData()
    {
        return $this->data;
    }



    /**
     * @param ContentTopic $contentTopic
     * @return $this
     */
    public function addTopic(ContentTopic $contentTopic)
    {
        if ($this->topic->contains($contentTopic)) {
            // Do nothing if its already part of our collection
            return;
        }

        $predictate = function ($element) use ($contentTopic) {
            return $element->getTopic() === $contentTopic->getTopic();
        };
        $existTopic = $this->topic->filter($predictate);

        if ($existTopic->isEmpty()) {
            $this->topic->add($contentTopic);
        } else {
            $topic = $existTopic->first();
            if ($topic->getPosition() == $contentTopic->getPosition()) {
                return $this;
            }

            $key = $existTopic->key();
            if ($contentTopic->getPosition()) {
                $this->getTopic()
                    ->get($key)
                    ->setPosition($contentTopic->getPosition());
            }
        }

        return $this;
    }

    /**
     * @param ContentTopic $contentTopic
     * @return $this
     */
    public function removeTopic(ContentTopic $contentTopic)
    {
        // If the topic does not exist in the collection, then we don't need to do anything
        if (!$this->topic->contains($contentTopic)) {
            return;
        }

        $this->topic->removeElement($contentTopic);
    }

    /**
     * @return ArrayCollection
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * @param ContentTag $contentTag
     * @return $this|void
     */
    public function addTag(ContentTag $contentTag)
    {
        if ($this->tag->contains($contentTag)) {
            // Do nothing if its already part of our collection
            return;
        }

        $predictate = function ($element) use ($contentTag) {
            return $element->getTag() === $contentTag->getTag();
        };
        $existTag = $this->topic->filter($predictate);

        if ($existTag->isEmpty()) {
            $this->tag->add($contentTag);
        } else {
            $tag = $existTag->first();
            if ($tag->getPosition() == $contentTag->getPosition()) {
                return $this;
            }

            $key = $existTag->key();
            if ($contentTag->getPosition()) {
                $this->getTag()
                    ->get($key)
                    ->setPosition($contentTag->getPosition());
            }
        }

        return $this;
    }

    /**
     * @param ContentTag $contentTopic
     */
    public function removeTag(ContentTag $contentTag)
    {
        // If the tag does not exist in the collection, then we don't need to do anything
        if (!$this->tag->contains($contentTag)) {
            return;
        }

        $this->tag->removeElement($contentTag);
    }

    /**
     * @return ArrayCollection
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @param ContentKey $contentKey
     * @return $this|void
     */
    public function addKey(ContentKey $contentKey)
    {
        if ($this->key->contains($contentKey)) {
            // Do nothing if its already part of our collection
            return;
        }

        $predictate = function ($element) use ($contentKey) {
            return $element->getKey() === $contentKey->getKey();
        };

        $exist = $this->key->filter($predictate);

        if ($exist->isEmpty()) {
            $this->key->add($contentKey);
        } else {
            $key = $exist->first();
            if ($key->getPosition() == $contentKey->getPosition()) {
                return $this;
            }

            $key = $exist->key();
            if ($contentKey->getPosition()) {
                $this->getKey()
                    ->get($key)
                    ->setPosition($contentKey->getPosition());
            }
        }

        return $this;
    }

    /**
     * @param ContentKey $contentKey
     */
    public function removeKey(ContentKey $contentKey)
    {
        // If does not exist in the collection, then we don't need to do anything
        if (!$this->key->contains($contentKey)) {
            return;
        }

        $this->key->removeElement($contentKey);
    }

    /**
     * @return ArrayCollection
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param ContentKeyPitchType $contentKeyPitchType
     * @return $this|void
     */
    public function addKeyPitchType(ContentKeyPitchType $contentKeyPitchType)
    {
        if ($this->keyPitchType->contains($contentKeyPitchType)) {
            // Do nothing if its already part of our collection
            return;
        }

        $predictate = function ($element) use ($contentKeyPitchType) {
            return $element->getKeyPitchType() === $contentKeyPitchType->getKeyPitchType();
        };

        $exist = $this->keyPitchType->filter($predictate);

        if ($exist->isEmpty()) {
            $this->keyPitchType->add($contentKeyPitchType);
        } else {
            $key = $exist->first();
            if ($key->getPosition() == $contentKeyPitchType->getPosition()) {
                return $this;
            }

            $key = $exist->key();
            if ($contentKeyPitchType->getPosition()) {
                $this->getKey()
                    ->get($key)
                    ->setPosition($contentKeyPitchType->getPosition());
            }
        }

        return $this;
    }

    /**
     * @param ContentKeyPitchType $contentKeyPitchType
     */
    public function removeKeyPitchType(ContentKeyPitchType $contentKeyPitchType)
    {
        // If does not exist in the collection, then we don't need to do anything
        if (!$this->keyPitchType->contains($contentKeyPitchType)) {
            return;
        }

        $this->keyPitchType->removeElement($contentKeyPitchType);
    }

    /**
     * @return ArrayCollection
     */
    public function getKeyPitchType()
    {
        return $this->keyPitchType;
    }

    /**
     * @param ContentSbtBpm $contentSbtBpm
     * @return $this|void
     */
    public function addSbtBpm(ContentSbtBpm $contentSbtBpm)
    {
        if ($this->sbtBpm->contains($contentSbtBpm)) {
            // Do nothing if its already part of our collection
            return;
        }

        $predictate = function ($element) use ($contentSbtBpm) {
            return $element->getSbtBpm() === $contentSbtBpm->getSbtBpm();
        };

        $exist = $this->sbtBpm->filter($predictate);

        if ($exist->isEmpty()) {
            $this->sbtBpm->add($contentSbtBpm);
        } else {
            $key = $exist->first();
            if ($key->getPosition() == $contentSbtBpm->getPosition()) {
                return $this;
            }

            $key = $exist->key();
            if ($contentSbtBpm->getPosition()) {
                $this->getKey()
                    ->get($key)
                    ->setPosition($contentSbtBpm->getPosition());
            }
        }

        return $this;
    }

    /**
     * @param ContentSbtBpm $contentSbtBpm
     */
    public function removeSbtBpm(ContentSbtBpm $contentSbtBpm)
    {
        // If does not exist in the collection, then we don't need to do anything
        if (!$this->sbtBpm->contains($contentSbtBpm)) {
            return;
        }

        $this->sbtBpm->removeElement($contentSbtBpm);
    }

    /**
     * @return ArrayCollection
     */
    public function getSbtBpm()
    {
        return $this->sbtBpm;
    }

    /**
     * @param ContentSbtExerciseNumber $contentSbtExerciseNumber
     * @return $this|void
     */
    public function addSbtExerciseNumber(ContentSbtExerciseNumber $contentSbtExerciseNumber)
    {
        if ($this->sbtExerciseNumber->contains($contentSbtExerciseNumber)) {
            // Do nothing if its already part of our collection
            return;
        }

        $predictate = function ($element) use ($contentSbtExerciseNumber) {
            return $element->getSbtExerciseNumber() === $contentSbtExerciseNumber->getSbtExerciseNumber();
        };

        $exist = $this->sbtExerciseNumber->filter($predictate);

        if ($exist->isEmpty()) {
            $this->sbtExerciseNumber->add($contentSbtExerciseNumber);
        } else {
            $sbtExerciseNumber = $exist->first();
            if ($sbtExerciseNumber->getPosition() == $contentSbtExerciseNumber->getPosition()) {
                return $this;
            }

            $key = $exist->key();
            if ($contentSbtExerciseNumber->getPosition()) {
                $this->getKey()
                    ->get($key)
                    ->setPosition($contentSbtExerciseNumber->getPosition());
            }
        }

        return $this;
    }

    /**
     * @param ContentSbtExerciseNumber $contentSbtExerciseNumber
     */
    public function removeSbtExerciseNumber(ContentSbtExerciseNumber $contentSbtExerciseNumber)
    {
        // If does not exist in the collection, then we don't need to do anything
        if (!$this->sbtExerciseNumber->contains($contentSbtExerciseNumber)) {
            return;
        }

        $this->sbtExerciseNumber->removeElement($contentSbtExerciseNumber);
    }

    /**
     * @return ArrayCollection
     */
    public function getSbtExerciseNumber()
    {
        return $this->sbtExerciseNumber;
    }

    /**
     * @param ContentPlaylist $contentPlaylist
     * @return $this|void
     */
    public function addPlaylist(ContentPlaylist $contentPlaylist)
    {
        if ($this->playlist->contains($contentPlaylist)) {
            // Do nothing if its already part of our collection
            return;
        }

        $predictate = function ($element) use ($contentPlaylist) {
            return $element->getPlaylist() === $contentPlaylist->getPlaylist();
        };

        $exist = $this->playlist->filter($predictate);

        if ($exist->isEmpty()) {
            $this->playlist->add($contentPlaylist);
        } else {
            $playlist = $exist->first();
            if ($playlist->getPosition() == $contentPlaylist->getPosition()) {
                return $this;
            }

            $key = $exist->key();
            if ($contentPlaylist->getPosition()) {
                $this->getKey()
                    ->get($key)
                    ->setPosition($contentPlaylist->getPosition());
            }
        }

        return $this;
    }

    /**
     * @param ContentPlaylist $contentPlaylist
     */
    public function removePlaylist(ContentPlaylist $contentPlaylist)
    {
        // If does not exist in the collection, then we don't need to do anything
        if (!$this->playlist->contains($contentPlaylist)) {
            return;
        }

        $this->playlist->removeElement($contentPlaylist);
    }

    /**
     * @return ArrayCollection
     */
    public function getPlaylist()
    {
        return $this->playlist;
    }

}