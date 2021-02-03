<?php
include './php/slog.function.php';

echo "string";
// 配置
slog([
    'host'                => 'localhost',  // Websocket 服务器地址，默认 localhost
    'optimize'            => false,        // 是否显示有利于程序优化的信息，如运行时间、吞吐率、消耗内存等，默认为 false
    'show_included_files' => false,        // 是否显示本次程序运行加载了哪些文件，默认为 false
    'error_handler'       => false,        // 是否接管程序错误，将程序错误显示在 Console 中，默认为 false
    'allow_client_ids'    => [             // 限制允许读取日志的 client_id，默认为空，表示所有人都可以获得日志。
        //'client_01',
        //'client_02',
        //'client_03',
    ],
    'force_client_ids'    => [             // 日志强制记录到配置的 client_id，默认为空，client_id 必须在 allow_client_ids 中
        //'client_01',
        //'client_02',
    ]
], 'config');

// 输出日志
slog('hello world');     // 一般日志
// slog('msg', 'log');       // 一般日志
// slog('msg', 'error');     // 错误日志
// slog('msg', 'info');      // 信息日志
// slog('msg', 'warn');      // 警告日志
// slog('msg', 'trace');     // 输入日志，同时会打出调用栈
// slog('msg', 'alert');     // 将日志以alert方式弹出
slog('msg', 'log', 'color:red;font-size:20px;');  // 自定义日志的样式，第三个参数为css样式

// 调试sql
/*
$link = mysql_connect( 'localhost:3306', 'root', '123456', true) ;
mysql_select_db('kuaijianli', $link);
$sql = "DELETE * FROM `uxxxser`";
slog($sql, $link);
*/
