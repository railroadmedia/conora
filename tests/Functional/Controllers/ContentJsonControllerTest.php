<?php

namespace Railroad\Railcontent\Tests\Functional\Controllers;

use Carbon\Carbon;
use Faker\ORM\Doctrine\Populator;
use Railroad\Railcontent\Entities\Content;
use Railroad\Railcontent\Entities\ContentHierarchy;
use Railroad\Railcontent\Entities\ContentInstructor;
use Railroad\Railcontent\Entities\Permission;
use Railroad\Railcontent\Entities\UserContentProgress;
use Railroad\Railcontent\Entities\UserPermission;
use Railroad\Railcontent\Services\ConfigService;
use Railroad\Railcontent\Services\ContentService;
use Railroad\Railcontent\Tests\RailcontentTestCase;
use Response;

class ContentJsonControllerTest extends RailcontentTestCase
{
    /**
     * @var ContentService
     */
    protected $serviceBeingTested;

    protected function setUp()
    {
        parent::setUp();

        $this->serviceBeingTested = $this->app->make(ContentService::class);
    }

    public function test_index_empty()
    {
        $response = $this->call('GET', 'railcontent/content');

        $this->assertEquals([], $response->decodeResponseJson('data'));
    }

    public function test_store_response_status()
    {
        $slug = $this->faker->word;
        $type = 'course';
        $status = ContentService::STATUS_SCHEDULED;

        $response = $this->call(
            'PUT',
            'railcontent/content',
            [
                'data' => [
                    'type' => 'content',
                    'attributes' => [
                        'slug' => $slug,
                        'position' => null,
                        'status' => $status,
                        'parent_id' => null,
                        'brand' => ConfigService::$brand,
                        'type' => $type,
                        'published_on' => Carbon::now()
                            ->toDateTimeString(),
                    ],
                ],
            ]
        );

        $this->assertEquals(201, $response->status());
    }

    public function test_store_not_pass_the_validation()
    {
        $response = $this->put('railcontent/content');

        //expecting it to redirect us to previous page.
        $this->assertEquals(422, $response->status());

        //check that all the error messages are received
        $errors = [
            [
                'title' => 'Validation failed.',
                'source' => 'data.attributes.status',
                'detail' => 'The status field is required.',
            ],
            [
                'title' => 'Validation failed.',
                'source' => 'data.attributes.type',
                'detail' => 'The type field is required.',
            ],
        ];

        $this->assertArraySubset($errors, $response->decodeResponseJson('errors'));
    }

    public function test_store_with_negative_position()
    {
        $slug = $this->faker->word;
        $type = $this->faker->word;
        $status = ContentService::STATUS_DRAFT;

        $response = $this->call(
            'PUT',
            'railcontent/content',
            [
                'data' => [
                    'type' => 'content',
                    'attributes' => [
                        'slug' => $slug,
                        'status' => $status,
                        'type' => $type,
                        'position' => -1,
                    ],
                ],
            ]
        );

        //expecting it to redirect us to previous page.
        $this->assertEquals(422, $response->status());

        //check that all the error messages are received
        $errors = [
            [
                'source' => 'data.attributes.position',
                'detail' => 'The position must be at least 0.',
                'title' => 'Validation failed.',
            ],

        ];
        $this->assertEquals($errors, $response->decodeResponseJson('errors'));
    }

    public function _test_store_with_custom_validation_and_slug_huge()
    {
        $slug = $this->faker->text(500);
        $status = ContentService::STATUS_DRAFT;

        $response = $this->call(
            'PUT',
            'railcontent/content',
            [
                'slug' => $slug,
                'status' => $status,
                'type' => $this->faker->word,
                'position' => 1,
            ]
        );

        $this->assertEquals(422, $response->status());

        $errors = [
            [
                'source' => "slug",
                "detail" => "The slug may not be greater than 64 characters.",
            ],

        ];
        $this->assertEquals($errors, json_decode($response->content(), true)['errors']);
    }

