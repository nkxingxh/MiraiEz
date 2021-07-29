# miraiez

![](https://img.shields.io/github/license/cyanray/mirai-cpp.svg)

## 开始使用

请在 mirai-api-http 的配置文件中启用 http 和 webhook 适配器

并将 webhook 适配器的回调地址设置为 webhook.php 的所在地址

例如 http://localhost/webhook.php

完成上述步骤后，请修改 config.php 中的相关设置

## 插件
将你编写的插件放入 plugins 文件夹

并在 webhook.php 中加入插件列表即可

请查看 plugins 文件夹中的示例插件

easyMirai.php 中的是核心函数

可以帮助你更快的编写插件