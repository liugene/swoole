<?php

namespace linkphp\swoole;

use linkphp\http\HttpRequest;
use swoole\websocket\server;
use framework\Application;
use Swoole\Table;

abstract class WebSocket
{

    protected $host;

    protected $port;

    protected $setting = [];

    /**
     * Process对象
     * @var Process
     */
    protected $_process;

    /**
     * Server对象
     * @var Server
     */
    protected $_server;

    /**
     * Application对象
     * @var Application
     */
    protected $app;

    /**
     * HttpRequest对象
     * @var HttpRequest
     */
    protected $request;

    protected $table;

    public function __construct(Application $application, HttpRequest $request)
    {
        $this->app = $application;
        $this->request = $request;
        $this->table = new Table(1024);
        $this->table->column('path', Table::TYPE_STRING, 16);
        $this->table->column('reactor_id', Table::TYPE_INT, 6);
        $this->table->column('server_fd', Table::TYPE_INT, 6);
        $this->table->column('server_port', Table::TYPE_INT, 5);
        $this->table->column('remote_port', Table::TYPE_INT, 5);
        $this->table->column('remote_ip', Table::TYPE_STRING, 16);
        $this->table->column('connect_time', Table::TYPE_INT, 10);
        $this->table->column('last_time', Table::TYPE_INT, 10);
        $this->table->create();
    }

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

    public function setting($setting)
    {
        $this->setting = $setting;
        return $this;
    }

    public function setProcess($process)
    {
        $this->_process = $process;
        return $this;
    }

    public function start()
    {
        $this->welcome();
        $this->app->event('router');
        $this->request->setRequestMethod('ws');
        $this->_server = new server($this->host, $this->port);
        $this->onRequest();
        $this->onHandShake();
        $this->onOpen();
        $this->onMessage();
        $this->onClose();
        $this->_server->set($this->setting);
        $this->_server->start();
    }

    protected function onRequest(){}

    protected function onHandShake(){}

    protected function onOpen(){}

    protected function onMessage(){}

    protected function onClose()
    {
        $this->_server->on('Close',function ($server, $fd, $reactorId){
            echo $reactorId;
            $this->close();
        });
    }

    protected function close()
    {
        $this->send('Server    Name: link-httpd was closed');
    }

    // 欢迎信息
    protected function welcome()
    {
        $swooleVersion = swoole_version();
        $phpVersion    = PHP_VERSION;
        echo <<<EOL
 _        _              _                  
| |      | |   _   ___  | |      ___
| |  ___ | | / / /  _  \| |_   /  _  \
| | | \ \| |/ /  | |_| ||  _ \ | |_| |
| |_| |\ V |\ \  | .___/| | | || .___/
|_____| \ _' \_\ | |    | | | || |

EOL;
        $this->send('Server    Name: link-ws');
        $this->send("PHP    Version: {$phpVersion}");
        $this->send("Swoole Version: {$swooleVersion}");
        $this->send("Listen    Address: {$this->host}");
        $this->send("Listen    Port: {$this->port}");
        return;
    }

    // 发送至屏幕
    protected static function send($msg)
    {
        $time = date('Y-m-d H:i:s');
        echo "[{$time}] " . $msg . PHP_EOL;
    }

}