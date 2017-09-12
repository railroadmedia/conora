<?php

namespace Railroad\Railcontent\Tests\Functional\Repositories;

use Carbon\Carbon;
use Railroad\Railcontent\Repositories\ContentRepository;
use Railroad\Railcontent\Services\ConfigService;
use Railroad\Railcontent\Tests\RailcontentTestCase;

class ContentRepositoryTest extends RailcontentTestCase
{
    /**
     * @var ContentRepository
     */
    protected $classBeingTested;

    protected function setUp()
    {
        parent::setUp();

        $this->classBeingTested = $this->app->make(ContentRepository::class);
    }

    public function test_get_by_id()
    {
        $content = [
            'slug' => $this->faker->word,
            'status' => $this->faker->word,
            'type' => $this->faker->word,
            'position' => $this->faker->numberBetween(),
            'parent_id' => null,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];

        $contentId = $this->query()->table(ConfigService::$tableContent)->insertGetId($content);

        $response = $this->classBeingTested->getById($contentId);

        $this->assertEquals(
            array_merge(['id' => $contentId], $content),
            $response
        );
    }

    public function test_get_by_id_none_exist()
    {
        $response = $this->classBeingTested->getById(rand());

        $this->assertEquals(
            null,
            $response
        );
    }

    public function test_get_many_by_id_with_fields()
    {
        // content that is linked via a field
        $linkedContent = [
            'slug' => $this->faker->word,
            'status' => $this->faker->word,
            'type' => $this->faker->word,
            'position' => $this->faker->numberBetween(),
            'parent_id' => null,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];

        $linkedContentId = $this->query()->table(ConfigService::$tableContent)->insertGetId($linkedContent);

        $linkedFieldKey = $this->faker->word;
        $linkedFieldValue = $this->faker->word;

        $linkedFieldId = $this->query()->table(ConfigService::$tableFields)->insertGetId(
            [
                'key' => $linkedFieldKey,
                'value' => $linkedFieldValue,
                'type' => 'string',
                'position' => null,
            ]
        );

        $linkedContentFieldLinkId = $this->query()->table(ConfigService::$tableContentFields)->insertGetId(
            [
                'content_id' => $linkedContentId,
                'field_id' => $linkedFieldId,
            ]
        );

        // main content
        $content = [
            'slug' => $this->faker->word,
            'status' => $this->faker->word,
            'type' => $this->faker->word,
            'position' => $this->faker->numberBetween(),
            'parent_id' => null,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];

        $contentId = $this->query()->table(ConfigService::$tableContent)->insertGetId($content);

        $fieldKey = $this->faker->word;

        $fieldId = $this->query()->table(ConfigService::$tableFields)->insertGetId(
            [
                'key' => $fieldKey,
                'value' => $linkedContentId,
                'type' => 'content_id',
                'position' => null,
            ]
        );

        $contentFieldLinkId = $this->query()->table(ConfigService::$tableContentFields)->insertGetId(
            [
                'content_id' => $contentId,
                'field_id' => $fieldId,
            ]
        );

        // Add a multiple key field
        $multipleKeyFieldKey = $this->faker->word;
        $multipleKeyFieldValues = [$this->faker->word, $this->faker->word, $this->faker->word];

        $multipleField1 = $this->query()->table(ConfigService::$tableFields)->insertGetId(
            [
                'key' => $multipleKeyFieldKey,
                'value' => $multipleKeyFieldValues[0],
                'type' => 'multiple',
                'position' => 0,
            ]
        );

        $multipleFieldLink1 = $this->query()->table(ConfigService::$tableContentFields)->insertGetId(
            [
                'content_id' => $contentId,
                'field_id' => $multipleField1,
            ]
        );

        $multipleField2 = $this->query()->table(ConfigService::$tableFields)->insertGetId(
            [
                'key' => $multipleKeyFieldKey,
                'value' => $multipleKeyFieldValues[2],
                'type' => 'multiple',
                'position' => 2,
            ]
        );

        $multipleFieldLink2 = $this->query()->table(ConfigService::$tableContentFields)->insertGetId(
            [
                'content_id' => $contentId,
                'field_id' => $multipleField2,
            ]
        );

        $multipleField3 = $this->query()->table(ConfigService::$tableFields)->insertGetId(
            [
                'key' => $multipleKeyFieldKey,
                'value' => $multipleKeyFieldValues[1],
                'type' => 'multiple',
                'position' => 1,
            ]
        );

        $multipleFieldLink3 = $this->query()->table(ConfigService::$tableContentFields)->insertGetId(
            [
                'content_id' => $contentId,
                'field_id' => $multipleField3,
            ]
        );

        $response = $this->classBeingTested->getManyById([$contentId]);

        $this->assertEquals(
            [
                2 => [
                    "id" => $contentId,
                    "slug" => $content["slug"],
                    "status" => $content["status"],
                    "type" => $content["type"],
                    "position" => $content["position"],
                    "parent_id" => $content["parent_id"],
                    "published_on" => $content["published_on"],
                    "created_on" => $content["created_on"],
                    "archived_on" => $content["archived_on"],
                    "fields" => [
                        $fieldKey => [
                            "id" => $linkedContentId,
                            "slug" => $linkedContent["slug"],
                            "status" => $linkedContent["status"],
                            "type" => $linkedContent["type"],
                            "position" => $linkedContent["position"],
                            "parent_id" => $linkedContent["parent_id"],
                            "published_on" => $linkedContent["published_on"],
                            "created_on" => $linkedContent["created_on"],
                            "archived_on" => $linkedContent["archived_on"],
                            "fields" => [
                                $linkedFieldKey => $linkedFieldValue,
                            ]
                        ],
                        $multipleKeyFieldKey => $multipleKeyFieldValues
                    ],
                ]
            ],
            $response
        );
    }

    public function test_get_by_slug_non_exist()
    {
        $content = [
            'slug' => $this->faker->word,
            'status' => $this->faker->word,
            'type' => $this->faker->word,
            'position' => $this->faker->numberBetween(),
            'parent_id' => null,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];

        $contentId = $this->query()->table(ConfigService::$tableContent)->insertGetId($content);

        $response = $this->classBeingTested->getBySlug($this->faker->word.rand(), null);

        $this->assertEquals([], $response);
    }

    public function test_get_by_slug_any_parent_single()
    {
        $content = [
            'slug' => $this->faker->word,
            'status' => $this->faker->word,
            'type' => $this->faker->word,
            'position' => $this->faker->numberBetween(),
            'parent_id' => null,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];

        $contentId = $this->query()->table(ConfigService::$tableContent)->insertGetId($content);

        $response = $this->classBeingTested->getBySlug($content['slug'], null);

        $this->assertEquals([$contentId => array_merge(['id' => $contentId], $content)], $response);
    }

    public function test_get_by_slug_any_parent_multiple()
    {
        $expectedContent = [];

        $slug = $this->faker->word;

        for($i = 0; $i < 3; $i++) {
            $content = [
                'slug' => $slug,
                'status' => $this->faker->word,
                'type' => $this->faker->word,
                'position' => $this->faker->numberBetween(),
                'parent_id' => $i == 0 ? null : $i,
                'published_on' => null,
                'created_on' => Carbon::now()->toDateTimeString(),
                'archived_on' => null,
            ];

            $contentId = $this->query()->table(ConfigService::$tableContent)->insertGetId($content);

            $expectedContent[$contentId] = array_merge(['id' => $contentId], $content);
        }

        $response = $this->classBeingTested->getBySlug($slug, null);

        $this->assertEquals($expectedContent, $response);
    }

