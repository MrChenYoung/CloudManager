<?php

namespace admin\controller;

use framework\core\Controller;
use framework\tools\DatabaseDataManager;
use framework\tools\LogManager;
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
        // 清空log
        LogManager::getSingleton()->clearLog();

        // 更新单个云盘还是一件更新所有云盘
        if (isset($_REQUEST["drivers"])){
            // 更新所有
            $drivers = $_REQUEST["drivers"];
            $drivers = explode(",",$drivers);
            if (count($drivers) > 0){
                DatabaseDataManager::getSingleton()->update("driver_setting",["status"=>1],["flag"=>"updatingDirTree"]);
                $index = 1;
                $driverCount = count($drivers);
                foreach ($drivers as $driver) {
                    LogManager::getSingleton()->addLog("正在更新第".$index."/".$driverCount."个云盘目录树...");
                    $this->updateSingleDriver($driver);
                }
                // 更新完成，恢复数据库标志位
                DatabaseDataManager::getSingleton()->update("driver_setting",["status"=>0],["flag"=>"updatingDirTree"]);
            }
        }else if (isset($_REQUEST["name"])){
            // 更新单个
            $remoteName = $_REQUEST["name"];
            if (strlen($remoteName) > 0){
                // 设置数据库正在更新目录树标志位为1
                DatabaseDataManager::getSingleton()->update("driver_setting",["status"=>1],["flag"=>"updatingDirTree"]);
                $this->updateSingleDriver($remoteName);
                // 更新完成，恢复数据库标志位
                DatabaseDataManager::getSingleton()->update("driver_setting",["status"=>0],["flag"=>"updatingDirTree"]);
            }
        }
        LogManager::getSingleton()->addLog("更新目录树完成。");
    }

    // 更新单个云盘
    private function updateSingleDriver($remoteName){
        // 获取云盘文件夹树形列表
        $dirData = $this->updateDirCache($remoteName);
        if (!$dirData){
            // 获取失败
            return false;
        }

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
    }

    // 更新云盘文件夹列表缓存
    private function updateDirCache($remoteName,$path=""){
        // rclone命令获取文件列表信息
        $cmd = "rclone lsd ".$remoteName.":".$path;

        LogManager::getSingleton()->addLog("获取目录:".$remoteName.":".$path);

        $res = ShellManager::exec($cmd);
        if (!$res["success"]){
            LogManager::getSingleton()->addLog("获取目录失败:".$remoteName.":".$path);
            LogManager::getSingleton()->addLog("错误信息:".json_encode($res["result"]));
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

    // 文件转存
    public function fileTransfer(){
        // 资源id
        if (!isset($_REQUEST["sourceId"])) die;
        $sourceId = $_REQUEST["sourceId"];
        // 存储路径
        if (!isset($_REQUEST["savePath"])) die;
        $savePath = $_REQUEST["savePath"];

        // 清空日志
        LogManager::getSingleton()->clearLog();
        LogManager::getSingleton()->addLog("开始转存文件...");

        $cmd = 'gclone copy GDSuiteTeam:{'.$sourceId."} GDSuiteTeam:".$savePath." --drive-server-side-across-configs -P >> ".LogManager::getSingleton()->logFilePath." 2>&1";
        $res = ShellManager::exec($cmd);
        if (!$res["success"]){
            LogManager::getSingleton()->addLog("文件转存失败");
        }
        LogManager::getSingleton()->addLog("文件转存完成");
        // 转存完成 修改数据库标识
        DatabaseDataManager::getSingleton()->update("file_transfer_info",["status"=>'0'],["id"=>1]);
    }
}