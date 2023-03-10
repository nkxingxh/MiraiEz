<?php

/**
 * MiraiEz Copyright (c) 2021-2023 NKXingXh
 * License AGPLv3.0: GNU AGPL Version 3 <https://www.gnu.org/licenses/agpl-3.0.html>
 * This is free software: you are free to change and redistribute it.
 * There is NO WARRANTY, to the extent permitted by law.
 *
 * Github: https://github.com/nkxingxh/MiraiEz
 */

pluginRegister(new class extends pluginParent   //建议继承 pluginParent 插件类,当框架更新导致插件类定义发生变化时, pluginParent 将能提供一定的容错能力
{
    //以下五行插件信息必须定义
    const _pluginName = "exampleCmdReg";                    //插件名称
    const _pluginAuthor = "nkxingxh";                       //插件作者
    const _pluginDescription = "示例命令注册插件";                  //插件描述
    const _pluginPackage = "top.nkxingxh.exampleCmdReg";    //插件包名 必须是唯一的 (如已加载相同包名的插件，将跳过当前插件类，不予加载)
    const _pluginVersion = "1.0.0";                         //插件版本

    public function __construct()
    {
        parent::__construct();
    }

    public function _init(): bool
    {
        cmdRegister(function ($_DATA, $argc, $args) {
            if (
                $_DATA['type'] == 'GroupMessage'
                ? in_array($_DATA['sender']['group']['id'], $GLOBALS['MIRAIEZ_DEBUG_GROUPS'])
                : in_array($_DATA['sender']['id'], $GLOBALS['MIRAIEZ_DEBUG_FRIENDS'])
            ) {
                replyMessage("argc: $argc\nargs: " . json_encode($args, JSON_UNESCAPED_UNICODE));
            }
        }, '/exampleCmd');
        return true;
    }
});
