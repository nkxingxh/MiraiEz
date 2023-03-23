<?php

/**
 * MiraiEz Copyright (c) 2021-2023 NKXingXh
 * License AGPLv3.0: GNU AGPL Version 3 <https://www.gnu.org/licenses/agpl-3.0.html>
 * This is free software: you are free to change and redistribute it.
 * There is NO WARRANTY, to the extent permitted by law.
 * 
 * Github: https://github.com/nkxingxh/MiraiEz
 */

const OneBotBridge = true;
require_once "$coreDir/miraiOneBot.php";

/**
 * OneBot_auth
 * 验证请求是否为 OneBot 标准实现发出的
 * 如果是，则将消息标准化为 mirai-api-http 格式且返回 true
 * 验证失败则返回 false
 */
function OneBot_auth(): bool
{
    if (!OneBotBridge) return false;
    //writeLog("验证开始", "Auth", "OneBot");
    $config = OneBot_get_config();
    $auth = false;

    //OneBot 12
    if (isset($_SERVER['HTTP_AUTHORIZATION']) && $_SERVER['HTTP_AUTHORIZATION'] === ("Bearer " . $config['OneBot12_access_token'])) {
        $auth = true;
        //OneBot_12mirai(null, true);
    }

    //OneBot 11
    elseif (
        isset($_SERVER['HTTP_X_SIGNATURE']) && $_SERVER['HTTP_X_SIGNATURE'] ===
        ("sha1=" . hash_hmac("sha1", file_get_contents("php://input"), $config['OneBot11_secret']))
    ) {
        //writeLog("OneBot 11 验证通过", 'Auth', "OneBot");

        $auth = true;

        $self_qq = $_SERVER['HTTP_X_SELF_ID'];
        if (empty($config['enable'][$self_qq])) {
            return false;       //未配置处理类型，不予处理
        }

        $_SERVER['HTTP_BOT'] = $self_qq;    //兼容 mirai-api-http 标准
        //define('bot', $self_qq);      //webhook中会定义，此处不定义

        //方便调用
        define('OneBot11_secret', $config['OneBot11_secret']);
        define('OneBot11_access_token', $config['OneBot11_access_token']);
        define('OneBot11_HTTP_API', $config['OneBot11_HTTP_API']);
        define("OneBot_FilterOwnMessages", $config['enable'][$self_qq]['FilterOwnMessages']);

        //writeLog("标准为 OneBot 11, Bridge 开始处理", 'Auth', "OneBot");
        $d = OneBot_11mirai(null, $config['enable'][$self_qq]['type']);
        if (empty($d)) return false;
    }
    if ($auth) {
        //writeLog(file_get_contents("php://input"), '收到数据', "OneBot");
    }
    return $auth;
}

function OneBot_get_config()
{
    $config = getConfig("OneBotBridge");
    $needSaveConfig = false;
    if (!isset($config['OneBot11_secret'])) {
        $needSaveConfig = true;
        $config['OneBot11_secret'] = "";
    }
    if (!isset($config['OneBot11_access_token'])) {
        $needSaveConfig = true;
        $config['OneBot11_access_token'] = "";
    }
    if (!isset($config['OneBot11_HTTP_API'])) {
        $needSaveConfig = true;
        $config['OneBot11_HTTP_API'] = "http://localhost:5700";
    }

    if (!isset($config['OneBot12_access_token'])) {
        $needSaveConfig = true;
        $config['OneBot12_access_token'] = "";
    }

    if (!isset($config['enable']) || !is_array($config['enable'])) {
        $needSaveConfig = true;
        $config['enable'] = array(
            "1234567890" => array(
                'type' => array(
                    'GuildChannelMessage'
                ),
                'FilterOwnMessages' => true,
                'explain' => '字段解释: type 为要翻译的上报类型, 不在 type 中的类型不会处理. 将 type 设置为 true 则处理所有支持的上报数据. FilterOwnMessages 为 true 时, 将不会接受 bot 发出的消息'
            )
        );
    }

    if ($needSaveConfig) saveConfig("OneBotBridge", $config);
    return $config;
}

/**
 * OneBot_11mirai
 * 将数据标准化
 * OneBot -> mirai_11
 */
