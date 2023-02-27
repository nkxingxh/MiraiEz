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
 * 自动获取 SessionKey 并绑定 QQ
 */
function getSessionKey($qq = 0, $forceUpdateSessionKey = false)
{
    $file = dataDir . "/session.json";
    if (!file_exists($file)) file_put_contents($file, "[]");
    $session = file_get_contents($file);
    $session = json_decode($session, true);
    if (json_last_error() != JSON_ERROR_NONE || is_array($session) == false) {
        file_put_contents($file, "[]");
        $session = array();
    }

    $n = count($session);
    for ($i = 0; $i < $n; $i++) {
        if ((!empty($session[$i]['qq'])) || $session[$i]['qq'] == $qq || empty($qq)) {
            if (empty($qq)) $qq = $session[$i]['qq'];    //传入 qq 为空时，选择第一个
            if (empty($session[$i]['session']) || $forceUpdateSessionKey) {
                $resp = HttpAdapter_verify();
                if (isset($resp['code']) && $resp['code'] == 0) {
                    $session[$i]['session'] = $resp['session'];
                }
                $resp = HttpAdapter_bind($session[$i]['session'], $qq);
                if (isset($resp['code']) && $resp['code'] == 0) {
                    file_put_contents($file, json_encode($session), LOCK_EX);
                    return $session[$i]['session'];
                }
            } elseif (time() - $session[$i]['time'] <= 1800) {
                return $session[$i]['session'];
            } else {    //定期释放 session 并重新申请 session (现在放到下面那一段去了)
                /*
                HttpAdapter_release($session[$i]['session'], $session[$i]['qq']);
                $data = array('qq' => $qq, 'session' => '', 'time' => time());
                $resp = HttpAdapter_verify();
                if ($resp['code'] == 0) {
                    $data['session'] = $resp['session'];
                }
                $resp = HttpAdapter_bind($data['session'], $qq);
                if ($resp['code'] == 0) {
                    $session[$i] = $data;
                    file_put_contents($file, json_encode($session), LOCK_EX);
                    return $data['session'];
                }*/
                $n = $i;
                break;
            }
        }
    }

    HttpAdapter_release($session[$i]['session'], $session[$i]['qq']);
    $data = array('qq' => $qq, 'session' => '', 'time' => time());
    $resp = HttpAdapter_verify();
    if (isset($resp['code']) && $resp['code'] == 0) {
        writeLog("记录 session: " . $resp['session'], __FUNCTION__, 'easyMirai', 1);
        $data['session'] = $resp['session'];
    }
    writeLog("绑定 [$qq] ...", __FUNCTION__, 'easyMirai', 1);
    $resp = HttpAdapter_bind($data['session'], $qq);
    if (isset($resp['code']) && $resp['code'] == 0) {
        $session[$n] = $data;
        file_put_contents($file, json_encode($session), LOCK_EX);
        return $data['session'];
    }
    writeLog("失败!", __FUNCTION__, 'easyMirai', 1);
    return "";
}

/**
 * 判断指定群是否存在指定成员
 * @param int $groupID      群号（传入true则表示当前收到的消息所在群号）
 * @param int $target       指定QQ号（留空表示Bot的QQ，传入true则表示当前收到的消息的发送者QQ）
 * 
 * @return bool|null         如果该成员在群中返回 true 反之返回 false，失败返回 null
 */
function isInGroup($groupID = true, $target = null)
{

    if ($groupID === true) {
        $groupID = getCurrentGroupId();
        if (!$groupID) return false;
    } else $groupID = (int) $groupID;

    if ($target === true) {
        $target = getCurrentSenderId();
        if (!$target) return false;
    } elseif ($target === null) {
        if (defined('bot')) $target = bot;
    } else $target = (int) $target;

    $resp = memberList($groupID);
    if ($resp['code'] !== 0) {
        return null;
    }

    foreach ($resp['data'] as $v) {
        if ($v['id'] == $target) {
            return true;
        }
    }
    return false;
}

/**
 * 获取 BOT 在群中的权限
 * 返回 MEMBER / ADMINISTRATOR / OWNER / false
 * 返回 false 表示未加群, 返回 null 表示获取当前群失败
 */
function getGroupPermission($groupID = true, $sessionKey = '')
{
    if ($groupID === true) {
        $groupID = getCurrentGroupId();
        if (!$groupID) return null;
    }
    $groupList = groupList($sessionKey);
    if ($groupList['code'] == 0) {
        foreach ($groupList['data'] as $value) {
            if ($value['id'] == $groupID) return $value['permission'];
        }
        unset($value);
    }
    return false;
}

