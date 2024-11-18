<?php
namespace Modules\Ynotz\EasyApi\DataObjects;

enum OperationEnum: string{
    case IS = 'is';
    case CONTAINS = 'ct';
    case STARTS_WITH = 'st';
    case ENDS_WITH = 'en';
    case GREATER_THAN = 'gt';
    case LESS_THAN = 'lt';
    case GREATER_THAN_OR_EQUAL_TO = 'gte';
    case LESS_THAN_OR_EQUAL_TO = 'lte';
    case EQUAL_TO = 'eq';
    case NO_EQUAL_TO = 'neq';

    public function operator(): string
    {
        return match($this) {
            OperationEnum::IS,
            OperationEnum::STARTS_WITH,
            OperationEnum::ENDS_WITH,
            OperationEnum::CONTAINS => 'like',
            OperationEnum::GREATER_THAN => '>',
            OperationEnum::LESS_THAN => '<',
            OperationEnum::GREATER_THAN_OR_EQUAL_TO => '>=',
            OperationEnum::LESS_THAN_OR_EQUAL_TO => '<=',
            OperationEnum::EQUAL_TO => '=',
            OperationEnum::NO_EQUAL_TO => '<>',
        };
    }

    public function formatSearchValue($value)
    {
        return match($this) {
            OperationEnum::GREATER_THAN,
            OperationEnum::LESS_THAN,
            OperationEnum::GREATER_THAN_OR_EQUAL_TO,
            OperationEnum::LESS_THAN_OR_EQUAL_TO,
            OperationEnum::EQUAL_TO,
            OperationEnum::NO_EQUAL_TO,
            OperationEnum::IS => $value,
            OperationEnum::STARTS_WITH => "$value%",
            OperationEnum::ENDS_WITH => "%$value",
            OperationEnum::CONTAINS => "%$value%",
        };
        return [
            'op' => $this->operator(),
            'val' => ''
        ];
        $v = $val;
        switch($op) {
            case OperationEnum::CONTAINS:
                $v = '%'.$val.'%';
                break;
            case OperationEnum::STARTS_WITH:
                $v = $val.'%';
                break;
            case OperationEnum::ENDS_WITH:
                $v = '%'.$val;
                break;
        }

        return [
            'op' => $op->operator(),
            'val' => $v
        ];
    }
}
