<?php

namespace Railroad\Railcontent\Tests\Functional\Controllers;

use Carbon\Carbon;
use Faker\ORM\Doctrine\Populator;
use Railroad\Railcontent\Entities\Comment;
use Railroad\Railcontent\Entities\Content;
use Railroad\Railcontent\Factories\CommentFactory;
use Railroad\Railcontent\Factories\ContentFactory;
use Railroad\Railcontent\Repositories\ContentRepository;
use Railroad\Railcontent\Services\CommentService;
use Railroad\Railcontent\Services\ConfigService;
use Railroad\Railcontent\Services\ContentService;
use Railroad\Railcontent\Tests\Hydrators\CommentFakeDataHydrator;
use Railroad\Railcontent\Tests\RailcontentTestCase;
use Railroad\Railcontent\Transformers\CommentTransformer;

class CommentJsonControllerTest extends RailcontentTestCase
{
    private $populator;

    protected function setUp()
    {
        parent::setUp();

        $this->fakeDataHydrator = new CommentFakeDataHydrator($this->entityManager);

        $this->populator = new Populator($this->faker, $this->entityManager);
        $this->populator->addEntity(
            Content::class,
            1,
            [
                'slug' => 'slug1',
                'status' => 'published',
                'type' => 'course',
                'brand' => ConfigService::$brand,
            ]
        );
        $this->populator->execute();

        $this->populator->addEntity(
            Content::class,
            1,
            [
                'slug' => 'slug1',
                'status' => 'published',
                'type' => $this->faker->word,
                'brand' => ConfigService::$brand,
            ]
        );
        $this->populator->execute();
    }

    public function fakeComment($nr = 1, $commentData = [])
    {
        if (empty($commentData)) {
            $commentData = [
                'userId' => 1,
                'content' => $this->entityManager->getRepository(Content::class)
                    ->find(1),
            ];
        }
        $this->populator->addEntity(
            Comment::class,
            $nr,
            $commentData

        );
        $fakePopulator = $this->populator->execute();

        return $fakePopulator[Comment::class];
    }

    public function fakeContent($nr = 1, $contentData = [])
    {
        if (empty($contentData)) {
            $contentData = [
                'brand' => ConfigService::$brand,
            ];
        }
        $this->populator->addEntity(
            Content::class,
            $nr,
            $contentData

        );
        $fakePopulator = $this->populator->execute();

        return $fakePopulator[Content::class];
    }

    public function test_add_comment_response()
    {
        $userId = $this->createAndLogInNewUser();

        $attributes = $this->fakeDataHydrator->getAttributeArray(Comment::class, new CommentTransformer());

        $attributes['user_id'] = $userId;

        unset($attributes['id']);
        unset($attributes['created_on']);
        unset($attributes['deleted_at']);

        $response = $this->call(
            'PUT',
            'railcontent/comment',
            [
                'data' => [
                    'attributes' => $attributes,
                    'relationships' => [
                        'content' => [
                            'type' => 'content',
                            'id' => 1,
                        ],
                    ],
                ],
            ]
        );

        $expectedResults = [
            'type' => 'comment',
            'id' => 1,
            'attributes' => $attributes,
        ];

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArraySubset($expectedResults, $response->decodeResponseJson('data'));

    }

    public function test_add_comment_on_not_commentable_type_response()
    {
        $this->createAndLogInNewUser();

        $response = $this->call(
            'PUT',
            'railcontent/comment',
            [
                'data' => [
                    'attributes' => [
                        'comment' => $this->faker->text(),
                    ],
                    'relationships' => [
                        'content' => [
                            'type' => 'content',
                            'id' => 2,
                        ],
                    ],
                ],
            ]
        );

        $this->assertEquals(403, $response->getStatusCode());
        $response->assertJsonFragment(['The content type does not allow comments.']);
    }

    public function test_add_comment_validation_errors()
    {
        $this->createAndLogInNewUser();

        $response = $this->call(
            'PUT',
            'railcontent/comment',
            [
                'data' => [
                    'relationships' => [
                        'content' => [
                            'type' => 'content',
                            'id' => rand(),
                        ],
                    ],
                ],
            ]
        );

        $this->assertEquals(422, $response->getStatusCode());
        $response->assertJsonFragment(
            ['The comment field is required.'],
            ['The selected content id is invalid.'],
            ['The display name field is required.']
        );
    }

