
#说明
* readme: https://github.com/luofei614/SocketLog

* composer require "jason-gao/socketlog:v2.2.2"

* 2种方法使用slog

```

require __ROOT__.'/vendor/autoload.php';

\SocketLog\Slog::config(array(
	'host'=>'192.168.5.188',//websocket服务器地址，默认localhost
	'optimize'=>true,//是否显示利于优化的参数，如果运行时间，消耗内存等，默认为false
	'show_included_files'=>true,//是否显示本次程序运行加载了哪些文件，默认为false
	'error_handler'=>true,//是否接管程序错误，将程序错误显示在console中，默认为false
	'force_client_id'=>'',//日志强制记录到配置的client_id,默认为空
	'allow_client_ids'=>array(123)//限制允许读取日志的client_id，默认为空,表示所有人都可以获得日志。
));

 slog(array(
	'host'=>'192.168.5.188',//websocket服务器地址，默认localhost
	'optimize'=>true,//是否显示利于优化的参数，如果运行时间，消耗内存等，默认为false
	'show_included_files'=>true,//是否显示本次程序运行加载了哪些文件，默认为false
	'error_handler'=>true,//是否接管程序错误，将程序错误显示在console中，默认为false
	'force_client_id'=>'',//日志强制记录到配置的client_id,默认为空
	'allow_client_ids'=>array(123)//限制允许读取日志的client_id，默认为空,表示所有人都可以获得日志。
	),'config');
	
	
```

* 启动服务端的node index.js服务
* 安装chrome插件配置ws连接到node服务

* 可以开始进行调试了

* 20180212-添加错误级别控制
    * 默认 define('__ERROR_HANDLE_LEVEL_SOCKETLOG__', E_ALL);
    * 项目入口文件定义错误级别define('__ERROR_HANDLE_LEVEL_SOCKETLOG__', E_ALL ^ E_WARNING ^E_NOTICE);

