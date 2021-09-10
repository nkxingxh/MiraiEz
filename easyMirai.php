<?php

/**
 * 获取 BOT 在群中的权限
 * 返回 MEMBER / ADMINISTRATOR / OWNER / false
 * 返回 false 表示未加群
 */
function getGroupPermission($groupID, $sessionKey = '')
{
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
        if ($id === true) return false;
    } else return false;
    return $id;
}

/**
 * 回复消息
 * 根据接收的消息类型自动回复消息
 * 可自动判断好友消息/群消息/临时消息
 * 
 * @param array|string $messageChain    消息链
 * @param int $quote                    要引用的消息 ID (0 为不引用, true 为自动引用, 其他 int 整数为 消息ID)
 */
function replyMessage($messageChain, $quote = 0, $sessionKey = '')
{
    global $_DATA, $_ImageUrl;
    //在临时消息中,回复带有图片的消息会出 bug
    if ($quote === true && (!($_DATA['type'] == 'TempMessage' && count($_ImageUrl) > 0))) $quote = $_DATA['messageChain'][0]['id'];
    else $quote = 0;
    if (webhook) {
        if ($_DATA['type'] == 'FriendMessage') {
            sendFriendMessage($_DATA['sender']['id'], $messageChain, $quote, $sessionKey);
        } elseif ($_DATA['type'] == 'GroupMessage') {
            sendGroupMessage($_DATA['sender']['group']['id'], $messageChain, $quote, $sessionKey);
        } elseif ($_DATA['type'] == 'TempMessage') {
            sendTempMessage($_DATA['sender']['id'], $_DATA['sender']['group']['id'], $messageChain, $quote, $sessionKey);
        }
    }
}

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
                $res = HttpAdapter_verify();
                if ($res['code'] == 0) {
                    $session[$i]['session'] = $res['session'];
                }
                $res = HttpAdapter_bind($session[$i]['session'], $qq);
                if ($res['code'] == 0) {
                    file_put_contents($file, json_encode($session), LOCK_EX);
                    return $session[$i]['session'];
                }
            } elseif (time() - $session[$i]['time'] <= 1800) {
                return $session[$i]['session'];
            } else {    //定期释放 session 并重新申请 session (现在放到下面那一段去了)
                /*HttpAdapter_release($session[$i]['session'], $session[$i]['qq']);
                $data = array('qq' => $qq, 'session' => '', 'time' => time());
                $res = HttpAdapter_verify();
                if ($res['code'] == 0) {
                    $data['session'] = $res['session'];
                }
                $res = HttpAdapter_bind($data['session'], $qq);
                if ($res['code'] == 0) {
                    $session[$i] = $data;
                    file_put_contents($file, json_encode($session), LOCK_EX);
                    return $data['session'];
                }*/
                $n = $i;
                break;
            }
        }
    }

    $data = array('qq' => $qq, 'session' => '', 'time' => time());
    $res = HttpAdapter_verify();
    if ($res['code'] == 0) {
        $data['session'] = $res['session'];
    }
    $res = HttpAdapter_bind($data['session'], $qq);
    if ($res['code'] == 0) {
        $session[$n] = $data;
        file_put_contents($file, json_encode($session), LOCK_EX);
        return $data['session'];
    }

    return "";
}

/**
 * 获取消息链
 * @param string @PlainText             消息文本
 * @param string|array $ImageUrl        图片链接(可以是数组)
 * @param int|array  $AtTarget          要 At 的 QQ 号(可以是数组)
 */
function getMessageChain($PlainText = '', $ImageUrl = '', $AtTarget = 0)
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

    if (!empty($ImageUrl)) {
        if (is_array($ImageUrl)) {
            $n = count($ImageUrl);
            for ($i = 0; $i < $n; $i++) {
                $MessageChain[] = getMessageChain_Image($ImageUrl[$i]);
            }
        } else {
            $MessageChain[] = getMessageChain_Image($ImageUrl);
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

function getMessageChain_Image($ImageUrl)
{
    return array('type' => 'Image', 'url' => $ImageUrl);
}

function isMessage($type)
{
    return $type == 'GroupMessage' || $type == 'FriendMessage' || $type == 'TempMessage';
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
    if (!file_put_contents($filename, $image)) return false;
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
            } else return false;
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

    $result = ($optType == 'png') ? imagepng($image_wp, $imgdst, $quality) : imagejpeg($image_wp, $imgdst, $quality);

    //释放资源
    imagedestroy($image_wp);
    imagedestroy($image);
    unlink($imgsrc);

    if ($result) {
        $d = file_get_contents($imgdst);
        unlink($imgdst);

        //防止越压越大, 亲身经历
        if (strlen($d) < strlen($OriginImage)) return $d;
        else return $OriginImage;
    } else {
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
 * 为私聊会话或私聊启动 session
 */
function mirai_session_start()
{
    if (defined('webhook') && webhook) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }
        global $_DATA;

        if ($_DATA['type'] == 'GroupMessage') {
            $sid = 'G' . $_DATA['sender']['id'] . '-' . $_DATA['sender']['group']['id'];
        } else {
            $sid = 'P' . $_DATA['sender']['id'];
        }
        session_id($sid);
        return session_start();
    } else {
        return false;
    }
}