    public function test_update_my_comment_response()
    {
        $userId = $this->createAndLogInNewUser();
        $comment = $this->fakeComment();

        $updatedComment = $this->faker->text();
        $response = $this->call(
            'PATCH',
            'railcontent/comment/' . $comment[0]->getId(),
            [
                'data' => [
                    'type' => 'comment',
                    'attributes' => [
                        'comment' => $updatedComment,
                    ],
                ],
            ]
        );

        $this->assertEquals(201, $response->getStatusCode());

        $expectedResults = [
            'comment' => $updatedComment,
            'user_id' => $userId,

        ];

        $this->assertArraySubset($expectedResults, $response->decodeResponseJson('data')['attributes']);
    }

    public function test_update_other_comment_response()
    {
        $userId = $this->createAndLogInNewUser();
        $comment = $this->fakeComment(
            1,
            [
                'userId' => rand(2, 10),
            ]
        );

        $updatedComment = $this->faker->text();
        $response = $this->call(
            'PATCH',
            'railcontent/comment/' . $comment[0]->getId(),
            [
                'data' => [
                    'type' => 'comment',
                    'attributes' => [
                        'comment' => $updatedComment,
                    ],
                ],
            ]
        );

        $this->assertEquals(403, $response->getStatusCode());

        $response->assertJsonFragment(['Update failed, you can update only your comments.']);
    }

    public function test_update_comment_validation_errors()
    {
        $userId = $this->createAndLogInNewUser();

        $response = $this->call(
            'PATCH',
            'railcontent/comment/' . 1,
            [
                'data' => [
                    'attributes' => [
                        'display_name' => '',
                    ],
                    'relationships' => [
                        'content' => [
                            'type' => 'content',
                            'id' => rand(30, 100),
                        ],
                        'parent' => [
                            'type' => 'comment',
                            'id' => rand(3, 10),
                        ],
                    ],
                ],
            ]
        );

        $this->assertEquals(422, $response->getStatusCode());

        $response->assertJsonFragment(['The selected content is invalid.']);
        $response->assertJsonFragment(['The selected parent is invalid.']);
        $response->assertJsonFragment(['The display name field must have a value.']);
    }

    public function test_update_inexistent_comment_response()
    {
        $userId = $this->createAndLogInNewUser();
        $randomId = rand();
        $response = $this->call('PATCH', 'railcontent/comment/' . $randomId);

        $this->assertEquals(404, $response->getStatusCode());

        $response->assertJsonFragment(['Update failed, comment not found with id: ' . $randomId]);
    }

    public function test_admin_can_update_other_comment_response()
    {
        $userId = $this->createAndLogInNewUser();
        $comment = $this->fakeComment(
            1,
            [
                'userId' => rand(2, 10),
            ]
        );

        CommentService::$canManageOtherComments = true;
        $updatedComment = $this->faker->text();
        $response = $this->call(
            'PATCH',
            'railcontent/comment/' . $comment[0]->getId(),
            [
                'data' => [
                    'type' => 'comment',
                    'attributes' => [
                        'comment' => $updatedComment,
                    ],
                ],
            ]
        );

        $this->assertEquals(201, $response->getStatusCode());

        $expectedResults = [
            'comment' => $updatedComment,
        ];

        $this->assertArraySubset($expectedResults, $response->decodeResponseJson('data')['attributes']);
    }

    public function test_delete_my_comment_response()
    {
        $userId = $this->createAndLogInNewUser();
        $comment = $this->fakeComment();

        $response = $this->call('DELETE', 'railcontent/comment/' . $comment[0]->getId());

        $this->assertEquals(204, $response->getStatusCode());
    }

