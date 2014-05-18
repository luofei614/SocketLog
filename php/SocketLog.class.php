<?php
/**
 * github: https://github.com/luofei614/SocketLog
 * @author luofei614<weibo.com/luofei614>
 */ 
function slog($log,$type='log',$css='')
{
    if(is_string($type))
    {
        $type=preg_replace_callback('/_([a-zA-Z])/',create_function('$matches', 'return strtoupper($matches[1]);'),$type);
        if(method_exists('SocketLog',$type) || in_array($type,SocketLog::$log_types))
        {
           return  call_user_func(array('SocketLog',$type),$log,$css); 
        }
    }

    if(is_object($type) && 'mysqli'==get_class($type))
    {
           return SocketLog::mysqlilog($log,$type);     
    }

    if(is_resource($type) && ('mysql link'==get_resource_type($type) || 'mysql link persistent'==get_resource_type($type)))
    {
           return SocketLog::mysqllog($log,$type);     
    }

    throw new Exception($type.' is not SocketLog method'); 
}

class SocketLog
{
    public static $start_time=0;
    public static $start_memory=0;
    public static $log_types=array('log','info','error','warn','table','group','groupCollapsed','groupEnd','alert');

    protected static $_instance;

    protected static $config=array(
        'host'=>'localhost',
        'port'=>'1229',
        //是否显示利于优化的参数，如果允许时间，消耗内存等
        'optimize'=>false,
        'show_included_files'=>false,
        'error_handler'=>false,
        //日志强制记录到配置的client_id
        'force_client_id'=>'',
        //限制允许读取日志的client_id
        'allow_client_ids'=>array()
    );

    protected static $logs=array();

    protected static $css=array(
        'sql'=>'color:#009bb4;',
        'sql_warn'=>'color:#009bb4;font-size:14px;',
        'error_handler'=>'color:#f4006b;font-size:14px;',
        'page'=>'color:#40e2ff;background:#171717;'
    );

    public static function __callStatic($method,$args)
    {
        if(in_array($method,self::$log_types))
        {
            array_unshift($args,$method);
            return call_user_func_array(array(self::getInstance(),'record'),$args); 
        } 
    }

    public static function big($log)
    {
            self::log($log,'font-size:20px;color:red;');
    }

    public static function trace($msg,$trace_level=2,$css='')
    {
        if(!self::check())
        {
            return ;
        }
        self::groupCollapsed($msg,$css);
        $traces=debug_backtrace(false);
        $traces=array_reverse($traces);
        $max=count($traces)-$trace_level;
        for($i=0;$i<$max;$i++){
            $trace=$traces[$i];
            $fun=isset($trace['class'])?$trace['class'].'::'.$trace['function']:$trace['function'];
            $file=isset($trace['file'])?$trace['file']:'unknown file';
            $line=isset($trace['line'])?$trace['line']:'unknown line';
            $trace_msg='#'.$i.'  '.$fun.' called at ['.$file.':'.$line.']';
            if(!empty($trace['args'])){
                self::groupCollapsed($trace_msg);
                self::log($trace['args']);
                self::groupEnd();
            }else{
                self::log($trace_msg);
            }
        }
        self::groupEnd();
    }


