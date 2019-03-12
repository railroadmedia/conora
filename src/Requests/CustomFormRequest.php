<?php

namespace Railroad\Railcontent\Requests;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Validation\ValidationException;
use Railroad\DoctrineArrayHydrator\JsonApiHydrator;
use Railroad\Railcontent\Entities\Content;
use Railroad\Railcontent\Entities\ContentData;
use Railroad\Railcontent\Entities\Entity;
use Railroad\Railcontent\Services\ConfigService;
use Railroad\Railcontent\Services\ContentDatumService;
use Railroad\Railcontent\Services\ContentFieldService;
use Railroad\Railcontent\Services\ContentHierarchyService;
use Railroad\Railcontent\Services\ContentService;

/** Custom Form Request that contain the validation logic for the CMS.
 * There are:
 *      general rules - are the same for all the brands and content types
 *      custom rules - are defined by the developers in the configuration file and are defined per brand and content
 * type
 *
 * Class FormRequest
 *
 * @package Railroad\Railcontent\Requests
 */
class CustomFormRequest extends FormRequest
{
    /**
     * @var array $generalRules
     */
    protected $generalRules = [];

    /**
     * @var array $customRules
     */
    protected $customRules = [];

    /**
     * @var ContentService
     */
    protected $contentService;
    /**
     * @var ContentDatumService
     */
    private $contentDatumService;
    /**
     * @var ContentFieldService
     */
    private $contentFieldService;
    /**
     * @var ValidationFactory
     */
    private $validationFactory;
    /**
     * @var ContentHierarchyService
     */
    private $contentHierarchyService;

    private $jsonApiHydrator;

    private $entityManager;

