<?php

namespace Railroad\Railcontent\Transformers;

use Doctrine\ORM\EntityManager;
use Illuminate\Support\Collection;
use League\Fractal\TransformerAbstract;
use Railroad\Doctrine\Serializers\BasicEntitySerializer;
use Railroad\Railcontent\Entities\Comment;

class CommentTransformer extends TransformerAbstract
{
    public function transform(Comment $comment)
    {
        $entityManager = app()->make(EntityManager::class);

        if (count($comment->getChildren()) > 0) {
            $this->defaultIncludes = ['content', 'replies'];
        } else {
            $this->defaultIncludes = ['content'];
        }

        $serializer = new BasicEntitySerializer();

        return (new Collection(
            $serializer->serializeToUnderScores(
                $comment,
                $entityManager->getClassMetadata(get_class($comment))
            )
        ))->toArray();
    }

    public function includeReplies(Comment $comment)
    {
        return $this->collection(
            $comment->getChildren(),
            new CommentTransformer(),
            'comment'
        );
    }

    public function includeContent(Comment $comment)
    {
        return $this->item($comment->getContent(), new ContentTransformer(), 'content');
    }
}