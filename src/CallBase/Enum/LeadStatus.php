<?php

namespace App\CallBase\Enum;

enum LeadStatus: string
{
    case NEW = 'new';
    case PROCESS = 'process';
    case SUCCESS = 'success';
    case REJECTED = 'rejected';
}
