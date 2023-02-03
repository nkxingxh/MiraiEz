<?php
/**
 * MiraiEz Copyright (c) 2021-2023 NKXingXh
 * License AGPLv3.0: GNU AGPL Version 3 <https://www.gnu.org/licenses/agpl-3.0.html>
 * This is free software: you are free to change and redistribute it.
 * There is NO WARRANTY, to the extent permitted by law.
 * 
 * Github: https://github.com/nkxingxh/MiraiEz
 */

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
        return array('code' => 0, 'msg' => 'success');
    }
}

/**
 * HTTP 适配器
 * @param string $command 命令字
 * @param array $content 参数内容
 * @param bool $post 是否使用 POST 方法
 * @param bool $json 是否使用 JSON 编码 (仅限 POST)
 */
function HttpAdapter($command, $content = array(), $post = null, $json = true)
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
        if (($post === null) ? in_array($command, $FUNC_GET) : (!$post)) {
            $url = httpApi . "/$command?" . http_build_query($content);
            writeLog('GET: ' . $url, __FUNCTION__, 'adapter', 1);
            $resp = CurlGET($url);
        } else {
            $url = httpApi . "/$command";
            $payload = $json ? json_encode($content) : $content;
            writeLog('POST: ' . $url, __FUNCTION__, 'adapter', 1);
            $resp = CurlPOST($payload, $url);
        }
        writeLog('Resp: ' . $resp, __FUNCTION__, 'adapter', 1);
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

/**消息发送与撤回 */

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
    if (defined('webhook') && webhook) global $_DATA;
    if ($messageId === true) {
        if (isset($_DATA['messageChain'][0]['id'])) $messageId = $_DATA['messageChain'][0]['id'];
        else return false;
    } else $messageId = (int) $messageId;
    if ($target === true) {
        if ($_DATA['type'] == 'GroupMessage') $target = $_DATA['sender']['group']['id'];
        elseif (isset($_DATA['sender']['id'])) $target = $_DATA['sender']['id'];
        else return false;
    } else $target = (int) $target;

    if (empty($messageId) || empty($target)) return false;
    return autoAdapter(__FUNCTION__, array('sessionKey' => $sessionKey, 'messageId' => $messageId, 'target' => $target));
}

/**文件操作 与 多媒体内容上传*/

/**
 * 获取文件信息
 */
function file_info($id = true, $path = null, $target = true, $group = null, $qq = null, $withDownloadInfo = false, $sessionKey = '')
{
    if ($id === true) {
        $id = messageChain2FileId();
        if ($id === false) return false;
    }
    if ($target === true) {
        if (isset($GLOBALS['_DATA']['sender']['group']['id'])) $target = $GLOBALS['_DATA']['sender']['group']['id'];
        elseif (isset($GLOBALS['_DATA']['sender']['id'])) $target = $GLOBALS['_DATA']['sender']['id'];
        else return false;
    }
    return autoAdapter(__FUNCTION__, array(
        'id' => $id,
        'path' => $path,
        'target' => ($target === null) ? null : ((int) $target),
        'group' => ($group === null) ? null : ((int) $group),
        'qq' => ($qq === null) ? null : ((int) $qq),
        'withDownloadInfo' => (bool) $withDownloadInfo,
        'sessionKey' => $sessionKey
    ));
}

function file_mkdir($id, $directoryName, $path = null, $target = true, $group = null, $qq = null, $sessionKey = '')
{
    if ($target === true) {
        if (isset($GLOBALS['_DATA']['sender']['group']['id'])) $target = $GLOBALS['_DATA']['sender']['group']['id'];
        elseif (isset($GLOBALS['_DATA']['sender']['id'])) $target = $GLOBALS['_DATA']['sender']['id'];
        else return false;
    }
    return autoAdapter(__FUNCTION__, array(
        'id' => $id,
        'directoryName' => $directoryName,
        'path' => $path,
        'target' => ($target === null) ? null : ((int) $target),
        'group' => ($group === null) ? null : ((int) $group),
        'qq' => ($qq === null) ? null : ((int) $qq),
        'sessionKey' => $sessionKey
    ));
}

function file_delete($id = true, $path = null, $target = true, $group = null, $qq = null, $sessionKey = '')
{
    if ($id === true) {
        $id = messageChain2FileId();
        if ($id === false) return false;
    }
    if ($target === true) {
        if (isset($GLOBALS['_DATA']['sender']['group']['id'])) $target = $GLOBALS['_DATA']['sender']['group']['id'];
        elseif (isset($GLOBALS['_DATA']['sender']['id'])) $target = $GLOBALS['_DATA']['sender']['id'];
        else return false;
    }
    return autoAdapter(__FUNCTION__, array(
        'id' => $id,
        'path' => $path,
        'target' => ($target === null) ? null : ((int) $target),
        'group' => ($group === null) ? null : ((int) $group),
        'qq' => ($qq === null) ? null : ((int) $qq),
        'sessionKey' => $sessionKey
    ));
}

