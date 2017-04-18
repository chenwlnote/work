/**
 * websocket 服务
 * @author dave@1201.us
 * @date 2017-03-28
 * @notice
 * 引入ws.js页面，需要有getMessage(json)方法
 */
;(function () {
    var WSPlugin = function (host,token) {
        this._init(host,token);
    };

    WSPlugin.prototype = {

        start : function(token) {
            var self = this;
            //onopen监听连接打开
            this.websocket.onopen = function (evt) {
                self.connect(token);
            };

            //监听连接关闭
            this.websocket.onclose = function (evt) {
                console.log("连接断开。。。");
            };

            //监听连接错误信息
            this.websocket.onerror = function (evt, e) {
                console.log('Error occured: ' + evt.data);
            };

            //onmessage 监听服务器数据推送
            this.websocket.onmessage = function (evt) {
                var json = JSON.parse(evt.data);
                var event = json['event'];
                var eventArr = event.split(".");
                var pluginName = eventArr[0];
                var func = eventArr[1];
                var msg = json['msg'];
                getMessage(pluginName,func,msg);
            };
        },
        sendMessage : function(json) {
            this.websocket.send(JSON.stringify(json));
        },
        connect : function (token) {
            var json = {"event":"client.connect","msg":token,"returnEvent":""};
            this.websocket.send(JSON.stringify(json));
        },
        _init: function (host,token) {
            if(window.WebSocket != undefined) {
                this.websocket = new WebSocket(host);
                this.start(token);
            }
        }
    };

    window['WSPlugin'] = WSPlugin;
})();
