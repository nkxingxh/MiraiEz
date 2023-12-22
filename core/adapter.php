<?php

/**
 * MiraiEz Copyright (c) 2021-2023 NKXingXh
 * License AGPLv3.0: GNU AGPL Version 3 <https://www.gnu.org/licenses/agpl-3.0.html>
 * This is free software: you are free to change and redistribute it.
 * There is NO WARRANTY, to the extent permitted by law.
 * 
 * Github: https://github.com/nkxingxh/MiraiEz
 */

/**HTTP 适配器相关 */
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
    $USE_HTTP = $webhooked || MIRAIEZ_ADAPTER_ALWAYS_USE_HTTP
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
 * @param bool|null $post 是否使用 POST 方法
 * @param bool $json 是否使用 JSON 编码 (仅限 POST)
 */
function HttpAdapter(string $command, array $content = array(), bool $post = null, bool $json = true)
{
    //OneBot Bridge
    if (defined("OneBot")) {
        writeLog("转交给 OneBot_API_bridge 处理", __FUNCTION__, "OneBot", 1);
        return OneBot_API_bridge($command, $content);
    }

    //使用 GET 请求的命令
    $FUNC_GET = array('about', 'botList', 'countMessage', 'fetchMessage', 'fetchLatestMessage', 'peekMessage', 'peekLatestMessage', 'about', 'messageFromId', 'friendList', 'groupList', 'memberList', 'botProfile', 'friendProfile', 'memberProfile', 'file/list', 'file/info', 'groupConfig', 'memberInfo');
    //自动获取 sessionKey
    if (defined('bot') && empty($content['sessionKey'])) {
        $content['sessionKey'] = getSessionKey(bot);
    } elseif (defined('webhook') && !webhook) {
        $content['sessionKey'] = getSessionKey();
    }

    $try_now = 0;
    do {
        //判断命令应该使用 GET 还是 POST 方式
        if (($post === null) ? in_array($command, $FUNC_GET) : (!$post)) {
            $url = MIRAIEZ_HTTP_API . "/$command?" . http_build_query($content);
            writeLog('GET: ' . $url, __FUNCTION__, 'adapter', 1);
            $resp = CurlGET($url);
        } else {
            $url = MIRAIEZ_HTTP_API . "/$command";
            $payload = $json ? json_encode($content) : $content;
            writeLog('POST: ' . $url, __FUNCTION__, 'adapter', 1);
            $resp = CurlPOST($payload, $url, null, null, $json ? ['Content-Type: application/json'] : []);
        }
        writeLog('Resp: ' . $resp, __FUNCTION__, 'adapter', 1);
        $resp = json_decode($resp, true);

        if (isset($resp['code']) && $resp['code'] == 3) {
            $content['sessionKey'] = getSessionKey(defined('bot') ? bot : 0, true);
        }
        $try_now++;
    } while (($resp === null || (isset($resp['code']) && $resp['code'] == 3)) && $try_now < 3);      //错误重试
    return $resp;
}

function HttpAdapter_verify()
{
    $data = json_encode(array('verifyKey' => MIRAIEZ_HTTP_KEY));
    $resp = CurlPOST($data, MIRAIEZ_HTTP_API . '/verify', null, null, ['Content-Type: application/json']);
    writeLog("响应内容: $resp", 'verify', 'core', 1);
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
    $resp = CurlPOST($data, MIRAIEZ_HTTP_API . '/bind', null, null, ['Content-Type: application/json']);
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
    $resp = CurlPOST($data, MIRAIEZ_HTTP_API . '/release', null, null, ['Content-Type: application/json']);
    $resp = json_decode($resp, true);
    if (empty($resp)) {
    } elseif ($resp['code'] != 0) {
    }
    return $resp;
}

function webhook_whoami()
{
    if (defined('webhook') && webhook)
        return (int) $_SERVER['HTTP_BOT'];
    else return false;
}
