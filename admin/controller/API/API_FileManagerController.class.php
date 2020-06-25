<?php


namespace admin\controller\API;


use framework\tools\DatabaseDataManager;
use framework\tools\FileManager;
use framework\tools\ShellManager;

class API_FileManagerController extends API_BaseController
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

        echo $this->success($data);
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

    // 获取云盘文件夹列表
    public function loadDriveDirList(){
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
        $cmd = "rclone lsd ".$remoteName.":".$path;
        $res = ShellManager::exec($cmd);
        if (!$res["success"]){
            echo $this->failed("获取文件夹列表失败");
            die;
        }

        $dirList = $res["result"];
        $data = [];
        foreach ($dirList as $dir) {
            $dirArray = explode("-1",$dir);
            if (count($dirArray) > 0){
                $data[] = [
                    'title'=>trim($dirArray[count($dirArray)-1]),
                    'children'=>[['title'=>'']]
                ];
            }
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

        $res = $this->loadDriverDetailInfo($driverName,$path);
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

        $oldFullPath = $path.$oldName;
        $newFullPath = $path.$newName;
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

        // 文件夹名字
        if (!isset($_GET["dirName"])){
            echo $this->failed("缺少dirName参数");
            die;
        }
        $dirName = $_GET["dirName"];

        $cmd = 'rclone mkdir '.$driverName.":".$path.$dirName;
        $res = ShellManager::exec($cmd);
        if (!$res["success"]){
            echo $this->failed("文件夹创建失败");
            die;
        }
        echo $this->success("文件夹创建成功");
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