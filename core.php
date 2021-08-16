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
define("version", '1.1.1');

/**
 * 自动接口适配器
 * 自动调用对应接口执行命令
 */
function autoAdapter($command = '', $content = array())
{
    global $webhooked;
    //可以使用 webhook 的命令
    $WEBHOOK_FUNC = array('sendFriendMessage', 'sendGroupMessage', 'sendTempMessage', 'sendNudge', 'resp_newFriendRequestEvent', 'resp_memberJoinRequestEvent', 'resp_botInvitedJoinGroupRequestEvent');
    $USE_HTTP = $webhooked || (!in_array($command, $WEBHOOK_FUNC));

    if ($USE_HTTP || empty(webhook)) {
        $command = str_replace('_', '/', $command);     //命令格式转换
        return HttpAdapter($command, $content);
    } else {
        $command = str_replace('/', '_', $command);     //命令格式转换
        echo json_encode(array('command' => $command, 'content' => $content));
        $webhooked = true;
        return array('code' => 200, 'msg' => 'OK');
    }
}

function HttpAdapter($command, $content = array())
{
    //使用 GET 请求的命令
    $FUNC_GET = array('countMessage', 'fetchMessage', 'fetchLatestMessage', 'peekMessage', 'peekLatestMessage', 'about', 'messageFromId', 'friendList', 'groupList', 'memberList', 'botProfile', 'friendProfile', 'memberProfile', 'file/list', 'file/info', 'groupConfig', 'memberInfo');
    //自动获取 sessionKey
    if (!empty(bot) && empty($content['sessionKey'])) {
        $content['sessionKey'] = getSessionKey(bot);
    } elseif (empty(webhook)) {
        $content['sessionKey'] = getSessionKey();
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
            $content['sessionKey'] = getSessionKey(bot, true);
        }
        $error++;
    } while (($res === null || $res['code'] == 3) && $error < 2);      //错误重试
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
 * 发送好友消息
 */
function sendFriendMessage($target, $messageChain, $quote = 0, $sessionKey = '')
{
    $messageChain = is_array($messageChain) ? $messageChain : getMessageChain($messageChain);
    $content =  array(
        'sessionKey' => $sessionKey,
        'target' => (int) $target,
        'messageChain' => $messageChain
    );
    if (!empty($quote)) $content['quote'] = $quote;
    return autoAdapter('sendFriendMessage', $content);
}

/**
 * 发送群消息
 */
function sendGroupMessage($target, $messageChain, $quote = 0, $sessionKey = '')
{
    $messageChain = is_array($messageChain) ? $messageChain : getMessageChain($messageChain, '');
    $content = array(
        'sessionKey' => $sessionKey,
        'target' => (int) $target,
        'messageChain' => $messageChain
    );
    if (!empty($quote)) $content['quote'] = $quote;
    return autoAdapter('sendGroupMessage', $content);
}

/**
 * 发送临时会话消息
 */
function sendTempMessage($qq, $group, $messageChain, $quote = 0, $sessionKey = '')
{
    $messageChain = is_array($messageChain) ? $messageChain : getMessageChain($messageChain, '');
    $content = array(
        'sessionKey' => $sessionKey,
        'qq' => (int) $qq,
        'group' => (int) $group,
        'messageChain' => $messageChain
    );
    if (!empty($quote)) $content['quote'] = $quote;
    return autoAdapter('sendTempMessage', $content);
}

/**
 * 撤回消息
 * @param int|bool $target  目标消息ID，当值为 TRUE 时则表示当前接收的消息
 */
function recall($target = true, $sessionKey = '')
{
    if ($target === true) {
        global $_DATA;
        if ($_DATA['type'] == 'GroupMessage') $target = $_DATA['messageChain'][0]['id'];
        else return false;
    } else $target = (int) $target;
    return autoAdapter('recall', array('sessionKey' => $sessionKey, 'target' => $target));
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
    return autoAdapter('resp_newFriendRequestEvent', array('eventId' => $eventId, 'fromId' => $fromId, 'groupId' => $groupId, 'operate' => $operate, 'message' => $message, 'sessionKey' => $sessionKey));
}

