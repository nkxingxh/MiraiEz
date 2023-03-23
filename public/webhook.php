<?php

/**
 * MiraiEz Copyright (c) 2021-2023 NKXingXh
 * License AGPLv3.0: GNU AGPL Version 3 <https://www.gnu.org/licenses/agpl-3.0.html>
 * This is free software: you are free to change and redistribute it.
 * There is NO WARRANTY, to the extent permitted by law.
 * 
 * Github: https://github.com/nkxingxh/MiraiEz
 */

const webhook = true;
const MIRAIEZ_RUNNING_MODE = 1;
require_once "../loader.php";
header('content-type: application/json');

if (verifyAuthorization()) {
    $_DATA = json_decode(file_get_contents("php://input"), true);
} else {
    if (!OneBot_auth()) {
        http_response_code(403);
        exit;
    }
}

writeLog(file_get_contents("php://input"), '收到数据', 'webhook', 1);

$webhooked = false;     //标记是否已使用 webhook 返回
$_Bot = (int) $_SERVER['HTTP_BOT'];
define("bot", $_Bot);

// Webhook 消息预处理
if (isMessage($_DATA['type'])) {
    $_PlainText = messageChain2PlainText($_DATA['messageChain']);
    $_ImageUrl = messageChain2ImageUrl($_DATA['messageChain']);
    $_At = messageChain2At($_DATA['messageChain']);
}

if (MIRAIEZ_PFA) $pfa_pluginInitTime = microtime(true);
require_once "../plugins.php"; //插件依赖
loadPlugins();  //加载插件
hookRegister('checkUpdates', 'BotOnlineEvent', 'FriendMessage');    //注册检查更新函数

if (MIRAIEZ_PFA) $pfa_pluginFuncTime = microtime(true);
execPluginsFunction();  //执行插件函数

if (MIRAIEZ_PFA) pfa_end();

function checkUpdates($_DATA): bool
{
    if ($_DATA['type'] == 'FriendMessage') {
        if ($_DATA['sender']['id'] == MIRAIEZ_ADMIN_QQ) {
            global $_PlainText;
            if (trim($_PlainText) != '检查更新') {
                return false;
            }
        } else return false;
    }
    $url = 'https://api.github.com/repos/nkxingxh/miraiez/releases/latest';
    $resp = CurlGET($url);
    $resp = json_decode($resp, true);
    if (empty($resp)) {
        if ($_DATA['type'] == 'FriendMessage') {
            sendFriendMessage(MIRAIEZ_ADMIN_QQ, 'MiraiEz 获取最新版本失败');
        }
        return false;
    }
    if (version_compare($resp['tag_name'], MIRAIEZ_VERSION, '>')) {
        sendFriendMessage(MIRAIEZ_ADMIN_QQ, "MiraiEz 发现新版本\n当前版本: " . MIRAIEZ_VERSION . "\n最新版本: " . $resp['tag_name']);
    } elseif ($_DATA['type'] == 'FriendMessage') {
        replyMessage("当前已经是最新版本");
    }
    return true;
}

function verifyAuthorization(): bool
{
    if (empty(MIRAIEZ_WEBHOOK_AUTH)) return true;
    if (empty($_SERVER['HTTP_AUTHORIZATION'])) return false;
    return (('[' . MIRAIEZ_WEBHOOK_AUTH . ']') == $_SERVER['HTTP_AUTHORIZATION']) || (MIRAIEZ_WEBHOOK_AUTH == $_SERVER['HTTP_AUTHORIZATION']);
}
