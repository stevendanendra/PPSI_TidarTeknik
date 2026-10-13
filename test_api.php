<?php
$data = [
    [
        'id' => 'test-id-123',
        'nomor' => 'INV-001',
        'tanggal' => '2026-05-01',
        'customer' => 'Test Customer',
        'items' => [
            ['qty' => 1, 'price' => 1000, 'amount' => 1000, 'desc' => 'Item 1']
        ]
    ]
];
$ch = curl_init('http://localhost/TidarTeknik/api.php?action=save_nota');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
$response = curl_exec($ch);
echo "Response: " . $response;
?>
