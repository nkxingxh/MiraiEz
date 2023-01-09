<?php

function CurlGET($url, $cookie = '', $referer = '', $header = '', $setopt = array(), $UserAgent = 'miraiez')
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    if ($UserAgent != "")
        curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);
    if ($header != '')
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    if ($referer != '')
        curl_setopt($curl, CURLOPT_REFERER, $referer);
    if ($cookie != '')
        curl_setopt($curl, CURLOPT_COOKIE, $cookie);
    #关闭SSL
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    if (!empty($setopt) && is_array($setopt)) {
        foreach ($setopt as $value) {
            curl_setopt($curl, $value[0], $value[1]);
        }
    }
    #返回数据不直接显示
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);

    //适配 gzip 压缩
    curl_setopt($curl, CURLOPT_ENCODING, 'gzip, deflate');

    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

function CurlPOST($payload, $url, $cookie = '', $referer = '', $header = array(), $setopt = array(), $UserAgent = 'miraiez')
{
    if (logging_level == 1) {
        writeLog('URL: ' . $url, __FUNCTION__, 'curl', 1);
        writeLog('Payload: ' . (is_array($payload) ? ('(Array) ' . json_encode($payload, JSON_UNESCAPED_UNICODE)) : $payload), __FUNCTION__, 'curl', 1);
    }
    $header = is_array($header) ? $header : array();
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);
    if (count($header) > 0)
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    if ($referer != '')
        curl_setopt($curl, CURLOPT_REFERER, $referer);
    if ($cookie != '')
        curl_setopt($curl, CURLOPT_COOKIE, $cookie);
    if (!empty($setopt) && is_array($setopt)) {
        foreach ($setopt as $value) {
            curl_setopt($curl, $value[0], $value[1]);
        }
    }
    #关闭SSL
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    #返回数据不直接显示
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);

    //适配 gzip 压缩
    curl_setopt($curl, CURLOPT_ENCODING, 'gzip, deflate');

    $response = curl_exec($curl);
    curl_close($curl);

    writeLog('Resp: ' . $response, __FUNCTION__, 'curl', 1);
    return $response;
}

function CurlPUT($payload, $url, $cookie = '', $referer = '', $header = array(), $setopt = array(), $UserAgent = 'miraiez')
{
    $header = is_array($header) ? $header : array();
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);
    if (count($header) > 0)
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    if ($referer != '')
        curl_setopt($curl, CURLOPT_REFERER, $referer);
    if ($cookie != '')
        curl_setopt($curl, CURLOPT_COOKIE, $cookie);
    if (!empty($setopt) && is_array($setopt)) {
        foreach ($setopt as $value) {
            curl_setopt($curl, $value[0], $value[1]);
        }
    }
    #关闭SSL
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    #返回数据不直接显示
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

function CurlPATCH($payload, $url, $cookie = '', $referer = '', $header = array(), $setopt = array(), $UserAgent = 'miraiez')
{
    $header = is_array($header) ? $header : array();
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);
    if (count($header) > 0)
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    if ($referer != '')
        curl_setopt($curl, CURLOPT_REFERER, $referer);
    if ($cookie != '')
        curl_setopt($curl, CURLOPT_COOKIE, $cookie);
    if (!empty($setopt) && is_array($setopt)) {
        foreach ($setopt as $value) {
            curl_setopt($curl, $value[0], $value[1]);
        }
    }
    #关闭SSL
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    #返回数据不直接显示
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

function CurlDELETE($payload, $url, $cookie = '', $referer = '', $header = array(), $setopt = array(), $UserAgent = 'miraiez')
{
    $header = is_array($header) ? $header : array();
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);
    if (count($header) > 0)
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    if ($referer != '')
        curl_setopt($curl, CURLOPT_REFERER, $referer);
    if ($cookie != '')
        curl_setopt($curl, CURLOPT_COOKIE, $cookie);
    if (!empty($setopt) && is_array($setopt)) {
        foreach ($setopt as $value) {
            curl_setopt($curl, $value[0], $value[1]);
        }
    }
    #关闭SSL
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    #返回数据不直接显示
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}

function Curl($payload, $url, $cookie = '', $referer = '', $header = array(), $setopt = array(), $UserAgent = 'miraiez')
{
    $header = is_array($header) ? $header : array();
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);
    if (count($header) > 0)
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    if ($referer != '')
        curl_setopt($curl, CURLOPT_REFERER, $referer);
    if ($cookie != '')
        curl_setopt($curl, CURLOPT_COOKIE, $cookie);
    #关闭SSL
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    #返回数据不直接显示
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);

    if (!empty($setopt) && is_array($setopt)) {
        foreach ($setopt as $value) {
            curl_setopt($curl, $value[0], $value[1]);
        }
    }

    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}
