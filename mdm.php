<?php

/**
 * MiraiEz Plugins Doge Manager
 * MDM 插件管理器
 */

class miraiEzPluginsDogeManager
{
    const _version = "1.1.0";
    const _pluginsCenterAPI = "https://api.nkxingxh.top/miraiez/plugins.php";

    //构造函数
    public function __construct($mode = 'plugin')
    {
        switch ($mode) {
            case 'cli':
                $this->_cli();
                break;
        }
    }

    public function _cli()
    {
        echo CliStyles::ColorCyan . "MiraiEz Plugins Doge Manager\n" . CliStyles::Reset;
        $shortopts  = "";
        $longopts  = array(
            "help",     //显示帮助
            "version",  //显示版本
            "install:", //安装插件
            "remove:",  //卸载插件
            "enable:",  //启用插件
            "disable:", //禁用插件
            "update",   //更新插件
            "search:",  //搜索插件
            "list"      //显示插件列表
        );
        $options = getopt($shortopts, $longopts);
        if (isset($options['help'])) {
            echo  CliStyles::ColorYellow . "\n--help" . CliStyles::Reset . "  -  " . CliStyles::ColorGreen . "帮助"
                . CliStyles::ColorYellow . "\n--version" . CliStyles::Reset . "  -  " . CliStyles::ColorGreen . "版本信息"
                . CliStyles::ColorYellow . "\n--install " . CliStyles::ColorCyan . "<插件包名>" . CliStyles::Reset . "  -  " . CliStyles::ColorGreen . "安装插件"
                . CliStyles::ColorYellow . "\n--remove " . CliStyles::ColorCyan . "<插件包名>" . CliStyles::Reset . "  -  " . CliStyles::ColorGreen . "卸载插件"
                . CliStyles::ColorYellow . "\n--enable " . CliStyles::ColorCyan . "<插件包名>" . CliStyles::Reset . "  -  " . CliStyles::ColorGreen . "启用插件"
                . CliStyles::ColorYellow . "\n--disable " . CliStyles::ColorCyan . "<插件包名>" . CliStyles::Reset . "  -  " . CliStyles::ColorGreen . "禁用插件"
                . CliStyles::ColorYellow . "\n--search " . CliStyles::ColorCyan . "<关键字>" . CliStyles::Reset . "  -  " . CliStyles::ColorGreen . "搜索插件"
                . CliStyles::ColorYellow . "\n--update" . CliStyles::Reset . "  -  " . CliStyles::ColorGreen . "更新插件"
                . CliStyles::ColorYellow . "\n--list" . CliStyles::Reset . "  -  " . CliStyles::ColorGreen . "列出所有插件"
                . CliStyles::ColorYellow .  "\n" . CliStyles::Reset;
            return;
        } elseif (isset($options['version'])) {
            echo  "\n版本: " . self::_version
                . "\n作者: NKXingXh"
                . "\n描述: 插件管理器"
                . "\n";
            return;
        } elseif (isset($options['list'])) {
            self::initPlugins();
            global $_plugins, $_plugins_count_register, $_plugins_count_load;
            echo CliStyles::ColorCyan . "已注册 $_plugins_count_register 个插件, 已加载 $_plugins_count_load 个插件"
                . "\n\n" . CliStyles::ColorGreen . "已启用的插件:"
                . "\n" . CliStyles::Reset;

            $plugins_disabled = array();
            $plugins_failed = array();
            foreach ($_plugins as $pluginPackage => $plugin) {
                if (isset($plugin['object']) && $plugin['object'] === false) {   //插件已禁用
                    $plugins_disabled[] = array_merge($plugin, array('package' => $pluginPackage));
                } elseif (isset($plugin['hooked']) && $plugin['hooked'] === false) { //插件加载失败
                    $plugins_failed[] = array_merge($plugin, array('package' => $pluginPackage));
                } else {
                    echo  CliStyles::ColorCyan . $plugin['name']
                        . CliStyles::ColorCyan . "  " . CliStyles::StyleInvert . " v" . $plugin['version'] . ' ' . CliStyles::Reset
                        . CliStyles::ColorMagenta . "  ($pluginPackage)"
                        . CliStyles::Reset . "  -  " . CliStyles::ColorGreen . $plugin['description'] . "  "
                        . CliStyles::Reset . "作者 " . CliStyles::BgLightGreen . CliStyles::ColorBlack . ' ' . $plugin['author'] . ' ' . CliStyles::Reset . "\n";
                }
            }

            if (!empty($plugins_disabled)) {
                echo CliStyles::ColorYellow . "\n已禁用的插件:\n" . CliStyles::Reset;
                foreach ($plugins_disabled as $plugin) {
                    echo  CliStyles::ColorCyan . $plugin['name']
                        . CliStyles::ColorCyan . "  " . CliStyles::StyleInvert . " v" . $plugin['version'] . ' ' . CliStyles::Reset
                        . CliStyles::ColorMagenta . " (" . $plugin['package'] . ")"
                        . CliStyles::Reset . "  -  " . CliStyles::ColorGreen . $plugin['description'] . "  "
                        . CliStyles::Reset . "作者 " . CliStyles::BgLightGreen . CliStyles::ColorBlack . ' ' . $plugin['author'] . ' ' . CliStyles::Reset . "\n";
                }
            }
            if (!empty($plugins_failed)) {
                echo CliStyles::ColorRed . "\n加载失败的插件:\n" . CliStyles::Reset;
                foreach ($plugins_failed as $plugin) {
                    echo  CliStyles::ColorCyan . $plugin['name']
                        . CliStyles::ColorCyan . "  " . CliStyles::StyleInvert . " v" . $plugin['version'] . ' ' . CliStyles::Reset
                        . CliStyles::ColorMagenta . " (" . $plugin['package'] . ")"
                        . CliStyles::Reset . "  -  " . CliStyles::ColorGreen . $plugin['description'] . "  "
                        . CliStyles::Reset . "作者 " . CliStyles::BgLightGreen . CliStyles::ColorBlack . ' ' . $plugin['author'] . ' ' . CliStyles::Reset . "\n";
                }
            }
            echo CliStyles::Reset;
            return;
        } elseif (isset($options['enable']) || isset($options['disable'])) {
            $status = isset($options['enable']) ? true : false;
            $package = $status ? $options['enable'] : $options['disable'];

            self::initPlugins();
            $result = $this->setPluginStatus($package, $status);

            $statusText = $status ? '启用' : '禁用';

            global $_plugins;
            if ($result === true) {
                echo  CliStyles::ColorCyan . $_plugins[$package]['name'] . CliStyles::ColorGreen . " 插件{$statusText}成功" . CliStyles::Reset . "\n";
            } elseif ($result === 1) {
                echo CliStyles::ColorCyan . $_plugins[$package]['name'] . CliStyles::ColorYellow . " 插件已经{$statusText}了" . CliStyles::Reset . "\n";
            } elseif ($result === null) {
                echo CliStyles::ColorRed . "插件不存在" . CliStyles::Reset . "\n";
            } else {
                echo CliStyles::ColorCyan . $_plugins[$package]['name'] . CliStyles::ColorRed . "插件{$statusText}失败" . CliStyles::Reset . "\n";
            }
            return;
        } elseif (isset($options['install'])) {
            self::initPlugins();
            global $_plugins;
            if ($options['install'] == pluginParent::_pluginPackage) {
                echo CliStyles::ColorRed . "暂不支持更新框架自身" . CliStyles::Reset . "\n";
                return;
            }
            //正在获取插件信息
            echo CliStyles::ColorCyan . "正在获取插件信息..." . CliStyles::Reset;
            $resp = self::getPluginInfo($options['install']);
            //清空当前行
            echo CliStyles::clearLine . CliStyles::toLineStart; //清除当前行并回到行首
            if ($resp === '') {
                echo CliStyles::ColorRed . "获取插件信息失败" . CliStyles::Reset . "\n";
                return;
            }
            $resp = json_decode($resp, true);
            if ($resp === null) {
                echo CliStyles::ColorRed . "解析返回数据失败" . CliStyles::Reset . "\n";
                return;
            } elseif ($resp['code'] == 404) {
                echo CliStyles::ColorRed . "未找到该插件" . CliStyles::Reset . "\n";
                return;
            } elseif ($resp['code'] != 200) {
                //服务器错误
                echo CliStyles::ColorRed . "服务器返回错误, 返回码: " . $resp['code'] . ", 消息: " . $resp['msg'] . CliStyles::Reset . "\n";
                return;
            }

            $package = $resp['data']['package'];
            $sizeStr = $resp['data']['filesize'] > 0 ? self::formatBytes($resp['data']['filesize']) : '未知';
            echo "\n"
                . CliStyles::ColorCyan . $resp['data']['name'] . '  -  ' . CliStyles::StyleInvert . ' v' . $resp['data']['version'] . ' ' . CliStyles::Reset . "  大小: $sizeStr\n"
                . CliStyles::ColorGreen . $resp['data']['description'] . CliStyles::Reset . "  作者 " . CliStyles::BgLightGreen . CliStyles::ColorBlack . ' ' . $resp['data']['author'] . ' ' . CliStyles::Reset . "\n";


            $file = '';
            //判断插件是否已经安装
            if (isset($_plugins[$package])) {
                $file = $_plugins[$package]['file'];
                //比较版本
                switch (version_compare($resp['data']['version'], $_plugins[$package]['version'])) {
                    case 0:
                        echo CliStyles::ColorGreen . "你已经安装了该插件的最新版本, 无需操作!" . CliStyles::Reset . "\n";
                        return;
                    case 1:
                        //本地版本更低
                        $action = 'update';
                        echo CliStyles::ColorYellow . "已安装该插件的老版本 (v" . $_plugins[$package]['version'] . "), 是否要升级? [Y/n] " . CliStyles::Reset;
                        break;
                    case -1:
                        //远程版本更低
                        $action = 'downgrade';
                        echo CliStyles::ColorYellow . "已安装的版本 (v" . $_plugins[$package]['version'] . ") 比远程版本更高, 是否要降级? [y/N] " . CliStyles::Reset;
                        break;
                }
            } else {
                $action = 'install';
                echo CliStyles::ColorYellow . "是否要安装该插件? [Y/n] " . CliStyles::Reset;
            }

            $actionText = $action == 'install' ? '安装' : ($action == 'update' ? '升级' : '降级');
            //读取用户输入
            $input = trim(fgets(STDIN));
            echo CliStyles::toPrevLine . CliStyles::clearLine . CliStyles::toLineStart; //清除询问与用户输入行
            if ($action == 'downgrade' ? strtolower($input) != 'y' : strtolower($input) == 'n') {
                echo CliStyles::ColorRed . "已取消{$actionText}" . CliStyles::Reset . "\n";
                return;
            }

            echo CliStyles::ColorCyan . "正在{$actionText}插件..." . CliStyles::Reset;
            $result = self::installPlugin($package, $file);
            echo CliStyles::clearLine . CliStyles::toLineStart; //清除当前行并回到行首
            if (is_string($result)) {
                echo CliStyles::ColorRed . $result . CliStyles::Reset . "\n";
                return;
            }
            switch ($result) {
                case true:
                    echo CliStyles::ColorGreen . "插件{$actionText}成功!" . CliStyles::Reset . "\n";
                    break;
                case 0:
                    //获取失败
                    echo CliStyles::ColorRed . "获取插件数据失败!" . CliStyles::Reset . "\n";
                    return;
                case -1:
                    //解析失败
                    echo CliStyles::ColorRed . "解析返回数据失败!" . CliStyles::Reset . "\n";
                case -2:
                    //校验失败
                    echo CliStyles::ColorRed . "插件内容校验失败!" . CliStyles::Reset . "\n";
                case -3:
                    //写入失败
                    echo CliStyles::ColorRed . "写入插件文件失败!" . CliStyles::Reset . "\n";
            }
            return;
        } elseif (isset($options['remove'])) {
            self::initPlugins();
            global $_plugins;
            if (isset($_plugins[$options['remove']])) {
                $plugin = $_plugins[$options['remove']];
                echo CliStyles::ColorYellow . "确定要删除插件 " . CliStyles::ColorCyan . $plugin['name'] . CliStyles::ColorYellow . " 吗? (y/N) " . CliStyles::Reset;
                $input = fgets(STDIN);
                echo CliStyles::toPrevLine . CliStyles::clearLine . CliStyles::toLineStart; //清除询问与用户输入行
                if (strtolower(trim($input)) !== 'y') {
                    echo CliStyles::ColorYellow . "已取消删除插件" . CliStyles::Reset . "\n";
                    return;
                }

                $result = $this->removePlugin($options['remove']);
                if ($result === true) {
                    echo CliStyles::ColorCyan . $plugin['name'] . CliStyles::ColorGreen . " 插件删除成功" . CliStyles::Reset . "\n";
                } elseif ($result === null) {
                    echo CliStyles::ColorRed . "未找到文件或无权访问" . CliStyles::Reset . "\n";
                } else {
                    echo CliStyles::ColorCyan . $plugin['name'] . CliStyles::ColorRed . " 插件删除失败" . CliStyles::Reset . "\n";
                }
            } else {
                echo CliStyles::ColorRed . "插件不存在" . CliStyles::Reset . "\n";
            }
        } elseif (isset($options['update'])) {
            self::initPlugins();
            global $_plugins;
            echo CliStyles::ColorYellow . "正在获取插件信息..." . CliStyles::Reset;
            //获取本地插件包名列表
            $packages = array_keys($_plugins);
            $resp = self::getPluginInfo($packages);
            unset($packages);

            echo CliStyles::clearLine . CliStyles::toLineStart; //清除当前行并回到行首

            if ($resp === '') {
                echo CliStyles::ColorRed . "获取插件信息失败" . CliStyles::Reset . "\n";
                return;
            }
            $resp = json_decode($resp, true);
            if ($resp === null) {
                echo CliStyles::ColorRed . "解析返回数据失败" . CliStyles::Reset . "\n";
                return;
            } elseif ($resp['code'] != 200) {
                echo CliStyles::ColorRed . "获取插件信息失败! 返回码: " . $resp['code'] . ", 消息: " . $resp['msg'] . CliStyles::Reset . "\n";
                return;
            }

            $updatable = array();       //可更新的插件列表
            $latest = array();          //最新版本的插件列表
            $unavailable = array();     //不可用的插件列表
            foreach ($_plugins as $package => $plugin) {
                if (array_key_exists($package, $resp['data'])) {
                    //比较版本
                    if (version_compare($plugin['version'], $resp['data'][$package]['version'], '<')) {
                        $updatable[] = $package;
                    } else {
                        $latest[] = $package;
                    }
                } else {
                    $unavailable[] = $package;
                }
            }
            unset($plugin);

            echo CliStyles::ColorGreen . "\n可更新插件 " . CliStyles::ColorCyan . CliStyles::StyleInvert . ' ' . count($updatable) . ' ' . CliStyles::Reset . CliStyles::ColorCyan . " 个\n" . CliStyles::Reset;
            foreach ($updatable as $package) {
                echo  CliStyles::ColorCyan . $_plugins[$package]['name']
                    . CliStyles::ColorCyan . "  " . CliStyles::StyleInvert . " v" . $_plugins[$package]['version'] . ' ' . CliStyles::Reset
                    . ' -> '
                    . CliStyles::ColorCyan . " v" . $resp['data'][$package]['version'] . ' ' .  CliStyles::Reset
                    . CliStyles::ColorMagenta . " (" . $package . ")"
                    . CliStyles::Reset . "  -  " . CliStyles::ColorGreen . $_plugins[$package]['description'] . "  "
                    . CliStyles::Reset . "作者 " . CliStyles::BgLightGreen . CliStyles::ColorBlack . ' ' . $_plugins[$package]['author'] . ' ' . CliStyles::Reset . "\n";
            }

            //无需更新插件
            echo CliStyles::ColorGreen . "\n无需更新插件 " . CliStyles::ColorCyan . CliStyles::StyleInvert . ' ' . count($latest) . ' ' . CliStyles::Reset . CliStyles::ColorCyan . " 个\n" . CliStyles::Reset;
            foreach ($latest as $package) {
                echo  CliStyles::ColorCyan . $_plugins[$package]['name']
                    . CliStyles::ColorCyan . "  " . CliStyles::StyleInvert . " v" . $_plugins[$package]['version'] . ' ' . CliStyles::Reset
                    . CliStyles::ColorMagenta . " (" . $package . ")"
                    . CliStyles::Reset . "  -  " . CliStyles::ColorGreen . $_plugins[$package]['description'] . "  "
                    . CliStyles::Reset . "作者 " . CliStyles::BgLightGreen . CliStyles::ColorBlack . ' ' . $_plugins[$package]['author'] . ' ' . CliStyles::Reset . "\n";
            }

            //不可用插件
            echo CliStyles::ColorRed . "\n未找到插件 " . CliStyles::ColorCyan . CliStyles::StyleInvert . ' ' . count($unavailable) . ' ' . CliStyles::Reset . CliStyles::ColorCyan . " 个\n" . CliStyles::Reset;
            foreach ($unavailable as $package) {
                echo  CliStyles::ColorCyan . $_plugins[$package]['name']
                    . CliStyles::ColorCyan . "  " . CliStyles::StyleInvert . " v" . $_plugins[$package]['version'] . ' ' . CliStyles::Reset
                    . CliStyles::ColorMagenta . " (" . $package . ")"
                    . CliStyles::Reset . "  -  " . CliStyles::ColorGreen . $_plugins[$package]['description'] . "  "
                    . CliStyles::Reset . "作者 " . CliStyles::BgLightGreen . CliStyles::ColorBlack . ' ' . $_plugins[$package]['author'] . ' ' . CliStyles::Reset . "\n";
            }

            echo "\n";
            if (count($updatable) == 0) {
                echo CliStyles::ColorYellow . "没有可更新的插件\n" . CliStyles::Reset;
                return;
            }

            echo CliStyles::ColorYellow . "是否更新? [Y/n] " . CliStyles::Reset;
            $answer = trim(fgets(STDIN));
            echo CliStyles::toPrevLine . CliStyles::clearLine . CliStyles::toLineStart; //清除询问与用户输入行
            if (strtolower($answer) == 'n') {
                echo CliStyles::ColorYellow . "已取消更新\n" . CliStyles::Reset;
                return;
            }

            foreach ($updatable as $package) {
                echo CliStyles::StyleBold . CliStyles::ColorCyan . $_plugins[$package]['name'] . CliStyles::Reset . CliStyles::ColorYellow . " 更新中..." . CliStyles::Reset;
                $result = self::installPlugin($package, $_plugins[$package]['file']);
                echo CliStyles::clearLine . CliStyles::toLineStart; //清除当前行并回到行首
                echo CliStyles::ColorCyan . $_plugins[$package]['name'] . ' ' . CliStyles::Reset;
                if (is_string($result)) {
                    echo CliStyles::ColorRed . $result . CliStyles::Reset . "\n";
                    return;
                }
                switch ($result) {
                    case true:
                        echo CliStyles::ColorGreen . "更新成功" . CliStyles::Reset . "\n";
                        break;
                    case 0:
                        //获取失败
                        echo CliStyles::ColorRed . "获取插件数据失败!" . CliStyles::Reset . "\n";
                        return;
                    case -1:
                        //解析失败
                        echo CliStyles::ColorRed . "解析返回数据失败!" . CliStyles::Reset . "\n";
                    case -2:
                        //校验失败
                        echo CliStyles::ColorRed . "插件内容校验失败!" . CliStyles::Reset . "\n";
                    case -3:
                        //写入失败
                        echo CliStyles::ColorRed . "写入插件文件失败!" . CliStyles::Reset . "\n";
                }
            }
        } elseif (isset($options['search'])) {
            self::initPlugins();
            echo CliStyles::ColorYellow . "正在从插件中心检索数据..." . CliStyles::Reset;
            $payload = http_build_query(array(
                'action' => 'search',
                'query' => $options['search']
            ));
            $resp = CurlPOST($payload, self::_pluginsCenterAPI);
            echo CliStyles::clearLine . CliStyles::toLineStart; //清除当前行并回到行首
            if ($resp == '') {
                echo CliStyles::ColorRed . "获取数据失败!" . CliStyles::Reset . "\n";
                return;
            }
            $resp = json_decode($resp, true);
            if ($resp === null) {
                echo CliStyles::ColorRed . "解析返回数据失败!" . CliStyles::Reset . "\n";
                return;
            }
            if ($resp['code'] != 200) {
                echo CliStyles::ColorRed . "检索失败! 返回码: " . $resp['code'] . ", 消息: " . $resp['msg'] . CliStyles::Reset . "\n";
                return;
            }
            $resultCount = count($resp['data']);
            if ($resultCount == 0) {
                echo CliStyles::ColorRed . "没有找到结果\n" . CliStyles::Reset;
                return;
            }
            echo CliStyles::ColorGreen . "\n检索到 " . CliStyles::ColorCyan . CliStyles::StyleInvert . " $resultCount " . CliStyles::Reset . CliStyles::ColorGreen . " 个插件\n" . CliStyles::Reset;
            foreach ($resp['data'] as $plugin) {
                echo CliStyles::ColorCyan . $plugin['name'] . CliStyles::ColorCyan . "  " . CliStyles::StyleInvert . " v" . $plugin['version'] . ' ' . CliStyles::Reset
                    . CliStyles::ColorMagenta . " (" . $plugin['package'] . ")"
                    . CliStyles::Reset . "  -  " . CliStyles::ColorGreen . $plugin['description'] . "  "
                    . CliStyles::Reset . "作者 " . CliStyles::BgLightGreen . CliStyles::ColorBlack . ' ' . $plugin['author'] . ' ' . CliStyles::Reset . "\n";
            }
        } else {
            echo CliStyles::ColorRed . "参数错误, 使用 --help 查看帮助\n" . CliStyles::Reset;
            return;
        }
    }

