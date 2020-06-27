<?php


namespace framework\tools;


class LogManager
{
    // 单利对象
    private static $instance;
    // 运行日志文件路径
    public $logFilePath;

    private function __construct()
    {
        // 创建日志文件
        $logDir = ROOT."Logs";
        if (!file_exists($logDir)){
            mkdir($logDir);
        }
        $this -> logFilePath = $logDir."/log.txt";
        if (!file_exists($this -> logFilePath)){
            $this -> clearLog();
        }
    }

    private function __clone()
    {
    }

    public static function getSingleton(){
        if(!self::$instance instanceof self){
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 添加运行日志
     * @param $content 日志内容
     */
    public function addLog($content){
        // 获取当前时间
        date_default_timezone_set('PRC');
        $time = date('Y-m-d H:i:s', time());
        $content = "[$time]".$content."\r\n";
        $res = file_put_contents($this->logFilePath,$content,FILE_APPEND);
    }

    /**
     * 清空log
     */
    public function clearLog(){
        file_put_contents($this -> logFilePath,"");
    }


    /**
     * 获取日志文件的md5值 用于监测日志文件是否发生改变
     * @return false|string
     */
    public function getLogFileMd5(){
        return md5_file($this -> logFilePath);
    }

    /**
     * 获取日志文件内容
     * @return false|string
     */
    public function getLogContents(){
        $logUrl = "http://".$_SERVER['HTTP_HOST']."/LoveMovie/Logs/log.txt";

        // 请求Log信息
        $request = new HttpRequest();
        $request -> url = $logUrl;
        $returnCode = $request -> reachable();

        if ($returnCode == 200){
            // 文件存在
            $logContent = file_get_contents($logUrl);
            $logContent = str_replace("\r\n","<br>",$logContent);
            return $logContent;
        }else {
            // 文件不存在
            return "log文件不存在";
        }
    }
}