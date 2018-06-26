<?php

namespace linkphp\swoole\websocket;

use linkphp\http\HttpRequest;

class WebSocketContext
{

    private static $context;

    const META_KEY = 'meta';
    const REQUEST_KEY = 'request';

    public static function init(int $fd, array $meta, HttpRequest $request)
    {
        self::$context[$fd][self::META_KEY] = $meta;
        self::$context[$fd][self::REQUEST_KEY] = $request;
    }

    /**
     * @param int $fd
     * @param string $ctxKey
     * @param mixed $ctxValue
     */
    public static function set(int $fd, string $ctxKey, $ctxValue)
    {
        self::$context[$fd][$ctxKey] = $ctxValue;
    }

    /**
     * @param int $fd
     * @param string|null $ctxKey
     * @return array|null
     */
    public static function get(int $fd = null, string $ctxKey = null)
    {
        if ($fd === null) {
            return null;
        }
        if ($ctxKey) {
            return self::getContext($ctxKey, $fd);
        }
        return self::$context[$fd] ?? null;
    }

    /**
     * @param int $fd
     * @return bool
     */
    public static function has(int $fd): bool
    {
        return isset(self::$context[$fd]);
    }

    /**
     * @param int|null $fd
     * @return null
     */
    public static function del(int $fd = null)
    {
        if ($fd === null) {
            return false;
        }
        if (isset(self::$context[$fd])) {
            unset(self::$context[$fd]);
            return true;
        }
        return false;
    }

    /**
     * @param string $ctxKey
     * @param int|null $fd
     * @return mixed|null
     */
    public static function getContext(string $ctxKey, int $fd = null)
    {
        if ($fd === null) {
            return null;
        }
        return self::$context[$fd][$ctxKey] ?? null;
    }

}