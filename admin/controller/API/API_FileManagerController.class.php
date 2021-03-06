<?php


namespace admin\controller\API;


use framework\tools\DatabaseDataManager;
use framework\tools\FileManager;
use framework\tools\LogManager;
use framework\tools\MultiThreadTool;
use framework\tools\ShellManager;

class API_FileManagerController extends API_BaseController
{
    // 获取云盘列表
    public function loadDriverList($return=false){
        // 数据库查询云盘信息
        $driveListData = [];
        $driveInfo = DatabaseDataManager::getSingleton()->find("driver_list");
        if ($driveInfo){
            foreach ($driveInfo as $drive) {
                $driveName = $drive["driver_name"];
                $driveListData[$driveName] = $drive;
            }
        }

        // 把rclone配置文件打包成json
        $cmd = "rclone config dump";
        $res = ShellManager::exec($cmd);
        if (!$res["success"]){
            if ($return){
                return false;
            }else {
                echo $this->failed("获取云盘列表失败");
                die;
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
                    $type = "谷歌云盘";
                    break;
                case "onedrive":
                    $type = "微软oneDriver";
                    break;
                default:
                    $type = "未知";
                    break;
            }
            $sort = 0;
            if (key_exists($key,$driveListData)){
                $dData = $driveListData[$key];
                $sort = $dData["sort"];
                $sortData[$type][] = $sort;
            }

            $driveData = ["name"=>$key,"sort"=>$sort];
            $data[$type][] = $driveData;
        }

        // 排序
        foreach ($data as $tp=>$datum) {
            $sArray = $sortData[$tp];
            // 按照sort排序
            array_multisort($sArray, SORT_ASC, $data[$tp]);
        }

        if ($return){
            return $data;
        }else {
            echo $this->success($data);
        }
    }

    // 获取文件列表
    public function loadFileList(){
        // 远程云盘名
        if (!isset($_GET["remoteName"])){
            echo $this->failed("缺少remoteName参数");
            die;
        }
        $remoteName = $_GET["remoteName"];

        // 路径
        if (!isset($_GET["path"])){
            echo $this->failed("缺少path参数");
            die;
        }
        $path = $_GET["path"];
        $path = base64_decode($path);
        $path = urldecode($path);

        // rclone命令获取文件列表信息
        $path = str_replace(" ","\ ",$path);
        $cmd = "rclone lsjson ".$remoteName.":".$path;
        $res = ShellManager::exec($cmd);
        if (!$res["success"]){
            echo $this->failed("获取文件列表失败");
            die;
        }

        // 获取是否要同时加载文件详细信息设置项
        $switchData = DatabaseDataManager::getSingleton()->find("driver_setting",["flag"=>"load_file_detail_info"],["status"]);
        $switchStatus = false;
        if ($switchData){
            $switchStatus = $switchData[0]["status"];
        }

        $fileList = $res["result"];
        $fileList = implode("",$fileList);
        $fileList = json_decode($fileList,true);
        foreach ($fileList as $key=>$file) {
            // 时间转换
            $timeStr = $file["ModTime"];
            date_default_timezone_set('Asia/Shanghai');
            $timeStr = date('Y-m-d H:i:s',strtotime($timeStr));
            $fileList[$key]["ModTime"] = $timeStr;

            // 文件大小
            $fileSize = $file["Size"];
            // 文件数量
            $fileCount = 1;
            if ($file["IsDir"]){
                $fileSize = "--";
                $fileCount = "--";
                if ($switchStatus){
                    $res = $this->loadDetaileInfo($remoteName,$path.$file["Name"]);
                    $fileSize = $res["size"];
                    $fileCount = $res["count"];
                }
            }else if ($fileSize >= 0){
                $fileSize = FileManager::formatBytes($fileSize);
            }else {
                $fileSize = "--";
            }
            $fileList[$key]["Size"] = $fileSize;
            $fileList[$key]["Count"] = $fileCount;
            // 文件类型处理
            $fileList[$key]["icon"] = $this->getFileIcon($file);
        }

        echo $this->success($fileList);
    }