/**
 * 获取消息链中的文本
 */
function messageChain2PlainText($messageChain = null)
{
    if (empty($messageChain) && defined('webhook') && webhook) {
        global $_DATA;
        $messageChain = $_DATA['messageChain'];
    }
    $text = '';
    $n = count($messageChain);
    for ($i = 0; $i < $n; $i++) {
        if ($messageChain[$i]['type'] == 'Plain') {
            $text .= $messageChain[$i]['text'];
        }
    }
    return $text;
}

/**
 * 获取消息链中的图片 Url
 * 返回 Url 数组
 */
function messageChain2ImageUrl($messageChain = null)
{
    if (empty($messageChain) && defined('webhook') && webhook) {
        global $_DATA;
        $messageChain = $_DATA['messageChain'];
    }
    $url = array();
    $n = count($messageChain);
    for ($i = 0; $i < $n; $i++) {
        if ($messageChain[$i]['type'] == 'Image') {
            $url[] = $messageChain[$i]['url'];
        }
    }
    return $url;
}

/**
 * 获取消息链中的 At
 * 返回数组
 */
function messageChain2At($messageChain = null)
{
    if (empty($messageChain) && defined('webhook') && webhook) {
        global $_DATA;
        $messageChain = $_DATA['messageChain'];
    }
    $At = array();
    $n = count($messageChain);
    for ($i = 0; $i < $n; $i++) {
        if ($messageChain[$i]['type'] == 'At') {
            $At[] = $messageChain[$i]['target'];
        } elseif ($messageChain[$i]['type'] == 'AtAll') {
            $At[] = -1;
        }
    }
    return $At;
}

/**
 * 获取消息链中的 Voice URL
 * 返回数组
 */
function messageChain2Voice($messageChain = null)
{
    if (empty($messageChain) && defined('webhook') && webhook) {
        global $_DATA;
        $messageChain = $_DATA['messageChain'];
    }
    $Voice = array();
    $n = count($messageChain);
    for ($i = 0; $i < $n; $i++) {
        if ($messageChain[$i]['type'] == 'Voice') {
            $Voice[] = $messageChain[$i]['url'];
        }
    }
    return $Voice;
}


/**
 * 获取消息链中的引用消息，返回 Quote，无引用返回 false
 */
function messageChain2Quote($messageChain = null)
{
    if (empty($messageChain) && defined('webhook') && webhook) {
        global $_DATA;
        $messageChain = $_DATA['messageChain'];
    }
    foreach ($messageChain as $value) {
        if ($value['type'] == 'Quote') {
            return $value;
        }
    }
    return false;
}

/**
 * 获取消息链中的文件信息，返回 FileID，无文件返回 false
 */
function messageChain2FileId($messageChain = null)
{
    if (empty($messageChain) && defined('webhook') && webhook) {
        global $_DATA;
        $messageChain = $_DATA['messageChain'];
    }
    if (isMessage($_DATA['type'])) {
        //这里不需要考虑消息顺序，故使用 foreach 效率较高
        foreach ($messageChain as $value) {
            if ($value['type'] == 'File') {
                $id = $value['id'];
                break;
            }
        }
        if (isset($id) && $id === true) return false;
    } else return false;
}

/**
 * 回复消息
 * 根据接收的消息类型自动回复消息
 * 可自动判断好友消息/群消息/临时消息
 * 
 * @param array|string $messageChain    消息链
 * @param int $quote                    要引用的消息 ID (0 为不引用, true 为自动引用, 其他 int 整数为 消息ID)
 * @param int $at                       要 @ 的人 (0 为不 @, true 为自动 @, 其他 int 整数为 qq 号或频道 tiny_id)
 */
