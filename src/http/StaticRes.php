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
            // 页面缓存
            ob_start();
            ob_implicit_flush(0);
            // 渲染输出
            try {
                include $filename;
            } catch (\Exception $e) {
                ob_end_clean();
                throw $e;
            }

            // 获取并清空缓存
            $content = ob_get_clean();
            return $content;
//            return $this->header(file_get_contents($filename));
        }
        return false;
    }

}