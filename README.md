#说明
 SocketLog方便API，AJAX的调试，能将日志通过WebSocket输出到Chrome浏览器的console中
 它能代替ChromePHP、FirePHP等工具，ChromePHP等是通过header通信，适合AJAX调试，但不适合API调试，而且它们是通过Header通信，Chrome浏览器对传递Header大小有显示，日志如果多了，Chrome浏览器就无法支持。
 目录结构：
 chrome 目录是 chrome插件，目前此插件还没有上架到chrome的商店，大家可以先以开发模式载入这个文件夹。
 php 目录是php相关脚本。   SocketLog.server.php 是一个 Websocket服务器，  SocketLog.class.php是发生日志的类库。
#使用方法
 首先，请在chrome浏览器上安装好插件。
 然后，启用Websocket服务器，  在命令行中运行 php php/SocketLog.server.php , 将会在本地起一个websocket服务 ，监听端口是1229
 在自己的程序中发送日志：
<?php
include './php/SocketLog.class.php';
slog('hello world');
?>

用slog函数发送日志， 支持多种日志类型：

slog('msg','log');  //一般日志
slog('msg','error'); //错误日志
slog('msg','info'); //信息日志
slog('msg','warn'); //警告日志
slog('msg','trace');// 输入日志同时会打出调用栈
slog('msg','log','color:red;font-size:20px;');// 自定义日志的样式，第三个参数为css样式

##配置
  TODO
##对数据库进行调试
  TODO
##对API进行调试
  TODO
##对命令行脚本进行调试
  TODO
