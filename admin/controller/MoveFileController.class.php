<?php

// 日志文件路径
$logPath = $argv[1];
// mysql数据库信息
$mysqlOption = json_decode($argv[2],true);
// 链接数据库
$mysqli = new \mysqli($mysqlOption['host'],$mysqlOption['user'],$mysqlOption['pass'],$mysqlOption['dbname'],$mysqlOption['port']);
if ($mysqli -> connect_error){
    addLog($logPath,"数据库连接失败：".$mysqli -> connect_error);
}

// 源云盘
$sourcedriver = $argv[3];
// 源路径
$sourcePath =  $argv[4];
// 目标云盘
$desdriver = $argv[5];
// 目标路劲
$desPath = $argv[6];

// 添加到数据库记录
$insertRes = insert($mysqli,["source_path"=>$sourcePath,"des_path"=>$desPath]);
if ($insertRes !== true){
    addLog($logPath,"插入数据失败".$res);
}

if ($sourcedriver == $desdriver){
    $cmd = "gclone moveto ".$sourcePath." ".$desPath." --drive-server-side-across-configs -P >> ".$logPath." 2>&1";
}else {
    $cmd = "gclone moveto ".$sourcePath." ".$desPath." -P >> ".$logPath." 2>&1";
}

// 执行rclone移动移动命令
addLog($logPath,"命令:".$cmd);
$res = myshellExec($cmd);
if (!$res["success"]){
    // 移动失败
    addLog($logPath,"文件移动失败");
}else {
    // 移动成功
    // 如果是跨云盘移动文件夹，原来的云盘里的文件夹会变成空，但是文件夹依然存在，需要再执行删除空文件夹命令
    if ($sourcedriver != $desdriver){
        myshellExec("rclone purge ".$sourcePath);
    }
    addLog($logPath,"文件移动成功");
}
// 从数据库记录删除
$deleteRes = delete($mysqli);
if ($deleteRes !== true){
    addLog($logPath,"添加记录删除失败:".$deleteRes);
}

// 执行shell脚本
function myshellExec($mycmd){
    exec($mycmd.' 2>&1',$result,$status);
    $success = $status == 0 ? true : false;
    return [
        "success"   =>  $success,
        "result"    =>  $result
    ];
}

// 添加log
function addLog($path,$content){
    // 获取当前时间
    date_default_timezone_set('PRC');
    $time = date('Y-m-d H:i:s', time());
    $content = "[$time]".$content."\r\n";
    file_put_contents($path,$content,FILE_APPEND);
}

// 插入数据到数据库
function insert($mysqlDAO,$data){
    $sql = "INSERT INTO file_move_info ";

    //2. 将传递的数组中的字段名拼接成字符串
    $keys = [];
    $values = [];
    foreach($data as $k=>$v){
        $keys[] = "`$k`";                    // `goods_name`    `shop_price`
        $values[] = "'".$v."'";                     // '小米Mix2'  '1 or 1=1 \''
    }
    //3. 拼接字段部分
    $fields = '('.implode(',',$keys).')';    // (`goods_name`,`shop_price`)
    $sql .= $fields;

    //3. 拼接字段值部分
    $fileds = ' VALUES('.implode(',',$values).')';
    $sql .= $fileds;

    //执行sql语句
    $result = $mysqlDAO -> query($sql);
    if ($result){
        return true;
    }else {
        return $mysqlDAO -> error;
    }
}

// 删除数据库记录
function delete($mysqlDAO,$where=[]){
    $index = 0;
    $whereStr = "";
    foreach ($where as $key => $value) {
        $value = singleQuotesInclude($value);
        if ($index > 0){
            $whereStr .= " AND `$key`=$value";
        }else {
            $whereStr .= " `$key`=$value";
        }
        $index++;
    }

    if (strlen($whereStr) > 0){
        $sql = "DELETE FROM file_move_info WHERE $whereStr";
    }else {
        $sql = "DELETE FROM file_move_info";
    }

    $res = $mysqlDAO -> query($sql);
    if ($res){
        return true;
    }else {
        return $mysqlDAO -> error;
    }
}

function singleQuotesInclude($str){
    $firstChar = substr($str,0,1);
    $lastChar = substr($str, strlen($str) - 1,1);

    $str = $firstChar === "'" ? $str : "'".$str;
    $str = $lastChar === "'" ? $str : $str."'";

    return $str;
}