    // 获取文件夹列表
    public function loadMoveDirList(){
        // 远程云盘名
        if (!isset($_GET["remoteName"])){
            echo $this->failed("缺少remoteName参数");
            die;
        }
        $remoteName = $_GET["remoteName"];

        // 路径
        if (!isset($_GET["path"])){
            echo $this->failed("缺少path参数");
            die;
        }
        $path = $_GET["path"];
        $path = base64_decode($path);
        $path = urldecode($path);

        // rclone命令获取文件列表信息
        $path = str_replace(" ","\ ",$path);
        // rclone命令获取文件夹列表信息
        $cmd = "rclone lsd ".$remoteName.":".$path;

        $res = ShellManager::exec($cmd);
        if (!$res["success"]){
            echo $this->failed("获取文件夹列表失败");
            die;
        }

        $dirList = $res["result"];
        $data = [];
        foreach ($dirList as $dir) {
            // 多个连续空格合并成一个
            $patt = '/\s{1,}/';
            $dir = preg_replace($patt,' ',$dir);
            // 以空格为基准分成数组
            $dirArray = explode(" ",$dir);

            // 时间转换
            $timeStr = $dirArray[2].$dirArray[3];
            $timeStr = trim($timeStr);
            date_default_timezone_set('Asia/Shanghai');
            $timeStr = date('Y-m-d H:i:s',strtotime($timeStr));

            // 文件夹名
            $dirName = "";
            for($i = 5;$i < count($dirArray); $i++){
                if ($i > 5){
                    $dirName .= " ";
                }
                $dirName .= $dirArray[$i];
            }

            $dirData = ["Name"=>$dirName,"ModTime"=>$timeStr,"icon"=>"icon-wenjian"];
            $data[] = $dirData;
        }

        echo $this->success($data);
    }

    // 删除文件
    public function deleteFile(){
        // 远程云盘名
        if (!isset($_GET["remoteName"])){
            echo $this->failed("缺少remoteName参数");
            die;
        }
        $remoteName = $_GET["remoteName"];

        // 文件路径
        if (!isset($_GET["path"])){
            echo $this->failed("缺少path参数");
            die;
        }
        $path = $_GET["path"];
        $path = base64_decode($path);
        $path = urldecode($path);
        // 空格转义
        $path = str_replace(" ","\ ",$path);

        // 是否是文件夹
        if (!isset($_GET["isDir"])){
            echo $this->failed("缺少isDir参数");
            die;
        }
        $isDir = $_GET["isDir"];

        // rclone命令删除
        $cmd = "rclone delete ".$remoteName.":".$path;
        if ($isDir == '1'){
            $cmd = "rclone purge ".$remoteName.":".$path;
        }

        $res = ShellManager::exec($cmd);
        if (!$res["success"]){
            echo $this->failed("删除失败");
            die;
        }

        echo $this->success("删除成功");
    }

    // 获取云盘所有文件大小和文件总数量
    public function loadFileDetailInfo(){
        // 云盘名
        if (!isset($_GET["driverName"])){
            echo $this->failed("缺少driverName参数");
            die;
        }
        $driverName = $_GET["driverName"];

        // 路径
        if (!isset($_GET["path"])){
            echo $this->failed("缺少path参数");
            die;
        }
        $path = $_GET["path"];
        $path = base64_decode($path);
        $path = urldecode($path);

        $path = $driverName.":".$path;

        // 后台获取文件详情
        $params = [
            "m"=>"admin",
            "c"=>"AsynTask",
            "a"=>"index",
            "path"=>$path
        ];

        MultiThreadTool::addTask($this->website."/index.php","getFileInfo",$params);
        // 提示正在后台移动
        echo $this->success("文件详情后台获取中");
    }

    // 重命名
    public function renameFile(){
        // 云盘名
        if (!isset($_GET["driverName"])){
            echo $this->failed("缺少driverName参数");
            die;
        }
        $driverName = $_GET["driverName"];

        // 路径
        if (!isset($_GET["path"])){
            echo $this->failed("缺少path参数");
            die;
        }
        $path = $_GET["path"];
        $path = base64_decode($path);
        $path = urldecode($path);

        // 原来的名字
        if (!isset($_GET["oldName"])){
            echo $this->failed("缺少oldName参数");
            die;
        }
        $oldName = $_GET["oldName"];
        $oldName = base64_decode($oldName);
        $oldName = urldecode($oldName);

        // 新名字
        if (!isset($_GET["newName"])){
            echo $this->failed("缺少newName参数");
            die;
        }
        $newName = $_GET["newName"];
        $newName = base64_decode($newName);
        $newName = urldecode($newName);

        // 完整路径 空格转义
        $oldFullPath = str_replace(" ","\ ",$path.$oldName);
        $newFullPath = str_replace(" ","\ ",$path.$newName);
        $cmd = 'rclone moveto '.$driverName.":".$oldFullPath." ".$driverName.":".$newFullPath;
        $res = ShellManager::exec($cmd);
        if (!$res["success"]){
            echo $this->failed("重命名失败");
            die;
        }
        echo $this->success("重命名成功");
    }

    // 新建文件夹
    function createNewDir(){
        // 云盘名
        if (!isset($_GET["driverName"])){
            echo $this->failed("缺少driverName参数");
            die;
        }
        $driverName = $_GET["driverName"];

        // 路径
        if (!isset($_GET["path"])){
            echo $this->failed("缺少path参数");
            die;
        }
        $path = $_GET["path"];
        $path = base64_decode($path);
        $path = urldecode($path);

        // 文件夹名字
        if (!isset($_GET["dirName"])){
            echo $this->failed("缺少dirName参数");
            die;
        }
        $dirName = $_GET["dirName"];
        $dirName = base64_decode($dirName);
        $dirName = urldecode($dirName);

        $fullPath = str_replace(" ","\ ",$path.$dirName);
        $cmd = 'rclone mkdir '.$driverName.":".$fullPath;

        $res = ShellManager::exec($cmd);
        if (!$res["success"]){
            echo $this->failed("文件夹创建失败");
            die;
        }
        echo $this->success("文件夹创建成功");
    }

