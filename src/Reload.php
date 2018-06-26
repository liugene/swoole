<?php

namespace linkphp\swoole;

use framework\Exception;
use linkphp\process\Process;

class Reload
{

    protected $pid;

    protected $watch_files;

    /**
     * @var resource
     */
    protected $inotify;

    protected $reload_file_types = ['.php' => true];

    protected $after_n_seconds;

    /**
     * 正在reload
     */
    protected $reloading = false;

    protected $events;

    /**
     * 进程管理
     * @var Process
     */
    protected $_process;

    /**
     * 根目录
     * @var array
     */
    protected $rootDirs = [];

    /**
     * @param $serverPid
     * @param $process
     * @throws Exception
     */
    public function __construct($serverPid, $process)
    {
        $this->_process = $process;

        if ($pid = $this->_process->getMasterPid($serverPid) === false) {
            throw new Exception("Process#$serverPid not found.");
        }

        $this->pid = $pid;

        $this->inotify = inotify_init();
        $this->events  = IN_MODIFY | IN_DELETE | IN_CREATE | IN_MOVE;

        swoole_event_add($this->inotify, function ($ifd) {
            $events = inotify_read($this->inotify);
            if (!$events) {
                return;
            }
            // var_dump($events);
            foreach ($events as $ev) {
                if ($ev['mask'] == IN_IGNORED) {
                    continue;
                } else if ($ev['mask'] == IN_CREATE or $ev['mask'] == IN_DELETE or $ev['mask'] == IN_MODIFY or $ev['mask'] == IN_MOVED_TO or $ev['mask'] == IN_MOVED_FROM) {
                    $fileType = strrchr($ev['name'], '.');
                    //非重启类型
                    if (!isset($this->reloadFileTypes[$fileType])) {
                        continue;
                    }
                }
                //正在reload，不再接受任何事件，冻结1秒
                if (!$this->reloading) {
                    //有事件发生了，进行重启
                    swoole_timer_after($this->after_n_seconds * 1000, [$this, 'reload']);
                    $this->reloading = true;
                }
            }
        });
    }

    public function reload()
    {
        //向主进程发送信号
        $this->_process->kill($this->pid, SIGUSR1);
        //清理所有监听
        $this->clearWatch();
        //重新监听
        foreach ($this->rootDirs as $root) {
            $this->watch($root);
        }
        //继续进行reload
        $this->reloading = false;
    }

    /**
     * 添加文件类型
     * @param $type
     */
    public function addFileType($type)
    {
        $type = trim($type, '.');
        $this->reload_file_types['.' . $type] = true;
    }

    /**
     * 添加事件
     * @param $inotifyEvent
     */
    public function addEvent($inotifyEvent)
    {
        $this->events |= $inotifyEvent;
    }

    /**
     * 清理所有inotify监听
     */
    public function clearWatch()
    {
        foreach ($this->watch_files as $wd) {
            inotify_rm_watch($this->inotify, $wd);
        }
        $this->watch_files = [];
    }

    /**
     * @param $dir
     * @param bool $root
     * @return bool
     * @throws Exception
     */
    public function watch($dir, $root = true)
    {
        //目录不存在
        if (!is_dir($dir)) {
            throw new Exception("[$dir] is not a directory.");
        }
        //避免重复监听
        if (isset($this->watchFiles[$dir])) {
            return false;
        }
        //根目录
        if ($root) {
            $this->rootDirs[] = $dir;
        }

        $wd = inotify_add_watch($this->inotify, $dir, $this->events);
        $this->watch_files[$dir] = $wd;

        $files = scandir($dir);
        foreach ($files as $f) {
            if ($f == '.' or $f == '..') {
                continue;
            }
            $path = $dir . '/' . $f;
            //递归目录
            if (is_dir($path)) {
                $this->watch($path, false);
            }
            //检测文件类型
            $fileType = strrchr($f, '.');
            if (isset($this->reloadFileTypes[$fileType])) {
                $wd = inotify_add_watch($this->inotify, $path, $this->events);
                $this->watch_files[$path] = $wd;
            }
        }
        return true;
    }

    public function run()
    {
        swoole_event_wait();
    }

}