<?php

namespace admin\controller;

use framework\core\Controller;

class MyDriverController extends Controller
{
    public function index()
    {
        parent::index();
        $this -> loadTemplate(["data"=>$this -> data],"mydriver/index.html");
    }

}