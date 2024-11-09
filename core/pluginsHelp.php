<?php

/**
 * MiraiEz Copyright (c) 2021-2024 NKXingXh
 * License AGPLv3.0: GNU AGPL Version 3 <https://www.gnu.org/licenses/agpl-3.0.html>
 * This is free software: you are free to change and redistribute it.
 * There is NO WARRANTY, to the extent permitted by law.
 * 
 * Github: https://github.com/nkxingxh/MiraiEz
 */

/**
 * 获取插件列表
 */
function pluginsList(bool $provide_infos = false): ?array
{
    $plugins = array(
        'loaded' => array(),    // 已加载但未初始化的插件
        'active' => array(),    // 已加载并激活的插件
        'failed' => array(),    // 初始化失败的插件
        'disabled' => array()   // 已停用的插件
    );
    if (!isset($GLOBALS['_plugins'])) return null;
    foreach ($GLOBALS['_plugins'] as $package => $plugin) {
        if (is_object($plugin['object'] ?? null) && $plugin['hooked'] === null) {
            // 未初始化
            $current_type = 'loaded';
        } elseif (($plugin['object'] ?? null) === false) {
            // 未启用
            $current_type = 'disabled';
        } elseif (($plugin['hooked'] ?? null) === false) {
            // 加载失败
            $current_type = 'failed';
        } else {
            // 正常插件
            $current_type = 'active';
        }
        if ($provide_infos) {
            $plugins[$current_type][] = array(
                'name' => $plugin['name'],
                'author' => $plugin['author'],
                'description' => $plugin['description'],
                'version' => $plugin['version'],
                'package' => $package
            );
        } else {
            $plugins[$current_type][$package] = $plugin['version'];
        }
    }
    return $plugins;
}

/**
 * 判断指定插件是否成功加载
 */
function plugin_isLoaded(string $package): ?bool
{
    global $_plugins;
    if (!array_key_exists($package, $_plugins)) {
        return null;    //插件不存在
    }
    return !(empty($_plugins[$package]['object']) || $_plugins[$package]['hooked'] === false);
}

/**
 * 获取指定插件信息
 */
function plugin_getInfo(string $package): ?array
{
    global $_plugins;
    if (!array_key_exists($package, $_plugins)) {
        return null;    //插件不存在
    }
    return array(
        'name' => $_plugins[$package]['name'],
        'author' => $_plugins[$package]['author'],
        'description' => $_plugins[$package]['description'],
        'version' => $_plugins[$package]['version'],
        'file' => $_plugins[$package]['file']
    );
}

/**
 * 获取当前插件身份
 *
 * @param bool $backtrace 是否使用 debug_backtrace 获取堆栈以取得准确的插件信息
 * @return string|bool 成功则返回插件包名，失败则返回 false
 */
function plugin_whoami(bool $backtrace = MIRAIEZ_PLUGINS_WHOAMI_BACKTRACE)
{
    if ($backtrace) {
        //这种方法更为准确，但是性能更差 (后者性能约为此方法的6倍)
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        //var_dump($backtrace);
        $n = count($backtrace);
        for ($i = 1; $i < $n; $i++) {
            if (
                isset($backtrace[$i]['class']) &&
                $backtrace[$i]['type'] == '->' &&   //限制为非静态调用
                defined($backtrace[$i]['class'] . '::_pluginPackage')
            ) {
                return $backtrace[$i]['class']::_pluginPackage;
            }
        }
        return false;
    } else {
        //这种方法会导致前置插件无法准确获取包名
        return empty($GLOBALS['__pluginPackage__']) ? false : $GLOBALS['__pluginPackage__'];
    }
}

/**
 * 获取前置插件类
 * 
 * @param string $package 插件包名
 */
function plugin_getFrontClass(string $package)
{
    global $_plugins;
    if (!array_key_exists($package, $_plugins)) return null;
    if (
        is_object($_plugins[$package]['object']) &&
        $_plugins[$package]['object']::_pluginFrontLib
    ) return $_plugins[$package]['class'];   //get_class($_plugins[$package]['object']);
    else return false;
}

/**
 * 加载(实例化)前置插件对象
 * 
 * @param string $package 插件包名
 */
function plugin_loadFrontObject(string $package, ...$init_args)
{
    global $_plugins;
    if (!array_key_exists($package, $_plugins)) return null;
    if (
        is_object($_plugins[$package]['object']) &&
        $_plugins[$package]['object']::_pluginFrontLib
    ) return new $_plugins[$package]['object'](...$init_args);
    else return false;
}

/**
 * 写出日志
 * @param string $content       日志内容
 * @param string $module        模块
 * @param string $log_file_name   日志文件名，不需要 .log
 * @param int $level          日志级别 (1 DEBUG, 2 INFO, 3 WARN, 4 ERROR, 5 FATAL)
 */
