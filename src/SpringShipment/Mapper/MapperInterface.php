<?php
declare(strict_types=1);

namespace App\SpringShipment\Mapper;

use App\SpringShipment\Model\Shipment\Shipment;

interface MapperInterface
{
    public function mapShipment(array $order, array $params): Shipment;
    public function mapRequestData(string $apiCommand, array $data): array;
}