    public function test_user_can_not_delete_others_comment()
    {
        $userId = $this->createAndLogInNewUser();
        $comment = $this->fakeComment(
            1,
            [
                'userId' => rand(2, 10),
            ]
        );

        CommentService::$canManageOtherComments = false;

        $response = $this->call('DELETE', 'railcontent/comment/' . $comment[0]->getId());

        $this->assertEquals(403, $response->getStatusCode());
        $response->assertJsonFragment(['Delete failed, you can delete only your comments.']);
    }

    public function test_delete_inexistent_comment_response()
    {
        $randomId = rand();
        $response = $this->call('DELETE', 'railcontent/comment/' . $randomId);

        $this->assertEquals(404, $response->getStatusCode());

        $response->assertJsonFragment(['Delete failed, comment not found with id: ' . $randomId]);
    }

    public function test_admin_can_delete_other_comment_response()
    {
        $userId = $this->createAndLogInNewUser();
        $comment = $this->fakeComment(
            1,
            [
                'userId' => rand(2, 10),
            ]
        );

        CommentService::$canManageOtherComments = true;

        $response = $this->call('DELETE', 'railcontent/comment/' . $comment[0]->getId());

        $this->assertEquals(204, $response->getStatusCode());
    }

    public function test_reply_to_a_comment()
    {
        $userId = $this->createAndLogInNewUser();
        $reply = $this->faker->paragraph;
        $comment = $this->fakeComment();

        $response = $this->call(
            'PUT',
            'railcontent/comment/reply',
            [
                'data' => [
                    'attributes' => [
                        'comment' => $reply,
                    ],
                    'relationships' => [
                        'parent' => [
                            'type' => 'comment',
                            'id' => $comment[0]->getId(),
                        ],
                    ],
                ],
            ]
        );

        $this->assertEquals(200, $response->getStatusCode());

        $expectedResults = [
            'data' => [
                'type' => 'comment',
                'attributes' => [
                    'comment' => $reply,
                    'user_id' => $userId,
                ],
            ],
        ];

        $this->assertArraySubset($expectedResults, $response->decodeResponseJson());

    }

    public function test_reply_to_a_comment_validation_errors()
    {
        $this->createAndLogInNewUser();

        $response = $this->call('PUT', 'railcontent/comment/reply');

        $this->assertEquals(422, $response->getStatusCode());
        $response->assertJsonFragment(['The comment field is required.']);
        $response->assertJsonFragment(['The parent field is required.']);
    }

    public function test_pull_comments_when_not_exists()
    {
        $response = $this->call('GET', 'railcontent/comment');

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals([], $response->decodeResponseJson('data'));
    }

