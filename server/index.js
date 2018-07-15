var ws = require("nodejs-websocket");
var http = require('http');
var fs = require('fs');
var stripJsonComments = require('strip-json-comments');
function loadJSONFile (file) {
      var json = fs.readFileSync(file).toString();
        return JSON.parse(stripJsonComments(json));
}
var server = ws.createServer(function (conn) {
    var config=loadJSONFile(__dirname+'/config.json');
    if(config.client_verify){
        //验证client_id
        var client_id=conn.path.substring(1);
        if(-1 === config.client_ids.indexOf(client_id)){
            conn.sendText('close:client_id不允许连接');
            conn.close();
        }
    }
    console.log("New connection");
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



//测试服务的申请页面
var httpServer = http.createServer(function(request, response){
      var html = fs.readFileSync(__dirname+'/index.html').toString();
      response.end(html);
});
httpServer.listen(8712);




console.log('SocketLog started success');
