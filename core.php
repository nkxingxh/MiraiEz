<?php
$baseDir = empty($_SERVER['DOCUMENT_ROOT']) ? __DIR__ : $_SERVER['DOCUMENT_ROOT'];
define('baseDir', $baseDir);            //定义站点目录

require_once "$baseDir/config.php";
require_once "$baseDir/string.php";
require_once "$baseDir/curl.php";
require_once "$baseDir/easyMirai.php";
require_once "$baseDir/pluginsHelp.php";

$dataDir = getDataDir();
define("dataDir", $dataDir);

/**
 * 发送好友消息
 */
function sendFriendMessage($target, $messageChain, $quote = 0, $sessionKey = '')
{
    $messageChain = is_array($messageChain) ? $messageChain : getMessageChain($messageChain);
    $content =  array('sessionKey' => $sessionKey, 'target' => $target, 'messageChain' => $messageChain);
    if (!empty($quote)) $content['quote'] = $quote;
    autoAdapter('sendFriendMessage', $content);
}

/**
 * 发送群消息
 */
function sendGroupMessage($target, $messageChain, $quote = 0, $sessionKey = '')
{
    $messageChain = is_array($messageChain) ? $messageChain : getMessageChain($messageChain, '');
    $content = array('sessionKey' => $sessionKey, 'target' => $target, 'messageChain' => $messageChain);
    if (!empty($quote)) $content['quote'] = $quote;
    autoAdapter('sendGroupMessage', $content);
}

/**
 * 发送临时会话消息
 */
function sendTempMessage($qq, $group, $messageChain, $quote = 0, $sessionKey = '')
{
    $messageChain = is_array($messageChain) ? $messageChain : getMessageChain($messageChain, '');
    $content = array('sessionKey' => $sessionKey, 'qq' => $qq, 'group' => $group, 'messageChain' => $messageChain);
    if (!empty($quote)) $content['quote'] = $quote;
    autoAdapter('sendTempMessage', $content);
}

/**
 * 撤回消息
 */
function recall($target, $sessionKey = '')
{
    autoAdapter('recall', array('sessionKey' => $sessionKey, 'target' => $target));
}

function groupList($sessionKey = '')
{
    return HttpAdapter('groupList', array('sessionKey' => $sessionKey));
}

/**
 * 处理 添加好友申请 事件
 * @see https://github.com/project-mirai/mirai-api-http/blob/master/docs/api/API.md#添加好友申请
 */
function resp_newFriendRequestEvent($eventId, $fromId, $groupId, $operate, $message = "", $sessionKey = '')
{
    autoAdapter('resp_newFriendRequestEvent', array('eventId' => $eventId, 'fromId' => $fromId, 'groupId' => $groupId, 'operate' => $operate, 'message' => $message, 'sessionKey' => $sessionKey));
}

/**
 * 处理 用户入群申请 事件
 */
function resp_memberJoinRequestEvent($eventId, $fromId, $groupId, $operate, $message = "", $sessionKey = '')
{
    autoAdapter('resp_memberJoinRequestEvent', array('eventId' => $eventId, 'fromId' => $fromId, 'groupId' => $groupId, 'operate' => $operate, 'message' => $message, 'sessionKey' => $sessionKey));
}

/**
 * 处理 Bot被邀请入群申请 事件
 */
function resp_botInvitedJoinGroupRequestEvent($eventId, $fromId, $groupId, $operate, $message = "", $sessionKey = '')
{
    autoAdapter('resp_botInvitedJoinGroupRequestEvent', array('eventId' => $eventId, 'fromId' => $fromId, 'groupId' => $groupId, 'operate' => $operate, 'message' => $message, 'sessionKey' => $sessionKey));
}

/**
 * 自动接口适配器
 * 方便使用 webhook 适配器，同时为以后使用 http 适配器做铺垫
 */
function autoAdapter($command = '', $content = array())
{
    global $webhooked;
    $WEBHOOK_FUNC = array('sendFriendMessage', 'sendGroupMessage', 'sendTempMessage', 'sendNudge', 'resp_newFriendRequestEvent', 'resp_memberJoinRequestEvent', 'resp_botInvitedJoinGroupRequestEvent');
    $USE_HTTP = $webhooked || (!in_array($command, $WEBHOOK_FUNC));

    if ($USE_HTTP) {
        $command = str_replace('_', '/', $command);     //命令格式转换
        HttpAdapter($command, $content);
    } else {
        $command = str_replace('/', '_', $command);     //命令格式转换
        echo json_encode(array('command' => $command, 'content' => $content));
        $webhooked = true;
    }
}

function HttpAdapter($command, $content = array())
{
    $FUNC_GET = array('countMessage', 'fetchMessage', 'fetchLatestMessage', 'peekMessage', 'peekLatestMessage', 'about', 'messageFromId', 'friendList', 'groupList', 'memberList', 'botProfile', 'friendProfile', 'memberProfile', 'file/list', 'file/info', 'groupConfig', 'memberInfo');
    if (webhook && empty($content['sessionKey'])) {
        $content['sessionKey'] = getSessionKey(bot);
    }

    $error = 0;
    do {
        //判断命令应该使用 GET 还是 POST 方式
        if (in_array($command, $FUNC_GET)) {
            $query = http_build_query($content);
            $res = CurlGET(httpApi . "/$command?$query");
        } else {
            $data = json_encode($content);
            $res = CurlPOST($data, httpApi . "/$command");
        }
        $res = json_decode($res, true);

        if ($res['code'] == 3) {
            $error++;
            $content['sessionKey'] = getSessionKey(bot, true);
        }
    } while ($res['code'] == 3 && $error < 2);      //错误重试
    return $res;
}

function HttpAdapter_verify()
{
    $data = json_encode(array('verifyKey' => verifyKey));
    $res = CurlPOST($data, httpApi . '/verify');
    $res = json_decode($res, true);
    if (empty($res)) {
    } elseif ($res['code'] != 0) {
    }
    return $res;
}

function HttpAdapter_bind($sessionKey, $qq)
{
    $data = json_encode(array('sessionKey' => $sessionKey, 'qq' => (int) $qq));
    $res = CurlPOST($data, httpApi . '/bind');
    $res = json_decode($res, true);
    if (empty($res)) {
    } elseif ($res['code'] != 0) {
    }
    return $res;
}

function HttpAdapter_release($sessionKey, $qq)
{
    $data = json_encode(array('sessionKey' => $sessionKey, 'qq' => (int) $qq));
    $res = CurlPOST($data, httpApi . '/release');
    $res = json_decode($res, true);
    if (empty($res)) {
    } elseif ($res['code'] != 0) {
    }
    return $res;
}

/**
 * 获取 HTTP 参数
 * 支持 GET 和 POST
 */
function getPar($name, $default = '', $strtolower = false)
{
    $value = empty($_POST[$name]) ? (empty($_GET[$name]) ? $default : $_GET[$name]) : $_POST[$name];
    $value = $strtolower ? strtolower($value) : $value;
    return $value;
}