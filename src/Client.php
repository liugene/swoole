<?php

namespace linkphp\swoole;

use swoole\Client as SwooleClient;

class Client
{

    /**
     * @var SwooleClient
     */
    protected $_client;

    protected $host;

    protected $port;

    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }

    public function start()
    {
        $this->_client = new SwooleClient(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
        $this->onReceive();
        $this->onClose();
        $this->_client->connect($this->host, $this->port);
    }

    public function onReceive()
    {
        $this->_client->on("receive", function(SwooleClient $cli, $data){
            echo "Receive: $data";
            $cli->send(str_repeat('A', 100)."\n");
            sleep(1);
        });
    }

    public function onClose()
    {
        $this->_client->on("close", function(SwooleClient $cli){
            echo "Connection close\n";
        });
    }

}