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
 * 写出日志
 * @param string $content       日志内容
 * @param string $module        模块
 * @param string $log_file_name   日志文件名，不需要 .log
 * @param int $level          日志级别 (1 DEBUG, 2 INFO, 3 WARN, 4 ERROR, 5 FATAL)
 */
function writeLog(string $content, string $module = '', string $log_file_name = '', int $level = 2)
{
    if($level < MIRAIEZ_LOGGING_LEVEL) return;
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

function getConfig($configFile = '')
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
