//环境标示，识别正式环境
chrome.extension.onMessage.addListener(
function(logs)
{
    if('online_evn'==logs)
    {
        if('complete'!=document.readyState){
            document.addEventListener('DOMContentLoaded', show_online_evn, true);
        }
        else
        {
            show_online_evn();
        }
    }
});

function show_online_evn()
{
    if(window.top==window ||  window.top.document.getElementsByTagName('frameset').length>0)
    {
        if(!document.getElementById('_socketlog_online_evn_span'))
        {
            //标记正式环境
            var mySpan = document.createElement("span");
            mySpan.innerHTML = "正式环境，请谨慎操作！";
            mySpan.style.color = "#fff";
            mySpan.id="_socketlog_online_evn_span";
            mySpan.style.backgroundColor = "red";
            mySpan.style.position="absolute";
            mySpan.style.top="10px";
            mySpan.style.left="10px";
            mySpan.style.padding="5px";
            mySpan.style.zIndex="10000";
            document.body.appendChild(mySpan);
        }
    }
}

