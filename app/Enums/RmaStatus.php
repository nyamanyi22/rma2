<?php

namespace App\Enums;

enum RmaStatus: string
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
    case COMPLETED = 'COMPLETED';

    case PROCESSING = 'PROCESSING';
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function labels(): array
    {
        return [
            self::PENDING->value => 'Pending',
            self::APPROVED->value => 'Approved',
            self::REJECTED->value => 'Rejected',
            self::COMPLETED->value => 'Completed',
            self ::PROCESSING->value => 'Processing',

        ];
    }
}
