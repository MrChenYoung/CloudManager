<?php
namespace framework\dao;
use framework\dao\i_DAOPDO;
use framework\tools\DatabaseBackupManager;
use framework\tools\FileManager;
use framework\tools\StringTool;
use PDO;
use PDOException;

require_once "./framework/dao/i_DAOPDO.interface.php";
require_once "./framework/tools/StringTool.class.php";
require_once "./framework/tools/DatabaseBackupManager.class.php";
require_once "./framework/tools/FileManager.class.php";
/**
 * Class DAOPDO PDO封装类
 * 封装PDO的操作
 */
class DAOPDO implements i_DAOPDO
{
    private static $instance;
    private $host;
    private $user;
    private $pass;
    private $port;
    private $charset;
    private $dbname;
    private $pdo;

    private function __construct(){}

    private function __clone(){}

    public static function getSingleton(){
        if(!self::$instance instanceof self){
            self::$instance = new self();
            self::$instance->loginDb($GLOBALS["db_info"]);
        }
        return self::$instance;
    }

    /**
     * 登录并连接到数据库
     */
    private function loginDb($option){
        $this -> host = isset($option['host'])?$option['host']:'';
        $this -> user = isset($option['user'])?$option['user']:'';
        $this -> pass = isset($option['pass'])?$option['pass']:'';
        $this -> dbname = isset($option['dbname'])?$option['dbname']:'';
        $this -> port = isset($option['port'])?$option['port']:3306;
        $this -> charset = isset($option['charset'])?$option['charset']:'utf8';

        // 登录mysql
        $dsn = "mysql:host=$this->host;port=$this->port";
        $res = $this->initDAO($dsn);
        if ($res["success"]){
            // 如果数据库不存在 创建
            $this->createDatabase();

            // 连接到account_db数据库
            $connectRes = $this->connectDb();
            if ($connectRes["success"]){
                // 登录成功
            }else {
                die("链接数据库失败,错误信息:".$connectRes["message"]);
            }
        }else {
            die("登录数据库失败,错误信息:".$res["message"]);
        }
    }

    /**
     * 链接数据库
     * @param $option
     */
    private function connectDb(){
        $dsn = "mysql:host=$this->host;dbname=$this->dbname;port=$this->port;charset=$this->charset";
        return $this->initDAO($dsn);
    }

    //初始化PDO
    private function initDAO($dsn){
        try{
            $this ->pdo = new PDO($dsn,$this -> user,$this -> pass);
            return ["success"=>true,"message"=>"成功"];
        }catch (PDOException $e){
            return ["success"=>false,"message"=>$e->getMessage()];
        }
    }

    // 如果数据库不存在创建
    private function createDatabase(){
        $dbName = $this->dbname;
        $sql = "SELECT * FROM information_schema.SCHEMATA where SCHEMA_NAME='".$dbName."'";
        $link = new \mysqli("localhost","root","199156");
        if (!$link->connect_error){
            // 链接数据库成功
            $result = $link -> query($sql);
            if ($result){
                // 获取所有行数据 只要关联数组
                $res = $result -> fetch_all(MYSQLI_ASSOC);
                // 释放资源
                $result -> free();
                if (!$res){
                    // 数据库不存在 创建
                    $result = $this->pdo->exec("CREATE DATABASE IF NOT EXISTS {$dbName} DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;");
                    if (!$result) {
                        die('数据库创建失败');
                    }else {
                        // 数据库创建成功 导入备份
                        $driveDbPath = "/www/wwwroot/cloudmount.yycode.ml/GDSuite/我的数据/备份数据/db/";
                        $path = $driveDbPath.$dbName."/all/";
                        $fileName = $this->getLastBackDb($path);
                        $path .= $fileName;
                        (new DatabaseBackupManager($GLOBALS["db_info"]))->restore($path);
                    }
                }
            }
        }
    }

    // 获取最后一次数据库备份
    private function getLastBackDb($path){
        // 获取所有文件名
        $fileLists = [];
        $handler = file_exists($path) ? opendir($path) : null;
        if ($handler){
            while (($filename = readdir($handler)) !== false) { //务必使用!==，防止目录下出现类似文件名“0”等情况
                if ($filename != "." && $filename != ".." && substr($filename,0,1) != ".") {
                    //获取文件修改日期
                    date_default_timezone_set('PRC');
                    $filetime = date('Y-m-d H:i:s', filemtime($path . "/" . $filename));
                    //文件修改时间作为健值
                    if (array_key_exists($filetime,$fileLists)){
                        // 已经有相同时间文件
                        $existsValue = $fileLists[$filetime];
                        if (is_array($existsValue)){
                            $existsValue[] = $filename;
                            $fileLists[$filetime] = $existsValue;
                        }else {
                            $fileLists[$filetime] = [$existsValue,$filename];
                        }
                    }else {
                        // 没有没有相同时间的文件
                        $fileLists[$filetime] = $filename;
                    }
                }
            }
            closedir($handler);
        }

        // key按照时间排序
        ksort($fileLists);
        $data = [];
        foreach ($fileLists as $key=>$item) {
            $fileInfo = [];
            if (is_array($item)){
                foreach ($item as $newItem){
                    // 获取文件大小
                    $fileSize = FileManager::getFileSize($path."/".$newItem);
                    $fileSize = FileManager::formatBytes($fileSize);
                    $fileInfo["size"] = $fileSize;
                    $fileInfo["name"] = $newItem;
                    $fileInfo["time"] = $key;
                    $data[] = $fileInfo;
                }
            }else {
                // 获取文件大小
                $fileSize = FileManager::getFileSize($path."/".$item);
                $fileSize = FileManager::formatBytes($fileSize);
                $fileInfo["size"] = $fileSize;
                $fileInfo["name"] = $item;
                $fileInfo["time"] = $key;
                $data[] = $fileInfo;
            }
        }
        // 倒序排列
        $data = array_reverse($data);
        if (count($data) > 0){
            return $data[0]["name"];
        }else {
            return "";
        }
    }

