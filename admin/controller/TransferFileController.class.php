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

// 共享资源id
$sourceId = $argv[3];
// 保存到的云盘名
$driverName =  $argv[4];
// 保存路径
$savePath = $argv[5];

// 开始转存文件，设置标志位
$startRes = update($mysqli,["status"=>1],["id"=>1]);
if ($startRes === true){
    addLog($logPath,"设置标志为成功");
}else {
    addLog($logPath,"设置标志为失败".$startRes);
}

// 执行gclone转存命令
//$cmd = "gclone copy $driverName:"."{".$sourceId."}"." ".$driverName.":$savePath --drive-server-side-across-configs -P >> ".$logPath." 2>&1";
//$res = myshellExec($cmd);
//if (!$res["success"]){
//    addLog($logPath,"文件转存失败");
//}else {
//    addLog($logPath,"文件转存成功");
//}

// 转存完成 修改数据库标识
//$endRes = update($mysqli,["status"=>0],["id"=>1]);
//if ($endRes === true){
//    addLog($logPath,"修改标志为成功");
//}else {
//    addLog($logPath,"修改标志为失败".$startRes);
//}

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
                $where_str.= " AND `$k` = $v";
            }else {
                $where_str.= "`$k` = $v";
            }

            $index++;
        }
    }

    //2. 拼接更新的字段
    $arr = [];
    foreach($data as $k=>$v){
        $v = $this->dao->quote($v);
        $arr[] = "`$k` = $v";
    }
    $fields = implode(',',$arr);

    //3. 拼接SQL语句
    $sql = "UPDATE file_transfer_info SET $fields $where_str";

    $result = $mysqlDAO -> query($sql);
    if ($result){
        return true;
    }else {
        return $mysqlDAO -> error;
    }
}