    public function test_get_by_slug_specified_parent_multiple()
    {
        $expectedContent = [];

        $slug = $this->faker->word;
        $parentId = $this->faker->randomNumber();

        for($i = 0; $i < 3; $i++) {
            $content = [
                'slug' => $slug,
                'status' => $this->faker->word,
                'type' => $this->faker->word,
                'position' => $this->faker->numberBetween(),
                'parent_id' => $parentId,
                'published_on' => null,
                'created_on' => Carbon::now()->toDateTimeString(),
                'archived_on' => null,
            ];

            $contentId = $this->query()->table(ConfigService::$tableContent)->insertGetId($content);

            $expectedContent[$contentId] = array_merge(['id' => $contentId], $content);
        }

        // add other content with the same slug but different parent id to make sure it gets excluded
        $this->query()->table(ConfigService::$tableContent)->insertGetId(
            [
                'slug' => $slug,
                'status' => $this->faker->word,
                'type' => $this->faker->word.rand(),
                'position' => $this->faker->numberBetween(),
                'parent_id' => $parentId + 1,
                'published_on' => null,
                'created_on' => Carbon::now()->toDateTimeString(),
                'archived_on' => null,
            ]
        );

        // add some other random content that should be excluded
        $this->query()->table(ConfigService::$tableContent)->insertGetId(
            [
                'slug' => $this->faker->word.rand(),
                'status' => $this->faker->word,
                'type' => $this->faker->word.rand(),
                'position' => $this->faker->numberBetween(),
                'parent_id' => $parentId + 1,
                'published_on' => null,
                'created_on' => Carbon::now()->toDateTimeString(),
                'archived_on' => null,
            ]
        );

        $response = $this->classBeingTested->getBySlug($slug, $parentId);

        $this->assertEquals($expectedContent, $response);
    }

    public function test_get_paginated_page_amount()
    {
        $page = 1;
        $amount = 3;
        $orderByDirection = 'desc';
        $orderByColumn = 'published_on';
        $statues = [$this->faker->word, $this->faker->word, $this->faker->word];
        $types = [$this->faker->word, $this->faker->word, $this->faker->word];
        $parentId = null;
        $includeFuturePublishedOn = false;
        $requiredFields = [];

        $expectedContent = [];

        // insert matching content
        for($i = 0; $i < 3; $i++) {
            $content = [
                'slug' => $this->faker->word,
                'status' => $this->faker->randomElement($statues),
                'type' => $this->faker->randomElement($types),
                'position' => $this->faker->numberBetween(),
                'parent_id' => $parentId,
                'published_on' => Carbon::now()->subDays(rand(1, 99))->toDateTimeString(),
                'created_on' => Carbon::now()->toDateTimeString(),
                'archived_on' => null,
            ];

            $contentId = $this->query()->table(ConfigService::$tableContent)->insertGetId($content);

            $expectedContent[$contentId] = array_merge(['id' => $contentId], $content);
        }

        // insert non-matching content
        for($i = 0; $i < 3; $i++) {
            $content = [
                'slug' => $this->faker->word,
                'status' => $this->faker->randomElement($statues),
                'type' => $this->faker->randomElement($types),
                'position' => $this->faker->numberBetween(),
                'parent_id' => $parentId,
                'published_on' => Carbon::now()->subDays(rand(100, 1000))->toDateTimeString(),
                'created_on' => Carbon::now()->toDateTimeString(),
                'archived_on' => null,
            ];

            $this->query()->table(ConfigService::$tableContent)->insertGetId($content);
        }

        $response = $this->classBeingTested->getPaginated(
            $page,
            $amount,
            $orderByDirection,
            $orderByColumn,
            $statues,
            $types,
            $requiredFields,
            $parentId,
            $includeFuturePublishedOn
        );

        $this->assertEquals($expectedContent, $response);
    }

    public function test_get_paginated_page_2_amount()
    {
        $page = 2;
        $amount = 3;
        $orderByDirection = 'desc';
        $orderByColumn = 'published_on';
        $statues = [$this->faker->word, $this->faker->word, $this->faker->word];
        $types = [$this->faker->word, $this->faker->word, $this->faker->word];
        $parentId = null;
        $includeFuturePublishedOn = false;
        $requiredFields = [];

        $expectedContent = [];

        // insert matching content
        for($i = 0; $i < 3; $i++) {
            $content = [
                'slug' => $this->faker->word,
                'status' => $this->faker->randomElement($statues),
                'type' => $this->faker->randomElement($types),
                'position' => $this->faker->numberBetween(),
                'parent_id' => $parentId,
                'published_on' => Carbon::now()->subDays(rand(100, 1000))->toDateTimeString(),
                'created_on' => Carbon::now()->toDateTimeString(),
                'archived_on' => null,
            ];

            $contentId = $this->query()->table(ConfigService::$tableContent)->insertGetId($content);

            $expectedContent[$contentId] = array_merge(['id' => $contentId], $content);
        }

        // insert non-matching content
        for($i = 0; $i < 3; $i++) {
            $content = [
                'slug' => $this->faker->word,
                'status' => $this->faker->randomElement($statues),
                'type' => $this->faker->randomElement($types),
                'position' => $this->faker->numberBetween(),
                'parent_id' => $parentId,
                'published_on' => Carbon::now()->subDays(rand(1, 99))->toDateTimeString(),
                'created_on' => Carbon::now()->toDateTimeString(),
                'archived_on' => null,
            ];

            $contentId = $this->query()->table(ConfigService::$tableContent)->insertGetId($content);
        }

        $response = $this->classBeingTested->getPaginated(
            $page,
            $amount,
            $orderByDirection,
            $orderByColumn,
            $statues,
            $types,
            $requiredFields,
            $parentId,
            $includeFuturePublishedOn
        );

        $this->assertEquals($expectedContent, $response);
    }

    public function test_get_paginated_order_by_desc()
    {
        $page = 1;
        $amount = 3;
        $orderByDirection = 'desc';
        $orderByColumn = 'published_on';
        $statues = [$this->faker->word, $this->faker->word, $this->faker->word];
        $types = [$this->faker->word, $this->faker->word, $this->faker->word];
        $parentId = null;
        $includeFuturePublishedOn = false;
        $requiredFields = [];

        $expectedContent = [];

        // insert matching content
        for($i = 0; $i < 3; $i++) {
            $content = [
                'slug' => $this->faker->word,
                'status' => $this->faker->randomElement($statues),
                'type' => $this->faker->randomElement($types),
                'position' => $this->faker->numberBetween(),
                'parent_id' => $parentId,
                'published_on' => Carbon::now()->subDays(($i + 1) * 10)->toDateTimeString(),
                'created_on' => Carbon::now()->toDateTimeString(),
                'archived_on' => null,
            ];

            $contentId = $this->query()->table(ConfigService::$tableContent)->insertGetId($content);

            $expectedContent[$contentId] = array_merge(['id' => $contentId], $content);
        }

        $response = $this->classBeingTested->getPaginated(
            $page,
            $amount,
            $orderByDirection,
            $orderByColumn,
            $statues,
            $types,
            $requiredFields,
            $parentId,
            $includeFuturePublishedOn
        );

        $this->assertEquals($expectedContent, $response);

        // for some reason phpunit doesn't test the order of the array values
        $this->assertEquals(array_keys($expectedContent), array_keys($response));
    }

    public function test_get_paginated_order_by_asc()
    {
        $page = 1;
        $amount = 3;
        $orderByDirection = 'asc';
        $orderByColumn = 'published_on';
        $statues = [$this->faker->word, $this->faker->word, $this->faker->word];
        $types = [$this->faker->word, $this->faker->word, $this->faker->word];
        $parentId = null;
        $includeFuturePublishedOn = false;
        $requiredFields = [];

        $expectedContent = [];

        // insert matching content
        for($i = 0; $i < 3; $i++) {
            $content = [
                'slug' => $this->faker->word,
                'status' => $this->faker->randomElement($statues),
                'type' => $this->faker->randomElement($types),
                'position' => $this->faker->numberBetween(),
                'parent_id' => $parentId,
                'published_on' => Carbon::now()->subDays(1000 - (($i + 1) * 10))->toDateTimeString(),
                'created_on' => Carbon::now()->toDateTimeString(),
                'archived_on' => null,
            ];

            $contentId = $this->query()->table(ConfigService::$tableContent)->insertGetId($content);

            $expectedContent[$contentId] = array_merge(['id' => $contentId], $content);
        }

        $response = $this->classBeingTested->getPaginated(
            $page,
            $amount,
            $orderByDirection,
            $orderByColumn,
            $statues,
            $types,
            $requiredFields,
            $parentId,
            $includeFuturePublishedOn
        );

        $this->assertEquals($expectedContent, $response);

        // for some reason phpunit doesn't test the order of the array values
        $this->assertEquals(array_keys($expectedContent), array_keys($response));
    }

