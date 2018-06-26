<?php

namespace linkphp\swoole\websocket;

use framework\Application;
use Swoole\Table;
use swoole_http_request as Request;
use swoole_http_response as Response;
use framework\Exception;
use swoole_server as Server;
use swoole_websocket_frame as Frame;

class Dispatcher
{

    private $app;

    public function __construct(Application $application)
    {
        $this->app = $application;
    }

    /**
     * dispatch handshake request
     * @param Request $request
     * @param Response $response
     * @return array eg. [status, response]
     * @throws \InvalidArgumentException
     * @throws \Throwable
     */
    public function handshake(Request $request, Response $response)
    {
        try {
            $path = $request->server['request_uri'];
        } catch (\Throwable $e) {
            // other error
            throw $e;
        }
        /** @var Exception $handler */
        $handler = $this->getHandler($path);
        if (!\method_exists($handler, 'Handshake')) {
            throw new Exception('method not found');
        }
        return $handler->HandShake($request, $response);
    }
    /**
     * @param Server $server
     * @param Request $request
     * @param int $fd
     * @throws \InvalidArgumentException
     */
    public function open(Server $server, Request $request, int $fd)
    {
        $path = $request->server['request_uri'];

        $handler = $this->getHandler($path);

        if (\method_exists($handler, 'open')) {
            $handler->open($server, $request, $fd);
        }
    }
    /**
     * dispatch ws message
     * @param Server $server
     * @param Frame $frame
     * @param $table
     * @throws \Exception
     */
    public function message(Server $server, Frame $frame, Table $table)
    {
        $fd = $frame->fd;
        try {
            if (!$path = $table->get($fd)) {
                throw new Exception("The connection info has lost of the fd#$fd, on message");
            }
            /** @var Exception $handler */
            $handler = $this->getHandler($path['path']);
            if (\method_exists($handler, 'message')) {
                $handler->message($server, $frame);
            }
        } catch (\Throwable $e) {

        }
    }
    /**
     * dispatch ws close
     * @param Server $server
     * @param int $fd
     * @param $table
     * @throws \Exception
     */
    public function close(Server $server, int $fd, Table $table)
    {
        try {
            if (!$path = $table->get($fd)) {
                throw new Exception(
                    "The connection info has lost of the fd#$fd, on connection closed"
                );
            }
            /** @var Exception $handler */
            $handler = $this->getHandler($path['path']);
            if (\method_exists($handler, 'close')) {
                $handler->close($server, $fd);
            }
        } catch (\Throwable $e) {

        }
    }
    /**
     * @param string $path
     * @return array
     * @throws \Exception
     */
    protected function getHandler($path)
    {
        $handle = $this->app->get(\linkphp\router\Router::class)
        ->setPath(
            $path
        )->setGetParam(
            $this->app->input('get.')
        )->setMethod(
            'ws'
        )->parser()
        ->dispatch();
        return $handle;
    }
}