    //初始化插件支持
    private static function initPlugins()
    {
        require_once "loader.php";
        global $_DATA;
        $_DATA = array('type' => 'MiraiEzPluginsDogeManager');
        //echo CliStyles::ColorYellow . "初始化插件支持...\n";
        require_once "plugins.php";
        //echo "加载插件...\n";
        loadPlugins();  //加载插件
        //重置颜色
        echo CliStyles::Reset;
    }

    private static function setPluginStatus($package, $enable)
    {
        global $_plugins;
        if (isset($_plugins[$package]['file']) && file_exists(pluginsDir . '/' . $_plugins[$package]['file'])) {

            $already = $enable
                ? (isset($_plugins[$package]['object']) && $_plugins[$package]['object'] !== false)
                : (isset($_plugins[$package]['object']) && $_plugins[$package]['object'] === false);

            if ($already) return 1;

            if ($enable) {
                $old = pluginsDir . '/' . $_plugins[$package]['file'];
                $new = pluginsDir . '/' . pathinfo($_plugins[$package]['file'])['filename'];
            } else {
                $old = pluginsDir . '/' . $_plugins[$package]['file'];
                $new = pluginsDir . '/' . $_plugins[$package]['file'] . '.disabled';
            }

            if (rename($old, $new)) return true;
            else return false;
        } else return null;
    }

