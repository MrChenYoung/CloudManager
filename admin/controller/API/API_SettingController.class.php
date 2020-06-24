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
}