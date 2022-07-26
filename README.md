# miraiez

![](https://img.shields.io/github/license/nkxingxh/miraiez.svg)

## 开始使用

建议从 Releases 中下载最新的稳定版本。
请在 mirai-api-http 的配置文件中启用 http 和 webhook 适配器，
并将 webhook 适配器的回调地址设置为 webhook.php 的所在地址。
例如 http://localhost/webhook.php
完成上述步骤后，请修改 config.php 中的相关设置。

## 插件

将你编写的插件放入 plugins 文件夹，
并在 webhook.php 中加入插件列表即可。
请查看 plugins 文件夹中的示例插件；
core.php 中的是核心函数；
easyMirai.php 中的函数则可以帮助你更快的编写插件。

## OneBot 兼容

miraiez 现在可以兼容部分 OneBot 框架，
相比其他 OneBot 框架来说， go-cqhttp 的兼容性是最好的。
建议与频道相关的功能使用 go-cqhttp，
其他功能使用 mirai-api-http 来获得最好的使用体验。

## OneBotBridge 配置方法

将 OneBotBridge 第二行的常量 `OneBotBridge` 的值设置为为 true, 即可启用 OneBot 支持。
miraiez 在收到消息上报后, 会自动在数据目录 (即 data_ 开头的目录) 生成配置文件。
请根据需要修改配置文件。
目前 miraiez 仅在 go-cqhttp 框架通过测试, 且除频道外的大部分功能可能不支持。
