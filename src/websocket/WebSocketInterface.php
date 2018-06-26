<?php

namespace linkphp\swoole\websocket;

use swoole_websocket_server;
use swoole_http_request;
use swoole_server;
use swoole_websocket_frame;
use swoole_http_response;

interface WebSocketInterface
{

    public function HandShake(swoole_http_request $request, swoole_http_response $response);

    public function open(swoole_websocket_server $svr, swoole_http_request $req);

    public function message(swoole_server $server, swoole_websocket_frame $frame);

    public function close($ser, $fd);

}