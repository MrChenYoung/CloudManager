<?php

// 日志文件路径
$logPath = $argv[1];
// 共享资源id
$sourceId = $argv[2];
// 保存到的云盘名
$driverName =  $argv[3];

// 执行gclone计算文件大小
//$cmd = "gclone ls $driverName:"."{".$sourceId."}";
$cmd = "gclone ls code007:/";
$res = myshellExec($cmd);

addLog($logPath,"结果:".json_encode($res));
die;
if (!$res["success"]){
    addLog($logPath,"获取文件列表失败");
}else {
    $fileList = $res["result"];
    $count = count($fileList);
    $count = $count>=10000 ? $count/10000 .'w' : $count;
    $size = 0;
    foreach ($fileList as $fileDes) {
        // 多个空格合并为一个空格
        $patt = '/\s{1,}/';
        $fileDes = preg_replace($patt,' ',$fileDes);
        // 去掉首尾空格
        $fileDes = trim($fileDes);
        // 转换成数组
        $fileDesArray = explode(" ",$fileDes);
        $fileSize = $fileDesArray[0];
        $filePath = $fileDesArray[1];
        addLog($logPath,"共<".$count.">个文件");
        addLog($logPath,"当前计算位置:".$filePath);
        $size += (int)$fileSize;
    }

    // 总大小格式化
    $size = formatBytes($size);
    addLog($logPath,"计算完成,大小为:".$size);
}

addLog($logPath,"计算结束");

// 添加log
function addLog($path,$content){
    // 获取当前时间
    date_default_timezone_set('PRC');
    $time = date('Y-m-d H:i:s', time());
    $content = "[$time]".$content."\r\n";
    file_put_contents($path,$content,FILE_APPEND);
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

/**
 *  字节格式化成B KB MB GB TB
 * @param $size 字节大小
 * @return string 格式化后的结果
 */
function formatBytes($size) {
    $units = array(' B', ' KB', ' MB', ' GB', ' TB');
    for ($i = 0; $size >= 1024 && $i < 4; $i++) {
        $size /= 1024;
    }
    return round($size, 2).$units[$i];
}