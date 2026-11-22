<?php

include '../../../bot/config.php';
include '../../../bot/functions.php';

$MySQLi = new mysqli('localhost',$DB['username'],$DB['password'],$DB['dbname']);
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
        $result = json_decode(file_get_contents('https://api.telegram.org/bot'.$apiKey.'/getChatMember?chat_id=-1002177270626&user_id='.$user_id));
        if($result->ok and in_array($result->result->status, ['member', 'administrator'])){
            $MySQLi->query("UPDATE `users` SET `score` = `score` + '{$reward}', `tasksReward` = `tasksReward` + '{$reward}' WHERE `id` = '{$user_id}' LIMIT 1");
            $MySQLi->query("INSERT INTO `user_tasks` (`user_id`, `task_name`, `check_time`) VALUES ('{$user_id}', '{$task_name}', '{$now}')");
            $isOK = true;
        }
        }
    break;

    case 'buy-0.2-Ton-ELEPHANTS':
        $task_name = 'buy-0.2-Ton-ELEPHANTS';

        // Check for user payments completed today
        $payment = mysqli_fetch_assoc(mysqli_query($MySQLi, "SELECT * FROM `user_payments` WHERE `user_id` = '{$user_id}' AND `payment_status` = 'complete' AND DATE(`payment_date`) = CURDATE()"));
        
        if ($payment) {
            $reward = 10000;
            $MySQLi->query("UPDATE `users` SET `score` = `score` + '{$reward}', `tasksReward` = `tasksReward` + '{$reward}' WHERE `id` = '{$user_id}' LIMIT 1");
            
            // Update payment status to 'done'
            $MySQLi->query("UPDATE `user_payments` SET `payment_status` = 'done' WHERE `user_id` = '{$user_id}' AND `id` = '{$payment['id']}'");
            
            $isOK = true;
        }
    break;

    case 'buy-0.5-Ton-ELEPHANTS':
        $task_name = 'buy-0.5-Ton-ELEPHANTS';

        // Check for user payments completed today
        $payment = mysqli_fetch_assoc(mysqli_query($MySQLi, "SELECT * FROM `user_payments` WHERE `user_id` = '{$user_id}' AND `payment_status` = 'complete' AND DATE(`payment_date`) = CURDATE()"));

        if ($payment) {
        $reward = 35000;
        $MySQLi->query("UPDATE `users` SET `score` = `score` + '{$reward}', `tasksReward` = `tasksReward` + '{$reward}' WHERE `id` = '{$user_id}' LIMIT 1");
    
        // Update payment status to 'done'
        $MySQLi->query("UPDATE `user_payments` SET `payment_status` = 'done' WHERE `user_id` = '{$user_id}' AND `id` = '{$payment['id']}'");
    
        $isOK = true;
        }
    break;

    case 'insert-payment':
        $payment_amount = $_REQUEST['payment_amount'];
        $isOK = false;
        
        if ($payment_amount > 0) {
            // Check if the user has already made a transaction today
            $result = mysqli_query($MySQLi, "SELECT COUNT(*) FROM `user_payments` WHERE `user_id` = '{$user_id}' AND DATE(`payment_date`) = CURDATE()");
            $transaction_count = mysqli_fetch_row($result)[0];
            
            if ($transaction_count == 0) {
                if($payment_amount == 0.5){
                    $reward = 35000;
                    $MySQLi->query("UPDATE `users` SET `score` = `score` + '{$reward}', `tasksReward` = `tasksReward` + '{$reward}' WHERE `id` = '{$user_id}' LIMIT 1");
            
                }else{
                    $reward = 10000;
                    $MySQLi->query("UPDATE `users` SET `score` = `score` + '{$reward}', `tasksReward` = `tasksReward` + '{$reward}' WHERE `id` = '{$user_id}' LIMIT 1");
            
                }
                // Insert the payment
                $MySQLi->query("INSERT INTO `user_payments` (`user_id`, `payment_amount`, `payment_date`, `payment_status`) 
                                VALUES ('{$user_id}', '{$payment_amount}', '{$now}', 'complete')");
                $isOK = true;
            }
        }
        break;
        

    default:
        $isOK = false;
}



$MySQLi->close();


if($isOK) echo '{"success":true}';
else echo '{"success":false}';