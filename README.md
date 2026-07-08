## DedeX

DedeX系统基于PHP7开发，为往圣继绝学，遵循GPLv2协议开源。模板继承原有标签体系，具备简洁易用、灵活扩展、安全稳定、性能高效等特点。

## 版本说明

DedeX是一个LTS版本，支持将到2035年11月截止，目前DedeX已经发布，可以[点击下载](https://codeload.github.com/xushubieli/DedeX/zip/refs/heads/main)获取。

![DedeX](docs/dedex.png)

## 参与开源

访问[代码托管](https://github.com/xushubieli/DedeX.git)，可以通过提交Pull requests的方式来贡献您的力量。

## v2.0 Roadmap

我们会收集、整理新的功能需求制定新的Roadmap，在这里，可以查看版本[更新记录](docs/changelog.md)

## 平台需求

1、Windows

IIS/Apache/Nginx + PHP7/PHP8 + MySQL5/8/10

2、Linux/Unix

Apache/Nginx + PHP7/PHP8 + MySQL5/8/10（PHP必须在非安全模式下运行），建议使用平台：Linux + Apache2.4 + PHP8.4 + MySQL8.0

3、PHP必须环境或启用的系统函数

Fileinfo：文件上传安全校验

GD扩展库：图像验证码、水印、二维码生成

MySQL扩展库：数据存储

4、基本目录结构及文件

```
./docs              更新记录
./src               系统源代码
..|_/a              默认网页文件存放目录[必须可写入]
..|_/admin          默认后台管理目录[可任意改名]
..|_/apps           插件扩展程序目录[不可写入，可执行]
..|_/data           系统缓存或其它可写入数据存放目录[必须可写入，但不可执行，建议关闭对外访问权限]
..|_/install        程序安装目录，安装完后可删除[安装时必须有可写入权限]
..|_/static         静态资源存放目录[必须可写入，无需执行]
..|_/system         类库文件目录[建议关闭对外访问权限]
..|_/theme          系统默认模板目录[建议关闭对外访问权限]
..|_/user           会员目录
..|_/index.php      入口文件
..|_/license.txt    GPLv2开源许可协议
./tools             系统工具
..|_/resetpwd.php   管理员密码修改工具（如需重置，放至站点根目录，用完删除）
```

5、PHP环境容易碰到的不兼容性问题

  * data目录没写入权限，导致系统session无法使用，这将导致无法登录管理后台（直接表现为验证码不能正常显示）；

  * 出现上传功能无法使用，原因是上传的文件夹权限问题或没安装扩展；
  
  * 出现安装时显示空白，原因是服务器环境配置尚未达到平台需求；

## 程序安装使用

1、下载程序解压到本地目录；

2、上传程序目录中的`/src`到网站根目录；

3、运行`http://www.yourwebsite.com/install/index.php`（yourwebsite.com）表示您的站点，按照安装说明进行程序安装；

## 版权信息

详细参考：[DedeX站点授权协议](LICENSE)

DedeX秉承开源初心，致力于打造纯粹开源环境。遵循GPLv2协议全面开放源码，支持自由使用和修改分发。

## 相关资源

- [代码托管](https://github.com/xushubieli/DedeX)

- 邮箱：xushubieli@qq.com