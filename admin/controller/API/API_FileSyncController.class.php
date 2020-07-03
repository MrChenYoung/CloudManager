<?php


namespace admin\controller\API;


use framework\tools\DatabaseDataManager;

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

    }
}