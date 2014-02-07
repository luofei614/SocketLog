<?php
/**
 * SocketLog ，WebSocket服务端，运行： php  SocketLog.server.php 
 * @author luofei614<weibo.com/luofei614>
 */
$host = 'localhost'; //host
$port = '1229'; //port
$null=NULL;

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($socket, 0, $port);
socket_listen($socket);
$clients = array($socket);

while (true) 
{
	$changed = $clients;
	socket_select($changed, $null, $null, 0, 10);
	
    //检查新连接
    if (in_array($socket, $changed)) 
    {
		$socket_new = socket_accept($socket); 
		$clients[] = $socket_new; 
		
		$header = socket_read($socket_new, 1024); 
        //握手
		perform_handshaking($header, $socket_new, $host, $port);
		$found_socket = array_search($socket, $changed);
		unset($changed[$found_socket]);
	}
	
    foreach ($changed as $changed_socket) 
    {	
       $read='';
       while(socket_recv($changed_socket, $buf, 1024,0)>0)
       {
                $read.=$buf;
       } 
       if(!empty($read) &&  preg_match('/\[socket_log_start\]([\s\S]*)\[socket_log_end\]/',$read,$match))
       {
            $read=$match[1];
            echo '#######get the log:'.PHP_EOL.$read.PHP_EOL.PHP_EOL;
            send_message(mask($read));
       }
			
		$buf = @socket_read($changed_socket, 1024, PHP_NORMAL_READ);
		if ($buf === false) {
            //断开连接
			$found_socket = array_search($changed_socket, $clients);
			unset($clients[$found_socket]);
		}
	}
}
socket_close($sock);

function send_message($msg)
{
	global $clients;
	foreach($clients as $changed_socket)
	{
		@socket_write($changed_socket,$msg,strlen($msg));
	}
	return true;
}


//Unmask incoming framed message
function unmask($text) {
	$length = ord($text[1]) & 127;
	if($length == 126) {
		$masks = substr($text, 4, 4);
		$data = substr($text, 8);
	}
	elseif($length == 127) {
		$masks = substr($text, 10, 4);
		$data = substr($text, 14);
	}
	else {
		$masks = substr($text, 2, 4);
		$data = substr($text, 6);
	}
	$text = "";
	for ($i = 0; $i < strlen($data); ++$i) {
		$text .= $data[$i] ^ $masks[$i%4];
	}
	return $text;
}

function mask($text)
{
    $b = 129; // FIN + text frame
    $len = strlen($text);
    if ($len < 126) {
        return pack('CC', $b, $len) . $text;
    } elseif ($len < 65536) {
        return pack('CCn', $b, 126, $len) . $text;
    } else {
        return pack('CCNN', $b, 127, 0, $len) . $text;
    }
}

function perform_handshaking($receved_header,$client_conn, $host, $port)
{
	$headers = array();
	$lines = preg_split("/\r\n/", $receved_header);
	foreach($lines as $line)
	{
		$line = chop($line);
		if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
		{
			$headers[$matches[1]] = $matches[2];
		}
	}

	$secKey = $headers['Sec-WebSocket-Key'];
	$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
	//hand shaking header
	$upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
	"Upgrade: websocket\r\n" .
	"Connection: Upgrade\r\n" .
	"WebSocket-Origin: $host\r\n" .
	"WebSocket-Location: ws://$host:$port/demo/shout.php\r\n".
	"Sec-WebSocket-Accept:$secAccept\r\n\r\n";
	socket_write($client_conn,$upgrade,strlen($upgrade));
}
