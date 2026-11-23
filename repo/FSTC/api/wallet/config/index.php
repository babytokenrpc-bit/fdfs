<?php
include '../../../bot/config.php';

header('Content-Type: application/json');
echo json_encode([
    'recipient_address' => $onchainRecipient,
    'asset_name' => $assetName,
    'networks' => ['GRAM']
]);