function OneBot_11mirai($d = null, $allowType = array(), $replace_global_data = true)
{
    global $_DATA;
    if ($d === null)
        $d = json_decode(file_get_contents("php://input"), true);

    $type = OneBot_11mirai_getType(
        $d['post_type'],
        $d[$d['post_type'] . '_type'],
        $d['sub_type'] ?? null
    );

    writeLog(json_encode($d, JSON_UNESCAPED_UNICODE), __FUNCTION__, 'OneBot', 1);

    //不在处理范围内，不予处理
    if (is_array($allowType) ? (!in_array($type, $allowType)) : ($allowType !== true)) {
        return false;
    }

    writeLog("上报类型 $type 在处理范围内, 开始转换消息类型", '11mirai', 'OneBot', 1);

    switch ($d['post_type']) {
        case 'message':
            $X_type = $d[$d['post_type'] . '_type'];
            $sub_type = $d['sub_type'];
            switch ($X_type) {
                case 'private':
                    $messageChain = OneBot_message2chain($d['message'], $d['message_seq'], $d['time']);
                    if ($type == 'FriendMessage') {
                        $sender = array(
                            'id' => (int) $d['user_id'],
                            'nickname' => $d['sender']['nickname'],
                            'remark' => null                //无法直接取得****
                        );
                    } else if ($type == 'TempMessage') {
                    } else if ($type == 'StrangerMessage') {
                    }
                    break;

                case 'group':
                    $messageChain = OneBot_message2chain($d['message'], $d['message_seq'], $d['time']);
                    if ($type == 'GroupMessage') {
                        //处理sender
                        $permission = OneBot_permission2mirai($d['sender']['role']);
                        $sender = array(    //发送者信息
                            "id" => (int) $d['user_id'],                  //QQID
                            "memberName" => $d['sender']['card'],   //群名片
                            "specialTitle" => null,                 //未知***************
                            "permission" => $permission,            //群成员身份
                            "joinTimestamp" => null,                //Don't Know
                            "lastSpeakTimestamp" => null,           //Don't Know
                            "muteTimeRemaining" => null,            //Don't Know
                            "group" => array(     //群信息
                                "id" => (int) $d['group_id'],     //群ID
                                "name" => "",               //群名称 未知****************//Don't Know
                                "permission" => "OWNER",    //机器人身份 未知****************//Don't Know

                            )
                        );
                    }
                    break;

                case 'guild':
                    /*
                    $config = getConfig('OneBotBridge');
                    if (empty($config['enable'][bot]['GuildServiceProfile']['tiny_id'])) {
                        $resp = getGuildServiceProfile();
                        if (empty($resp['status']) || $resp['status'] !== 'ok') return false;
                        $config['enable'][bot]['GuildServiceProfile'] = $resp['data'];
                        $config['enable'][bot]['GuildServiceProfile']['tiny_id'] =
                            (int)$resp['data']['tiny_id'];
                        saveConfig('OneBotBridge', $config);
                    }
                    define('bot_tiny_id', $config['enable'][bot]['GuildServiceProfile']['tiny_id']);

                    //是否需要抛弃该条消息
                    if (
                        empty($config['enable'][bot]['GuildServiceProfile']['tiny_id']) ||
                        (OneBot_FilterOwnMessages &&
                            ($d['user_id'] == bot_tiny_id)
                        )
                    ) {
                        return false;
                    }*/
                    if ($d['user_id'] == $d['self_tiny_id']) return false;

                    writeLog("正在处理频道消息", 'Guild', 'OneBot', 1);

                    $messageChain = OneBot_message2chain($d['message'], $d['message_id'], $d['time']);
                    if ($sub_type == 'channel') {
                        $sender = array(
                            'id' => (int) $d['user_id'],
                            'nickname' => $d['sender']['nickname'],
                            'remark' => null,
                            'guild' => array(
                                'id' => (int) $d['guild_id'],
                                'channel' => array(
                                    'id' => (int) $d['channel_id']
                                )
                            )
                        );
                    }
            }
            //发送者信息

            $new = array(
                'type' => $type,
                'sender' => $sender,
                'messageChain' => $messageChain
            );
            break;

        default:
            break;
    }

    if (empty($new)) {
        return false;   //翻译失败
    }
    if ($replace_global_data) {
        define("OneBot", 11);
        $_DATA = $new;
    }
    //writeLog(json_encode($new, JSON_UNESCAPED_UNICODE), '', 'OneBot');
    return $new;
}

function OneBot_permission2mirai($permission): string
{
    if (strcasecmp($permission, 'member') == 0) return "MEMBER";
    if (strcasecmp($permission, 'admin') == 0) return "ADMINISTRATOR";
    else return "OWNER";
}

function OneBot_12mirai($d = null, $replace_input = true)
{
    if ($d === null)
        $d = json_decode(file_get_contents("php://input"), true);
}

/*
 *从OneBot到mirai_11的消息类型获取 
 */
