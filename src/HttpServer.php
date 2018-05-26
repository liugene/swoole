<?php

namespace linkphp\swoole;

use swoole_http_server;
use swoole_http_request;
use swoole_http_response;

class HttpServer
{

    private $host;

    private $port;

    public function setHost(){}

    public function setPort(){}

    public function start()
    {
        $http_server = new swoole_http_server("127.0.0.1", 9501);
        $http_server->on('request', function(swoole_http_request $request, swoole_http_response $response) {
            $response->end("<h1>hello swoole</h1>");
        });
        $http_server->start();
    }

}