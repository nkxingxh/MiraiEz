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
    const _pluginVersion = "1.2.0";
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

    public function _init(): bool
    {
        // echo "Hello: " . plugin_whoami(true) . "\n";
        hookRegister(function ($_DATA) {
            global $_PlainText;
            if (
                mb_strlen($_PlainText) > self::$_maxCmdLen ||
                !in_array(substr($_PlainText, 0, 1), self::$_cmdStartWith)
            ) return 0;

            //解析命令参数
            self::$_cmdArgs = self::parseMessageChain($_DATA['messageChain']);
            self::$_cmdArgc = count(self::$_cmdArgs);

            //执行 cmdRegister 注册的函数
            global $__pluginPackage__;
            foreach (self::$_regPlugins as $__pluginPackage__ => $_plugin_) {
                foreach ($_plugin_['reg'] as $_plugin_reg_) {
                    foreach ($_plugin_reg_['cmds'] as $cmd) {
                        //判断注册的命令与当前是否匹配
                        $cmdc = count($cmd);
                        if ($cmdc > self::$_cmdArgc) {   //现行命令深度比注册的命令低
                            continue;   //跳过
                        }
                        for ($i = 0; $i < $cmdc; $i++) {
                            if (!self::argcmp($cmd[$i], self::$_cmdArgs[$i])) {    //判断命令是否匹配
                                continue 2; //不匹配，跳出
                            }
                        }
                        //执行注册的函数
                        if (is_string($_plugin_reg_['func'])) {
                            writeLog(
                                "package: $__pluginPackage__, "
                                    . json_encode($GLOBALS['_plugins'][$__pluginPackage__], JSON_UNESCAPED_UNICODE)
                                    . ", is_object: " . (is_object($GLOBALS['_plugins'][$__pluginPackage__]['object']) ? 'True' : 'False')
                                    . ", " . json_encode($_plugin_reg_, JSON_UNESCAPED_UNICODE),
                                'exec',
                                'MiraiEzCommand',
                                1
                            );
                            $func = $_plugin_reg_['func'];
                            $return_code = $_plugin_['object']->$func($_DATA, self::$_cmdArgc, self::$_cmdArgs);
                            unset($func);
                        } else {
                            $return_code = $_plugin_reg_['func']($_DATA, self::$_cmdArgc, self::$_cmdArgs);
                        }
                        if ($return_code === 1 || $return_code === 2) {
                            break 3;    //拦截
                        } else {
                            continue 2;     //下一个注册项
                        }
                    }
                }
                //释放已使用的资源
                unset(self::$_regPlugins[$__pluginPackage__]);
            }
            $__pluginPackage__ = self::_pluginPackage;  //恢复包名
            if (($return_code ?? 0) === 1) return 1;   //拦截 hook
            else return 0;
        }, 'FriendMessage', 'GroupMessage');
        return true;
    }

    /**
     * 命令注册
     */
    public static function cmdRegister($func, ...$commands): bool
    {
        // echo "Hello: " . plugin_whoami(true) . "\n";
        $package = plugin_whoami();
        if (empty($package)) return false;
        if (!isset(self::$_regPlugins[$package]) || !is_array(self::$_regPlugins[$package])) {
            self::$_regPlugins[$package] = array(
                'object' => $GLOBALS['_plugins'][$package]['object'],   //引用 Object 避免被释放
                'reg' => array()
            );
        }

        /**
         * ~~此时位于各插件的 init 阶段，“当前”插件的对象 (Object) 一定是不在 $GLOBALS['_plugins'][$package] 中的~~
         * 已在 MiraiEz 2.4.1 中将插件对象赋值提前至 _init 前
         */

        //判断注册的函数是否存在
        if (!(is_string($func)
            ? method_exists($GLOBALS['_plugins'][$package]['object'], $func)
            : is_callable($func))) {
            writeLog("$func 不存在", 'reg', 'MiraiEzCommand', 1);
            return false;
        }

        foreach ($commands as &$cmd) {
            $cmd = is_array($cmd) ? $cmd : self::parseCommand(trim($cmd));
            foreach ($cmd as &$v) {
                $len = strlen($v);
                //判断是否注册的消息链成员类型
                if (
                    is_string($v) &&
                    substr($v, 0, 1) == '<' &&
                    substr($v, $len - 1) == '>'
                ) {
                    $v = array(
                        'type' => substr($v, 1, $len - 2)
                    );
                }
            }
            unset($len);
        }

        self::$_regPlugins[$package]['reg'][] = array(
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
     * @param string $cmd 被解析的字符串
     * @param int $limit 需要解析多少个, 0 代表全部解析 
     */
    public static function parseCommand(string $cmd, int $limit = 0): array
    {
        $args = [];
        $buffer = '';     //当前读取的参数内容 (缓冲区)
        $begin = false;   //是否处于参数中
        $mark = false;    //是否处于引号包含中
        if (class_exists('IntlChar')) {
            $is_space = '\IntlChar::isspace';
        } else {
            $is_space = '\MiraiEzCommand::isspace';
        }

        for ($i = 0; $i < strlen($cmd); $i++) {
            if ($begin) {   //当前正处于参数中
                if ($is_space($cmd[$i])) {  //当前字符是否为不可见 (类似空格)
                    if ($mark) {    //当前正处于引号包含中
                        $buffer .= $cmd[$i];   //拼接
                    } else {    //不处于引号包含中，遇到不可见字符，将作为参数分隔符
                        $begin = false;     //标记不处于参数中
                        $args[] = $buffer;     //添加参数
                        $buffer = '';          //重置缓冲区
                        if (!--$limit) break;   //计数器
                    }
                } elseif ($cmd[$i] == '\\') {   //当前字符是否为转义符 '\'
                    $buffer .= $cmd[++$i];     //位置指向下一个字符并拼接
                } elseif ($mark && $cmd[$i] == '"') {   //当前处于引号包含中且遇到另一个引号，将作为参数分隔符
                    $mark = $begin = false;     //标记不处于参数与引号中
                    $args[] = $buffer;          //添加参数
                    $buffer = '';           //重置缓冲区
                    if (!--$limit) break;   //计数器
                } else {    //其他字符
                    $buffer = $buffer . $cmd[$i];   //直接拼接
                }
            } elseif (!$is_space($cmd[$i])) {   //当前不处于参数中，且当前字符不是空格
                switch ($cmd[$i]) { //判断当前字符类型
                    case '"':   //引号
                        $begin = $mark = true;  //标记当前处于参数中，且处于引号包含中
                        break;  //结束判断
                    case '\\':  //转义符
                        // $begin = true;
                        // $buffer .= $cmd[++$i];
                        // break;   //有重复操作, 直接执行下一个语句块
                        $i++;   //位置移到下一个字符
                    default:    //其他字符
                        $begin = true;  //标记当前处于参数中
                        $buffer .= $cmd[$i];    //拼接字符
                }
            }
        }
        if (!empty($buffer)) {  //拼接最后一个参数
            $args[] = $buffer;
        }
        return $args;
    }

    /**
     * 判断是否为空白字符
     */
    public static function isspace(string $char): bool
    {
        return (bool) preg_match("/\s/", $char);
    }

    private static function argcmp($arg1, $arg2, bool $strict_case = false): bool
    {
        if (gettype($arg1) !== gettype($arg2)) return false;
        if (is_array($arg1)) {
            if (empty($arg1['type']) || empty($arg2['type'])) return false;
            if ($arg1['type'] == '*' || $arg2['type'] == '*') return true;
            $arg1 = $arg1['type'];
            $arg2 = $arg2['type'];
        }
        return ($strict_case ? strcmp($arg1, $arg2) : strcasecmp($arg1, $arg2)) == 0;
    }
}

pluginRegister(new MiraiEzCommand);

/**
 * 注册命令
 * @param mixed $func 要注册的方法名或闭包函数 (Closure)
 */
function cmdRegister($func, ...$commands): bool
{
    return MiraiEzCommand::cmdRegister($func, ...$commands);
}
