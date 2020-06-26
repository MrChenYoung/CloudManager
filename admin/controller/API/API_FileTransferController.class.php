<?php


namespace admin\controller\API;


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

        // 解析处资源id
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

        die;
        MultiThreadTool::addTask($this->website."/index.php","fileTransfer",$params);
        // 提示正在后台更新
        echo $this->success("文件后台转存中");
    }
}