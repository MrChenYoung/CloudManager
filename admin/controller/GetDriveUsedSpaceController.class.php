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

// 保存到的云盘名
$driverName =  $argv[3];

// 清空历史记录
update($mysqli,["used_space"=>"--","file_count"=>"--"],["driver_name"=>$driverName],$logPath);

// rclone脚本获取数据
$cmd = "rclone size $driverName:";
$res = myshellExec($cmd);
if ($res["success"]){
    $fileSize = "--";
    $fileCount = 0;

    $res = $res["result"];
    $fileCount = (int)trim(str_replace("Total objects:","",$res[0]));
    preg_match("/\((.*)\)/",$res[1],$match);
    if (count($match) > 1){
        $fileSize = $match[1];
        $fileSize = trim(str_replace("Bytes","",$fileSize));
    }
    $fileCount = $fileCount>=10000 ? $fileCount/10000 .'w' : $fileCount."";
    $fileSize = formatBytes($fileSize);

    addLog($logPath,"获取到使用空间大小:".$fileSize);
    // 更新数据库数据
    update($mysqli,["used_space"=>$fileSize,"file_count"=>$fileCount],["driver_name"=>$driverName],$logPath);
}else {
    addLog($logPath,"获取使用空间失败");
}


/**
 *  字节格式化成B KB MB GB TB
 * @param $size 字节大小
 * @return string 格式化后的结果
 */
function formatBytes($size) {
    $units = array(' B', ' KB', ' MB', ' GB', ' TB');
    for ($i = 0; $size >= 1024 && $i < 4; $i++) {
        $size /= 1024;
    }
    return round($size, 2).$units[$i];
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

// 更新数据库
function update($mysqlDAO,$data,$where=[],$path){
    //1. 判断是否传递了where条件
    if(empty($where) || !is_array($where)){
    }else{
        //拼接WHERE 条件，例如：WHERE `goods_id`=506
        $where_str =" WHERE ";
        $index = 0;

        foreach($where as $k=>$v){
            //将可能存在的 单引号 转义并包裹
            if ($index){
                $where_str.= " AND `$k` = '".$v."'";
            }else {
                $where_str.= "`$k` = '".$v."'";
            }

            $index++;
        }
    }

    //2. 拼接更新的字段
    $arr = [];
    foreach($data as $k=>$v){
        $arr[] = "`$k` = '".$v."'";
    }
    $fields = implode(',',$arr);

    //3. 拼接SQL语句
    $sql = "UPDATE driver_list SET $fields $where_str";

    addLog($path,"sql语句:".$sql);
    $result = $mysqlDAO -> query($sql);
    if ($result){
        return true;
    }else {
        return $mysqlDAO -> error;
    }
}