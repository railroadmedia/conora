<?php

namespace Railroad\Railcontent\Decorators\Mobile;

use Railroad\Railcontent\Decorators\DecoratorInterface;


class StripTagDecorator implements DecoratorInterface
{
    public function decorate(array $entities)
    : array {
        foreach ($entities as $entity) {

            $commentText = $entity->getComment();
            $entity->setComment(strip_tags(html_entity_decode($commentText)));

            foreach ($entity->getChildren() as $reply) {
                $reply->setComment(strip_tags(html_entity_decode($reply->getComment())));
            }
        }

        return $entities;
    }
}