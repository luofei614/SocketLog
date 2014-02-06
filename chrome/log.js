chrome.extension.onMessage.addListener(
function(logs)
{
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


