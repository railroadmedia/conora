<?php

namespace Railroad\Railcontent\Transformers;

use Doctrine\ORM\EntityManager;
use Illuminate\Support\Collection;
use League\Fractal\TransformerAbstract;
use Railroad\Doctrine\Serializers\BasicEntitySerializer;
use Railroad\Railcontent\Entities\Content;
use Railroad\Railcontent\Entities\ContentPermission;

class DecoratedContentTransformer extends TransformerAbstract
{
    public function transform(Content $content)
    {
        $entityManager = app()->make(EntityManager::class);

        $serializer = new BasicEntitySerializer();

        $contents = (new Collection(
            $serializer->serializeToUnderScores(
                $content,
                $entityManager->getClassMetadata(get_class($content))
            )
        ))->toArray();

        $defaultIncludes = [];
        if (count($content->getData()) > 0) {
            $defaultIncludes[] = 'data';
        }

        if ($content->getInstructor()) {
            $defaultIncludes[] = 'instructor';
        }

        if (count($content->getTopic()) > 0) {
            $defaultIncludes[] = 'topic';
        }

        if (count($content->getExercise()) > 0) {
            $defaultIncludes[] = 'exercise';
        }

        if (count($content->getTag()) > 0) {
            $defaultIncludes[] = 'tag';
        }

        if (count($content->getKey()) > 0) {
            $defaultIncludes[] = 'key';
        }

        if (count($content->getKeyPitchType()) > 0) {
            $defaultIncludes[] = 'keyPitchType';
        }

        if (count($content->getSbtBpm()) > 0) {
            $defaultIncludes[] = 'sbtBpm';
        }

        if (count($content->getSbtExerciseNumber()) > 0) {
            $defaultIncludes[] = 'sbtExerciseNumber';
        }

        if (count($content->getPlaylist()) > 0) {
            $defaultIncludes[] = 'playlist';
        }

        if ($content->getParent()) {
            $defaultIncludes[] = 'parent';
        }

        $permissions = $this->getPermissions($content, $entityManager);

        if ($permissions) {
            $content->setPermissions($permissions);
            $defaultIncludes[] = 'permissions';
        }

        $this->setDefaultIncludes($defaultIncludes);

        return $contents;
    }

    /**
     * @param Content $content
     * @return \League\Fractal\Resource\Collection
     */
    public function includeTag(Content $content)
    {
        return $this->collection(
            $content->getTag(),
            new ContentTagTransformer(),
            'tag'
        );
    }

    /**
     * @param Content $content
     * @return \League\Fractal\Resource\Collection
     */
    public function includeData(Content $content)
    {
        return $this->collection(
            $content->getData(),
            new ContentDataTransformer(),
            'contentData'
        );
    }

    /**
     * @param Content $content
     * @return \League\Fractal\Resource\Item
     */
    public function includeInstructor(Content $content)
    {
        return $this->item(
            $content->getInstructor(),
            new ContentInstructorTransformer(),
            'instructor'
        );
    }

    /**
     * @param Content $content
     * @return \League\Fractal\Resource\Collection
     */
    public function includeTopic(Content $content)
    {
        return $this->collection(
            $content->getTopic(),
            new ContentTopicTransformer(),
            'topic'
        );
    }

    /**
     * @param Content $content
     * @return \League\Fractal\Resource\Collection
     */
    public function includeKey(Content $content)
    {
        return $this->collection(
            $content->getKey(),
            new ContentKeyTransformer(),
            'key'
        );
    }

    /**
     * @param Content $content
     * @return \League\Fractal\Resource\Collection
     */
    public function includeKeyPitchType(Content $content)
    {
        return $this->collection(
            $content->getKeyPitchType(),
            new ContentKeyPitchTypeTransformer(),
            'keyPitchType'
        );
    }

    /**
     * @param Content $content
     * @return \League\Fractal\Resource\Collection
     */
    public function includeSbtBpm(Content $content)
    {
        return $this->collection(
            $content->getSbtBpm(),
            new ContentSbtBpmTransformer(),
            'sbtBpm'
        );
    }

    /**
     * @param Content $content
     * @return \League\Fractal\Resource\Collection
     */
    public function includeExercise(Content $content)
    {
        return $this->collection(
            $content->getExercise(),
            new ContentExerciseTransformer(),
            'exercise'
        );
    }

    /**
     * @param Content $content
     * @return \League\Fractal\Resource\Collection
     */
    public function includePlaylist(Content $content)
    {
        return $this->collection(
            $content->getPlaylist(),
            new ContentPlaylistTransformer(),
            'playlist'
        );
    }

    /**
     * @param Content $content
     * @return \League\Fractal\Resource\Collection
     */
    public function includeSbtExerciseNumber(Content $content)
    {
        return $this->collection(
            $content->getSbtExerciseNumber(),
            new ContentSbtExerciseNumberTransformer(),
            'sbtExerciseNumber'
        );
    }

    /**
     * @param Content $content
     * @return \League\Fractal\Resource\Item
     */
    public function includeParent(Content $content)
    {
        return $this->item(
            $content->getParent(),
            new ContentParentTransformer(),
            'parent'
        );
    }

    /**
     * @param Content $content
     * @return \League\Fractal\Resource\Collection
     */
    public function includePermissions(Content $content)
    {
        return $this->collection(
            $content->getPermissions(),
            new ContentPermissionTransformer(),
            'permissions'
        );
    }

    /**
     * @param Content $content
     * @param $entityManager
     * @return mixed
     */
    private function getPermissions(Content $content, $entityManager)
    {
        $contentPermission = $entityManager->getRepository(ContentPermission::class);

        $permissions =
            $contentPermission->createQueryBuilder('cp')
                ->where('cp.content IN (:content)')
                ->orWhere('cp.contentType = :contentType')
                ->setParameter('content', $content->getId())
                ->setParameter('contentType', $content->getType())
                ->getQuery()
                ->getResult();
        return $permissions;
    }
}