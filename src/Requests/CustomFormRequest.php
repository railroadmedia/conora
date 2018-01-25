<?php

namespace Railroad\Railcontent\Requests;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Railroad\Railcontent\Exceptions\NotFoundException;
use Railroad\Railcontent\Services\ConfigService;
use Railroad\Railcontent\Services\ContentDatumService;
use Railroad\Railcontent\Services\ContentFieldService;
use Railroad\Railcontent\Services\ContentService;
use Illuminate\Validation\Factory as ValidationFactory;

/** Custom Form Request that contain the validation logic for the CMS.
 * There are:
 *      general rules - are the same for all the brands and content types
 *      custom rules - are defined by the developers in the configuration file and are defined per brand and content type
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
     * ValidationService constructor.
     *
     * @param $contentService
     */
    public function __construct(
        ContentService $contentService,
        ContentDatumService $contentDatumService,
        ContentFieldService $contentFieldService,
        ValidationFactory $validationFactory
    ){
        $this->contentService = $contentService;
        $this->contentDatumService = $contentDatumService;
        $this->contentFieldService = $contentFieldService;
        $this->validationFactory = $validationFactory;

        parent::__construct();
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
            $thereIsEntity ? $this->getContentTypeVal($request) : $request->request->get('type');

        if (isset(ConfigService::$validationRules[ConfigService::$brand]) &&
            array_key_exists($contentType, ConfigService::$validationRules[ConfigService::$brand])) {
            if (!$entity) {
                $customRules = ConfigService::$validationRules[ConfigService::$brand][$contentType];
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
        if ( ($request instanceof ContentDatumCreateRequest) || ($request instanceof ContentFieldCreateRequest) ) {
            $contentId = $request->request->get('content_id');
            $content = $this->contentService->getById($contentId);

            return $content['type'];
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

        if (array_key_exists($entity, ConfigService::$validationRules[ConfigService::$brand][$contentType])) {
            $customRules = ConfigService::$validationRules[ConfigService::$brand][$contentType][$entity];

            $entity_key = $request->request->get('key');
            $entity_type = $request->request->get('type');

            foreach ($customRules as $key => $value) {

                $keyForField = $key == implode('|', [$entity_key, $entity_type]);
                $keyForDatum = $key == $entity_key;

                $getRulesForField = $keyForField && ($request instanceof ContentFieldCreateRequest);
                $getRulesForDatum = $keyForDatum && ($request instanceof ContentDatumCreateRequest);

                if ($getRulesForField || $getRulesForDatum) {
                    $rules = array_merge( $rules, ['value' => $value]);
                }
            }
        }
        return $rules;
    }

    public function validateContent($request)
    {
        $content = null;
        $keysOfValuesRequestedToSet = [];
        $restricted = null;
        $input = $request->request->all();

        $this->setContentToValidate($content, $keysOfValuesRequestedToSet, $restricted, $input);

        $contentId = null;
        $contentType = null;

        $contentValidationRequired = $this->contentValidationRequired($request);

        $this->setRulesForBrandAndContentType($contentType, $restricted);

        $this->prepareForContentValidation();

        if($contentValidationRequired){ // ... then we need to validate lest we set restricted on an invalid content

            $rules = $this->contentService->getValidationRules($content);

            if($rules === false){
                return new JsonResponse('Application misconfiguration. Validation rules missing perhaps.', 503);
            }

            $contentPropertiesForValidation = $this->contentService->getContentPropertiesForValidation($content, $rules);

            try{
                $this->validationFactory->make($contentPropertiesForValidation, $rules)->validate();
            }catch(ValidationException $exception){
                $messages = $exception->validator->messages()->messages();
                return new JsonResponse(['messages' => $messages], 422);

                /*
                 * Validation failure will interrupt writing field|datum - thus preventing the publication or
                 * scheduling of a ill-formed lesson.
                 *
                 * Jonathan, January 2018
                 */
            }
        }

        return true;
    }

    protected function setContentToValidate(&$content, &$keysOfValuesRequestedToSet, &$restricted, &$input){
        return true;
    }

    protected function prepareForContentValidation(&$content, &$keysOfValuesRequestedToSet, &$restricted, &$input){
        return true;
    }

    protected function contentValidationRequired($request){
        /*
         * We have to validate the content if:
         * 1. the user is setting the status to a restricted value
         * or
         * 2. the user is creating|updating a content-field or content-datum for a content with a status value that
         * is currently a restricted value.
         */

        if($request instanceof ContentCreateRequest || $request instanceof ContentUpdateRequest){

            // are they attempting to set the status?

            // if so are they attempting to set it to a restricted value?


        }elseif(
            $request instanceof ContentDatumCreateRequest ||
            $request instanceof ContentFieldCreateRequest ||
            $request instanceof ContentDatumUpdateRequest ||
            $request instanceof ContentFieldUpdateRequest
        ){

            // what is the content's current status?

            // is the content's current status a restricted value?

        }
    }

    protected function setRulesForBrandAndContentType($contentType, &$restricted){
        $rulesExistForBrand = isset(ConfigService::$validationRules[ConfigService::$brand]);

        $restrictedExistsForBrand = array_key_exists(
            'restricted_for_invalid_content',
            ConfigService::$validationRules[ConfigService::$brand]
        );

        if ($rulesExistForBrand && $restrictedExistsForBrand){
            $restricted = ConfigService::$validationRules[ConfigService::$brand]['restricted_for_invalid_content'];
        }

        if(in_array($contentType, array_keys($restricted['custom']))){
            $restricted = $restricted['custom'][$contentType];
        }else{
            $restricted = $restricted['default'];
        }

        throw_if(empty($restricted), // code-smell! Why are we doing this? Is it not obvious that it should just be set?
            new \Exception('$restricted not filled in (Railroad) CustomFormRequest::validateContent')
        );
    }

}