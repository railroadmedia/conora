<?php

namespace Railroad\Railcontent\Controllers;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Illuminate\Routing\Controller;
use Railroad\Railcontent\Contracts\UserProviderInterface;
use Railroad\Railcontent\Requests\UserContentRequest;
use Railroad\Railcontent\Services\ResponseService;
use Railroad\Railcontent\Services\UserContentProgressService;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ContentProgressJsonController
 *
 * @group User progress API
 *
 * @package Railroad\Railcontent\Controllers
 */
class ContentProgressJsonController extends Controller
{
    /**
     * @var UserContentProgressService
     */
    private $userContentService;

    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    /**
     * ContentProgressJsonController constructor.
     *
     * @param UserContentProgressService $userContentService
     */
    public function __construct(
        UserContentProgressService $userContentService,
    UserProviderInterface $userProvider
    ) {
        $this->userContentService = $userContentService;
        $this->userProvider = $userProvider;
    }

    /** Start a content for the authenticated user
     *
     * @param UserContentRequest $request
     * @return \Illuminate\Http\JsonResponse|JsonResponse
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @permission Must be logged in
     */
    public function startContent(UserContentRequest $request)
    {
        $response = $this->userContentService->startContent(
            $request->input('data.relationships.content.data.id'),
            auth()->id()
        );

        return ResponseService::empty(200)
            ->setData(['data' => $response]);
    }

    /** Set content as complete for the authenticated user
     *
     * @param UserContentRequest $request
     * @return \Illuminate\Http\JsonResponse|JsonResponse
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @permission Must be logged in
     */
    public function completeContent(UserContentRequest $request)
    {

         $response = $this->userContentService->completeContent(
            $request->input('data.relationships.content.data.id'),
            auth()->id()
        );

        return ResponseService::empty(201)
            ->setData(['data' => $response]);
    }

    /** Reset content progress for authenticated user
     *
     * @param UserContentRequest $request
     * @return \Illuminate\Http\JsonResponse|JsonResponse
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @permission Must be logged in
     */
    public function resetContent(UserContentRequest $request)
    {
        $response = $this->userContentService->resetContent(
            $request->input('data.relationships.content.data.id'),
            auth()->id()
        );

        return ResponseService::empty(201)
            ->setData(['data' => $response]);
    }

    /** Save the progress on a content for the authenticated user
     *
     * @param UserContentRequest $request
     * @return \Illuminate\Http\JsonResponse|JsonResponse
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     *
     * @permission Must be logged in
     * @bodyParam data.attributes.progress_percent integer required Progress percent. Example:10
     */
    public function saveProgress(UserContentRequest $request)
    {
        $response = $this->userContentService->saveContentProgress(
            $request->input('data.relationships.content.data.id'),
            $request->input('data.attributes.progress_percent'),
            auth()->id()
        );
        return ResponseService::empty(201)
            ->setData(['data' => $response]);
    }
}