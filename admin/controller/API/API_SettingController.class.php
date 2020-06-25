<?php


namespace admin\controller\API;


use framework\tools\DatabaseDataManager;
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
        echo $this->success($data);
    }

    // 更新云盘文件夹树形列表缓存
    public function updateDriveDirList(){
        // 远程云盘名
        if (!isset($_GET["remoteName"])){
            echo $this->failed("缺少remoteName参数");
            die;
        }
        $remoteName = $_GET["remoteName"];

        // 获取文件夹列表
        $dirData = $this->updateDirCache($remoteName);
        if (!$dirData){
            echo $this->failed("更新失败");
            die;
        }

        echo $this->success($dirData);
    }

    // 更新云盘文件夹列表缓存
    private function updateDirCache($remoteName,$path=""){
        // rclone命令获取文件列表信息
        $cmd = "rclone lsd ".$remoteName.":".$path;
        $res = ShellManager::exec($cmd);
        if (!$res["success"]){
            return false;
        }

        $dirList = $res["result"];
        $data = [];
        foreach ($dirList as $dir) {
            $dirArray = explode("-1",$dir);
            $dirName = trim($dirArray[count($dirArray)-1]);
            $dirData = [];
            if (count($dirArray) > 0){
                $dirData['title'] = $dirName;
            }

            // 获取子目录
           $subDirData = $this->updateDirCache($remoteName,$path."/".$dirName);
            if ($subDirData && count($subDirData) > 0){
                $dirData["children"] = $subDirData;
            }

            $data[] = $dirData;
        }

        return $data;
    }

}