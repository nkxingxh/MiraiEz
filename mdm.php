<?php

/**
 * MiraiEz Plugins Doge Manager
 * MDM 插件管理器
 */

class miraiEzPluginsDogeManager
{
    const _version = "1.0.0";
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
        echo "MiraiEz Plugins Doge Manager\n";
        $shortopts  = "";
        $longopts  = array(
            "help",
            "version",
            "install",
            "remove",
            "enable",
            "disable",
            "update",
            "list",
        );
        $options = getopt($shortopts, $longopts);
        if (isset($options['help'])) {
            echo  "\n--help - 帮助"
                . "\n--version - 版本信息"
                . "\n--install - 安装插件"
                . "\n--remove - 卸载插件"
                . "\n--enable - 启用插件"
                . "\n--disable - 禁用插件"
                . "\n--update - 更新插件"
                . "\n--list - 列出所有插件";
            return;
        } elseif (isset($options['version'])) {
            echo "MiraiEz Plugins Doge Manager"
                . "\n版本: " . self::_version
                . "\n作者: NKXingXh"
                . "\n描述: 插件管理器";
            return;
        } elseif (isset($options['list'])) {
            require_once "loader.php";
            global $_DATA;
            $_DATA = array('type' => 'MiraiEzPluginsDogeManager');
            require_once "plugins.php";
            loadPlugins();  //加载插件

            global $_plugins, $_plugins_count_register, $_plugins_count_load;
            echo "已注册 $_plugins_count_register 个插件, 已加载 $_plugins_count_load 个插件"
                . "\n\n插件列表:"
                . "\n";
            foreach ($_plugins as $pluginClassName => $plugin) {
                echo  "[$pluginClassName] " . $plugin['name'] . "  v" . $plugin['version'] . "  -  " . $plugin['description'] . "  作者: " . $plugin['author'] . "\n";
            }
            return;
        } else {
            echo "参数错误, 使用 --help 查看帮助";
            return;
        }
    }
}

//判断是否在 CLI 中运行
if (php_sapi_name() === 'cli') {
    define('mdm', true);
    $mdm = new miraiEzPluginsDogeManager('cli');
}
