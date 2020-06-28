<?php


namespace admin\controller;
use framework\dao;

require_once "./framework/dao/DAOPDO.class.php";
class CreateTablesController
{
    private $dao;
    public function __construct()
    {
        $this->initDabaseInfo();
        $this->dao = dao\DAOPDO::getSingleton();

        // 创建云盘信息表
        $this->initDriverListTable();
        // 创建设置表
        $this->initSettingTable();
        // 登录密码存放表
        $this->initPassWDTable();
        // 创建文件转存信息表
        $this->initFileTransferInfoTable();
        // 创建文件后台移动信息表
        $this->initFileMoveInfoTable();
    }

    // 初始化数据库信息
    private function initDabaseInfo(){
        // 获取数据库链接信息
        $path = getcwd()."/config/config.php";
        $config = require_once $path;
        $GLOBALS['config'] = $config;

        $option['host'] = $config['host'];
        $option['user'] = $config['user'];
        $option['pass'] = $config['pass'];
        $option['dbname'] = $config['dbname'];
        $option['port'] = $config['port'];
        $option['charset'] = $config["charset"];
        $GLOBALS["db_info"] = $option;
    }

    // 创建云盘信息表
    public function initDriverListTable(){
        $tableName = "driver_list";
        // 创建视频数据表
        $sql = <<<EEE
                    CREATE TABLE $tableName(
                        id int AUTO_INCREMENT PRIMARY KEY COMMENT 'id',
                        driver_name varchar(64) DEFAULT '' COMMENT '云盘名',
                        remark varchar(300) DEFAULT '' COMMENT '备注'
                    ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='云盘表';
EEE;
        $this->dao->createTable($tableName,$sql);
    }

    // 创建设置表
    public function initSettingTable(){
        $tableName = "driver_setting";
        $sql = <<<EEE
                    CREATE TABLE $tableName(
                        id int AUTO_INCREMENT PRIMARY KEY COMMENT 'id',
                        flag varchar(64) DEFAULT '' COMMENT '标志',
                        status tinyint DEFAULT 0 COMMENT '状态',
                        func_desc varchar(64) DEFAULT '' COMMENT '功能描述'
                    ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='设置表';
EEE;
        $this->dao->createTable($tableName,$sql);

        // 添加数据
        $data = [[
            "flag"     =>  "'load_file_detail_info'",
            "func_desc"=>  "'是否加载云盘文件大小和文件数'"
        ],[
            "flag"     =>  "'updatingDirTree'",
            "func_desc"=>  "'是否正在更新云盘目录树'"
        ]];

        foreach ($data as $datum) {
            $this->dao->insertData($tableName,"flag",$datum);
        }
    }

    // 创建密码表
    public function initPassWDTable(){
        $tableName = "acc_passwd";
        // 创建视频数据表
        $sql = <<<EEE
                    CREATE TABLE $tableName(
                        id int AUTO_INCREMENT PRIMARY KEY COMMENT '密码id',
                        pass_desc varchar(128) DEFAULT '' COMMENT '密码描述',
                        passwd varchar(256) DEFAULT '' COMMENT '密码值',
                        pass_level int DEFAULT 0 COMMENT '密码安全级别'
                    ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='密码表';
EEE;
        $this->dao->createTable($tableName,$sql);
        // 添加登录密码
        $data = [
            "pass_desc"     =>  "'登录密码'",
            "passwd"        =>  "QDAoTKf4iGADBGSjt4VXXElC7eanPD3gS9sn3DRZHTBjVpbm/ZQ7Y5a2KEYujU6cjXFJdMudNB06Y1UalS6Gd5ThiYd+EcwKcPsT1Xp5xHdDtJL0lWyirZhRwdOHPQ/P/Xzc0wArFP2hjccJAlucpc8FpN+oOvfAzojzL0/liYQ=",
            "pass_level"    => 4
        ];
        $this->dao->insertData($tableName,"pass_desc",$data);
    }

    // 创建文件转存信息表
    public function initFileTransferInfoTable(){
        $tableName = "file_transfer_info";
        // 创建视频数据表
        $sql = <<<EEE
                    CREATE TABLE $tableName(
                        id int AUTO_INCREMENT PRIMARY KEY COMMENT 'id',
                        file_name varchar(256) DEFAULT '' COMMENT '文件名',
                        file_path varchar(128) DEFAULT '' COMMENT '文件保存路径',
                        status tinyint DEFAULT 0 COMMENT '状态'
                    ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='文件转存信息表';
EEE;
        $this->dao->createTable($tableName,$sql);
        $this->dao->insertData($tableName,"id",["id"=>1]);
    }

    // 创建文件后台移动信息表
    public function initFileMoveInfoTable(){
        $tableName = "file_move_info";
        // 创建视频数据表
        $sql = <<<EEE
                    CREATE TABLE $tableName(
                        id int AUTO_INCREMENT PRIMARY KEY COMMENT 'id',
                        source_path varchar(256) DEFAULT '' COMMENT '源路径',
                        des_path varchar(256) DEFAULT '' COMMENT '目标路径'
                    ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='文件移动信息表';
EEE;
        $this->dao->createTable($tableName,$sql);
    }
}