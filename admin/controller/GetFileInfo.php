<?php

// 日志文件路径
$logPath = $argv[1];
// 路径
$path = $argv[2];

addLog($logPath,"开始获取文件详细信息:".$path);
$cmd = "rclone size ".$path;
$res = myshellExec($cmd);
if ($res["success"]){
    addLog($logPath,"获取文件详情成功,详情如下：");
    addLog($logPath,$res["result"]);
}else {
    addLog($logPath,"获取文件详情失败");
}

// 执行shell脚本
function myshellExec($mycmd){
    exec($mycmd.' 2>&1',$result,$status);
    $success = $status == 0 ? true : false;
    return [
        "success"   =>  $success,
        "result"    =>  $result
    ];
}

// 添加log
function addLog($path,$content){
    // 获取当前时间
    date_default_timezone_set('PRC');
    $time = date('Y-m-d H:i:s', time());
    $content = "[$time]".$content."\r\n";
    file_put_contents($path,$content,FILE_APPEND);
}