<?php
declare(strict_types=1);

namespace App\SpringShipment\Model\Address;

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

        return array_merge($baseFields, $specificFields);
    }
}