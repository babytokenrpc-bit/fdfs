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

$task = $_REQUEST['task'];
$user_id = $_REQUEST['user_id'];
$reference = $_REQUEST['reference'];

$get_user = mysqli_fetch_assoc(mysqli_query($MySQLi, "SELECT * FROM `users` WHERE `id` = '{$user_id}' AND `hash` = '{$reference}' LIMIT 1"));

if(!$get_user){
    http_response_code(300);
    echo json_encode(['ok' => false, 'message' => 'user not found'], JSON_PRETTY_PRINT);
    $MySQLi->close();
    die;
}

$now = date('Y-m-d H:i:s', time());

switch($task){
    case 'good-age':
        $task_name = 'good-age';
        if(!mysqli_fetch_assoc(mysqli_query($MySQLi, "SELECT * FROM `user_tasks` WHERE `user_id` = '{$user_id}' and `task_name` = '{$task_name}'"))){
        $reward = 50;
        $MySQLi->query("UPDATE `users` SET `score` = `score` + '{$reward}', `tasksReward` = `tasksReward` + '{$reward}' WHERE `id` = '{$user_id}' LIMIT 1");
        $MySQLi->query("INSERT INTO `user_tasks` (`user_id`, `task_name`, `check_time`) VALUES ('{$user_id}', '{$task_name}', '{$now}')");
        $isOK = true;
        }
    break;

    case 'follow-age-x':
        $task_name = 'follow-age-x';
        if(!mysqli_fetch_assoc(mysqli_query($MySQLi, "SELECT * FROM `user_tasks` WHERE `user_id` = '{$user_id}' and `task_name` = '{$task_name}'"))){
        $reward = 100;
        $MySQLi->query("UPDATE `users` SET `score` = `score` + '{$reward}', `tasksReward` = `tasksReward` + '{$reward}' WHERE `id` = '{$user_id}' LIMIT 1");
        $MySQLi->query("INSERT INTO `user_tasks` (`user_id`, `task_name`, `check_time`) VALUES ('{$user_id}', '{$task_name}', '{$now}')");
        $isOK = true;
        }
    break;

    case 'invite-frens':
        $task_name = 'invite-frens';
        if(!mysqli_fetch_assoc(mysqli_query($MySQLi, "SELECT * FROM `user_tasks` WHERE `user_id` = '{$user_id}' and `task_name` = '{$task_name}'"))){
        $reward = 5000;
        $get_referrals = mysqli_fetch_all(mysqli_query($MySQLi, "SELECT `id` FROM `users` WHERE `inviterID` = '{$user_id}' LIMIT 10"));
        if(count($get_referrals) >= 5){
            $MySQLi->query("UPDATE `users` SET `score` = `score` + '{$reward}', `tasksReward` = `tasksReward` + '{$reward}' WHERE `id` = '{$user_id}' LIMIT 1");
            $MySQLi->query("INSERT INTO `user_tasks` (`user_id`, `task_name`, `check_time`) VALUES ('{$user_id}', '{$task_name}', '{$now}')");
            $isOK = true;
        }
        }
    break;

    case 'add-time-telegram':
        $task_name = 'add-time-telegram';
        if(!mysqli_fetch_assoc(mysqli_query($MySQLi, "SELECT * FROM `user_tasks` WHERE `user_id` = '{$user_id}' and `task_name` = '{$task_name}'"))){
        $reward = 500;
        $get_data = json_decode(file_get_contents('https://api.telegram.org/bot'.$apiKey.'/getchat?chat_id='.$user_id), true);
        $name = $get_data['result']['first_name'] . ' '. $get_data['result']['last_name'];
        if (strpos($name, '🐘') !== false) {
            $MySQLi->query("UPDATE `users` SET `score` = `score` + '{$reward}', `tasksReward` = `tasksReward` + '{$reward}' WHERE `id` = '{$user_id}' LIMIT 1");
            $MySQLi->query("INSERT INTO `user_tasks` (`user_id`, `task_name`, `check_time`) VALUES ('{$user_id}', '{$task_name}', '{$now}')");
            $isOK = true;
        }
        }
    break;

    case 'subscribe-age-telegram':
        $task_name = 'subscribe-age-telegram';
        if(!mysqli_fetch_assoc(mysqli_query($MySQLi, "SELECT * FROM `user_tasks` WHERE `user_id` = '{$user_id}' and `task_name` = '{$task_name}'"))){
        $reward = 150;
        $result = json_decode(file_get_contents('https://api.telegram.org/bot'.$apiKey.'/getChatMember?chat_id='.$telegramCommunityChatId.'&user_id='.$user_id));
        if($result->ok and in_array($result->result->status, ['member', 'administrator'])){
            $MySQLi->query("UPDATE `users` SET `score` = `score` + '{$reward}', `tasksReward` = `tasksReward` + '{$reward}' WHERE `id` = '{$user_id}' LIMIT 1");
            $MySQLi->query("INSERT INTO `user_tasks` (`user_id`, `task_name`, `check_time`) VALUES ('{$user_id}', '{$task_name}', '{$now}')");
            $isOK = true;
        }
        }
    break;

    case 'buy-0.2-Gram-FSTC':
    case 'buy-0.5-Gram-FSTC':
        $isOK = false;
        break;
        

    default:
        $isOK = false;
}



$MySQLi->close();


if($isOK) echo '{"success":true}';
else echo '{"success":false}';