<?php

/**
 * MiraiEz Copyright (c) 2021-2024 NKXingXh
 * License AGPLv3.0: GNU AGPL Version 3 <https://www.gnu.org/licenses/agpl-3.0.html>
 * This is free software: you are free to change and redistribute it.
 * There is NO WARRANTY, to the extent permitted by law.
 * 
 * Github: https://github.com/nkxingxh/MiraiEz
 */

if (defined('mdm_cli') || !empty(__FUNCTION__)) { //如果在 mdm_cli 中运行 (或在函数中运行) 则定义全局变量
    global $baseDir, $dataDir;
}
$baseDir = __DIR__;
define('baseDir', $baseDir);            //定义站点目录
$coreDir = "$baseDir/core";

if (!defined("webhook")) define("webhook", false);

require_once "$baseDir/config/adapter.php";
require_once "$baseDir/config/debug.php";

require_once "$coreDir/pfa.php";
require_once "$coreDir/errorHandle.php";
require_once "$coreDir/string.php";
require_once "$coreDir/curl.php";
require_once "$coreDir/easyMirai.php";
require_once "$coreDir/pluginsHelp.php";
require_once "$coreDir/OneBotBridge.php";

if (file_exists(baseDir . '/vendor/autoload.php'))
    require_once baseDir . '/vendor/autoload.php';

$dataDir = getDataDir();
define("dataDir", $dataDir);
const MIRAIEZ_VERSION = '2.4.1';

require_once "$coreDir/adapter.php";
require_once "$coreDir/core.php";

if (MIRAIEZ_PFA) $pfa_loadedTime = microtime(true);

$TypedArt = "\n███╗   ███╗██╗██████╗  █████╗ ██╗███████╗███████╗\n████╗ ████║██║██╔══██╗██╔══██╗██║██╔════╝╚══███╔╝\n██╔████╔██║██║██████╔╝███████║██║█████╗    ███╔╝ \n██║╚██╔╝██║██║██╔══██╗██╔══██║██║██╔══╝   ███╔╝  \n██║ ╚═╝ ██║██║██║  ██║██║  ██║██║███████╗███████╗\n╚═╝     ╚═╝╚═╝╚═╝  ╚═╝╚═╝  ╚═╝╚═╝╚══════╝╚══════╝\n\nMiraiEz " . MIRAIEZ_VERSION . " - Copyright (c) 2021-2024 NKXingXh\n\n";
if (defined('MIRAIEZ_RUNNING_MODE') &&  MIRAIEZ_RUNNING_MODE == 2) {
    echo $TypedArt;
}
