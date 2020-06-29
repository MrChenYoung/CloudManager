<?php

// 日志文件路径
$logPath = $argv[1];
// mysql数据库信息
$mysqlOption = json_decode($argv[2],true);
// 链接数据库
$mysqli = new \mysqli($mysqlOption['host'],$mysqlOption['user'],$mysqlOption['pass'],$mysqlOption['dbname'],$mysqlOption['port']);

if ($mysqli -> connect_error){
    addLog($logPath,"数据库连接失败：".$mysqli -> connect_error);
}else {
    addLog($logPath,"数据库链接成功");
}

// 添加log
function addLog($path,$content){
    // 获取当前时间
    date_default_timezone_set('PRC');
    $time = date('Y-m-d H:i:s', time());
    $content = "[$time]".$content."\r\n";
    file_put_contents($path,$content,FILE_APPEND);
}

