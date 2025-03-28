<?php
declare(strict_types=1);

include __DIR__ . "/courier.php";

$order = [
    'senderCompany' => 'BaseLinker',
    'senderFullname' => 'Jan Kowalski',
    'senderAddress' => 'Kopernika 10',
    'senderCity' => 'Gdansk',
    'senderPostalCode' => '80208',
    'senderEmail' => '',
    'senderPhone' => '666666666',

    // Another sender params

//    'senderCountry' => '',
//    'senderAddress2' => '',
//    'senderAddress3' => '',
//    'senderState' => '',
//    'senderVat' => '',
//    'senderEori' => '',
//    'senderNlVat' => '',
//    'senderEuEori' => '',
//    'senderIoss' => '',

    'deliveryCompany' => 'Spring GDS',
    'deliveryFullname' => 'Maud Driant',
    'deliveryAddress' => 'Strada Foisorului, Nr. 16, Bl. F11C, Sc. 1, Ap. 10',
    'deliveryCity' => 'Bucuresti, Sector 3',
    'deliveryPostalCode' => '031179',
    'deliveryCountry' => 'RO',
    'deliveryEmail' => 'john@doe.com',
    'deliveryPhone' => '555555555',

    // Another delivery params

//    'deliveryState' => '',
//    'deliveryVat' => '',
//    'deliveryPudoLocationId'=> '',

    // If you want to add products

//    'products' => [
//        [
//            'description' => '',
//            'daysForReturn' => '',
//            'hsCode' => '',
//            'imgUrl' => '',
//            'nonReturnable' => '',
//            'originCountry' => '',
//            'purchaseUrl' => '',
//            'quantity' => '',
//            'sku' => '',
//            'value' => '',
//            'weight' => '',
//        ]
//    ],

    // Another order params

//    'consigneeAddress' => '',
//    'consignorAddress' => '',
//    'currency' => '',
//    'description' => '',
//    'displayId' => '',
//    'invoiceNumber' => '',
//    'orderDate' => '',
//    'orderReference' => '',
//    'shippingValue' => '',
//    'value' => '',

];

$params = [
    'apiKey' => 'ed1e2e1567b781d6',
    'labelFormat' => 'PDF',
    'service' => 'EXPR',

    // Another Shipment Object params

//    'weight' => '',
//    'weightUnit' => '',
//    'width' => '',
//    'length' => '',
//    'height' => '',
//    'dimUnit' => '',
//    'declarationType' => '',
//    'dangerousGoods' => '',
//    'exportAwb' => '',
//    'exportCarrierName' => '',
//    'customsDuty' => '',
//    'shipperReference' => '',
];

try {
    // 1. Create courier object
    $springCourier = new SpringCourier($params['apiKey']);

    // 2. Create shipment
    $shipment = $springCourier->newPackage($order, $params);

    // 3. Get shipping label and force a download dialog
    $springCourier->packagePDF($shipment->getShipmentDetails()->trackingNumber);

} catch (Exception $exception) {
    echo $exception->getMessage();
}






