<?php


namespace framework\tools;

class MultiThreadTool
{
    // 添加任务
    public static function addTask($url,$action,$param=[]){
        $param["API"] = "";
        $param["action"] = $action;
        echo "<pre>";
        var_dump($url);
        var_dump($param);
        die;
        self::doRequest($url, $param);
        ignore_user_abort(true); // 忽略客户端断开
        set_time_limit(0);    // 设置执行不超时
    }

    // 发送请求
    private static function doRequest($url, $param=array()){
        $urlinfo = parse_url($url);

        $host = $urlinfo['host'];
        $path = $urlinfo['path'];
        $query = isset($param)? http_build_query($param) : '';

        $port = 80;
        $errno = 0;
        $errstr = '';
        $timeout = 10;

        $fp = fsockopen($host, $port, $errno, $errstr, $timeout);

        $out = "POST ".$path." HTTP/1.1\r\n";
        $out .= "host:".$host."\r\n";
        $out .= "content-length:".strlen($query)."\r\n";
        $out .= "content-type:application/x-www-form-urlencoded\r\n";
        $out .= "connection:close\r\n\r\n";
        $out .= $query;

        fputs($fp, $out);
        fclose($fp);
    }
}