    private static function installPlugin($package, $file = '')
    {
        $payload = http_build_query(array(
            'action' => 'file',
            'package' => $package
        ));
        $resp = CurlPOST($payload, self::_pluginsCenterAPI);
        if ($resp == '') {
            //获取文件失败
            return 0;
        }
        $resp = json_decode($resp, true);
        if ($resp === null) {
            //解析返回数据失败
            return -1;
        } elseif ($resp['code'] != 200) {
            //服务器错误
            return "服务器返回错误, 返回码: " . $resp['code'] . ", 消息: " . $resp['msg'];
        }
        $plugin_content = base64_decode($resp['data']['file']);
        //校验文件
        if (md5($plugin_content) != $resp['data']['md5']) {
            //文件校验失败
            return -2;
        }
        //写入文件
        if (empty($file)) $file = $package . '.php';
        $file = "plugins/" . $file;
        if (file_put_contents($file, $plugin_content) === false) {
            //写入文件失败
            return -3;
        }
        return true;
    }

    private static function removePlugin($package)
    {
        global $_plugins;
        if (isset($_plugins[$package]['file']) && file_exists(pluginsDir . '/' . $_plugins[$package]['file'])) {
            return unlink(pluginsDir . '/' . $_plugins[$package]['file']);
        } else return null;
    }

