<?php
namespace Modules\Ynotz\EasyApi\DataObjects;

enum OperationEnum{
    case IS;
    case CONTAINS;
    case STARTS_WITH;
    case ENDS_WITH;
    case GREATER_THAN;
    case LESS_THAN;
    case GREATER_THAN_OR_EQUAL_TO;
    case LESS_THAN_OR_EQUAL_TO;
    case EQUAL_TO;
    case NO_EQUAL_TO;
}