    public static function mysqlilog($sql,$db)
    {
        if(!self::check())
        {
            return ;
        }

        $css=self::$css['sql'];
        if(preg_match('/^SELECT /i', $sql)) 
        {
            //explain
            $query = @mysqli_query($db,"EXPLAIN ".$sql);
            $arr=mysqli_fetch_array($query);
            if(false!==strpos($arr['Extra'],'Using filesort'))
            {
                  $sql.=' <---################[Using filesort]';
                  $css=self::$css['sql_warn'];
            }
            if(false!==strpos($arr['Extra'],'Using temporary'))
            {
                  $sql.=' <---################[Using temporary]';
                  $css=self::$css['sql_warn'];
            }
        } 
        //判断sql语句是否有where
        if(preg_match('/^UPDATE |DELETE /i',$sql) && !preg_match('/WHERE.*(=|>|<|LIKE|IN)/i',$sql))
        {
           $sql.='<---###########[NO WHERE]'; 
           $css=self::$css['sql_warn'];
        }

        self::trace($sql,2,$css);

    }
    public static function mysqllog($sql,$db)
    {
        if(!self::check())
        {
            return ;
        }
        $css=self::$css['sql'];
        if(preg_match('/^SELECT /i', $sql))
        {
            //explain
            $query = @mysql_query("EXPLAIN ".$sql,$db);
            $arr=mysql_fetch_array($query);
            if(false!==strpos($arr['Extra'],'Using filesort'))
            {
                  $sql.=' <---################[Using filesort]';
                  $css=self::$css['sql_warn'];
            }
            if(false!==strpos($arr['Extra'],'Using temporary'))
            {
                  $sql.=' <---################[Using temporary]';
                  $css=self::$css['sql_warn'];
            }
        } 
        //判断sql语句是否有where
        if(preg_match('/^UPDATE |DELETE /i',$sql) && !preg_match('/WHERE.*(=|>|<|LIKE)/i',$sql))
        {
           $sql.='<---###########[NO WHERE]'; 
           $css=self::$css['sql_warn'];
        }
        self::trace($sql,2,$css);
    }

    /**
     * 接管报错
     */ 
    public static function registerErrorHandler()
    {
        if(!self::check())
        {
            return ;
        }
        
        set_error_handler(array('SocketLog','error_handler')); 
    } 

    public static function error_handler($errno, $errstr, $errfile, $errline)
    {
        switch($errno){
            case E_WARNING: $severity = 'E_WARNING'; break;
            case E_NOTICE: $severity = 'E_NOTICE'; break;
            case E_USER_ERROR: $severity = 'E_USER_ERROR'; break;
            case E_USER_WARNING: $severity = 'E_USER_WARNING'; break;
            case E_USER_NOTICE: $severity = 'E_USER_NOTICE'; break;
            case E_STRICT: $severity = 'E_STRICT'; break;
            case E_RECOVERABLE_ERROR: $severity = 'E_RECOVERABLE_ERROR'; break;
            case E_DEPRECATED: $severity = 'E_DEPRECATED'; break;
            case E_USER_DEPRECATED: $severity = 'E_USER_DEPRECATED'; break;
            default: $severity= 'E_UNKNOWN_ERROR_'.$errno; break;
        }
        $msg="{$severity}: {$errstr} in {$errfile} on line {$errline} -- SocketLog error handler";
        self::trace($msg,2,self::$css['error_handler']);
    }


    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
   
    protected static function _log($type,$logs,$css='')
    {
        self::getInstance()->record($type,$logs,$css);
    }


    protected static function check()
    {
        $tabid=self::getClientArg('tabid');
         //是否记录日志的检查
        if(!$tabid && !self::getConfig('force_client_id'))
        {
            return false; 
        }
        //用户认证
        $allow_client_ids=self::getConfig('allow_client_ids');
        if(!empty($allow_client_ids))
        {
            if (!$tabid && in_array(self::getConfig('force_client_id'), $allow_client_ids)) {
                return true;
            }
            
            $client_id=self::getClientArg('client_id');
            if(!in_array($client_id,$allow_client_ids))
            {
                return false;
            }
        }
        return true;
    }

    protected static function getClientArg($name)
    {
        static $args=array();

        $key = 'HTTP_USER_AGENT';

        if (isset($_SERVER['HTTP_SOCKETLOG'])) {
            $key = 'HTTP_SOCKETLOG';
        }

        if(!isset($_SERVER[$key]))
        {
            return null; 
        }
        if(empty($args))
        {
            if(!preg_match('/SocketLog\((.*?)\)/',$_SERVER[$key],$match))
            {
                $args=array('tabid'=>null);
                return null; 
            }
            parse_str($match[1],$args);
        }
        if(isset($args[$name]))
        {
            return $args[$name]; 
        }
        return null;
    }


