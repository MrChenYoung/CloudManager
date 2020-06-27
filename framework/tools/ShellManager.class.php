<?php


namespace framework\tools;


class ShellManager
{
    // 执行shell命令
    public static function exec($cmd){
        exec($cmd.' 2>&1',$result,$status);
        $success = $status == 0 ? true : false;
        return [
            "success"   =>  $success,
            "result"    =>  $result
        ];
    }
}