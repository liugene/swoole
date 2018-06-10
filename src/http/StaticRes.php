<?php

namespace linkphp\swoole\http;

class StaticRes
{

    private $document_root;

    private $query_uri;

    public function setDocumentRoot($root)
    {
        $this->document_root = $root;
        return $this;
    }

    public function setQueryUri($uri)
    {
        $this->query_uri = $uri;
        return $this;
    }

    public function getStaticRes()
    {
        $filename = $this->document_root . $this->query_uri;
        if(is_file($filename)){
            return file_get_contents($filename);
//            return $this->header(file_get_contents($filename));
        }
        return false;
    }

    private function header($string)
    {
        return "HTTP/1.1 200 OK\r\n"
            . "Connection: close\r\n"
            . "Content-Type: text/html\r\n"
            . "Content-Length: ".strlen($string)."\r\n"
            . "Server: httpd\r\n\r\n".$string;
    }

}