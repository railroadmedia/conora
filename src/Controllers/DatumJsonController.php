<?php

namespace Railroad\Railcontent\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Railroad\Railcontent\Events\ContentUpdated;
use Railroad\Railcontent\Events\DatumUpdate;
use Railroad\Railcontent\Requests\DatumRequest;
use Railroad\Railcontent\Services\DatumService;

class DatumJsonController extends Controller
{
    private $datumService;

    /**
     * DatumController constructor.
     *
     * @param DatumService $datumService
     */
    public function __construct(DatumService $datumService)
    {
        $this->datumService = $datumService;
    }

    /**
     * Call the method from service that create new data and link the content with the data.
     *
     * @param DatumRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(DatumRequest $request)
    {
        //save a content version before datum creation
        // todo: rename to DatumCreated (after save to db) or ContentCreation (before save to db)
//        event(new DatumUpdate($request->input('content_id')));

        $categoryData = $this->datumService->createDatum(
            $request->input('content_id'),
            $request->input('key'),
            $request->input('value'),
            $request->input('position')
        );

        return response()->json($categoryData, 200);
    }

    /**
     * Call the method from service to update a content datum
     *
     * @param integer $dataId
     * @param DatumRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($dataId, DatumRequest $request)
    {
        //check if datum exist in the database
        $datum = $this->datumService->getDatum($dataId, $request->input('content_id'));

        if (is_null($datum)) {
            return response()->json('Update failed, datum not found with id: ' . $dataId, 404);
        }

        //save a content version before datum update
        // todo: this should be after the datum is saved, or renamed to 'ContentUpdating' if its being triggered before the actual update
        event(new ContentUpdated($request->input('content_id')));

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
     *
     * @param integer $dataId
     * @param Request $request
     */
    public function delete($dataId, Request $request)
    {
        //check if datum exist in the database
        $datum = $this->datumService->getDatum($dataId, $request->input('content_id'));

        if (is_null($datum)) {
            return response()->json('Delete failed, datum not found with id: ' . $dataId, 404);
        }

        //save a content version before datum deletion
        // todo: this should be after the datum is deleted and renamed to DatumDeleted
        event(new ContentUpdated($request->input('content_id')));

        $deleted = $this->datumService->deleteDatum(
            $dataId,
            $request->input('content_id')
        );

        return response()->json($deleted, 200);
    }
}