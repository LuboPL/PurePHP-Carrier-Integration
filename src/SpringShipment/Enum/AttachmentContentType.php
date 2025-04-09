<?php
declare(strict_types=1);

namespace App\SpringShipment\Enum;

enum AttachmentContentType: string
{
    case PDF = 'PDF';
    // case ZPL = 'ZPL';
    // ...
    public function getType(): string
    {
        return match ($this) {
            self::PDF => 'application/pdf',
            // self:: ZPL => 'application/x-zpl'
        };
    }

    public static function getTypeOrDefault(string $format): string
    {
        return self::tryFrom($format)?->getType() ?? 'application/pdf';
    }
}