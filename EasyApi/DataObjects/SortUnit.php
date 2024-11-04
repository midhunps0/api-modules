<?php
namespace Modules\Ynotz\EasyApi\DataObjects;

class SortUnit
{
    public function __construct(
        public $field,
        public $direction,
    )
    {
        # code...
    }
}
