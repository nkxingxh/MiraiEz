<?php

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
    $str = substr($str, 0, $rpos);
    return $str;
}

/**
 * 裁剪某字符串，将指定字符串右边保留
 */
function CutStr_Right($str, $left)
{
    $str = strstr($str, $left);
    $str = substr($str, strlen($left));
    return $str;
}

/**
 *
 * 中英混合的字符串截取
 * @param string $sourcestr
 * @param double $cutlength
 */
function assoc_substr($sourcestr, $cutlength)
{
    $returnstr = '';
    $i = 0;
    $n = 0;
    $str_length = strlen($sourcestr); //字符串的字节数
    while (($n < $cutlength) and ($i <= $str_length)) {
        $temp_str = substr($sourcestr, $i, 1);
        $ascnum = Ord($temp_str);
        //得到字符串中第$i位字符的ascii码
        if ($ascnum >= 224) {
            //如果ASCII位高与224，
            $returnstr = $returnstr . substr($sourcestr, $i, 3);
            //根据UTF-8编码规范，将3个连续的字符计为单个字符
            $i = $i + 3;
            //实际Byte计为3
            $n++;
            //字串长度计1
        } elseif ($ascnum >= 192) {
            //如果ASCII位高与192，
            $returnstr = $returnstr . substr($sourcestr, $i, 2);
            //根据UTF-8编码规范，将2个连续的字符计为单个字符
            $i += 2;
            //实际Byte计为2
            $n++;
            //字串长度计1
        } elseif ($ascnum >= 65 && $ascnum <= 90) {
            //如果是大写字母，
            $returnstr = $returnstr . substr($sourcestr, $i, 1);
            $i++;
            //实际的Byte数仍计1个
            $n++;
            //但考虑整体美观，大写字母计成一个高位字符
        } elseif ($ascnum >= 97 && $ascnum <= 122) {
            $returnstr = $returnstr . substr($sourcestr, $i, 1);
            $i++;
            //实际的Byte数仍计1个
            $n++;
            //但考虑整体美观，大写字母计成一个高位字符
        } else {
            //其他情况下，半角标点符号，
            $returnstr = $returnstr . substr($sourcestr, $i, 1);
            $i++;
            $n = $n + 1;
        }
    }
    return $returnstr;
}

function isEmptyLine($str)
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
function is_json($string)
{
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}

/**
 * 获取随机字符串
 */
function str_rand($length)
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
    $str = preg_replace_callback(
	        '/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },
            $str);

     return $str;
}
