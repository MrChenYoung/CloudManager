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

    // 文件转存
    public function fileTransfer(){
        // 资源id
        if (!isset($_REQUEST["sourceId"])) die;
        $sourceId = $_REQUEST["sourceId"];
        // 存储路径
        if (!isset($_REQUEST["savePath"])) die;
        $savePath = $_REQUEST["savePath"];
        // 云盘名
        if (!isset($_REQUEST["driverName"])) die;
        $driverName = $_REQUEST["driverName"];

        // 清空日志
        LogManager::getSingleton()->clearLog();
        LogManager::getSingleton()->addLog("开始转存文件：".$savePath);

        $cmd = "gclone copy $driverName:"."{".$sourceId."}"." ".$driverName.":$savePath --drive-server-side-across-configs -P >> ".LogManager::getSingleton()->logFilePath." 2>&1";
        $res = ShellManager::exec($cmd);
        if (!$res["success"]){
            LogManager::getSingleton()->addLog("文件转存失败");
        }
        LogManager::getSingleton()->addLog("文件转存完成");
        // 转存完成 修改数据库标识
        DatabaseDataManager::getSingleton()->update("file_transfer_info",["status"=>'0'],["id"=>1]);
    }

    // 移动文件
    public function moveFile(){
        // 源云盘
        if (!isset($_REQUEST["sourcedriver"])) die;
        $sourcedriver = $_REQUEST["sourcedriver"];
        // 源路径
        if (!isset($_REQUEST["sourcePath"])) die;
        $sourcePath = $_REQUEST["sourcePath"];
        // 目标云盘
        if (!isset($_REQUEST["desdriver"])) die;
        $desdriver = $_REQUEST["desdriver"];
        // 目标路劲
        if (!isset($_REQUEST["desPath"])) die;
        $desPath = $_REQUEST["desPath"];

        $cmd = "php ".ADMIN."controller/MoveFileController.class.php 123";
        $res = ShellManager::exec($cmd);
        if (!$res["success"]){
            LogManager::getSingleton()->addLog("执行测试php脚本失败");
        }else {
            LogManager::getSingleton()->addLog("执行测试php脚本成功");
        }

        // 添加到数据库记录
//        DatabaseDataManager::getSingleton()->insert("file_move_info",["source_path"=>$sourcePath,"des_path"=>$desPath]);
//
//        // 添加日志
//        LogManager::getSingleton()->clearLog();
//        LogManager::getSingleton()->addLog("移动".$sourcePath."到".$desPath);
//        if ($sourcedriver == $desdriver){
//            $cmd = "rclone moveto ".$sourcePath." ".$desPath." --drive-server-side-across-configs -P >> ".LogManager::getSingleton()->logFilePath." 2>&1";
//        }else {
//            $cmd = "rclone moveto ".$sourcePath." ".$desPath." -P >> ".LogManager::getSingleton()->logFilePath." 2>&1";
//        }
//
//        LogManager::getSingleton()->addLog("命令:".$cmd);
//        $res = ShellManager::exec($cmd);
//        if (!$res["success"]){
//            // 移动失败
//            LogManager::getSingleton()->addLog("文件移动失败");
//        }else {
//            // 移动成功
//            // 如果是跨云盘移动文件夹，原来的云盘里的文件夹会变成空，但是文件夹依然存在，需要再执行删除空文件夹命令
//            if ($sourcedriver != $desdriver){
//                ShellManager::exec("rclone purge ".$sourcePath);
//            }
//            LogManager::getSingleton()->addLog("文件移动成功");
//        }
//        // 从数据库记录删除
//        DatabaseDataManager::getSingleton()->delete("file_move_info");
    }
}