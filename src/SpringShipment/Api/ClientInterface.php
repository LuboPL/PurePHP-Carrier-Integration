<?php
declare(strict_types=1);

namespace App\SpringShipment\Api;

interface ClientInterface
{
    public function executeRequest(string $apiUrl, array $data): ShipmentApiResponse;
}