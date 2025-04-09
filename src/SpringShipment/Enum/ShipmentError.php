<?php
declare(strict_types=1);

namespace App\SpringShipment\Enum;

enum ShipmentError: int
{
    case COMMAND_COMPLETED_WITH_ERRORS = 1;
    case FATAL_ERROR = 10;

    public function getMessage(): string
    {
        return match ($this) {
            self::COMMAND_COMPLETED_WITH_ERRORS => 'Command completed with errors: %s',
            self::FATAL_ERROR => 'Fatal error, command is not completed at all: %s',
        };
    }
}