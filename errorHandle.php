<?php
define("WEBHOOK_ERROR_REPORT_LEAVE", 0);    //webhook 模式下的错误报告级别
define("IGNORE_UNREPORTED_ERRORS", true);   //是否忽略未报告的错误
define("MEMORY_RESERVE_SIZE", 1048576);     //内存预留大小

if (webhook) {
    //隐藏所有错误
    error_reporting(WEBHOOK_ERROR_REPORT_LEAVE);
}

if (MEMORY_RESERVE_SIZE > 0) {
    $_memoryReserve = str_repeat('x', MEMORY_RESERVE_SIZE); //预留内存
}

//接管未捕获的异常
set_exception_handler(function ($e) {
    //判断是否需要忽略
    if (IGNORE_UNREPORTED_ERRORS && ($e->getCode() & error_reporting())) {
        return;
    }

    $msg = "在 " . $e->getFile() . " 中的第 " . $e->getLine() . " 行处发生异常 (" . $e->getCode() . ") : " . $e->getMessage();
    writeLog($msg, 'Exception', 'errorHandle');

    //尝试回复消息给调试人员
    if (webhook) {
        global $debug_friends, $debug_groups, $_DATA;
        if (
            ($_DATA['type'] == 'FriendMessage' && in_array($_DATA['sender']['id'], $debug_friends)) ||
            ($_DATA['type'] == 'GroupMessage' && in_array($_DATA['sender']['group']['id'], $debug_groups))
        ) {
            replyMessage($msg);
        }
    }
});

//接管各种错误
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    //判断是否需要忽略
    if (IGNORE_UNREPORTED_ERRORS && ($errno & error_reporting())) {
        return;
    }
    $msg = "在 " . $errfile . " 中的第 " . $errline . " 行处发生错误 (" . $errno . ") : " . $errstr;
    writeLog($msg, 'Error', 'errorHandle');

    //尝试回复消息给调试人员
    if (webhook) {
        global $debug_friends, $debug_groups, $_DATA;
        if (
            ($_DATA['type'] == 'FriendMessage' && in_array($_DATA['sender']['id'], $debug_friends)) ||
            ($_DATA['type'] == 'GroupMessage' && in_array($_DATA['sender']['group']['id'], $debug_groups))
        ) {
            replyMessage($msg);
        }
    }
});

//接管致命错误
register_shutdown_function(function () {
    //释放预留的内存
    unset($_memoryReserve);

    $error = error_get_last();
    if(!isFatalError($error)) {
        $_memoryReserve = str_repeat('x', MEMORY_RESERVE_SIZE); //预留内存
        return;
    }

    //判断是否需要忽略
    if (IGNORE_UNREPORTED_ERRORS && ($error['type'] & error_reporting())) {
        return;
    }

    $msg = "在 " . $error['file'] . " 中的第 " . $error['line'] . " 行处发生致命错误 (" . $error['type'] . ") : " . $error['message'];
    writeLog($msg, 'Fatal', 'errorHandle');

    //尝试回复消息给调试人员
    if (webhook) {
        global $debug_friends, $debug_groups, $_DATA;
        if (
            ($_DATA['type'] == 'FriendMessage' && in_array($_DATA['sender']['id'], $debug_friends)) ||
            ($_DATA['type'] == 'GroupMessage' && in_array($_DATA['sender']['group']['id'], $debug_groups))
        ) {
            replyMessage($msg);
        }
    }

    //致命错误 终止运行
    die(1);
});

function isFatalError($error)
{
    $fatalErrors = array(
        E_ERROR,
        E_PARSE,
        E_CORE_ERROR,
        E_CORE_WARNING,
        E_COMPILE_ERROR,
        E_COMPILE_WARNING
    );
    return isset($error['type']) && in_array($error['type'], $fatalErrors);
}
