<?php

namespace admin\controller;

use framework\core\Controller;
use framework\tools\LogManager;

$ctr = new MoveFileController();
$ctr->test();

class MoveFileController extends Controller
{
    public function test(){
        LogManager::getSingleton()->addLog("测试移动文件:".$argv[1]);
    }
}