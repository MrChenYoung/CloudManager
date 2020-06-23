<?php


namespace admin\controller\API;


use framework\tools\ShellManager;

class API_FileManagerController extends API_BaseController
{
    // 获取云盘列表
    public function loadDriverList(){
        // 把rclone配置文件打包成json
        $cmd = "rclone config dump";
        $res = ShellManager::exec($cmd);
        if (!$res["success"]){
            echo $this->failed("获取云盘列表失败");
            die;
        }

        $driverList = $res["result"];
        $driverList = implode("",$driverList);
        $driverList = json_decode($driverList,true);
        $data = [];
        foreach ($driverList as $key=>$driver) {
            $type = "";
            switch ($driver["type"]){
                case "drive":
                    $type = "谷歌云盘";
                    break;
                case "onedrive":
                    $type = "微软oneDriver";
                    break;
                default:
                    $type = "未知";
                    break;
            }
            $data[$type][] = $key;
        }

        echo $this->success($data);
    }

    // 获取文件列表
    public function loadFileList(){
        // 远程云盘名
        if (!isset($_GET["remoteName"])){
            echo $this->failed("缺少remoteName参数");
            die;
        }
        $remoteName = $_GET["remoteName"];

        // 路径
        if (!isset($_GET["path"])){
            echo $this->failed("缺少path参数");
            die;
        }
        $path = $_GET["path"];

        // rclone命令获取文件列表信息
        $cmd = "rclone lsjson ".$remoteName.":".$path;
        $res = ShellManager::exec($cmd);
        if (!$res["success"]){
            echo $this->failed("获取文件列表失败");
            die;
        }

        $fileList = $res["result"];
        $fileList = implode("",$fileList);
        $fileList = json_decode($fileList,true);
        foreach ($fileList as $key=>$file) {
            // 时间转换
            $timeStr = $file["ModTime"];
            date_default_timezone_set('Asia/Shanghai');
            $timeStr = date('Y-m-d H:i:s',strtotime($timeStr));
            $fileList[$key]["ModTime"] = $timeStr;

            // 文件大小
            $fileSize = $file["Size"];
            // 文件数量
            $fileCount = 1;
            if ($file["IsDir"]){
                $getSizeCmd = "rclone size ".$remoteName.":".$path;
                $sizeRes = ShellManager::exec($getSizeCmd);
                if (!$sizeRes["success"]){
                    // 获取文件大小失败
                    $fileSize = "未知";
                    $fileCount = 0;
                }else {
                    $sizeRes = $sizeRes["result"];
                    $fileCount = trim(str_replace("Total objects:","",$sizeRes[0]));
                    preg_match("/\((.*)\)/",$sizeRes[1],$match);
                    if (count($match) > 1){
                        $fileSize = $match[1];
                        $fileSize = trim(str_replace("Bytes","",$fileSize));
                    }

                    $fileSize = $this->formatBytes($fileSize);
                }
            }else if ($fileSize >= 0){
                $fileSize = $this->formatBytes($fileSize);
            }else {
                $fileSize = "未知";
            }
            $fileList[$key]["Size"] = $fileSize;
            $fileList[$key]["Count"] = $fileCount;
        }

        echo $this->success($fileList);
    }
}