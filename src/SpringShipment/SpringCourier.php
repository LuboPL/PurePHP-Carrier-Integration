<?php
declare(strict_types=1);

namespace App\SpringShipment;

use App\SpringShipment\Api\ClientInterface;
use App\SpringShipment\Config\ConfigInterface;
use App\SpringShipment\Mapper\MapperInterface;
use App\SpringShipment\Model\Shipment\Shipment;
use Application\SpringShipment\Service\LabelInterface;

readonly class SpringCourier
{
    public function __construct(
        private ClientInterface $client,
        private LabelInterface $labelService,
        private MapperInterface $shipmentMapper,
        private ConfigInterface $config
    )
    {
    }

    public function newPackage(array $order, array $params): Shipment
    {
        $shipment = $this->shipmentMapper->mapShipment($order, $params);
        $response = $this->client->executeRequest(
            $this->config->getApiUrl(),
            $this->shipmentMapper->mapRequestData(
                $this->config->getCreateOrderShipmentCommand(),
                $shipment->jsonSerialize()
            )
        );
        $shipment->setShipmentDetails($response->shipmentDetails);

        return $shipment;
    }

    public function downloadSticker(string $trackingNumber): void
    {
        $response = $this->client->executeRequest(
            $this->config->getApiUrl(),
            $this->shipmentMapper->mapRequestData(
                $this->config->getGetShipmentLabelCommand(),
                ['TrackingNumber' => $trackingNumber]
            )
        );
        $label = $this->labelService->printLabel($response->shipmentDetails);
        echo $label;
        exit();
    }
}
