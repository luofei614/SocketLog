<?php

/**
 * github: https://github.com/luofei614/SocketLog
 * @author luofei614<weibo.com/luofei614>
 */

namespace think\slog;

class Slog
{
    public static $start_time   = 0;
    public static $start_memory = 0;
    public static $port         = 1116; // SocketLog 服务的 http 的端口号
    public static $log_types    = ['log', 'info', 'error', 'warn', 'table', 'group', 'groupCollapsed', 'groupEnd', 'alert'];

    protected static $_allowForceClientIds = [];    // 配置强制推送且被授权的client_id

    protected static $_instance;

    protected static $config = [
        'enable'              => true,      // 是否记录日志的开关
        'host'                => 'localhost',
        'optimize'            => false,     // 是否显示利于优化的参数，如果允许时间，消耗内存等
        'show_included_files' => false,
        'error_handler'       => false,
        'force_client_ids'    => [],   // 日志强制记录到配置的 client_id
        'allow_client_ids'    => []    // 限制允许读取日志的client_id
    ];

    protected static $logs = [];

    protected static $css = [
        'sql'           => 'color:#009bb4;',
        'sql_warn'      => 'color:#009bb4;font-size:14px;',
        'error_handler' => 'color:#f4006b;font-size:14px;',
        'page'          => 'color:#40e2ff;background:#171717;'
    ];

    /**
     * [__callStatic description]
     * @param  [type] $method [description]
     * @param  [type] $args   [description]
     * @return [type]         [description]
     */
    public static function __callStatic($method, $args)
    {
        if (in_array($method, self::$log_types)) {
            array_unshift($args, $method);
            $ret = call_user_func_array([self::getInstance(), 'record'], $args);
            // 立即发送日志
            self::sendLog();
            self::$logs = [];

            return $ret;
        }
    }

    /**
     * 显示 SQL 语句调试信息
     * @param  [type] $sql  [description]
     * @param  [type] $link [description]
     * @return [type]       [description]
     */
    public static function sql($sql, $link)
    {
        // 使用 mysqli 方式连接 DB
        if (is_object($link) && 'mysqli' == get_class($link)) {
            return self::mysqliLog($sql, $link);
        }

        // 使用 mysql 方式连接 DB
        if (is_resource($link) && ('mysql link' == get_resource_type($link) || 'mysql link persistent' == get_resource_type($link))) {
            return self::mysqlLog($sql, $link);
        }

        // 使用 PDO 方式连接 DB
        if (is_object($link) && 'PDO' == get_class($link)) {
            return self::pdoLog($sql, $link);
        }

        throw new Exception('SocketLog can not support this database link');
    }

    /**
     * 增大 log 日志的字体，颜色设置为 red
     * @param  [type] $log [description]
     * @return [type]      [description]
     */
    public static function big($log)
    {
        self::log($log, 'font-size:20px;color:red;');
    }

    public static function trace($msg, $trace_level = 1, $css = '')
    {
        if (!self::check()) {
            return;
        }
        self::groupCollapsed($msg, $css);

        $traces      = debug_backtrace(false);
        $traces      = array_reverse($traces);
        $trace_level = ($trace_level == '') ? 0 : intval($trace_level);
        $max         = count($traces) - $trace_level;

        for ($i = 0; $i < $max; $i++) {
            $trace     = $traces[$i];
            $fun       = isset($trace['class']) ? $trace['class'] . '::' . $trace['function'] : $trace['function'];
            $file      = isset($trace['file']) ? $trace['file'] : 'unknown file';
            $line      = isset($trace['line']) ? $trace['line'] : 'unknown line';
            $trace_msg = '#' . $i . '  ' . $fun . ' called at [' . $file . ':' . $line . ']';
            //不输出参数速度会有明显的改善
            //if(!empty($trace['args'])){
            //    self::groupCollapsed($trace_msg);
            //    self::log($trace['args']);
            //    self::groupEnd();
            //}else{
            self::log($trace_msg);
            //}
        }
        self::groupEnd();
    }

