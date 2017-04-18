<?php
namespace App;

/**
* websocket 服务管理
*/
class WebSocketServiceManage
{

    /** 错误代码前缀 */
    const ErrorCodePrefix = 200;

	private $ws = null;
	private $redis = null;

    public function __construct()
    {
        $port = \Swoole::$php->config['server']['port'];
        if ( isset( $_SERVER[ 'argv' ][ 1 ] ) )
        {
            $port = $_SERVER[ 'argv' ][ 1 ];
        }
        $this->redis = \Swoole::$php->redis;

        $this->ws = new \swoole_websocket_server('0.0.0.0',$port);
        $this->ws->on('open',array($this,'onOpen'));
        $this->ws->on('message',array($this,'sendMessage'));
        $this->ws->on('close',array($this,'onClose'));
        $this->ws->start();

    }


    /**
     * 客户端服务器建立连接
     * @param \swoole_websocket_server $ws
     * @param \swoole_http_request $request
     */
    public function onOpen(\swoole_websocket_server $ws , \swoole_http_request $request)
	{
		$fd = $request->fd;

		$data = [
			'msg'=>'连接服务器成功！'
		];

		$connectInfo = $ws->connection_info($fd);
		$header = $request->header;

		$deviceInfo = array_merge($header,$connectInfo);

		// 记录连接日志
		DeviceManage::LoginDevice($fd,$deviceInfo);
		LogManage::connectLog($deviceInfo);

		if ( !$this->isAllowRequest($request->header['origin']) ) {
            if ($_SERVER['SOCKET_ENV'] != 'test')
            {
                $ws->close($fd);
            }
        }

		$callback = json_encode($data);
		$ws->push($fd,$callback);

	}

    /**
     * 是否允许请求
     * @param string $origin
     * @return bool
     */
	private function isAllowRequest(string $origin = '')
    {
        $flag = false;
        $allow_origin = \Swoole::$php->config['server']['allow_host'];

        if ( in_array( $origin ,$allow_origin) ) {
            $flag = true;
        }

        return $flag;

    }


    /**
     * 消息处理
     * @param \swoole_websocket_server $ws
     * @param \swoole_websocket_frame $frame
     */
    public function sendMessage(\swoole_websocket_server $ws , \swoole_websocket_frame $frame)
	{
	    try {

            $fd = $frame->fd;

	        //请求日志
	        LogManage::requestLog($frame->data);

            $service = new PluginManage();
            $data = json_decode( $frame -> data , true );

            $data['fd'] = $fd;

            //运行消息插件管理
            $result = $service->runPluginManage($data);

            $fd_to = $result[ 'to' ]??'';


            $callbackData = $result['data']??[];

            if ( is_array($callbackData) )
            {
                //发送调试信息
                $this->sendDebugMessage($ws,$fd ,$callbackData);
            }

            var_dump($callbackData);


            //---------------------Response Client Msg---------start----------
            if ( !empty( $fd_to ) )
            {
                if ( is_array($fd_to) )
                {
                    foreach ((array) $fd_to as $to)
                    {
                        $ws->push($to,json_encode($callbackData));
                    }
                }else
                {
                    $ws->push($fd_to,json_encode($callbackData));
                }
            } else
            {
                $ws->push( $fd , json_encode($callbackData) );
            }
            //---------------------Response Client Msg---------end----------
        }catch (\Exception $e)
        {
            $callbackData = [
                'code'=>$e->getCode(),
                'msg'=>$e->getMessage()
            ];
	        $ws->push( $fd , json_encode($callbackData) );
        }
	}

    /**
     * 客户端断开连接
     * @param \swoole_websocket_server $ws
     * @param int $fd
     */
    public function onClose(\swoole_websocket_server $ws , int $fd)
	{

	    //停止调试
        DeviceManage::stopDebug($fd);

        //断开设备连接
        DeviceManage::LogoutDevice($fd);


        try {
            $ws->close($fd);
        }catch (\Exception $e)
        {
            echo "客户端：".$fd."已连接已断开!";
        }


	}


    /**
     * 发送调试信息
     * @param \swoole_websocket_server $ws
     * @param int $fd
     * @param array $data
     */
	public function sendDebugMessage(\swoole_websocket_server $ws,int $fd, array $data = [])
    {
        $accountInfo = DeviceManage::getAccountInfoByFd($fd);
        if ( !empty( $accountInfo['listener'] ) )
        {
            $listener = (int)$accountInfo['listener'];
            $account = DeviceManage::getAccountInfoByAccountId($listener);
            $debugFd = intval($account['fd']);
            $ws->push($debugFd,json_encode($data));
        }

    }

}



