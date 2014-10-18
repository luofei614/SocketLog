var ws = require("nodejs-websocket");
var server = ws.createServer(function (conn) {
    console.log("New connection");
    conn.on("text", function (str) {
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
