<?php


namespace admin\controller\API;

use framework\tools\DatabaseDataManager;

class API_SettingController extends API_BaseController
{
    // 加载设置选项
    public function loadSetting(){
        $res = DatabaseDataManager::getSingleton()->find("driver_setting");
        if ($res){
            echo $this->success($res);
        }else {
            echo $this->failed("加载设置失败");
        }
    }

    // 更改设置
    public function changeSetting(){
        // 标志
        if (!isset($_GET["flag"])){
            echo $this->failed("缺少flag参数");
            die;
        }
        $flag = $_GET["flag"];

        // 状态
        if (!isset($_GET["status"])){
            echo $this->failed("缺少status参数");
            die;
        }
        $status = $_GET["status"];
        $res = DatabaseDataManager::getSingleton()->update("driver_setting",["status"=>$status],["flag"=>$flag]);
        if ($res){
            echo $this->success($res);
        }else {
            echo $this->failed("更改设置失败");
        }
    }

}