function writeLog(string $content, string $module = '', string $log_file_name = '', int $level = 2)
{
    if ($level < MIRAIEZ_LOGGING_LEVEL) return;
    if (empty($log_file_name) && defined('webhook') && webhook) {
        if (function_exists('plugin_whoami') && $package = plugin_whoami()) {
            $log_file_name = $package;
        } else $log_file_name = 'pluginParent';
    } elseif (empty($log_file_name)) $log_file_name = 'MiraiEz';

    switch ($level) {
        case 1:
            $level = 'DEBUG';
            break;
        case 2:
            $level = 'INFO';
            break;
        case 3:
            $level = 'WARN';
            break;
        case 4:
            $level = 'ERROR';
            break;
        case 5:
            $level = 'FATAL';
            break;
        default:
            $level = 'UNKNOWN';
    }

    $fileName = baseDir . "/logs/$log_file_name.log";
    makeDir(dirname($fileName));
    file_put_contents($fileName, '[' . date("Y-m-d H:i:s", time()) . " $level]" . (empty($module) ? '' : "[$module]") . " $content\n", LOCK_EX | FILE_APPEND);
}

function getDataDir(): string
{
    if (defined('dataDir')) {
        return dataDir;
    }
    $dir = scandir(baseDir);
    foreach ($dir as $value) {
        if (strlen($value) == 21 && is_dir(baseDir . "/$value") && substr($value, 0, 5) == "data_") {
            return baseDir . "/$value";
        }
    }
    $dir = baseDir . "/data_" . str_rand(16);
    mkdir($dir);
    return $dir;
}

function getConfig(string $configFile = '')
{
    $configFile = str_replace('./', '.', $configFile);
    $configFile = str_replace('.\\', '.', $configFile);
    if (empty($configFile) && defined('webhook') && webhook) {
        if (function_exists('plugin_whoami') && $package = plugin_whoami()) {
            if (MIRAIEZ_PLUGINS_DATA_ISOLATION) {
                $configFile = $package . '/config';
            } else {
                $configFile = $package;
            }
        } else return false;
    } elseif (empty($configFile)) return false;

    $file = dataDir . "/$configFile.json";
    if (!file_exists($file)) {
        saveFile($file, "[]");
        return array();
    }
    $config = file_get_contents($file);
    $config = json_decode($config, true);
    if ($config === null) $config = array();
    return $config;
}

/**
 * 保存配置
 * @param string $configFile configFile 配置文件名 (留空则为当前插件包名)
 * @param array $config config 配置内容 (可进行 JSON 编码的内容)
 * @param int $jsonEncodeFlags jsonEncodeFlags JSON 编码选项
 */
function saveConfig(string $configFile = '', array $config = array(), int $jsonEncodeFlags = JSON_UNESCAPED_UNICODE): bool
{
    $configFile = str_replace('./', '.', $configFile);
    $configFile = str_replace('.\\', '.', $configFile);
    if (empty($configFile) && defined('webhook') && webhook) {
        if (function_exists('plugin_whoami') && $package = plugin_whoami()) {
            if (MIRAIEZ_PLUGINS_DATA_ISOLATION) {
                $configFile = $package . '/config';
            } else {
                $configFile = $package;
            }
        } else return false;
    } elseif (empty($configFile)) return false;

    $configFile = str_replace('./', '.', $configFile);
    $configFile = str_replace('.\\', '.', $configFile);
    $file = dataDir . "/$configFile.json";
    return saveFile($file, json_encode($config, $jsonEncodeFlags));
}

/**
 * 保存文件
 *
 * @param string $fileName 文件名（含相对路径）
 * @param string $text 文件内容
 * @return boolean
 */
function saveFile(string $fileName, string $text): bool
{
    if (!$fileName || !$text)
        return false;
    if (makeDir(dirname($fileName))) {
        if ($fp = fopen($fileName, "w")) {
            if (@fwrite($fp, $text)) {
                fclose($fp);
                return true;
            } else {
                fclose($fp);
                return false;
            }
        }
    }
    return false;
}
/**
 * 连续创建目录
 *
 * @param string $dir 目录字符串
 * @param int $mode 权限数字
 * @return boolean
 */
function makeDir(string $dir, int $mode = 0755): bool
{
    /*function makeDir($dir, $mode="0777") { 此外0777不能加单引号和双引号，
	 加了以后，"0400" = 600权限，处以为会这样，我也想不通*/
    if (empty($dir)) return false;
    if (!file_exists($dir)) {
        return mkdir($dir, $mode, true);
    } else {
        return true;
    }
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
