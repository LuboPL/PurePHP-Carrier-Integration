<?php
declare(strict_types=1);

namespace App\SpringShipment\Model\Product;

use JsonSerializable;

readonly class Product implements JsonSerializable
{
    public function __construct(
        private string  $description,
        private string  $sku,
        private string  $hsCode,
        private string  $originCountry,
        private string  $imgUrl,
        private string  $purchaseUrl,
        private string  $quantity,
        private string  $value,
        private string  $weight,
        private ?string $daysForReturn = null,
        private ?string $nonReturnable = null
    )
    {
    }

    public function jsonSerialize(): array
    {
        return [
            'Description' => $this->description,
            'Sku' => $this->sku,
            'HsCode' => $this->hsCode,
            'OriginCountry' => $this->originCountry,
            'ImgUrl' => $this->imgUrl,
            'PurchaseUrl' => $this->purchaseUrl,
            'Quantity' => $this->quantity,
            'Value' => $this->value,
            'Weight' => $this->weight,
            'DaysForReturn' => $this->daysForReturn,
            'NonReturnable' => $this->nonReturnable,
        ];
    }
}