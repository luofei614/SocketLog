/**
 * github: https://github.com/luofei614/SocketLog
 * @author luofei614<weibo.com/luofei614>
 */ 
chrome.extension.onMessage.addListener(
function(logs)
{
    if('object'!=typeof(logs))
    {
        return ;
    }
    logs.forEach(function(log)
    {
            if(console[log.type])
            {
              if(log.css)
              {
                console[log.type]('%c'+log.msg,log.css);
              }
              else
              {
                console[log.type](log.msg);
              }
              return ;
            }

            if('alert'==log.type)
            {
                alert(log.msg); 
            }
            else
            {
                alert('SocketLog type error, '+log.type); 
            }
    });
});


