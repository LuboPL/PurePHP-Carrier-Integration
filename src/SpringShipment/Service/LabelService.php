<?php
declare(strict_types=1);

namespace App\SpringShipment\Service;

use App\SpringShipment\Enum\AttachmentContentType;
use App\SpringShipment\Exception\SpringShipmentException;
use App\SpringShipment\Model\Shipment\ShipmentDetails;
use Application\SpringShipment\Service\LabelInterface;

readonly class LabelService implements LabelInterface
{
    /**
     * @throws  SpringShipmentException
     */
    public function printLabel(ShipmentDetails $shipmentDetails): string
    {
        $label = $this->getLabelByShipmentDetails($shipmentDetails);
        $this->setHeadersForBrowser($label, $shipmentDetails);

        return $label;
    }

    /**
     * @throws  SpringShipmentException
     */
    private function getLabelByShipmentDetails(ShipmentDetails $shipmentDetails): string
    {
        $handlers = [
            'PDF' => fn($shipment) => $this->decodePDFLabelFromResponse($shipment->labelImage),
            // 'ZPL' => fn($shipment) => $this->decodeZPLLabelFromResponse($shipment->labelImage),
            // ...
        ];
        $handler = $handlers[$shipmentDetails->labelFormat] ?? throw new  SpringShipmentException('Label type not supported');

        return $handler($shipmentDetails);
    }

    /**
     * @throws  SpringShipmentException
     */
    private function decodePDFLabelFromResponse(string $labelImage): string
    {
        $pdfContent = base64_decode($labelImage);

        return $pdfContent !== false ? $pdfContent : throw new  SpringShipmentException('PDF content is empty');
    }

    private function setHeadersForBrowser(string $label, ShipmentDetails $shipmentDetails): void
    {
        header(sprintf('Content-Type: %s', AttachmentContentType::getTypeOrDefault($shipmentDetails->labelFormat)));
        header(sprintf('Content-Disposition: attachment; filename="%s"', $this->getFilename($shipmentDetails)));
        header('Content-Transfer-Encoding: binary');
        header(sprintf('Content-Length: %d', strlen($label)));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    private function getFilename(ShipmentDetails $shipmentDetails): string
    {
        return sprintf(
            '%s_label.%s',
            $shipmentDetails->trackingNumber,
            strtolower($shipmentDetails->labelFormat)
        );
    }
}