    //设置配置
    public static function  setConfig($config)
    {
        $config=array_merge(self::$config,$config); 
        self::$config=$config;
        if(self::check())
        {
            self::getInstance(); //强制初始化SocketLog实例
            if($config['optimize'])
            {
                self::$start_time=microtime(true); 
                self::$start_memory=memory_get_usage(); 
            }

            if($config['error_handler'])
            {
                self::registerErrorHandler(); 
            }
        }
    }


    //获得配置
    public static function  getConfig($name)
    {
        if(isset(self::$config[$name]))
            return self::$config[$name];
        return null;
    }

    //记录日志
    public function record($type,$msg='',$css='')
    {
        if(!self::check())
        {
            return ;
        }

        self::$logs[]=array(
            'type'=>$type,
            'msg'=>$msg,
            'css'=>$css
        );
    }



    public function __destruct()
    {
        $time_str='';
        $memory_str='';
        if(self::$start_time)
        {
            $runtime=microtime(true)-self::$start_time; 
            $reqs=number_format(1/$runtime,2);
            $time_str="[运行时间：{$runtime}s][吞吐率：{$reqs}req/s]";
        }
        if(self::$start_memory)
        {
            $memory_use=number_format(self::$start_memory-memory_get_usage()/1024,2);
            $memory_str="[内存消耗：{$memory_use}kb]"; 
        }

        if(isset($_SERVER['HTTP_HOST']))
        {
            $current_uri=$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        }
        else
        {
            $current_uri="cmd:".implode(' ',$_SERVER['argv']);
        }
        array_unshift(self::$logs,array(
                'type'=>'group',
                'msg'=>$current_uri.$time_str.$memory_str,
                'css'=>self::$css['page']
        ));

        if(self::getConfig('show_included_files'))
        {
            self::$logs[]=array(
                    'type'=>'groupCollapsed',
                    'msg'=>'included_files',
                    'css'=>''
            );
            self::$logs[]=array(
                    'type'=>'log',
                    'msg'=>implode("\n",get_included_files()),
                    'css'=>''
            );
            self::$logs[]=array(
                    'type'=>'groupEnd',
                    'msg'=>'',
                    'css'=>'',
            );
        }

        self::$logs[]=array(
                'type'=>'groupEnd',
                'msg'=>'',
                'css'=>'',
        );

        $tabid=self::getClientArg('tabid');
        $client_id=self::getClientArg('client_id');
        if(!$client_id)
        {
            $client_id=''; 
        }
        $logs=array(
            'tabid'=>$tabid,
            'client_id'=>$client_id,
            'logs'=>self::$logs,
            'force_client_id'=>self::getConfig('force_client_id'),
        );
        //发送日志
        $header = "GET /echo HTTP/1.1\r\n";
        $header.= "Upgrade: WebSocket\r\n";
        $header.= "Connection: Upgrade\r\n";
        $header.= "Host: ".self::getConfig('host').":".self::getConfig('port')."\r\n";
        $header.= "Origin: http://foobar.com\r\n";
        $header.= "Sec-WebSocket-Key: 4 @1  46546xW%0l 1 5\r\n";
        $header.= "Sec-WebSocket-Key1: 4 @1  46546xW%0l 1 5\r\n";
        $header.= "Sec-WebSocket-Key2: 12998 5 Y3 1  .P00\r\n";
        $header.= "\r\n";
        $header.= '^n:ds[4U';
        $socket = fsockopen(self::getConfig('host'), self::getConfig('port'), $errno, $errstr, 2); 
        if(fwrite($socket, $header))
        {
            $response = fread($socket, 2000);
            $msg=@json_encode($logs);
            fwrite($socket, "\x00[socket_log_start]" . $msg . "[socket_log_end]\xff" ); 
        }
     }

}
