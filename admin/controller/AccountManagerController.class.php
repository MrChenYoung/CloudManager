<?php


namespace admin\controller;

use framework\core\Controller;

class AccountManagerController extends Controller
{
    public function index()
    {
        parent::index();
        $this -> loadTemplate(["data"=>$this -> data],"account/index.html");
    }
}