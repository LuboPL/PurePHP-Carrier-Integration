<?php
declare (strict_types=1);

namespace Application\SpringShipment\Service;

use App\SpringShipment\Model\Shipment\ShipmentDetails;

interface LabelInterface
{
    public function printLabel(ShipmentDetails $shipmentDetails): string;
}