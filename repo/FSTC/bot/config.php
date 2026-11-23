<?php


/*
Free SVG Codes : https://svgicons.sparkk.fr
*/


header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'UTC');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

function envValue(string $key, ?string $default = null): ?string {
    $value = getenv($key);
    return $value !== false && $value !== '' ? $value : $default;
}

// Load a local .env file when the host does not provide process environment variables.
if (file_exists(dirname(__DIR__) . '/.env')) {
    foreach (file(dirname(__DIR__) . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(ltrim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if ($value !== '' && getenv($key) === false) putenv($key . '=' . $value);
    }
}

$apiKey = envValue('TELEGRAM_BOT_TOKEN');
$botUsername = envValue('TELEGRAM_BOT_USERNAME', '');
$web_app = rtrim(envValue('WEB_APP_URL', ''), '/');
$telegramChannel = envValue('TELEGRAM_CHANNEL_URL', '');
$telegramX = envValue('TELEGRAM_X_URL', '');
$telegramCommunityChatId = envValue('TELEGRAM_COMMUNITY_CHAT_ID');
$telegramWebhookSecret = envValue('TELEGRAM_WEBHOOK_SECRET');

if ($apiKey === null || $web_app === '') {
    http_response_code(500);
    error_log('Missing required environment variables: TELEGRAM_BOT_TOKEN or WEB_APP_URL');
    exit;
}

$age_rewards = array(
    '1' => 1024,
    '2' => 2118,
    '3' => 2720,
    '4' => 3085,
    '5' => 4025,
    '6' => 6012,
    '7' => 8055,
    '8' => 1010,
    '9' => 13500,
    '10' => 16800,
    '11' => 20000,
);

$ref_percentage = (float) envValue('REFERRAL_PERCENTAGE', '35');

$DB = [
    'host' => envValue('DB_HOST', 'localhost'),
    'dbname' => envValue('DB_NAME', ''),
    'username' => envValue('DB_USER', ''),
    'password' => envValue('DB_PASSWORD', ''),
];

$admins_user_id = array_values(array_filter(array_map(
    'intval',
    array_map('trim', explode(',', envValue('TELEGRAM_ADMIN_IDS', '')))
)));

$onchainRecipient = envValue('ONCHAIN_RECIPIENT_ADDRESS');
$onchainApiBase = rtrim(envValue('TONAPI_BASE_URL', 'https://tonapi.io'), '/');
$onchainApiKey = envValue('TONAPI_KEY', '');
$assetName = envValue('ASSET_NAME', 'GRAM');