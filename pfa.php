<?php
//是否启用性能分析
define("pfa", true);

if (pfa) {
    //开始统计性能
    $pfa_startTime = microtime(true);
    if (webhook) { //初始化 已注册函数
        $pfa_registeredFunc = 0;
        //初始化 已挂钩函数
        $pfa_hookedFunc = 0;
    }
}

//结束性能分析
function pfa_end()
{
    global $pfa_startTime, $pfa_loadedTime, $pfa_webhookInitTime, $pfa_webhookProcessTime, $pfa_endTime;
    $pfa_endTime = microtime(true);
    if (webhook) {
        //加载核心库花费时间
        $pfa_spend_load = round($pfa_loadedTime - $pfa_startTime, 4);
        //webhook 初始化花费时间
        $pfa_spend_webhook_init = round($pfa_webhookInitTime - $pfa_loadedTime, 4);
        //webhook 注册花费时间
        $pfa_spend_webhook_register  = round($pfa_webhookProcessTime - $pfa_webhookInitTime, 4);
        //webhook 处理函数花费时间
        $pfa_spend_webhook_process = round($pfa_endTime - $pfa_webhookProcessTime, 4);
        //总花费时间
        $pfa_spend_total = round($pfa_endTime - $pfa_startTime, 4);

        //声明全局变量: 已注册与已挂钩函数数量
        global $pfa_registeredFunc, $pfa_hookedFunc, $_DATA;
        $msg = "R: $pfa_registeredFunc func, H: $pfa_hookedFunc func, L: $pfa_spend_load, I: $pfa_spend_webhook_init, W: $pfa_spend_webhook_register, P: $pfa_spend_webhook_process, T: $pfa_spend_total";

        if (defined('OneBot')) {
            $msg .=  " (OneBotBridge Active)";
        }

        //输出性能分析结果
        writeLog($msg, $_DATA['type'], 'pfa');
    }
}