    private static function getPluginInfo($package)
    {
        if (is_array($package)) {
            $payload = http_build_query(array(
                'action' => 'info',
                'packages' => json_encode($package)
            ));
        } else {
            $payload = http_build_query(array(
                'action' => 'info',
                'package' => $package
            ));
        }

        return CurlPOST($payload, self::_pluginsCenterAPI);
    }

    private static function formatBytes($bytes, $Space = true)
    {
        $sizes = array('字节', 'KB', 'MB', 'GB', 'TB', 'PB');
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.2f", $bytes / pow(1024, $factor)) . ($Space ? ' ' : '') . $sizes[$factor];
    }
}

class CliStyles
{
    const ColorRed = "\033[31m";         //红色
    const ColorGreen = "\033[32m";       //绿色
    const ColorYellow = "\033[33m";      //黄色
    const ColorBlue = "\033[34m";        //蓝色
    const ColorMagenta = "\033[35m";     //紫色
    const ColorCyan = "\033[36m";        //青色
    const ColorWhite = "\033[37m";       //白色
    const ColorBlack = "\033[30m";       //黑色
    const ColorDefault = "\033[39m";     //默认颜色

    const ColorLightGray = "\033[90m";   //浅灰色
    const ColorLightRed = "\033[91m";    //浅红色
    const ColorLightGreen = "\033[92m";  //浅绿色
    const ColorLightYellow = "\033[93m"; //浅黄色
    const ColorLightBlue = "\033[94m";   //浅蓝色
    const ColorLightMagenta = "\033[95m"; //浅紫色
    const ColorLightCyan = "\033[96m";   //浅青色
    const ColorLightWhite = "\033[97m";  //浅白色
    const ColorLightDefault = "\033[99m"; //浅默认色

