<?php
declare(strict_types=1);

namespace App\SpringShipment\Model\Address;

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
    )
    {
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

        return array_merge($baseFields, $specificFields);
    }
}