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

        // 执行转存文件php脚本
        $cmd = "php ".ADMIN."controller/TransferFileController.class.php ".LogManager::getSingleton()->logFilePath." '".json_encode($GLOBALS["db_info"])."'";
        $cmd = $cmd." ".$sourceId." ".$driverName." ".$savePath;
        $res = ShellManager::exec($cmd);
        if (!$res["success"]){
            LogManager::getSingleton()->addLog("执行测试php脚本失败:".json_encode($res));
        }else {
            LogManager::getSingleton()->addLog("执行测试php脚本成功:".json_encode($res));
        }
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

        // 添加日志
        LogManager::getSingleton()->clearLog();
        LogManager::getSingleton()->addLog("移动".$sourcePath."到".$desPath);

        // 执行移动文件php脚本
        $cmd = "php ".ADMIN."controller/MoveFileController.class.php ".LogManager::getSingleton()->logFilePath." '".json_encode($GLOBALS["db_info"])."'";
        $cmd = $cmd." ".$sourcedriver." ".$sourcePath." ".$desdriver." ".$desPath;
        $res = ShellManager::exec($cmd);
        if (!$res["success"]){
            LogManager::getSingleton()->addLog("执行测试php脚本失败:".json_encode($res));
        }else {
            LogManager::getSingleton()->addLog("执行测试php脚本成功:".json_encode($res));
        }
    }

    // 计算共享资源的大小
    public function getShareFileSize(){
        // 资源id
        if (!isset($_REQUEST["sourceId"])) die;
        $sourceId = $_REQUEST["sourceId"];
        // 云盘名
        if (!isset($_REQUEST["driverName"])) die;
        $driverName = $_REQUEST["driverName"];

        // 清空日志
        LogManager::getSingleton()->clearLog();
        LogManager::getSingleton()->addLog("开始计算共享资源大小...");

        // 执行计算文件大小php脚本
        $cmd = "php ".ADMIN."controller/GetShareFileSizeController.class.php ".LogManager::getSingleton()->logFilePath;
        $cmd = $cmd." ".$sourceId." ".$driverName;
        $res = ShellManager::exec($cmd);
        if (!$res["success"]){
            LogManager::getSingleton()->addLog("计算共享资源大小失败:".json_encode($res));
        }else {
            LogManager::getSingleton()->addLog("计算共享资源大小成功:".json_encode($res));
        }
    }

    // 获取云盘已使用空间和文件总数量
    public function getDriveUsedSpace(){
        // 云盘名
        if (!isset($_REQUEST["driverName"])) die;
        $driverName = $_REQUEST["driverName"];

        // 清空日志
        LogManager::getSingleton()->clearLog();
        LogManager::getSingleton()->addLog("开始获取".$driverName."云盘已使用空间大小...");

        // 执行计算文件大小php脚本
        $cmd = "php ".ADMIN."controller/GetDriveUsedSpaceController.class.php ".LogManager::getSingleton()->logFilePath." '".json_encode($GLOBALS["db_info"])."'";
        $cmd = $cmd." ".$driverName;
        ShellManager::exec($cmd);
    }
}