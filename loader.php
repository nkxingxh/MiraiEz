<?php
/**
 * MiraiEz Copyright (c) 2021-2023 NKXingXh
 * License AGPLv3.0: GNU AGPL Version 3 <https://www.gnu.org/licenses/agpl-3.0.html>
 * This is free software: you are free to change and redistribute it.
 * There is NO WARRANTY, to the extent permitted by law.
 * 
 * Github: https://github.com/nkxingxh/MiraiEz
 */

if (defined('mdm_cli')) { //如果在 mdm_cli 中运行则定义全局变量
    global $baseDir, $dataDir;
}
$baseDir = empty($_SERVER['DOCUMENT_ROOT']) ? __DIR__ : $_SERVER['DOCUMENT_ROOT'];
define('baseDir', $baseDir);            //定义站点目录

if (!defined("webhook")) define("webhook", false);

require_once "$baseDir/pfa.php";
require_once "$baseDir/errorHandle.php";
require_once "$baseDir/config.php";
require_once "$baseDir/string.php";
require_once "$baseDir/curl.php";
require_once "$baseDir/easyMirai.php";
require_once "$baseDir/pluginsHelp.php";
require_once "$baseDir/OneBotBridge.php";

$dataDir = getDataDir();
define("dataDir", $dataDir);
define("version", '2.2.1');

require_once "core.php";

if (pfa) $pfa_loadedTime = microtime(true);
