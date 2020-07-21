<?php


namespace admin\controller\API;

use framework\tools\DatabaseDataManager;
use framework\tools\FileManager;
use framework\tools\MultiThreadTool;
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

        // 数据库查询云盘信息
        $driveData = [];
        $driveInfo = DatabaseDataManager::getSingleton()->find("driver_list");
        
        if ($driveInfo){
            foreach ($driveInfo as $drive) {
                $driveName = $drive["driver_name"];
                $driveData[$driveName] = $drive;
            }
        }

        $driverList = $res["result"];
        $driverList = implode("",$driverList);
        $driverList = json_decode($driverList,true);
        $data = [];
        $sortData = [];
        foreach ($driverList as $key=>$driver) {
            $type = "";
            switch ($driver["type"]){
                case "drive":
                    if (key_exists("team_drive",$driver)){
                        $type = "谷歌团队盘";
                    }else {
                        $type = "谷歌云盘";
                    }
                    break;
                case "onedrive":
                    $type = "微软oneDriver";
                    break;
                default:
                    $type = "未知";
                    break;
            }

            // 如果云盘信息记录不存在  插入记录
            $fileInfoExist = DatabaseDataManager::getSingleton()->find("driver_list",["driver_name"=>$key]);
            if (!$fileInfoExist){
                DatabaseDataManager::getSingleton()->insert("driver_list",["driver_name"=>$key]);
            }

            // 额外信息
            $sort = 0;
            $mainAdmin = "--";
            $memberCount = "--";
            $remark = "--";
            // 已使用空间大小
            $size = "--";
            // 总文件数
            $count = "--";
            if (key_exists($key,$driveData)){
                $dData = $driveData[$key];
                $mainAdmin = $dData["main_admin"];
                $memberCount = $dData["member_count"];
                $remark = $dData["remark"];
                $size = $dData["used_space"];
                $count = $dData["file_count"];
                $sort = $dData["sort"];
            }


            $data[] = [
                "name"          =>  $key,
                "type"          =>  $type,
                "size"          =>  $size,
                "count"         =>  $count,
                "mainAdmin"     => $mainAdmin,
                "memberCount"   => $memberCount,
                "remark"        =>  $remark,
                "sort"          => $sort
            ];
            $sortData[] = $sort;
        }

        // 按照sort排序
        array_multisort($sortData, SORT_ASC, $data);

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

    // 修改云盘信息
    public function modifyDriveInfo(){
        // 云盘名
        if (!isset($_GET["driverName"])){
            echo $this->failed("缺少driverName参数");
            die;
        }
        $driverName = $_GET["driverName"];

        // 主管理员
        if (!isset($_GET["mainAdmin"])){
            echo $this->failed("缺少mainAdmin参数");
            die;
        }
        $mainAdmin = $_GET["mainAdmin"];

        // 成员数量
        if (!isset($_GET["memberCount"])){
            echo $this->failed("缺少memberCount参数");
            die;
        }
        $memberCount = (int)$_GET["memberCount"];
        // 每个成员每天限制拷贝750G数据
        $memberCount = $memberCount."(".FileManager::formatBytes($memberCount * 750 * 1024 * 1024 * 1024).")";

        // 备注
        if (!isset($_GET["remark"])){
            echo $this->failed("缺少remark参数");
            die;
        }
        $remark = $_GET["remark"];

        // 排序
        if (!isset($_GET["sort"])){
            echo $this->failed("缺少sort参数");
            die;
        }
        $sort = $_GET["sort"];

        $data = [
            "driver_name"=>$driverName,
            "main_admin"    =>  $mainAdmin,
            "member_count"  =>  $memberCount,
            "remark"=>$remark,
            "sort" => $sort
        ];

        // 先查询是否有该云盘记录
        $remarkInfo = DatabaseDataManager::getSingleton()->find("driver_list",["driver_name"=>$driverName]);
        $res = false;
        if (!$remarkInfo){
            $res = DatabaseDataManager::getSingleton()->insert("driver_list",$data);
        }else {
            $res = DatabaseDataManager::getSingleton()->update("driver_list",$data,["driver_name"=>$driverName]);
        }

        if ($res){
            echo $this->success("修改成功");
        }else {
            echo $this->failed("修改失败");
        }
    }

    // 获取云盘所有文件大小和文件总数量
    public function loadFileDetailInfo(){
        // 云盘名
        if (!isset($_GET["driverName"])){
            echo $this->failed("缺少driverName参数");
            die;
        }
        $driverName = $_GET["driverName"];

        $this->updateUsedInfo($driverName);

        // 提示正在后台获取中
        echo $this->success("正在后台更新".$driverName."云盘使用详情");
    }

    // 更新所有盘使用详情
    public function updateAllDriveUsedInfo() {
        $this->updateUsedInfo("");

        // 提示正在后台获取中
        echo $this->success("正在后台更新所有云盘使用详情");
    }

    // 后台更新云盘使用详细信息
    private function updateUsedInfo($driverName){
        // 后台更新使用详情
        $params = [
            "m"=>"admin",
            "c"=>"AsynTask",
            "a"=>"index",
            "driverName"=>$driverName
        ];

        MultiThreadTool::addTask($this->website."/index.php","getDriveUsedSpace",$params);
    }
}

