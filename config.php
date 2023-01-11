<?php
/**
 * MiraiEz Copyright (c) 2021-2023 NKXingXh
 * License AGPLv3.0: GNU AGPL Version 3 <https://www.gnu.org/licenses/agpl-3.0.html>
 * This is free software: you are free to change and redistribute it.
 * There is NO WARRANTY, to the extent permitted by law.
 * 
 * Github: https://github.com/nkxingxh/MiraiEz
 */

define("httpApi", "http://localhost:90");           //http api
define("verifyKey", "yourKey");                     //http api verifyKey
define("Authorization", "");                        //webhook Authorization

define('admin_qq', 1234567890);                     //填写用于管理机器人，接收机器人通知的 qq

$debug_groups = [123456789];                        //启用调试的群组
$debug_friends = [1234567890];                      //启用调试的好友

define("adapter_always_use_http", false);           //只使用 HTTP 适配器
define("plugins_data_isolation", false);            //是否为不同的插件隔离数据 (即每个插件一个专用目录)
define("logging_level", 2);                         //日志记录级别