    // 创建表
    public function createTable($tableName,$sql){
        if (!$this->tableExist($tableName)){
            $this->fetchAll($sql);
        }
    }

    /**
     * 添加一条记录到表
     * @param $tbName               表名
     * @param $standardFiledName    判断标准字段，如果$data内该字段对应的value在表中不存在，则添加，否则无操作
     * @param array $data           要添加的一条记录键值对
     * @return bool                 添加是否成功
     */
    public function insertData($tbName,$standardFiledName,$data=[]){
        $success = false;
        if (!$data) return $success;

        // 查询表中是否有该记录
        $value = $data[$standardFiledName];
        $querySql = <<<EEE
SELECT * FROM $tbName WHERE $standardFiledName=$value
EEE;
        $res = $this -> FetchAll($querySql);

        if (!$res){
            // 没有该条数据 添加
            $fileds = [];
            $values = [];
            foreach ($data as $filed => $value){
                $fileds[] = $filed;
                $values[] = StringTool::singleQuotesInclude($value);
            }

            $fileStr = implode(",",$fileds);
            $valueStr = implode(",",$values);

            $insertSql = <<<EEE
                INSERT INTO $tbName ($fileStr) VALUES($valueStr);
EEE;
            // 添加数据
            $this -> FetchAll($insertSql);
            $success = true;
        }

        return $success;
    }

    /**
     * 给表添加字段
     * @param $tbName 表名
     * @param mixed ...$fields 要添加的字段
     * @return bool            添加是否成功
     */
    public function addField($tbName,...$fields){
        $success = false;
        foreach ($fields as $field){
            // 字段不存在 添加
            if (array_key_exists("name",$field) && array_key_exists("type",$field) && !$this -> fieldExist($tbName,$field["name"])){
                $fieldName = array_key_exists("name",$field) ? $field["name"] : null;
                $fieldType = array_key_exists("type",$field) ? $field["type"] : null;
                $fieldComment = array_key_exists("comment",$field) ? "COMMENT ".$field["comment"] : null;
                $filedDefault = array_key_exists("default",$field) ? "DEFAULT ".$field["default"] : null;
                $sql = "ALTER TABLE $tbName ADD $fieldName  $fieldType $filedDefault  $fieldComment";

                $this -> fetchAll($sql);
                $success = true;
            }
        }

        return $success;
    }

    // 判断数据表是否存在
    public function tableExist($tbName){
        // 判断表是否存在
        $sql1 = "SHOW TABLES LIKE '".$tbName."'";
        $res = $this -> fetchAll($sql1);

        if('1' == count($res)){
            // 表存在
            return true;
        }else {
            // 表不存在
            return false;
        }
    }

    /**
     * 检查表中是否有指定字段
     * @param $tbName 表名
     * @param $fieldName 字段名
     * @return bool 是否存在
     */
    public function fieldExist($tbName,$fieldName){
        $sql = "Describe $tbName $fieldName";
        $res = $this-> fetchAll($sql);
        if ($res){
            // 字段存在
            return true;
        }else {
            // 字段不存在
            return false;
        }
    }

    /**
     * 删除表
     * @param $tableName
     */
    public function deleteTable($tableName){
        if ($this -> tableExist($tableName)){
            $sql = "DROP TABLE $tableName";
            $this -> fetchAll($sql);
        }
    }

    //查询一条记录的方法
    public function fetchOne($sql)
    {
       $pdo_statement = $this-> pdo -> query($sql);
        if($pdo_statement == false){
            //说明没有执行成功
            echo 'SQL语句:<br>'.$sql.'<br>'.'有误，详细信息如下:<br>'.$this->pdo->errorInfo()[2];
            exit;
        }
        //执行到这里说明没有错误
        return $pdo_statement -> fetch(PDO::FETCH_ASSOC);
    }
    //查询所有记录
    public function fetchAll($sql)
    {
        $pdo_statement = $this-> pdo -> query($sql);
        if($pdo_statement == false){
            //说明没有执行成功
            echo 'SQL语句:<br>'.$sql.'<br>'.'有误，详细信息如下:<br>'.$this->pdo->errorInfo()[2];
            exit;
        }
        //执行到这里说明没有错误
        return $pdo_statement -> fetchAll(PDO::FETCH_ASSOC);
    }

    //查询一个字段的值
    public function fetchColumn($sql)
    {
        $pdo_statement = $this-> pdo -> query($sql);
        if($pdo_statement == false){
            //说明没有执行成功
            echo 'SQL语句:<br>'.$sql.'<br>'.'有误，详细信息如下:<br>'.$this->pdo->errorInfo()[2];
            exit;
        }
        //执行到这里说明没有错误
        return $pdo_statement -> fetchColumn();
    }
    //执行增删改的方法
    public function query($sql)
    {
        $result = $this -> pdo -> exec($sql);
        //注意：可能返回等同于 false的非布尔值， 影响的记录数可能：0
        if($result === false){
            echo 'SQL语句:<br>'.$sql.'<br>'.'有误，详细信息如下:<br>'.$this -> pdo->errorInfo()[2];
            exit;
        }
        //执行到这里,sql没有错误，返回受影响的记录数
        return $result;
    }
    //返回刚刚执行插入语句的主键
    public function lastId()
    {
        return $this -> pdo -> lastInsertId();
    }
    //对数据引号转义、并包裹的方法
    public function quote($data)
    {
        return $this -> pdo -> quote($data);
    }
}