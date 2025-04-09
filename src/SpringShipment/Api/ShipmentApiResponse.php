<?php
declare(strict_types=1);

namespace App\SpringShipment\Api;

use App\SpringShipment\Model\Shipment\ShipmentDetails;

readonly class ShipmentApiResponse
{
    private function __construct(
        public int $errorLevel,
        public string $error,
        public ?ShipmentDetails $shipmentDetails = null
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            errorLevel: $data['ErrorLevel'] ?? 0,
            error: $data['Error'] ?? '',
            shipmentDetails: isset($data['Shipment'])
                ? ShipmentDetails::fromArray($data['Shipment'])
                : null
        );
    }
}