function replyMessage($messageChain, $quote = 0, $at = 0, $sessionKey = '')
{
    $messageChain = is_array($messageChain) ? $messageChain : getMessageChain($messageChain);
    global $_DATA, $_ImageUrl;
    //在临时消息中,回复带有图片的消息会出 bug
    if ($quote === true && isset($_DATA['messageChain'][0]['id']) && ($_DATA['type'] === 'TempMessage' && count($_ImageUrl) > 0) == false) {
        $quote = $_DATA['messageChain'][0]['id'];
    } else $quote = (int) $quote;
    $at = in_array($_DATA['type'], ['GroupMessage', 'GuildChannelMessage']) ? ($at === true ? $_DATA['sender']['id'] : $at) : 0;
    if (webhook) {
        if ($_DATA['type'] == 'FriendMessage') {
            return sendFriendMessage($_DATA['sender']['id'], $messageChain, $quote, $sessionKey);
        } elseif ($_DATA['type'] == 'GroupMessage') {
            if (!empty($at)) $messageChain = array_merge([getMessageChain_At($at)], $messageChain);
            return sendGroupMessage($_DATA['sender']['group']['id'], $messageChain, $quote, $sessionKey);
        } elseif ($_DATA['type'] == 'TempMessage') {
            return sendTempMessage($_DATA['sender']['id'], $_DATA['sender']['group']['id'], $messageChain, $quote, $sessionKey);
        } elseif ($_DATA['type'] == 'GuildChannelMessage') {
            if (!empty($at)) $messageChain = array_merge([getMessageChain_At($at)], $messageChain);
            return sendGuildChannelMessage($_DATA['sender']['guild']['id'], $_DATA['sender']['guild']['channel']['id'], $messageChain, $quote);
        } elseif (isset($_DATA['member']['group']['id'])) {  //其他可能的群消息/事件
            if (!empty($at)) $messageChain = array_merge([getMessageChain_At($at)], $messageChain);
            return sendGroupMessage($_DATA['member']['group']['id'], $messageChain, $quote, $sessionKey);
        } else return false;
    } else return false;
}

/**
 * 获取消息链
 * @param string @PlainText             消息文本
 * @param string|array $Images          图片链接或 base64 (可以是数组)
 * @param int|array  $AtTarget          要 At 的 QQ 号(可以是数组)
 */
function getMessageChain($PlainText = '', $Images = '', $AtTarget = 0)
{
    $MessageChain = array();
    if (!empty($AtTarget)) {
        if (is_array($AtTarget)) {
            $n = count($AtTarget);
            for ($i = 0; $i < $n; $i++) {
                $MessageChain[] = getMessageChain_At($AtTarget[$i]);
            }
        } else $MessageChain[] = getMessageChain_At($AtTarget);
        $MessageChain[] = getMessageChain_PlainText(' ');       //加一个空格更美观
    }

    if (!empty($PlainText)) {
        if (is_array($PlainText)) {
            $n = count($PlainText);
            for ($i = 0; $i < $n; $i++) {
                $MessageChain[] = getMessageChain_PlainText($PlainText[$i]);
            }
        } else $MessageChain[] = getMessageChain_PlainText($PlainText);
    }


    if (!empty($Images)) {
        if (!is_array($Images)) {
            $Images = array($Images);
        }
        $n = count($Images);
        for ($i = 0; $i < $n; $i++) {
            if (
                strtolower(substr($Images[$i], 0, 7)) == 'http://' ||
                strtolower(substr($Images[$i], 0, 8)) == 'https://'
            ) {
                $MessageChain[] = getMessageChain_Image($Images[$i]);
            } else {
                $MessageChain[] = getMessageChain_Image(null, $Images[$i]);
            }
        }
    }

    return $MessageChain;
}

function getMessageChain_PlainText($PlainText)
{
    return array('type' => 'Plain', 'text' => $PlainText);
}

function getMessageChain_At($target)
{
    return array('type' => 'At', 'target' => $target);
}

/**
 * 生产消息链中的图片类型成员 (参数二选一)
 * @param string ImageUrl 图片链接
 * @param string ImageBase64 图片 BASE64 编码后的内容
 */
function getMessageChain_Image($ImageUrl = null, $ImageBase64 = null)
{
    return array('type' => 'Image', 'url' => $ImageUrl, 'base64' => $ImageBase64);
}

function getMessageChain_Json($json)
{
    return array('type' => 'Json', 'json' => $json);
}

function isMessage($type = true)
{
    if ($type === true && webhook) {
        global $_DATA;
        $type = $_DATA['type'];
    }
    return $type == 'GroupMessage' || $type == 'FriendMessage' || $type == 'TempMessage' || $type == 'GuildChannelMessage';
}

/**
 * 获取图片类型
 * @param string $image     图片内容
 */
function getImageType($image)
{
    do {    //取一个没有被占用的文件名
        $filename = baseDir . '/tmp/img_' . str_rand(8) . '.img';
    } while (file_exists($filename));
    if (file_put_contents($filename, $image) === false) return false;
    $imginfo = getimagesize($filename);
    unlink($filename);
    if ($imginfo === false) return false;
    $ext = CutStr_Right($imginfo['mime'], '/');
    if ($ext == 'jpeg') $ext = 'jpg';
    return $ext;
}

/**
 * desription 压缩图片
 * @param string $image     原图片内容
 * @param int $maxWidth     最大宽度
 * @param int $maxHeight    最大高度
 * @param int $quality      压缩质量
 */
