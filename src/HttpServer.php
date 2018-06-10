<?php

namespace linkphp\swoole;

use framework\Application;
use framework\Exception;
use linkphp\process\Process;
use Swoole\Http\Server;

abstract class HttpServer
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

    protected $document_root;

    protected $enable_static_handler = false;

    public function __construct(Application $application)
    {
        $this->app = $application;
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

    public function setDocumentRoot($root)
    {
        $this->document_root = $root;
        return $this;
    }

    public function getDocumentRoot()
    {
        return $this->document_root;
    }

    public function setEnableStaticHandler($bool)
    {
        $this->enable_static_handler = $bool;
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
        $this->_server = new Server($this->host, $this->port);
        $this->onStart();
        $this->onManagerStart();
        $this->onWorkerStart();
        $this->onReceive();
        $this->onRequest();
        $this->onClose();
        $this->_server->set($this->setting);
        $this->_server->start();
    }

    protected function onStart(){}

    // 管理进程启动事件
    protected function onManagerStart(){}

    // 工作进程启动事件
    protected function onWorkerStart(){}

    protected function onReceive(){}

    // 请求事件
    protected function onRequest(){}

    protected function onClose()
    {
        $this->_server->on('Close',function ($server, $fd, $reactorId){
            echo $reactorId;
            $this->close();
        });
    }

    // 欢迎信息
    protected function welcome(){}

    protected function close()
    {
        $this->send('Server    Name: link-httpd was closed');
    }

    // 发送至屏幕
    protected static function send($msg)
    {
        $time = date('Y-m-d H:i:s');
        echo "[{$time}] " . $msg . PHP_EOL;
    }

}