    public function test_store_published_on_not_required()
    {
        $slug = $this->faker->word;
        $type = $this->faker->word;
        $position = $this->faker->numberBetween();
        $status = ContentService::STATUS_DRAFT;

        $response = $this->call(
            'PUT',
            'railcontent/content',
            [
                'data' => [
                    'attributes' => [
                        'slug' => $slug,
                        'status' => $status,
                        'type' => $type,
                        'position' => $position,
                    ],
                ],
            ]
        );

        //expecting that the response has a successful status code
        $response->assertSuccessful();
    }

    public function test_content_created_is_returned_in_json_format()
    {
        $slug = $this->faker->word;
        $type = $this->faker->word;
        $position = $this->faker->numberBetween();
        $status = ContentService::STATUS_PUBLISHED;

        $contentData = [
            'slug' => $slug,
            'status' => $status,
            'type' => $type,
            'sort' => $position,
            'brand' => ConfigService::$brand,
        ];
        $response = $this->call(
            'PUT',
            'railcontent/content',
            [
                'data' => [
                    'attributes' => $contentData,
                ],
            ]
        );

        $this->assertArraySubset($contentData, $response->decodeResponseJson('data')['attributes']);
    }

    public function test_content_service_return_new_content_after_create()
    {
        $content = $this->fakeContent(
            1,
            [
                'slug' => 'slug1',
            ]
        );

        $response = $this->call('GET', 'railcontent/content/' . $content[0]->getId());

        // assert the user data is subset of response
        $this->assertArraySubset(
            [
                'slug' => 'slug1',
            ],
            $response->decodeResponseJson()['data']['attributes']
        );
    }

    public function test_update_response_status()
    {
        $content = $this->fakeContent();

        $response = $this->call(
            'PATCH',
            'railcontent/content/' . $content[0]->getId(),
            [
                'slug' => 'new slug',
                'status' => ContentService::STATUS_PUBLISHED,
                'type' => 'roxana',
            ]
        );

        $this->assertEquals(200, $response->status());
    }

    public function test_update_missing_content_response_status()
    {
        $slug = implode('-', $this->faker->words());
        $type = $this->faker->word;

        $response = $this->call(
            'PATCH',
            'railcontent/content/' . rand(),
            [
                'slug' => $slug,
                'position' => 1,
                'status' => ContentService::STATUS_DRAFT,
                'type' => $type,
            ]
        );
        $this->assertEquals(404, $response->status());
    }

    public function test_update_with_negative_position()
    {
        $response = $this->call(
            'PATCH',
            'railcontent/content/' . 1,
            [
                'data' => [
                    'attributes' => [
                        'position' => -1,
                    ],
                ],
            ]
        );

        //expecting a response with 422 status
        $this->assertEquals(422, $response->status());
        //check that the error message is received
        $errors = [
            [
                'source' => 'data.attributes.position',
                'detail' => 'The data.attributes.position must be at least 0.',
                'title' => 'Validation failed.',
            ],
        ];
        $this->assertEquals($errors, $response->decodeResponseJson('errors'));
    }

    public function test_update_not_pass_the_validation()
    {
        $response = $this->call(
            'PATCH',
            'railcontent/content/' . 1,
            [
                'data' => [
                    'attributes' => [
                        'status' => $this->faker->word,
                    ],
                ],
            ]
        );

        //expecting a response with 422 status
        $this->assertEquals(422, $response->status());

        //check that all the error messages are received
        $errors = [
            [
                "title" => "Validation failed.",
                "source" => "data.attributes.status",
                "detail" => "The selected status is invalid.",
            ],
        ];
        $this->assertEquals($errors, $response->decodeResponseJson('errors'));
    }

    public function test_after_update_content_is_returned_in_json_format()
    {
        $content = $this->fakeContent();

        $new_slug = implode('-', $this->faker->words());

        $response = $this->call(
            'PATCH',
            'railcontent/content/' . $content[0]->getId(),
            [
                'data' => [
                    'attributes' => [
                        'slug' => $new_slug,
                        'status' => ContentService::STATUS_PUBLISHED,
                    ],
                ],
            ]
        );

        $this->assertArraySubset(
            [
                'data' => [
                    'attributes' => [
                        'slug' => $new_slug,
                        'status' => ContentService::STATUS_PUBLISHED,
                    ],
                ],
            ],
            $response->decodeResponseJson()
        );
    }

