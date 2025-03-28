<?php
declare(strict_types=1);

class SpringCourier
{
    private const string API_URL = 'https://mtapi.net/?testMode=1';
    private const string ORDER_SHIPMENT_COMMAND = 'OrderShipment';
    private const string GET_SHIPMENT_LABEL_COMMAND = 'GetShipmentLabel';

    private ApiClient $client;
    private LabelService $labelService;
    private ShipmentMapper $shipmentMapper;

    public function __construct(private readonly string $apiKey)
    {
        $this->client = new ApiClient();
        $this->labelService = new LabelService();
        $this->shipmentMapper = new ShipmentMapper();
    }

    /**
     * @throws Exception
     */
    public function newPackage(array $order, array $params): Shipment
    {
        $shipment = $this->shipmentMapper->mapShipment($order, $params);

        $response = $this->client->executeRequest(
            self::API_URL,
            $this->shipmentMapper->mapRequestData(
                $this->apiKey,
                self::ORDER_SHIPMENT_COMMAND,
                $shipment->jsonSerialize()
            )
        );

        $shipment->setShipmentDetails($response->shipmentDetails);

        return $shipment;
    }

    /**
     * @throws Exception
     */
    public function packagePDF(string $trackingNumber): void
    {
        $response = $this->client->executeRequest(
            self::API_URL,
            $this->shipmentMapper->mapRequestData(
                $this->apiKey,
                self::GET_SHIPMENT_LABEL_COMMAND,
                ['TrackingNumber' => $trackingNumber]
            )
        );
        $label = $this->labelService->getLabel($response->shipmentDetails);
        echo $label;
        exit();
    }
}

readonly class LabelService
{
    /**
     * @throws Exception
     */
    public function getLabel(ShipmentDetails $shipmentDetails): string
    {
        $label = match ($shipmentDetails->labelFormat) {
            'PDF' => $this->decodePDFLabelFromResponse($shipmentDetails->labelImage),
            default => throw new Exception('Label Type not supported')
        };
        $this->setHeadersForBrowser($label, $shipmentDetails->trackingNumber);

        return $label;
    }
    /**
     * @throws Exception
     */
    private function decodePDFLabelFromResponse(string $labelImage): string
    {
        $pdfContent = base64_decode($labelImage);

        if ($pdfContent === false) {
            throw new Exception('PDF content is empty');
        }

        return $pdfContent;
    }

    private function setHeadersForBrowser(string $label, string $trackingNumber): void
    {
        header('Content-Type: application/pdf');
        header(sprintf('Content-Disposition: attachment; filename="%s_label.pdf"', $trackingNumber));
        header('Content-Transfer-Encoding: binary');
        header(sprintf('Content-Length: %d', strlen($label)));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
}

readonly class ShipmentMapper
{
    public function mapShipment(array $order, array $params): Shipment
    {
        return new Shipment(
            shipperReference: $order['shipperReference'] ?? '',
            service: $params['service'] ?? '',
            weight: $order['weight'] ?? '',
            value: $order['value'] ?? '',
            consignorAddress: ConsignorAddress::fromArray($order),
            consigneeAddress: ConsigneeAddress::fromArray($order),
            products: $order['products'] ?? []
        );
    }

    public function mapRequestData(string $apiKey, string $apiCommand, array $data): array
    {
        return [
            'Apikey' => $apiKey,
            'Command' => $apiCommand,
            'Shipment' => $data
        ];
    }
}

readonly class ConsignorAddress implements JsonSerializable
{
    private function __construct(
        public string $name,
        public string $company,
        public string $addressLine1,
        public string $addressLine2,
        public string $addressLine3,
        public string $city,
        public string $state,
        public string $zip,
        public string $country,
        public string $phone,
        public string $email,
        public string $vat,
        public string $eori,
        public string $nlVat,
        public string $euEori,
        public string $ioss
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['senderFullname'] ?? '',
            company: $data['senderCompany'] ?? '',
            addressLine1: $data['senderAddress'] ?? '',
            addressLine2: $data['senderAddress'] ?? '',
            addressLine3: $data['senderAddress'] ?? '',
            city: $data['senderCity'] ?? '',
            state: $data['senderState'] ?? '',
            zip: $data['senderZip'] ?? '',
            country: $data['senderCountry'] ?? '',
            phone: $data['senderPhone'] ?? '',
            email: $data['senderEmail'] ?? '',
            vat: $data['senderVat'] ?? '',
            eori: $data['senderEori'] ?? '',
            nlVat: $data['senderNlVat'] ?? '',
            euEori: $data['senderEuEori'] ?? '',
            ioss: $data['senderIoss'] ?? ''
        );
    }

    public function jsonSerialize(): array
    {
        $allFields = [
            'Name' => $this->name,
            'Company' => $this->company,
            'AddressLine1' => $this->addressLine1,
            'AddressLine2' => $this->addressLine2,
            'AddressLine3' => $this->addressLine3,
            'City' => $this->city,
            'State' => $this->state,
            'Zip' => $this->zip,
            'Country' => $this->country,
            'Phone' => $this->phone,
            'Email' => $this->email,
            'Vat' => $this->vat,
            'Eori' => $this->eori,
            'NlVat' => $this->nlVat,
            'EuEori' => $this->euEori,
            'Ioss' => $this->ioss
        ];

        return array_filter($allFields, function ($value) {
            return !empty($value);
        });
    }
}