function OneBot_11mirai_getType($post_type, $X_type, $sub_type)
{
    $type_map = array(
        "message" => array(
            "private" => array(
                "friend" => "FriendMessage",
                "group" => "TempMessage",
                "other" => "StrangerMessage"
            ),
            "group" => array(
                "normal" => "GroupMessage",
                "anonymous" => null,
                "notice" => null
            ),
            "guild" => array(
                "channel" => "GuildChannelMessage"
            )
        ),
        "notice" => array(
            "group_upload" => "GroupMessage",
            "group_admin" => array(
                "set" => "BotGroupPermissionChangeEvent",   //要舍去除Bot权限被改变以外的事件
                "unset" => "BotGroupPermissionChangeEvent"  //要舍去除Bot权限被改变以外的事件
            ),
            "group_decrease" => array(
                "leave" => "MemberLeaveEventQuit",          // 或 BotLeaveEventActive
                "kick" => "MemberLeaveEventKick",
                "kick_me" => "BotLeaveEventKick"
            ),
            "group_increase" => array(
                "approve" => "MemberJoinEvent",     //管理员同意
                "invite" => "MemberJoinEvent"       //(管理员)邀请
            ),
            "group_ban" => array(
                "ban" => "MemberMuteEvent",         // 或 BotMuteEvent
                "lift_ban" => "MemberUnmuteEvent"   // 或 BotUnmuteEvent
            ),
            "friend_add" => null,
            "group_recall" => "GroupRecallEvent",
            "friend_recall" => "FriendRecallEvent",
            "notify" => array(
                "poke" => "NudgeEvent",                     //戳一戳事件
                "lucky_king" => null,                       //群红包运气王
                "honor" => "MemberSpecialTitleChangeEvent"  //群头衔改动
            )
        ),
        "request" => array(
            "friend" => "NewFriendRequestEvent",
            "group" => array(
                "add" => "MemberJoinRequestEvent",
                "invite" => "BotInvitedJoinGroupRequestEvent"
            )
        ),
        "meta_event" => array(
            "lifecycle" => array(
                "enable" => "BotOnlineEvent",
                "disable" => "BotOfflineEventActive",
                "connect" => 'BotConnected'
            ),
            "heartbeat" => 'BotHeartBeat'
        ),
    );
    if (isset($type_map[$post_type])) {
        if (isset($type_map[$post_type][$X_type])) {
            if (is_array($type_map[$post_type][$X_type])) {
                if (isset($type_map[$post_type][$X_type][$sub_type])) {
                    return $type_map[$post_type][$X_type][$sub_type];
                }
            } else {
                return $type_map[$post_type][$X_type];
            }
        }
    }
    return null;
}

/**
 * 从OneBot中的消息（String）(CQ码)中获取关键节点并以Miria_11规定的数组的方式返回
 */
function OneBot_message2chain($message, $msgID, $time): array
{
    $ps = array();
    $i = 0;
    //寻找切割点
    while (true) {
        $i = strpos($message, '[', $i);
        if ($i === false) {
            break;
        } else {
            $ps[] = $i;
            $ps[] = strpos($message, ']', $i) + 1;
        }
    }
    $ps[] = strlen($message);
    $lp = 0;
    $sa = array();
    $n = count($ps);
    //切割字符串
    for ($i = 0; $i < $n; $i++) {
        $sa[] = substr($message, $lp, $ps[$i] - $lp);
        $lp = $ps[$i];
    }
    unset($message, $ps, $lp);    //释放资源

    $messageChain = array(
        array(
            'type' => 'Source',
            'id' => (strval((int) $msgID) == strval($msgID)) ? ((int) $msgID) : $msgID,
            'time' => (int) $time
        )
    );
    $n = count($sa);
    //分段转换为消息链
    for ($i = 0; $i < $n; $i++) {
        //判断是否为CQ码
        if (substr($sa[$i], 0, 1) === '[') {
            $tmp = OneBot_CQCode2messageChain($sa[$i]);
            if (!empty($tmp)) $messageChain[] = $tmp;
        } else {
            /*
            $sa[$i] = str_replace('\u0026amp;', '&', $sa[$i]);
            $sa[$i] = str_replace('\u0026#91;', '[', $sa[$i]);
            $sa[$i] = str_replace('\u0026#93;', '[', $sa[$i]);*/
            $messageChain[] = array(
                'type' => 'Plain',
                'text' => OneBot_CQEscapeBack($sa[$i])
            );
        }
    }
    return $messageChain;
}

