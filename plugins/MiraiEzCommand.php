<?php

/**
 * MiraiEz Copyright (c) 2021-2023 NKXingXh
 * License AGPLv3.0: GNU AGPL Version 3 <https://www.gnu.org/licenses/agpl-3.0.html>
 * This is free software: you are free to change and redistribute it.
 * There is NO WARRANTY, to the extent permitted by law.
 *
 * Github: https://github.com/nkxingxh/MiraiEz
 */

class MiraiEzCommand extends pluginParent
{
    const _pluginName = "MiraiEzCommand";
    const _pluginAuthor = "NKXingXh";
    const _pluginDescription = "MiraiEz 命令支持前置插件";
    const _pluginPackage = "top.nkxingxh.MiraiEzCommand";
    const _pluginVersion = "1.0.0";
    const _pluginFrontLib = true;

    private static int $_maxCmdLen = 1024;
    private static array $_cmdStartWith = array('/');
    private static int $_cmdArgc = 0;
    private static array $_cmdArgs = array();
    private static array $_regPlugins = array();

    public function __construct()
    {
        parent::__construct();
    }

    public function _init()
    {
        // echo "Hello: " . plugin_whoami(true) . "\n";
        hookRegister(function ($_DATA) {
            global $_PlainText;
            if (
                mb_strlen($_PlainText) > self::$_maxCmdLen ||
                !in_array(substr($_PlainText, 0, 1), self::$_cmdStartWith)
            ) return;

            //解析命令参数
            self::$_cmdArgs = self::parseMessageChain($_DATA['messageChain']);
            self::$_cmdArgc = count(self::$_cmdArgs);

            //执行 cmdRegister 注册的函数
            global $__pluginPackage__;
            foreach (self::$_regPlugins as $__pluginPackage__ => $_plugin_) {
                foreach ($_plugin_ as $_plugin_reg_) {
                    foreach ($_plugin_reg_['cmds'] as $cmd) {
                        //判断注册的命令与当前是否匹配
                        $cmdc = count($cmd);
                        if ($cmdc > self::$_cmdArgc) {   //现行命令深度比注册的命令低
                            continue;   //跳过
                        }
                        for ($i = 0; $i < $cmdc; $i++) {
                            if (
                                !is_string($cmd[$i]) ||
                                !is_string(self::$_cmdArgs[$i]) ||
                                strcasecmp($cmd[$i], self::$_cmdArgs[$i]) != 0
                            ) {    //判断命令是否匹配
                                continue 2; //不匹配，跳出
                            }
                        }
                        //执行注册的函数
                        $return_code = $_plugin_reg_['func']($_DATA, self::$_cmdArgc, self::$_cmdArgs);
                        if ($return_code === 1) {
                            break 3;    //拦截
                        }
                        continue 2;     //下一个注册项
                    }
                }
            }
            $__pluginPackage__ = self::_pluginPackage;  //恢复包名
        }, 'FriendMessage', 'GroupMessage');
        return true;
    }

    /**
     * 命令注册
     */
    public static function cmdRegister(Closure $func, ...$commands): bool
    {
        // echo "Hello: " . plugin_whoami(true) . "\n";
        $package = plugin_whoami();
        if (empty($package)) return false;
        if (!isset(self::$_regPlugins[$package]) || !is_array(self::$_regPlugins[$package])) {
            self::$_regPlugins[$package] = array();
        }

        foreach ($commands as &$cmd) {
            $cmd = is_array($cmd) ? $cmd : self::parseCommand(trim($cmd));
        }

        self::$_regPlugins[$package][] = array(
            'func' => $func,
            'cmds' => $commands
        );
        return true;
    }

    /**
     * 解析消息链命令 (支持 At/图片/语音)
     */
    public static function parseMessageChain(array $messageChain): array
    {
        /**
         * 消息链处理机制:
         * 1. 将每一段文本单独解析
         * 2. 保留文本类型之外的消息链成员
         * 3. 去除 Source
         * 4. 将 Quote 移动到最后 (如果有)
         */
        $args = array();
        $n = count($messageChain);
        for ($i = 0; $i < $n; $i++) {
            switch ($messageChain[$i]['type']) {
                case 'Plain':
                    $args = array_merge($args, self::parseCommand(trim($messageChain[$i]['text'])));
                    break;
                case 'Source':
                    unset($messageChain[$i]);
                    break;
                case 'Quote':
                    $messageChain[] = $messageChain[$i];
                    unset($messageChain[$i]);
                    break;
                default:
                    //保留
                    $args[] = $messageChain[$i];
            }
        }
        $messageChain = array_values($messageChain);
        $n = count($messageChain) - 1;  //指向可能存在的 Quote 成员
        if ($messageChain[$n]['type'] == 'Quote') {
            $args[] = $messageChain[$n];
        }
        return $args;
    }

    /**
     * 解析字符串命令
     */
    public static function parseCommand(string $cmd): array
    {
        $args = [];
        $now = '';          //当前读取的参数内容
        $isBegin = false;   //是否已经在读取一个参数
        $inMark = false;    //是否在双引号内
        if (class_exists('IntlChar')) {
            $is_space = '\IntlChar::isspace';
        } else {
            $is_space = '\MiraiEzCommand::isspace';
        }

        for ($i = 0; $i < strlen($cmd); $i++) {
            if ($isBegin) {
                if ($is_space($cmd[$i])) {
                    if ($inMark) {
                        $now = $now . $cmd[$i];
                    } else {
                        $isBegin = false;
                        $args[] = $now;
                        $now = '';
                    }
                } else if ($cmd[$i] == '\\') {
                    $i++;
                    $now = $now . $cmd[$i];
                } else {
                    if ($inMark && $cmd[$i] == '"') {
                        $inMark = false;
                        $isBegin = false;
                        $args[] = $now;
                        $now = '';
                    } else {
                        $now = $now . $cmd[$i];
                    }
                }
            } else {
                if (!$is_space($cmd[$i])) {
                    if ($cmd[$i] == '"') {
                        $inMark = true;
                        $isBegin = true;
                    } else if ($cmd[$i] == '\\') {
                        $i++;
                        $isBegin = true;
                        $now = $now  . $cmd[$i];
                    } else {
                        $isBegin = true;
                        $now = $now . $cmd[$i];
                    }
                }
            }
        }
        if (!empty($now)) {
            $args[] = $now;
        }
        return $args;
    }

    public static function isspace($char): bool
    {
        preg_match("/\s/", $char);
        return (bool)$char;
    }
}

pluginRegister(new MiraiEzCommand);

function cmdRegister(Closure $func, ...$commands): bool
{
    return MiraiEzCommand::cmdRegister($func, ...$commands);
}
