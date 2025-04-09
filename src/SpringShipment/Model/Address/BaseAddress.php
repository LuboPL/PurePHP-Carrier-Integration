<?php
declare(strict_types=1);

namespace App\SpringShipment\Model\Address;

use JsonSerializable;

abstract class BaseAddress implements JsonSerializable
{
    public function __construct(
        public readonly string  $name,
        public readonly string  $country,
        public readonly string  $phone,
        public readonly string  $email,
        public readonly string  $city,
        public string $addressLine1,
        public ?string $addressLine2 = null,
        public ?string $addressLine3 = null,
        public ?string $company = null,
        public readonly ?string $state = null,
        public readonly ?string $zip = null,
        public readonly ?string $vat = null
    )
    {
        $this->normalizeAddressLines();
    }

    protected function normalizeAddressLines(): void
    {
        if (strlen($this->addressLine1) <= 35) return;

        $lines = explode("\n", wordwrap($this->addressLine1, 35, "\n", true));

        $this->addressLine1 = $lines[0] ?? '';
        $this->addressLine2 = $lines[1] ?? '';
        $this->addressLine3 = $lines[2] ?? '';
    }

    public function jsonSerialize(): array
    {
        return [
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
    }
}