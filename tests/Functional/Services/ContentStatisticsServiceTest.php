<?php

namespace Railroad\Railcontent\Tests\Functional\Services;

use Carbon\Carbon;
use Railroad\Railcontent\Helpers\ContentHelper;
use Railroad\Railcontent\Services\ConfigService;
use Railroad\Railcontent\Repositories\CommentRepository;
use Railroad\Railcontent\Repositories\ContentHierarchyRepository;
use Railroad\Railcontent\Repositories\ContentLikeRepository;
use Railroad\Railcontent\Repositories\ContentRepository;
use Railroad\Railcontent\Repositories\UserContentProgressRepository;
use Railroad\Railcontent\Services\ContentService;
use Railroad\Railcontent\Services\ContentStatisticsService;
use Railroad\Railcontent\Services\UserContentProgressService;
use Railroad\Railcontent\Tests\RailcontentTestCase;

class ContentStatisticsServiceTest extends RailcontentTestCase
{
    /**
     * @var CommentRepository
     */
    protected $commentRepository;

    /**
     * @var ContentHierarchyRepository
     */
    protected $contentHierarchyRepository;

    /**
     * @var ContentLikeRepository
     */
    protected $contentLikeRepository;

    /**
     * @var ContentRepository
     */
    protected $contentRepository;

    /**
     * @var UserContentProgressRepository
     */
    protected $userContentRepository;

    protected function setUp()
    {
        parent::setUp();

        $this->commentRepository = $this->app->make(CommentRepository::class);
        $this->contentHierarchyRepository = $this->app->make(ContentHierarchyRepository::class);
        $this->contentLikeRepository = $this->app->make(ContentLikeRepository::class);
        $this->contentRepository = $this->app->make(ContentRepository::class);
        $this->userContentRepository = $this->app->make(UserContentProgressRepository::class);
    }

    /*
    public function test_get_content_statistics_intervals()
    {
        $this->contentStatisticsService = $this->app->make(ContentStatisticsService::class);

        $expectedFirstIntervalStartDay = Carbon::parse('2020-01-26'); // Sunday
        $smallDate = $expectedFirstIntervalStartDay->copy()->addDays(3);

        $expectedLastIntervalDay = Carbon::parse('2020-02-22'); // Saturday
        $bigDate = $expectedLastIntervalDay->copy()->subDays(2);

        $intervals = $this->contentStatisticsService->getContentStatisticsIntervals($smallDate, $bigDate);

        $firstInterval = $intervals[0];

        // assert first interval start day is the expected Sunday
        $this->assertEquals($expectedFirstIntervalStartDay, $firstInterval['start']);

        // assert first interval end day is next Saturday
        $this->assertEquals($expectedFirstIntervalStartDay->copy()->addDays(6), $firstInterval['end']);

        // assert first interval week number
        $this->assertEquals($expectedFirstIntervalStartDay->copy()->addDays(6)->weekOfYear, $firstInterval['week']);

        $lastInterval = $intervals[count($intervals) - 1];

        // assert last interval start day is the expected Sunday
        $this->assertEquals($expectedLastIntervalDay->copy()->subDays(6), $lastInterval['start']);

        // assert last interval end day is next Saturday
        $this->assertEquals($expectedLastIntervalDay, $lastInterval['end']);

        // assert last interval week number
        $this->assertEquals($expectedLastIntervalDay->weekOfYear, $lastInterval['week']);

        $totalDays = $expectedLastIntervalDay->dayOfYear - $expectedFirstIntervalStartDay->dayOfYear + 1;

        // assert intervals count
        $this->assertEquals(
            (int)($totalDays / 7),
            count($intervals)
        );
    }
    */

