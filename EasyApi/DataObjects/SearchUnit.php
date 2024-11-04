<?php
namespace Modules\Ynotz\EasyApi\DataObjects;

class SearchUnit
{
    public function __construct(
        public $field,
        public OperationEnum $operation,
        public $value
    )
    {
        # code...
    }
}
