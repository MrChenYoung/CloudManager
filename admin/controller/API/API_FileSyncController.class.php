<?php


namespace admin\controller\API;


use framework\tools\DatabaseDataManager;
use framework\tools\MultiThreadTool;

class API_FileSyncController extends API_BaseController
{
    // 表名
    private $tableName;
    public function __construct()
    {
        parent::__construct();
        $this->tableName = "file_sysnc_info";
    }

    // 获取同步列表
    public function loadFileSyncList(){
        $res = DatabaseDataManager::getSingleton()->find($this->tableName);

        $data = [];
        if ($res){
            foreach ($res as $re) {
                $type = $re["type"] == 1 ? "本地同步" : "云盘同步";
                $status = $re["status"] == 1 ? "同步中" : "未同步";
                $re["typeStr"] = $type;
                $re["statusStr"] = $status;
                $data[] = $re;
            }
        }
        echo $this->success($data);
    }

    // 添加/修改同步信息
    public function updateSyncInfo(){
        // 同步类型
        if (!isset($_GET["type"])){
            echo $this->failed("缺少type参数");
            die;
        }
        $type = $_GET["type"];

        // 是否是编辑同步信息
        if (!isset($_GET["edit"])){
            echo $this->failed("缺少edit参数");
            die;
        }
        $edit = $_GET["edit"];

        // 要编辑的同步信息id
        if (!isset($_GET["syncId"])){
            echo $this->failed("缺少syncId参数");
            die;
        }
        $syncId = $_GET["syncId"];


        // 源云盘
        if (!isset($_GET["srcDrive"])){
            echo $this->failed("缺少srcDrive参数");
            die;
        }
        $srcDrive = $_GET["srcDrive"];

        // 目标云盘
        if (!isset($_GET["desDrive"])){
            echo $this->failed("缺少desDrive参数");
            die;
        }
        $desDrive = $_GET["desDrive"];

        // 源路径
        if (!isset($_GET["srcPath"])){
            echo $this->failed("缺少srcPath参数");
            die;
        }
        $srcPath = $_GET["srcPath"];
        $srcPath = base64_decode($srcPath);
        $srcPath = urldecode($srcPath);
        if ($type == "0"){
            $srcPath = $srcDrive.":".$srcPath;
        }

        // 目标路径
        if (!isset($_GET["desPath"])){
            echo $this->failed("缺少desPath参数");
            die;
        }
        $desPath = $_GET["desPath"];
        $desPath = base64_decode($desPath);
        $desPath = urldecode($desPath);
        if ($type == "0"){
            $desPath = $desDrive.":".$desPath;
        }

        $data = [
            "source_path"   =>$srcPath,
            "des_path"      =>$desPath,
            "type"          =>$type
        ];

        $isEdit = $edit == "1" ? true : false;
        if ($isEdit){
            $res = DatabaseDataManager::getSingleton()->update($this->tableName,$data,["id"=>$syncId]);
        }else {
            $res = DatabaseDataManager::getSingleton()->insert($this->tableName,$data);
        }

        if ($res){
            echo $this->success($isEdit ? "同步信息修改成功" : "添加同步成功");
        }else {
            echo $this->failed($isEdit ? "同步信息修改失败" : "添加同步失败");
        }
    }

    // 编辑同步
    public function editSyncInfo(){
        // 同步类型
        if (!isset($_GET["syncId"])){
            echo $this->failed("缺少syncId参数");
            die;
        }
        $syncId = $_GET["syncId"];

        $res = DatabaseDataManager::getSingleton()->find($this->tableName,["id"=>$syncId]);
        if ($res){
            $res = $res[0];
            $type = $res["type"]."";
            $status = $res["status"];

            if ($status == 1){
                // 正在同步中
                echo $this->failed("正在同步中，无法编辑");
            }else {
                $srcPath = $res["source_path"];
                $srcDrive = "";
                if ($type == "0"){
                    $srcArr = explode(":",$srcPath);
                    $srcDrive = $srcArr[0];
                    $srcPath = $srcArr[1];
                }

                $desPath = $res["des_path"];
                $desDrive = "";
                $desArr = explode(":",$desPath);
                $desDrive = $desArr[0];
                $desPath = $desArr[1];

                $data = [
                    "type"      =>  $type,
                    "srcDrive"  =>  $srcDrive,
                    "srcPath"   =>  $srcPath,
                    "desDrive"  =>  $desDrive,
                    "desPath"   =>  $desPath
                ];
                echo $this->success($data);
            }
        }else {
            echo $this->failed("获取同步信息失败");
        }
    }

    // 删除同步
    public function deleteSyncInfo(){
        // 同步信息id
        if (!isset($_GET["syncId"])){
            echo $this->failed("缺少syncId参数");
            die;
        }
        $syncId = $_GET["syncId"];

        $res = DatabaseDataManager::getSingleton()->delete($this->tableName,["id"=>$syncId]);
        if ($res){
            echo $this->success("删除成功");
        }else {
            echo $this->failed("删除失败");
        }
    }

    // 开始同步
    public function startSyncInfo(){
        // 同步信息id
        if (!isset($_GET["syncId"])){
            echo $this->failed("缺少syncId参数");
            die;
        }
        $syncId = $_GET["syncId"];

        // 是否同步所有任务
        if (!isset($_GET["syncAll"])){
            echo $this->failed("缺少syncAll参数");
            die;
        }
        $syncAll = $_GET["syncAll"];

        // 后台开启同步
        $params = [
            "m"=>"admin",
            "c"=>"AsynTask",
            "a"=>"index",
            "syncAll"=>$syncAll,
            "syncId"=>$syncId
        ];

        MultiThreadTool::addTask($this->website."/index.php","startSyncData",$params);
        // 提示正在后台移动
        echo $this->success("后台同步中...");
    }
}