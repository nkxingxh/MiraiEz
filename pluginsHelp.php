<?php

/**
 * 写出日志
 * @param string    $content       日志内容
 * @param string    $module        模块
 * @param string    $logfilename   日志文件名，不需要 .log
 * @param int       $level          日志级别 (1 DEBUG, 2 INFO, 3 WARN, 4 ERROR, 5 FATAL)
 */
function writeLog($content, $module = '', $logfilename = '', $level = 2)
{
    if($level < logging_level) return;
    if (empty($logfilename) && defined('webhook') && webhook) {
        if (function_exists('plugin_whoami') && $package = plugin_whoami()) {
            $logfilename = $package;
        } else $logfilename = 'pluginParent';
    } elseif (empty($logfilename)) $logfilename = 'MiraiEz';

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

    $fileName = baseDir . "/logs/$logfilename.log";
    makeDir(dirname($fileName));
    file_put_contents($fileName, '[' . date("Y-m-d H:i:s", time()) . " $level]" . (empty($module) ? '' : "[$module]") . " $content\n", LOCK_EX | FILE_APPEND);
}

function getDataDir()
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
            if (plugins_data_isolation) {
                if (empty($configFile)) $configFile = 'config';
                $configFile = $package . '/' . $configFile;
            } else {
                if (empty($configFile)) $configFile = $package;
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

function saveConfig($configFile = '', $config, $jsonEncodeFlags = JSON_UNESCAPED_UNICODE)
{
    $configFile = str_replace('./', '.', $configFile);
    $configFile = str_replace('.\\', '.', $configFile);
    if (empty($configFile) && defined('webhook') && webhook) {
        if (function_exists('plugin_whoami') && $package = plugin_whoami()) {
            if (plugins_data_isolation) {
                if (empty($configFile)) $configFile = 'config';
                $configFile = $package . '/' . $configFile;
            } else {
                if (empty($configFile)) $configFile = $package;
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
function saveFile($fileName, $text)
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
function makeDir($dir, $mode = 0755)
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
