<?php


namespace admin\controller\API;

use framework\tools\DatabaseDataManager;
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

            // 获取大小
            $size = "--";
            // 备注
            $remark = "--";
            $remarkInfo = DatabaseDataManager::getSingleton()->find("driver_list",["driver_name"=>$key],["remark"]);
            if ($remarkInfo){
                $remark = $remarkInfo[0]["remark"];
            }
            // 文件数
            $count = "--";
            $data[] = [
                "name"  =>  $key,
                "type"  =>  $type,
                "size"  =>  $size,
                "count" =>  $count,
                "remark"=>  $remark
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
            // 删除数据库数据
            DatabaseDataManager::getSingleton()->delete('driver_list',['driver_name'=>$name]);
            echo $this->success("删除成功");
        }else {
            echo $this->failed("删除失败");
        }
    }

    // 重命名
    public function renameDrive(){
        if (!isset($_GET["oldName"])){
            echo $this->failed("缺少oldName参数");
            die;
        }
        $oldName = $_GET["oldName"];

        if (!isset($_GET["newName"])){
            echo $this->failed("缺少newName参数");
            die;
        }
        $newName = $_GET["newName"];

        $confPath = '/home/www/.config/rclone/rclone.conf';
        $confContent = file_get_contents($confPath);
        $confContent = str_replace("[".$oldName."]","[".$newName."]",$confContent);
        $res = file_put_contents($confPath,$confContent);
        if ($res){
            // 修改数据库内云盘名
            DatabaseDataManager::getSingleton()->update('driver_list',['driver_name'=>$newName],['driver_name'=>$oldName]);
            echo $this->success("重命名成功");
        }else {
            echo $this->failed("重命名失败");
        }
    }

    // 修改备注
    public function modifyRemark(){
        // 云盘名
        if (!isset($_GET["driverName"])){
            echo $this->failed("缺少driverName参数");
            die;
        }
        $driverName = $_GET["driverName"];

        // 备注
        if (!isset($_GET["remark"])){
            echo $this->failed("缺少remark参数");
            die;
        }
        $remark = $_GET["remark"];

        // 先查询是否有该云盘记录，如果没有，先记录到数据库，再更改备注
        $remarkInfo = DatabaseDataManager::getSingleton()->find("driver_list",["driver_name"=>$driverName]);
        $res = false;
        if (!$remarkInfo){
            $res = DatabaseDataManager::getSingleton()->insert("driver_list",["driver_name"=>$driverName,"remark"=>$remark]);
        }else {
            $res = DatabaseDataManager::getSingleton()->update("driver_list",["remark"=>$remark],["driver_name"=>$driverName]);
        }

        if ($res){
            echo $this->success("备注修改成功");
        }else {
            echo $this->failed("备注修改失败");
        }
    }

    // 获取云盘所有文件大小和文件总数量
    public function loadDriverDetailInfo(){
        // 云盘名
        if (!isset($_GET["driverName"])){
            echo $this->failed("缺少driverName参数");
            die;
        }
        $driverName = $_GET["driverName"];

        $res = $this->loadDetaileInfo($driverName,"/");
        echo $this->success($res);
    }
}