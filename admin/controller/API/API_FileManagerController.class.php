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
            $data[$type][] = $key;
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
            $patt = '/\s{1,}/';
            $dir = preg_replace($patt,' ',$dir);
            $dirArray = explode(" ",$dir);
            $dirName = trim($dirArray[count($dirArray)-1]);

            // 时间转换
            $timeStr = trim($dirArray[count($dirArray)-3]);
            date_default_timezone_set('Asia/Shanghai');
            $timeStr = date('Y-m-d H:i:s',strtotime($timeStr));
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

        $res = $this->loadDetaileInfo($driverName,$path);
        echo $this->success($res);
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

        // 原来的名字
        if (!isset($_GET["oldName"])){
            echo $this->failed("缺少oldName参数");
            die;
        }
        $oldName = $_GET["oldName"];

        // 新名字
        if (!isset($_GET["newName"])){
            echo $this->failed("缺少newName参数");
            die;
        }
        $newName = $_GET["newName"];

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

        echo $path;
        die;

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
        // 源文件路径
        if (!isset($_GET["sourcePath"])){
            echo $this->failed("缺少sourcePath参数");
            die;
        }
        $sourcePath = $_GET["sourcePath"];

        // 目标文件路径
        if (!isset($_GET["desPath"])){
            echo $this->failed("缺少desPath参数");
            die;
        }
        $desPath = $_GET["desPath"];

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
                "desPath"=>$desPath
            ];

            MultiThreadTool::addTask($this->website."/index.php","moveFile",$params);
            // 提示正在后台移动
            echo $this->success("文件后台移动中");
        }else {
            // 前台直接移动
            $cmd = "rclone moveto ".$sourcePath." ".$desPath." --drive-server-side-across-configs -P >> ".LogManager::getSingleton()->logFilePath." 2>&1";
            $res = ShellManager::exec($cmd);
            if (!$res["success"]){
                echo $this->failed("移动失败");
                die;
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
        // 转义空格
        $sourcePath = str_replace(" ","\ ",$sourcePath);
        // 获取要移动文件的大小
        $res = $this->loadDetaileInfo("","",$sourcePath);
        $size = (int)$res["sizeBytes"];
        //  如果要移动的文件大于10G，转入后台移动
        if ($size > 10 * 1024 * 1024 * 1024){
            // 查看是否有文件正在后台移动
            $tableRes = DatabaseDataManager::getSingleton()->find("file_move_info");
            if ($tableRes && count($tableRes) > 0){
                echo $this->success(["canMove"=>false,"back"=>true,"msg"=>"有文件正在后台移动中"]);
            }else {
                echo $this->success(["canMove"=>true,"back"=>true,"msg"=>""]);
            }
        }else {
            echo $this->success(["canMove"=>true,"back"=>false,"msg"=>""]);
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