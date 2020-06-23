<?php


namespace admin\controller\API;

use framework\tools\ShellManager;

class API_MyDriverController extends API_BaseController
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
            $data[] = [
                "name"  =>  $key,
                "type"  =>  $type
            ];
        }

        echo $this->success($data);
    }

    // 删除云盘
    public function deleteDrive(){
        if (!isset($_GET["name"])){
            echo $this->failed("缺少name参数");
            die;
        }
        $name = $_GET["name"];
        // rclone删除命令
        $cmd = "rclone config delete ".$name;
        $res = ShellManager::exec($cmd);
        if ($res["success"]){
            echo $this->success("删除成功");
        }else {
            echo $this->failed("删除失败");
        }
    }
}