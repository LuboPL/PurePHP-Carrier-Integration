<?php
declare(strict_types=1);

namespace App\SpringShipment\Model\Shipment;

use App\SpringShipment\Model\Address\ConsigneeAddress;
use App\SpringShipment\Model\Address\ConsignorAddress;
use JsonSerializable;

class Shipment implements JsonSerializable
{
    private ?ShipmentDetails $shipmentDetails = null;

    public function __construct(
        public readonly string $shipperReference,
        public readonly string $service,
        public readonly string $weight,
        public readonly string $value,
        public readonly ConsignorAddress $consignorAddress,
        public readonly ConsigneeAddress $consigneeAddress,
        /**
         * @var array<Product>
         */
        public readonly array $products = [],
        public readonly ?string $labelFormat = 'PDF',
        public readonly ?string $orderReference = null,
        public readonly ?string $orderDate = null,
        public readonly ?string $displayId = null,
        public readonly ?string $invoiceNumber = null,
        public readonly ?string $weightUnit = 'kg',
        public readonly ?string $length = null,
        public readonly ?string $width = null,
        public readonly ?string $height = null,
        public readonly ?string $dimUnit = 'cm',
        public readonly ?string $shippingValue = null,
        public readonly ?string $currency = 'EUR',
        public readonly ?string $customsDuty = 'DDU',
        public readonly ?string $description = null,
        public readonly ?string $declarationType = 'SaleOfGoods',
        public readonly ?string $dangerousGoods = 'N',
        public readonly ?string $exportCarrierName = null,
        public readonly ?string $exportAwb = null
    )
    {
    }

    public function jsonSerialize(): array
    {
        return [
           'ShipperReference' => $this->shipperReference,
           'Service' => $this->service,
           'Weight' => $this->weight,
           'Value' => $this->value,
           'LabelFormat' => $this->labelFormat,
           'WeightUnit' => $this->weightUnit,
           'Currency' => $this->currency,
           'CustomsDuty' => $this->customsDuty,
           'DeclarationType' => $this->declarationType,
           'DangerousGoods' => $this->dangerousGoods,
           'ConsigneeAddress' => $this->consigneeAddress->jsonSerialize(),
           'ConsignorAddress' => $this->consignorAddress->jsonSerialize(),
           'Products' => $this->products,
           'OrderReference' => $this->orderReference,
           'OrderDate' => $this->orderDate,
           'DisplayId' => $this->displayId,
           'InvoiceNumber' => $this->invoiceNumber,
           'Length' => $this->length,
           'Width' => $this->width,
           'Height' => $this->height,
           'DimUnit' => $this->dimUnit,
           'ShippingValue' => $this->shippingValue,
           'Description' => $this->description,
           'ExportCarrierName' => $this->exportCarrierName,
           'ExportAwb' => $this->exportAwb
        ];
    }

    public function getShipmentDetails(): ShipmentDetails
    {
        return $this->shipmentDetails;
    }

    public function setShipmentDetails(ShipmentDetails $shipmentDetails): void
    {
        $this->shipmentDetails = $shipmentDetails;
    }
}