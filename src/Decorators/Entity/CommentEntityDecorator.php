<?php

namespace Railroad\Railcontent\Decorators\Entity;

use Railroad\Railcontent\Decorators\DecoratorInterface;
use Railroad\Railcontent\Entities\CommentEntity;

class CommentEntityDecorator implements DecoratorInterface
{
    public function decorate($commentResults)
    {
        $entities = [];

        foreach ($commentResults as $resultsIndex => $result) {
            $entities[$resultsIndex] = new CommentEntity($result);
        }

        return $entities;
    }
}