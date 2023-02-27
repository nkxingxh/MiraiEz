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
 * 裁剪字符串
 * 自动在字符串中裁剪两段文本的中间部分
 */
function CutStr($str, $left, $right)
{
    $str = strstr($str, $left);
    $str = substr($str, strlen($left));
    $rpos = strpos($str, $right);
    if ($rpos === false) {
        return $str;
    }
    return substr($str, 0, $rpos);
}

/**
 * 裁剪某字符串，将指定字符串右边保留
 */
function CutStr_Right($str, $left)
{
    $str = strstr($str, $left);
    return substr($str, strlen($left));
}

/**
 *
 * 中英混合的字符串截取
 * @param string $source_str
 * @param double $cut_length
 * @return string
 */
function assoc_substr(string $source_str, float $cut_length): string
{
    $return_str = '';
    $i = 0;
    $n = 0;
    $str_length = strlen($source_str); //字符串的字节数
    while (($n < $cut_length) and ($i <= $str_length)) {
        $temp_str = substr($source_str, $i, 1);
        $asc_num = Ord($temp_str);
        //得到字符串中第$i位字符的ascii码
        if ($asc_num >= 224) {
            //如果ASCII位高与224，
            $return_str = $return_str . substr($source_str, $i, 3);
            //根据UTF-8编码规范，将3个连续的字符计为单个字符
            $i = $i + 3;
            //实际Byte计为3
            $n++;
            //字串长度计1
        } elseif ($asc_num >= 192) {
            //如果ASCII位高与192，
            $return_str = $return_str . substr($source_str, $i, 2);
            //根据UTF-8编码规范，将2个连续的字符计为单个字符
            $i += 2;
            //实际Byte计为2
            $n++;
            //字串长度计1
        } elseif ($asc_num >= 65 && $asc_num <= 90) {
            //如果是大写字母，
            $return_str = $return_str . substr($source_str, $i, 1);
            $i++;
            //实际的Byte数仍计1个
            $n++;
            //但考虑整体美观，大写字母计成一个高位字符
        } elseif ($asc_num >= 97 && $asc_num <= 122) {
            $return_str = $return_str . substr($source_str, $i, 1);
            $i++;
            //实际的Byte数仍计1个
            $n++;
            //但考虑整体美观，大写字母计成一个高位字符
        } else {
            //其他情况下，半角标点符号，
            $return_str = $return_str . substr($source_str, $i, 1);
            $i++;
            $n = $n + 1;
        }
    }
    return $return_str;
}

function isEmptyLine($str): bool
{
    $str = str_replace("\r", "", $str);
    $str = str_replace("\n", "", $str);
    $str = str_replace(" ", "", $str);
    return empty($str);
}

/**
 * findDel
 * 查找并删除指定字符串及其中间的字符串
 */
function str_findDel($str, $left, $right)
{
    while (true) {
        $l = strpos($str, $left);
        if ($l === false) return $str;
        //$l += strlen($left);

        $r = strpos($str, $right);
        if ($r === false) return $str;
        $r += strlen($right);

        $len = $r - $l;

        $str = substr($str, 0, $l) . substr($str, $r);
    }
    return $str;
}

/**
 * 裁剪是否 JSON 格式字符串
 */
function is_json($string): bool
{
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}

/**
 * 获取随机字符串
 */
function str_rand($length): string
{
    //字符组合
    $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $len = strlen($str) - 1;
    $randstr = '';
    for ($i = 0; $i < $length; $i++) {
        $num = mt_rand(0, $len);
        $randstr .= $str[$num];
    }
    return $randstr;
}

function utf8_strlen($str)
{
    $count = 0;
    for ($i = 0; $i < strlen($str); $i++) {
        $value = ord($str[$i]);
        if ($value > 127) {
            $count++;
            if ($value >= 192 && $value <= 223) $i++;
            elseif ($value >= 224 && $value <= 239) $i = $i + 2;
            elseif ($value >= 240 && $value <= 247) $i = $i + 3;
            else die('Not a UTF-8 compatible string');
        }
        $count++;
    }
    return $count;
}

// 过滤掉emoji表情
function filterEmoji($str)
{
    return preg_replace_callback(
        '/./u',
        function (array $match) {
            return strlen($match[0]) >= 4 ? '' : $match[0];
        },
        $str
    );
}

// 随机 UUID
function rand_uuid(): string
{
    $chars = md5(uniqid(mt_rand(), true));
    return substr($chars, 0, 8) . '-'
        . substr($chars, 8, 4) . '-'
        . substr($chars, 12, 4) . '-'
        . substr($chars, 16, 4) . '-'
        . substr($chars, 20, 12);
}

/**
 * str_starts_with_non_native
 * 判断字符串是否以特定字符串开始
 */
function str_starts_with_non_native($haystack, $needle): bool
{
    return strcmp($needle, substr($haystack, 0, strlen($needle))) === 0;
}
