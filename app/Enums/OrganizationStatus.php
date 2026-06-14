<?php

namespace App\Enums;

enum OrganizationStatus: string
{
    case Pending = 'pending';
    case Parsing = 'parsing';
    case Done = 'done';
    case Failed = 'failed';
}
