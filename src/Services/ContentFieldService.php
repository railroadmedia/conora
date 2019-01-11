<?php

namespace Railroad\Railcontent\Services;

use Doctrine\ORM\EntityManager;
use Railroad\Railcontent\Entities\ContentField;
use Railroad\Railcontent\Events\ContentFieldCreated;
use Railroad\Railcontent\Events\ContentFieldDeleted;
use Railroad\Railcontent\Events\ContentFieldUpdated;
use Railroad\Railcontent\Helpers\CacheHelper;
use Railroad\Railcontent\Repositories\ContentFieldRepository;
use Railroad\Railcontent\Repositories\Traits\ByContentIdTrait;

class ContentFieldService
{
    private $entityManager;
    /**
     * @var ContentFieldRepository
     */
    private $fieldRepository;

    /**
     * @var ContentService
     */
    private $contentService;

    /**
     * FieldService constructor.
     *
     * @param ContentFieldRepository $fieldRepository
     * @param ContentService $contentService
     */
    public function __construct(EntityManager $entityManager,
        ContentService $contentService)
    {
        $this->entityManager = $entityManager;

        $this->fieldRepository = $this->entityManager->getRepository(ContentField::class);

       // $this->fieldRepository = $fieldRepository;
        $this->contentService = $contentService;
    }

    /**
     * @param integer $id
     * @return array
     */
    public function get($id)
    {
        return $this->fieldRepository->find($id);
    }

    /**
     * @param integer $id
     * @return array
     */
    public function getByKeyValueTypePosition($key, $value, $type, $position)
    {
        return $this->fieldRepository->query()
            ->where(
                ['key' => $key, 'value' => $value, 'type' => $type, 'position' => $position]
            )
            ->get();

        $contentIds = [];
        $contents = [];

        foreach ($contentFields as $contentField) {
            if (!empty($contentField) && $contentField['type'] == 'content_id') {
                $contentIds[] = $contentField['value'];
            }
        }

        if (!empty($contentIds)) {
            $contents = $this->contentService->getByIds($contentIds);
        }

        foreach ($contentFields as $contentFieldIndex => $contentField) {
            foreach ($contents as $content) {
                if ($contentField['type'] == 'content_id' && $contentField['value'] == $content['id']) {
                    $contentFields[$contentFieldIndex]['value'] = $content;
                }
            }
        }

        return $contentFields;
    }

    /**
     * @param integer $id
     * @return array
     */
    public function getByKeyValueType($key, $value, $type)
    {
        return $this->fieldRepository->query()
            ->where(
                ['key' => $key, 'value' => $value, 'type' => $type]
            )
            ->get();

        $contentIds = [];
        $contents = [];

        foreach ($contentFields as $contentField) {
            if (!empty($contentField) && $contentField['type'] == 'content_id') {
                $contentIds[] = $contentField['value'];
            }
        }

        if (!empty($contentIds)) {
            $contents = $this->contentService->getByIds($contentIds);
        }

        foreach ($contentFields as $contentFieldIndex => $contentField) {
            foreach ($contents as $content) {
                if ($contentField['type'] == 'content_id' && $contentField['value'] == $content['id']) {
                    $contentFields[$contentFieldIndex]['value'] = $content;
                }
            }
        }

        return $contentFields;
    }

    /**
     * Create a new field and return it.
     *
     * @param integer $contentId
     * @param string $key
     * @param string $value
     * @param string $position
     * @param string $type
     * @return array
     */
    public function create($contentId, $key, $value, $position, $type)
    {
        $field = $this->fieldRepository->createOrUpdateAndReposition(
            null,
            [
                'content_id' => $contentId,
                'key' => $key,
                'value' => $value,
                'position' => $position,
                'type' => $type,
            ]
        );

        //Fire an event that the content was modified
        event(new ContentFieldCreated($contentId));

        //delete cache associated with the content id
        CacheHelper::deleteCache('content_' . $contentId);

        return $field;
    }

    /**
     * @param integer $id
     * @param array $data
     * @return array
     */
    public function update($id, array $data)
    {
        //Check if field exist in the database
        $field = $this->get($id);

        if (is_null($field)) {
            return $field;
        }

        if (count($data) == 0) {
            return $field;
        }

        $this->fieldRepository->reposition($id, $data);

        //Save a new content version
//        event(new ContentFieldUpdated($field['content_id']));

        //delete cache for associated content id
    //    CacheHelper::deleteCache('content_' . $field['content_id']);

        return $this->get($id);
    }

    /**
     * Call the repository method to unlink the content's field
     *
     * @param $id
     * @return bool
     */
    public function delete($id)
    {
        //Check if field exist in the database
        $field = $this->get($id);

        if (is_null($field)) {
            return $field;
        }

        $deleted = $this->fieldRepository->deleteAndReposition(['id' => $id]);

        //Save a new content version
       // event(new ContentFieldDeleted($field['content_id']));

        //delete cache for associated content id
       // CacheHelper::deleteCache('content_' . $field['content_id']);

        return $deleted;
    }

    public function createOrUpdate($data)
    {
        return $this->fieldRepository->reposition($data['id']??null, $data);

        $contentField = new ContentField();

        if(array_key_exists('id',$data)) {
            $contentField = $this->fieldRepository->find($data['id']);
        }

        $content = $this->contentService->getById($data['content_id']);

        $contentField->setKey($data['key']);
        $contentField->setValue($data['value']);
        $contentField->setPosition($data['position']);
        $contentField->setType($data['type']);
        $contentField->setContent($content);

        $this->entityManager->persist($contentField);
        $this->entityManager->flush();

//        $id = $this->fieldRepository->createOrUpdateAndReposition(
//            $data['id'] ?? null,
//            $data
//        );

        //Fire an event that the content was modified
//        if (array_key_exists('id', $data)) {
//            event(new ContentFieldUpdated($data['content_id']));
//        } else {
//            event(new ContentFieldCreated($data['content_id']));
//        }

        //delete cache associated with the content id
        //CacheHelper::deleteCache('content_' . $data['content_id']);

        return $contentField;
    }

}