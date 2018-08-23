<?php

namespace Railroad\Railcontent\Requests;


use Illuminate\Validation\Rule;
use Railroad\Railcontent\Repositories\ContentRepository;
use Railroad\Railcontent\Services\ConfigService;

class CommentUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'comment' => 'nullable|max:10024',
            'content_id' =>
                ['numeric',
                    Rule::exists(
                        ConfigService::$databaseConnectionName . '.' .
                        ConfigService::$tableContent,
                        'id'
                    )->where(
                        function ($query) {
                            if (is_array(ContentRepository::$availableContentStatues)) {
                                $query->whereIn('status', ContentRepository::$availableContentStatues);
                            }
                        }
                    )
                ],
            'parent_id' => 'numeric|exists:' . ConfigService::$databaseConnectionName . '.' .
                ConfigService::$tableComments . ',id',
            'display_name' => 'filled'
        ];
    }
}