    /**
     * CustomFormRequest constructor.
     *
     * @param ContentService $contentService
     * @param ContentDatumService $contentDatumService
     * @param JsonApiHydrator $jsonApiHydrator
     */
    public function __construct(
        ContentService $contentService,
        ContentDatumService $contentDatumService,
        JsonApiHydrator $jsonApiHydrator,
        EntityManager $entityManager,
        ValidationFactory $validationFactory
    ) {
        $this->contentService = $contentService;
        $this->contentDatumService = $contentDatumService;
        $this->jsonApiHydrator = $jsonApiHydrator;
        $this->entityManager = $entityManager;
        $this->validationFactory = $validationFactory;

        ConfigService::$cacheTime = -1;
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /** Get the general validation rules and the custom validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = array_merge($this->generalRules, $this->customRules);

        return $rules;
    }

    /** Set general rules
     *
     * @param array $rules
     */
    public function setGeneralRules(array $rules)
    {
        $this->generalRules = $rules;
    }

    /** Set the validation custom rules defined in the configuration file per brand and content type
     *
     * @param CustomFormRequest $request - the requests
     * @param null|string $entity - can be null, 'fields' or 'datum'
     *
     * @return array $customRules
     */
    public function setCustomRules($request, $entity = null)
    {
        $customRules = [];

        $noEntity = is_null($entity);
        $thereIsEntity = (!$noEntity);

        $contentType =
            $thereIsEntity ? $this->getContentTypeVal($request) :
                $request->request->get('data')['attributes']['type'] ?? '';

        if (isset(ConfigService::$validationRules[config('railcontent.brand')]) &&
            array_key_exists($contentType, ConfigService::$validationRules[config('railcontent.brand')])) {
            if (!$entity) {
                $customRules['data.attributes.fields'] =
                    ConfigService::$validationRules[config('railcontent.brand')][$contentType][$request->request->get(
                        'data'
                    )['attributes']['status']];
            } else {
                $customRules = $this->prepareCustomRules($request, $contentType, $entity);
            }
        }

        $this->customRules = $customRules;
        return $customRules;
    }

    /** Get the content's type based on content id for DatumRequest and FieldRequest instances
     *
     * @param ContentDatumCreateRequest|ContentFieldCreateRequest $request
     * @return string
     */
    private function getContentTypeVal($request)
    {
        $type = '';
        if (($request instanceof ContentDatumCreateRequest) || ($request instanceof ContentFieldCreateRequest)) {
            $contentId = $request->request->get('data')['relationships']['content']['data']['id'];
            $content = $this->contentService->getById($contentId);

            return ($content) ? $content->getType() : '';
        }

        return $type;
    }

    /** Prepare the custom validation rules.
     *
     * @param $entity
     * @param $contentType
     * @param $rules
     * @param $generalRules
     * @return mixed
     */
    private function prepareCustomRules($request, $contentType, $entity)
    {
        $rules = [];

        if (array_key_exists($entity, ConfigService::$validationRules[config('railcontent.brand')][$contentType])) {
            $customRules = ConfigService::$validationRules[config('railcontent.brand')][$contentType][$entity];

            $entity_key = $request->request->get('key');
            $entity_type = $request->request->get('type');

            foreach ($customRules as $key => $value) {

                $keyForField = $key == implode('|', [$entity_key, $entity_type]);
                $keyForDatum = $key == $entity_key;

                $getRulesForField = $keyForField && ($request instanceof ContentFieldCreateRequest);
                $getRulesForDatum = $keyForDatum && ($request instanceof ContentDatumCreateRequest);

                if ($getRulesForField || $getRulesForDatum) {
                    $rules = array_merge($rules, ['value' => $value]);
                }
            }
        }
        return $rules;
    }

    /**
     * @param CustomFormRequest $request
     * @return bool
     */
    public function validateContent($request)
    {
        $contentValidationRequired = null;
        $rulesForBrand = null;
        $content = null;
        $messages = [];

        try {
            $this->getContentForValidation($request, $contentValidationRequired, $rulesForBrand, $content);
        } catch (\Exception $exception) {
            throw new HttpResponseException(
                response()->json(
                    [
                        'code' => 500,
                        'errors' => $exception,
                    ]
                )
            );
        }

        if (!$contentValidationRequired) {
            return true;
        }

        $counts = [];
        $cannotHaveMultiple = [];

        foreach ($rulesForBrand[$content->getType()] as $setOfContentTypes => $rulesForContentType) {

            $setOfContentTypes = explode('|', $setOfContentTypes);

            if (!in_array($content->getStatus(), $setOfContentTypes)) {
                continue;
            }

            //            if (isset($rulesForContentType['number_of_children'])) {
            //
            //                $numberOfChildren = $this->contentHierarchyService->countParentsChildren(
            //                        [$content['id']]
            //                    )[$content['id']] ?? 0;
            //
            //                $rule = $rulesForContentType['number_of_children'];
            //
            //                if (is_array($rule) && key_exists('rules', $rule)) {
            //                    $rule = $rule['rules'];
            //                }
            //
            //                $messages = array_merge(
            //                    $messages,
            //                    $this->validateRuleAndGetErrors($numberOfChildren, $rule, 'number_of_children', 1)
            //                );
            //            }

            foreach ($rulesForContentType as $key => $rules) {
                if ($key == "fields") {
                    foreach ($rules as $field => $rule) {
                        $messages = array_merge(
                            $messages,
                            $this->validateRuleAndGetErrors($content, $rule['rules'] ?? $rule, $field)
                        );
                    }
                }
            }
        }

        /*
         * Determine "required" elements, and validate that they're present in the content.
         * The main validation section below fails to do this, thus its handled here by itself.
         * Maybe one day refactor it so it's all tidy and together, for now this works.
         */

        $required = [];


        /*
         * Loop through the components of the content which we're modifying (or modifying a component of) and on
         * each of those loops, then loop through validation rules for that content's type
         */


        foreach ($cannotHaveMultiple as $key) {
            $messages = array_merge(
                $messages,
                $this->validateRuleAndGetErrors((int)$counts[$key], 'numeric|max:1', $key . '_count', 1)
            );
        }

        // -------------------------------------------------------------------------------------------------------------

        /*
         * Make a request exempt from validation if the content was created before the validation was implemented
         * (defined by the "validation_exemption_date" in config"), AND the content property edited (when applicable)
         * does not fail validation.
         *
         * Otherwise users can't edit old content that was created before the validation was implemented and may not pass
         * validation. This means that while it's state is a protected one, nothing could be edited because the content
         * would always fail validation.
         */

        if ($request instanceof ContentDatumDeleteRequest) {
            $idInParam = array_values(
                $request->route()
                    ->parameters()
            )[0];

            $keyToCheckForExemption =
                $this->contentDatumService->get($idInParam)
                    ->getKey();
        }

        if ($request instanceof ContentDatumUpdateRequest) {
            $idInParam = array_values(
                $request->route()
                    ->parameters()
            )[0];

            $keyToCheckForExemption =
                $this->contentDatumService->get($idInParam)
                    ->getKey();
        }

        $contentCreatedOn = Carbon::parse(
            $content->getCreatedOn()
                ->format('Y-m-d H:i:s')
        );
        $exemptionDate = new Carbon('1970-01-01 00:00');
        if (!empty(ConfigService::$validationExemptionDate)) {
            $exemptionDate = new Carbon(ConfigService::$validationExemptionDate);
        }
        $exempt = $exemptionDate->gt($contentCreatedOn);

        foreach ($messages as $message) {
            if (empty($keyToCheckForExemption)) {
                $keyToCheckForExemption = null;
                if (!empty($request->request->all()['key'])) {
                    $keyToCheckForExemption = $request->request->all()['key'];
                }
            }
            if ($keyToCheckForExemption === $message['key']) {
                $exempt = false;
                $alternativeMessages = [$message];
            }
        }

        if (isset($alternativeMessages)) {
            $messages = $alternativeMessages;
        }

        // -------------------------------------------------------------------------------------------------------------

        /*
         * Passes Validation
         */
        if (empty($messages) || $exempt) {
            return true;
        }

        /*
         * Fails Validation
         */
        throw new HttpResponseException(
            response()->json(
                ['messages' => $messages],
                422
            )
        );
    }

    /**
     * @param CustomFormRequest $request
     * @param $contentValidationRequired
     * @param $rulesForBrand
     * @param $content
     * @throws \Exception
     */
    private function getContentForValidation(
        CustomFormRequest $request,
        &$contentValidationRequired,
        &$rulesForBrand,
        &$content
    ) {
        $minimumRequiredChildren = null;
        $contentValidationRequired = false;

        $input = $request->request->all();

        $brand = null;

        $content = $this->getContentFromRequest($request);

        if (empty($content)) {
            $contentValidationRequired = false;
            return;
        }

        $allValidationRules = ConfigService::$validationRules;

        if (empty($allValidationRules)) {
            $contentValidationRequired = false;
            return;
        }

        if (empty($content->getBrand())) {
            $contentValidationRequired = false;
            return;
        }

        $rulesForBrand = $allValidationRules[$content->getBrand()] ?? [];

        if (empty($rulesForBrand[$content->getType()])) {
            $contentValidationRequired = false;
            return;
        }

        $restrictions = $this->getStatusRestrictionsForType($content->getType(), $rulesForBrand);

        if ($request instanceof ContentCreateRequest) {
            if (isset($input['status'])) {
                if (in_array($input['status'], $restrictions)) {
                    throw new \Exception('Status cannot be set to: "' . $input['status'] . '" on content-create.');
                }
            }
        }

        /*
         * For each of the following "if request is instance of x" sections:
         *
         * part 1 - Validation required?
         *
         * part 2 - If request to create, update, or delete **A FIELD OR DATUM**, need content prepared for validation
         * to reflect the "requested whole" of the content - fields, data and all. The many cases below accomplish that
         * by preparing the content
         */

        if ($request instanceof ContentUpdateRequest) {
            // part 1
            $requestedStatusRequiresValidation = false;
            if (isset($input['data']['attributes']['status'])) {
                if (in_array($input['data']['attributes']['status'], $restrictions)) {
                    $requestedStatusRequiresValidation = true;
                }
            }

            $contentValidationRequired = $requestedStatusRequiresValidation || in_array(
                    $content->getStatus(),
                    $restrictions
                );

            // part 2
            if (array_key_exists('fields', $input['data']['attributes'])) {
                foreach ($input['data']['attributes']['fields'] as $field) {
                    if (in_array(
                        $field['key'],
                        $this->entityManager->getClassMetadata(Content::class)
                            ->getFieldNames()
                    )) {
                        $input['data']['attributes'][$field['key']] = $field['value'];
                    } else {
                        $set = 'set' . ucfirst($field['key']);
                        $add = 'add' . ucfirst($field['key']);
                        $newResource =
                            $this->entityManager->getClassMetadata(Content::class)
                                ->getAssociationTargetClass($field['key']);
                        $relationship = new $newResource;
                        $relationship->setContent($content);
                        $relationship->$set($field['value']);
                        $content->$add($relationship);
                    }
                }

                unset($input['fields']);
            }

            $this->jsonApiHydrator->hydrate($content, $input);
        }

        if ($request instanceof ContentDatumCreateRequest) {
            // part 1
            $contentValidationRequired = in_array($content->getStatus(), $restrictions);

            //            $contentData = new ContentData();
            //            $contentData->setContent($content);
            //            $contentData->setKey($input['data']['attributes']['key']);
            //            $contentData->setValue($input['data']['attributes']['value']);
            //$content->addData($contentData);
        }

        if ($request instanceof ContentDatumUpdateRequest) {

            // part 1
            $contentValidationRequired = in_array($content->getStatus(), $restrictions);

            // part 2
            $fieldsOrData = $request instanceof ContentFieldUpdateRequest ? 'fields' : 'data';
            //            dd($fieldsOrData);
            //            foreach ($content[$fieldsOrData] as &$item) {
            //                if ($item['id'] == $input['id']) {
            //                    $item['value'] = $input['value'];
            //                }
            //            }
        }

        $contentValidationRequired = $contentValidationRequired && isset($rulesForBrand[$content->getType()]);
    }

    private function getContentFromRequest(Request $request)
    {
        if ($request instanceof ContentCreateRequest) {
            $content = new Content();

            $this->jsonApiHydrator->hydrate($content, $request->onlyAllowed());

            return $content;
        }

        if ($request instanceof ContentUpdateRequest) {

            $urlPath = explode('/', parse_url($request->fullUrl())['path']);
            $id = (integer)array_values(array_slice($urlPath, -1))[0];

            return $this->contentService->getById($id);
        }

        if ($request instanceof ContentDatumCreateRequest) {
            $contentId = $request->request->get('data')['relationships']['content']['data']['id'];

            if (empty($contentId)) {
                error_log(
                    'Somehow we have a ContentDatumCreateRequest or ContentFieldCreateRequest without a' .
                    'content_id passed. This is at odds with what we\'re expecting and might be cause for concern'
                );
            }

            return $this->contentService->getById($contentId);
        }

        if ($request instanceof ContentDatumUpdateRequest) {
            $idInParam = array_values(
                $request->route()
                    ->parameters()
            )[0];

            $contentDatumOrField = $this->contentDatumService->get($idInParam);

        }

        if ($request instanceof ContentDatumDeleteRequest) {
            $idInParam = array_values(
                $request->route()
                    ->parameters()
            )[0];
            $contentDatumOrField = $this->contentDatumService->get($idInParam);

        }

        if (!empty($contentDatumOrField)) {
            return $this->contentService->getById(
                $contentDatumOrField->getContent()
                    ->getId()
            );
        }

        return [];
    }

    private function getStatusRestrictionsForType($contentType, $rulesForBrand)
    {
        $restrictions = [];
        foreach ($rulesForBrand[$contentType] as $setOfRestrictedStatuses => $rulesForContentType) {
            $setOfRestrictedStatuses = explode('|', $setOfRestrictedStatuses);
            $restrictions = array_merge($restrictions, $setOfRestrictedStatuses);
        }
        return $restrictions;
    }

    public function validateRule($fieldOrDatumValue, $rule, $key, $position = 0)
    {
        $get = 'get' . ucfirst($key);
        $val = $fieldOrDatumValue->$get();

        try {
            $this->validationFactory->make(
                [$key => $val ?? null],
                [$key => $rule]
            )
                ->validate();
        } catch (ValidationException $exception) {

            $messages =
                $exception->validator->messages()
                    ->messages();

            $formattedValidationMessages = [];

            foreach ($messages as $messageKey => $errors) {
                $formattedValidationMessages[] = [
                    'key' => $messageKey,
                    'position' => $position,
                    'errors' => $errors,
                ];
            }

            throw new HttpResponseException(
                response()->json(
                    [
                        'messages' => $formattedValidationMessages,
                    ],
                    422
                )
            );
        }
    }

    public function validateRuleAndGetErrors($fieldOrDatumValue, $rule, $key, $position = 0)
    {
        try {
            $this->validateRule($fieldOrDatumValue, $rule, $key, $position);
        } catch (HttpResponseException $exception) {
            return $exception->getResponse()
                ->getData(true)['messages'];
        }

        return [];
    }

    /**
     * @param CustomFormRequest $request
     * @return bool
     */
    public function validateContentOld($request)
    {
        $contentValidationRequired = null;
        $rulesForBrand = null;
        $content = null;
        $messages = [];

        try {
            $this->getContentForValidation($request, $contentValidationRequired, $rulesForBrand, $content);
        } catch (\Exception $exception) {
            throw new HttpResponseException(
                response()->json(
                    [
                        'code' => 500,
                        'errors' => $exception,
                    ]
                )
            );
        }

        if (!$contentValidationRequired) {
            return true;
        }

        $counts = [];
        $cannotHaveMultiple = [];

        foreach ($rulesForBrand[$content->getType()] as $setOfContentTypes => $rulesForContentType) {

            $setOfContentTypes = explode('|', $setOfContentTypes);

            if (!in_array($content->getStatus(), $setOfContentTypes)) {
                continue;
            }

            if (isset($rulesForContentType['number_of_children'])) {

                $numberOfChildren = $this->contentHierarchyService->countParentsChildren(
                        [$content['id']]
                    )[$content['id']] ?? 0;

                $rule = $rulesForContentType['number_of_children'];

                if (is_array($rule) && key_exists('rules', $rule)) {
                    $rule = $rule['rules'];
                }

                $messages = array_merge(
                    $messages,
                    $this->validateRuleAndGetErrors($numberOfChildren, $rule, 'number_of_children', 1)
                );
            }
        }

        /*
         * Determine "required" elements, and validate that they're present in the content.
         * The main validation section below fails to do this, thus its handled here by itself.
         * Maybe one day refactor it so it's all tidy and together, for now this works.
         */

        $required = [];

        foreach ($rulesForBrand[$content->getType()] as $setOfContentTypes => $rulesForContentType) {

            $setOfContentTypes = explode('|', $setOfContentTypes);

            if (!in_array($content->getStatus(), $setOfContentTypes)) {
                continue;
            }

            foreach ($rulesForContentType as $rulesPropertyKey => $rules) {

                if (!is_array($rules)) {
                    continue;
                }

                if ($rulesPropertyKey === 'number_of_children') {
                    continue;
                }

                foreach ($rules as $criteriaKey => &$criteria) {

                    if (!isset($criteria['rules'])) {
                        error_log(
                            $content['type'] .
                            '.' .
                            $criteriaKey .
                            ' for one of the brands is missing the ' .
                            '"rules" key in the validation config'
                        );
                    }

                    if (is_array($criteria['rules'])) {
                        if (in_array('required', $criteria['rules'])) {
                            $required[$rulesPropertyKey][] = $criteriaKey;
                        }
                    } elseif (strpos($criteria['rules'], 'required') !== false) {
                        $required[$rulesPropertyKey][] = $criteriaKey;
                    }
                }
            }
        }

        foreach ($required as $propertyKey => $list) {

            if (!is_string($propertyKey)) {
                $message =
                    'You are likely missing a key in the config validation rules for this content-type: "' .
                    print_r(json_encode($required), true) .
                    '"';
                if (!array_key_exists('fields', $required)) {
                    $message = $message . ' Perhaps the "' . $propertyKey . '" key should instead be "fields"?';
                } elseif (!array_key_exists('data', $required)) {
                    $message = $message . ' Perhaps the "' . $propertyKey . '" key should instead be "data"?';
                }
                throw new HttpResponseException(
                    reply()->json(
                        new Entity(['messages' => $message]),
                        [
                            'code' => 500,
                            'errors' => $message,
                        ]
                    )
                );
            }

            foreach ($list as $requiredElement) {
                $pass = false;
                foreach ($content[$propertyKey] as $contentPropertySet) {
                    if ($contentPropertySet['key'] === $requiredElement) {
                        $pass = true;
                    }
                }
                if (!$pass) {
                    $messages = array_merge(
                        $messages,
                        $this->validateRuleAndGetErrors(null, 'required', $requiredElement)
                    );
                }
            }
        }

        /*
         * Loop through the components of the content which we're modifying (or modifying a component of) and on
         * each of those loops, then loop through validation rules for that content's type
         */
        foreach ($content as $propertyName => $contentPropertySet) {

            foreach ($rulesForBrand[$content['type']] as $setOfContentTypes => $rulesForContentType) {

                $setOfContentTypes = explode('|', $setOfContentTypes);

                if (!in_array($content['status'], $setOfContentTypes)) {
                    continue;
                }

                foreach ($rulesForContentType as $rulesPropertyKey => $rules) {

                    /*
                     * "number_of_children" rules are handled elsewhere.
                     */
                    if ($rulesPropertyKey === 'number_of_children') {
                        continue;
                    }

                    // $rulesPropertyKey will be "data" or "fields"

                    /*
                     * If there's rule for the content-component we're currently at in our looping, then validate
                     * that component.
                     */
                    foreach ($rules as $criteriaKey => $criteria) {

                        if (!($propertyName === $rulesPropertyKey && !empty($criteria))) {
                            continue; // if does not match field & datum segments
                        }

                        /*
                         * Loop through the components to validate where needed
                         */
                        foreach ($contentPropertySet as $contentProperty) {

                            $key = $contentProperty['key'];
                            $fieldOrDatumValue = $contentProperty['value'];

                            /*
                             * If the field|datum item is itself a piece of content, get the id so that can be
                             * passed to the closure that evaluates the presence of that content in the database
                             */
                            if (($contentProperty['type'] ?? null) === 'content' && isset($fieldOrDatumValue['id'])) {
                                $fieldOrDatumValue = $fieldOrDatumValue['id'];
                            }

                            if ($key !== $criteriaKey) {
                                continue;
                            }

                            // Validate the component

                            $position = $contentProperty['position'] ?? null;

                            $messages = array_merge(
                                $messages,
                                $this->validateRuleAndGetErrors(
                                    $fieldOrDatumValue,
                                    $criteria['rules'],
                                    $key,
                                    $position
                                )
                            );

                            $thisOneCanHaveMultiple = false;

                            if (array_key_exists('can_have_multiple', $criteria)) {
                                $thisOneCanHaveMultiple = $criteria['can_have_multiple'];
                            }

                            if (!$thisOneCanHaveMultiple) {
                                $cannotHaveMultiple[] = $key;
                                $counts[$key] = isset($counts[$key]) ? $counts[$key] + 1 : 1;
                            }
                        }
                    }
                }
            }
        }

        foreach ($cannotHaveMultiple as $key) {
            $messages = array_merge(
                $messages,
                $this->validateRuleAndGetErrors((int)$counts[$key], 'numeric|max:1', $key . '_count', 1)
            );
        }

        // -------------------------------------------------------------------------------------------------------------

        /*
         * Make a request exempt from validation if the content was created before the validation was implemented
         * (defined by the "validation_exemption_date" in config"), AND the content property edited (when applicable)
         * does not fail validation.
         *
         * Otherwise users can't edit old content that was created before the validation was implemented and may not pass
         * validation. This means that while it's state is a protected one, nothing could be edited because the content
         * would always fail validation.
         */

        if ($request instanceof ContentDatumDeleteRequest || $request instanceof ContentFieldDeleteRequest) {
            $idInParam = array_values(
                $request->route()
                    ->parameters()
            )[0];
            if ($request instanceof ContentFieldDeleteRequest) {
                $keyToCheckForExemption =
                    $this->contentFieldService->get($idInParam)
                        ->getKey();
            } else {
                $keyToCheckForExemption =
                    $this->contentDatumService->get($idInParam)
                        ->getKey();
            }
        }

        if ($request instanceof ContentDatumUpdateRequest || $request instanceof ContentFieldUpdateRequest) {
            $idInParam = array_values(
                $request->route()
                    ->parameters()
            )[0];
            if ($request instanceof ContentFieldUpdateRequest) {
                $keyToCheckForExemption =
                    $this->contentFieldService->get($idInParam)
                        ->getKey();
            } else {
                $keyToCheckForExemption =
                    $this->contentDatumService->get($idInParam)
                        ->getKey();
            }
        }

        $contentCreatedOn = Carbon::parse(
            $content->getCreatedOn()
                ->format('Y-m-d H:i:s')
        );
        $exemptionDate = new Carbon('1970-01-01 00:00');
        if (!empty(ConfigService::$validationExemptionDate)) {
            $exemptionDate = new Carbon(ConfigService::$validationExemptionDate);
        }
        $exempt = $exemptionDate->gt($contentCreatedOn);

        foreach ($messages as $message) {
            if (empty($keyToCheckForExemption)) {
                $keyToCheckForExemption = null;
                if (!empty($request->request->all()['key'])) {
                    $keyToCheckForExemption = $request->request->all()['key'];
                }
            }
            if ($keyToCheckForExemption === $message['key']) {
                $exempt = false;
                $alternativeMessages = [$message];
            }
        }

        if (isset($alternativeMessages)) {
            $messages = $alternativeMessages;
        }

        // -------------------------------------------------------------------------------------------------------------

        /*
         * Passes Validation
         */
        if (empty($messages) || $exempt) {
            return true;
        }

        /*
         * Fails Validation
         */
        throw new HttpResponseException(
            reply()->json(
                new Entity(['messages' => $messages]),
                [
                    'code' => 422,
                    'errors' => $messages,
                ]
            )
        );
    }
}