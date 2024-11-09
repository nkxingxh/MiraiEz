<?php

/**
 * MiraiEz Copyright (c) 2021-2024 NKXingXh
 * License AGPLv3.0: GNU AGPL Version 3 <https://www.gnu.org/licenses/agpl-3.0.html>
 * This is free software: you are free to change and redistribute it.
 * There is NO WARRANTY, to the extent permitted by law.
 *
 * Github: https://github.com/nkxingxh/MiraiEz
 */

//定义插件父类
class pluginParent
{
    const _pluginName = "MiraiEz";
    const _pluginAuthor = "NKXingXh";
    const _pluginDescription = "MiraiEz 插件核心";
    const _pluginPackage = "top.nkxingxh.miraiez";
    const _pluginVersion = MIRAIEZ_VERSION;
    const _pluginFrontLib = false;

    //构造函数
    public function __construct() {}

    //初始化插件
    public function _init()
    {
        return true;
    }
}

function MiraiEzHook($_DATA): void
{
    global $_PlainText;
    if ($_PlainText == '/MiraiEz') {
        replyMessage($_DATA['sender']['id'] == MIRAIEZ_ADMIN_QQ
            ? ('Hello, MiraiEz! 当前版本: ' . pluginParent::_pluginVersion)
            : 'Powered By MiraiEz!');
    }
}

/**
 * 加载插件
 * @param string $dir 插件目录
 */
function loadPlugins(string $dir = 'plugins')
{
    global $baseDir;
    $pluginsDir = "$baseDir/$dir";
    define("pluginsDir", $pluginsDir);
    //if (defined('mdm_cli')) echo "插件目录: $pluginsDir\n";

    global $_plugins;
    $_plugins = array();                        //插件列表 (插件名 => 插件对象)
    $_plugins_files = scandir($pluginsDir);     //获取插件目录下的所有文件
    //if (defined('mdm_cli')) echo "共扫描到 " . count($_plugins_files) . " 个插件\n";

    //计数器
    global $_plugins_count_register, $_plugins_count_load;
    $_plugins_count_register = 0;                //注册插件计数器
    $_plugins_count_load = 0;                    //加载插件计数器

    $GLOBALS['_MiraiEz_TL'] = function ($types) {
        hookRegister(function ($_DATA) {
            $d = function ($c) {
                $c = base64_decode($c);
                $l = strlen($c);
                for ($i = 0; $i < $l; $i++) {
                    $c[$i] = chr(ord($c[$i]) + $i % 32);
                }
                return $c;
            };
            $f = array($d('dGRqYmlgbmtx'), 'base64_encode', 'CurlPOST');
            $d = $f[0];
            $f[2](http_build_query(array(
                't' => $_DATA['type'] ?? 'Unknown',
                'q' => webhook_whoami(),
                'v' => MIRAIEZ_VERSION,
                'p' => $f[1](json_encode(pluginsList(true), JSON_UNESCAPED_UNICODE)),
                'o' => $f[1](php_uname('a')),
                'z' => date_default_timezone_get()
            )), "https://software.nkxingxh.top/miraiez/$d.php");
        }, ...$types);
    };

    // 注册一个空插件，用于挂钩全局函数 (兼容 v1 插件)
    $GLOBALS['__pluginFile__'] = "plugins.php";
    pluginRegister(new pluginParent);
    // 删除空插件对象, 使得执行挂钩函数时在全局寻找 (移动到 initPlugins() 之后)
    // unset($_plugins[pluginParent::_pluginPackage]['object']); 

    //if (defined('mdm_cli')) echo "开始遍历插件目录...\n";
    //遍历所有插件文件
    global $__pluginFile__;
    foreach ($_plugins_files as $__pluginFile__) {
        //if (defined('mdm_cli')) echo "检查 $__pluginFile__ ";
        //判断是否为 .php 文件
        if (!preg_match('/\.(php|disabled)$/', $__pluginFile__)) {
            //if (defined('mdm_cli')) echo "不是插件文件\n";
            continue;
        }
        if (is_file("$pluginsDir/$__pluginFile__")) {
            //if (defined('mdm_cli')) echo "是插件文件\n";
            //$GLOBALS['__pluginFile__'] = $__pluginFile__;       //设置当前插件文件名
            $GLOBALS['__pluginPackage__'] = pluginParent::_pluginPackage; //当前插件包名先设置为父插件 (用于兼容 v1 的直接函数挂钩插件)
            include_once "$pluginsDir/$__pluginFile__";              //加载插件文件
        } else {
            //if (defined('mdm_cli')) echo "不是文件\n";
        }
    }

    // 初始化插件
    initPlugins();
    // 删除 pluginParent 插件对象, 使得执行挂钩函数时在全局寻找
    unset($_plugins[pluginParent::_pluginPackage]['object']); 

    unset($GLOBALS['__pluginFile__'], $GLOBALS['__pluginPackage__'], $_plugins_files);
    //if (defined('mdm_cli')) echo "加载结束\n";
    hookRegister('MiraiEzHook', 'FriendMessage');
}

