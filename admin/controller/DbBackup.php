<?php

require_once "framework/tools/DatabaseBackupManager.class.php";

// 日志文件路径
$logPath = $argv[1];
// mysql数据库信息
$mysqlOption = json_decode($argv[2],true);
$dbName = $mysqlOption['dbname'];
// 本地数据库备份目录
$localBackupPath = $argv[3];
// 表名
$tbName = $argv[4];
$tbName = $tbName === "-1" ? "" : $tbName;
// 数据表备份文件夹
$tbDirName = strlen($tbName) > 0 ? $tbName : "all";
$path = $localBackupPath.$dbName."/".$tbDirName. "/";
// 备份类文件位置
$backupManagerFilePath = $argv[5];
// 是否是备份所有数据库
$backupAll = $argv[6];
// 数据库列表
$dbList = json_decode($argv[7],true);

if ($backupAll == "1"){
    // 备份所有数据库
    foreach ($dbList as $item) {
        $path = $localBackupPath.$item."/all/";
        $mysqlOption["dbname"] = $item;
        backupDb($item,"",$path,$logPath,"all",$backupManagerFilePath,$mysqlOption);
    }
}else {
    // 备份当前数据库
    backupDb($dbName,$tbName,$path,$logPath,$tbDirName,$backupManagerFilePath,$mysqlOption);
}

addLog($logPath,"数据库备份完成");

// 备份指定数据库指定表
function backupDb($dataBase,$table="",$localTbPath,$logP,$tableDirName,$backupFilePath,$option){
    addLog($logP,"正在备份数据库:".$dataBase." 表:".$tableDirName);
    // 备份数据库到本地
    if(!file_exists($localTbPath)){
        mkdir($localTbPath);
        chmod($localTbPath,0700);
    }
    $res = (new \framework\tools\DatabaseBackupManager($option))->backup($table,$localTbPath);
    if (!$res){
        addLog($logP,"备份失败");
    }

    // 移动本地备份文件到谷歌云盘
    $cmd = "rclone lsjson GDSuite:我的数据/备份数据/db/";
    $checkDbDirRes = myshellExec($cmd);
    if ($checkDbDirRes["success"]){
        $fileList = $checkDbDirRes["result"];
        $fileList = implode("",$fileList);
        $fileList = json_decode($fileList,true);
        $exist = false;
        foreach ($fileList as $item) {
            if ($item["name"] == $dataBase){
                $exist = true;
                break;
            }
        }
        if (!$exist){
            $cmd = "rclone mkdir GDSuite:我的数据/备份数据/db/".$dataBase;
            $execRes = myshellExec($cmd);
            if (!$execRes["success"]){
                $cmd = "rm -f ".$localTbPath."*";
                myshellExec($cmd);

                addLog($logP,"创建".$dataBase."文件夹失败,备份终止");
                die;
            }
        }
    }
// 先清空目录内的历史数据
//        $cmd = "rclone delete GDSuite:我的数据/备份数据/db/".$dbName."/".$tbDirName;
//        ShellManager::exec($cmd);
// 移动备份文件
    $cmd = "rclone moveto ".$localTbPath." GDSuite:我的数据/备份数据/db/".$dataBase."/".$tableDirName;
    $moveRes = myshellExec($cmd);
    if (!$moveRes["success"]){
        addLog($logP,"备份".$tableDirName."表失败");
    }
    addLog($logP,"备份".$tableDirName."表完成");
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