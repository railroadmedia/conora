<?php
namespace Railroad\Railcontent\Serializer;

use League\Fractal\Serializer\DataArraySerializer;

class OldStyleSerializer extends DataArraySerializer
{
    public function mergeIncludes($transformedData, $includedData)
    {
        $includedData = array_map(function ($include) {
            return $include['data'];
        }, $includedData);

        return parent::mergeIncludes($transformedData, $includedData);
    }
}