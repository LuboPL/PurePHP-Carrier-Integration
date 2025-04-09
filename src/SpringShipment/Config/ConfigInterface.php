<?php
declare(strict_types=1);

namespace App\SpringShipment\Config;

interface ConfigInterface
{
    const string API_URL = 'https://mtapi.net/?testMode=1';
    const string CREATE_ORDER_SHIPMENT_COMMAND = 'OrderShipment';
    const string GET_SHIPMENT_LABEL_COMMAND = 'GetShipmentLabel';

    public function getApiUrl(): string;
    public function getCreateOrderShipmentCommand(): string;
    public function getGetShipmentLabelCommand(): string;
}