    const BgRed = "\033[41m";            //红色
    const BgGreen = "\033[42m";          //绿色
    const BgYellow = "\033[43m";         //黄色
    const BgBlue = "\033[44m";           //蓝色
    const BgMagenta = "\033[45m";        //紫色
    const BgCyan = "\033[46m";          //青色
    const BgWhite = "\033[47m";         //白色
    const BgBlack = "\033[40m";         //黑色
    const BgDefault = "\033[49m";       //默认背景色

    const BgLightRed = "\033[101m";     //浅红色
    const BgLightGreen = "\033[102m";   //浅绿色
    const BgLightYellow = "\033[103m";  //浅黄色
    const BgLightBlue = "\033[104m";    //浅蓝色
    const BgLightMagenta = "\033[105m"; //浅紫色
    const BgLightCyan = "\033[106m";    //浅青色
    const BgLightWhite = "\033[107m";   //浅白色
    const BgLightBlack = "\033[100m";  //浅黑色
    const BgLightDefault = "\033[109m"; //浅默认背景色

    const StyleBold = "\033[1m";         //粗体
    const StyleUnderline = "\033[4m";    //下划线
    const StyleBlink = "\033[5m";        //闪烁
    const StyleInvert = "\033[7m";       //反色

    const Reset = "\033[0m";            //重置

    const clearLine = "\033[2K";         //清除当前行
    const clearScreen = "\033[2J";       //清除屏幕

    const toLineStart = "\033[0G";       //光标到行首
    const toLineEnd = "\033[0K";         //光标到行尾

    const toPrevLine = "\033[1A";       //光标上移一行
    const toNextLine = "\033[1B";       //光标下移一行
    const toPrevPage = "\033[1D";       //光标左移一页
    const toNextPage = "\033[1C";       //光标右移一页
    const toPrevColumn = "\033[1F";     //光标左移一列
    const toNextColumn = "\033[1E";     //光标右移一列
    const toPrevLineColumn = "\033[1S"; //光标上移一行并左移一列
    const toNextLineColumn = "\033[1T"; //光标下移一行并左移一列
}

//判断是否在 CLI 中运行
if (php_sapi_name() === 'cli') {
    define('mdm_cli', true);
    $mdm = new miraiEzPluginsDogeManager('cli');
}
