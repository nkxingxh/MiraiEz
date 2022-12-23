<?php

/**
 * 自动接口适配器
 * 自动调用对应接口执行命令
 */
function autoAdapter($command = '', $content = array())
{
    writeLog("$command => " . json_encode($content, JSON_UNESCAPED_UNICODE), __FUNCTION__, 'adapter', 1);
    global $webhooked;
    //可以使用 webhook 的命令
    $WEBHOOK_FUNC = array('sendFriendMessage', 'sendGroupMessage', 'sendTempMessage', 'sendNudge', 'resp_newFriendRequestEvent', 'resp_memberJoinRequestEvent', 'resp_botInvitedJoinGroupRequestEvent');
    $USE_HTTP = $webhooked || adapter_always_use_http
        || (!in_array($command, $WEBHOOK_FUNC))
        || (defined('webhook') == false || webhook == false)
        || defined("OneBot");

    if ($USE_HTTP) {
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
    //OneBot Bridge
    if (defined("OneBot")) {
        writeLog("转交给 OneBot_API_bridge 处理", __FUNCTION__, "OneBot", 1);
        return OneBot_API_bridge($command, $content);
    }

    //使用 GET 请求的命令
    $FUNC_GET = array('countMessage', 'fetchMessage', 'fetchLatestMessage', 'peekMessage', 'peekLatestMessage', 'about', 'messageFromId', 'friendList', 'groupList', 'memberList', 'botProfile', 'friendProfile', 'memberProfile', 'file/list', 'file/info', 'groupConfig', 'memberInfo');
    //自动获取 sessionKey
    if (defined('bot') && empty($content['sessionKey'])) {
        $content['sessionKey'] = getSessionKey(bot);
    } elseif (defined('webhook') && webhook == false) {
        $content['sessionKey'] = getSessionKey();
    }

    $error = 0;
    do {
        //判断命令应该使用 GET 还是 POST 方式
        if (in_array($command, $FUNC_GET)) {
            $query = http_build_query($content);
            $resp = CurlGET(httpApi . "/$command?$query");
        } else {
            $data = json_encode($content);
            $resp = CurlPOST($data, httpApi . "/$command");
        }
        $resp = json_decode($resp, true);

        if (isset($resp['code']) && $resp['code'] == 3) {
            $content['sessionKey'] = getSessionKey(defined('bot') ? bot : 0, true);
        }
        $error++;
    } while (($resp === null || (isset($resp['code']) && $resp['code'] == 3)) && $error < 2);      //错误重试
    return $resp;
}

function HttpAdapter_verify()
{
    $data = json_encode(array('verifyKey' => verifyKey));
    $resp = CurlPOST($data, httpApi . '/verify');
    $resp = json_decode($resp, true);
    if (empty($resp)) {
        writeLog("解析数据失败", 'verify', 'core', 1);
    } elseif ($resp['code'] != 0) {
        writeLog("验证失败! 响应: " . json_encode($resp, JSON_UNESCAPED_UNICODE), 'bind', 'core', 1);
    } else {
        writeLog("验证成功! session: " . $resp['session'], 'verify', 'core', 1);
    }
    return $resp;
}

function HttpAdapter_bind($sessionKey, $qq)
{
    $data = json_encode(array('sessionKey' => $sessionKey, 'qq' => (int) $qq), JSON_UNESCAPED_UNICODE);
    writeLog("请求内容: " . $data, 'bind', 'core', 1);
    $resp = CurlPOST($data, httpApi . '/bind');
    $resp = json_decode($resp, true);
    if (empty($resp)) {
        writeLog("解析数据失败", 'bind', 'core', 1);
    } elseif ($resp['code'] != 0) {
        writeLog("绑定失败! 响应: " . json_encode($resp, JSON_UNESCAPED_UNICODE), 'bind', 'core', 1);
    }
    return $resp;
}

function HttpAdapter_release($sessionKey, $qq)
{
    $data = json_encode(array('sessionKey' => $sessionKey, 'qq' => (int) $qq));
    $resp = CurlPOST($data, httpApi . '/release');
    $resp = json_decode($resp, true);
    if (empty($resp)) {
    } elseif ($resp['code'] != 0) {
    }
    return $resp;
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
 * @param int|bool $messageId 需要撤回的消息的 messageId, 传入 true 代表当前的消息 (默认值为 true)
 * @param int|bool $target  好友id或群 id， 传入 true 代表当前消息发送者 或 消息所在群 (默认值为 true)
 */
function recall($messageId = true, $target = true, $sessionKey = '')
{
    if (webhook) {
        global $_DATA;
        if ($messageId === true && webhook) {

            if ($_DATA['type'] == 'GroupMessage') $messageId = $_DATA['messageChain'][0]['id'];
            else return false;
        } else $messageId = (int) $messageId;
        if ($target === true && webhook) {
            global $_DATA;
            if ($_DATA['type'] == 'GroupMessage') $target = $_DATA['sender']['group']['id'];
            else $target = $_DATA['sender']['id'];
        } else $target = (int) $target;
    }
    if (empty($messageId) || empty($target)) return false;
    return autoAdapter(__FUNCTION__, array('sessionKey' => $sessionKey, 'messageId' => $messageId, 'target' => $target));
}

function friendList($sessionKey = '')
{
    return HttpAdapter(__FUNCTION__, array('sessionKey' => $sessionKey));
}

function groupList($sessionKey = '')
{
    return HttpAdapter(__FUNCTION__, array('sessionKey' => $sessionKey));
}

function memberList($target = true, $sessionKey = '')
{
    if ($target === true) {
        global $_DATA;
        if ($_DATA['type'] == 'GroupMessage') $target = $_DATA['sender']['group']['id'];
        else return false;
    } else $target = (int) $target;
    return autoAdapter(__FUNCTION__, array('sessionKey' => $sessionKey, 'target' => $target));
}

/**
 * 处理 添加好友申请 事件
 * @see https://github.com/project-mirai/mirai-api-http/blob/master/docs/api/API.md#添加好友申请
 */
function resp_newFriendRequestEvent($operate, $eventId = true, $fromId = true, $groupId = true, $message = "", $sessionKey = '')
{
    global $_DATA;
    if ($_DATA['type'] == 'BotInvitedJoinGroupRequestEvent') {
        if ($eventId === true) $eventId = $_DATA['eventId'];
        if ($fromId === true) $fromId = $_DATA['fromId'];
        if ($groupId === true) $groupId = $_DATA['groupId'];
    }

    return autoAdapter(__FUNCTION__, array(
        'eventId' => (int) $eventId,
        'fromId' => (int) $fromId,
        'groupId' => (int) $groupId,
        'operate' => (int) $operate,
        'message' => $message,
        'sessionKey' => $sessionKey
    ));
}

/**
 * 处理 用户入群申请 事件
 */
function resp_memberJoinRequestEvent($operate, $eventId = true, $fromId = true, $groupId = true, $message = "", $sessionKey = '')
{
    global $_DATA;
    if ($_DATA['type'] == 'BotInvitedJoinGroupRequestEvent') {
        if ($eventId === true) $eventId = $_DATA['eventId'];
        if ($fromId === true) $fromId = $_DATA['fromId'];
        if ($groupId === true) $groupId = $_DATA['groupId'];
    }

    return autoAdapter(__FUNCTION__, array(
        'eventId' => (int) $eventId,
        'fromId' => (int) $fromId,
        'groupId' => (int) $groupId,
        'operate' => (int) $operate,
        'message' => $message,
        'sessionKey' => $sessionKey
    ));
}

/**
 * 处理 Bot被邀请入群申请 事件
 */
function resp_botInvitedJoinGroupRequestEvent($operate, $eventId = true, $fromId = true, $groupId = true, $message = "", $sessionKey = '')
{
    global $_DATA;
    if ($_DATA['type'] == 'BotInvitedJoinGroupRequestEvent') {
        if ($eventId === true) $eventId = $_DATA['eventId'];
        if ($fromId === true) $fromId = $_DATA['fromId'];
        if ($groupId === true) $groupId = $_DATA['groupId'];
    }

    return autoAdapter(__FUNCTION__, array(
        'eventId' => (int) $eventId,
        'fromId' => (int) $fromId,
        'groupId' => (int) $groupId,
        'operate' => (int) $operate,
        'message' => $message,
        'sessionKey' => $sessionKey
    ));
}

function groupConfig($target = true, $sessionKey = '')
{
    if ($target === true) {
        global $_DATA;
        if ($_DATA['type'] == 'GroupMessage') $target = $_DATA['sender']['group']['id'];
        else return false;
    } else $target = (int) $target;
    return autoAdapter(__FUNCTION__, array('sessionKey' => $sessionKey, 'target' => $target));
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
