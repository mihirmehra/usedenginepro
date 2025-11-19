<?php
// Simple test harness to POST JSON to send_mail.php on the local server.
// Usage: php test_send_mail.php

$endpoint = 'http://localhost:8000/send_mail.php';

$sample = [
    'pickupLocation' => 'Los Angeles International Airport (LAX)',
    'dropoffLocation' => 'San Diego International Airport (SAN)',
    'pickupDate' => '2025-12-01',
    'pickupTime' => '10:00',
    'returnDate' => '2025-12-05',
    'returnTime' => '16:00',
    'renterAge' => '30',
    'corporateID' => 'ACME-1234'
];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($sample),
        'ignore_errors' => true,
    ],
];
$context  = stream_context_create($options);

echo "Posting sample reservation to $endpoint\n";
$result = file_get_contents($endpoint, false, $context);
if ($result === false) {
    echo "Request failed\n";
    exit(1);
}

echo "Response: \n";
echo $result . "\n";

?>
