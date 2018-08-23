<?php

namespace Railroad\Railcontent\Requests;

use Railroad\Railcontent\Services\ConfigService;

class ReplyRequest extends FormRequest
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
            'parent_id' => 'required|numeric|exists:' .
                ConfigService::$databaseConnectionName .
                '.' .
                ConfigService::$tableComments .
                ',id'
        ];
    }
}