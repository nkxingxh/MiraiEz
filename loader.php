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
$coreDir = "$baseDir/core";

if (!defined("webhook")) define("webhook", false);

require_once "$coreDir/pfa.php";
require_once "$coreDir/errorHandle.php";
require_once "$baseDir/config.php";
require_once "$coreDir/string.php";
require_once "$coreDir/curl.php";
require_once "$coreDir/easyMirai.php";
require_once "$coreDir/pluginsHelp.php";
require_once "$coreDir/OneBotBridge.php";

$dataDir = getDataDir();
define("dataDir", $dataDir);
define("version", '2.3.0');

require_once "$coreDir/core.php";

if (pfa) $pfa_loadedTime = microtime(true);
