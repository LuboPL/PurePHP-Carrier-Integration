<?php
declare(strict_types=1);

namespace App\SpringShipment\Config;

class Config implements ConfigInterface
{

    public function getApiUrl(): string
    {
        return self::API_URL;
    }

    public function getCreateOrderShipmentCommand(): string
    {
        return self::CREATE_ORDER_SHIPMENT_COMMAND;
    }

    public function getGetShipmentLabelCommand(): string
    {
        return self::GET_SHIPMENT_LABEL_COMMAND;
    }
}