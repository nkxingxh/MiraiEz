<?php
class examplePlugin extends pluginParent
{
    const _pluginName = "examplePlugin";
    const _pluginPackage = "top.nkxingxh.examplePlugin";
    const _pluginVersion = "1.0.0";

    public function __construct()
    {
        parent::__construct();
    }

    public function _init()
    {
        hookRegister('hook', 'FriendMessage');
        return true;
    }

    public function hook($_DATA)
    {
        global $_PlainText;
        if ($_PlainText == "ping") {
            replyMessage("pong");
        }
    }
}

pluginRegister(new examplePlugin);
