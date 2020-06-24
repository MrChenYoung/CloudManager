<?php


namespace admin\controller\API;
use framework\core\Controller;
use framework\tools\FileManager;
use framework\tools\SessionManager;
use framework\tools\ShellManager;

class API_BaseController extends Controller
{
    /**
     * 请求失败调用
     * @param $data
     * @return string
     */
    protected function failed($message,$data=[]){
        return $this -> putJson(0,$message,$data);
    }

    /**
     * 请求成功调用
     * @param $data 数据
     * @return string 返回json字符串结果
     */
    protected function success($data){
        return $this -> putJson(200,"成功",$data);
    }

    /**
     * 按json方式输出通信数据
     * @param integer $code 状态码
     * @param string  $message 提示信息
     * @param array   $data 数据
     * @return string 返回值为json数据
     */
    protected function putJson($code, $message = '', $data = ''){
        if (!is_numeric($code)) {
            return '';
        }
        // 返回值默认json
        header('Content-type: application/json');
        $result = array('code' => $code, 'message' => $message, 'data' => $data);
        return json_encode($result);
    }

    // 解密
    public function decrypt(){
        // 密码id
        if (!isset($_GET["pass"])){
            echo $this->failed("需要pass参数");
            die;
        }
        $pass = $_GET["pass"];

        $decryptPass = $this->getDecryptPass($pass);
        echo $this->success($decryptPass);
    }

    // 退出d登录
    function logout(){
        // 删除session里面的login标识
        SessionManager::getSingleTon() -> deleteSession("isLogin");

        // 跳转到登录页面
        echo "<script>window.location.href='index.php?logout='</script>";
    }

    /**
     * 获取云盘指定路径下的详细信息(总大小和文件总数量)
     * @param $remoteName   云盘名字
     * @param $path         路径
     * @return array        包含大小和文件数量的数组
     */
    public function loadDetaileInfo($remoteName,$path){
        $fileSize = "--";
        $fileCount = 0;
        $getSizeCmd = "rclone size ".$remoteName.":".$path;

        $sizeRes = ShellManager::exec($getSizeCmd);
        if (!$sizeRes["success"]){
            // 获取文件详情失败
        }else {
            $sizeRes = $sizeRes["result"];
            $fileCount = (int)trim(str_replace("Total objects:","",$sizeRes[0]));
            preg_match("/\((.*)\)/",$sizeRes[1],$match);
            if (count($match) > 1){
                $fileSize = $match[1];
                $fileSize = trim(str_replace("Bytes","",$fileSize));
            }

            $fileSize = FileManager::formatBytes($fileSize);
        }

        return ["size"=>$fileSize,"count"=>$fileCount];
    }

    // 获取云盘所有文件大小和文件总数量
    public function loadDriverDetailInfo($driverName,$path){
        $res = $this->loadDetaileInfo($driverName,$path);
        return $res;
    }
}