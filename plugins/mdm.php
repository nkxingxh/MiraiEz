<?php

/**
 * MiraiEz Plugins Doge Manager
 * 插件管理器
 */

class miraiEzPluginsDogeManagerPlugin extends pluginParent
{
    const _pluginName = "MiraiEz Plugins Doge Manager";
    const _pluginAuthor = "nkxingxh";
    const _pluginDescription = "插件管理器";
    const _pluginPackage = "top.nkxingxh.mdm.plugin";
    const _pluginVersion = "1.0.0";

    public function __construct()
    {
        parent::__construct();
    }

    public function _init()
    {
        return true;
    }

    public function _hook($_DATA)
    {
        global $_PlainText;
        if (!str_starts_with_non_native(strtolower($_PlainText), '/mdm ') && strtolower($_PlainText) !== "/mdm") {
            return;
        }
        //分割参数
        $args = explode(' ', $_PlainText);
        $argc = count($args);

        //检查参数数量
        if ($argc < 2) {
            replyMessage(
                "参数错误, 用法: /mdm <命令> <参数>"
                    . "\n命令:"
                    . "\nlist - 列出所有插件"

                    . "\nenable - 启用插件"
                    . "\ndisable - 禁用插件"
                    . "\nupdate - 更新插件"

                    . "\ninstall - 安装插件"
                    . "\nremove - 卸载插件"
            );
            return;
        }

        switch ($args[1]) {
                //列出插件
            case 'list':
                global $_plugins;
                $plugins = array_keys($_plugins);
                $plugins = array_map(function ($plugin) {
                    global $_plugins;
                    return $_plugins[$plugin]->_pluginName;
                }, $plugins);
                break;
        }
    }
}
