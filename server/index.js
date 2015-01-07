var ws = require("nodejs-websocket");
var http = require('http');
var server = ws.createServer(function (conn) {
    console.log("New connection");
    conn.on("text", function (str) {
        //老版本的发送日志的方法。 新版可以去掉
        console.log(str);
        broadcast(str,conn.path);
    });
    conn.on("close", function (code, reason) {
        console.log("Connection closed");
    });
    conn.on("error", function (err) {
        console.log(err);
    });
}).listen(1229);

//广播消息
function broadcast(msg,path) {
    server.connections.forEach(function (conn) {
        //通过path判断，将日志发给指定的client_id
        if(conn.path==path)
        {
            console.log('##send message##');
            conn.sendText(msg);
        }
    });
}

var httpServer = http.createServer(function(request, response){
     if('POST'==request.method)
     {
        var requestBody = '';
        request.on('data', function(data) {
            requestBody+=data;       
        });
        request.on('end',function(){
            console.log(requestBody);
            //发送日志
            broadcast(requestBody,request.url);
            response.end('sucess');
        });
     }
     else
     {
         response.end('please request with POST method');
     }
});
httpServer.listen(1116);
