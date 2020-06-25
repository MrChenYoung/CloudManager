<?php

namespace admin\controller;

use framework\core\Controller;
use framework\tools\DatabaseDataManager;
use framework\tools\ShellManager;

class AsynTaskController extends Controller
{
    public function index()
    {
        parent::index();

        // 要调用哪个方法
        if (!isset($_POST["action"])){
            die;
        }
        $action = $_POST["action"];

        // 执行指定方法
        if ($action){
            $this->$action();
        }
    }

    // 更新云盘文件夹树形列表缓存
    public function updateDriveDirList(){
        $path = "/www/wwwroot/cloudmanager.yycode.ml/test.txt";
        file_put_contents($path,"");
        file_put_contents($path,"进入预想方法123456666:".json_encode($_REQUEST));
        $remoteName = $_REQUEST["name"];
        // 获取云盘文件夹树形列表
        $dirData = $this->updateDirCache($remoteName);
        if (!$dirData){
            // 获取失败
            return false;
        }

        // 设置数据库正在更新目录树标志位为1
        DatabaseDataManager::getSingleton()->update("driver_setting",["status"=>1],["flag"=>"updatingDirTree"]);
        $dirData = [["title"=>"根目录","children"=>$dirData]];
        // 更新缓存文件
        // 存放缓存的目录
        $cacheRootPath = ADMIN."resource/driveDirTreeCache/";
        if (!file_exists($cacheRootPath)){
            mkdir($cacheRootPath);
            chmod($cacheRootPath,0700);
        }
        $path = $cacheRootPath.$remoteName.".json";
        file_put_contents($path,json_encode($dirData));
        // 更新完成，恢复数据库标志位
//        DatabaseDataManager::getSingleton()->update("driver_setting",["status"=>0],["flag"=>"updatingDirTree"]);
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