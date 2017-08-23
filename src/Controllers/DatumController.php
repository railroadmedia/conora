<?php

namespace Railroad\Railcontent\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Railroad\Railcontent\Requests\DatumRequest;
use Railroad\Railcontent\Services\ConfigService;
use Railroad\Railcontent\Services\DatumService;

class DatumController extends Controller
{
    private $datumService;

    public function __construct(DatumService $datumService)
    {
        $this->datumService = $datumService;
    }

    /**
     * Call the method from service that create new data and link the content with the data.
     * @param DatumRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(DatumRequest $request)
    {
        $categoryData = $this->datumService->createDatum(
            $request->input('content_id'),
            null,
            $request->input('key'),
            $request->input('value'),
            $request->input('position')
        );

        return response()->json($categoryData, 200);
    }

    /**
     * Call the method from service to update a content datum
     * @param integer $dataId
     * @param DatumRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($dataId, DatumRequest $request)
    {
        $datum = $this->datumService->getDatum($dataId,  $request->input('content_id'));

        if (is_null($datum)) {
            return response()->json('Update failed, datum not found with id: ' . $dataId, 404);
        }

        $categoryData = $this->datumService->updateDatum(
            $request->input('content_id'),
            $dataId,
            $request->input('key'),
            $request->input('value'),
            $request->input('position')
        );

        return response()->json($categoryData, 201);
    }

    /**
     * Call the method from service to delete the content data
     * @param integer $dataId
     * @param Request $request
     */
    public function delete($dataId,Request $request)
    {
        $deleted = $this->datumService->deleteDatum(
            $dataId,
            $request->input('content_id')
        );

        if (!$deleted) {
            return response()->json('Delete failed, datum not found with id: ' . $dataId, 404);
        }

        return response()->json($deleted,200);
    }
}