    /**
     * 使用 mysqli 对象的 Log
     * @param  [type] $sql [description]
     * @param  [type] $db  [description]
     * @return [type]      [description]
     */
    public static function mysqliLog($sql, $db)
    {
        if (!self::check()) {
            return;
        }

        $css = self::$css['sql'];
        if (preg_match('/^SELECT /i', $sql)) {
            // 对传入的查询语句执行 explain
            $query = @mysqli_query($db, "EXPLAIN " . $sql);
            $arr   = mysqli_fetch_array($query);
            self::sqlExplain($arr, $sql, $css);
        }
        self::sqlWhere($sql, $css);
        self::trace($sql, 2, $css);
    }

    /**
     * 使用 mysql 对象的 Log
     * @param  [type] $sql [description]
     * @param  [type] $db  [description]
     * @return [type]      [description]
     */
    public static function mysqlLog($sql, $db)
    {
        if (!self::check()) {
            return;
        }
        $css = self::$css['sql'];
        if (preg_match('/^SELECT /i', $sql)) {
            // 对传入的查询语句执行 explain
            $query = @mysql_query("EXPLAIN " . $sql, $db);
            $arr   = mysql_fetch_array($query);
            self::sqlExplain($arr, $sql, $css);
        }
        //判断sql语句是否有where
        self::sqlWhere($sql, $css);
        self::trace($sql, 2, $css);
    }

    /**
     * 使用 PDO 对象的 Log
     * @param  [type] $sql [description]
     * @param  [type] $db  [description]
     * @return [type]      [description]
     */
    public static function pdoLog($sql, $pdo)
    {
        if (!self::check()) {
            return;
        }
        $css = self::$css['sql'];
        if (preg_match('/^SELECT /i', $sql)) {
            //explain
            try {
                $obj = $pdo->query("EXPLAIN " . $sql);
                if (is_object($obj) && method_exists($obj, 'fetch')) {
                    $arr = $obj->fetch(\PDO::FETCH_ASSOC);
                    self::sqlExplain($arr, $sql, $css);
                }
            } catch (Exception $e) {
            }
        }
        self::sqlWhere($sql, $css);
        self::trace($sql, 2, $css);
    }

    /**
     * 对 SQL 语句执行 Explain 解析
     * @param  [type] $arr  [description]
     * @param  [type] &$sql [description]
     * @param  [type] &$css [description]
     * @return [type]       [description]
     */
    private static function sqlExplain($arr, &$sql, &$css)
    {
        $arr = array_change_key_case($arr, CASE_LOWER);
        if (false !== strpos($arr['extra'], 'Using filesort')) {
            $sql .= ' <---################[Using filesort]';
            $css = self::$css['sql_warn'];
        }
        if (false !== strpos($arr['extra'], 'Using temporary')) {
            $sql .= ' <---################[Using temporary]';
            $css = self::$css['sql_warn'];
        }
    }

    /**
     * 检查 SQL 语句是否还有 Where 条件，如果不含有则显示 warn 信息
     * @param  [type] &$sql [description]
     * @param  [type] &$css [description]
     * @return [type]       [description]
     */
    private static function sqlWhere(&$sql, &$css)
    {
        // 判断 SQL 语句是否有 where 条件
        if (preg_match('/^UPDATE | DELETE /i', $sql) && !preg_match('/WHERE.*(=|>|<|LIKE|IN)/i', $sql)) {
            $sql .= '<---###########[NO WHERE]';
            $css = self::$css['sql_warn'];
        }
    }

    /**
     * 接管报错
     */
    public static function registerErrorHandler()
    {
        if (!self::check()) {
            return;
        }
        // 自定义的错误处理
        set_error_handler([__CLASS__, 'errorHandler']);
        register_shutdown_function([__CLASS__, 'fatalError']);
    }

