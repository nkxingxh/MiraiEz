<?php
//定义插件父类
class pluginParent
{
    const _pluginName = "MiraiEz";
    const _pluginAuthor = "NKXingXh";
    const _pluginDescription = "MiraiEz 插件核心";
    const _pluginPackage = "top.nkxingxh.miraiez";
    const _pluginVersion = version;

    //构造函数
    public function __construct()
    {
    }

    //初始化插件
    public function _init()
    {
        hookRegister('miraiezHook', 'FriendMessage');
        return true;
    }
}

function miraiezHook($_DATA)
{
    global $_PlainText;
    if ($_PlainText == '/miraiez') {
        replyMessage("欢迎使用 MiraiEz! 当前版本: " . pluginParent::_pluginVersion);
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
    //if (defined('mdm_cli')) echo "插件目录: $pluginsDir\n";

    global $_plugins;
    $_plugins = array();                        //插件列表 (插件名 => 插件对象)
    $_plugins_files = scandir($pluginsDir);     //获取插件目录下的所有文件
    //if (defined('mdm_cli')) echo "共扫描到 " . count($_plugins_files) . " 个插件\n";

    //计数器
    global $_plugins_count_register, $_plugins_count_load;
    $_plugins_count_register = 0;                //注册插件计数器
    $_plugins_count_load = 0;                    //加载插件计数器

    $GLOBALS['__pluginFile__'] = "plugins.php";
    pluginRegister(new pluginParent);           //注册一个空插件，用于挂钩全局函数 (兼容 v1 插件)
    unset($_plugins[pluginParent::_pluginPackage]['object']); //删除空插件对象, 使得执行挂钩函数时在全局寻找

    //if (defined('mdm_cli')) echo "开始遍历插件目录...\n";
    //遍历所有插件文件
    foreach ($_plugins_files as $__pluginFile__) {
        //if (defined('mdm_cli')) echo "检查 $__pluginFile__ ";
        //判断是否为 .php 文件
        if (!preg_match('/\.(php|disabled)$/', $__pluginFile__)) {
            //if (defined('mdm_cli')) echo "不是插件文件\n";
            continue;
        }
        if (is_file("$pluginsDir/$__pluginFile__")) {
            //if (defined('mdm_cli')) echo "是插件文件\n";
            $GLOBALS['__pluginFile__'] = $__pluginFile__;       //设置当前插件文件名
            $GLOBALS['__pluginPackage__'] = pluginParent::_pluginPackage; //当前插件包名先设置为父插件 (用于兼容 v1 的直接函数挂钩插件)
            include "$pluginsDir/$__pluginFile__";              //加载插件文件
        } else {
            //if (defined('mdm_cli')) echo "不是文件\n";
        }
    }
    unset($GLOBALS['__pluginFile__'], $GLOBALS['__pluginPackage__'], $_plugins_files);
    //if (defined('mdm_cli')) echo "加载结束\n";
}

/**
 * 注册插件
 * @param Class $pluginClass 插件类
 */
function pluginRegister($pluginClass)
{
    global $_plugins, $__pluginPackage__;

    $__pluginClassName__ = get_class($pluginClass);
    $__pluginPackage__ = $pluginClass::_pluginPackage;

    if (array_key_exists($__pluginPackage__, $_plugins)) {
        //插件已存在
        return false;
    }
    //计数器
    global $_plugins_count_register, $_plugins_count_load;
    $_plugins_count_register++;
    //创建插件对象
    $_plugins[$__pluginPackage__] = array(
        'name' => $pluginClass::_pluginName,
        'author' => $pluginClass::_pluginAuthor,
        'description' => $pluginClass::_pluginDescription,
        //'package' => $pluginClass::_pluginPackage,
        'version' => $pluginClass::_pluginVersion,
        'className' => $__pluginClassName__,
        'file' => $GLOBALS['__pluginFile__'],
        'object' => null,
        'hooked' => null
    );
    if (pluginIsEnable($__pluginPackage__, $GLOBALS['__pluginFile__'])) {
        $_plugins[$__pluginPackage__]['hooked'] = array();
        //初始化插件
        if ($pluginClass->_init() === false) {
            $_plugins[$__pluginPackage__]['hooked'] = false;  //插件初始化失败
        } else {
            //计数器
            $_plugins_count_load++;
            $_plugins[$__pluginPackage__]['object'] = $pluginClass;   //插件初始化成功
            return true;
        }
    } else {
        //插件未启用
        $_plugins[$__pluginPackage__]['object'] = false;
        $_plugins[$__pluginPackage__]['hooked'] = null;
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
        global $pfa_func_registered, $pfa_func_hooked;
        $pfa_func_registered++;  //已注册函数数量 +1
    }
    foreach ($types as $type) {
        if ($type == $_DATA['type']) {      //仅当注册类型与 webhook 上报的类型一样时，才添加
            if (empty($GLOBALS['__pluginPackage__']) || empty($_plugins[$GLOBALS['__pluginPackage__']]['object'])) {
                if (!function_exists($func)) {
                    throw new Exception("Try to hook a function that does not exist"/*, 1*/);
                    return false;
                }
                $_plugins[pluginParent::_pluginPackage]['hooked'][] = $func;    //添加到空插件中 (v1 插件)
            } else {
                if (!method_exists($_plugins[$GLOBALS['__pluginPackage__']]['object'], $func)) {
                    throw new Exception("Try to hook a method that does not exist"/*, 1*/);
                    return false;
                }
                $_plugins[$GLOBALS['__pluginPackage__']]['hooked'][] = $func;   //挂钩对象中的方法 (v2 插件)
            }

            if (pfa) $pfa_func_hooked++;  //挂钩函数数量加 1
            return true;                //挂钩成功
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
 * @param string $pluginFile 插件文件名
 */
function pluginIsEnable($pluginPackage = "", $pluginFile = "")
{
    if (substr($pluginFile, -4) !== ".php") return false;
    return true;
}

/**
 * 执行插件函数
 */
function execPluginsFunction()
{
    global $_plugins, $_DATA;                          //插件列表，数据
    global $_plugins_count_exec;                       //执行插件计数器
    $_plugins_count_exec = 0;                           //初始化计数器
    global $__pluginPackage__;                          //当前正在执行的插件
    foreach ($_plugins as $__plugin__) {            //遍历已注册的插件列表
        if (!empty($__plugin__['hooked']) && is_array($__plugin__['hooked'])) { //判断是否已挂钩
            $inObject = isset($__plugin__['object']) && is_object($__plugin__['object']);
            $__pluginPackage__ = $inObject ? $__plugin__['object']::_pluginPackage : "pluginParent";
            foreach ($__plugin__['hooked'] as $__hooked_func__) {          //遍历挂钩函数列表
                $_plugins_count_exec++;                               //计数器加1
                if ($inObject) { //判断插件对象是否存在
                    $return_code = $__plugin__['object']->$__hooked_func__($_DATA);
                } else {
                    $return_code = $__hooked_func__($_DATA);
                }

                //拦截
                if (isset($return_code) && $return_code === 1)
                    break;
            }
            unset($__plugin__['object']);                       //释放插件对象
        }
    }
    //返回计数器
    return $_plugins_count_exec;
}

/**
 * 获取当前插件身份
 * 
 * @return string|bool 成功则返回插件包名，失败则返回 false
 */
function plugin_whoami()
{
    return empty($GLOBALS['__pluginPackage__']) ? false : $GLOBALS['__pluginPackage__'];
}