    public function test_pull_comments_paginated()
    {
        $limit = 10;
        $totalNumber = $this->faker->numberBetween($limit, ($limit + 25));
        //        $this->populator->addEntity(
        //            Content::class,
        //            1,
        //            [
        //                'slug' => 'slug1',
        //                'status' => 'published',
        //                'type' => 'course',
        //                'brand' => ConfigService::$brand,
        //            ]
        //        );
        //
        //        $this->populator->execute();

        $comments = $this->fakeComment(
            $totalNumber,
            [
                'parent' => null,
            ]
        );

        $request = [
            'limit' => $limit,
            'sort' => '-createdOn',
        ];
        $response = $this->call(
            'GET',
            'railcontent/comment',
            $request + ['page' => 1]
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->decodeResponseJson()['data'];

        $this->assertEquals($request['limit'], count($data));
    }

    public function test_pull_content_comments_paginated()
    {
        $page = 2;
        $limit = 3;
        $totalNumber = $this->faker->numberBetween(10, ($limit + 25));

        $this->populator->addEntity(
            Comment::class,
            $totalNumber,
            [
                'content' => $this->entityManager->getRepository(Content::class)
                    ->find(1),
                'parent' => null,
            ]
        );

        $this->populator->execute();

        $response = $this->call(
            'GET',
            'railcontent/comment',
            [
                'page' => $page,
                'limit' => $limit,
                'content_id' => 1,
            ]
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->decodeResponseJson('data');

        $this->assertEquals($limit, count($data));
    }

    public function test_pull_user_comments_paginated()
    {
        $page = 1;
        $limit = 3;
        $totalNumber = $this->faker->numberBetween(3, 10);
        $userId = 1;

        $this->populator->addEntity(
            Comment::class,
            $totalNumber,
            [
                'userId' => $userId,
                'parent' => null,
            ]
        );

        $fakeData = $this->populator->execute();

        $response = $this->call(
            'GET',
            'railcontent/comment',
            [
                'page' => $page,
                'limit' => $limit,
                'user_id' => $userId,
            ]
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(
            count($fakeData[Comment::class]),
            $response->decodeResponseJson('meta')['pagination']['total']
        );

        $data = $response->decodeResponseJson()['data'];

        $this->assertEquals($limit, count($data));

        foreach ($data as $res) {
            $this->assertEquals($userId, $res['attributes']['user_id']);
        }
    }

    public function _test_pull_comments_ordered_by_like_count()
    {
        // create content
        $content = $this->contentFactory->create(
            $this->faker->word,
            $this->faker->randomElement(ConfigService::$commentableContentTypes),
            ContentService::STATUS_PUBLISHED
        );

        // create content comments
        $comments = [];

        $comments[] = $this->commentFactory->create($this->faker->text, $content['id'], null, rand());
        $comments[] = $this->commentFactory->create($this->faker->text, $content['id'], null, rand());
        $comments[] = $this->commentFactory->create($this->faker->text, $content['id'], null, rand());
        $comments[] = $this->commentFactory->create($this->faker->text, $content['id'], null, rand());
        $comments[] = $this->commentFactory->create($this->faker->text, $content['id'], null, rand());

        // select two comment ids
        $firstOrderedCommentId = $comments[2]['id'];
        $secondOrderedCommentId = $comments[4]['id'];

        // add a known number of likes to selected comments
        $commentThreeLikeOne = [
            'comment_id' => $firstOrderedCommentId,
            'user_id' => $this->faker->randomNumber(),
            'created_on' => Carbon::instance($this->faker->dateTime)
                ->toDateTimeString(),
        ];

        $this->databaseManager->table(ConfigService::$tableCommentLikes)
            ->insertGetId($commentThreeLikeOne);

        $commentThreeLikeTwo = [
            'comment_id' => $firstOrderedCommentId,
            'user_id' => $this->faker->randomNumber(),
            'created_on' => Carbon::instance($this->faker->dateTime)
                ->toDateTimeString(),
        ];

        $this->databaseManager->table(ConfigService::$tableCommentLikes)
            ->insertGetId($commentThreeLikeTwo);

        $commentFourLike = [
            'comment_id' => $secondOrderedCommentId,
            'user_id' => $this->faker->randomNumber(),
            'created_on' => Carbon::instance($this->faker->dateTime)
                ->toDateTimeString(),
        ];

        $this->databaseManager->table(ConfigService::$tableCommentLikes)
            ->insertGetId($commentFourLike);

        $response = $this->call(
            'GET',
            'railcontent/comment',
            [
                'page' => 1,
                'limit' => 25,
                'content_id' => $content['id'],
                'sort' => '-like_count',
            ]
        );

        $decodedResponse = $response->decodeResponseJson();

        // assert the order of results
        $this->assertEquals($decodedResponse['data'][0]['id'], $firstOrderedCommentId);
        $this->assertEquals($decodedResponse['data'][1]['id'], $secondOrderedCommentId);
    }

    public function test_pull_comments_filtered_by_my_comments()
    {
        $currentUserId = $this->createAndLogInNewUser();
        $myComments = $this->fakeComment(
            1,
            [
                'userId' => $currentUserId,
                'content' => $this->entityManager->getRepository(Content::class)
                    ->find(1),
            ]
        );
        $replyToMyComment = $this->fakeComment(
            1,
            [
                'userId' => rand(2, 10),
                'content' => $this->entityManager->getRepository(Content::class)
                    ->find(1),
                'parent' => $this->entityManager->getRepository(Comment::class)
                    ->find(1),
            ]
        );

        $myCommentsOtherContent = $this->fakeComment(
            1,
            [
                'userId' => $currentUserId,
                'content' => $this->entityManager->getRepository(Content::class)
                    ->find(2),
            ]
        );

        $response = $this->call(
            'GET',
            'railcontent/comment',
            [
                'page' => 1,
                'limit' => 25,
                'content_id' => 1,
                'sort' => '-mine',
            ]
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(1, $response->decodeResponseJson('meta')['pagination']['total']);
    }

    public function test_pull_comments_filtered_by_content_type()
    {
        $page = 1;
        $limit = 3;
        $totalNumber = 10;
        $type = 'test';

        $this->populator->addEntity(
            Content::class,
            1,
            [
                'type' => $type,
                'slug' => ConfigService::$brand
            ]
        );

        $this->populator->execute();

        $content =
            $this->entityManager->getRepository(Content::class)
                ->find(3);
        $comment = $this->fakeComment(
            1,
            [
                'content' => $this->entityManager->getRepository(Content::class)
                    ->find(1),
            ]
        );

        $this->populator->addEntity(
            Comment::class,
            $totalNumber,
            [
                'content' => $content,
                'parent' => $comment[0],
            ]
        );

        $fakeData = $this->populator->execute();

        $response = $this->call(
            'GET',
            'railcontent/comment',
            [
                'page' => $page,
                'limit' => $limit,
                'content_type' => $type,
                'sort' => 'id',
            ]
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->decodeResponseJson('data');

        foreach ($data as $comment) {
            $this->assertEquals(
                $type,
                $this->entityManager->getRepository(Content::class)
                    ->find($comment['relationships']['content']['data']['id'])
                    ->getType()
            );
        }
        $this->assertEquals($limit, count($data));
    }

    public function test_pull_comments_filtered_by_brand()
    {
        $page = 1;
        $limit = 3;
        $otherBrand = $this->faker->word;

        $contentForBrand1 = $this->fakeContent(
            1,
            [
                'brand' => $otherBrand,
            ]
        );
        $ContentBrandConfig = $this->fakeContent(1);

        $this->fakeComment(
            7,
            [
                'content' => $contentForBrand1[0],
            ]
        );

        $this->fakeComment(
            2,
            [
                'content' => $ContentBrandConfig[0],
            ]
        );

        $response = $this->call(
            'GET',
            'railcontent/comment',
            [
                'page' => $page,
                'limit' => $limit,
            ]
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(2, count($response->decodeResponseJson('data')));
    }

    public function _test_get_linked_comment()
    {
        $commentsNr = 12;
        $content = $this->contentFactory->create(
            $this->faker->word,
            ConfigService::$commentableContentTypes[0]
        );
        $comment = $this->commentFactory->create($this->faker->text, $content['id'], null, rand());

        for ($i = 1; $i <= $commentsNr; $i++) {
            $comments[$i] = $this->commentFactory->create($this->faker->text, $content['id'], null, rand());
        }

        $response = $this->call('GET', 'railcontent/comment/' . $comment['id']);

        $this->assertEquals([$comments[2], $comments[1], $comment], $response->decodeResponseJson('data'));
        $this->assertEquals(($commentsNr + 1), $response->decodeResponseJson('meta')['totalResults']);
    }

    public function test_pull_comment_with_replies()
    {
        $comment = $this->fakeComment(
            2,
            [
                'content' => $this->entityManager->getRepository(Content::class)
                    ->find(1),
                'parent' => null,
            ]
        );

        $replies = $this->fakeComment(
            1,
            [
                'content' => $this->entityManager->getRepository(Content::class)
                    ->find(1),
                'parent' => $comment[0],
            ]
        );

        $response = $this->call(
            'GET',
            'railcontent/comment',
            [
                'page' => 1,
                'limit' => 10,
                'sort' => 'id',
            ]
        );

        $this->assertEquals(3, count($response->decodeResponseJson('data')));

        $this->assertEquals($comment[0]->getId(), $response->decodeResponseJson('data')[0]['id']);
        $this->assertEquals($comment[1]->getId(), $response->decodeResponseJson('data')[1]['id']);
        $this->assertEquals($replies[0]->getId(), $response->decodeResponseJson('data')[2]['id']);
    }
}