readonly class ConsigneeAddress implements JsonSerializable
{
    public function __construct(
        public string $name,
        public string $country,
        public string $phone,
        public string $email,
        public string $city,
        public string $addressLine1,
        public ?string $addressLine2 = null,
        public ?string $addressLine3 = null,
        public ?string $company = null,
        public ?string $state = null,
        public ?string $zip = null,
        public ?string $vat = null,
        public ?string $pudoLocationId = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['deliveryFullname'] ?? '',
            country: $data['deliveryCountry'] ?? '',
            phone: $data['deliveryPhone'] ?? '',
            email: $data['deliveryEmail'] ?? '',
            city: $data['deliveryCity'] ?? '',
            addressLine1: $data['deliveryAddress'] ?? '',
            addressLine2: $data['deliveryAddress'] ?? '',
            addressLine3: $data['deliveryAddress'] ?? '',
            company: $data['deliveryCompany'] ?? '',
            state: $data['deliveryState'] ?? '',
            zip: $data['deliveryPostalCode'] ?? '',
            vat: $data['deliveryVat'] ?? '',
            pudoLocationId: $data['deliveryPudoLocationId'] ?? ''
        );
    }

    public function jsonSerialize(): array
    {
        $allFields = [
            'Name' => $this->name,
            'Country' => $this->country,
            'Phone' => $this->phone,
            'Email' => $this->email,
            'City' => $this->city,
            'AddressLine1' => $this->addressLine1,
            'AddressLine2' => $this->addressLine2,
            'AddressLine3' => $this->addressLine3,
            'Company' => $this->company,
            'State' => $this->state,
            'Zip' => $this->zip,
            'Vat' => $this->vat,
            'PudoLocationId' => $this->pudoLocationId
        ];

        return array_filter($allFields, function ($value) {
            return !empty($value);
        });
    }
}

class Shipment implements JsonSerializable
{
    private ShipmentDetails $shipmentDetails;

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
        $data = [
            'ShipperReference' => $this->shipperReference,
            'Service' => $this->service,
            'Weight' => $this->weight,
            'LabelFormat' => $this->labelFormat,
            'WeightUnit' => $this->weightUnit,
            'Currency' => $this->currency,
            'CustomsDuty' => $this->customsDuty,
            'DeclarationType' => $this->declarationType,
            'DangerousGoods' => $this->dangerousGoods,
            'ConsigneeAddress' => $this->consigneeAddress->jsonSerialize(),
            'ConsignorAddress' => $this->consignorAddress->jsonSerialize(),
            'Products' => $this->products,
        ];

