<?php
//定义插件父类
class pluginParent
{
    const _pluginName = "miraiez";
    const _pluginPackage = "top.nkxingxh.miraiez";
    const _pluginVersion = version;

    //构造函数
    public function __construct()
    {
    }

    //初始化插件
    public function _init()
    {
        return true;
    }
}

/**
 * 加载插件
 * @param string $dir 插件目录
 */
function loadPlugins($dir = 'plugins')
{
    global $baseDir;
    $pluginsDir = "$baseDir/$dir";
    define("pluginsDir", $pluginsDir);

    global $_plugins;
    $_plugins = array();                        //插件列表 (插件名 => 插件对象)
    $_plugins_files = scandir($pluginsDir);     //获取插件目录下的所有文件

    //计数器
    global $_plugins_count_register, $_plugins_count_load;
    $_plugins_count_register = 0;                //注册插件计数器
    $_plugins_count_load = 0;                    //加载插件计数器

    //global $__pluginFile__, $__pluginClassName__;
    $GLOBALS['__pluginFile__'] = "plugins.php";
    pluginRegister(new pluginParent);           //注册一个空插件，用于挂钩全局函数
    unset($_plugins['pluginParent']['object']); //删除空插件中的 Object

    //遍历所有插件文件
    foreach ($_plugins_files as $__pluginFile__) {
        //判断是否为 .php 文件
        if (!preg_match('/\.php$/', $__pluginFile__)) continue;
        if (is_file("$pluginsDir/$__pluginFile__")) {
            $GLOBALS['__pluginFile__'] = $__pluginFile__;
            $GLOBALS['__pluginClassName__'] = "pluginParent";
            include "$pluginsDir/$__pluginFile__";
        }
    }
    unset($GLOBALS['__pluginFile__'], $GLOBALS['__pluginClassName__'], $_plugins_files);
}

/**
 * 执行插件函数
 */
function execPluginsFunction()
{
    global $_plugins, $_DATA;
    //已执行函数数量
    global $_plugins_count_exec;
    $_plugins_count_exec = 0;
    //开始执行已挂钩的函数
    foreach ($_plugins as $__plugin__) {
        //判断是否有挂钩函数
        if (!empty($__plugin__['hooked']) && is_array($__plugin__['hooked'])) {
            //遍历挂钩函数
            foreach ($__plugin__['hooked'] as $__hooked_func__) {
                $_plugins_count_exec++;
                //执行挂钩函数
                if (isset($__plugin__['object']) && is_object($__plugin__['object'])) {
                    $return_code = $__plugin__['object']->$__hooked_func__($_DATA);
                } else
                    $return_code = $__hooked_func__($_DATA);

                //拦截
                if (isset($return_code) && $return_code === 1)
                    break;
            }
            //释放 Object
            unset($__plugin__['object']);
        }
    }
    //返回计数器
    return $_plugins_count_exec;
}


/**
 * 注册插件
 * @param Class $pluginClass 插件类
 */
function pluginRegister($pluginClass)
{
    global $_plugins;
    //获取插件类名
    $__pluginClassName__ = get_class($pluginClass);
    if (array_key_exists($__pluginClassName__, $_plugins)) {
        //插件已存在
        return false;
    }
    //计数器
    global $_plugins_count_register, $_plugins_count_load;
    $_plugins_count_register++;
    //创建插件对象
    $_plugins[$__pluginClassName__] = array(
        'name' => $pluginClass::_pluginName,
        'package' => $pluginClass::_pluginPackage,
        'version' => $pluginClass::_pluginVersion,
        'file' => $GLOBALS['__pluginFile__'],
        'object' => null,
        'hooked' => null
    );
    if (pluginIsEnable($__pluginClassName__, $pluginClass::_pluginPackage)) {
        $_plugins[$__pluginClassName__]['hooked'] = array();
        $GLOBALS['__pluginClassName__'] = $__pluginClassName__;
        //初始化插件
        if ($pluginClass->_init() === false) {
            $_plugins[$__pluginClassName__]['hooked'] = false;  //插件初始化失败
        } else {
            //计数器
            $_plugins_count_load++;
            $_plugins[$__pluginClassName__]['object'] = $pluginClass;   //插件初始化成功
            return true;
        }
    } else {
        //插件未启用
        $_plugins[$__pluginClassName__]['object'] = false;
        $_plugins[$__pluginClassName__]['hooked'] = null;
    }
    return false;
}

/**
 * 挂钩函数
 */
function hookRegister($func, ...$types)
{
    global $_plugins, $_DATA;

    if (pfa) {
        global $pfa_registeredFunc, $pfa_hookedFunc;
        $pfa_registeredFunc++;  //已注册函数数量 +1
    }
    foreach ($types as $type) {
        if ($type == $_DATA['type']) {      //仅当注册类型与 webhook 上报的类型一样时，才添加
            $_plugins[$GLOBALS['__pluginClassName__']]['hooked'][] = $func;  //挂钩类函数
            if (pfa) $pfa_hookedFunc++;  //挂钩函数数量加 1
            return true;                               //挂钩成功
            break;
        }
    }
    //挂钩失败
    return false;
}

/**
 * 检查插件是否启用
 * @param string $pluginClassName 插件类名
 * @param string $pluginPackage 插件包名
 */
function pluginIsEnable($pluginClassName = "", $pluginPackage = "")
{
    return true;
}
