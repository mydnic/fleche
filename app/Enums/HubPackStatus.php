<?php

namespace App\Enums;

enum HubPackStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