    public function test_create_content()
    {
        $slug = $this->faker->word;
        $status = $this->faker->word;
        $type = $this->faker->word;

        $categoryId = $this->classBeingTested->create($slug, $status, $type, 1, null, Carbon::now()->subDays(1000 - 10)->toDateTimeString());

        $this->assertDatabaseHas(
            ConfigService::$tableContent,
            [
                'id' => $categoryId,
                'slug' => $slug,
                'status' => $status,
                'type' => $type,
                'position' => 1,
                'parent_id' => null,
                'published_on' => Carbon::now()->subDays(1000 - 10)->toDateTimeString(),
                'created_on' => Carbon::now()->toDateTimeString(),
                'archived_on' => null
            ]
        );
    }

    public function test_push_position_stack()
    {
        $slug = implode('-', $this->faker->words());
        $slug2 = implode('-', $this->faker->words());
        $slug3 = implode('-', $this->faker->words());
        $status = $this->faker->word;
        $status2 = $this->faker->word;
        $status3 = $this->faker->word;
        $type = $this->faker->word;
        $type2 = $this->faker->word;
        $type3 = $this->faker->word;

        $contentId = $this->classBeingTested->create($slug, $status, $type, 1, null, Carbon::now()->subDays(990)->toDateTimeString());
        $content2Id = $this->classBeingTested->create($slug2, $status2, $type2, 1, null, Carbon::now()->subDays(10)->toDateTimeString());
        $content3Id = $this->classBeingTested->create($slug3, $status3, $type3, 1, null, null);

        $this->assertDatabaseHas(
            ConfigService::$tableContent,
            [
                'id' => $contentId,
                'position' => 3,
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableContent,
            [
                'id' => $content2Id,
                'position' => 2,
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableContent,
            [
                'id' => $content3Id,
                'position' => 1,
            ]
        );
    }

    public function test_push_position_stack_abnormal()
    {
        $slug = implode('-', $this->faker->words());
        $slug2 = implode('-', $this->faker->words());
        $status = $this->faker->word;
        $status2 = $this->faker->word;
        $type = $this->faker->word;
        $type2 = $this->faker->word;

        $contentId = $this->classBeingTested->create($slug, $status, $type, 1, null, null);
        $content2Id = $this->classBeingTested->create($slug2, $status2, $type2, -581, null, null);

        $this->assertDatabaseHas(
            ConfigService::$tableContent,
            [
                'id' => $contentId,
                'slug' => $slug,
                'status' => $status,
                'type' => $type,
                'position' => 2,
                'parent_id' => null,
                'published_on' => null,
                'created_on' => Carbon::now()->toDateTimeString(),
                'archived_on' => null
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableContent,
            [
                'id' => $content2Id,
                'slug' => $slug2,
                'status' => $status2,
                'type' => $type2,
                'position' => 1,
                'parent_id' => null,
                'published_on' => null,
                'created_on' => Carbon::now()->toDateTimeString(),
                'archived_on' => null
            ]
        );
    }

    public function test_create_full_content_tree()
    {
        /*
         * --- $slug1 null - 1
         * ------ $slug2 1 - 1
         * --------- $slug3 2 - 1
         * --------- $slug4 2 - 2
         * --------- $slug5 2 - 3
         * --------- $slug6 2 - 4
         * ------------ $slug7 6 - 1
         * ------ $slug8 1 - 2
         * --- $slug9 null - 2
         */

        $slug1 = implode('-', $this->faker->words());
        $slug2 = implode('-', $this->faker->words());
        $slug3 = implode('-', $this->faker->words());
        $slug4 = implode('-', $this->faker->words());
        $slug5 = implode('-', $this->faker->words());
        $slug6 = implode('-', $this->faker->words());
        $slug7 = implode('-', $this->faker->words());
        $slug8 = implode('-', $this->faker->words());
        $slug9 = implode('-', $this->faker->words());
        $status = $this->faker->word;
        $type = $this->faker->text(64);

        $contentId1 = $this->classBeingTested->create($slug1, $status, $type, 1, null, null);
        $contentId2 = $this->classBeingTested->create($slug2, $status, $type, 1, $contentId1, null);
        $contentId3 = $this->classBeingTested->create($slug3, $status, $type, 1, $contentId2, null);
        $contentId4 = $this->classBeingTested->create($slug4, $status, $type, 2, $contentId2, null);
        $contentId5 = $this->classBeingTested->create($slug5, $status, $type, 3, $contentId2, null);
        $contentId6 = $this->classBeingTested->create($slug6, $status, $type, 4, $contentId2, null);
        $contentId7 = $this->classBeingTested->create($slug7, $status, $type, 1, $contentId6, null);
        $contentId8 = $this->classBeingTested->create($slug8, $status, $type, 2, $contentId1, null);
        $contentId9 = $this->classBeingTested->create($slug9, $status, $type, 2, null, null);

        $this->assertDatabaseHas(
            ConfigService::$tableContent,
            [
                'id' => $contentId1,
                'slug' => $slug1,
                'status' => $status,
                'type' => $type,
                'position' => 1,
                'parent_id' => null,
                'published_on' => null,
                'created_on' => Carbon::now()->toDateTimeString(),
                'archived_on' => null
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableContent,
            [
                'id' => $contentId2,
                'slug' => $slug2,
                'status' => $status,
                'type' => $type,
                'position' => 1,
                'parent_id' => $contentId1,
                'published_on' => null,
                'created_on' => Carbon::now()->toDateTimeString(),
                'archived_on' => null
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableContent,
            [
                'id' => $contentId3,
                'slug' => $slug3,
                'status' => $status,
                'type' => $type,
                'position' => 1,
                'parent_id' => $contentId2,
                'published_on' => null,
                'created_on' => Carbon::now()->toDateTimeString(),
                'archived_on' => null
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableContent,
            [
                'id' => $contentId4,
                'slug' => $slug4,
                'status' => $status,
                'type' => $type,
                'position' => 2,
                'parent_id' => $contentId2,
                'published_on' => null,
                'created_on' => Carbon::now()->toDateTimeString(),
                'archived_on' => null
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableContent,
            [
                'id' => $contentId5,
                'slug' => $slug5,
                'status' => $status,
                'type' => $type,
                'position' => 3,
                'parent_id' => $contentId2,
                'published_on' => null,
                'created_on' => Carbon::now()->toDateTimeString(),
                'archived_on' => null
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableContent,
            [
                'id' => $contentId6,
                'slug' => $slug6,
                'status' => $status,
                'type' => $type,
                'position' => 4,
                'parent_id' => $contentId2,
                'published_on' => null,
                'created_on' => Carbon::now()->toDateTimeString(),
                'archived_on' => null
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableContent,
            [
                'id' => $contentId7,
                'slug' => $slug7,
                'status' => $status,
                'type' => $type,
                'position' => 1,
                'parent_id' => $contentId6,
                'published_on' => null,
                'created_on' => Carbon::now()->toDateTimeString(),
                'archived_on' => null
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableContent,
            [
                'id' => $contentId8,
                'slug' => $slug8,
                'status' => $status,
                'type' => $type,
                'position' => 2,
                'parent_id' => $contentId1,
                'published_on' => null,
                'created_on' => Carbon::now()->toDateTimeString(),
                'archived_on' => null
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableContent,
            [
                'id' => $contentId9,
                'slug' => $slug9,
                'status' => $status,
                'type' => $type,
                'position' => 2,
                'parent_id' => null,
                'published_on' => null,
                'created_on' => Carbon::now()->toDateTimeString(),
                'archived_on' => null
            ]
        );
    }

    public function test_update_content_slug()
    {
        $content = [
            'slug' => $this->faker->word,
            'status' => $this->faker->word,
            'type' => $this->faker->word,
            'position' => $this->faker->numberBetween(),
            'parent_id' => null,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];

        $contentId = $this->query()->table(ConfigService::$tableContent)->insertGetId($content);

        $new_slug = $this->faker->word;

        $this->classBeingTested->update($contentId, $new_slug, $content['status'], $content['type'], $content['position'], $content['parent_id'], null, null);

        $this->assertDatabaseHas(
            ConfigService::$tableContent,
            [
                'id' => $contentId,
                'slug' => $new_slug,
                'status' => $content['status'],
                'type' => $content['type'],
                'position' => 1,
                'parent_id' => null,
                'published_on' => null,
                'created_on' => Carbon::now()->toDateTimeString(),
                'archived_on' => null,
            ]
        );
    }

    public function test_update_content_position()
    {
        $content1 = [
            'slug' => $this->faker->word,
            'status' => $this->faker->word,
            'type' => $this->faker->word,
            'position' => $this->faker->numberBetween(),
            'parent_id' => null,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];
        $contentId1 = $this->query()->table(ConfigService::$tableContent)->insertGetId($content1);

        $content2 = [
            'slug' => $this->faker->word,
            'status' => $this->faker->word,
            'type' => $this->faker->word,
            'position' => 1,
            'parent_id' => $contentId1,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];
        $contentId11 = $this->query()->table(ConfigService::$tableContent)->insertGetId($content2);

        $content3 = [
            'slug' => $this->faker->word,
            'status' => $this->faker->word,
            'type' => $this->faker->word,
            'position' => 2,
            'parent_id' => $contentId1,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];
        $contentId12 = $this->query()->table(ConfigService::$tableContent)->insertGetId($content3);

        $contentId12 = $this->classBeingTested->update($contentId12, $content3['slug'], $content3['status'], $content3['type'], 1, $content3['parent_id'], null, null);

        $this->assertDatabaseHas(
            ConfigService::$tableContent,
            [
                'id' => $contentId12,
                'position' => 1
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableContent,
            [
                'id' => $contentId11,
                'position' => 2
            ]
        );
    }

    public function test_update_content_and_reposition()
    {
        $content1 = [
            'slug' => $this->faker->word,
            'status' => $this->faker->word,
            'type' => $this->faker->word,
            'position' => $this->faker->numberBetween(),
            'parent_id' => null,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];
        $contentId1 = $this->query()->table(ConfigService::$tableContent)->insertGetId($content1);

        $content2 = [
            'slug' => $this->faker->word,
            'status' => $this->faker->word,
            'type' => $this->faker->word,
            'position' => 2,
            'parent_id' => null,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];
        $contentId2 = $this->query()->table(ConfigService::$tableContent)->insertGetId($content2);

        $content3 = [
            'slug' => $this->faker->word,
            'status' => $this->faker->word,
            'type' => $this->faker->word,
            'position' => 3,
            'parent_id' => null,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];
        $contentId3 = $this->query()->table(ConfigService::$tableContent)->insertGetId($content3);

        $contentId1 = $this->classBeingTested->update($contentId1, $content1['slug'], $content1['status'], $content1['type'], 2, $content1['parent_id'], null, null);

        $this->assertDatabaseHas(
            ConfigService::$tableContent,
            [
                'id' => $contentId2,
                'position' => 1
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableContent,
            [
                'id' => $contentId1,
                'position' => 2
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableContent,
            [
                'id' => $contentId3,
                'position' => 3
            ]
        );
    }

    public function test_update_content_and_children_reposition()
    {
        $content1 = [
            'slug' => $this->faker->word,
            'status' => $this->faker->word,
            'type' => $this->faker->word,
            'position' => $this->faker->numberBetween(),
            'parent_id' => null,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];
        $contentId1 = $this->query()->table(ConfigService::$tableContent)->insertGetId($content1);

        $content2 = [
            'slug' => $this->faker->word,
            'status' => $this->faker->word,
            'type' => $this->faker->word,
            'position' => 2,
            'parent_id' => null,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];
        $contentId2 = $this->query()->table(ConfigService::$tableContent)->insertGetId($content2);

        $content3 = [
            'slug' => $this->faker->word,
            'status' => $this->faker->word,
            'type' => $this->faker->word,
            'position' => 3,
            'parent_id' => null,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];
        $contentId3 = $this->query()->table(ConfigService::$tableContent)->insertGetId($content3);

        $contentId1 = $this->classBeingTested->update($contentId1, $content1['slug'], $content1['status'], $content1['type'], 3, $content1['parent_id'], null, null);

        $this->assertDatabaseHas(
            ConfigService::$tableContent,
            [
                'id' => $contentId2,
                'position' => 1
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableContent,
            [
                'id' => $contentId3,
                'position' => 2
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableContent,
            [
                'id' => $contentId1,
                'position' => 3
            ]
        );
    }

    public function test_delete_content()
    {
        $content = [
            'slug' => $this->faker->word,
            'status' => $this->faker->word,
            'type' => $this->faker->word,
            'position' => $this->faker->numberBetween(),
            'parent_id' => null,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];
        $contentId = $this->query()->table(ConfigService::$tableContent)->insertGetId($content);

        $this->classBeingTested->delete($contentId, 1);

        $this->assertDatabaseMissing(
            ConfigService::$tableContent,
            [
                'id' => $contentId,
                'slug' => $content['slug']
            ]
        );
    }

    public function test_delete_content_move_children()
    {
        $content1 = [
            'slug' => $this->faker->word,
            'status' => $this->faker->word,
            'type' => $this->faker->word,
            'position' => 1,
            'parent_id' => null,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];
        $contentId1 = $this->query()->table(ConfigService::$tableContent)->insertGetId($content1);

        $content11 = [
            'slug' => $this->faker->word,
            'status' => $this->faker->word,
            'type' => $this->faker->word,
            'position' => 1,
            'parent_id' => $contentId1,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];
        $contentId11 = $this->query()->table(ConfigService::$tableContent)->insertGetId($content11);

        $content12 = [
            'slug' => $this->faker->word,
            'status' => $this->faker->word,
            'type' => $this->faker->word,
            'position' => 2,
            'parent_id' => $contentId1,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];
        $contentId12 = $this->query()->table(ConfigService::$tableContent)->insertGetId($content12);

        $content2 = [
            'slug' => $this->faker->word,
            'status' => $this->faker->word,
            'type' => $this->faker->word,
            'position' => 2,
            'parent_id' => null,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];
        $contentId2 = $this->query()->table(ConfigService::$tableContent)->insertGetId($content2);

        $this->classBeingTested->delete($contentId1, 1);

        $this->assertDatabaseMissing(
            ConfigService::$tableContent,
            [
                'id' => $contentId1,
                'slug' => $content1['slug']
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableContent,
            [
                'id' => $contentId11,
                'slug' => $content11['slug'],
                'parent_id' => null,
                'position' => 1
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableContent,
            [
                'id' => $contentId12,
                'slug' => $content12['slug'],
                'parent_id' => null,
                'position' => 2
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableContent,
            [
                'id' => $contentId2,
                'slug' => $content2['slug'],
                'parent_id' => null,
                'position' => 3
            ]
        );
    }

    public function test_link_datum()
    {
        $contentId = $this->faker->numberBetween();
        $datumId = $this->faker->numberBetween();

        $datumLinkId = $this->classBeingTested->linkDatum($contentId, $datumId);

        $this->assertEquals(1, $datumLinkId);

        $this->assertDatabaseHas(
            ConfigService::$tableContentData,
            [
                'id' => $datumLinkId,
                'content_id' => $contentId,
                'datum_id' => $datumId
            ]
        );

    }

    public function test_get_content_datum_non_exist()
    {
        $contentDatum = $this->classBeingTested->getLinkedDatum(1, 1);
        $this->assertEquals(null, $contentDatum);
    }

    public function test_get_content_datum()
    {
        $content = [
            'slug' => $this->faker->word,
            'status' => $this->faker->word,
            'type' => $this->faker->word,
            'position' => $this->faker->numberBetween(),
            'parent_id' => null,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];
        $contentId = $this->query()->table(ConfigService::$tableContent)->insertGetId($content);

        $datum = [
            'key' => $this->faker->word,
            'value' => $this->faker->text(),
            'position' => $this->faker->numberBetween()
        ];
        $datumId = $this->query()->table(ConfigService::$tableData)->insertGetId($datum);

        $datumLinkId = $this->query()->table(ConfigService::$tableContentData)->insertGetId([
            'content_id' => $contentId,
            'datum_id' => $datumId
        ]);

        $contentDatum = $this->classBeingTested->getLinkedDatum($datumId, $contentId);

        $expectedResults = [
            'id' => $datumLinkId,
            'content_id' => $contentId,
            'datum_id' => $datumId,
            'key' => $datum['key'],
            'value' => $datum['value'],
            'position' => $datum['position']
        ];

        $this->assertEquals($expectedResults, $contentDatum);
    }

    public function test_unlink_content_specific_datum()
    {
        $contentDatum = [
            'content_id' => $this->faker->numberBetween(),
            'datum_id' => $this->faker->numberBetween()
        ];
        $contentDatumId = $this->query()->table(ConfigService::$tableContentData)->insertGetId($contentDatum);

        $this->classBeingTested->unlinkDatum($contentDatum['content_id'], $contentDatum['datum_id']);

        $this->assertDatabaseMissing(
            ConfigService::$tableContentData,
            [
                'id' => $contentDatumId,
                'content_id' => $contentDatum['content_id'],
                'datum_id' => $contentDatum['datum_id']
            ]
        );
    }

    public function test_unlink_content_all_datum()
    {
        $contentDatum = [
            'content_id' => $this->faker->numberBetween(),
            'datum_id' => $this->faker->numberBetween()
        ];
        $contentDatumId = $this->query()->table(ConfigService::$tableContentData)->insertGetId($contentDatum);

        $contentDatum2 = [
            'content_id' => $contentDatum['content_id'],
            'datum_id' => $this->faker->numberBetween()
        ];
        $contentDatumId2 = $this->query()->table(ConfigService::$tableContentData)->insertGetId($contentDatum2);

        $this->classBeingTested->unlinkDatum($contentDatum['content_id']);

        $this->assertDatabaseMissing(
            ConfigService::$tableContentData,
            [
                'content_id' => $contentDatum['content_id']
            ]
        );
    }

    public function test_link_field()
    {
        $contentId = $this->faker->numberBetween();
        $fieldId = $this->faker->numberBetween();

        $fieldLinkId = $this->classBeingTested->linkField($contentId, $fieldId);

        $this->assertEquals(1, $fieldLinkId);

        $this->assertDatabaseHas(
            ConfigService::$tableContentFields,
            [
                'id' => $fieldLinkId,
                'content_id' => $contentId,
                'field_id' => $fieldId
            ]
        );

    }

    public function test_get_content_field_non_exist()
    {
        $contentField = $this->classBeingTested->getLinkedField(1, 1);
        $this->assertEquals(null, $contentField);
    }

    public function test_get_content_field()
    {
        $content = [
            'slug' => $this->faker->word,
            'status' => $this->faker->word,
            'type' => $this->faker->word,
            'position' => $this->faker->numberBetween(),
            'parent_id' => null,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];
        $contentId = $this->query()->table(ConfigService::$tableContent)->insertGetId($content);

        $field = [
            'key' => $this->faker->word,
            'value' => $this->faker->text(),
            'type' => $this->faker->word,
            'position' => $this->faker->numberBetween()
        ];
        $fieldId = $this->query()->table(ConfigService::$tableFields)->insertGetId($field);

        $fieldLinkId = $this->query()->table(ConfigService::$tableContentFields)->insertGetId([
            'content_id' => $contentId,
            'field_id' => $fieldId
        ]);

        $contentField = $this->classBeingTested->getLinkedField($fieldId, $contentId);

        $expectedResults = [
            'id' => $fieldLinkId,
            'content_id' => $contentId,
            'field_id' => $fieldId,
            'key' => $field['key'],
            'value' => $field['value'],
            'type' => $field['type'],
            'position' => $field['position']
        ];

        $this->assertEquals($expectedResults, $contentField);
    }

    public function test_unlink_content_specific_field()
    {
        $contentField = [
            'content_id' => $this->faker->numberBetween(),
            'field_id' => $this->faker->numberBetween()
        ];
        $contentFieldId = $this->query()->table(ConfigService::$tableContentFields)->insertGetId($contentField);

        $contentField2 = [
            'content_id' => $contentField['content_id'],
            'field_id' => $this->faker->numberBetween()
        ];
        $contentFieldId2 = $this->query()->table(ConfigService::$tableContentFields)->insertGetId($contentField2);

        $this->classBeingTested->unlinkField($contentField['content_id'], $contentField['field_id']);

        $this->assertDatabaseMissing(
            ConfigService::$tableContentFields,
            [
                'id' => $contentFieldId,
                'content_id' => $contentField['content_id'],
                'field_id' => $contentField['field_id']
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableContentFields,
            [
                'id' => $contentFieldId2,
                'content_id' => $contentField['content_id'],
                'field_id' => $contentField2['field_id']
            ]
        );
    }

    public function test_unlink_content_all_fields()
    {
        $contentField1 = [
            'content_id' => $this->faker->numberBetween(),
            'field_id' => $this->faker->numberBetween()
        ];
        $contentFieldId1 = $this->query()->table(ConfigService::$tableContentFields)->insertGetId($contentField1);

        $contentField2 = [
            'content_id' => $contentField1['content_id'],
            'field_id' => $this->faker->numberBetween()
        ];
        $contentFieldId2 = $this->query()->table(ConfigService::$tableContentFields)->insertGetId($contentField2);

        $this->classBeingTested->unlinkField($contentField1['content_id']);

        $this->assertDatabaseMissing(
            ConfigService::$tableContentFields,
            [
                'content_id' => $contentField1['content_id']
            ]
        );
    }

    public function test_get_many_by_id_with_datum()
    {
        $content = [
            'slug' => $this->faker->word,
            'status' => $this->faker->word,
            'type' => $this->faker->word,
            'position' => $this->faker->numberBetween(),
            'parent_id' => null,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];

        $contentId = $this->query()->table(ConfigService::$tableContent)->insertGetId($content);

        $linkedDatumdKey = $this->faker->word;
        $linkedDatumValue = $this->faker->word;

        $linkedDatumId = $this->query()->table(ConfigService::$tableData)->insertGetId(
            [
                'key' => $linkedDatumdKey,
                'value' => $linkedDatumValue,
                'position' => 1,
            ]
        );

        $linkedContentDatumLinkId = $this->query()->table(ConfigService::$tableContentData)->insertGetId(
            [
                'content_id' => $contentId,
                'datum_id' => $linkedDatumId,
            ]
        );

        $response = $this->classBeingTested->getById($contentId);

        $this->assertEquals(
            [
                "id" => $contentId,
                "slug" => $content["slug"],
                "status" => $content["status"],
                "type" => $content["type"],
                "position" => $content["position"],
                "parent_id" => $content["parent_id"],
                "published_on" => $content["published_on"],
                "created_on" => $content["created_on"],
                "archived_on" => $content["archived_on"],
                "datum" => [
                    $linkedDatumdKey => $linkedDatumValue
                ],
            ],
            $response
        );
    }

    public function test_get_many_by_id_with_multiple_datum()
    {
        $content = [
            'slug' => $this->faker->word,
            'status' => $this->faker->word,
            'type' => $this->faker->word,
            'position' => $this->faker->numberBetween(),
            'parent_id' => null,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];

        $contentId = $this->query()->table(ConfigService::$tableContent)->insertGetId($content);

        $linkedDatumdKey = $this->faker->word;
        $linkedDatumValue = $this->faker->word;

        $linkedDatumId = $this->query()->table(ConfigService::$tableData)->insertGetId(
            [
                'key' => $linkedDatumdKey,
                'value' => $linkedDatumValue,
                'position' => 1,
            ]
        );

        $linkedContentDatumLinkId = $this->query()->table(ConfigService::$tableContentData)->insertGetId(
            [
                'content_id' => $contentId,
                'datum_id' => $linkedDatumId,
            ]
        );

        $linkedDatumdKey2 = $this->faker->word;
        $linkedDatumValue2 = $this->faker->word;

        $linkedDatumId2 = $this->query()->table(ConfigService::$tableData)->insertGetId(
            [
                'key' => $linkedDatumdKey2,
                'value' => $linkedDatumValue2,
                'position' => 1,
            ]
        );

        $linkedContentDatumLinkId2 = $this->query()->table(ConfigService::$tableContentData)->insertGetId(
            [
                'content_id' => $contentId,
                'datum_id' => $linkedDatumId2,
            ]
        );

        $response = $this->classBeingTested->getById($contentId);

        $this->assertEquals(
            [
                "id" => $contentId,
                "slug" => $content["slug"],
                "status" => $content["status"],
                "type" => $content["type"],
                "position" => $content["position"],
                "parent_id" => $content["parent_id"],
                "published_on" => $content["published_on"],
                "created_on" => $content["created_on"],
                "archived_on" => $content["archived_on"],
                "datum" => [
                    $linkedDatumdKey => $linkedDatumValue,
                    $linkedDatumdKey2 => $linkedDatumValue2
                ],
            ],
            $response
        );
    }

    public function test_get_many_by_id_with_field_and_datum()
    {
        $content = [
            'slug' => $this->faker->word,
            'status' => $this->faker->word,
            'type' => $this->faker->word,
            'position' => $this->faker->numberBetween(),
            'parent_id' => null,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];

        $contentId = $this->query()->table(ConfigService::$tableContent)->insertGetId($content);

        $linkedFieldKey = $this->faker->word;
        $linkedFieldValue = $this->faker->word;

        $linkedFieldId = $this->query()->table(ConfigService::$tableFields)->insertGetId(
            [
                'key' => $linkedFieldKey,
                'value' => $linkedFieldValue,
                'type' => 'string',
                'position' => null,
            ]
        );

        $linkedContentFieldLinkId = $this->query()->table(ConfigService::$tableContentFields)->insertGetId(
            [
                'content_id' => $contentId,
                'field_id' => $linkedFieldId,
            ]
        );

        $linkedDatumKey = $this->faker->word;
        $linkedDatumValue = $this->faker->word;

        $linkedDatumId = $this->query()->table(ConfigService::$tableData)->insertGetId(
            [
                'key' => $linkedDatumKey,
                'value' => $linkedDatumValue,
                'position' => 1,
            ]
        );

        $linkedContentDatumLinkId = $this->query()->table(ConfigService::$tableContentData)->insertGetId(
            [
                'content_id' => $contentId,
                'datum_id' => $linkedDatumId,
            ]
        );

        $linkedDatumKey2 = $this->faker->word;
        $linkedDatumValue2 = $this->faker->word;

        $linkedDatumId2 = $this->query()->table(ConfigService::$tableData)->insertGetId(
            [
                'key' => $linkedDatumKey2,
                'value' => $linkedDatumValue2,
                'position' => 1,
            ]
        );

        $linkedContentDatumLinkId2 = $this->query()->table(ConfigService::$tableContentData)->insertGetId(
            [
                'content_id' => $contentId,
                'datum_id' => $linkedDatumId2,
            ]
        );

        $response = $this->classBeingTested->getById($contentId);

        $this->assertEquals(
            [
                "id" => $contentId,
                "slug" => $content["slug"],
                "status" => $content["status"],
                "type" => $content["type"],
                "position" => $content["position"],
                "parent_id" => $content["parent_id"],
                "published_on" => $content["published_on"],
                "created_on" => $content["created_on"],
                "archived_on" => $content["archived_on"],
                "fields" => [
                    $linkedFieldKey => $linkedFieldValue,
                ],
                "datum" => [
                    $linkedDatumKey => $linkedDatumValue,
                    $linkedDatumKey2 => $linkedDatumValue2
                ],
            ],
            $response
        );
    }

    /*
     *
     */
    public function test_get_paginated_with_searched_fields()
    {
        $page = 1;
        $amount = 10;
        $orderByDirection = 'desc';
        $orderByColumn = 'published_on';
        $statues = [$this->faker->word, $this->faker->word, $this->faker->word];
        $types = [$this->faker->word, $this->faker->word, $this->faker->word];
        $parentId = null;
        $includeFuturePublishedOn = false;
        $requiredFields = [
            'topic' => 'jazz'
        ];

        $expectedContent = [];

        $field = [
            'key' => 'topic',
            'value' => 'jazz',
            'type' => 'multiple',
            'position' => 1
        ];

        $fieldId = $this->query()->table(ConfigService::$tableFields)->insertGetId($field);


        // insert matching content
        for($i = 0; $i < 30; $i++) {
            $content = [
                'slug' => $this->faker->word,
                'status' => $this->faker->randomElement($statues),
                'type' => $this->faker->randomElement($types),
                'position' => $this->faker->numberBetween(),
                'parent_id' => $parentId,
                'published_on' => Carbon::now()->subDays(($i + 1) * 10)->toDateTimeString(),
                'created_on' => Carbon::now()->toDateTimeString(),
                'archived_on' => null,
            ];

            $contentId = $this->query()->table(ConfigService::$tableContent)->insertGetId($content);

            $contents[$contentId] = array_merge(['id' => $contentId], $content);
        }

        //link field to 5 contents
        for($i = 5; $i < 10; $i++) {
            $this->query()->table(ConfigService::$tableContentFields)->insertGetId(
                [
                    'content_id' => $contents[$i]['id'],
                    'field_id' => $fieldId,
                ]
            );
            $expectedContent[$i] = array_merge($contents[$i], ['fields' => ['topic' => [1 => 'jazz']]]);
        }

        $response = $this->classBeingTested->getPaginated(
            $page,
            $amount,
            $orderByDirection,
            $orderByColumn,
            $statues,
            $types,
            $requiredFields,
            $parentId,
            $includeFuturePublishedOn
        );

        //check that in the response we have 5 results
        $this->assertEquals(5, count($response));

        $this->assertEquals($expectedContent, $response);

        // for some reason phpunit doesn't test the order of the array values
        $this->assertEquals(array_keys($expectedContent), array_keys($response));
    }

    /**
     * Get a courses 10th to 20th lessons where the instructor is caleb and the topic is bass drumming
     */
    public function test_get_paginated_search_parent_fields()
    {
        $page = 2;
        $amount = 10;
        $orderByDirection = 'desc';
        $orderByColumn = 'published_on';
        $statues = [$this->faker->word, $this->faker->word, $this->faker->word];
        $types = ['course lessons'];
        $parentId = null;
        $includeFuturePublishedOn = false;
        $requiredFields = [
            'instructor' => 'caleb',
            'topic' => 'bass drumming'
        ];

        $expectedContent = [];

        //create 2 courses with instructor 'caleb' and different topics
        $course1 = [
            'slug' => $this->faker->word,
            'status' => $this->faker->randomElement($statues),
            'type' => 'course',
            'position' => $this->faker->numberBetween(),
            'parent_id' => null,
            'published_on' => Carbon::now()->subDays(10)->toDateTimeString(),
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null
        ];

        $courseId1 = $this->query()->table(ConfigService::$tableContent)->insertGetId($course1);

        $course2 = [
            'slug' => $this->faker->word,
            'status' => $this->faker->randomElement($statues),
            'type' => 'course',
            'position' => $this->faker->numberBetween(),
            'parent_id' => null,
            'published_on' => Carbon::now()->subDays(10)->toDateTimeString(),
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null
        ];

        $courseId2 = $this->query()->table(ConfigService::$tableContent)->insertGetId($course2);

        //create and link instructor caleb to the course
        $instructor = [
            'slug' => 'caleb',
            'status' => $this->faker->word,
            'type' => 'instructor',
            'position' => $this->faker->numberBetween(),
            'parent_id' => null,
            'published_on' => Carbon::now()->subDays(10)->toDateTimeString(),
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null
        ];
        $instructorId = $this->query()->table(ConfigService::$tableContent)->insertGetId($instructor);

        $fieldForInstructor = [
            'key' => 'instructor',
            'value' => $instructorId,
            'type' => 'content_id',
            'position' => null
        ];

        $fieldForInstructorId = $this->query()->table(ConfigService::$tableFields)->insertGetId($fieldForInstructor);

        $this->query()->table(ConfigService::$tableContentFields)->insertGetId(
            [
                'content_id' => $courseId1,
                'field_id' => $fieldForInstructorId,
            ]
        );

        $this->query()->table(ConfigService::$tableContentFields)->insertGetId(
            [
                'content_id' => $courseId2,
                'field_id' => $fieldForInstructorId,
            ]
        );

        //create and link topic bass drumming to the course
        $fieldForTopic = [
            'key' => 'topic',
            'value' => 'bass drumming',
            'type' => 'multiple',
            'position' => 1
        ];

        $fieldForInstructorId = $this->query()->table(ConfigService::$tableFields)->insertGetId($fieldForTopic);

        $this->query()->table(ConfigService::$tableContentFields)->insertGetId(
            [
                'content_id' => $courseId1,
                'field_id' => $fieldForInstructorId,
            ]
        );

        //link 25 lessons to the course
        for($i = 0; $i < 25; $i++) {
            $content = [
                'slug' => $this->faker->word,
                'status' => $this->faker->randomElement($statues),
                'type' => 'course lessons',
                'position' => $this->faker->numberBetween(),
                'parent_id' => $courseId1,
                'published_on' => Carbon::now()->subDays(($i + 1) * 10)->toDateTimeString(),
                'created_on' => Carbon::now()->toDateTimeString(),
                'archived_on' => null,
            ];

            $contentId = $this->query()->table(ConfigService::$tableContent)->insertGetId($content);

            $contents[$contentId] = array_merge(['id' => $contentId], $content);
        }

        //Get 10th to 20th lessons for the course with instructor 'caleb' and topic 'bass drumming'
        $expectedContent = array_slice($contents, 10, 10, true);

        $response = $this->classBeingTested->getPaginated(
            $page,
            $amount,
            $orderByDirection,
            $orderByColumn,
            $statues,
            $types,
            $requiredFields,
            $parentId,
            $includeFuturePublishedOn
        );

        //check that in the response we have 10 results
        $this->assertEquals(10, count($response));

        //check that the expected contents are returned
        $this->assertEquals($expectedContent, $response);

        // for some reason phpunit doesn't test the order of the array values
        $this->assertEquals(array_keys($expectedContent), array_keys($response));
    }

    /*
     * Get 40th to 60th library lesson where the topic is snare
     */
    public function test_get_paginated_library_lesson_with_topic()
    {
        $page = 3;
        $amount = 20;
        $orderByDirection = 'desc';
        $orderByColumn = 'published_on';
        $statues = ['published'];
        $types = ['library lessons'];
        $parentId = null;
        $includeFuturePublishedOn = false;
        $requiredFields = [
            'topic' => 'snare'
        ];

        $expectedContent = [];

        for($i = 0; $i < 80; $i++) {
            $content = [
                'slug' => $this->faker->word,
                'status' => 'published',
                'type' => 'library lessons',
                'position' => $this->faker->numberBetween(),
                'parent_id' => null,
                'published_on' => Carbon::now()->subDays(($i + 1) * 10)->toDateTimeString(),
                'created_on' => Carbon::now()->toDateTimeString(),
                'archived_on' => null,
            ];

            $contentId = $this->query()->table(ConfigService::$tableContent)->insertGetId($content);

            $contents[$contentId] = array_merge(['id' => $contentId], $content);
        }

        //link topic snare to 65 library lessons
        $fieldForTopic = [
            'key' => 'topic',
            'value' => 'snare',
            'type' => 'string',
            'position' => 1
        ];

        $fieldForTopicId = $this->query()->table(ConfigService::$tableFields)->insertGetId($fieldForTopic);


        for($i = 1; $i < 66; $i++) {

            $this->query()->table(ConfigService::$tableContentFields)->insertGetId(
                [
                    'content_id' => $contents[$i]['id'],
                    'field_id' => $fieldForTopicId,
                ]
            );

            $expectedContent[$i] = array_merge($contents[$i], ['fields' => [
                'topic' => 'snare'
            ]
            ]);
        }

        //Get 40th to 60th library lesson where the topic is snare
        $expectedContent = array_slice($expectedContent, 40, 20, true);

        $response = $this->classBeingTested->getPaginated(
            $page,
            $amount,
            $orderByDirection,
            $orderByColumn,
            $statues,
            $types,
            $requiredFields,
            $parentId,
            $includeFuturePublishedOn
        );

        //check that in the response we have 20 results
        $this->assertEquals(20, count($response));

        //check that the expected contents are returned
        $this->assertEquals($expectedContent, $response);

        // for some reason phpunit doesn't test the order of the array values
        $this->assertEquals(array_keys($expectedContent), array_keys($response));
    }

    /**
     * Get the most recent play along draft lesson
     */
    public function test_get_most_recent_content_with_type()
    {
        $page = 1;
        $amount = 1;
        $orderByDirection = 'desc';
        $orderByColumn = 'id';
        $statues = ['draft'];
        $types = ['play along'];
        $parentId = null;
        $includeFuturePublishedOn = true;
        $requiredFields = [];

        $expectedContent = [];
        $content1 = [
            'slug' => $this->faker->word,
            'status' => 'draft',
            'type' => 'play along',
            'position' => $this->faker->numberBetween(),
            'parent_id' => null,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];

        $contentId1 = $this->query()->table(ConfigService::$tableContent)->insertGetId($content1);

        $content2 = [
            'slug' => $this->faker->word,
            'status' => 'draft',
            'type' => 'play along',
            'position' => $this->faker->numberBetween(),
            'parent_id' => null,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];

        $contentId2 = $this->query()->table(ConfigService::$tableContent)->insertGetId($content2);

        //last inserted content it's expected to be returned by the getPaginated method
        $expectedContent[$contentId2] = array_merge(['id' => $contentId2], $content2);

        $response = $this->classBeingTested->getPaginated(
            $page,
            $amount,
            $orderByDirection,
            $orderByColumn,
            $statues,
            $types,
            $requiredFields,
            $parentId,
            $includeFuturePublishedOn
        );

        //check that the expected contents are returned
        $this->assertEquals($expectedContent, $response);

        // for some reason phpunit doesn't test the order of the array values
        $this->assertEquals(array_keys($expectedContent), array_keys($response));
    }

    public function test_can_not_view_content_if_dont_have_permission()
    {
        $page = 1;
        $amount = 1;
        $orderByDirection = 'desc';
        $orderByColumn = 'id';
        $statues = ['draft'];
        $types = ['play along'];
        $parentId = null;
        $includeFuturePublishedOn = true;
        $requiredFields = [];

        $content1 = [
            'slug' => $this->faker->word,
            'status' => 'draft',
            'type' => 'play along',
            'position' => $this->faker->numberBetween(),
            'parent_id' => null,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];

        $contentId1 = $this->query()->table(ConfigService::$tableContent)->insertGetId($content1);

        $permission = [
            'name' => $this->faker->word,
            'created_on' => Carbon::now()->toDateTimeString()
        ];

        $permissionId = $this->query()->table(ConfigService::$tablePermissions)->insertGetId($permission);

        $contentPermission = [
            'content_id' => $contentId1,
            'content_type' => null,
            'required_permission_id' => $permissionId
        ];

        $this->query()->table(ConfigService::$tableContentPermissions)->insertGetId($contentPermission);

        $response = $this->classBeingTested->getPaginated(
            $page,
            $amount,
            $orderByDirection,
            $orderByColumn,
            $statues,
            $types,
            $requiredFields,
            $parentId,
            $includeFuturePublishedOn
        );

        $this->assertEquals([], $response);
    }

    public function test_can_view_specific_content_if_have_permission()
    {
        $permissionName = $this->faker->word;

        //add user permission on request
        request()->request->add(['permissions' => [$permissionName]]);

        $page = 1;
        $amount = 1;
        $orderByDirection = 'desc';
        $orderByColumn = 'id';
        $statues = ['draft'];
        $types = ['play along'];
        $parentId = null;
        $includeFuturePublishedOn = true;
        $requiredFields = [];

        $expectedContent = [];
        $content1 = [
            'slug' => $this->faker->word,
            'status' => 'draft',
            'type' => 'play along',
            'position' => $this->faker->numberBetween(),
            'parent_id' => null,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];

        $contentId1 = $this->query()->table(ConfigService::$tableContent)->insertGetId($content1);

        $permission = [
            'name' => $permissionName,
            'created_on' => Carbon::now()->toDateTimeString()
        ];

        $permissionId = $this->query()->table(ConfigService::$tablePermissions)->insertGetId($permission);

        $contentPermission = [
            'content_id' => $contentId1,
            'content_type' => null,
            'required_permission_id' => $permissionId
        ];

        $this->query()->table(ConfigService::$tableContentPermissions)->insertGetId($contentPermission);

        $response = $this->classBeingTested->getPaginated(
            $page,
            $amount,
            $orderByDirection,
            $orderByColumn,
            $statues,
            $types,
            $requiredFields,
            $parentId,
            $includeFuturePublishedOn
        );
        $expectedContent[$contentId1] = array_merge(['id' => $contentId1], $content1);

        $this->assertEquals($expectedContent, $response);
    }

    public function test_can_view_all_contents_with_specific_type_if_have_permission()
    {
        $permissionName = $this->faker->word;

        $permission = [
            'name' => $permissionName,
            'created_on' => Carbon::now()->toDateTimeString()
        ];

        $permissionId = $this->query()->table(ConfigService::$tablePermissions)->insertGetId($permission);

        $contentType = $this->faker->word;

        $contentPermission = [
            'content_id' => null,
            'content_type' => $contentType,
            'required_permission_id' => $permissionId
        ];


        $this->query()->table(ConfigService::$tableContentPermissions)->insertGetId($contentPermission);

        //add user permission on request
        request()->request->add(['permissions' => [$permissionName]]);

        $page = 1;
        $amount = 100;
        $orderByDirection = 'asc';
        $orderByColumn = 'id';
        $statues = ['draft'];
        $types = [$contentType];
        $parentId = null;
        $includeFuturePublishedOn = true;
        $requiredFields = [];

        for($i = 0; $i < 50; $i++) {
            $content = [
                'slug' => $this->faker->word,
                'status' => 'draft',
                'type' => $contentType,
                'position' => $this->faker->numberBetween(),
                'parent_id' => null,
                'published_on' => Carbon::now()->subDays(($i+10))->toDateTimeString(),
                'created_on' => Carbon::now()->toDateTimeString(),
                'archived_on' => null,
            ];

            $contentId = $this->query()->table(ConfigService::$tableContent)->insertGetId($content);

            $contents[$contentId] = array_merge(['id' => $contentId], $content);
        }

        $response = $this->classBeingTested->getPaginated(
            $page,
            $amount,
            $orderByDirection,
            $orderByColumn,
            $statues,
            $types,
            $requiredFields,
            $parentId,
            $includeFuturePublishedOn
        );

        $this->assertEquals($contents, $response);
    }

    public function test_can_not_view_content_without_permission()
    {
        $content1 = [
            'slug' => $this->faker->word,
            'status' => 'draft',
            'type' => 'play along',
            'position' => $this->faker->numberBetween(),
            'parent_id' => null,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];

        $contentId1 = $this->query()->table(ConfigService::$tableContent)->insertGetId($content1);

        $permissionName = $this->faker->word;
        $permission = [
            'name' => $permissionName,
            'created_on' => Carbon::now()->toDateTimeString()
        ];

        $permissionId = $this->query()->table(ConfigService::$tablePermissions)->insertGetId($permission);

        $contentPermission = [
            'content_id' => $contentId1,
            'content_type' => null,
            'required_permission_id' => $permissionId
        ];

        $this->query()->table(ConfigService::$tableContentPermissions)->insertGetId($contentPermission);

        $response = $this->classBeingTested->getById($contentId1);

        $this->assertNull($response);

    }

    public function test_can_view_content_if_have_permission_to_access_specific_id()
    {
        $content1 = [
            'slug' => $this->faker->word,
            'status' => 'draft',
            'type' => 'play along',
            'position' => $this->faker->numberBetween(),
            'parent_id' => null,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];

        $contentId1 = $this->query()->table(ConfigService::$tableContent)->insertGetId($content1);

        $permissionName = $this->faker->word;
        $permission = [
            'name' => $permissionName,
            'created_on' => Carbon::now()->toDateTimeString()
        ];

        $permissionId = $this->query()->table(ConfigService::$tablePermissions)->insertGetId($permission);

        $contentPermission = [
            'content_id' => $contentId1,
            'content_type' => null,
            'required_permission_id' => $permissionId
        ];

        $this->query()->table(ConfigService::$tableContentPermissions)->insertGetId($contentPermission);

        //add user permission on request
        request()->request->add(['permissions' => [$permissionName]]);

        $response = $this->classBeingTested->getById($contentId1);

        $this->assertEquals(array_merge(['id' => $contentId1], $content1),$response);
    }

    public function test_can_view_content_with_type_if_have_permission_to_access_type()
    {
        $contentType = $this->faker->word;

        $content1 = [
            'slug' => $this->faker->word,
            'status' => 'draft',
            'type' => $contentType,
            'position' => $this->faker->numberBetween(),
            'parent_id' => null,
            'published_on' => null,
            'created_on' => Carbon::now()->toDateTimeString(),
            'archived_on' => null,
        ];

        $contentId1 = $this->query()->table(ConfigService::$tableContent)->insertGetId($content1);

        $permissionName = $this->faker->word;
        $permission = [
            'name' => $permissionName,
            'created_on' => Carbon::now()->toDateTimeString()
        ];

        $permissionId = $this->query()->table(ConfigService::$tablePermissions)->insertGetId($permission);

        $contentPermission = [
            'content_id' => null,
            'content_type' => $contentType,
            'required_permission_id' => $permissionId
        ];

        $this->query()->table(ConfigService::$tableContentPermissions)->insertGetId($contentPermission);

        //add user permission on request
        request()->request->add(['permissions' => [$permissionName]]);

        $response = $this->classBeingTested->getById($contentId1);

        $this->assertEquals(array_merge(['id' => $contentId1], $content1),$response);

    }

    /**
     * @return \Illuminate\Database\Connection
     */
    public function query()
    {
        return $this->databaseManager->connection();
    }
}