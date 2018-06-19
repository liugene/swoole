<?php

namespace linkphp\swoole\http;

use framework\Exception;
use linkphp\swoole\HttpServer;

class HttpdServer extends HttpServer
{

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
            $kernel = $this->app->get(\bin\http\Kernel::class);
            $request_uri = $request->server['request_uri'];
            $php = preg_match('/\.php/i',$request_uri);
            if($php || $request_uri == '' || $request_uri == null || $request_uri == '/'){
                $httpRequest = $this->app->make(\linkphp\http\HttpRequest::class);
                $httpRequest->setRequester($request);
                $kernel->then(function() use($kernel){
                    try{
                        $this->app->get(\linkphp\router\Router::class)
                            ->setPath(
                                $_SERVER['path_info']
                            )->setGetParam($this->app->input('get.'))
                            ->parser()
                            ->dispatch();
                        $kernel->setData($this->app->make(\linkphp\router\Router::class)
                            ->getReturnData());
                    } catch (Exception $e) {
                        $kernel->setData($e->getMessage());
                    }
                });
            }
            if($this->enable_static_handler &&
                !$php && $request_uri != '' &&
                $request_uri != null &&
                $request_uri != '/'){
                $static = $this->app->get(\linkphp\swoole\http\StaticRes::class);
                $res = $static->setDocumentRoot($this->getDocumentRoot())
                    ->setQueryUri($request->server['request_uri'])
                    ->getStaticRes();
                if(!$res){
                    $kernel->setData('file not found');
                } else {
                    $kernel->setData($res);
                    $response->status(200);
                    $response->end($kernel->complete());
                }
            }
            $response->status(200);
            $response->header('Content-Type', 'text/html; charset=utf-8');
//            dump($kernel->complete());
//            $response->end('<html><title>test</title><body>test</body></html>');
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

}