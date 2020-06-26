<?php

namespace admin\controller;

use framework\core\Controller;

class FileTansferController extends Controller
{
    public function index()
    {
        parent::index();
        $this -> loadTemplate(["data"=>$this -> data],"filetransfer/index.html");
    }
}