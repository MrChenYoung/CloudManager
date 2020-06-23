<?php

namespace admin\controller;

use framework\core\Controller;

class SettingController extends Controller
{
    public function index()
    {
        parent::index();
        $this -> loadTemplate(["data"=>$this -> data],"setting/index.html");
    }
}