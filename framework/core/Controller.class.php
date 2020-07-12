<?php
namespace framework\core;
use framework\tools\Page;
use framework\tools\ServerInfoManager;
use framework\tools\SessionManager;
use \Smarty;

class Controller
{
    // 模板
    protected $smarty;

    // 数据
    protected $data;
    // 数据额外信息  子类控制器自己定义的数据信息
    protected $dataExtension;

    // 本站站点路径
    protected $website;

    public function __construct()
    {
        $this -> smarty = new Smarty();
        $this -> smarty -> left_delimiter = '<{';
        $this -> smarty -> right_delimiter = '}>';
        $tplsDir = ROOT.'public/tpls_c';
        if (!file_exists($tplsDir)){
            mkdir($tplsDir);
            chmod($tplsDir,0700);
        }
        $this -> smarty -> setTemplateDir(ROOT.MODULE.'/view/');
        $this -> smarty -> setCompileDir($tplsDir);

        // 本站站点
        $this -> website = "http://".$_SERVER['HTTP_HOST'];
        // 图片路径
        $imageUrl = $this->website."/public/common/img/";

        // 数据
        $this -> data = [
            "baseUrl"       => $this -> website,
            "imageUrl"      => $imageUrl,
            "dbname"        => $GLOBALS["db_info"]["dbname"]
        ];
    }

    /**
     * 每个控制器的入口函数
     */
    public function index(){}

    /**
     *  字节格式化成B KB MB GB TB
     * @param $size 字节大小
     * @return string 格式化后的结果
     */
    public function formatBytes($size) {
        $scale = ServerInfoManager::getSingleTon() -> isWindowsSystem() ? 1024 : 1000;
        $units = array(' B', ' KB', ' MB', ' GB', ' TB');
        for ($i = 0; $size >= $scale && $i < 4; $i++) {
            $size /= $scale;
        }
        return round($size, 2).$units[$i];
    }

    /**
     * 创建页码显示控件
     * @param $dataCount 数据总条数
     * @param $pageSize 每页显示数量
     * @param $pageIndex 当前页码（从1开始）
     * @return string 返回创建HTML代码
     */
    protected function createPageControl($url,$dataCount,$pageSize,$pageIndex){
        $page = new Page();

        // 总共有多少条数据
        $page -> total_rows = $dataCount;
        // 每一页显示多少条数据
        $page -> pagesize = $pageSize;

        // 点击页码请求的url
        $page -> url = $url;
        // 当前页码
        $page -> now_page = $pageIndex;
        $pageHTML = $page -> create();

        return $pageHTML;
    }

    /**
     * 加载模板
     */
    protected function loadTemplate($data,$temPath){
        // 额外数据信息追加
        if ($this -> dataExtension){
            foreach ($this -> dataExtension as $eKey => $eValue) {
                $data["data"][$eKey] = $eValue;
            }
        }

        $state = $this -> getSlideBarState();
        $data["data"]["slideBarState"] = $state;

        // 设置模板
        foreach ($data as $key => $datum) {
            $this -> smarty -> assign($key,$datum);
        }
        $this -> smarty -> display($temPath);
    }

    //跳转、重定向
    //参数1：url，目标地址
    //参数2：message, 提示的信息
    //参数3：delay，延迟的时间，默认3秒
    public function jump($url,$message,$delay=3)
    {
        header("Refresh:$delay;url=$url");
        echo $message;
        exit;
    }

    /**
     * 存储菜单栏显示装填
     */
    public function saveSlideBarState(){
        $stateHidden = $_REQUEST["slidebarState"];
        SessionManager::getSingleTon() -> setSession("slidebarState",$stateHidden);
    }

    /**
     * 获取菜单栏隐藏状态
     * @return mixed
     */
    private function getSlideBarState(){
        $state = SessionManager::getSingleTon() -> getSession("slidebarState");
        if (strlen($state) <= 0){
            // 默认显示菜单栏
            $state = "0";
        }
        return $state;
    }

    /**
     * 打开PHPinfo界面
     */
    public function openPhpInfo(){
        echo phpinfo();
    }
}

