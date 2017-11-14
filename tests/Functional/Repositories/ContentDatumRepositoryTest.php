<?php

namespace Railroad\Railcontent\Tests\Functional\Repositories;

use Railroad\Railcontent\Repositories\ContentDatumRepository;
use Railroad\Railcontent\Services\ConfigService;
use Railroad\Railcontent\Tests\RailcontentTestCase;

class ContentDatumRepositoryTest extends RailcontentTestCase
{
    /**
     * @var ContentDatumRepository
     */
    protected $classBeingTested;

    protected function setUp()
    {
        parent::setUp();

        $this->classBeingTested = $this->app->make(ContentDatumRepository::class);
    }

    public function test_get_by_id()
    {
        $contentId = rand();
        $key = $this->faker->word;
        $value = $this->faker->text();
        $position = rand();

        $result = $this->classBeingTested->getById(
            $this->classBeingTested->create(
                [
                    'content_id' => $contentId,
                    'key' => $key,
                    'value' => $value,
                    'position' => $position
                ]
            )
        );

        $this->assertEquals(
            [
                'id' => 1,
                'content_id' => $contentId,
                'key' => $key,
                'value' => $value,
                'position' => $position
            ],
            $result
        );
    }

    public function test_get_by_content_id_empty()
    {
        $response = $this->classBeingTested->getByContentId(rand());

        $this->assertEquals(
            [],
            $response
        );
    }

    public function test_get_by_content_id()
    {
        $contentId = rand();
        $expectedData = [];

        for ($i = 0; $i < 3; $i++) {
            $data = [
                'content_id' => $contentId,
                'key' => $this->faker->word,
                'value' => $this->faker->word,
                'position' => rand()
            ];

            $data['id'] = $this->classBeingTested->create($data);

            $expectedData[] = $data;
        }

        // random data that shouldn't be returned
        for ($i = 0; $i < 3; $i++) {
            $data = [
                'content_id' => rand(),
                'key' => $this->faker->word,
                'value' => $this->faker->word,
                'position' => rand()
            ];

            $this->classBeingTested->create($data);
        }

        $response = $this->classBeingTested->getByContentId($contentId);

        $this->assertEquals(
            $expectedData,
            $response
        );
    }

    public function test_get_by_content_ids()
    {
        $expectedData = [];

        for ($i = 0; $i < 3; $i++) {
            $data = [
                'content_id' => $i + 1,
                'key' => $this->faker->word,
                'value' => $this->faker->word,
                'position' => rand()
            ];

            $data['id'] = $this->classBeingTested->create($data);

            $expectedData[] = $data;
        }

        // random data that shouldn't be returned
        for ($i = 0; $i < 3; $i++) {
            $data = [
                'content_id' => rand(),
                'key' => $this->faker->word,
                'value' => $this->faker->word,
                'position' => rand()
            ];

            $this->classBeingTested->create($data);
        }

        $response = $this->classBeingTested->getByContentIds([1, 2, 3]);

        $this->assertEquals(
            $expectedData,
            $response
        );
    }

    public function test_create()
    {
        $contentId = rand();
        $key = $this->faker->word;
        $value = $this->faker->text();
        $position = rand();

        $result = $this->classBeingTested->create(
            [
                'content_id' => $contentId,
                'key' => $key,
                'value' => $value,
                'position' => $position
            ]
        );

        $this->assertEquals(1, $result);

        $this->assertDatabaseHas(
            ConfigService::$tableContentData,
            [
                'content_id' => $contentId,
                'key' => $key,
                'value' => $value,
                'position' => $position
            ]
        );
    }

    public function test_update()
    {
        $oldData = [
            'content_id' => rand(),
            'key' => $this->faker->word,
            'value' => $this->faker->word,
            'position' => rand(),
        ];

        $newData = [
            'content_id' => rand(),
            'key' => $this->faker->word,
            'value' => $this->faker->word,
            'position' => rand(),
        ];

        $id = $this->query()->table(ConfigService::$tableContentData)->insertGetId($oldData);

        $result = $this->classBeingTested->update($id, $newData);

        $this->assertEquals(1, $result);

        $this->assertDatabaseHas(
            ConfigService::$tableContentData,
            [
                'content_id' => $newData['content_id'],
                'key' => $newData['key'],
                'value' => $newData['value'],
                'position' => $newData['position']
            ]
        );

    }

    public function test_delete()
    {
        $data = [
            'content_id' => rand(),
            'key' => $this->faker->word,
            'value' => $this->faker->word,
            'position' => rand(),
        ];

        $id = $this->query()->table(ConfigService::$tableContentData)->insertGetId($data);

        $deleted = $this->classBeingTested->delete($id);

        $this->assertTrue($deleted);

        $this->assertDatabaseMissing(
            ConfigService::$tableContentData,
            [
                'id' => $id,
            ]
        );
    }

    public function test_delete_content_data()
    {
        $contentId = rand();
        $expectedData = [];

        for ($i = 0; $i < 3; $i++) {
            $data = [
                'content_id' => $contentId,
                'key' => $this->faker->word,
                'value' => $this->faker->word,
                'position' => rand()
            ];

            $data['id'] = $this->classBeingTested->create($data);

            $expectedData[] = $data;
        }

        $this->classBeingTested->deleteByContentId($contentId);

        $this->assertDatabaseMissing(
            ConfigService::$tableContentData,
            [
                'content_id' => $contentId,
            ]
        );
    }
}