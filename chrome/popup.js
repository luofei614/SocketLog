/**
 * github: https://github.com/luofei614/SocketLog
 * @author luofei614<weibo.com/luofei614>
 */ 
document.addEventListener('DOMContentLoaded', init, false);
function init()
{
    if(localStorage.getItem('address'))
    {
        document.getElementById('address').value=localStorage.getItem('address'); 
    }

    if(localStorage.getItem('client_id'))
    {
        document.getElementById('client_id').value=localStorage.getItem('client_id'); 
    }
    var status=localStorage.getItem('status');
    if(status)
    {
        var text='';

        switch(status)
        {
            case "open":
                text='链接成功';
            break;
            case "close":
                text='链接断开';
            break;
            case "error":
                text='链接失败';
            break;
            default:
              alert('运行状态异常');
            break;
        }
        document.getElementById('status').innerHTML=text; 
    }

    document.getElementById('save').addEventListener('click',save,false);
}

function save()
{
    localStorage.setItem('address',document.getElementById('address').value);
    localStorage.setItem('client_id',document.getElementById('client_id').value);
    chrome.extension.getBackgroundPage().ws_restart();
    window.close();
}
