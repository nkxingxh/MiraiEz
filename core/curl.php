<?php

/**
 * MiraiEz Copyright (c) 2021-2024 NKXingXh
 * License AGPLv3.0: GNU AGPL Version 3 <https://www.gnu.org/licenses/agpl-3.0.html>
 * This is free software: you are free to change and redistribute it.
 * There is NO WARRANTY, to the extent permitted by law.
 * 
 * Github: https://github.com/nkxingxh/MiraiEz
 */

define('MIRAIEZ_CURL_DEBUG', false);

function CurlGET($url, $cookie = null, $referer = null, $header = array(), $setopt = array(), $UserAgent = 'MiraiEz', ...$other)
{
    return Curl(null, $url, $cookie, $referer, $header, $setopt, $UserAgent, ...$other);
}

function CurlPOST($payload, $url, $cookie = null, $referer = null, $header = array(), $setopt = array(), $UserAgent = 'MiraiEz', ...$other)
{
    //$setopt[] = [CURLOPT_POST, 1];    //当设置了 CURLOPT_POSTFIELDS 时, CURLOPT_POST 默认为 1
    //$setopt[] = [CURLOPT_POSTFIELDS, $payload];
    return Curl($payload, $url, $cookie, $referer, $header, $setopt, $UserAgent, ...$other);
}

function CurlPUT($payload, $url, $cookie = null, $referer = null, $header = array(), $setopt = array(), $UserAgent = 'MiraiEz', ...$other)
{
    $setopt[] = [CURLOPT_CUSTOMREQUEST, 'PUT'];
    return Curl($payload, $url, $cookie, $referer, $header, $setopt, $UserAgent, ...$other);
}

function CurlPATCH($payload, $url, $cookie = null, $referer = null, $header = array(), $setopt = array(), $UserAgent = 'MiraiEz', ...$other)
{
    $setopt[] = [CURLOPT_CUSTOMREQUEST, 'PATCH'];
    return Curl($payload, $url, $cookie, $referer, $header, $setopt, $UserAgent, ...$other);
}

function CurlDELETE($payload, $url, $cookie = null, $referer = null, $header = array(), $setopt = array(), $UserAgent = 'MiraiEz', ...$other)
{
    $setopt[] = [CURLOPT_CUSTOMREQUEST, 'DELETE'];
    return Curl($payload, $url, $cookie, $referer, $header, $setopt, $UserAgent, ...$other);
}

function Curl($payload, $url, $cookie = null, $referer = null, $header = array(), $setopt = array(), $UserAgent = 'MiraiEz', &$curl = null)
{
    $header = is_array($header) ? $header : array($header);
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.0.0 Safari/537.36');
    if (!empty($header)) {
        // KV 数组转数字索引
        foreach ($header as $key => $value) {
            if (is_string($key)) {
                $header[] = "$key: $value";
                unset($header[$key]);
            }
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    }
    if (!empty($referer)) curl_setopt($curl, CURLOPT_REFERER, $referer);
    if (!empty($cookie)) curl_setopt($curl, CURLOPT_COOKIE, $cookie);
    if (!empty($payload)) curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);

    // 关闭 SSL
    // curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    // curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

    // 返回数据不直接显示
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    // 适配 gzip 压缩
    curl_setopt($curl, CURLOPT_ENCODING, 'gzip, deflate');

    if (!empty($setopt) && is_array($setopt)) {
        $n = count($setopt);
        for($i = 0; $i < $n; $i++) {
            curl_setopt($curl, $setopt[$i][0], $setopt[$i][1]);
        }
    }

    if (MIRAIEZ_CURL_DEBUG) {
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        writeLog($url, '请求', 'curl', 1);
    }

    $response = curl_exec($curl);

    if (MIRAIEZ_CURL_DEBUG) {
        $req_header = curl_getinfo($curl, CURLINFO_HEADER_OUT);
        writeLog("请求头信息:\n$req_header", '请求', 'curl', 1);
        writeLog(curl_getinfo($curl, CURLINFO_HTTP_CODE) . "\n" . $response, '响应', 'curl', 1);
    }

    // 如果传入了 $curl 参数则不释放
    if (!array_key_exists(7, func_get_args())) {
        curl_close($curl);
    }
    return $response;
}
