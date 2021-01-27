/**
 * github: https://github.com/luofei614/SocketLog
 * @author luofei614<weibo.com/luofei614>
 */
document.addEventListener('DOMContentLoaded', init, false);
/**
 * 初始化函数
 * @return {[type]} [description]
 */
function init() {
    var protocol = document.getElementById('protocol');
    var address = document.getElementById('address');
    var port = document.getElementById('port');
    var full_address = document.getElementById('full_address');
    var client_id = document.getElementById('client_id');
    var open = document.getElementById('open');

    if (localStorage.getItem('protocol')) {
        protocol.value = localStorage.getItem('protocol');
    }
    if (localStorage.getItem('address')) {
        address.value = localStorage.getItem('address');
    }
    if (localStorage.getItem('port')) {
        port.value = localStorage.getItem('port');
    }

    full_address.value = protocol.value + '://' + address.value + ':' + port.value;

    if (localStorage.getItem('client_id')) {
        client_id.value = localStorage.getItem('client_id');
    }
    if (localStorage.getItem('open')) {
        open.checked = localStorage.getItem('open') == 'false' ? false : true;
    }
    // 显示连接状态
    var status = localStorage.getItem('status');
    viewStatusNotic(status);

    document.getElementById('save').addEventListener('click', save, false);

    protocol.addEventListener('input', addressChange, false);
    address.addEventListener('input', addressChange, false);
    port.addEventListener('input', addressChange, false);

    protocol.addEventListener('porpertychange', addressChange, false);
    address.addEventListener('porpertychange', addressChange, false);
    port.addEventListener('porpertychange', addressChange, false);

}
/**
 * 检测主机地址变化，实时各项配置值
 * @param  {[type]} e [description]
 * @return {[type]}   [description]
 */
function addressChange(e) {
    var protocol = document.getElementById('protocol').value;
    var address = document.getElementById('address').value;
    var port = document.getElementById('port').value;
    document.getElementById('full_address').value = protocol + '://' + address + ':' + port;
}
/**
 * 辅助函数，检测一个值 IP 是否合法
 * @param  {[type]}  ip [description]
 * @return {Boolean}    [description]
 */
function isValidIP(ip) {
    var reg = /^(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.(\d{1,2}|1\d\d|2[0-4]\d|25[0-5])$/;
    return reg.test(ip);
}
/**
 * 辅助函数，检测一个值域名是否合法
 * @param  {[type]}  domain [description]
 * @return {Boolean}        [description]
 */
function isValidDomain(domain) {
    var reg = /^[a-zA-Z0-9][-a-zA-Z0-9]{0,62}(.[a-zA-Z0-9][-a-zA-Z0-9]{0,62})+.?$/;
    return reg.test(domain);
}
/**
 * 辅助函数，检测一个值是否在数组中
 * @param  {[type]} search [description]
 * @param  {[type]} array  [description]
 * @return {[type]}        [description]
 */
function inArray(search, array) {
    for (var i in array) {
        if (array[i] == search) {
            return true;
        }
    }
    return false;
}

/**
 * 显示弹窗
 * @param  {[type]} status [description]
 * @return {[type]}        [description]
 */
function viewStatusNotic(status) {
    if (status) {
        var text = '', color = '';

        switch (status) {
            case "connecting":
                text = '正在连接...';
                color = '#64bd63';
                break;
            case "open":
                text = '链接成功';
                color = '#64bd63';
                break;
            case "close":
                text = '链接断开';
                color = 'gray';
                break;
            case "error":
                text = '链接失败';
                color = 'red';
                break;
            default:
                alert('运行状态异常');
                break;
        }
        document.getElementById('status-div').style.color = color;
        document.getElementById('status').innerHTML = text;
    }
}

/**
 * 保存配置项
 * @return {[type]} [description]
 */
function save() {
    // 获取配置项的值
    var protocol = document.getElementById('protocol').value;
    var address = document.getElementById('address').value;
    var port = document.getElementById('port').value;
    var client_id = document.getElementById('client_id').value;
    var open = document.getElementById('open').checked;

    // 校验主机地址和协议
    var allow_protocols = ['ws', 'wss'];
    var ipOk = isValidIP(address);
    var domainOk = isValidDomain(address);

    if (!inArray(protocol, allow_protocols)) {
        alert('不支持的协议类型');
        var status = 'error';
        viewStatusNotic(status);
        return false;
    }
    if (!ipOk || !domainOk) {
        alert('不是合法的 IP 地址或域名');
        var status = 'error';
        viewStatusNotic(status);
        return false;
    }

    // 保存配置项的值
    localStorage.setItem('protocol', protocol);
    localStorage.setItem('address', address);
    localStorage.setItem('port', port);
    localStorage.setItem('client_id', client_id);
    localStorage.setItem('open', open);
    // 触发重新连接
    chrome.extension.getBackgroundPage().ws_restart();
    // 显示连接状态
    var status = localStorage.getItem('status');
    console.log(status);
    viewStatusNotic(status);
    // 如果连接成功，关闭当前设置弹出
    if (status == 'open') {
        window.close();
    }
}
