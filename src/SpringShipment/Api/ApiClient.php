<?php
declare(strict_types=1);

namespace App\SpringShipment\Api;

use App\SpringShipment\Enum\ShipmentError;
use App\SpringShipment\Exception\SpringShipmentException;
use CurlHandle;
use JsonException;

readonly class ApiClient implements ClientInterface
{
    public function __construct(private string $apiKey, private int $curlTimeout = 30)
    {
    }

    /**
     * @throws SpringShipmentException|JsonException
     */
    public function executeRequest(string $apiUrl, array $data): ShipmentApiResponse
    {
        $data['Apikey'] = $this->apiKey;
        $jsonData = json_encode($this->removeEmptyValues($data));
        $curlHandle = $this->initializeCurlPostRequest($apiUrl, $jsonData);

        $response = $this->sendCurlRequest($curlHandle);
        $this->validateCurlExecution($curlHandle);

        $responseData = ShipmentApiResponse::fromArray($this->parseResponse($response));
        $this->validateApiResponse($responseData);
        curl_close($curlHandle);

        return $responseData;
    }

    private function removeEmptyValues(array $array): array
    {
        $array = array_map(fn($item) => is_array($item) ? $this->removeEmptyValues($item) : $item, $array);
        return array_filter($array, fn($item) => is_array($item) ? !empty($item) : $item !== null && $item !== '');
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
     * @throws SpringShipmentException
     */
    private function sendCurlRequest(CurlHandle $curlHandle): string
    {
        $response = curl_exec($curlHandle);

        return $response !== false ? $response : throw new SpringShipmentException('cURL request failed');
    }

    /**
     * @throws SpringShipmentException
     */
    private function validateCurlExecution(CurlHandle $curlHandle): void
    {
        $curlError = curl_errno($curlHandle);
        $curlError !== 0 && throw new SpringShipmentException(sprintf('cURL error %d: %s', $curlError, curl_error($curlHandle)));
    }

    /**
     * @throws JsonException
     */
    private function parseResponse(string $response): array
    {
        return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws SpringShipmentException
     */
    private function validateApiResponse(ShipmentApiResponse $response): void
    {
        if ($response->errorLevel === 0) return;

        $error = ShipmentError::tryFrom($response->errorLevel)
            ?? throw new SpringShipmentException(sprintf('Undefined error code: %d', $response->errorLevel));

        throw new SpringShipmentException(sprintf($error->getMessage(), json_encode($response->error)));
    }
}