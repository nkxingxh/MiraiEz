<?php

/**
 * MiraiEz Copyright (c) 2021-2024 NKXingXh
 * License AGPLv3.0: GNU AGPL Version 3 <https://www.gnu.org/licenses/agpl-3.0.html>
 * This is free software: you are free to change and redistribute it.
 * There is NO WARRANTY, to the extent permitted by law.
 * 
 * Github: https://github.com/nkxingxh/MiraiEz
 */

/**消息发送、获取与撤回 */

/**
 * (缓存操作) 通过messageId获取消息
 */
function messageFromId($messageId = true, $target = true, $sessionKey = '')
{
    if ($messageId === true) {
        if (!isMessage() || $GLOBALS['_DATA']['messageChain'][0]['type'] !== 'Source') return false;
        $messageId = $GLOBALS['_DATA']['messageChain'][0]['id'];
    } else {
        $messageId = (int) $messageId;
    }
    $target = ($target === true) ? getCurrentTarget() : ((int) $target);
    if (empty($messageId) || empty($target)) return false;
    return HttpAdapter(__FUNCTION__, array(
        'sessionKey' => $sessionKey,
        'messageId' => $messageId,
        'target' => $target
    ));
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
    return autoAdapter(__FUNCTION__, $content);
}

/**
 * 发送群消息
 */
function sendGroupMessage($target, $messageChain, $quote = 0, $sessionKey = '')
{
    $messageChain = is_array($messageChain) ? $messageChain : getMessageChain($messageChain);
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
    $messageChain = is_array($messageChain) ? $messageChain : getMessageChain($messageChain);
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
        if (isset($_DATA['type']) && $_DATA['type'] == 'GroupMessage') $target = $_DATA['sender']['group']['id'];
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

/**获取插件信息 (mirai-api-http) */
function botList()
{
    return HttpAdapter(__FUNCTION__);
}