function OneBot_CQCode2messageChain($CQCode)
{
    $CQCode = substr($CQCode, 1, strlen($CQCode) - 2);
    $CQCode = explode(',', $CQCode);
    $type = explode(':', $CQCode[0])[1];
    $d = array('type' => $type);
    $n = count($CQCode);
    for ($i = 1; $i < $n; $i++) {
        $tmp = explode('=', $CQCode[$i]);
        $d[$tmp[0]] = $tmp[1];
    }
    $type = strtolower($type);      //转成小写进行判断，防止传入数据不标准
    switch ($type) {
        case 'at':
            if ($d['qq'] === 'all') $mc = array(
                'type' => 'AtAll'
            );
            else $mc = array(
                'type' => 'At',
                'target' => (int) $d['qq']
            );
            break;

        case 'reply':
            $mc = array(
                'type' => 'Quote',
                'id' => (int) $d['id']
            );
            break;

        case 'face':
            $mc = array(
                'type' => 'Face',
                'faceId' => (int) $d['id'],
                'name' => null
            );
            break;

        case 'image':
            $mc = array(
                'type' => 'Image',
                'imageId' => $d['file'],
                'url' => $d['url'],
                'path' => null,
                'base64' => null
            );
            break;

        case 'record':
            $mc = array(
                'type' => 'Voice',
                'voiceId' => $d['file'],
                'url' => $d['url'],
                'path' => null,
                'base64' => null,
                'length' => null
            );
            break;

        case 'poke':
            $mc = array(
                'type' => 'Poke',
                'name' => ($d['type'] >= 1 && $d['type'] <= 6)
                    ? array('Poke', 'ShowLove', 'Like', 'Heartbroken', 'SixSixSix', 'FangDaZhao')[(int) $d['type']]
                    : 'Poke'
            );
            break;

        case 'json':
            $mc = array(
                'type' => 'Json',
                'json' => $d['data']
            );
            break;

        case 'xml':
            $mc = array(
                'type' => 'Xml',
                'xml' => $d['data']
            );
            break;

        default:
            $mc = false;
            break;
    }
    return isset($mc) ? $mc : false;    //false 表示翻译失败
}

/**
 * OneBot_messageChain2OneBot
 * 将 messageChain 转换为 CQCode
 */
function OneBot_messageChain2OneBot($messageChain)
{
    $CQ = "";
    $n = count($messageChain);
    for ($i = 0; $i < $n; $i++) {
        switch ($messageChain[$i]['type']) {
            case 'Plain':
                $CQ .= $messageChain[$i]['text'];
                break;

            default:
                $CQ .= OneBot_messageChain2CQCode($messageChain[$i]);
                break;
        }
    }
    return $CQ;
}

function OneBot_messageChain2CQCode($messageChain)
{
    $type = '';
    $d = array();
    switch ($messageChain['type']) {
        case 'Face':
            $type = 'face';
            $d = array(
                'id' => (int) $messageChain['faceId']
            );
            break;

        case 'At':
            $type = 'at';
            $d = array(
                'qq' => (int) $messageChain['target']
            );
            break;

        case 'AtAll':
            $type = 'at';
            $d = array(
                'qq' => 'all'
            );
            break;

        case 'Quote':
            $type = 'reply';
            $d = array(
                'id' => (strval((int) $messageChain['id']) === strval($messageChain['id']))
                    ? ((int) $messageChain['id'])
                    : $messageChain['id']
            );
            break;

        case 'Image':
            $type = 'image';
            $d = array(
                'file' => empty($messageChain['url'])
                    ? (empty($messageChain['path'])
                        ? $messageChain['base64']
                        : $messageChain['path'])
                    : $messageChain['url']
            );
            break;

        case 'FlashImage':
            $type = 'image';
            $d = array(
                'file' => empty($messageChain['url'])
                    ? (empty($messageChain['path'])
                        ? $messageChain['base64']
                        : $messageChain['path'])
                    : $messageChain['url'],
                'type' => 'flash'
            );
            break;

        case 'Voice':
            $type = 'record';
            $d = array(
                'file' => empty($messageChain['url'])
                    ? (empty($messageChain['path'])
                        ? $messageChain['base64']
                        : $messageChain['path'])
                    : $messageChain['url']
            );
            break;

        case 'Video':           // 拓展类型
            $type = 'video';
            $d = array(
                'file' => empty($messageChain['url'])
                    ? (empty($messageChain['path'])
                        ? $messageChain['base64']
                        : $messageChain['path'])
                    : $messageChain['url']
            );
            break;

        case 'Poke':
            $type = 'poke';
            switch ($messageChain['name']) {
                case 'Poke':
                    $d = array('type' => 1, 'id' => -1);
                    break;

                case 'ShowLove':
                    $d = array('type' => 2, 'id' => -1);
                    break;

                case 'Like':
                    $d = array('type' => 3, 'id' => -1);
                    break;

                case 'Heartbroken':
                    $d = array('type' => 4, 'id' => -1);
                    break;

                case 'SixSixSix':
                    $d = array('type' => 5, 'id' => -1);
                    break;

                case 'FangDaZhao':
                    $d = array('type' => 6, 'id' => -1);
                    break;

                default:
                    $d = array('type' => 1, 'id' => -1);
                    break;
            }
            break;

        case 'Json':
            $type = 'json';
            $d = array(
                'data' => $messageChain['json']
            );
            break;

        case 'Xml':
            $type = 'xml';
            $d = array(
                'data' => $messageChain['xml']
            );
            break;

        default:
            return '';
    }
    $CQCode = "[CQ:$type,";
    foreach ($d as $key => $val) {
        $val = OneBot_CQEscape($val);
        $CQCode .= "$key=$val,";
    }
    return substr_replace($CQCode, ']', strlen($CQCode) - 1);   //将末尾的 ',' 替换为 ']'
}