    /**
     * 设置错误处理函数
     * @param  [type] $errno   [description]
     * @param  [type] $errstr  [description]
     * @param  [type] $errfile [description]
     * @param  [type] $errline [description]
     * @return [type]          [description]
     */
    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        switch ($errno) {
            case E_WARNING:
                $severity = 'E_WARNING';
                break;
            case E_NOTICE:
                $severity = 'E_NOTICE';
                break;
            case E_USER_ERROR:
                $severity = 'E_USER_ERROR';
                break;
            case E_USER_WARNING:
                $severity = 'E_USER_WARNING';
                break;
            case E_USER_NOTICE:
                $severity = 'E_USER_NOTICE';
                break;
            case E_STRICT:
                $severity = 'E_STRICT';
                break;
            case E_RECOVERABLE_ERROR:
                $severity = 'E_RECOVERABLE_ERROR';
                break;
            case E_DEPRECATED:
                $severity = 'E_DEPRECATED';
                break;
            case E_USER_DEPRECATED:
                $severity = 'E_USER_DEPRECATED';
                break;
            case E_ERROR:
                $severity = 'E_ERR';
                break;
            case E_PARSE:
                $severity = 'E_PARSE';
                break;
            case E_CORE_ERROR:
                $severity = 'E_CORE_ERROR';
                break;
            case E_COMPILE_ERROR:
                $severity = 'E_COMPILE_ERROR';
                break;
            case E_USER_ERROR:
                $severity = 'E_USER_ERROR';
                break;
            default:
                $severity = 'E_UNKNOWN_ERROR_' . $errno;
                break;
        }
        $msg = "{$severity}: {$errstr} in {$errfile} on line {$errline} -- SocketLog error handler";
        self::trace($msg, 2, self::$css['error_handler']);
    }

    /**
     * Fatal Error 函数
     * @return [type] [description]
     */
    public static function fatalError()
    {
        // 保存日志记录
        if ($e = error_get_last()) {
            self::errorHandler($e['type'], $e['message'], $e['file'], $e['line']);
            self::sendLog(); // 此类终止不会调用类的 __destruct 方法，所以此处手动 sendLog
        }
    }

    /**
     * 获取实例
     * @return [type] [description]
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 执行检查
     * @return [type] [description]
     */
    protected static function check()
    {
        if (!self::getConfig('enable')) {
            return false;
        }

        $tabid = self::getClientArg('tabid');
        // 是否记录日志的检查
        if (!$tabid && !self::getConfig('force_client_ids')) {
            return false;
        }

        // 用户认证
        $allow_client_ids = self::getConfig('allow_client_ids');
        if (!empty($allow_client_ids)) {
            // 通过数组交集运算，得出授权强制推送的 client_id
            self::$_allowForceClientIds = array_intersect($allow_client_ids, self::getConfig('force_client_ids'));

            if (!$tabid && count(self::$_allowForceClientIds)) {
                return true;
            }

            $client_id = self::getClientArg('client_id');
            if (!in_array($client_id, $allow_client_ids)) {
                return false;
            }
        } else {
            self::$_allowForceClientIds = self::getConfig('force_client_ids');
        }

        return true;
    }

    /**
     * 获取客户端参数
     * @param  [type] $name [description]
     * @return [type]       [description]
     */
    protected static function getClientArg($name)
    {
        static $args = [];

        $key = 'HTTP_USER_AGENT';

        if (isset($_SERVER['HTTP_SOCKETLOG'])) {
            $key = 'HTTP_SOCKETLOG';
        }

        if (!isset($_SERVER[$key])) {
            return null;
        }

        if (empty($args)) {
            if (!preg_match('/SocketLog\((.*?)\)/', $_SERVER[$key], $match)) {
                $args = ['tabid' => null];
                return null;
            }
            parse_str($match[1], $args);
        }

        if (isset($args[$name])) {
            return $args[$name];
        }

        return null;
    }

    /**
     * 按需设置项目的调试配置项
     * @param  [type] $config [description]
     * @return [type]         [description]
     */
    public static function config($config)
    {
        $config = array_merge(self::$config, $config);

        self::$config = $config;

        if (self::check()) {
            self::getInstance(); // 强制初始化 SocketLog 实例

            if ($config['optimize']) {
                self::$start_time   = microtime(true);
                self::$start_memory = memory_get_usage();
            }

            if ($config['error_handler']) {
                self::registerErrorHandler();
            }
        }
    }

    /**
     * 获取配置项
     * @param  [type] $name [description]
     * @return [type]       [description]
     */
    public static function getConfig($name)
    {
        if (isset(self::$config[$name])) {
            return self::$config[$name];
        }
        return null;
    }

    /**
     * 记录日志
     * @param  [type] $type [description]
     * @param  string $msg  [description]
     * @param  string $css  [description]
     * @return [type]       [description]
     */
    public function record($type, $msg = '', $css = '')
    {
        if (!self::check()) {
            return;
        }

        self::$logs[] = [
            'type' => $type,
            'msg'  => $msg,
            'css'  => $css
        ];
    }

    /**
     * 调试后台 API 时，发送信息
     * @param null $host - $host of socket server
     * @param string $message - 发送的消息
     * @param string $address - 地址
     * @return bool
     */
    public static function send($host, $message = '', $address = '/')
    {
        $url = 'http://' . $host . ':' . self::$port . $address;
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $headers = ["Content-Type: application/json;charset=UTF-8"];

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); // 设置 header
        $txt = curl_exec($ch);

        return true;
    }

    /**
     * 发送 Log
     * @return [type] [description]
     */
    public static function sendLog()
    {
        if (!self::check()) {
            return;
        }

        $time_str   = '';
        $memory_str = '';
        if (self::$start_time) {
            $runtime  = microtime(true) - self::$start_time;
            $reqs     = number_format(1 / $runtime, 2);
            $time_str = "[运行时间：{$runtime}s][吞吐率：{$reqs}req/s]";
        }
        if (self::$start_memory) {
            $memory_use = number_format((memory_get_usage() - self::$start_memory) / 1024, 2);
            $memory_str = "[内存消耗：{$memory_use}kb]";
        }

        if (isset($_SERVER['HTTP_HOST'])) {
            $current_uri = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        } else {
            $current_uri = "cmd:" . implode(' ', $_SERVER['argv']);
        }

        array_unshift(self::$logs, [
            'type' => 'group',
            'msg'  => $current_uri . $time_str . $memory_str,
            'css'  => self::$css['page']
        ]);

        if (self::getConfig('show_included_files')) {
            self::$logs[] = [
                'type' => 'groupCollapsed',
                'msg'  => 'included_files',
                'css'  => ''
            ];
            self::$logs[] = [
                'type' => 'log',
                'msg'  => implode("\n", get_included_files()),
                'css'  => ''
            ];
            self::$logs[] = [
                'type' => 'groupEnd',
                'msg'  => '',
                'css'  => '',
            ];
        }

        self::$logs[] = [
            'type' => 'groupEnd',
            'msg'  => '',
            'css'  => '',
        ];

        $tabid = self::getClientArg('tabid');
        if (!$client_id = self::getClientArg('client_id')) {
            $client_id = '';
        }

        if (!empty(self::$_allowForceClientIds)) {
            // 强制推送到多个 client_id
            foreach (self::$_allowForceClientIds as $force_client_id) {
                $client_id = $force_client_id;
                self::sendToClient($tabid, $client_id, self::$logs, $force_client_id);
            }
        } else {
            self::sendToClient($tabid, $client_id, self::$logs, '');
        }
    }

    /**
     * 发送给指定客户端
     * @author Zjmainstay
     * @param $tabid
     * @param $client_id
     * @param $logs
     * @param $force_client_id
     */
    protected static function sendToClient($tabid, $client_id, $logs, $force_client_id)
    {
        $logs = [
            'tabid'           => $tabid,
            'client_id'       => $client_id,
            'logs'            => $logs,
            'force_client_id' => $force_client_id,
        ];
        $msg     = @json_encode($logs);
        $address = '/' . $client_id; // 将 client_id 作为地址，server 端通过地址判断将日志发布给谁
        self::send(self::getConfig('host'), $msg, $address);
    }

    public function __destruct()
    {
        // self::sendLog();
    }
}
