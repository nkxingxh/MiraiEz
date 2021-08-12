<?php
require_once "core.php";
header('content-type: application/json');

$_DATA = json_decode(file_get_contents("php://input"), true);
saveFile('webhook.log', file_get_contents("php://input"));

if (!empty(Authorization) && (empty($_SERVER['HTTP_AUTHORIZATION']) || $_SERVER['HTTP_AUTHORIZATION'] != Authorization)) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

// Webhook 消息预处理
$webhooked = false;
define("webhook", true);
define("bot", $_SERVER['HTTP_BOT']);

if (isMessage($_DATA['type'])) {
    $_PlainText = messageChain2PlainText($_DATA['messageChain']);
    $_ImageUrl = messageChain2ImageUrl($_DATA['messageChain']);
    $_At = messageChain2At($_DATA['messageChain']);
}

//这里是你要加载的插件列表
//这里是你要加载的插件列表
//这里是你要加载的插件列表
//插件放在 plugins 文件夹
$_loadPlugins = array(
    'examplePlugin.php'
);

$pluginsDir = "$baseDir/plugins";
define("pluginsDir", $pluginsDir);

$plugins = array();                         //插件列表

foreach ($_loadPlugins as $__plugin__) {
    include "./plugins/$__plugin__";
}
unset($__plugin__);

if (is_array($_HOOK)) {
    foreach ($_HOOK as $_FUNC) {
        $_FUNC($_DATA);
    }
    unset($_FUNC);
}

/**
 * HOOK 注册
 * @param string $func      目标函数名
 * @param string ...$types  注册的消息或事件类型, 具体可查阅 mirai-api-http 开发文档
 */
function hookRegister($func, ...$types)
{
    global $_HOOK, $_DATA;
    foreach ($types as $type) {
        if ($type == $_DATA['type']) {      //仅当注册类型与 webhook 上报的类型一样时，才添加
            if (empty($_HOOK)) {
                $_HOOK = array($func);
            } else {
                $_HOOK[] = $func;
            }
            break;
        }
    }
}