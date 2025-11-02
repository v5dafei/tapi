<?php

namespace App\Utils\Socket;

/**
 * 服务代码热重载
 * Class InotifyReload
 */
Class InotifyReload
{
    // 需要监听的目录
    public $watchDirs  = [];
    // 需要监听的文件
    public $watchFiles = [];
    // 已监听的资源列表
    public $listenList = [];

    public $watchFd;
    public $swooleServer;
    public $fileType = [ 'php', 'ini' ];

    /**
     * @title inotify - 初始化
     * @param $server
     * @throws \Exception
     */
    public function __construct ( $server ) {
        if ( !extension_loaded('inotify') ) {
            throw new \Exception("请安装：inotify 扩展");
        }
        echo "\r\n" . '已开启代码热重载...' . "\r\n";

        $this->swooleServer = $server;

        # 初始化句柄
        $this->watchFd = inotify_init();
    }

    /**
     * @title  设置监听的目录列表
     * @param array $dirs
     * @return $this
     * @author benjamin
     */
    public function addDirs ( array $dirs ) {
        if ( !empty($dirs) ) {
            $this->watchDirs = array_merge($this->watchDirs, $dirs);
//            consoleLog('监听目录 >>>>>>>> ', $this->watchDirs);
        }
        return $this;
    }

    /**
     * @title  设置监听的文件列表
     * @param array $files
     * @return $this
     * @author benjamin
     */
    public function addFiles ( array $files ) {
        if ( !empty($files) ) {
            $this->watchFiles = array_merge($this->watchFiles, $files);
//            consoleLog('监听文件 >>>>>>>> ', $this->watchFiles);
        }
        return $this;
    }

//    public function addDir ( $path, $mask = DIRWATCHER_CHANGED ) {
//        $key = md5($path);
//        if ( !isset($this->watchDirs[$key]) ) {
//            $wd                    = inotify_add_watch($this->watchFd, $path, $mask);
//            $this->watchDirs[$key] = array(
//                'wd'   => $wd,
//                'path' => $path,
//                'mask' => $mask,
//            );
//        }
//        return $this;
//    }

    /**
     * @title  监听者
     * @author benjamin
     */
    public function watch () {
        global $listenList;

        # 设置非阻塞模式
        stream_set_blocking($this->watchFd, 0);

        # 监听目录下每个文件
        foreach ( $this->watchDirs as $dir ) {
            $dir_iterator = new \RecursiveDirectoryIterator($dir);
            $iterator     = new \RecursiveIteratorIterator($dir_iterator);
            foreach ( $iterator as $file ) {
                if ( !in_array(pathinfo($file, PATHINFO_EXTENSION), $this->fileType) ) {
                    continue;
                }
                $wd              = inotify_add_watch($this->watchFd, $file, IN_MODIFY);
                $listenList[$wd] = $file;
            }
        }

        # 监听指定单个文件列表
        foreach ( $this->watchFiles as $file ) {
            if ( !in_array(pathinfo($file, PATHINFO_EXTENSION), $this->fileType) ) {
                continue;
            }
            $wd              = inotify_add_watch($this->watchFd, $file, IN_MODIFY);
            $listenList[$wd] = $file;
        }


        # 监控 Inotify 句柄可读事件
        swoole_event_add($this->watchFd, function ( $inotify_fd ) {
            global $listenList;
            $events = inotify_read($inotify_fd);
            if ( $events ) {
                foreach ( $events as $ev ) {
                    $file = $listenList[$ev['wd']];
                    $time = date('Y-m-d H:i:s');
                    echo "\r\n";
                    echo($file . "- fileWd:{$ev['wd']}" . "，time：{$time}" . "- update\n");
                    unset($listenList[$ev['wd']]);
                    $wd              = inotify_add_watch($inotify_fd, $file, IN_MODIFY);
                    $listenList[$wd] = $file;
                }

                # 开始Swoole文件重载
                $this->swooleServer->reload();
            }
        }, null, SWOOLE_EVENT_READ);
    }

}