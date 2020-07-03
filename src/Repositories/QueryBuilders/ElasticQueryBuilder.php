<?php

namespace Railroad\Railcontent\Repositories\QueryBuilders;

use Elastica\Query;
use Elastica\Query\FunctionScore;
use Elastica\Query\Terms;
use Railroad\Railcontent\Contracts\UserProviderInterface;
use Railroad\Railcontent\Entities\Content;
use Railroad\Railcontent\Entities\ContentPermission;
use Railroad\Railcontent\Entities\UserContentProgress;
use Railroad\Railcontent\Entities\UserPermission;
use Railroad\Railcontent\Repositories\ContentRepository;

class ElasticQueryBuilder extends \Elastica\Query
{
    /**
     * @param array $slugHierarchy
     * @return $this
     */
    public function restrictBySlugHierarchy(array $slugHierarchy)
    {
        if (empty($slugHierarchy)) {
            return $this;
        }

        $query = ($this->hasParam('query')) ? $this->getQuery() : new Query\BoolQuery();
        $termsQuery = new Terms('parent_slug', $slugHierarchy);

        $query->addMust($termsQuery);

        $this->setQuery($query);

        return $this;
    }

    /**
     * @return $this
     */
    public function restrictStatuses()
    {
        if (is_array(ContentRepository::$availableContentStatues)) {
            $query = ($this->hasParam('query')) ? $this->getQuery() : new Query\BoolQuery();
            $termsQuery = new Terms('status', ContentRepository::$availableContentStatues);

            $query->addMust($termsQuery);

            $this->setQuery($query);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function restrictPublishedOnDate()
    {
        $query = ($this->hasParam('query')) ? $this->getQuery() : new Query\BoolQuery();

        if (!ContentRepository::$pullFutureContent) {

            $range = new \Elastica\Query\Range();
            $range->addField('published_on.date', ['lte' => 'now']);

            $query->addMust($range);

            $this->setQuery($query);
        }

        if (ContentRepository::$getFutureContentOnly) {
            $range = new \Elastica\Query\Range();
            $range->addField('published_on.date', ['gt' => 'now']);

            $query->addMust($range);

            $this->setQuery($query);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function restrictBrand()
    {
        $query = ($this->hasParam('query')) ? $this->getQuery() : new Query\BoolQuery();
        $termsQuery = new Terms('brand', array_values(array_wrap(config('railcontent.available_brands'))));

        $query->addMust($termsQuery);

        $this->setQuery($query);

        return $this;
    }

    /**
     * @param array $typesToInclude
     * @return $this
     */
    public function restrictByTypes(array $typesToInclude)
    {
        if (!empty($typesToInclude)) {
            $query = ($this->hasParam('query')) ? $this->getQuery() : new Query\BoolQuery();
            $termsQuery = new Terms('content_type', $typesToInclude);

            $query->addMust($termsQuery);

            $this->setQuery($query);
        }

        return $this;
    }

    /**
     * @param array $parentIds
     * @return $this
     */
    public function restrictByParentIds(array $parentIds)
    {
        if (empty($parentIds)) {
            return $this;
        }

        $query = ($this->hasParam('query')) ? $this->getQuery() : new Query\BoolQuery();
        $termsQuery = new Terms('parent_id', $parentIds);

        $query->addMust($termsQuery);

        $this->setQuery($query);

        return $this;
    }

    /**
     * @param array $requiredUserStates
     * @return $this
     */
    public function restrictByUserStates(array $requiredUserStates, $client)
    {
        if (empty($requiredUserStates)) {
            return $this;
        }

        $userId = auth()->id();

        $progressIndex = $client->getIndex('progress');
        $queryBuilder = new Query();
        $query = new Query\BoolQuery();
        $termsQuery = new Terms('user_id', [$userId]);

        $query->addMust($termsQuery);

        $termsQuery = new Terms('state', $requiredUserStates);

        $query->addMust($termsQuery);
        $queryBuilder->setQuery($query)
            ->setSize(1000)
            ->setFrom(0);

        $contentIds = [];
        foreach (
            $progressIndex->search($queryBuilder)
                ->getResults() as $prog
        ) {
            $contentIds[] = $prog->getData()['content_id'];
        }

        if (empty($contentIds)) {
            return $this;
        }

        $query = ($this->hasParam('query')) ? $this->getQuery() : new Query\BoolQuery();
        $termsQuery = new Terms('id', $contentIds);

        $query->addMust($termsQuery);

        $this->setQuery($query);

        return $this;
    }

    /**
     * @param array $includedUserStates
     * @return $this
     */
    public function includeByUserStates(array $includedUserStates, $client)
    {
        //TODO
        if (empty($includedUserStates)) {
            return $this;
        }

        $userId = auth()->id();
        //$userId = 149628;

        $progressIndex = $client->getIndex('progress');
        $queryBuilder = new Query();
        $query = new Query\BoolQuery();
        $termsQuery = new Terms('user_id', [$userId]);

        $query->addMust($termsQuery);

        $termsQuery = new Terms('state', $includedUserStates);

        $query->addShould($termsQuery);
        $queryBuilder->setQuery($query)
            ->setSize(1000)
            ->setFrom(0);

        $contentIds = [];
        foreach (
            $progressIndex->search($queryBuilder)
                ->getResults() as $prog
        ) {
            $contentIds[] = $prog->getData()['content_id'];
        }

        if (empty($contentIds)) {
            return $this;
        }

        $query = ($this->hasParam('query')) ? $this->getQuery() : new Query\BoolQuery();
        $termsQuery = new Terms('id', $contentIds);

        $query->addMust($termsQuery);

        $this->setQuery($query);

        return $this;
    }

    /**
     * @param array $requiredFields
     * @return $this
     */
    public function restrictByFields(array $requiredFields)
    {
        if (empty($requiredFields)) {
            return $this;
        }

        $query = ($this->hasParam('query')) ? $this->getQuery() : new Query\BoolQuery();

        foreach ($requiredFields as $index => $requiredFieldData) {

            $termsQuery = new Terms($requiredFieldData['name'], [strtolower($requiredFieldData['value'])]);

            $query->addFilter($termsQuery);
        }

        $this->setQuery($query);

        return $this;
    }

    /**
     * @param array $includedFields
     * @return $this
     */
    public function includeByFields(array $includedFields)
    {
        //TODO
        if (empty($includedFields)) {
            return $this;
        }
        $query = ($this->hasParam('query')) ? $this->getQuery() : new Query\BoolQuery();

        foreach ($includedFields as $index => $includedFieldData) {

            $termsQuery = new Terms($includedFieldData['name'], [strtolower($includedFieldData['value'])]);

            $query->addShould($termsQuery);
        }

        $this->setQuery($query);

        return $this;
    }

    /**
     * @return $this
     */
    public function restrictByPermissions()
    {

        if (ContentRepository::$bypassPermissions === true) {
            return $this;
        }

        $currentUserActivePermissions = [1, 131];

        $query = ($this->hasParam('query')) ? $this->getQuery() : new Query\BoolQuery();

        $exists = new Query\Exists('permission_ids');

        $nullPermissionsQuery = new Query\BoolQuery();
        $mustNot = $nullPermissionsQuery->addMustNot($exists);

        $termsQuery = new Terms('permission_ids', $currentUserActivePermissions);
        $query3 = new Query\BoolQuery();
        $query3->addShould($termsQuery)
            ->addShould($mustNot);
        $query->addMust($query3);
        $this->setQuery($query);

        return $this;
    }

    /**
     * @return $this
     */
    public function restrictByUserAccess()
    {
        $this->restrictPublishedOnDate()
            ->restrictStatuses()
            ->restrictBrand()
            ->restrictByPermissions();

        return $this;
    }

    /**
     * @param array $userPlaylistIds
     * @return $this
     */
    public function restrictByPlaylistIds(array $userPlaylistIds)
    {
        if (empty($userPlaylistIds)) {
            return $this;
        }

        $query = ($this->hasParam('query')) ? $this->getQuery() : new Query\BoolQuery();
        $termsQuery = new Terms('playlist_ids', $userPlaylistIds);

        $query->addMust($termsQuery);

        $this->setQuery($query);

        return $this;
    }

    /**
     * @param $orderBy
     * @return $this
     */
    public function sortResults($orderBy)
    {
        switch ($orderBy) {
            case 'newest':
                $this->addSort(
                    [
                        'published_on' => [
                            'order' => 'desc',
                            'unmapped_type' => 'date',
                        ],
                    ]
                );
                break;
            case 'oldest':
                $this->addSort(
                    [
                        'published_on' => [
                            'order' => 'asc',
                            'unmapped_type' => 'date',
                        ],
                    ]
                );

                break;
            case 'popularity':
                $this->addSort(
                    [
                        'all_progress_count' => [
                            'order' => 'desc',
                        ],
                    ]
                );

                break;

            case 'trending':
                $this->addSort(
                    [
                        'last_week_progress_count' => [
                            'order' => 'desc',
                        ],
                    ]
                );

                break;
            case 'relevance':
                //difficulty and topics will be defined on user
                $userDifficulty = 2;
                $userTopics = ['Fills'];

                $contentTypeFilter = new Query\BoolQuery();

                $difficulty = new Terms('difficulty');
                $difficulty->setTerms([$userDifficulty, 'All Skill Levels']);

                $topics = new Terms('topics');
                $topics->setTerms($userTopics);

                $contentTypeFilter->addMust($difficulty)
                    ->addMust($topics);

                $contentTypeFilter2 = new Query\BoolQuery();
                $contentTypeFilter2->addShould($difficulty)
                    ->addShould($topics);

                $query = new FunctionScore();
                $query->setParam('query', $this->getQuery());

                //set different relevance
                $query->addWeightFunction(15.0, $contentTypeFilter);
                $query->addWeightFunction(10.0, $contentTypeFilter);
                $this->setQuery($query);
                $this->addSort(
                    [
                        '_score' => [
                            'order' => 'desc',
                        ],
                    ]
                );

                break;
        }

        return $this;
    }

    public function fullSearchSort($term)
    {

        $contentTypeFilter = new Query\BoolQuery();

        $title = new Terms('title');
        $title->setTerms($term);

        $slug = new Terms('slug');
        $slug->setTerms($term);

        $contentTypeFilter->addMust($title)
            ->addMust($slug);

        $contentTypeFilter2 = new Query\BoolQuery();
        $contentTypeFilter2->addShould($title)
            ->addShould($slug);

        $query = new FunctionScore();
        $query->setParam('query', $this->getQuery());

        //set different relevance
        $query->addWeightFunction(15.0, $contentTypeFilter);
        $query->addWeightFunction(10.0, $contentTypeFilter2);

        $this->setQuery($query);
        $this->addSort(
            [
                '_score' => [
                    'order' => 'desc',
                ],
            ]
        );
        return $this;

    }

    public function restrictByTerm($arrTerms)
    {
        $searchableFields = ['title', 'slug', 'album', 'style'];
        $query = ($this->hasParam('query')) ? $this->getQuery() : new Query\BoolQuery();
        $queryFields = new Query\BoolQuery();
        foreach ($searchableFields as $field) {

            $title = new Terms($field);
            $title->setTerms($arrTerms);

            $queryFields->addShould($title);
        }

        $query->addMust($queryFields);
        $this->setQuery($query);

        return $this;
    }
}
