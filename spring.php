<?php
declare(strict_types=1);

include __DIR__ . "/SpringCourier.php";

$order = [
    'senderCompany' => 'BaseLinker',
    'senderFullname' => 'Jan Kowalski',
    'senderAddress' => 'Kopernika 10',
    'senderCity' => 'Gdansk',
    'senderPostalCode' => '80208',
    'senderEmail' => '',
    'senderPhone' => '666666666',

    'deliveryCompany' => 'Spring GDS',
    'deliveryFullname' => 'Maud Driant',
    'deliveryAddress' => 'Strada Foisorului, Nr. 16, Bl. F11C, Sc. 1, Ap. 10',
    'deliveryCity' => 'Bucuresti, Sector 3',
    'deliveryPostalCode' => '031179',
    'deliveryCountry' => 'RO',
    'deliveryEmail' => 'john@doe.com',
    'deliveryPhone' => '555555555',
    // If you want to add products
    'products' => [
    ]
];

$params = [
    'apiKey' => 'ed1e2e1567b781d6',
    'labelFormat' => 'PDF',
    'service' => 'EXPR',
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






