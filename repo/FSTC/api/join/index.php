<?php

include '../../bot/config.php';
include '../../bot/functions.php';

$MySQLi = new mysqli($DB['host'],$DB['username'],$DB['password'],$DB['dbname']);
$MySQLi->query("SET NAMES 'utf8'");
$MySQLi->set_charset('utf8mb4');
if ($MySQLi->connect_error) die;
function ToDie($MySQLi){
    $MySQLi->close();
    die;
}

function validate_telegram_hash($telegram_data, $bot_token, $received_hash) {
    $data = [
        'auth_date' => $telegram_data['auth_date'],
        'query_id' => $telegram_data['query_id'],
        'user' => $telegram_data['user'],
    ];
    $data_check_string = '';
    ksort($data);
    foreach ($data as $key => $value) {
        $data_check_string .= "$key=$value\n";
    }
    $data_check_string = rtrim($data_check_string, "\n");
    $secret_key = hash_hmac('sha256', $bot_token, 'WebAppData', true);
    $computed_hash = hash_hmac('sha256', $data_check_string, $secret_key);
    return $computed_hash == $received_hash;
}

$headers = file_get_contents('php://input');

parse_str($headers, $telegram_data);
if (!isset($telegram_data['user'], $telegram_data['hash']) || !validate_telegram_hash($telegram_data, $apiKey, $telegram_data['hash'])) {
    http_response_code(422);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'message' => 'invalid request']);
    $MySQLi->close();
    die;
}

$user_id = (int) json_decode($telegram_data['user'], true)['id'];
$hash = $telegram_data['hash'];

$getUserStmt = $MySQLi->prepare('SELECT * FROM `users` WHERE `id` = ? LIMIT 1');
$getUserStmt->bind_param('s', (string)$user_id);
$getUserStmt->execute();
$getUser = $getUserStmt->get_result()->fetch_assoc();

if (!$getUser) {
    $username = json_decode($telegram_data['user'], true)['username'] ?? '';
    $newUserStmt = $MySQLi->prepare("INSERT INTO `users` (`id`, `username`, `score`, `streak`, `isPremium`, `dailyReward`, `dailyRewardDate`, `lastSeenDate`) VALUES (?, ?, 0, 0, 0, 0, 0, NOW())");
    $newUserStmt->bind_param('ss', (string)$user_id, $username);
    if ($newUserStmt->execute()) {
        $getUserStmt->execute();
        $getUser = $getUserStmt->get_result()->fetch_assoc();
    } else {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'message' => 'Failed to create new user']);
        $MySQLi->close();
        die;
    }
}

if ($getUser['step'] == 'banned') {
    http_response_code(422);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'message' => 'invalid request']);
    $MySQLi->close();
    die;
}

if ((int)$getUser['dailyRewardDate'] + 86400 <= time()) {
    $streak = (int)$getUser['streak'] + 1;
    $streak_reward = $streak <= 7 ? 700 + ($streak * 100) : 1400;
    $now = time();
    $update = $MySQLi->prepare('UPDATE `users` SET `score` = `score` + ?, `dailyReward` = `dailyReward` + ?, `dailyRewardDate` = ?, `streak` = ? WHERE `id` = ? LIMIT 1');
    $update->bind_param('iiiis', $streak_reward, $streak_reward, $now, $streak, (string)$user_id);
    $update->execute();
    $getUserStmt->execute();
    $getUser = $getUserStmt->get_result()->fetch_assoc();
}

$last_seen = (new DateTime())->format('Y-m-d\TH:i:s.u\Z');
$updateHash = $MySQLi->prepare('UPDATE `users` SET `hash` = ?, `tdata` = ?, `lastSeenDate` = ? WHERE `id` = ? LIMIT 1');
$tdata = urlencode($headers);
$updateHash->bind_param('sssi', $hash, $tdata, $last_seen, (string)$user_id);
$updateHash->execute();

header('Content-Type: application/json');
$MySQLi->close();
echo json_encode([
    'telegram_id' => (int)$getUser['id'],
    'username' => $getUser['username'],
    'age' => (int)$getUser['age'],
    'is_premium' => (int)$getUser['isPremium'] === 1,
    'balance' => (int)$getUser['score'],
    'reference' => $hash,
    'avatar' => '',
    'top_group' => 5,
    'top_percent' => 25,
    'wallet' => $getUser['wallet'],
    'streak' => (int)$getUser['streak'],
    'last_seen' => $last_seen
]);