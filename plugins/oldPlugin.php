<?php
/**
 * 这个是 v1 的示例插件，现已不推荐使用
 * 请查看 examplePlugin.php 文件
 */


//注册事件(可一次性注册多个事件)
//第一个参数是目标函数名
//接下来的参数至少要有一个，可以有多个，表示要注册的消息或事件类型 (只有这些类型的消息或事件才会触发参数一对应的函数)
hookRegister('exampleOldFunc', 'FriendMessage');

/**
 * 注册的函数的第一个参数会传入 JSON 解码后的 webhook 上报数据
 * 例如 $_DATA['sender']['id'] 就是发送者的 qq 号
 * 更多可查阅 mirai-api-http 开发文档 
 */
function exampleOldFunc($_DATA)
{
    //如果你需要使用，则需在函数内声明使用全局变量
    //这三个变量分别是消息链中的 文本消息 (string)、图片链接 (数组)、At的成员QQ (数组)
    global $_PlainText, $_ImageUrl, $_At;

    //如果消息文本为 "hello", 将会发送 "Hello world"
    if ($_PlainText == 'hello') {
        //使用 replyMessage() 可以快速回复消息
        //replyMessage 的第一个参数是 消息链, 如果不是消息链将会自动转换成消息链
        //replyMessage 的第二个参数是 要引用的 msgid, 如果值严格为 true 将会自动引用当前上报的消息
        replyMessage("Hello world");

        //上面的语句和这一句效果一样
        //replyMessage(getMessageChain("Hello world"));
        //使用 getMessageChain() 可以生成消息链
    }

    //如果消息文本为 "image", 将会发送你的头像
    if ($_PlainText == 'image') {
        $imgUrl = 'http://q1.qlogo.cn/g?b=qq&s=640&nk=' . $_DATA['sender']['id'];
        //$imgUrl = "你的图片地址";
        replyMessage(getMessageChain('这里是消息文本', $imgUrl), true);
    }

    //使用 mirai_session_start() 启动 SESSION
    //对于私聊（好友/临时）消息，每个 QQ 号对应一个会话
    //对于群消息，每一对 QQ号 和 群号 对应一个会话（假如一个QQ加入了A群和B群，那么这个QQ在这两个群的SESSION是隔离开的）
    //启动 SESSION 后可使用 $_SESSION 储存会话数据
    mirai_session_start();

    $_SESSION['hello'] = "world";

    //使用 getConfig() 可以读取指定的配置文件并返回 JSON 解码后的数据 (当然叫它数据文件之类的也可以)
    //只需要传入文件名即可, 注意这个文件名不需要加上 .json 之类的后缀
    $conf = getConfig('exampleConfig');

    $conf['data'] = "Hello world";

    //使用 saveConfig() 可以保存配置文件 (会自动进行 JSON 编码, 所以无需手动编码)
    //第一个参数同上
    //第二个参数是未经过 JSON 编码的数据
    saveConfig('exampleConfig', $conf);
}