function file_move($id = true, $moveTo = null, $path = null, $moveToPath = null, $target = true, $group = null, $qq = null, $sessionKey = '')
{
    if ($id === true) {
        $id = messageChain2FileId();
        if ($id === false) return false;
    }
    if ($target === true) {
        if (isset($GLOBALS['_DATA']['sender']['group']['id'])) $target = $GLOBALS['_DATA']['sender']['group']['id'];
        elseif (isset($GLOBALS['_DATA']['sender']['id'])) $target = $GLOBALS['_DATA']['sender']['id'];
        else return false;
    }
    return autoAdapter(__FUNCTION__, array(
        'id' => $id,
        'moveTo' => $moveTo,
        'path' => $path,
        'moveToPath' => $moveToPath,
        'target' => ($target === null) ? null : ((int) $target),
        'group' => ($group === null) ? null : ((int) $group),
        'qq' => ($qq === null) ? null : ((int) $qq),
        'sessionKey' => $sessionKey
    ));
}

function file_rename($id = true, $renameTo = null, $path = null, $target = true, $group = null, $qq = null, $sessionKey = '')
{
    if ($id === true) {
        $id = messageChain2FileId();
        if ($id === false) return false;
    }
    if ($target === true) {
        if (isset($GLOBALS['_DATA']['sender']['group']['id'])) $target = $GLOBALS['_DATA']['sender']['group']['id'];
        elseif (isset($GLOBALS['_DATA']['sender']['id'])) $target = $GLOBALS['_DATA']['sender']['id'];
        else return false;
    }
    return autoAdapter(__FUNCTION__, array(
        'id' => $id,
        'renameTo' => $renameTo,
        'path' => $path,
        'target' => ($target === null) ? null : ((int) $target),
        'group' => ($group === null) ? null : ((int) $group),
        'qq' => ($qq === null) ? null : ((int) $qq),
        'sessionKey' => $sessionKey
    ));
}

/**
 * 群文件上传
 * @param string $type          当前仅支持 "group" (传入 true 指定为当前类型)
 * @param int $target           上传目标群号 (传入 true 指定为当前群)
 * @param string $path          上传目录的id, 空串为上传到根目录
 * @param CURLFile $file          cURL 文件对象
 * @param string $sessionKey    已经激活的 Session
 */
function file_upload($file, $path = '', $type = 'group', $target = true, $sessionKey = '')
{
    if (defined('webhook') && webhook) {
        global $_DATA;
        if (isset($_DATA['sender']['group']['id'])) {
            if ($type === true) $type = 'group';
            if ($target === true) $target = $_DATA['sender']['group']['id'];
        } elseif (isset($_DATA['member']['group']['id'])) {
            if ($type === true) $type = 'group';
            if ($target === true) $target = $_DATA['member']['group']['id'];
        }
    }
    if (empty($file) || $target <= 0 || !is_string($type)) return false;
    writeLog("尝试上传文件至 $type => $target", __FUNCTION__, 'core', 1);
    return HttpAdapter('file/upload', array(
        'file' => $file,
        'path' => $path,
        'target' => (int) $target,
        'type' => $type,
        'sessionKey' => $sessionKey
    ), true, false);
}

/**获取账号信息 与 账号管理 */

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
    if (defined('webhook') && webhook) global $_DATA;
    if ($target === true) {
        if (isset($_DATA['sender']['group']['id'])) $target = $_DATA['sender']['group']['id'];
        elseif (isset($_DATA['member']['group']['id'])) $target = $_DATA['member']['group']['id'];
        else return false;
    } else $target = (int) $target;
    return HttpAdapter(__FUNCTION__, array('sessionKey' => $sessionKey, 'target' => $target));
}

/**群管理 与 群公告 */

function groupConfig($target = true, $config = array(), $sessionKey = '')
{
    if ($target === true) {
        $target = getCurrentGroupId();
        if (!$target) return false;
    } else $target = (int) $target;
    if (empty($config)) return HttpAdapter(__FUNCTION__, array(
        'sessionKey' => $sessionKey,
        'target' => $target
    ));
    else return HttpAdapter(__FUNCTION__, array(
        'sessionKey' => $sessionKey,
        'target' => $target,
        'config' => $config
    ), true);
}

/**
 * 获取/修改群员设置
 * @param int $target 指定群的群号
 * @param int $memberId 群员QQ号
 * @param array $info 要设置的群员资料
 */
function memberInfo($target = true, $memberId = true, $info = array(), $sessionKey = '')
{
    if (defined('webhook') && webhook) global $_DATA;
    if ($target === true) {
        if (isset($_DATA['sender']['group']['id'])) $target = $_DATA['sender']['group']['id'];
        elseif (isset($_DATA['member']['group']['id'])) $target = $_DATA['member']['group']['id'];
        else return false;
    } else $target = (int) $target;
    if ($memberId === true) {
        if (isset($_DATA['sender']['id'])) $memberId = $_DATA['sender']['id'];
        elseif (isset($_DATA['member']['id'])) $memberId = $_DATA['member']['id'];
        else return false;
    } else $memberId = (int) $memberId;

    if (empty($info)) return HttpAdapter(__FUNCTION__, array(
        'sessionKey' => $sessionKey,
        'target' => $target,
        'memberId' => $memberId
    ), false);
    else return HttpAdapter(__FUNCTION__, array(
        'sessionKey' => $sessionKey,
        'target' => $target,
        'memberId' => $memberId,
        'info' => $info
    ), true);
}

/**事件处理 */

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
