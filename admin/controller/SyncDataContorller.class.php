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

// 是否同步所有
$syncAll = $argv[3];

// 同步信息id
$syncId =  $argv[4];

if ($syncAll == "1"){
    // 同步所有
    $res = find($mysqli,"file_sysnc_info");
    if ($res){
        foreach ($res as $re) {
            $sId = $re["id"];
            syncSingleTask($sId);
        }
    }else {
        // 查询同步列表失败
        addLog($logPath,"查询同步列表失败");
    }
}else {
    // 同步单个任务
    syncSingleTask($syncId);
}

// 同步单个任务
function syncSingleTask($syId){
    global $mysqli;
    global $logPath;

    clearLog($logPath);
    addLog($logPath,"开始查询".$syId."任务同步信息");

    // 查询同步信息
    $res = find($mysqli,"file_sysnc_info",["id"=>$syId]);
    if ($res){
        $res = $res[0];
        $status = $res["status"];
        // 源路径
        $srcPath =  $res["source_path"];
        // 目标路径
        $desPath =  $res["des_path"];

        if ($status == 1){
            addLog($logPath, "同步任务:".$srcPath."==>".$desPath."已经开始,跳过。。。");
        }else {
            addLog($logPath, "开始同步:".$srcPath."==>".$desPath);
            update($mysqli,["status"=>1],["id"=>$syId]);
            // 开始同步时间
            $startTime = getTime();
            update($mysqli,["lastStartTime"=>$startTime],["id"=>$syId]);
            // 同步完成时间
            update($mysqli,["lastCompTime"=>"--"],["id"=>$syId]);

            $cmd = "gclone sync ".$srcPath." ".$desPath." -P >> ".$logPath." 2>&1";
            // 执行rclone同步命令
            addLog($logPath,"命令:".$cmd);
            $res = myshellExec($cmd);
            if (!$res["success"]){
                // 同步
                addLog($logPath,$srcPath."==>".$desPath."同步失败");
            }else {
                // 同步成功
                addLog($logPath,$srcPath."==>".$desPath."同步完成");
            }
            // 更新数据库
            update($mysqli,["status"=>0],["id"=>$syId]);
            // 同步完成时间
            $completeTime = getTime();
            update($mysqli,["lastCompTime"=>$completeTime],["id"=>$syId]);
        }
    }else {
        addLog($logPath,"查询同步任务信息失败");
    }
}

addLog($logPath,"所有同步任务完成");

// 获取当前时间
function getTime(){
    date_default_timezone_set('PRC');
    $time = date('Y-m-d H:i:s', time());
    return $time;
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

// 清空log
function clearLog($path){
    file_put_contents($path,"");
}

// 更新数据库
function update($mysqlDAO,$data,$where=[]){
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
    $sql = "UPDATE file_sysnc_info SET $fields $where_str";
    $result = $mysqlDAO -> query($sql);
    if ($result){
        return true;
    }else {
        return $mysqlDAO -> error;
    }
}

// 从数据库查找
function find($mysqlDAO,$tbName,$where = [],$fields=[],$other="")
{
    //1. 确定查询的条件
    if(empty($where) || !is_array($where)){
        $where_str = '';
    }else{
        $where_str = '';
        $index = 0;
        foreach ($where as $key=>$value) {
            if ($index == 0){
                $where_str .= " WHERE ";
            }else {
                $where_str .= " AND ";
            }
            $where_str .= " `$key` = '$value' ";
            $index++;
        }
    }

    //2. 确定查询的字段
    if(empty($fields) || !is_array($fields)){
        $fields_str = '*';
    }else{
        $field = [];
        foreach ($fields as $k=>$v){
            $field[] = "`$v`";
        }
        //将数组的值使用,连接成字符串, `goods_id`,`goods_name`,`shop_price`
        $fields_str = implode(',',$field);
    }
    //3. 拼接sql语句
    $sql = "SELECT $fields_str FROM $tbName $where_str $other";

    //4. 执行sql语句，返回结果
    $result = $mysqlDAO -> query($sql);
    if ($result){
        // 获取所有行数据 只要关联数组
        $res = $result -> fetch_all(MYSQLI_ASSOC);
        // 释放资源
        $result -> free();
        return $res;
    }else {
        // 查询失败
        echo "查询失败,错误如下:".$mysqlDAO -> error;
        $result -> free();
        exit;
    }
}