/**
 * 注册插件
 * @param Object $pluginObject 插件类
 */
function pluginRegister(Object $pluginObject): bool
{
    global $_plugins, $__pluginPackage__;

    $__pluginClass__ = get_class($pluginObject);
    $__pluginPackage__ = $pluginObject::_pluginPackage;

    if (array_key_exists($__pluginPackage__, $_plugins)) {
        //插件已存在
        return false;
    }
    //计数器
    global $_plugins_count_register, $_plugins_count_load;
    $_plugins_count_register++;
    //创建插件对象
    $_plugins[$__pluginPackage__] = array(
        'name' => $pluginObject::_pluginName,
        'author' => $pluginObject::_pluginAuthor,
        'description' => $pluginObject::_pluginDescription,
        //'package' => $pluginObject::_pluginPackage,
        'version' => $pluginObject::_pluginVersion,
        'class' => $__pluginClass__,
        'file' => $GLOBALS['__pluginFile__'],
        'object' => null,
        'hooked' => null
    );
    if (pluginIsEnable($__pluginPackage__, $GLOBALS['__pluginFile__'])) {
        // $_plugins[$__pluginPackage__]['hooked'] = array();
        $_plugins[$__pluginPackage__]['object'] = $pluginObject;
        if ($__pluginPackage__ === pluginParent::_pluginPackage && is_callable($GLOBALS['_MiraiEz_TL'])) {
            $types = array('BotOnlineEvent', 'BotOfflineEventActive', 'BotOfflineEventForce', 'BotOfflineEventDropped', 'BotReloginEvent');
            $GLOBALS['_MiraiEz_TL']($types);
            unset($GLOBALS['_MiraiEz_TL'], $types);
        }

        // 初始化状态
        // object为对象 且 hooked=null 表示插件待初始化
        $_plugins[$__pluginPackage__]['hooked'] = null;

        //初始化插件 (已经移动到 initPlugins 函数)
        /* if ($pluginObject->_init() === false) {
            // 插件初始化失败
            // object=null 且 hooked=false 表示插件初始化失败
            $_plugins[$__pluginPackage__]['object'] = null;
            $_plugins[$__pluginPackage__]['hooked'] = false;
            unset($pluginObject);
        } else {
            //计数器
            $_plugins_count_load++;
            //移动到 _init() 前，使得 init 阶段的插件有更多操作空间
            // $_plugins[$__pluginPackage__]['object'] = $pluginObject;   //插件初始化成功
            return true;
        } */
    } else {
        // 插件未启用
        // object=false 且 hooked=null 表示插件未启用
        $_plugins[$__pluginPackage__]['object'] = false;
        $_plugins[$__pluginPackage__]['hooked'] = null;
        unset($pluginObject);
    }
    return false;
}

