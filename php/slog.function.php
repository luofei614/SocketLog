<?php
require 'slog.class.php';

use think\slog\Slog;

function slog($log, $type = 'log', $css = '')
{
    if (is_string($type)) {
        $type = preg_replace_callback('/_([a-zA-Z])/', function ($matches) {
            return strtoupper($matches[1]);
        }, $type);

        if (method_exists('\think\slog\Slog', $type) || in_array($type, Slog::$log_types)) {
            return  call_user_func(['\think\slog\Slog', $type], $log, $css);
        }
    }

    if (is_object($type) && 'mysqli' == get_class($type)) {
        return Slog::mysqliLog($log, $type);
    }

    if (is_resource($type) && ('mysql link' == get_resource_type($type) || 'mysql link persistent' == get_resource_type($type))) {
        return Slog::mysqlLog($log, $type);
    }


    if (is_object($type) && 'PDO' == get_class($type)) {
        return Slog::pdoLog($log, $type);
    }

    throw new Exception($type . ' is not SocketLog method');
}
