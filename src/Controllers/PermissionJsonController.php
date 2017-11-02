<?php

namespace Railroad\Railcontent\Controllers;

use Illuminate\Routing\Controller;
use Railroad\Railcontent\Requests\PermissionAssignRequest;
use Railroad\Railcontent\Requests\PermissionRequest;
use Railroad\Railcontent\Services\ContentPermissionService;
use Railroad\Railcontent\Services\PermissionService;

/**
 * Class PermissionController
 *
 * @package Railroad\Railcontent\Controllers
 */
class PermissionJsonController extends Controller
{
    /**
     * @var PermissionService
     */
    private $permissionService;
    /**
     * @var ContentPermissionService
     */
    private $contentPermissionService;

    /**
     * PermissionController constructor.
     *
     * @param PermissionService $permissionService
     * @param ContentPermissionService $contentPermissionService
     */
    public function __construct(
        PermissionService $permissionService,
        ContentPermissionService $contentPermissionService
    ) {
        $this->permissionService = $permissionService;
        $this->contentPermissionService = $contentPermissionService;
    }

    /**
     * Create a new permission and return it in JSON format
     *
     * @param PermissionRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(PermissionRequest $request)
    {
        $permission = $this->permissionService->create($request->input('name'));

        return response()->json($permission, 200);
    }

    /**
     * Update a permission if exist and return it in JSON format
     *
     * @param integer $id
     * @param PermissionRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id, PermissionRequest $request)
    {
        //check if permission exist in the database
        $permission = $this->permissionService->get($id);

        if (is_null($permission)) {
            return response()->json('Update failed, permission not found with id: ' . $id, 404);
        }

        $permission = $this->permissionService->update($id, $request->input('name'));

        return response()->json($permission, 201);
    }

    /**
     * Delete a permission if exist and it's not linked with content id or content type
     *
     * @param integer $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id)
    {
        //check if permission exist in the database
        $permission = $this->permissionService->get($id);

        if (is_null($permission)) {
            return response()->json('Delete failed, permission not found with id: ' . $id, 404);
        }

        //check if exist contents attached to the permission
//        $linkedWithContent = $this->permissionService->linkedWithContent($id);
//
//        if ($linkedWithContent->isNotEmpty()) {
//            $ids = $linkedWithContent->implode('content_id', ', ');
//            $types = $linkedWithContent->implode('content_type', ', ');
//            $message = '';
//            if ($ids != '') {
//                $message .= ' content(' . $ids . ')';
//            }
//            if ($types != '') {
//                $message .= ' content types(' . $types . ')';
//            }
//
//            return response()->json(
//                'This permission is being referenced by other' .
//                $message .
//                ', you must delete that reference first.',
//                404
//            );
//        }

        $deleted = $this->permissionService->delete($id);

        return response()->json($deleted, 200);
    }

    /**
     * Attach permission to a specific content or to all content of a certain type
     *
     * @param PermissionAssignRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function assign(PermissionAssignRequest $request)
    {
        $assignedPermission = $this->contentPermissionService->create(
            $request->input('content_id'),
            $request->input('content_type'),
            $request->input('permission_id')
        );

        return response()->json($assignedPermission, 200);
    }
}
