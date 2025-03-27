<?php
declare(strict_types=1);

class SpringCourier
{
    private const string API_URL = 'https://mtapi.net/?testMode=1';
    private const string ORDER_SHIPMENT_COMMAND = 'OrderShipment';
    private const string GET_SHIPMENT_LABEL_COMMAND = 'GetShipmentLabel';
    private string $label;
    public readonly ?string $trackingNumber;

    public function __construct(private readonly string $apiKey)
    {
    }

    /**
     * @throws Exception
     */
    public function newPackage(array $order, array $params): void
    {
        $response = $this->executeRequest(
            [
                'Apikey' => $this->apiKey,
                'Command' => self::ORDER_SHIPMENT_COMMAND,
                'Shipment' => $this->prepareShipmentRequestData($order, $params)
            ]
        );
        $this->trackingNumber = $response['Shipment']['TrackingNumber'];
    }

    /**
     * @throws Exception
     */
    public function packagePDF(?string $trackingNumber): void
    {
        if ($trackingNumber === null) {
            throw new Exception('TrackingNumber is null');
        }

        $response = $this->executeRequest(
            [
                'Apikey' => $this->apiKey,
                'Command' => self::GET_SHIPMENT_LABEL_COMMAND,
                'Shipment' => [
                    'TrackingNumber' => $trackingNumber
                ]
            ]
        );

        $this->decodePDFLabelFromResponse($response);
        $this->setHeadersForBrowser();

        echo $this->label;
        exit();
    }

    /**
     * @throws Exception
     */
    private function decodePDFLabelFromResponse(array $response): void
    {
        $pdfContent = base64_decode($response['Shipment']['LabelImage']);

        if ($pdfContent === false) {
            throw new Exception('PDF content is empty');
        }

        $this->label = $pdfContent;
    }

    private function setHeadersForBrowser(): void
    {
        header('Content-Type: application/pdf');
        header(sprintf('Content-Disposition: attachment; filename="%s_label.pdf"', $this->trackingNumber));
        header('Content-Transfer-Encoding: binary');
        header(sprintf('Content-Length: %d', strlen($this->label)));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    private function prepareShipmentRequestData(array $order, array $params): array
    {
        return [
            'Service' => $params['service'] ?? null,
            'LabelFormat' => $params['labelFormat'] ?? null,
            'ShipperReference' => $order['shipperReference'] ?? null,
            'Weight' => $order['weight'] ?? null,
            'WeightUnit' => $order['weightUnit'] ?? null,
            'Value' => $order['value'] ?? null,
            'ConsignorAddress' => [
                'Name' => $order['senderFullname'] ?? null,
                'Company' => $order['senderCompany'] ?? null,
                'AddressLine1' => $order['senderAddress'] ?? null,
                'City' => $order['senderCity'] ?? null,
                'Zip' => $order['senderPostalCode'] ?? null,
                'Phone' => $order['senderPhone'] ?? null,
                'Email' => $order['senderEmail'] ?? null,
            ],
            'ConsigneeAddress' => [
                'Name' => $order['deliveryFullname'] ?? null,
                'Company' => $order['deliveryCompany'] ?? null,
                'AddressLine1' => $order['deliveryAddress'] ?? null,
                'City' => $order['deliveryCity'] ?? null,
                'Zip' => $order['deliveryPostalCode'] ?? null,
                'Country' => $order['deliveryCountry'] ?? null,
                'Phone' => $order['deliveryPhone'] ?? null,
                'Email' => $order['deliveryEmail'] ?? null,
            ],
            'Products' => [
                'Description' => $order['productDescription'] ?? null,
                'HsCode' => $order['productHsCode'] ?? null,
                'Quantity' => $order['productQuantity'] ?? null,
                'Value' => $order['productValue'] ?? null,
                'Weight' => $order['productWeight'] ?? null,
            ]
        ];
    }

    /**
     * @throws Exception
     */
    private function executeRequest(array $data): array
    {
        $jsonData = $this->prepareJsonPayload($data);
        $curlHandle = $this->initializeCurlRequest($jsonData);

        $response = $this->sendCurlRequest($curlHandle);
        $this->validateCurlExecution($curlHandle);

        $responseData = $this->parseResponse($response);
        $this->validateApiResponse($responseData);
        curl_close($curlHandle);

        return $responseData;
    }

    private function prepareJsonPayload(array $data): string
    {
        return json_encode($data);
    }

    private function initializeCurlRequest(string $jsonData): CurlHandle
    {
        $curlHandle = curl_init(self::API_URL);

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
    private function validateApiResponse(array $responseData): void
    {
        if ($responseData['ErrorLevel'] !== 0) {
            throw new Exception(
                sprintf('API error: %s', json_encode($responseData))
            );
        }
    }
}



