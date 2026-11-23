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


$user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
$reference = $_GET['reference'] ?? '';

$stmt = $MySQLi->prepare('SELECT `id` FROM `users` WHERE `id` = ? AND `hash` = ? LIMIT 1');
$stmt->bind_param('ss', $user_id, $reference);
$stmt->execute();
$get_user = $stmt->get_result()->fetch_assoc();

if (!$get_user) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'message' => 'user not found']);
    $MySQLi->close();
    die;
}

$randomCode = generateRandomCode();
$expiresAt = time() + 600;
$payload = $randomCode . ':' . $expiresAt;
$update = $MySQLi->prepare('UPDATE `users` SET `walletOTP` = ? WHERE `id` = ? LIMIT 1');
$update->bind_param('ss', $payload, $user_id);
$update->execute();

header('Content-Type: application/json');
$MySQLi->close();
echo json_encode(['data' => $payload]);