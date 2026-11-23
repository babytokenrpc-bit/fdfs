<?php
include '../../../../bot/config.php';

header('Content-Type: application/json');

if (!$onchainRecipient) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'ONCHAIN_RECIPIENT_ADDRESS is not configured']);
    die;
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = (int)($input['user_id'] ?? 0);
$reference = (string)($input['reference'] ?? '');
$amountGram = (float)($input['amount_gram'] ?? 0);

$mysqli = new mysqli($DB['host'], $DB['username'], $DB['password'], $DB['dbname']);
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database unavailable']);
    die;
}

$stmt = $mysqli->prepare('SELECT `id`, `wallet` FROM `users` WHERE `id` = ? AND `hash` = ? LIMIT 1');
$stmt->bind_param('is', $userId, $reference);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || !$user['wallet']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Wallet is not connected']);
    $mysqli->close();
    die;
}

if (!in_array($amountGram, [0.2, 0.5], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid GRAM amount']);
    $mysqli->close();
    die;
}

$expectedNano = (int)round($amountGram * 1000000000);
$url = $onchainApiBase . '/v2/accounts/' . rawurlencode($onchainRecipient) . '/transactions?limit=100';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$headers = ['Accept: application/json'];
if ($onchainApiKey !== '') $headers[] = 'Authorization: Bearer ' . $onchainApiKey;
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = json_decode(curl_exec($ch), true);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$verified = false;
foreach (($response['transactions'] ?? []) as $tx) {
    $inMsg = $tx['in_msg'] ?? null;
    if (!$inMsg) continue;
    $value = (int)($inMsg['value'] ?? 0);
    $sender = $inMsg['sender']['address'] ?? '';
    $destination = $inMsg['destination']['address'] ?? '';
    if ($value >= $expectedNano && str_contains($destination, $onchainRecipient)) {
        $verified = true;
        break;
    }
}

if (!$verified) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Verified GRAM transaction not found']);
    $mysqli->close();
    die;
}

$todayCount = $mysqli->prepare('SELECT COUNT(*) AS c FROM `user_payments` WHERE `user_id` = ? AND `payment_status` = ? AND DATE(`payment_date`) = CURDATE()');
$status = 'complete';
$todayCount->bind_param('is', $userId, $status);
$todayCount->execute();
$row = $todayCount->get_result()->fetch_assoc();

if ((int)$row['c'] > 0) {
    echo json_encode(['success' => false, 'message' => 'On-chain task already submitted today']);
    $mysqli->close();
    die;
}

$now = date('Y-m-d H:i:s');
$insert = $mysqli->prepare("INSERT INTO `user_payments` (`user_id`, `payment_amount`, `payment_date`, `payment_status`) VALUES (?, ?, ?, 'complete')");
$insert->bind_param('ids', $userId, $amountGram, $now);
$insert->execute();

$reward = $amountGram === 0.5 ? 35000 : 10000;
$rewardStmt = $mysqli->prepare('UPDATE `users` SET `score` = `score` + ?, `tasksReward` = `tasksReward` + ? WHERE `id` = ? LIMIT 1');
$rewardStmt->bind_param('iis', $reward, $reward, $userId);
$rewardStmt->execute();

$mysqli->close();
echo json_encode(['success' => true, 'amount_gram' => $amountGram, 'reward' => $reward]);
