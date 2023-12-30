<?php
/**
 * MiraiEz Copyright (c) 2021-2024 NKXingXh
 * License AGPLv3.0: GNU AGPL Version 3 <https://www.gnu.org/licenses/agpl-3.0.html>
 * This is free software: you are free to change and redistribute it.
 * There is NO WARRANTY, to the extent permitted by law.
 * 
 * Github: https://github.com/nkxingxh/MiraiEz
 */

/**
 * sendGuildChannelMessage
 * 发送频道消息
 */
function sendGuildChannelMessage($guild, $channel, $messageChain/*, $quote = 0*/)
{
    writeLog(json_encode($messageChain, JSON_UNESCAPED_UNICODE), 'send', 'OneBot', 1);
    $messageChain = is_array($messageChain) ? $messageChain : getMessageChain($messageChain);
    //实际上还不支持回复功能
    //if (!empty($quote)) $messageChain[] = array('type' => 'Quote', 'id' => $quote);
    writeLog(json_encode($messageChain, JSON_UNESCAPED_UNICODE), 'send', 'OneBot', 1);
    $content = array(
        'guild' => $guild,
        'channel' => $channel,
        'messageChain' => $messageChain
    );
    return autoAdapter('sendGuildChannelMessage', $content);
}

function getGuildServiceProfile()
{
    return OneBot_API_11('get_guild_service_profile');
}