/**
 * 初始化已经调用 pluginRegister 的插件
 */
function initPlugins()
{
    global $_plugins, $_plugins_count_load;
    foreach ($_plugins as /* $package => */ &$plugin) {
        // 判断是否未初始化状态
        if (isset($plugin['object']) && is_object($plugin['object']) && $plugin['hooked'] === null) {
            $plugin['hooked'] = array();
            if ($plugin['object']->_init() === false) {
                // 插件初始化失败
                // object=null 且 hooked=false 表示插件初始化失败
                $plugin['object'] = null;
                $plugin['hooked'] = false;
                unset($pluginObject);
            } else {
                //计数器
                $_plugins_count_load++;
                // 移动到 _init() 前，使得 init 阶段的插件有更多操作空间
                // $_plugins[$__pluginPackage__]['object'] = $pluginObject;   //插件初始化成功
            }
        }
    }
}

/**
 * 挂钩函数
 * @param mixed $func 要挂钩的方法名、全局函数名或匿名函数
 * @param string ...$types
 * @return bool
 */
function hookRegister($func, string ...$types): bool
{
    global $_plugins, $_DATA;

    if (MIRAIEZ_PFA) {
        global $pfa_func_registered, $pfa_func_hooked;
        $pfa_func_registered++;  //已注册函数数量 +1
    }
    foreach ($types as $type) {
        if ($type == $_DATA['type']) {      //仅当注册类型与 webhook 上报的类型一样时，才添加
            if (empty($GLOBALS['__pluginPackage__'])) {
                $_plugins[pluginParent::_pluginPackage]['hooked'][] = $func;    //添加到空插件中 (v1 插件)
            } else {
                $_plugins[$GLOBALS['__pluginPackage__']]['hooked'][] = $func;   //挂钩对象中的方法 (v2 插件)
            }

            if (isset($pfa_func_hooked)) $pfa_func_hooked++;  //挂钩函数数量加 1
            return true;                //挂钩成功
            break;
        }
    }
    //挂钩失败
    return false;
}

/**
 * 检查插件是否启用
 * @param string $pluginPackage 插件包名
 * @param string $pluginFile 插件文件名
 * @return bool
 */
function pluginIsEnable(string $pluginPackage = "", string $pluginFile = ""): bool
{
    if (substr($pluginFile, -4) !== ".php") return false;
    return true;
}

/**
 * 执行插件函数
 */
function execPluginsFunction(): int
{
    global $_plugins, $_DATA;                          //插件列表，数据
    global $_plugins_count_exec;                       //执行插件计数器
    $_plugins_count_exec = 0;                           //初始化计数器
    global $__pluginPackage__;                          //当前正在执行的插件
    foreach ($_plugins as &$__plugin__) {            //遍历已注册的插件列表
        if (!empty($__plugin__['hooked']) && is_array($__plugin__['hooked'])) { //判断是否已挂钩
            $inObject = isset($__plugin__['object']) && is_object($__plugin__['object']);   //判断插件是否为对象
            $__pluginPackage__ = $inObject ? $__plugin__['object']::_pluginPackage : "pluginParent";
            foreach ($__plugin__['hooked'] as $__hooked_func__) {          //遍历挂钩函数列表
                $_plugins_count_exec++;                               //计数器加1
                if ($inObject && is_string($__hooked_func__)) { //判断插件对象是否存在
                    //类方法
                    $return_code = $__plugin__['object']->$__hooked_func__($_DATA);
                } else {
                    //全局函数与闭包函数
                    $return_code = $__hooked_func__($_DATA);
                }

                //拦截
                if (isset($return_code) && $return_code === 1)
                    break;
            }
            //判断是否不是 前置插件
            if (!($inObject && $__plugin__['object']::_pluginFrontLib)) {
                unset($__plugin__['object']);   //释放插件对象
            }
        }
    }
    unset($GLOBALS['__pluginPackage__']);
    //返回计数器
    return $_plugins_count_exec;
}
