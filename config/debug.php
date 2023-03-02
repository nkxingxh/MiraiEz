<?php

/**
 * MiraiEz Copyright (c) 2021-2023 NKXingXh
 * License AGPLv3.0: GNU AGPL Version 3 <https://www.gnu.org/licenses/agpl-3.0.html>
 * This is free software: you are free to change and redistribute it.
 * There is NO WARRANTY, to the extent permitted by law.
 * 
 * Github: https://github.com/nkxingxh/MiraiEz
 */

const MIRAIEZ_ADMIN_QQ = 1234567890;                     //填写用于管理机器人，接收机器人通知的 qq

$MIRAIEZ_DEBUG_GROUPS = [123456789];                        //启用调试的群组
$MIRAIEZ_DEBUG_FRIENDS = [1234567890];                      //启用调试的好友

const MIRAIEZ_ADAPTER_ALWAYS_USE_HTTP = false;           //只使用 HTTP 适配器
const MIRAIEZ_PLUGINS_DATA_ISOLATION = false;            //是否为不同的插件隔离数据 (即每个插件一个专用目录)
const MIRAIEZ_LOGGING_LEVEL = 1;                         //日志记录级别
const MIRAIEZ_PLUGINS_WHOAMI_BACKTRACE = false;          //plugin_whoami 默认方法

const MIRAIEZ_PFA = false;                               //是否启用性能分析