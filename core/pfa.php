<?php
/**
 * MiraiEz Copyright (c) 2021-2023 NKXingXh
 * License AGPLv3.0: GNU AGPL Version 3 <https://www.gnu.org/licenses/agpl-3.0.html>
 * This is free software: you are free to change and redistribute it.
 * There is NO WARRANTY, to the extent permitted by law.
 * 
 * Github: https://github.com/nkxingxh/MiraiEz
 */

if (MIRAIEZ_PFA) {
    //开始统计性能
    $pfa_startTime = microtime(true);
    if (webhook) {
        //初始化 已注册函数
        $pfa_func_registered = 0;
        //初始化 已挂钩函数
        $pfa_func_hooked = 0;
    }
}

//结束性能分析
function pfa_end()
{
    global $pfa_startTime, $pfa_loadedTime, $pfa_pluginInitTime, $pfa_pluginFuncTime, $pfa_endTime;
    $pfa_endTime = microtime(true);
    if (webhook) {
        //加载核心库花费时间
        $pfa_spend_load = round($pfa_loadedTime - $pfa_startTime, 4);
        //webhook 初始化花费时间
        $pfa_spend_webhook_init = round($pfa_pluginInitTime - $pfa_loadedTime, 4);
        //webhook 注册花费时间
        $pfa_spend_plugins_load  = round($pfa_pluginFuncTime - $pfa_pluginInitTime, 4);
        //webhook 处理函数花费时间
        $pfa_spend_plugins_exec = round($pfa_endTime - $pfa_pluginFuncTime, 4);
        //总花费时间
        $pfa_spend_total = round($pfa_endTime - $pfa_startTime, 4);

        //声明全局变量: 已注册与已挂钩函数数量
        global $pfa_func_registered, $pfa_func_hooked, $_DATA, $_plugins_count_register, $_plugins_count_load;
        $msg = "PR: $_plugins_count_register, PL: $_plugins_count_load, FR: $pfa_func_registered, FH: $pfa_func_hooked, CL: $pfa_spend_load, WI: $pfa_spend_webhook_init, PL: $pfa_spend_plugins_load, PE: $pfa_spend_plugins_exec, T: $pfa_spend_total";

        if (defined('OneBot')) {
            $msg .=  " (OneBotBridge Active)";
        }

        //输出性能分析结果
        writeLog($msg, $_DATA['type'], 'MIRAIEZ_PFA', 2);
    }
}