/**
 * 处理 用户入群申请 事件
 */
function resp_memberJoinRequestEvent($eventId, $fromId, $groupId, $operate, $message = "", $sessionKey = '')
{
    return autoAdapter('resp_memberJoinRequestEvent', array('eventId' => $eventId, 'fromId' => $fromId, 'groupId' => $groupId, 'operate' => $operate, 'message' => $message, 'sessionKey' => $sessionKey));
}

/**
 * 处理 Bot被邀请入群申请 事件
 */
function resp_botInvitedJoinGroupRequestEvent($eventId, $fromId, $groupId, $operate, $message = "", $sessionKey = '')
{
    return autoAdapter('resp_botInvitedJoinGroupRequestEvent', array('eventId' => $eventId, 'fromId' => $fromId, 'groupId' => $groupId, 'operate' => $operate, 'message' => $message, 'sessionKey' => $sessionKey));
}

/**
 * 获取文件信息
 */
function file_info($id = true, $target = null, $group = null, $qq = null, $withDownloadInfo = false, $sessionKey = '')
{
    if ($id === true) {
        $id = messageChain2FileId();
        if ($id === false) return false;
    }
    return autoAdapter(__FUNCTION__, array(
        'id' => $id,
        'target' => ($target === null) ? null : ((int) $target),
        'group' => ($group === null) ? null : ((int) $group),
        'qq' => ($qq === null) ? null : ((int) $qq),
        'withDownloadInfo' => (bool) $withDownloadInfo,
        'sessionKey' => $sessionKey
    ));
}

function file_mkdir($id, $directoryName, $target = null, $group = null, $qq = null, $sessionKey = '')
{
    return autoAdapter(__FUNCTION__, array(
        'id' => $id,
        'directoryName' => $directoryName,
        'target' => ($target === null) ? null : ((int) $target),
        'group' => ($group === null) ? null : ((int) $group),
        'qq' => ($qq === null) ? null : ((int) $qq),
        'sessionKey' => $sessionKey
    ));
}

function file_delete($id = true, $target = null, $group = null, $qq = null, $sessionKey = '')
{
    if ($id === true) {
        $id = messageChain2FileId();
        if ($id === false) return false;
    }
    return autoAdapter(__FUNCTION__, array(
        'id' => $id,
        'target' => ($target === null) ? null : ((int) $target),
        'group' => ($group === null) ? null : ((int) $group),
        'qq' => ($qq === null) ? null : ((int) $qq),
        'sessionKey' => $sessionKey
    ));
}

function file_move($id = true, $moveTo = null, $target = null, $group = null, $qq = null, $sessionKey = '')
{
    if ($id === true) {
        $id = messageChain2FileId();
        if ($id === false) return false;
    }
    return autoAdapter(__FUNCTION__, array(
        'id' => $id,
        'moveTo' => $moveTo,
        'target' => ($target === null) ? null : ((int) $target),
        'group' => ($group === null) ? null : ((int) $group),
        'qq' => ($qq === null) ? null : ((int) $qq),
        'sessionKey' => $sessionKey
    ));
}

function file_rename($id = true, $renameTo = null, $target = null, $group = null, $qq = null, $sessionKey = '')
{
    if ($id === true) {
        $id = messageChain2FileId();
        if ($id === false) return false;
    }
    return autoAdapter(__FUNCTION__, array(
        'id' => $id,
        'renameTo' => $renameTo,
        'target' => ($target === null) ? null : ((int) $target),
        'group' => ($group === null) ? null : ((int) $group),
        'qq' => ($qq === null) ? null : ((int) $qq),
        'sessionKey' => $sessionKey
    ));
}
