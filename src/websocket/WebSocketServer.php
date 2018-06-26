<?php

namespace linkphp\swoole\websocket;

use linkphp\http\HttpRequest;
use linkphp\swoole\WebSocket;
use swoole_http_request;
use swoole_http_response;
use framework\Exception;
use swoole_websocket_server;
use swoole_websocket_frame;

class WebSocketServer extends WebSocket
{

    protected function onHandShake()
    {
        $this->_server->on('handshake', function (swoole_http_request $request, swoole_http_response $response){
            $secWebSocketKey = $request->header['sec-websocket-key'];
            $patten = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';
            if (0 === preg_match($patten, $secWebSocketKey) || 16 !== strlen(base64_decode($secWebSocketKey))) {
                $response->end();
                return false;
            }
            $key = base64_encode(sha1(
                $request->header['sec-websocket-key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11',
                true
            ));

            $fd = $request->fd;

            $this->request->setRequester($request);

            $meta = $this->buildConnectionMetadata($fd, $this->request);

            $this->table->set($fd, $meta);

            $headers = [
                'Upgrade' => 'websocket',
                'Connection' => 'Upgrade',
                'Sec-WebSocket-Accept' => $key,
                'Sec-WebSocket-Version' => '13',
            ];

            if (isset($request->header['sec-websocket-protocol'])) {
                $headers['Sec-WebSocket-Protocol'] = $request->header['sec-websocket-protocol'];
            }

            foreach ($headers as $key => $val) {
                $response->header($key, $val);
            }

            $response->status(101);

            $dispatcher = $this->app->get(\linkphp\swoole\websocket\Dispatcher::class);

            $dispatcher->handshake($request, $response);

            $response->end();

            return true;
        });
    }

    /**
     * @param int $fd
     * @param HttpRequest $request
     * @return array
     */
    protected function buildConnectionMetadata(int $fd, HttpRequest $request)
    {
        $info = $this->getClientInfo($fd);
        $path = \parse_url($request->input('server.request_uri'), \PHP_URL_PATH);
        return [
            'fd' => $fd,
            'ip' => $info['remote_ip'],
            'port' => $info['remote_port'],
            'path' => $path,
            'handshake' => false,
            'connectTime' => $info['connect_time'],
            'handshakeTime' => \microtime(true),
        ];
    }

    /**
     * @param int $fd
     * @return array
     */
    public function getClientInfo(int $fd)
    {
        return $this->_server->getClientInfo($fd);
    }

    protected function onOpen()
    {
        $this->_server->on('open', function (swoole_websocket_server $svr, swoole_http_request $request){
            $dispatcher = $this->app->get(\linkphp\swoole\websocket\Dispatcher::class);
            // 实例化Apps
            $dispatcher->open($svr,$request);
        });
    }

    protected function onMessage()
    {
        $this->_server->on('message', function (swoole_websocket_server $server, swoole_websocket_frame $frame){

            $dispatcher = $this->app->get(\linkphp\swoole\websocket\Dispatcher::class);

            $dispatcher->message($server, $frame, $this->table);
        });
    }

    protected function onClose()
    {
        $this->_server->on('close', function ($ser, $fd){

            $dispatcher = $this->app->get(\linkphp\swoole\websocket\Dispatcher::class);

            $dispatcher->close($ser, $fd, $this->table);
        });
    }

}