    public function test_content_service_return_updated_content_after_update()
    {
        $content = $this->fakeContent();

        $new_slug = implode('-', $this->faker->words());
        $updatedContent = $this->serviceBeingTested->update(
            $content[0]->getId(),
            [
                'data' => [
                    'attributes' => [
                        "slug" => $new_slug,
                    ],
                ],
            ]
        );

        $this->assertEquals($new_slug, $updatedContent->getSlug());
    }

    public function test_service_delete_method_result()
    {
        $content = $this->fakeContent();
        $delete = $this->serviceBeingTested->delete($content[0]->getId());

        $this->assertTrue($delete);
    }

    public function test_service_delete_method_when_content_not_exist()
    {
        $delete = $this->serviceBeingTested->delete(rand(100, 500));

        $this->assertNull($delete);
    }

    public function test_controller_delete_method_response_status()
    {
        $content = $this->fakeContent();

        $response = $this->call('DELETE', 'railcontent/content/' . $content[0]->getId());

        $this->assertEquals(204, $response->status());

        $this->assertDatabaseMissing(
            ConfigService::$tableContent,
            [
                'id' => 1,
            ]
        );
    }

    public function test_delete_missing_content_response_status()
    {
        $randomId = $this->faker->numberBetween();
        $response = $this->call('DELETE', 'railcontent/content/' . $randomId);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function test_index_response_no_results()
    {
        $randomContents = $this->fakeContent(12);

        $response = $this->call(
            'GET',
            'railcontent/content',
            [
                'page' => 1,
                'amount' => 10,
                'statues' => ['draft', 'published'],
                'types' => ['course'],
                'fields' => [],
                'parent_slug' => '',
                'include_future_published_on' => false,
            ]
        );

        $expectedResults = [];

        $this->assertEquals(200, $response->status());

        $response->assertJson($expectedResults);
    }

    public function test_index_with_results()
    {
        $content = $this->fakeContent(
            3,
            [
                'difficulty' => 1,
                'type' => 'course',
            ]
        );
        $otherContent = $this->fakeContent(12);

        $types = ['course'];
        $page = 1;
        $limit = 10;
        $filter = ['difficulty,1'];

        $response = $this->call(
            'GET',
            'railcontent/content',
            [
                'page' => $page,
                'limit' => $limit,
                'sort' => 'id',
                'included_types' => $types,
                'required_fields' => $filter,
            ]
        );

        $responseContent = $response->decodeResponseJson('data');

        foreach ($responseContent as $content) {
            $this->assertTrue(in_array($content['attributes']['type'], $types));
            $this->assertEquals(1, $content['attributes']['difficulty']);
        }
    }

    public function _test_index_with_required_fields()
    {
        $expectedResults = [];
        $statues = ['published'];
        $types = ['course'];
        $page = 1;
        $limit = 10;

        $expectedContent = [
            'page' => $page,
            'limit' => $limit,
            'status' => 'ok',
            'code' => 200,
        ];

        $nrCourses = 30;

        //create courses
        //        for ($i = 1; $i < $nrCourses; $i++) {
        //            $content = $this->contentFactory->create(
        //                $this->faker->word,
        //                $types[0],
        //                $this->faker->randomElement($statues)
        //            );
        //            $contents[$i] = (array)$content;
        //        }
        //
        //        $contentWithFieldsNr = 5;
        $fieldKey = 'difficulty';
        $fieldValue = 1;
        $fieldType = 'integer';
        //
        $filter = [$fieldKey . ',' . $fieldValue . ',' . $fieldType];
        //        $expectedContent['filter_options'] = [];

        //        for ($i = 1; $i < $contentWithFieldsNr; $i++) {
        //            $field =
        //                $this->fieldFactory->create($contents[$i]['id'], $fieldKey, $fieldValue, null, $fieldType)
        //                    ->getArrayCopy();
        //
        //            $expectedResults[$i - 1] = $contents[$i];
        //            $expectedResults[$i - 1]['fields'][] = array_merge($field, ['id' => $field['id']]);
        //        }
        //        $expectedContent['results'] = $expectedResults;
        //        $expectedContent['total_results'] = $contentWithFieldsNr - 1;

        $response = $this->call(
            'GET',
            'railcontent/content',
            [
                'page' => $page,
                'limit' => $limit,
                'sort' => 'id',
                'included_types' => $types,
                'required_fields' => $filter,
            ]

        );
        $responseContent = $response->decodeResponseJson('data');

        $this->assertArraySubset($expectedContent['results'], $responseContent);
    }

    //Get 5 courses with given string field
    public function test_index_with_fields()
    {
        $statues = ['published'];
        $types = ['course'];
        $page = 1;
        $limit = 5;

        $randomContents = $this->fakeContent(5);
        $contents = $this->fakeContent(
            6,
            [
                'difficulty' => 1,
                'type' => 'course',
                'status' => 'published',
            ]
        );
        $instructor = $this->fakeContent(
            1,
            [
                'type' => 'instructor',
                'status' => 'published',
                'slug' => $this->faker->name,
                'brand' => config('railcontent.brand'),
            ]
        );
        foreach ($contents as $content) {
            $contentInstructor = $this->fakeContentInstructor(
                1,
                [
                    'content' => $content,
                    'instructor' => $instructor[0],
                ]
            );
        }

        $randomContents = $this->fakeContent(19);

        $response = $this->call(
            'GET',
            'railcontent/content',
            [
                'page' => $page,
                'limit' => $limit,
                'statues' => $statues,
                'sort' => 'id',
                'included_types' => $types,
                'required_fields' => [
                    'difficulty,1',
                    'instructor,' . $instructor[0]->getId(),
                ],
            ]
        );

        $responseContent = $response->decodeResponseJson('data');
        foreach ($responseContent as $data) {
            $this->assertEquals(1, $data['attributes']['difficulty']);
            $this->assertEquals($statues[0], $data['attributes']['status']);
        }
    }

    public function test_getByParentId_when_parentId_not_exist()
    {
        $response = $this->call(
            'GET',
            'railcontent/content/parent/' . rand()
        );

        $this->assertEquals([], $response->decodeResponseJson('data'));
    }

    public function test_getByParentId()
    {
        $parent = $this->fakeContent();
        $child = $this->fakeContent();
        $hierarchy = $this->fakeHierarchy(
            1,
            [
                'parent' => $parent[0],
                'child' => $child[0],
            ]
        );

        $response = $this->call(
            'GET',
            'railcontent/content/parent/' . $parent[0]->getId()
        );

        $results = $response->decodeResponseJson('data');

        $this->assertEquals(1, count($results));
        $this->assertEquals($child[0]->getId(), $results[0]['id']);
    }

    public function test_get_by_ids_when_ids_not_exists()
    {
        $response = $this->call(
            'GET',
            'railcontent/content/get-by-ids',
            [rand(), rand()]
        );

        $this->assertEquals([], $response->decodeResponseJson('data'));
    }

    public function test_get_by_ids()
    {
        $contents = $this->fakeContent(2);
        $response = $this->call(
            'GET',
            'railcontent/content/get-by-ids',
            ['ids' => 2 . ',' . 1]
        );

        $this->assertArraySubset([['id' => 2], ['id' => 1]], $response->decodeResponseJson('data'));
    }

    public function test_get_id_cached()
    {
        //        $content1 = $this->contentFactory->create(
        //            $this->faker->word,
        //            $this->faker->randomElement(ConfigService::$commentableContentTypes),
        //            ContentService::STATUS_PUBLISHED
        //        );

        // $id = $content1->getId();
        $start1 = microtime(true);
        $response = $this->call('GET', 'railcontent/content/' . 1);

        $time1 = microtime(true) - $start1;

        $start2 = microtime(true);
        $response = $this->call('GET', 'railcontent/content/' . 1);
        $time2 = microtime(true) - $start2;

        $start3 = microtime(true);
        $response = $this->call('GET', 'railcontent/content/' . 1);
        $time3 = microtime(true) - $start3;

        $start4 = microtime(true);
        $response = $this->call('GET', 'railcontent/content/' . 1);
        $time4 = microtime(true) - $start4;

        $start5 = microtime(true);
        $response = $this->call('GET', 'railcontent/content/' . 1);
        $time5 = microtime(true) - $start5;

        $start6 = microtime(true);
        $response = $this->call(
            'GET',
            'railcontent/content/get-by-ids',
            ['ids' => 1]
        );
        $time6 = microtime(true) - $start6;

        $start7 = microtime(true);
        $response = $this->call(
            'GET',
            'railcontent/content/get-by-ids',
            ['ids' => 1]
        );
        $time7 = microtime(true) - $start7;

        $response->assertStatus(200);
    }

    public function test_store_content_execution_time()
    {
        $slug = $this->faker->word;
        $type = $this->faker->word;
        $status = ContentService::STATUS_PUBLISHED;

        //prepare Redis cache with 300.000 keys that will be deleted when a new content it's created
        //        for ($i = 0; $i < 100000; $i++) {
        //            Redis::set('contents_results_' . $i, $i);
        //            Redis::set('_type_' . $type . $i, $i);
        //            Redis::set('types' . $i, $i);
        //        }

        $executionStartTime = microtime(true);

        $response = $this->call(
            'PUT',
            'railcontent/content',
            [
                'data' => [
                    'attributes' => [
                        'slug' => $slug,
                        'position' => null,
                        'status' => $status,
                        'parent_id' => null,
                        'type' => $type,
                        'published_on' => Carbon::now()
                            ->toDateTimeString(),
                    ],
                ],
            ]
        );
        $executionEndTime = microtime(true);

        //The result will be in seconds and milliseconds.
        $seconds = $executionEndTime - $executionStartTime;

        //Print it out
        echo "Create content method(inclusive clear Redis cache) took $seconds seconds to execute when in Redis cache exists 300.000 keys that should be deleted.";

        $this->assertEquals(201, $response->status());
    }

    public function test_pull_content_permission()
    {
        $userId = $this->createAndLogInNewUser();
        $content1 = $this->fakeContent();

        $permission = $this->fakePermission();

        $this->fakeContentPermission(
            1,
            [
                'content' => $content1[0],
                'permission' => $permission[0],
            ]
        );

        $content2 = $this->fakeContent();

        $permission2 = $this->fakePermission();

        $this->fakeContentPermission(
            1,
            [
                'content' => $content2[0],
                'permission' => $permission2[0],
            ]
        );

        $userPermission = $this->fakeUserPermission(
            1,
            [
                'permission' => $permission[0],
                'userId' => $userId,
                'startDate' => Carbon::parse(now())
                    ->subMonth(2),
                'expirationDate' => Carbon::parse(now())
                    ->addMonth(1),
            ]
        );

        $response = $this->call(
            'GET',
            'railcontent/content/get-by-ids',
            ['ids' => $content2[0]->getId() . ',' . $content1[0]->getId()]
        );
        $results = $response->decodeResponseJson('data');

        $this->assertEquals(1, count($results));
        $this->assertEquals($content1[0]->getId(), $results[0]['id']);
    }

    public function test_pull_content_user_permission()
    {
        $user = $this->createAndLogInNewUser();
        $contents = $this->fakeContent(2);

        $permission = $this->fakePermission();
        $this->fakeContentPermission(1, [
            'content' => $contents[0],
            'permission' => $permission[0]
        ]);

   $this->fakeUserPermission(1, [
       'userId' => $user,
       'permission' => $permission[0],
       'startDate' => Carbon::now()->subMonth(2),
       'expirationDate' => Carbon::now()
           ->subMonth(1)
           ,
   ]);

//        $userPermission = $this->userPermissionRepository->create(
//            [
//                'user_id' => $user,
//                'permission_id' => $permission['id'],
//                'start_date' => Carbon::now()
//                    ->subMonth(2)
//                    ->toDateTimeString(),
//                'expiration_date' => Carbon::now()
//                    ->subMonth(1)
//                    ->toDateTimeString(),
//                'created_on' => Carbon::now()
//                    ->subMonth(2)
//                    ->toDateTimeString(),
//            ]
//        );

        $response = $this->call(
            'GET',
            'railcontent/content/get-by-ids',
            ['ids' => $contents[0]->getId(). ',' . $contents[1]->getId()]
        );
       // $expectedResults = [(array)$content2];
dd($response);
       // $this->assertEquals($expectedResults, $response->decodeResponseJson('data'));
    }

}