<?php

//初始化常量
initConst();
function initConst()
{
    $root = str_replace('\\','/',getcwd().'/');
    define('ROOT',$root);
    define('ADMIN',ROOT.'admin/');
    define('FRAMEWORK',ROOT.'framework/');
    //公共的静态资源路径
    define('PUBLIC_PATH','./public/');
}

// 初始化数据库
initDb();
function initDb(){
    require_once "./admin/controller/CreateTablesController.class.php";
    new \admin\controller\CreateTablesController();
}

// 设置选项
settingInit();
function settingInit(){
    // 获取云盘/文件列表是否同时获取文件总大小和总数量，默认关闭
    $GLOBALS["load_detail_info"] = false;
}