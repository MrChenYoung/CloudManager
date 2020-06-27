<?php


namespace admin\controller\API;


use admin\controller\AsynTaskController;
use framework\tools\DatabaseDataManager;
use framework\tools\MultiThreadTool;
use framework\tools\ShellManager;

class API_SettingController extends API_BaseController
{
    // 加载设置选项
    public function loadSetting(){
        $res = DatabaseDataManager::getSingleton()->find("driver_setting");
        if ($res){
            echo $this->success($res);
        }else {
            echo $this->failed("加载设置失败");
        }
    }

    // 更改设置
    public function changeSetting(){
        // 标志
        if (!isset($_GET["flag"])){
            echo $this->failed("缺少flag参数");
            die;
        }
        $flag = $_GET["flag"];

        // 状态
        if (!isset($_GET["status"])){
            echo $this->failed("缺少status参数");
            die;
        }
        $status = $_GET["status"];
        $res = DatabaseDataManager::getSingleton()->update("driver_setting",["status"=>$status],["flag"=>$flag]);
        if ($res){
            echo $this->success($res);
        }else {
            echo $this->failed("更改设置失败");
        }
    }

    // 获取云盘文件夹缓存
    public function loadDriveDirCache(){
        // 远程云盘名
        if (!isset($_GET["remoteName"])){
            echo $this->failed("缺少remoteName参数");
            die;
        }
        $remoteName = $_GET["remoteName"];

        $path = ADMIN."resource/driveDirTreeCache/".$remoteName.".json";
        $data = [];
        if (file_exists($path)){
            // 有缓存
            $str = file_get_contents($path);
            $data = json_decode($str);
        }

        // 获取目录树更新状态
        $res = DatabaseDataManager::getSingleton()->find("driver_setting",["flag"=>"updatingDirTree"]);
        $status = 0;
        if ($res){
            $status = (int)$res[0]["status"];
        }
        echo $this->success(["status"=>$status,"data"=>$data]);
    }

    // 更新云盘目录树缓存
    public function updateDriveDirList(){
        // 远程云盘名
        if (!isset($_GET["remoteName"])){
            echo $this->failed("缺少remoteName参数");
            die;
        }
        $remoteName = $_GET["remoteName"];

        // 因为更新云盘目录树需要很长时间，所以使用异步执行
        // 添加异步任务
        $params = [
            "m"=>"admin",
            "c"=>"AsynTask",
            "a"=>"index",
            "name"=>$remoteName
        ];
        MultiThreadTool::addTask($this->website."/index.php","updateDriveDirList",$params);
        // 提示正在后台更新
        echo $this->success("目录树后台更新中");
    }

    // 一键更新所有云盘目录树缓存
    public function updataAllDriverDirCache(){
        // 获取云盘列表
        $driverList = (new API_FileManagerController())->loadDriverList(true);
        $drivers = [];
        foreach ($driverList as $ds) {
            foreach ($ds as $d) {
                $drivers[] = $d;
            }

        }
        $drivers = implode(",",$drivers);

        // 后台更新
        $params = [
            "m"=>"admin",
            "c"=>"AsynTask",
            "a"=>"index",
            "drivers"=>$drivers
        ];
        MultiThreadTool::addTask($this->website."/index.php","updateDriveDirList",$params);
        // 提示正在后台更新
        echo $this->success("目录树后台更新中");
    }

    // 复位目录树更新标志位
    public function resetFlag() {
        $res = DatabaseDataManager::getSingleton()->update("driver_setting",["status"=>0],["flag"=>"updatingDirTree"]);
        if ($res){
            echo $this->success("");
        }else {
            echo $this->failed("");
        }
    }

}