    public function test_compute_content_statistics()
    {
        $this->contentStatisticsService = $this->app->make(ContentStatisticsService::class);

        // random date, between 16 and 30 days ago
        $testSmallDate = Carbon::now()->subDays($this->faker->numberBetween(16, 30));

        // random date, between 5 and 15 days ago
        $testBigDate = Carbon::now()->subDays($this->faker->numberBetween(5, 15));

        // add content
        $contentIds = [];

        for ($i=0; $i < 10; $i++) {
            $content = $this->addContent(
                $this->faker->randomElement(ConfigService::$statisticsContentTypes),
                ContentService::STATUS_PUBLISHED,
                Carbon::now()->subDays($this->faker->numberBetween(60, 90))
            );
            $contentIds[] = $content['id'];
        }

        // add progress complete
        for ($i=0; $i < 50; $i++) {

            $contentId = $this->faker->randomElement($contentIds);

            // user content progress date may be a little out of the test interval
            $updatedOn = Carbon::now()->subDays($this->faker->numberBetween(2, 35));

            $userContentProgress = $this->addUserContentProgress(
                $contentId,
                UserContentProgressService::STATE_COMPLETED,
                $updatedOn
            );

            if (
                $updatedOn >= $testSmallDate
                && $updatedOn <= $testBigDate
            ) {
                // todo - add to expected bucket
            }
        }

        // add progress started
        for ($i=0; $i < 50; $i++) {

            $contentId = $this->faker->randomElement($contentIds);

            // user content progress date may be a little out of the test interval
            $updatedOn = Carbon::now()->subDays($this->faker->numberBetween(2, 35));

            $userContentProgress = $this->addUserContentProgress(
                $contentId,
                UserContentProgressService::STATE_STARTED,
                $updatedOn
            );

            if (
                $updatedOn >= $testSmallDate
                && $updatedOn <= $testBigDate
            ) {
                // todo - add to expected bucket
            }
        }

        // add comments
        for ($i=0; $i < 50; $i++) {

            // increased chance to add comment to test content id
            $contentId = $this->faker->randomElement($contentIds);

            // comment date may be a little out of the test interval
            $createdOn = Carbon::now()->subDays($this->faker->numberBetween(2, 35));

            $comment = $this->addContentComment($contentId, $createdOn);

            if (
                $createdOn >= $testSmallDate
                && $createdOn <= $testBigDate
            ) {
                // todo - add to expected bucket
            }
        }

        // add likes
        for ($i=0; $i < 50; $i++) {

            $contentId = $this->faker->randomElement($contentIds);

            // like date may be a little out of the test interval
            $createdOn = Carbon::now()->subDays($this->faker->numberBetween(2, 35));

            $like = $this->addContentLike($contentId, $createdOn);

            if (
                $createdOn >= $testSmallDate
                && $createdOn <= $testBigDate
            ) {
                // todo - add to expected bucket
            }
        }

        // add to lists
        for ($i=0; $i < 50; $i++) {

            $contentId = $this->faker->randomElement($contentIds);

            // add to lists date may be a little out of the test interval
            $createdOn = Carbon::now()->subDays($this->faker->numberBetween(2, 35));

            $addToList = $this->addContentToList($contentId, $createdOn);

            if (
                $createdOn >= $testSmallDate
                && $createdOn <= $testBigDate
            ) {
                // todo - add to expected bucket
            }
        }

        $this->contentStatisticsService->computeContentStatistics($testSmallDate, $testBigDate);

        $this->assertTrue(true);

        // todo - assert expected bucket elements exist in content statistics table
    }

    protected function addContent($contentType, $contentStatus, $contentCreatedOn = null)
    {
        // ContentFactory does not allow to specify the content created_on field

        $id = $this->contentRepository->create(
            [
                'slug' => ContentHelper::slugify($this->faker->words(rand(2, 6), true)),
                'type' => $contentType,
                'sort' => 0,
                'status' => $contentStatus,
                'language' => 'en-US',
                'brand' => ConfigService::$brand,
                'total_xp' => null,
                'user_id' => null,
                'published_on' => $contentCreatedOn ?? Carbon::now()
                    ->toDateTimeString(),
                'created_on' => $contentCreatedOn ?? Carbon::now()
                    ->toDateTimeString(),
            ]
        );

        return $this->contentRepository->getById($id);
    }

    protected function addUserContentProgress($contentId, $state, $updatedOn = null)
    {
        // UserContentProgressFactory does not allow to specify the UserContentProgress updated_on field

        $progressPercent = $state == UserContentProgressService::STATE_COMPLETED ? 100 : 50;

        if (!$updatedOn) {
            $updatedOn = Carbon::now();
        }

        $updatedOn = $updatedOn->toDateTimeString();

        $this->userContentRepository->create(
            [
                'content_id' => $contentId,
                'user_id' => rand(),
                'state' => $state,
                'progress_percent' => $progressPercent,
                'updated_on' => $updatedOn,
            ]
        );
    }

    protected function addContentComment($contentId, $createdOn = null)
    {
        // CommentFactory does not allow to specify the comment created_on field

        if (!$createdOn) {
            $createdOn = Carbon::now();
        }

        $createdOn = $createdOn->toDateTimeString();

        $this->commentRepository->create(
            [
                'content_id' => $contentId,
                'user_id' => rand(),
                'comment' => $this->faker->word,
                'temporary_display_name' => $this->faker->word,
                'created_on' => $createdOn,
            ]
        );
    }

    protected function addContentLike($contentId, $createdOn = null)
    {
        if (!$createdOn) {
            $createdOn = Carbon::now();
        }

        $createdOn = $createdOn->toDateTimeString();

        $this->contentLikeRepository->create(
            [
                'content_id' => $contentId,
                'user_id' => rand(),
                'created_on' => $createdOn,
            ]
        );
    }

    protected function addContentToList($contentId, $createdOn = null)
    {
        if (!$createdOn) {
            $createdOn = Carbon::now();
        }

        $createdOn = $createdOn->toDateTimeString();

        $contentUserPlaylist = $this->addContent(
            'user-playlist',
            ContentService::STATUS_PUBLISHED,
            Carbon::now()->subDays($this->faker->numberBetween(60, 90))
        );

        $this->contentHierarchyRepository->create(
            [
                'parent_id' => $contentUserPlaylist['id'],
                'child_id' => $contentId,
                'child_position' => 0,
                'created_on' => $createdOn,
            ]
        );
    }
}
