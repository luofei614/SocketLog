/**
 * github: https://github.com/luofei614/SocketLog
 * @author luofei614<weibo.com/luofei614>
 */ 
var websocket=null;
var websocket_timeout=0;
function ws_init()
{
    if(websocket)
    {
        //避免重复监听
        websocket.onclose=function(){};//onclose 函数置空，防止重复链接
        websocket.close(); 
    }
    var address=localStorage.getItem('address');
    var client_id=localStorage.getItem('client_id');
    if(!address)
    {
        address="ws://localhost:1229"; 
    }
    if(client_id)
    {
        //client_id作为地址
        address+='/'+client_id;     
    }
    websocket=new WebSocket(address);

    websocket.onerror=function(msg)
    {
        clearTimeout(websocket_timeout);
        websocket_timeout=setTimeout(ws_init,2000);
        localStorage.setItem('status','error');
        disable_icon();
    };

    websocket.onclose=function()
    {
        setTimeout(function(){
            clearTimeout(websocket_timeout);
            websocket_timeout=setTimeout(ws_init,2000);
        },1000);
        localStorage.setItem('status','close');
        disable_icon();
    }

    websocket.onopen=function()
    {
        localStorage.setItem('status','open');
        enable_icon(); 
    }


    websocket.onmessage=function(event){

        var check_error=function()
        {
            if(event.data.indexOf('SocketLog error handler')!='-1')
            {
               var opt = {
                  type: "basic",
                  title: "注意",
                  message: "有异常报错，请注意查看console 控制台中的日志",
                  iconUrl: "logo.png"
                };
                chrome.notifications.create('',opt,function(id){
                    setTimeout(function(){
                        chrome.notifications.clear(id,function(){});
                    },3000);
                });

            }


            if(event.data.indexOf('[NO WHERE]')!='-1')
            {
                var opt = {
                  type: "basic",
                  title: "注意",
                  message: "存在没有WHERE语句的操作sql语句",
                  iconUrl: "logo.png"
                };
                chrome.notifications.create('',opt,function(id){
                    setTimeout(function(){
                        chrome.notifications.clear(id,function(){});
                    },3000);
                });
            }

        };
       
        try
        {
                  var data=JSON.parse(event.data);
        }
        catch(e)
        {
           if(0==event.data.indexOf('close:')){
             websocket.onclose=function(){};//onclose 函数置空，防止重复链接
             alert('此client_id不允许连接服务');
           }else{
             alert('日志格式错误，'+event.data);
           }
           return ; 
        }
        var client_id=localStorage.getItem('client_id');
        //判断是否有强制日志
        if(client_id && data.force_client_id==client_id)
        {
          //将强制日志输出到当前的tab页
          chrome.tabs.query(
                {currentWindow: true, active: true},
                function(tabArray) {
                    if (tabArray && tabArray[0])
                    {
                        //延迟保证日志每次都能记录
                        setTimeout(function(){
                            check_error();
                            chrome.tabs.sendMessage(tabArray[0].id,data.logs);
                        },100);
                    }
                }
            );
          return ;
        }

        if((client_id  && data.client_id!=client_id) || !data.tabid)
        {
           //不是当前用户的日志不显示。 
           return ; 
        }
        //延迟保证日志每次都能记录
        setTimeout(function(){
            check_error();
            chrome.tabs.sendMessage(parseInt(data.tabid),data.logs);
        },100);
    };
}

function ws_restart()
{
    ws_init();
}


function enable_icon() {
    chrome.browserAction.setIcon({
        path: "logo.png"
    });
}

function disable_icon() {
    chrome.browserAction.setIcon({
        path: "logo_disabled.png"
    });
}



ws_init();


function url_exp(url)
{
    var splatParam    = /\*/g;
    var escapeRegExp  = /[-[\]{}()+?.,\\^$#\s]/g;
    url = url.replace(escapeRegExp, '\\$&')
        .replace(splatParam, '(.*?)');
    return new RegExp(url, 'i');
} 

chrome.webRequest.onBeforeSendHeaders.addListener(
  function(details) {

        var header="tabid="+details.tabId;

        var client_id=localStorage.getItem('client_id');
        if(!client_id)
        {
            client_id='';
        }


        header+="&client_id="+client_id;

        //将Header隐藏在User-Agent中， 不能使用自定义Header了， 不让HTTPS情况下会报不安全
        for (var i = 0; i < details.requestHeaders.length; ++i) 
        {
            if (details.requestHeaders[i].name === 'User-Agent') 
            {
                details.requestHeaders[i].value+=" SocketLog("+header+")";
                break;
            }
        }

       return {requestHeaders: details.requestHeaders};
  },
  {urls: ["<all_urls>"]},
  ["blocking", "requestHeaders"]);

chrome.webRequest.onCompleted.addListener(function(details){
    var online_domain=localStorage.getItem('online_domain');
    if(online_domain){
        var exp=url_exp(online_domain);
        if(exp.test(details.url))
        {
                chrome.tabs.sendMessage(details.tabId,'online_evn');
        }
    }
},{urls: ["<all_urls>"],types:['main_frame','sub_frame']});
