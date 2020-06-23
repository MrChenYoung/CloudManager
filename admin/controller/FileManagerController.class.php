<?php

namespace admin\controller;

use framework\core\Controller;

class FileManagerController extends Controller
{
    public function index()
    {
        parent::index();
        $this -> loadTemplate(["data"=>$this -> data],"filemanager/index.html");
    }
}