function compressedImage($OriginImage, $maxWidth = 2000, $maxHeight = 2000, $quality = 68, $optType = 'jpg')
{
    do {    //取一个没有被占用的文件名
        $imgsrc = baseDir . '/tmp/img_' . str_rand(8) . '.img';
    } while (file_exists($imgsrc));

    do {    //取一个没有被占用的文件名
        $imgdst = baseDir . '/tmp/img_' . str_rand(8) . '.jpg';
    } while (file_exists($imgdst));

    saveFile($imgsrc, $OriginImage);

    list($width, $height, $type) = getimagesize($imgsrc);

    $new_width = $width; //压缩后的图片宽
    $new_height = $height; //压缩后的图片高

    if ($width > $maxWidth || $height > $maxHeight) {
        $per = min($maxWidth / $width, $maxHeight / $height); //计算比例
        $new_width = $width * $per;
        $new_height = $height * $per;
    }

    $image_wp = imagecreatetruecolor($new_width, $new_height);

    switch ($type) {
        case 1:
            $giftype = check_gifcartoon($imgsrc);
            if ($giftype) {
                $image = imagecreatefromgif($imgsrc);
                imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            } //else return false;  //在此处处理会导致无法清理图片
            break;
        case 2:
            $image = imagecreatefromjpeg($imgsrc);
            imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            break;
        case 3:
            $image = imagecreatefrompng($imgsrc);
            imagecopyresampled($image_wp, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            break;
    }
    unlink($imgsrc);        //删除源文件
    if (empty($image)) return false;
    imagedestroy($image);   //释放内存

    $result = ($optType == 'png') ? imagepng($image_wp, $imgdst, $quality) : imagejpeg($image_wp, $imgdst, $quality);
    imagedestroy($image_wp);    //释放内存

    if ($result) {
        $d = file_get_contents($imgdst);
        unlink($imgdst);

        //防止越压越大, 亲身经历
        if (strlen($d) < strlen($OriginImage)) return $d;
        else return $OriginImage;
    } else {
        unlink($imgdst);
        return false;
    }
}

/**
 * 判断是否为 gif 格式文件
 * @param string $image_file    要判断的文件路径
 */
function check_gifcartoon($image_file)
{
    $fp = fopen($image_file, 'rb');
    $image_head = fread($fp, 1024);
    fclose($fp);
    return preg_match("/" . chr(0x21) . chr(0xff) . chr(0x0b) . 'NETSCAPE2.0' . "/", $image_head) ? false : true;
}

/**
 * 为私聊或群聊启动 session
 * 
 * @param bool $isolate_users   是否隔离不同用户的会话
 * @param bool $isolate_groups  是否隔离不同群的会话
 * @param bool $isolate_PG      是否隔离群聊和私聊消息 (当该参数为 false 时, $isolate_groups 参数不生效)
 */
function mirai_session_start($isolate_users = true, $isolate_groups = true, $isolate_PG = true)
{
    if (defined('webhook') && webhook) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }
        global $_DATA;
        $uid = $isolate_users ? $_DATA['sender']['id'] : '';
        if ($_DATA['type'] == 'GroupMessage' && $isolate_PG) {
            $gid = $isolate_groups ? $_DATA['sender']['group']['id'] : '';
            $sid = 'G' . $uid . '-' . $gid;
        } else {
            $sid = 'P' . $uid;
        }
        session_id($sid);
        return session_start();
    } else {
        return false;
    }
}

/**
 * 获取当前消息发送者 (或事件触发者) 的 qq
 * @return int|false
 */
function getCurrentSenderId()
{
    if (isset($GLOBALS['_DATA']['sender']['id'])) return $GLOBALS['_DATA']['sender']['id'];
    if (isset($GLOBALS['_DATA']['member']['id'])) return $GLOBALS['_DATA']['member']['id'];
    if (isset($GLOBALS['_DATA']['fromId'])) return $GLOBALS['_DATA']['fromId'];
    return false;
}

/**
 * 获取当前 webhook 上报的群号 (或事件触发所在群)
 * @return int|false
 */
function getCurrentGroupId()
{
    if (isset($GLOBALS['_DATA']['sender']['group']['id'])) return $GLOBALS['_DATA']['sender']['group']['id'];
    if (isset($GLOBALS['_DATA']['member']['group']['id'])) return $GLOBALS['_DATA']['member']['group']['id'];
    if (isset($GLOBALS['_DATA']['groupId'])) return $GLOBALS['_DATA']['groupId'];
    return false;
}
