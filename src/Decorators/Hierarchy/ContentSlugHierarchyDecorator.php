<?php

namespace Railroad\Railcontent\Decorators\Hierarchy;

use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Railroad\Railcontent\Repositories\ContentRepository;
use Railroad\Railcontent\Services\ConfigService;
use Railroad\Railcontent\Support\Collection;
use Railroad\Resora\Decorators\DecoratorInterface;
use Railroad\Resora\Queries\CachedQuery;

class ContentSlugHierarchyDecorator implements DecoratorInterface
{
    private $contentRepository;

    /**
     * ContentSlugHierarchyDecorator constructor.
     *
     * @param $contentRepository
     */
    public function __construct(ContentRepository $contentRepository)
    {
        $this->contentRepository = $contentRepository;
    }

    public function decorate($contentResults)
    {
	//TODO: integrate resora		
        return $contentResults;

        $query = DB::table(config('railcontent.table_prefix'). 'content' . ' as parent_content_0')
            ->whereIn('parent_content_0.id', $contentResults->pluck('id'));

        for ($i = 0; $i < config('railcontent.content_hierarchy_max_depth'); $i++) {

            $query->leftJoin(
                config('railcontent.table_prefix'). 'content_hierarchy' . ' as content_hierarchy_' . $i,
                'content_hierarchy_' . $i . '.child_id',
                '=',
                'parent_content_' . $i . '.id'
            )
                ->leftJoin(
                    config('railcontent.table_prefix'). 'content' . ' as parent_content_' . ($i + 1),
                    function (JoinClause $join) use ($i) {

                        $join->on(
                            'parent_content_' . ($i + 1) . '.id',
                            '=',
                            'content_hierarchy_' . $i . '.parent_id'
                        )
                            ->whereIn(
                                'parent_content_' . ($i + 1) . '.type',
                                config(
                                    'railcontent.content_hierarchy_decorator_allowed_types' . ''
                                )
                            );
                    }
                )
                ->addSelect('parent_content_0.id as id')
                ->addSelect('parent_content_' . ($i + 1) . '.id as parent_content_' . ($i + 1) . '.id')
                ->addSelect('parent_content_' . ($i + 1) . '.slug as parent_content_' . ($i + 1) . '.slug')
                ->addSelect('parent_content_' . ($i + 1) . '.type as parent_content_' . ($i + 1) . '.type');
        }

        $slugHierarchies = $query->get()->toArray();

        foreach ($contentResults as $contentIndex => $content) {
            foreach ($slugHierarchies as $slugHierarchy) {
                $slugHierarchy = (array)$slugHierarchy;
                for ($i = 1; $i < config('railcontent.content_hierarchy_max_depth'); $i++) {
                    if ($slugHierarchy['id'] == $content['id'] &&
                        !empty($slugHierarchy['parent_content_' . ($i) . '.slug'])) {

                        $contentResults
                        [$contentIndex]
                        ['parent_slug_hierarchy']
                        [$slugHierarchy['parent_content_' . ($i) . '.type']]
                        [$slugHierarchy['parent_content_1.id']] =
                            $slugHierarchy['parent_content_' . ($i) . '.slug'];
                    }
                }
            }
        }

        return $contentResults;
    }
}