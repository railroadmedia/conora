<?php

namespace Railroad\Railcontent\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Railcontent\Requests\UserContentRequest;
use Railroad\Railcontent\Responses\JsonResponse;
use Railroad\Railcontent\Services\UserContentProgressService;

class ContentProgressJsonController extends Controller
{
    /**
     * @var UserContentProgressService
     */
    private $userContentService;

    /**
     * ContentProgressJsonController constructor.
     *
     * @param UserContentProgressService $userContentService
     */
    public function __construct(UserContentProgressService $userContentService)
    {
        $this->userContentService = $userContentService;
    }

    /** Start a content for the authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function startContent(UserContentRequest $request)
    {
        $response = $this->userContentService->startContent($request->input('content_id'), $request->user()->id);

        return new JsonResponse($response, 200);
    }

    /** Set content as complete for the authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function completeContent(UserContentRequest $request)
    {
        $response = $this->userContentService->completeContent($request->input('content_id'), $request->user()->id);

        return new JsonResponse($response, 201);
    }

    /** Save the progress on a content for the authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function saveProgress(UserContentRequest $request)
    {
        $response =
            $this->userContentService->saveContentProgress(
                $request->input('content_id'),
                $request->input('progress_percent'),
                $request->user()->id
            );

        return new JsonResponse($response, 201);
    }
}