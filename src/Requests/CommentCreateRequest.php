<?php

namespace Railroad\Railcontent\Requests;


use Illuminate\Validation\Rule;
use Railroad\Railcontent\Repositories\ContentRepository;
use Railroad\Railcontent\Services\ConfigService;

class CommentCreateRequest extends FormRequest
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
            'comment' => 'required|max:10024',
            'content_id' =>
                ['required',
                    'numeric',
                    Rule::exists(
                        ConfigService::$databaseConnectionName . '.' . ConfigService::$tableContent, 'id'
                    )->where(function ($query) {
                        if (is_array(ContentRepository::$availableContentStatues)) {
                            $query->whereIn('status', ContentRepository::$availableContentStatues);
                        }
                    })
                ],
//            'display_name' => 'required|max:255'
        ];
    }
}