function OneBot_CQEscape($str, $comma = false)
{
    $str = str_replace('&', '&amp;', $str);
    $str = str_replace('[', '&#91;', $str);
    $str = str_replace(']', '&#93;', $str);
    if ($comma) $str = str_replace(',', '&#44;', $str);
    return $str;
}

function OneBot_CQEscapeBack($str, $comma = false)
{
    $str = str_replace('&amp;', '&', $str);
    $str = str_replace('&#91;', '[', $str);
    $str = str_replace('&#93;', ']', $str);

    $str = str_replace('\u0026amp;', '&', $str);
    $str = str_replace('\u0026#91;', '[', $str);
    $str = str_replace('\u0026#93;', ']', $str);

    if ($comma) {
        $str = str_replace('&#44;', ',', $str);
        $str = str_replace('\u0026#44;', ',', $str);
    }
    return $str;
}

function OneBot_API_bridge($command, $content = array())
{
    writeLog($command . ' => ' . json_encode($content, JSON_UNESCAPED_UNICODE), __FUNCTION__, 'OneBot', 1);
    if (defined("OneBot")) {
        writeLog("转交给 OneBot_API_bridge_11 处理", '', "OneBot");
        if (OneBot === 11) {
            return OneBot_API_bridge_11($command, $content);
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function OneBot_API_bridge_11($command, $content = array())
{
    $newCommand = OneBot_API_cmd2path11($command);
    if ($newCommand === false) return false;   //翻译失败
    switch ($command) {
        case 'sendFriendMessage':
            $d = array(
                'user_id' => empty($content['target']) ? $content['qq'] : $content['target'],
                'message' => OneBot_messageChain2OneBot($content['messageChain']),
                'auto_escape' => false
            );
            break;

        case 'sendGroupMessage':
            $d = array(
                'group_id' => empty($content['target']) ? $content['group'] : $content['target'],
                'message' => OneBot_messageChain2OneBot($content['messageChain']),
                'auto_escape' => false
            );
            break;

        case 'sendGuildChannelMessage':
            $d = array(
                'guild_id' => $content['guild'],
                'channel_id' => $content['channel'],
                'message' => OneBot_messageChain2OneBot($content['messageChain'])
            );
            break;

        default:
            return false;
            break;
    }
    if (empty($d)) return false;
    return OneBot_API_11($newCommand, $d);
}

function OneBot_API_11($command, $d = array())
{
    writeLog($command . ' => ' . json_encode($d, JSON_UNESCAPED_UNICODE), __FUNCTION__, 'OneBot', 1);

    $d = json_encode($d);
    $header = array('Content-Type: application/json');
    if (!empty(OneBot11_access_token)) $header[] = "Authorization: Bearer " . OneBot11_access_token;

    $url = OneBot11_HTTP_API . '/' . $command;
    writeLog($d, $url, 'OneBot', 1);

    $resp = CurlPOST($d, $url, '', '', $header);
    writeLog($resp, 'OneBot_API_11', 'OneBot', 1);

    $resp = json_decode($resp, true);
    return $resp;
}

/**
 * OneBot_API_cmd2path11
 * 将 mirai-api-http 中的 command 转换为 OneBot11 请求中的 path
 */
function OneBot_API_cmd2path11($command)
{
    $command_map = array(
        'sendFriendMessage' => 'send_private_msg',
        'sendGroupMessage' => 'send_group_msg',
        'sendGuildChannelMessage' => 'send_guild_channel_msg'
    );
    if (empty($command_map[$command])) return false;
    return $command_map[$command];
}
