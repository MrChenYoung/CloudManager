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
        LogManager::getSingleton()->addLog("开始更新".(strlen($driverName) > 0 ? $driverName : "所有")."云盘使用详情...");

        // 执行计算文件大小php脚本
        $cmd = "php ".ADMIN."controller/GetDriveUsedSpaceController.class.php ".LogManager::getSingleton()->logFilePath." '".json_encode($GLOBALS["db_info"])."'";
        $cmd = $cmd." ".(strlen($driverName) > 0 ? $driverName : "1");
        LogManager::getSingleton()->addLog("cmd命令:".$cmd);
        ShellManager::exec($cmd);
    }

    // 开启同步任务
    public function startSyncData(){
        // 是否同步所有
        if (!isset($_REQUEST["syncAll"])) die;
        $syncAll = $_REQUEST["syncAll"];
        // 同步信息id
        if (!isset($_REQUEST["syncId"])) die;
        $syncId = $_REQUEST["syncId"];

        // 清空日志
        LogManager::getSingleton()->clearLog();
        LogManager::getSingleton()->addLog("开始同步...");

        // 执行同步php脚本
        $cmd = "php ".ADMIN."controller/SyncDataContorller.class.php ".LogManager::getSingleton()->logFilePath." '".json_encode($GLOBALS["db_info"])."'";
        $cmd = $cmd." ".$syncAll." ".$syncId;
        LogManager::getSingleton()->addLog("cmd命令:".$cmd);
        ShellManager::exec($cmd);
    }

    // 备份数据库
    public function backupDb(){
        // 本地数据库备份目录
        if (!isset($_REQUEST["localBackupPath"])) die;
        $localBackupPath = $_REQUEST["localBackupPath"];
        // 备份类文件位置
        if (!isset($_REQUEST["backupManagerFilePath"])) die;
        $backupManagerFilePath = $_REQUEST["backupManagerFilePath"];
        // 表名
        if (!isset($_REQUEST["tbName"])) die;
        $tbName = $_REQUEST["tbName"];
        $tbName = strlen($tbName) == 0 ? "-1" : $tbName;
        // 是否是备份所有数据库
        if (!isset($_REQUEST["backupAll"])) die;
        $backupAll = $_REQUEST["backupAll"];
        // 数据库列表
        if (!isset($_REQUEST["dbList"])) die;
        $dbList = $_REQUEST["dbList"];
        // 数据库
        $dbName = $GLOBALS["db_info"]["dbname"];
        $tName = $tbName === "-1" ? "全部" : $tbName;

        // 清空日志
        LogManager::getSingleton()->clearLog();
        LogManager::getSingleton()->addLog("开始备份数据库:".$dbName.",数据表:".$tName);

        // 执行转存文件php脚本
        $cmd = "php ".ADMIN."controller/DbBackup.php ".LogManager::getSingleton()->logFilePath." '".json_encode($GLOBALS["db_info"])."'";
        $cmd = $cmd." ".$localBackupPath." ".$tbName." ".$backupManagerFilePath." ".$backupAll." '".$dbList."'";
        $res = ShellManager::exec($cmd);
        if (!$res["success"]){
            LogManager::getSingleton()->addLog("执行测试php脚本失败:".json_encode($res));
        }else {
            LogManager::getSingleton()->addLog("执行测试php脚本成功:".json_encode($res));
        }
    }

    // 获取文件详情
    public function getFileInfo(){
        // 文件路径
        if (!isset($_REQUEST["path"])) die;
        $path = $_REQUEST["path"];

        $cmd = "php ".ADMIN."controller/GetFileInfo.php ".LogManager::getSingleton()->logFilePath." ".$path;
        ShellManager::exec($cmd);
    }
}