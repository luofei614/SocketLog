/**
 * github: https://github.com/luofei614/SocketLog
 * @author luofei614<weibo.com/luofei614>
 */ 
var websocket=null;
var websocket_timeout=0;
function ws_init()
{
    var address=localStorage.getItem('address');
    if(!address)
    {
        address="ws://localhost:1229"; 
    }
    websocket=new WebSocket(address);

    websocket.onerror=function(msg)
    {
        console.log(websocket_timeout,'error');
        clearTimeout(websocket_timeout);
        websocket_timeout=setTimeout(ws_init,2000);
        console.log(websocket_timeout,'error_new');
        localStorage.setItem('status','error');
        disable_icon();
    };

    websocket.onclose=function()
    {
        setTimeout(function(){
            console.log(websocket_timeout,'close');
            clearTimeout(websocket_timeout);
            websocket_timeout=setTimeout(ws_init,2000);
            console.log(websocket_timeout,'close_new');
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
                var notification = window.webkitNotifications.createNotification(
                        'logo.png',   
                        '注意',    
                        '有异常报错，请注意查看console 控制台中的日志'
                        );
                notification.ondisplay = function(event) {
                     setTimeout(function() {
                                     event.currentTarget.cancel();
                                 }, 5000);
                }
                notification.show();
            }
        };
       
        try
        {
                  var data=JSON.parse(event.data);
        }
        catch(e)
        {
           alert('日志格式错误，'+event.data);
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
    if(websocket){ 
        websocket.close(); 
    }
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


    

chrome.webRequest.onBeforeSendHeaders.addListener(
  function(details) {

        var header="tabid="+details.tabId;

        var client_id=localStorage.getItem('client_id');
        if(!client_id)
        {
            client_id='';
        }


        header+="&client_id="+client_id;

        if (details.url.indexOf('sinaapp.com') > 0) {//如果是sae的域名，目前只能根据这个判断，如果绑定了自定义域名的SAE则不行
            for (var i = 0; i < details.requestHeaders.length; ++i) {
                if (details.requestHeaders[i].name === 'User-Agent') {
                    //将参数放在User-agent中，兼容SAE的情况
                    details.requestHeaders[i].value+=" SocketLog("+header+")";
                    break;
                }
            }
        } else {
            details.requestHeaders.push({name:'SocketLog',value:" SocketLog("+header+")"});
        }

       return {requestHeaders: details.requestHeaders};
  },
  {urls: ["<all_urls>"]},
  ["blocking", "requestHeaders"]);