        if (!empty($this->value)) {
            $data['value'] = $this->value;
        }

        $optionalFields = [
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

        foreach ($optionalFields as $key => $value) {
            if ($value !== null) {
                $data[$key] = $value;
            }
        }

        return $data;
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

readonly class Product implements JsonSerializable
{
    private function __construct(
        private string $description,
        private string $sku,
        private string $hsCode,
        private string $originCountry,
        private string $imgUrl,
        private string $purchaseUrl,
        private string $quantity,
        private string $value,
        private string $weight,
        private ?string $daysForReturn = null,
        private ?string $nonReturnable = null
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            description: $data['description'],
            sku: $data['sku'] ?? '',
            hsCode: $data['hsCode'],
            originCountry: $data['originCountry'] ?? '',
            imgUrl: $data['imgUrl'] ?? '',
            purchaseUrl: $data['purchaseUrl'] ?? '',
            quantity: $data['quantity'],
            value: $data['value'],
            weight: $data['weight'],
            daysForReturn: $data['daysForReturn'] ?? null,
            nonReturnable: $data['nonReturnable'] ?? null
        );
    }

    public function jsonSerialize(): array
    {
        $data = [
            'Description' => $this->description,
            'Sku' => $this->sku,
            'HsCode' => $this->hsCode,
            'OriginCountry' => $this->originCountry,
            'ImgUrl' => $this->imgUrl,
            'PurchaseUrl' => $this->purchaseUrl,
            'Quantity' => $this->quantity,
            'Value' => $this->value,
            'Weight' => $this->weight
        ];

        if ($this->daysForReturn !== null) {
            $data['DaysForReturn'] = $this->daysForReturn;
        }
        if ($this->nonReturnable !== null) {
            $data['NonReturnable'] = $this->nonReturnable;
        }

        return $data;
    }
}

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

readonly class ApiClient
{
    /**
     * @throws Exception
     */
    public function executeRequest(string $apiUrl, array $data): ShipmentApiResponse
    {
        $jsonData = $this->prepareJsonPayload($data);
        $curlHandle = $this->initializeCurlRequest($apiUrl, $jsonData);

        $response = $this->sendCurlRequest($curlHandle);
        $this->validateCurlExecution($curlHandle);

        $responseData = ShipmentApiResponse::fromArray($this->parseResponse($response));
        $this->validateApiResponse($responseData);
        curl_close($curlHandle);

        return $responseData;
    }

    private function prepareJsonPayload(array $data): string
    {
        return json_encode($data);
    }

    private function initializeCurlRequest(string $apiUrl, string $jsonData): CurlHandle
    {
        $curlHandle = curl_init($apiUrl);

        curl_setopt_array($curlHandle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json']
        ]);

        return $curlHandle;
    }

    /**
     * @throws Exception
     */
    private function sendCurlRequest(CurlHandle $curlHandle): string
    {
        $response = curl_exec($curlHandle);

        if ($response === false) {
            throw new Exception('cURL request failed');
        }

        return $response;
    }

    /**
     * @throws Exception
     */
    private function validateCurlExecution(CurlHandle $curlHandle): void
    {
        $curlError = curl_errno($curlHandle);

        if ($curlError !== 0) {
            throw new Exception(
                sprintf('cURL error %d: %s', $curlError, curl_error($curlHandle))
            );
        }
    }

    /**
     * @throws Exception
     */
    private function parseResponse(string $response): array
    {
        $parsedResponse = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response');
        }

        return $parsedResponse;
    }

    /**
     * @throws Exception
     */
    private function validateApiResponse(ShipmentApiResponse $response): void
    {
        if ($response->errorLevel !== 0) {
            throw new Exception(
                sprintf('API error: %s', json_encode($response->error))
            );
        }
    }
}



