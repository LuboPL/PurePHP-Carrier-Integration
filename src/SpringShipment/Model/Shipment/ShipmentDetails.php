<?php
declare(strict_types=1);

namespace App\SpringShipment\Model\Shipment;

readonly class ShipmentDetails
{
    private function __construct(
        public string $trackingNumber,
        public string $shipperReference,
        public string $displayId,
        public string $service,
        public string $carrier,
        public string $carrierTrackingNumber,
        public string $carrierLocalTrackingNumber,
        public string $carrierTrackingUrl,
        public string $labelFormat,
        public string $labelType,
        public string $labelImage
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            trackingNumber: $data['TrackingNumber'],
            shipperReference: $data['ShipperReference'],
            displayId: $data['DisplayId'],
            service: $data['Service'],
            carrier: $data['Carrier'],
            carrierTrackingNumber: $data['CarrierTrackingNumber'],
            carrierLocalTrackingNumber: $data['CarrierLocalTrackingNumber'],
            carrierTrackingUrl: $data['CarrierTrackingUrl'],
            labelFormat: $data['LabelFormat'],
            labelType: $data['LabelType'],
            labelImage: $data['LabelImage']
        );
    }
}