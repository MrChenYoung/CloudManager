<?php


namespace framework\tools;

class MultiThreadTool
{
    // 添加任务
    public static function addTask($url,$action,$param=[]){
        $param["API"] = "";
        $param["action"] = $action;
        self::doRequest($url, $param);
        // 为了使脚本完整执行，不会中断执行，需要配置：
        //1. 在nginx.conf添加fastcgi_intercept_errors on;配置
        //2. 设置php.ini里max_execution_time值为更大(默认30,表示脚本最长执行时长为30s)，或者设为0，表示时长不限
        //3. 设置以下两行代码
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

        if (!$fp) {
            echo "添加任务失败";
            echo "$errstr ($errno)<br />\n";
            die;
        }

        $out = "POST ".$path." HTTP/1.1\r\n";
        $out .= "host:".$host."\r\n";
        $out .= "content-length:".strlen($query)."\r\n";
        $out .= "content-type:application/x-www-form-urlencoded\r\n";
        $out .= "connection:close\r\n\r\n";
        $out .= $query;

        fputs($fp, $out);
        usleep(20000);
        fclose($fp);
    }
}