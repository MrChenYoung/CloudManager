<?php


namespace admin\controller\API;


use framework\tools\DatabaseDataManager;
use framework\tools\MultiThreadTool;

class API_FileTransferController extends API_BaseController
{
    // 文件转存
    public function fileTransfer(){
        // 资源地址
        if (!isset($_GET["address"])){
            echo $this->failed("缺少address参数");
            die;
        }
        $address = $_GET["address"];

        // 路径
        if (!isset($_GET["path"])){
            echo $this->failed("缺少path参数");
            die;
        }
        $path = $_GET["path"];

        // 文件要保存到的文件夹
        $dirArr = explode("/",$path);
        $dirName = "";
        if (count($dirArr) > 0){
            $dirName = $dirArr[count($dirArr) - 1];
            if (strlen($dirName) == 0 && count($dirArr) > 1){
                $dirName = $dirArr[count($dirArr) - 2];
            }
        }
        DatabaseDataManager::getSingleton()->update("file_transfer_info",["status"=>1,"file_name"=>$dirName,"file_path"=>$path],["id"=>1]);

        // 解析资源id
        $needle = "folders/";
        $end = substr($address,strpos($address,$needle) + strlen($needle));
        $needle2 = "?";
        $pos = strpos($end,$needle2);
        if ($pos){
            $end = substr($end,0,$pos);
        }

        // 后台存储文件
        $params = [
            "m"=>"admin",
            "c"=>"AsynTask",
            "a"=>"index",
            "sourceId"=>$end,
            "savePath"=>$path
        ];

        MultiThreadTool::addTask($this->website."/index.php","fileTransfer",$params);
        // 提示正在后台更新
        echo $this->success("文件后台转存中");
    }

    // 是否正在转存文件
    public function fileTransfering(){
        $data = $this->getFileTransferStatus();
        if ($data){
            echo $this->success(["flag"=>true]);
        }else {
            echo $this->success(["flag"=>false]);
        }
    }

    // 是否有文件正在转存
    private function getFileTransferStatus(){
        $res = DatabaseDataManager::getSingleton()->find("file_transfer_info",["id"=>1]);
        if ($res){
            return $res[0]["status"] == "0" ? false : $res[0];
        }else {
            return false;
        }
    }
}