    // 移动文件
    function moveFile(){
        // 源云盘名
        if (!isset($_GET["sourcedriver"])){
            echo $this->failed("缺少sourcedriver参数");
            die;
        }
        $sourcedriver = $_GET["sourcedriver"];

        // 源文件路径
        if (!isset($_GET["sourcePath"])){
            echo $this->failed("缺少sourcePath参数");
            die;
        }
        $sourcePath = $_GET["sourcePath"];
        $sourcePath = base64_decode($sourcePath);
        $sourcePath = urldecode($sourcePath);
        $sourcePath = $sourcedriver.":".$sourcePath;

        // 目标云盘名
        if (!isset($_GET["desdriver"])){
            echo $this->failed("缺少desdriver参数");
            die;
        }
        $desdriver = $_GET["desdriver"];

        // 目标文件路径
        if (!isset($_GET["desPath"])){
            echo $this->failed("缺少desPath参数");
            die;
        }
        $desPath = $_GET["desPath"];
        $desPath = base64_decode($desPath);
        $desPath = urldecode($desPath);
        $desPath = $desdriver.":".$desPath;

        // 是否要在后台移动
        if (!isset($_GET["back"])){
            echo $this->failed("缺少back参数");
            die;
        }
        $back = $_GET["back"];

        // 转义空格
        $sourcePath = str_replace(" ","\ ",$sourcePath);
        $desPath = str_replace(" ","\ ",$desPath);

        //  如果要移动的文件大于10G，转入后台移动
        if ($back == "1"){
            // 后台移动
            $params = [
                "m"=>"admin",
                "c"=>"AsynTask",
                "a"=>"index",
                "sourcePath"=>$sourcePath,
                "desPath"=>$desPath,
                "desdriver"=>$desdriver,
                "sourcedriver"=>$sourcedriver
            ];

            MultiThreadTool::addTask($this->website."/index.php","moveFile",$params);
            // 提示正在后台移动
            echo $this->success("文件后台移动中");
        }else {
            // 前台直接移动
            if ($sourcedriver == $desdriver){
                // 云盘内移动文件，不耗费vps流量
                $cmd = "gclone moveto ".$sourcePath." ".$desPath." --drive-server-side-across-configs -P >> ".LogManager::getSingleton()->logFilePath." 2>&1";
            }else {
                // 跨云盘移动文件 耗费vps流量
                $cmd = "gclone moveto ".$sourcePath." ".$desPath." -P >> ".LogManager::getSingleton()->logFilePath." 2>&1";
            }

            $res = ShellManager::exec($cmd);
            if (!$res["success"]){
                echo $this->failed("移动失败");
                die;
            }

            // 如果是跨云盘移动文件夹，原来的云盘里的文件夹会变成空，但是文件夹依然存在，需要再执行删除空文件夹命令
            if ($sourcedriver != $desdriver){
                ShellManager::exec("rclone purge ".$sourcePath);
            }
            echo $this->success("移动成功");
        }
    }

    // 移动文件前检查
    public function beforMoveFileCheck(){
        // 源文件路径
        if (!isset($_GET["sourcePath"])){
            echo $this->failed("缺少sourcePath参数");
            die;
        }
        $sourcePath = $_GET["sourcePath"];
        $sourcePath = base64_decode($sourcePath);
        $sourcePath = urldecode($sourcePath);

        // 转义空格
        $sourcePath = str_replace(" ","\ ",$sourcePath);
        // 查看是否有文件正在后台移动
        $tableRes = DatabaseDataManager::getSingleton()->find("file_move_info");
        if ($tableRes && count($tableRes) > 0){
            echo $this->success(["canMove"=>false,"msg"=>"有文件正在后台移动中"]);
        }else {
            echo $this->success(["canMove"=>true,"msg"=>""]);
        }
    }

    // 根据文件类型获取显示图标
    private function getFileIcon($file){
        if ($file["IsDir"]){
            return "icon-wenjian";
        }

        $icon = "";
        $pre = "";
        $arr = explode("/",$file["MimeType"]);
        if (count($arr) > 0){
            $pre = $arr[0];
        }

        switch ($pre){
            case "text":
                // 文本文件
                $icon = "icon-icontxt";
                break;
            case "video":
                // 视频
                $icon = "icon-video-f";
                break;
            case "audio":
                // 音频
                $icon = "icon-yinlemusic215";
                break;
            case "image":
                // 图片
                $icon = "icon-tupian";
                break;
            default:
                // 其他
                $icon = "icon-wenjian1";
                break;
        }

        return $icon;
    }

}