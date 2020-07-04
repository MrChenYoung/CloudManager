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
                $type = $re["type"] == 1 ? "本地同步" : "线上同步";
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
        $res = DatabaseDataManager::getSingleton()->insert($this->tableName,$data);
        if ($res){
            echo $this->success("添加同步成功");
        }else {
            echo $this->failed("添加同步失败");
        }
    }
}