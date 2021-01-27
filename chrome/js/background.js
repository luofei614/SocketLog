/**
 * github: https://github.com/luofei614/SocketLog
 * @author luofei614<weibo.com/luofei614>
 */
var websocket = null;
var websocket_timeout = 0;
var limit_connect = 1;  // 断线重连次数
var count = 0;  // 重连计数

function ws_init() {
    if (websocket) {
        //避免重复监听
        websocket.onclose = function() {}; //onclose 函数置空，防止重复链接
        websocket.close();
    }
    var protocol = localStorage.getItem("protocol");
    var address = localStorage.getItem("address");
    var port = localStorage.getItem("port");
    var full_address = "";
    var client_id = localStorage.getItem("client_id");
    var open = localStorage.getItem("open");

    if (!protocol) {
        protocol = "ws";
    }
    if (!address) {
        address = "localhost";
    }
    if (!port) {
        port = "1229";
    }

    full_address = protocol + "://" + address + ":" + port;

    if (client_id) {
        //client_id作为地址
        full_address += "/" + client_id;
    }

    if (open == "false" || open == null) {
        disable_icon();
        localStorage.setItem("status", "close");
        return false;
    }

    websocket = new WebSocket(full_address);

    websocket.onerror = function(msg) {
        localStorage.setItem("status", "error");
        disable_icon();
    };

    websocket.onclose = function() {
        localStorage.setItem("status", "close");
        disable_icon();
    };

    websocket.onopen = function() {
        localStorage.setItem("status", "open");
        enable_icon();
    };

    websocket.onmessage = function(event) {
        var check_error = function() {
            if (event.data.indexOf("SocketLog error handler") != "-1") {
                var opt = {
                    type: "basic",
                    title: "注意",
                    message: "有异常报错，请注意查看 Console 控制台中的日志",
                    iconUrl: "logo.png",
                };
                chrome.notifications.create("", opt, function(id) {
                    setTimeout(function() {
                        chrome.notifications.clear(id, function() {});
                    }, 3000);
                });
            }

            if (event.data.indexOf("[NO WHERE]") != "-1") {
                var opt = {
                    type: "basic",
                    title: "注意",
                    message: "存在没有 WHERE 条件的操作 SQL 语句",
                    iconUrl: "logo.png",
                };
                chrome.notifications.create("", opt, function(id) {
                    setTimeout(function() {
                        chrome.notifications.clear(id, function() {});
                    }, 3000);
                });
            }
        };

        try {
            var data = JSON.parse(event.data);
        } catch (e) {
            if (0 == event.data.indexOf("close:")) {
                websocket.onclose = function() {}; //onclose 函数置空，防止重复链接
                alert("此 client_id 不允许连接服务");
            } else {
                alert("日志格式错误，" + event.data);
            }
            return;
        }
        var client_id = localStorage.getItem("client_id");
        //判断是否有强制日志
        if (client_id && data.force_client_id == client_id) {
            //将强制日志输出到当前的tab页
            chrome.tabs.query({ currentWindow: true, active: true },
                function(tabArray) {
                    if (tabArray && tabArray[0]) {
                        //延迟保证日志每次都能记录
                        setTimeout(function() {
                            check_error();
                            chrome.tabs.sendMessage(tabArray[0].id, data.logs);
                        }, 100);
                    }
                }
            );
            return;
        }

        if ((client_id && data.client_id != client_id) || !data.tabid) {
            //不是当前用户的日志不显示。
            return;
        }
        //延迟保证日志每次都能记录
        setTimeout(function() {
            check_error();
            chrome.tabs.sendMessage(parseInt(data.tabid), data.logs);
        }, 100);
    };
}

function ws_restart() {
    ws_init();
}

function enable_icon() {
    chrome.browserAction.setIcon({
        path: "images/logo_16.png",
    });
}

function disable_icon() {
    chrome.browserAction.setIcon({
        path: "images/logo_disabled_16.png",
    });
}

ws_init();

function url_exp(url) {
    var splatParam = /\*/g;
    var escapeRegExp = /[-[\]{}()+?.,\\^$#\s]/g;
    url = url.replace(escapeRegExp, "\\$&").replace(splatParam, "(.*?)");
    return new RegExp(url, "i");
}

chrome.webRequest.onBeforeSendHeaders.addListener(
    function(details) {
        var header = "tabid=" + details.tabId;
        var client_id = localStorage.getItem("client_id");

        if (!client_id) {
            client_id = "";
        }

        header += "&client_id=" + client_id;

        var special_domain = localStorage.getItem("special_domain");
        if (!special_domain) {
            special_domain = "*.sinaapp.com";
            localStorage.setItem("special_domain", special_domain);
        }
        var exp = url_exp(special_domain);
        if (exp.test(details.url)) {
            //如果是特殊环境域名
            for (var i = 0; i < details.requestHeaders.length; ++i) {
                if (details.requestHeaders[i].name === "User-Agent") {
                    //将参数放在User-agent中，兼容SAE的情况
                    details.requestHeaders[i].value += " SocketLog(" + header + ")";
                    break;
                }
            }
        } else {
            details.requestHeaders.push({
                name: "SocketLog",
                value: " SocketLog(" + header + ")",
            });
        }

        return { requestHeaders: details.requestHeaders };
    }, { urls: ["<all_urls>"] }, ["blocking", "requestHeaders"]
);

chrome.webRequest.onCompleted.addListener(
    function(details) {
        var online_domain = localStorage.getItem("online_domain");
        if (online_domain) {
            var exp = url_exp(online_domain);
            if (exp.test(details.url)) {
                chrome.tabs.sendMessage(details.tabId, "online_evn");
            }
        }
    }, { urls: ["<all_urls>"], types: ["main_frame", "sub_frame"] }
);
