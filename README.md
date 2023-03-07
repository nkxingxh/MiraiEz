# MiraiEz

![GitHub](https://img.shields.io/github/license/nkxingxh/MiraiEz?style=flat-square)
![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/nkxingxh/MiraiEz?style=flat-square)

## [用户手册](https://miraiez.nkxingxh.top/)

## 交流

我们的交流群: 749709015

## 开始使用

### 建议阅读 [快速部署指南](https://miraiez.nkxingxh.top/guide/)

- 建议从 Releases 中下载最新的稳定版本。
- 请在 mirai-api-http 的配置文件中启用 http 和 webhook 适配器
- 并将 webhook 适配器的回调地址设置为 /webhook.php 的所在地址。
- 例如 http://localhost/webhook.php
- 完成上述步骤后，请修改 config 目录中的相关配置文件。
- **(重要) 在 Web 服务器的站点配置中，将运行目录修改为 /public**

#### 注意

1. 请勿在 mirai-api-http 中开启单会话模式！

1. 测试发现 mirai-api-http v2.6.2 的 webhook 适配器存在一些问题, 导致不执行 MiraiEz 通过其返回的命令。

**临时解决方案**

> 在 config/debug.php 中将 MIRAIEZ_ADAPTER_ALWAYS_USE_HTTP 改为 true 使 MiraiEz 只通过 HTTP 适配器发送命令即可。
> 但该方式会导致响应时间变长, 在 Bug 修复后建议还原。

## 插件开发

**墙裂(雾)建议各位开发者阅读[用户手册](https://miraiez.nkxingxh.top/)**

将你编写的插件放入 plugins 文件夹即可自动加载
请查看 plugins 文件夹中的示例插件；
core.php 中的是核心函数；
easyMirai.php 中的函数则可以帮助你更快的编写插件。

> 现已支持将插件包装在 类 (Class) 中，同时兼容老版本插件。
推荐各位开发者将老版本插件封装起来，具体可参考示例插件。

## MDM 插件管理器

> MiraiEz Plugins Doge Manager

目前支持 插件列表、启用、禁用、安装、卸载与更新 功能。

查看 [插件仓库](https://github.com/nkxingxh/miraiez-plugins "插件仓库")

#### ~~演示~~

    Shell > php mdm.php --help
    MiraiEz Plugins Doge Manager
    
    --help  -  帮助
    --version  -  版本信息
    --install <插件包名>  -  安装插件
    --remove <插件包名>  -  卸载插件
    --enable <插件包名>  -  启用插件
    --disable <插件包名>  -  禁用插件
    --update  -  更新插件
    --list  -  列出所有插件
    
    Shell > php mdm.php --version
    MiraiEz Plugins Doge Manager
    
    版本: 1.0.0
    作者: NKXingXh
    描述: 插件管理器
    
    Shell > php mdm.php --list
    MiraiEz Plugins Doge Manager
    已注册 1 个插件, 已加载 1 个插件
    
    已启用的插件:
    examplePlugin   v1.0.0   (top.nkxingxh.examplePlugin)  -  示例插件  作者  nkxingxh
    

## OneBot 兼容 (目前只支持频道)

miraiez 现在可以兼容部分 OneBot 框架，
相比其他 OneBot 框架来说， go-cqhttp 的兼容性是最好的。
建议与频道相关的功能使用 go-cqhttp，
其他功能使用 mirai-api-http 来获得最好的使用体验。

## OneBotBridge 配置方法

将 OneBotBridge 第二行的常量 `OneBotBridge` 的值设置为为 true, 即可启用 OneBot 支持。
miraiez 在收到消息上报后, 会自动在数据目录 (即 data_ 开头的目录) 生成配置文件。
请根据需要修改配置文件。
目前 miraiez 仅在 go-cqhttp 框架通过测试, 且除频道外的大部分功能可能不支持。

## 开发计划 ~~(鸽子画饼)~~

| 功能或特性 | 开发状态 | 说明 |
| ----------- | --------- | ----- |
| 插件对象 | 已完成 | 将每个插件的内容包含在一个 类 (Class) 中。 |
| 插件管理 | 测试中 | 允许通过插件管理器对插件进行常见操作。 |
| 命令解析 | 开发中 | 能够解析消息中的命令，例如 /ping github.com，并使插件可以注册特定命令而无需处理不相关消息。 |
| cli 独立运行 | 待开发 | 引入 workerman 框架，脱离 web 服务器，提升性能 |
| 计划任务 | 待开发 | 允许插件注册定时任务，使得插件具有一定的主动能力。 |

## 许可

> Copyright (c) 2021-2023 NKXingXh

MiraiEz 根据 **AGPL-3.0 许可证** 进行许可，有关详细信息，请参阅 [LICENSE](https://github.com/nkxingxh/MiraiEz/blob/main/LICENSE) 文件。

**附加要求**

你必须保留每个文件顶部的版权标识。

如果你修改后进行重新分发，请在自述文件或修改的文件顶部说明你修改的部分。

## 兄弟项目

[MiraiTravel](https://github.com/MR-XieXuan/MiraiTravel)

| 功能或特性 | MiraiEz | MiraiTravel |
| --------- | ------- | ----------- |
| 消息处理 | ✅ | ✅ |
| 事件处理 | ✅ | ✅ |
| HTTP 适配器 | ✅ | ✅ |
| 异常处理 | ✅ | ✅ |
| 日志记录 | ✅ | ✅ |
| 异常反馈 | ✅ | ❌ |
| 性能分析 | 简单分析 | ❌ |
| 命令注册 | 由插件提供 | ❌ |
| 命名空间 | 无要求 | 较为严格 |
| 框架 API 实现 | 全局函数 | 类方法 |
| 致命错误处理 | 终止运行 | 取消插件载入 |
| 多账号 | 由插件处理 | 由独立[脚本](https://github.com/MR-XieXuan/MiraiTravel/blob/main/script/Q2771717841.php)处理 |

## 鸣谢

[<img src="https://resources.jetbrains.com/storage/products/company/brand/logos/jb_beam.png" width="200"/>](https://www.jetbrains.com/?from=MiraiEz)

感谢 [JetBrains](https://www.jetbrains.com/?from=MiraiEz) 为本项目提供其产品的许可证。
