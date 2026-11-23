<?php

include '../../../bot/config.php';
include '../../../bot/functions.php';

$MySQLi = new mysqli($DB['host'],$DB['username'],$DB['password'],$DB['dbname']);
$MySQLi->query("SET NAMES 'utf8'");
$MySQLi->set_charset('utf8mb4');
if ($MySQLi->connect_error) die;
function ToDie($MySQLi){
    $MySQLi->close();
    die;
}


$update = json_decode(file_get_contents('php://input'), true);
if (!$update || empty($update['proof']['payload']) || empty($update['wallet']['address'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['connected' => false, 'error_code' => 'invalid payload']);
    $MySQLi->close();
    die;
}

$payload = $update['proof']['payload'];
$stmt = $MySQLi->prepare('SELECT * FROM `users` WHERE `walletOTP` = ? LIMIT 1');
$stmt->bind_param('s', $payload);
$stmt->execute();
$get_user = $stmt->get_result()->fetch_assoc();

if (!$get_user) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['connected' => false, 'error_code' => 'invalid payload']);
    $MySQLi->close();
    die;
}

$addressParts = explode(':', $update['wallet']['address']);
if (count($addressParts) !== 2) {
    http_response_code(400);
    echo json_encode(['connected' => false, 'error_code' => 'invalid wallet address']);
    $MySQLi->close();
    die;
}
$wallet = $addressParts[1];

$updateStmt = $MySQLi->prepare('UPDATE `users` SET `wallet` = ?, `walletOTP` = NULL WHERE `id` = ? LIMIT 1');
$updateStmt->bind_param('ss', $wallet, $get_user['id']);
$updateStmt->execute();

if ((int)$get_user['walletReward'] === 0) {
    $rewardStmt = $MySQLi->prepare('UPDATE `users` SET `score` = `score` + 15000, `walletReward` = 15000 WHERE `id` = ? LIMIT 1');
    $rewardStmt->bind_param('s', $get_user['id']);
    $rewardStmt->execute();
}

header('Content-Type: application/json');
$MySQLi->close();
echo json_encode(['connected' => true]);