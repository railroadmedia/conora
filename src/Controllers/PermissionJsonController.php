<?php

namespace Railroad\Railcontent\Controllers;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Illuminate\Routing\Controller;
use Railroad\DoctrineArrayHydrator\JsonApiHydrator;
use Railroad\Permissions\Exceptions\NotAllowedException;
use Railroad\Permissions\Services\PermissionService;
use Railroad\Railcontent\Entities\Content;
use Railroad\Railcontent\Entities\Permission;
use Railroad\Railcontent\Exceptions\NotFoundException;
use Railroad\Railcontent\Managers\RailcontentEntityManager;
use Railroad\Railcontent\Repositories\PermissionRepository;
use Railroad\Railcontent\Requests\PermissionAssignRequest;
use Railroad\Railcontent\Requests\PermissionDissociateRequest;
use Railroad\Railcontent\Requests\PermissionRequest;
use Railroad\Railcontent\Services\ContentPermissionService;
use Railroad\Railcontent\Services\ResponseService;
use ReflectionException;
use Spatie\Fractal\Fractal;
use Throwable;

/**
 * Class PermissionController
 *
 * @package Railroad\Railcontent\Controllers
 */
class PermissionJsonController extends Controller
{
    /**
     * @var RailcontentEntityManager
     */
    private $entityManager;

    /**
     * @var PermissionRepository
     */
    private $permissionRepository;

    /**
     * @var ContentPermissionService
     */
    private $contentPermissionService;

    /**
     * @var PermissionService
     */
    private $permissionPackageService;

    /**
     * @var JsonApiHydrator
     */
    private $jsonApiHydrator;

    /**
     * PermissionJsonController constructor.
     *
     * @param RailcontentEntityManager $entityManager
     * @param ContentPermissionService $contentPermissionService
     * @param PermissionService $permissionPackageService
     * @param JsonApiHydrator $jsonApiHydrator
     */
    public function __construct(
        RailcontentEntityManager $entityManager,
        ContentPermissionService $contentPermissionService,
        PermissionService $permissionPackageService,
        JsonApiHydrator $jsonApiHydrator
    ) {
        $this->contentPermissionService = $contentPermissionService;
        $this->permissionPackageService = $permissionPackageService;
        $this->jsonApiHydrator = $jsonApiHydrator;

        $this->entityManager = $entityManager;
        $this->permissionRepository = $this->entityManager->getRepository(Permission::class);

        $this->middleware(config('railcontent.controller_middleware'));
    }

    /**
     * Create a new permission and return it in JSON format
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index()
    {
        $this->permissionPackageService->canOrThrow(auth()->id(), 'pull.permissions');

        $permissions = $this->permissionRepository->findAll();

        return ResponseService::permission($permissions);
    }

    /**
     * Create a new permission and return it in JSON format
     *
     * @param PermissionRequest $request
     * @return Fractal
     * @throws DBALException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws NotAllowedException
     * @throws ReflectionException
     */
    public function store(PermissionRequest $request)
    {
        $this->permissionPackageService->canOrThrow(auth()->id(), 'create.permission');

        $permission = new Permission();

        $this->jsonApiHydrator->hydrate($permission, $request->onlyAllowed());

        $this->entityManager->persist($permission);
        $this->entityManager->flush();

        $this->entityManager->getCache()
            ->evictEntityRegion(Permission::class);

        return ResponseService::permission($permission);
    }

    /**
     * Update a permission if exist and return it in JSON format
     *
     * @param integer $id
     * @param PermissionRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function update($id, PermissionRequest $request)
    {
        $this->permissionPackageService->canOrThrow(auth()->id(), 'update.permission');

        $permission = $this->permissionRepository->find($id);

        throw_unless(
            $permission,
            new NotFoundException('Update failed, permission not found with id: ' . $id)
        );

        $this->jsonApiHydrator->hydrate($permission, $request->onlyAllowed());

        $this->entityManager->flush();

        $this->entityManager->getCache()
            ->evictEntity(Permission::class, $id);

        return ResponseService::permission($permission)
            ->respond(201);
    }

    /**
     * Delete a permission if exist and it's not linked with content id or content type
     *
     * @param integer $id
     * @return JsonResponse
     * @throws Throwable
     */
    public function delete($id)
    {
        $this->permissionPackageService->canOrThrow(auth()->id(), 'delete.permission');
        $permission = $this->permissionRepository->find($id);

        throw_unless(
            $permission,
            new NotFoundException('Delete failed, permission not found with id: ' . $id)
        );

        $contentPermissions = $this->contentPermissionService->getByPermission($id);
        foreach ($contentPermissions as $contentPermission) {
            $this->entityManager->remove($contentPermission);
        }

        $this->entityManager->remove($permission);
        $this->entityManager->flush();

        $this->entityManager->getCache()
            ->evictEntity(Permission::class, $id);

        $this->entityManager->getCache()
            ->evictEntityRegion(Content::class);

        return ResponseService::empty(204);
    }

    /**
     * Attach permission to a specific content or to all content of a certain type
     *
     * @param PermissionAssignRequest $request
     * @return JsonResponse
     */
    public function assign(PermissionAssignRequest $request)
    {
        $this->permissionPackageService->canOrThrow(auth()->id(), 'assign.permission');

        $assignedPermission = $this->contentPermissionService->create(
            $request->input('data.relationships.content.data.id'),
            $request->input('data.attributes.content_type'),
            $request->input('data.relationships.permission.data.id')
        );
        return ResponseService::contentPermission($assignedPermission);
    }

    /**
     * Dissociate ("unattach") permissions from a specific content or all content of a certain type
     *
     * @param PermissionDissociateRequest $request
     * @return JsonResponse
     */
    public function dissociate(PermissionDissociateRequest $request)
    {
        $this->permissionPackageService->canOrThrow(auth()->id(), 'disociate.permissions');

        $dissociate = $this->contentPermissionService->dissociate(
            $request->input('data.relationships.content.data.id'),
            $request->input('data.attributes.content_type'),
            $request->input('data.relationships.permission.data.id')
        );
        return ResponseService::empty(200);
    }
}
