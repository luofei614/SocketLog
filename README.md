#说明
 * SocketLog方便API，AJAX的调试，能将日志通过WebSocket输出到Chrome浏览器的console中
 * 它能代替ChromePHP、FirePHP等工具，ChromePHP等是通过header通信，适合AJAX调试，但不适合API调试，而且它们是通过Header通信，Chrome浏览器对传递Header大小有显示，日志如果多了，Chrome浏览器就无法支持。
 * 目录结构：
 * chrome 目录是 chrome插件，目前此插件还没有上架到chrome的商店，大家可以先以开发模式载入这个文件夹。
 * php 目录是php相关脚本。   SocketLog.server.php 是一个 Websocket服务器，  SocketLog.class.php是发生日志的类库,我们在发生日志的时候，需要载入这个类库然后调用函数slog即可。
 * 效果展示： 我们在浏览网站的时候在浏览器console中就知道程序做了什么，这对于二次开发产品十分有用。 下面效果图在console中打印出浏览discuz程序时，执行了哪些sql语句， 以及执行sql语句的调用栈。程序的warning，notice等错误信息也可以打到console中。
![enter image description here][1]
#使用方法
 * 首先，请在chrome浏览器上安装好插件。
 * 然后，启用Websocket服务器，  在命令行中运行 php php/SocketLog.server.php , 将会在本地起一个websocket服务 ，监听端口是1229
 * 在自己的程序中发送日志：

        <?php
        include './php/SocketLog.class.php';
        slog('hello world');
        ?>


 * 用slog函数发送日志， 支持多种日志类型：

        slog('msg','log');  //一般日志
        slog('msg','error'); //错误日志
        slog('msg','info'); //信息日志
        slog('msg','warn'); //警告日志
        slog('msg','trace');// 输入日志同时会打出调用栈
        slog('msg','alert');//将日志以alert方式弹出
        slog('msg','log','color:red;font-size:20px;');//自定义日志的样式，第三个参数为css样式

 * 通过上面例子可以看出， slog函数支持三个参数：
 * 第一个参数是日志内容，日志内容不光能支持字符串哟，大家如果传递数组一样可以打印到console中。
 * 第二个参数是日志类型，可选，如果没有指定日志类型默认类型为log， 第三个参数是自定样式，在这里写上你自定义css样式即可。

##配置
* 在载入SocketLog.class.php文件后，还可以对SocketLog进行一些配置。
* 例如：我们如果想将程序的报错信息页输出到console，可以配置

        <?php
        include './php/SocketLog.class.php';
        slog(array(
        'error_handler'=>true
        ),'set_config');
        echo notice;//制造一个notice报错
        slog('这里是输出的一般日志');
        ?>
* 配置SocketLog也是用slog函数， 第一个参数传递配置项的数组，第二个参数设置为set_config
* 还支持其他配置项

        <?php
        include './php/SocketLog.class.php';
        slog(array(
        'host'=>'localhost',//websocket服务器地址，默认localhost
        'port'=>'1229',//websocket服务器端口，默认端口是1229
        'optimize'=>false,//是否显示利于优化的参数，如果运行时间，消耗内存等，默认为false
        'show_included_files'=>false,//是否显示本次程序运行加载了哪些文件，默认为false
        'error_handler'=>false,//是否接管程序错误，将程序错误显示在console中，默认为false
        'force_client_id'=>'',//日志强制记录到配置的client_id,默认为空
        'allow_client_ids'=>array()////限制允许读取日志的client_id，默认为空
        );
        ,'set_config');
        ?>
* optimize 参数如果设置为true， 可以在日志中看见利于优化参数，如：[运行时间：0.081346035003662s][吞吐率：12.29req/s][内存消耗：346,910.45kb] 
* 设置client_id


##对数据库进行调试
  TODO
##对API进行调试
  TODO
##对命令行脚本进行调试
  TODO


  [1]: http://sinaclouds-themepic.stor.sinaapp.com/socketlog.png
