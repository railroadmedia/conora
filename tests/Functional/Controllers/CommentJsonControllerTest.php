<?php

namespace Railroad\Railcontent\Tests\Functional\Controllers;

use Carbon\Carbon;
use Faker\ORM\Doctrine\Populator;
use Railroad\Railcontent\Entities\Comment;
use Railroad\Railcontent\Entities\CommentAssignment;
use Railroad\Railcontent\Entities\Content;
use Railroad\Railcontent\Factories\CommentFactory;
use Railroad\Railcontent\Factories\ContentFactory;
use Railroad\Railcontent\Repositories\CommentRepository;
use Railroad\Railcontent\Repositories\ContentRepository;
use Railroad\Railcontent\Services\CommentService;
use Railroad\Railcontent\Services\ConfigService;
use Railroad\Railcontent\Services\ContentService;
use Railroad\Railcontent\Tests\Hydrators\CommentFakeDataHydrator;
use Railroad\Railcontent\Tests\RailcontentTestCase;
use Railroad\Railcontent\Transformers\CommentTransformer;

class CommentJsonControllerTest extends RailcontentTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->fakeDataHydrator = new CommentFakeDataHydrator($this->entityManager);
    }

    public function test_add_comment_response()
    {
        $userId = $this->createAndLogInNewUser();
        $content = $this->fakeContent(
            1,
            [
                'type' => $this->faker->randomElement(ConfigService::$commentableContentTypes),
                'status' => $this->faker->randomElement(ContentRepository::$availableContentStatues),
            ]
        );

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
                            'data' => [
                                'type' => 'content',
                                'id' => $content[0]->getId(),
                            ],
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
        $content = $this->fakeContent(
            1,
            [
                'status' => $this->faker->randomElement(ContentRepository::$availableContentStatues),
            ]
        );

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
                            'data' => [
                                'type' => 'content',
                                'id' => $content[0]->getId(),
                            ],
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
                            'data' => [
                                'type' => 'content',
                                'id' => rand(),
                            ],
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
                            'data' => [
                                'type' => 'content',
                                'id' => rand(30, 100),
                            ],
                        ],
                        'parent' => [
                            'data' => [
                                'type' => 'comment',
                                'id' => rand(3, 10),
                            ],
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
        $content = $this->fakeContent();

        $comment = $this->fakeComment(
            1,
            [
                'userId' => rand(2, 10),
                'content' => $content[0],
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

        $comment = $this->fakeComment(
            2,
            [
                'parent' => null,
            ]
        );
        $replies = $this->fakeComment(
            1,
            [
                'parent' => $comment[0],
            ]
        );
        $replies = $this->fakeComment(
            5,
            [
                'parent' => $comment[1],
            ]
        );

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
        $content = $this->fakeContent();

        $comment = $this->fakeComment(
            1,
            [
                'userId' => rand(2, 10),
                'content' => $content[0],
            ]
        );

        $commentId = $comment[0]->getId();

        CommentService::$canManageOtherComments = true;
        CommentRepository::$softDelete = false;

        $response = $this->call('DELETE', 'railcontent/comment/' . $commentId);

        $this->assertEquals(204, $response->getStatusCode());

        $this->assertDatabaseMissing(
            ConfigService::$tableComments,
            [
                'id' => $commentId,
            ]
        );
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
                            'data' => [
                                'type' => 'comment',
                                'id' => $comment[0]->getId(),
                            ],
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

        $comments = $this->fakeComment(
            $totalNumber,
            [
                'parent' => null,
                'deletedAt' => null,
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
        $comments = $this->fakeComment(
            $totalNumber,
            [
                'parent' => null,
                'deletedAt' => null,
            ]
        );

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

        $comments = $this->fakeComment(
            $totalNumber,
            [
                'userId' => $userId,
                'parent' => null,
                'deletedAt' => null,
            ]
        );

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
            count($comments),
            $response->decodeResponseJson('meta')['pagination']['total']
        );

        $data = $response->decodeResponseJson()['data'];

        $this->assertEquals($limit, count($data));

        foreach ($data as $res) {
            $this->assertEquals($userId, $res['attributes']['user_id']);
        }
    }

    public function test_pull_comments_assigned_to_user()
    {
        $page = 1;
        $limit = 3;
        $totalNumber = $this->faker->numberBetween(3, 10);
        $userId = 1;
        $comments = $this->fakeComment(
            $totalNumber,
            [
                'userId' => rand(2, 10),
                'parent' => null,
                'deletedAt' => null,
            ]
        );

        $this->populator->addEntity(
            CommentAssignment::class,
            1,
            [
                'userId' => $userId,
                'comment' => $comments[0],
            ]
        );

        $fakeData = $this->populator->execute();

        $response = $this->call(
            'GET',
            'railcontent/comment',
            [
                'page' => $page,
                'limit' => $limit,
                'assigned_to_user_id' => $userId,
            ]
        );

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(
            1,
            $response->decodeResponseJson('meta')['pagination']['total']
        );
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
        $content1 = $this->fakeContent(
            1,
            [
                'brand' => $this->faker->randomElement(config('railcontent.available_brands')),
                'type' => $this->faker->randomElement(config('railcontent.commentable_content_types')),
            ]
        );
        $content2 = $this->fakeContent(
            1,
            [
                'brand' => $this->faker->randomElement(config('railcontent.available_brands')),
                'type' => $this->faker->randomElement(config('railcontent.commentable_content_types')),
            ]
        );
        $myComment = $this->fakeComment(
            1,
            [
                'userId' => $currentUserId,
                'content' => $content1[0],
                'deletedAt' => null,
            ]
        );

        $replyToMyComment = $this->fakeComment(
            1,
            [
                'userId' => rand(2, 10),
                'content' => $content1[0],
                'parent' => $this->entityManager->getRepository(Comment::class)
                    ->find(1),
                'deletedAt' => null,
            ]
        );

        $myCommentsOtherContent = $this->fakeComment(
            1,
            [
                'userId' => $currentUserId,
                'content' => $content2[0],
                'deletedAt' => null,
            ]
        );

        $response = $this->call(
            'GET',
            'railcontent/comment',
            [
                'page' => 1,
                'limit' => 25,
                'content_id' => $content1[0]->getId(),
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

        $type = $this->faker->randomElement(config('railcontent.commentable_content_types'));

        $content = $this->fakeContent(
            1,
            [
                'type' => $type,
                'brand' => ConfigService::$brand,
            ]
        );

        $comment = $this->fakeComment(
            10,
            [
                'content' => $content[0],
                'deletedAt' => null,
                'parent' => null
            ]
        );

        $this->populator->addEntity(
            Comment::class,
            $totalNumber,
            [
                'content' => $content[0],
                'parent' => $comment[0],
                'deletedAt' => null,
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
                'deletedAt' => null,
                'parent' => null
            ]
        );

        $this->fakeComment(
            2,
            [
                'content' => $ContentBrandConfig[0],
                'deletedAt' => null,
                'parent' => null
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

    public function test_get_linked_comment()
    {
        $commentsNr = 12;
        $content = $this->fakeContent(
            1,
            [
                'brand' => $this->faker->randomElement(config('railcontent.available_brands')),
                'type' => $this->faker->randomElement(config('railcontent.commentable_content_types')),
            ]
        );
        $comments = $this->fakeComment(
            $commentsNr / 2,
            [
                'parent' => null,
                'content' => $content[0],
                'createdOn' => Carbon::parse(now()),
                'deletedAt' => null,
            ]
        );

        $this->fakeComment(
            $commentsNr / 2,
            [
                'parent' => null,
                'content' => $content[0],
                'createdOn' => Carbon::parse(
                    (Carbon::now()
                        ->subDay(2))
                ),
                'deletedAt' => null,
            ]
        );

        $response = $this->call(
            'GET',
            'railcontent/comment/3',
            [
                'limit' => 2,
            ]
        );

        $this->assertEquals(2, count($response->decodeResponseJson('data')));
    }

    public function test_pull_comment_with_replies()
    {
        $comment = $this->fakeComment(
            2,
            [
                'parent' => null,
                'deletedAt' => null,
            ]
        );

        $replies = $this->fakeComment(
            1,
            [
                'parent' => $comment[0],
                'deletedAt' => null,
            ]
        );

        $response = $this->call(
            'GET',
            'railcontent/comment',
            [
                'page' => 1,
                'limit' => 3,
                'sort' => 'id',
            ]
        );

        $this->assertEquals(2, count($response->decodeResponseJson('data')));

        $this->assertEquals($comment[0]->getId(), $response->decodeResponseJson('data')[0]['id']);
        $this->assertEquals($comment[1]->getId(), $response->decodeResponseJson('data')[1]['id']);
        $this->assertEquals(3, $response->decodeResponseJson('meta')['totalCommentsAndReplies']);
    }

    public function test_cache_invalidation()
    {
        $userId = $this->createAndLogInNewUser();

        $content = $this->fakeContent(1,[
            'brand' => config('railcontent.brand'),
            'status' => 'published',
            'type' => $this->faker->randomElement(ConfigService::$commentableContentTypes),
        ]);
        $comment = $this->fakeComment(
            3,
            [
                'parent' => null,
                'userId' => $userId,
                'deletedAt' => null,
            ]
        );

        $replies = $this->fakeComment(
            1,
            [
                'parent' => $comment[0],
                'deletedAt' => null,
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

        $response = $this->call(
            'PUT',
            'railcontent/comment',
            [
                'data' => [
                    'attributes' => [
                        'comment' => 'roxana',
                    ],
                    'relationships' => [
                        'content' => [
                            'data' => [
                                'type' => 'content',
                                'id' => $content[0]->getId(),
                            ],
                        ],
                    ],
                ],
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

        $this->assertEquals(4, count($response->decodeResponseJson('data')));

        $this->assertEquals($comment[0]->getId(), $response->decodeResponseJson('data')[0]['id']);
        $this->assertEquals($comment[1]->getId(), $response->decodeResponseJson('data')[1]['id']);

        $response = $this->call('DELETE', 'railcontent/comment/' . $comment[0]->getId());

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

        CommentService::$canManageOtherComments = true;
        $updatedComment = $this->faker->text();
        $response = $this->call(
            'PATCH',
            'railcontent/comment/' . $comment[1]->getId(),
            [
                'data' => [
                    'type' => 'comment',
                    'attributes' => [
                        'comment' => $updatedComment,
                    ],
                ],
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

        $this->assertEquals($updatedComment, $response->decodeResponseJson('data')[0]['attributes']['comment']);
    }
}
