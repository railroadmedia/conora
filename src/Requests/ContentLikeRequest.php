<?php


namespace Railroad\Railcontent\Requests;

/**
 * Class ContentLikeRequest
 *
 * @package Railroad\Railcontent\Requests
 *
 * @bodyParam data.relationships.content.data.type string  required Must be 'content'. Example: content
 * @bodyParam data.relationships.content.data.id integer  required Must exists in contents. Example: 1
 */
class ContentLikeRequest extends FormRequest
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
    public static function rules()
    {
        return [
            'data.relationships.content.data.id' => 'required|numeric|exists:' . config('railcontent.database_connection_name') . '.' .
                config('railcontent.table_prefix'). 'content' . ',id'
        ];
    }

    /**
     * @return array
     */
    public function onlyAllowed()
    {
        return $this->only(
            [
                'data.relationships.content'
            ]
        );
    }
}