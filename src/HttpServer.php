<?php

namespace linkphp\swoole;

use linkphp\Application;
use linkphp\Exception;
use linkphp\process\Process;
use Swoole\Http\Server;

class HttpServer
{

    private $host;

    private $port;

    private $setting = [];

    /**
     * Process对象
     * @var Process
     */
    private $_process;

    /**
     * Server对象
     * @var Server
     */
    private $_server;

    /**
     * Application对象
     * @var Application
     */
    private $app;

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
        $this->onRequest();
        $this->_server->set($this->setting);
        $this->_server->start();
    }

    protected function onStart()
    {
        $this->_server->on('Start',function ($server){
            // 进程命名
            $this->_process::setName("link-httpd: master {$this->host}:{$this->port}");
        });
    }

    // 管理进程启动事件
    protected function onManagerStart()
    {
        $this->_server->on('ManagerStart', function ($server) {
            // 进程命名
            $this->_process::setName("link-httpd: manager");
        });
    }

    // 工作进程启动事件
    protected function onWorkerStart()
    {
        $this->_server->on('WorkerStart', function ($server, $workerId) {
            // 进程命名
            if ($workerId < $server->setting['worker_num']) {
                $this->_process::setName("link-httpd: worker #{$workerId}");
            } else {
                $this->_process::setName("link-httpd: task #{$workerId}");
            }
        });
        // 实例化Apps
        $this->app->event('router');
    }

    // 请求事件
    protected function onRequest()
    {
        $this->_server->on('request', function ($request, $response) {
            $_GET = $request->get;
            $_POST = $request->post;
            $_COOKIE = $request->cookie;
            $_FILES = $request->files;
            if(isset($_SERVER)){
                $_SERVER[] = array_merge($_SERVER,$request->server);
            } else {
                $_SERVER[] = $request->server;
            }
            $kernel = $this->app->get(\bin\http\Kernel::class);
            $kernel->then(function() use($kernel){
                $this->app->get(\linkphp\router\Router::class)
                    ->setPath(
                        $_SERVER[0]['path_info']
                    )->setGetParam($this->app->input('get.'))
                    ->parser()
                    ->dispatch();
                $kernel->setData($this->app->make(\linkphp\router\Router::class)
                    ->getReturnData());
            });
            $response->status(200);
            $response->end($kernel->complete());
        });
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
        $this->send('Server    Name: link-httpd');
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