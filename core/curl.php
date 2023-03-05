<?php

/**
 * MiraiEz Copyright (c) 2021-2023 NKXingXh
 * License AGPLv3.0: GNU AGPL Version 3 <https://www.gnu.org/licenses/agpl-3.0.html>
 * This is free software: you are free to change and redistribute it.
 * There is NO WARRANTY, to the extent permitted by law.
 * 
 * Github: https://github.com/nkxingxh/MiraiEz
 */

function CurlGET($url, $cookie = '', $referer = '', $header = array(), $setopt = array(), $UserAgent = 'MiraiEz')
{
    return Curl(null, $url, $cookie, $referer, $header, $setopt, $UserAgent);
}

function CurlPOST($payload, $url, $cookie = '', $referer = '', $header = array(), $setopt = array(), $UserAgent = 'MiraiEz')
{
    //$setopt[] = [CURLOPT_POST, 1];    //当设置了 CURLOPT_POSTFIELDS 时, CURLOPT_POST 默认为 1
    //$setopt[] = [CURLOPT_POSTFIELDS, $payload];
    return Curl($payload, $url, $cookie, $referer, $header, $setopt, $UserAgent);
}

function CurlPUT($payload, $url, $cookie = '', $referer = '', $header = array(), $setopt = array(), $UserAgent = 'MiraiEz')
{
    $setopt[] = [CURLOPT_CUSTOMREQUEST, 'PUT'];
    return Curl($payload, $url, $cookie, $referer, $header, $setopt, $UserAgent);
}

function CurlPATCH($payload, $url, $cookie = '', $referer = '', $header = array(), $setopt = array(), $UserAgent = 'MiraiEz')
{
    $setopt[] = [CURLOPT_CUSTOMREQUEST, 'PATCH'];
    return Curl($payload, $url, $cookie, $referer, $header, $setopt, $UserAgent);
}

function CurlDELETE($payload, $url, $cookie = '', $referer = '', $header = array(), $setopt = array(), $UserAgent = 'MiraiEz')
{
    $setopt[] = [CURLOPT_CUSTOMREQUEST, 'DELETE'];
    return Curl($payload, $url, $cookie, $referer, $header, $setopt, $UserAgent);
}

function Curl($payload, $url, $cookie = null, $referer = null, $header = array(), $setopt = array(), $UserAgent = 'MiraiEz')
{
    $header = is_array($header) ? $header : array($header);
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);
    if (!empty($header)) curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    if (!empty($referer)) curl_setopt($curl, CURLOPT_REFERER, $referer);
    if (!empty($cookie)) curl_setopt($curl, CURLOPT_COOKIE, $cookie);
    if (!empty($payload)) curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);

    #关闭SSL
    //curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    //curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    #返回数据不直接显示
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    //适配 gzip 压缩
    curl_setopt($curl, CURLOPT_ENCODING, 'gzip, deflate');

    if (!empty($setopt) && is_array($setopt)) {
        $n = count($setopt);
        for($i = 0; $i < $n; $i++) {
            curl_setopt($curl, $setopt[$i][0], $setopt[$i][1]);
        }
    }

    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}
