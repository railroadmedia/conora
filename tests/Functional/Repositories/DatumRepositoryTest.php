<?php

namespace Railroad\Railcontent\Tests\Functional\Repositories;

use Carbon\Carbon;
use Railroad\Railcontent\Repositories\DatumRepository;
use Railroad\Railcontent\Tests\RailcontentTestCase;
use Railroad\Railcontent\Services\ConfigService;

class DatumRepositoryTest extends RailcontentTestCase
{
    protected $classBeingTested;

    protected function setUp()
    {
        parent::setUp();

        $this->classBeingTested = $this->app->make(DatumRepository::class);
    }

    public function test_insert_data()
    {
        $key = $this->faker->word;
        $value = $this->faker->text();
        $position = $this->faker->numberBetween();

        $result = $this->classBeingTested->updateOrCreateDatum(1,$key,$value, $position);

        $this->assertEquals(1, $result);

        $this->assertDatabaseHas(
            ConfigService::$tableData,
            [
                'id' => 1,
                'key' => $key,
                'value' => $value,
                'position' => $position
            ]
        );
    }

    public function test_update_data()
    {
        $data = [
            'key' => $this->faker->word,
            'value' => $this->faker->word,
            'position' => $this->faker->numberBetween()
        ];

        $dataId = $this->query()->table(ConfigService::$tableData)->insertGetId($data);

        $new_value = $this->faker->text();

        $result = $this->classBeingTested->updateOrCreateDatum($dataId,$data['key'],$new_value, $data['position']);

        $this->assertEquals(1, $result);

        //assert that old value not exist in the database
        $this->assertDatabaseMissing(
            ConfigService::$tableData,
            [
                'id' => $dataId,
                'key' => $data['key'],
                'value' => $data['value'],
                'position' => $data['position']
            ]
        );

        $this->assertDatabaseHas(
            ConfigService::$tableData,
            [
                'id' => $dataId,
                'key' => $data['key'],
                'value' => $new_value,
                'position' => $data['position']
            ]
        );
    }

    public function test_delete_data()
    {
        $data = [
            'key' => $this->faker->word,
            'value' => $this->faker->word,
            'position' => $this->faker->numberBetween()
        ];

        $dataId = $this->query()->table(ConfigService::$tableData)->insertGetId($data);

        $this->classBeingTested->deleteDatum($dataId);

        $this->assertDatabaseMissing(
            ConfigService::$tableData,
            [
                'id' => $dataId,
                'key' => $data['key'],
                'value' => $data['value'],
                'position' => $data['position']
            ]
        );
    }

    /**
     * @return \Illuminate\Database\Connection
     */
    public function query()
    {
        return $this->databaseManager->connection();
    }
}
