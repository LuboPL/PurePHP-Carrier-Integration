<?php
declare(strict_types=1);

readonly class SpringCourier
{
    private const string API_URL = 'https://mtapi.net/?testMode=1';
    private const string CREATE_ORDER_SHIPMENT_COMMAND = 'OrderShipment';
    private const string GET_SHIPMENT_LABEL_COMMAND = 'GetShipmentLabel';

    private ApiClient $client;
    private LabelService $labelService;
    private ShipmentMapper $shipmentMapper;

    public function __construct(private string $apiKey)
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
                self::CREATE_ORDER_SHIPMENT_COMMAND,
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
        $label = $this->labelService->printLabel($response->shipmentDetails);
        echo $label;
        exit();
    }
}

readonly class LabelService
{
    /**
     * @throws Exception
     */
    public function printLabel(ShipmentDetails $shipmentDetails): string
    {
        $label = $this->getLabelByShipmentDetails($shipmentDetails);
        $this->setHeadersForBrowser($label, $shipmentDetails->trackingNumber);

        return $label;
    }

    /**
     * @throws Exception
     */
    private function getLabelByShipmentDetails(ShipmentDetails $shipmentDetails): string
    {
        $handlers = [
            'PDF' => fn($shipment) => $this->decodePDFLabelFromResponse($shipment->labelImage),
            // 'ZPL' => fn($shipment) => $this->decodeZPLLabelFromResponse($shipment->labelImage),
            // ...
        ];
        $handler = $handlers[$shipmentDetails->labelFormat] ?? throw new Exception('Label type not supported');

        return $handler($shipmentDetails);
    }

    /**
     * @throws Exception
     */
    private function decodePDFLabelFromResponse(string $labelImage): string
    {
        $pdfContent = base64_decode($labelImage);

        return $pdfContent !== false ? $pdfContent : throw new Exception('PDF content is empty');
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

    public function mapRequestData(string $apiKey, string $apiCommand, array $data): array
    {
        return [
            'Apikey' => $apiKey,
            'Command' => $apiCommand,
            'Shipment' => $data
        ];
    }

    private function mapProducts(array $order): array
    {
        return array_map(
            fn(array $product) => (new Product(
                description: $product['description'],
                sku: $product['sku'] ?? '',
                hsCode: $product['hsCode'],
                originCountry: $product['originCountry'] ?? '',
                imgUrl: $product['imgUrl'] ?? '',
                purchaseUrl: $product['purchaseUrl'] ?? '',
                quantity: $product['quantity'],
                value: $product['value'],
                weight: $product['weight'],
                daysForReturn: $product['daysForReturn'] ?? null,
                nonReturnable: $product['nonReturnable'] ?? null
            ))->jsonSerialize(),
            $order['products'] ?? []
        );
    }
}

abstract class BaseAddress implements JsonSerializable
{
    public function __construct(
        public readonly string $name,
        public readonly string $country,
        public readonly string $phone,
        public readonly string $email,
        public readonly string $city,
        public string $addressLine1,
        public ?string $addressLine2 = null,
        public ?string $addressLine3 = null,
        public ?string $company = null,
        public readonly ?string $state = null,
        public readonly ?string $zip = null,
        public readonly ?string $vat = null
    ) {
        $this->normalizeAddressLines();
    }

    protected function normalizeAddressLines(): void
    {
        if (strlen($this->addressLine1) <= 30) return;

        $fullAddress = $this->addressLine1;
        $this->addressLine1 = substr($fullAddress, 0, 30);
        strlen($fullAddress) > 30 && $this->addressLine2 = substr($fullAddress, 30, 30);
        strlen($fullAddress) > 60 && $this->addressLine3 = substr($fullAddress, 60, 30);
    }

    public function jsonSerialize(): array
    {
        $baseFields = [
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
            'Vat' => $this->vat
        ];

        return array_filter($baseFields, fn($value) => $value !== null);
    }
}

class ConsignorAddress extends BaseAddress
{
    public function __construct(
        string $name,
        string $country,
        string $phone,
        string $email,
        string $city,
        string $addressLine1,
        ?string $addressLine2 = null,
        ?string $addressLine3 = null,
        ?string $company = null,
        ?string $state = null,
        ?string $zip = null,
        ?string $vat = null,
        public readonly ?string $eori = null,
        public readonly ?string $nlVat = null,
        public readonly ?string $euEori = null,
        public readonly ?string $ioss = null
    ) {
        parent::__construct(
            $name, $country, $phone, $email, $city,
            $addressLine1, $addressLine2, $addressLine3,
            $company, $state, $zip, $vat
        );
    }

    public static function fromArray(array $data): self
    {
        $addressLine = $data['senderAddress'] ?? '';

        return new self(
            name: $data['senderFullname'] ?? '',
            country: $data['senderCountry'] ?? '',
            phone: $data['senderPhone'] ?? '',
            email: $data['senderEmail'] ?? '',
            city: $data['senderCity'] ?? '',
            addressLine1: $addressLine,
            addressLine2: $data['senderAddress2'] ?? null,
            addressLine3: $data['senderAddress3'] ?? null,
            company: $data['senderCompany'] ?? null,
            state: $data['senderState'] ?? null,
            zip: $data['senderZip'] ?? null,
            vat: $data['senderVat'] ?? null,
            eori: $data['senderEori'] ?? null,
            nlVat: $data['senderNlVat'] ?? null,
            euEori: $data['senderEuEori'] ?? null,
            ioss: $data['senderIoss'] ?? null
        );
    }

    public function jsonSerialize(): array
    {
        $baseFields = parent::jsonSerialize();
        $specificFields = [
            'Eori' => $this->eori,
            'NlVat' => $this->nlVat,
            'EuEori' => $this->euEori,
            'Ioss' => $this->ioss
        ];

        return array_filter(array_merge($baseFields, $specificFields), fn($value) => $value !== null);
    }
}

class ConsigneeAddress extends BaseAddress
{
    public function __construct(
        string $name,
        string $country,
        string $phone,
        string $email,
        string $city,
        string $addressLine1,
        ?string $addressLine2 = null,
        ?string $addressLine3 = null,
        ?string $company = null,
        ?string $state = null,
        ?string $zip = null,
        ?string $vat = null,
        public readonly ?string $pudoLocationId = null
    ) {
        parent::__construct(
            $name, $country, $phone, $email, $city,
            $addressLine1, $addressLine2, $addressLine3,
            $company, $state, $zip, $vat
        );
    }

    public static function fromArray(array $data): self
    {
        $addressLine = $data['deliveryAddress'] ?? '';

        return new self(
            name: $data['deliveryFullname'] ?? '',
            country: $data['deliveryCountry'] ?? '',
            phone: $data['deliveryPhone'] ?? '',
            email: $data['deliveryEmail'] ?? '',
            city: $data['deliveryCity'] ?? '',
            addressLine1: $addressLine,
            addressLine2: $data['deliveryAddress2'] ?? null,
            addressLine3: $data['deliveryAddress3'] ?? null,
            company: $data['deliveryCompany'] ?? null,
            state: $data['deliveryState'] ?? null,
            zip: $data['deliveryPostalCode'] ?? null,
            vat: $data['deliveryVat'] ?? null,
            pudoLocationId: $data['deliveryPudoLocationId'] ?? null
        );
    }

    public function jsonSerialize(): array
    {
        $baseFields = parent::jsonSerialize();
        $specificFields = ['PudoLocationId' => $this->pudoLocationId];

        return array_filter(array_merge($baseFields, $specificFields), fn($value) => $value !== null);
    }
}

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

        !empty($this->value) && $data['Value'] = $this->value;

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

        return array_merge($data, array_filter($optionalFields, fn($value) => $value !== null));
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
    public function __construct(
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

        $this->daysForReturn !== null && $data['DaysForReturn'] = $this->daysForReturn;
        $this->nonReturnable !== null && $data['NonReturnable'] = $this->nonReturnable;

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
    public function __construct(private int $curlTimeout = 30)
    {
    }

    /**
     * @throws Exception
     */
    public function executeRequest(string $apiUrl, array $data): ShipmentApiResponse
    {
        $jsonData = json_encode($data);
        $curlHandle = $this->initializeCurlPostRequest($apiUrl, $jsonData);

        $response = $this->sendCurlRequest($curlHandle);
        $this->validateCurlExecution($curlHandle);

        $responseData = ShipmentApiResponse::fromArray($this->parseResponse($response));
        $this->validateApiResponse($responseData);
        curl_close($curlHandle);

        return $responseData;
    }

    private function initializeCurlPostRequest(string $apiUrl, string $jsonData): CurlHandle
    {
        $curlHandle = curl_init($apiUrl);

        curl_setopt_array($curlHandle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => $this->curlTimeout
        ]);

        return $curlHandle;
    }

    /**
     * @throws Exception
     */
    private function sendCurlRequest(CurlHandle $curlHandle): string
    {
        $response = curl_exec($curlHandle);

        return $response !== false ? $response : throw new Exception('cURL request failed');
    }

    /**
     * @throws Exception
     */
    private function validateCurlExecution(CurlHandle $curlHandle): void
    {
        $curlError = curl_errno($curlHandle);
        $curlError !== 0 && throw new Exception(sprintf('cURL error %d: %s', $curlError, curl_error($curlHandle)));
    }

    /**
     * @throws Exception
     */
    private function parseResponse(string $response): array
    {
        return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws Exception
     */
    private function validateApiResponse(ShipmentApiResponse $response): void
    {
        $errorMap = [
            1 => 'Command completed with errors: %s',
            10 => 'Fatal error, command is not completed at all: %s',
        ];

        isset($errorMap[$response->errorLevel]) && throw new Exception(
            sprintf(
                $errorMap[$response->errorLevel],
                json_encode($response->error)
            )
        );
    }
}



