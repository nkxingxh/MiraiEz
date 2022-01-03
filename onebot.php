<?php
define("OneBot_secret", "");                //OneBot 11 的 HMAC SHA1 验证密钥
define("OneBot_access_token", "");          //OneBot 12 的访问令牌

/**
 * OneBot_auth
 * 验证请求是否为 OneBot 标准实现发出的
 * 如果是，则将消息标准化为 mirai-api-http 格式且返回 true
 * 验证失败则返回 false
 */
function OneBot_auth()
{
    $auth = false;
    if (isset($_SERVER['HTTP_AUTHORIZATION']) && $_SERVER['HTTP_AUTHORIZATION'] === ("Bearer " . OneBot_access_token)) {
        $auth = true;
        //OneBot_12mirai(null, true);
    } elseif (isset($_SERVER['HTTP_SIGNATURE']) && $_SERVER['HTTP_SIGNATURE'] === hash_hmac("sha1", file_get_contents("php://input"), OneBot_secret, false)) {
        $auth = true;
        //OneBot_11mirai(null, true);
    }
    return $auth;
}

/**
 * OneBot_11mirai
 * 将数据标准化
 */
function OneBot_11mirai($d = null, $replace_input = true)
{
    if ($d === null)
        $d = json_decode(file_get_contents("php://input"), true);

    $type = OneBot_11mirai_getType($d['post_type'], $d[$d['post_type'] . '_type'], isset($d['sub_type']) ? $d['sub_type'] : null);
    switch ($d['post_type']) {
        case 'message':
            switch ($d[$d['post_type'] . '_type']) {
                case 'private':
                    if($type == 'TempMessage') {
                        
                    } else {

                    }
                    break;

                case 'group':
                    break;
            }
            $new = array(
                'type' => $type,
                'sender' => $sender,
                'messageChain' => null
            );
            break;
    }
}

function OneBot_12mirai($d = null, $replace_input = true)
{
    if ($d === null)
        $d = json_decode(file_get_contents("php://input"), true);
}

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
                "connect" => null
            ),
            "heartbeat" => null
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
