<?php
declare(strict_types=1);

namespace App\SpringShipment\Mapper;

use App\SpringShipment\Model\Address\ConsigneeAddress;
use App\SpringShipment\Model\Address\ConsignorAddress;
use App\SpringShipment\Model\Product\Product;
use App\SpringShipment\Model\Shipment\Shipment;

readonly class ShipmentMapper implements MapperInterface
{
    public function mapShipment(array $order, array $params): Shipment
    {
        return new Shipment(
            shipperReference: $params['shipperReference'] ?? '',
            service: $params['service'] ?? '',
            weight: $params['weight'] ?? '',
            value: $order['value'] ?? '',
            consignorAddress: ConsignorAddress::fromArray($order),
            consigneeAddress: ConsigneeAddress::fromArray($order),
            products: $this->mapProducts($order),
            labelFormat: $params['labelFormat'] ?? 'PDF',
            orderReference: $order['orderReference'] ?? null,
            orderDate: $order['orderDate'] ?? null,
            displayId: $order['displayId'] ?? null,
            invoiceNumber: $order['invoiceNumber'] ?? null,
            weightUnit: $params['weightUnit'] ?? 'kg',
            length: $params['length'] ?? null,
            width: $params['width'] ?? null,
            height: $params['height'] ?? null,
            dimUnit: $params['dimUnit'] ?? 'cm',
            shippingValue: $order['shippingValue'] ?? null,
            currency: $order['currency'] ?? 'EUR',
            customsDuty: $params['customsDuty'] ?? 'DDU',
            description: $order['description'] ?? null,
            declarationType: $params['declarationType'] ?? 'SaleOfGoods',
            dangerousGoods: $params['dangerousGoods'] ?? 'N',
            exportCarrierName: $params['exportCarrierName'] ?? null,
            exportAwb: $params['exportAwb'] ?? null
        );
    }

    public function mapRequestData(string $apiCommand, array $data): array
    {
        return [
            'Command' => $apiCommand,
            'Shipment' => $data
        ];
    }

    private function mapProducts(array $order): array
    {
        return array_map(
            fn(array $product) => (new Product(
                description: $product['description'] ?? '',
                sku: $product['sku'] ?? '',
                hsCode: $product['hsCode'] ?? '',
                originCountry: $product['originCountry'] ?? '',
                imgUrl: $product['imgUrl'] ?? '',
                purchaseUrl: $product['purchaseUrl'] ?? '',
                quantity: $product['quantity'] ?? '',
                value: $product['value'] ?? '',
                weight: $product['weight'] ?? '',
                daysForReturn: $product['daysForReturn'] ?? null,
                nonReturnable: $product['nonReturnable'] ?? null
            ))->jsonSerialize(),
            $order['products'] ?? []
        );
    }
}