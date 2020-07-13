<?php


namespace framework\tools;


class DatabaseDataManager extends DatabaseManager
{
    /**
     * 自动查询，封装执行查询的sql语句
     * SELECT `goods_id`,`goods_name` FROM tn_goods WHERE goods_id = 1
     * 参数1：查询的条件，$where = ['goods_id'=>1]
     * 参数2：查询的字段，$fields = ['goods_id','goods_name','shop_price']
     */
    public function find($tbName,$where = [],$fields=[],$other="")
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

        echo "sql:".$sql;
        die;
        //4. 执行sql语句，返回结果
        return $this -> dao -> fetchAll($sql);
    }


    /**
     * 模糊查询
     * @param $tbName 表名
     * @param array $where
     * @param array $fields
     * @param string $other
     * @return mixed
     */
    public function fuzzyQuery($tbName,$where = [],$fields=[],$other=""){
        //1. 确定查询的条件
        if(empty($where) || !is_array($where)){
            $where_str = '';
        }else{
            $where_str = '';
            foreach ($where as $key=>$value) {
                if ($key == 0){
                    $where_str .= " WHERE ";
                }else {
                    $where_str .= " AND ";
                }
                $where_str .= " `$key` = '$value' ";
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
        return $this -> dao -> fetchAll($sql);
    }

    // 连表查询
    public function findMovieInfo($leftWhere = [],$rightWhere = [],$fields=[],$other="",$titleKeywords=""){
        //1. 确定查询的条件
        $where_str = '';
        if(!empty($leftWhere) && is_array($leftWhere)){
            foreach ($leftWhere as $key=>$value) {
                if ($key == 0){
                    $where_str .= " WHERE ";
                }else {
                    $where_str .= " AND ";
                }
                $where_str .= " movie_play_info.".$key."='$value'";
            }
        }

        if(!empty($rightWhere) && is_array($rightWhere)){
            foreach ($rightWhere as $key=>$value) {
                if ($key == 0 && strlen($where_str)==0){
                    $where_str .= " WHERE ";
                }else {
                    $where_str .= " AND ";
                }
                $where_str .= " movie_tj.".$key."='$value'";
            }
        }

        // 添加标题模糊查询条件
        if (strlen($titleKeywords) > 0){
            if (strlen($where_str) > 0){
                $where_str .= " AND movie_play_info.movie_title LIKE '%$titleKeywords%' ";
            }else {
                $where_str .= " WHERE movie_play_info.movie_title LIKE '%$titleKeywords%' ";
            }
        }

        //3. 拼接sql语句
        $sql = <<<EEE
SELECT * FROM `movie_play_info` LEFT JOIN `movie_tj` ON movie_play_info.movie_tj_id=movie_tj.tjId $where_str $other
EEE;
        //4. 执行sql语句，返回结果
        return $this -> dao -> fetchAll($sql);
    }

    /**
     * 删除数据，根据主键字段删除
     * 参数：主键值，删除主键等于谁的记录
     */
    public function delete($tbName,$where=[])
    {
        $index = 0;
        $whereStr = "";
        foreach ($where as $key => $value) {
            $value = StringTool::singleQuotesInclude($value);
            if ($index > 0){
                $whereStr .= " AND `$key`=$value";
            }else {
                $whereStr .= " `$key`=$value";
            }
            $index++;
        }

        if (strlen($whereStr) > 0){
            $sql = "DELETE FROM $tbName WHERE $whereStr";
        }else {
            $sql = "DELETE FROM $tbName";
        }

        return $this -> dao -> query($sql);
    }

    /**
     * 统计表中数据条数
     * @param $tbName       表名
     * @param array $where  条件
     * @return int          查询到的条数
     */
    public function rowCount($tbName,$where=[]){
        $w = "";
        if ($where){
            $index = 0;
            foreach ($where as $key=>$value){
                if (StringTool::isFiled($value)){
                    $v = $value;
                }else {
                    $v = StringTool::singleQuotesInclude($value);
                }

                if ($index > 0){
                    $w .= " AND `$key`=$v";
                }else {
                    $w .= " `$key`=$v";
                }
            }
        }

        if ($w){
            $w = " WHERE $w";
        }

        $sql = <<<EEE
SELECT count(*) as count FROM $tbName $w
EEE;

        $res = $this -> dao -> fetchAll($sql);

        if ($res){
            return $res[0]["count"];
        }

        return 0;
    }

    /**
     * 自动更新的操作
     * 参数1：更新的字段，$data = ['goods_name'=>'小米6','shop_price'=>2300]
     * 参数2：更新有条件：$where = ['goods_id' => 506]
     * 目标：UPDATE tn_goods SET `goods_name`='小米6',`shop_price`=2300 WHERE `goods_id`=506
     */
    public function update($tbName,$data,$where=[])
    {
        //1. 判断是否传递了where条件
        if(empty($where) || !is_array($where)){
        }else{
            //拼接WHERE 条件，例如：WHERE `goods_id`=506
            $where_str =" WHERE ";
            $index = 0;

            foreach($where as $k=>$v){
                //将可能存在的 单引号 转义并包裹
                $v = $this->dao->quote($v);
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
        $sql = "UPDATE $tbName SET $fields $where_str";

        $lastId = $this -> dao -> query($sql);
        return $lastId ? true : false;
    }


    /**
     * 添加数据
     * 参数，向哪个字段插入什么值，$data = ['goods_name'=>'小米Mix2','shop_price'=>2000]
     * 目标：INSERT INTO `tn_goods`(`goods_name`,`shop_price`) VALUES('小米Mix2',2000)
     */
    public function insert($tbName,$data)
    {
        $sql = "INSERT INTO $tbName";

        //2. 将传递的数组中的字段名拼接成字符串
        $keys = [];
        $values = [];
        foreach($data as $k=>$v){
            $keys[] = "`$k`";                    // `goods_name`    `shop_price`
            $values[] = $this->dao->quote($v);   // '小米Mix2'  '1 or 1=1 \''
        }
        //3. 拼接字段部分
        $fields = '('.implode(',',$keys).')';    // (`goods_name`,`shop_price`)
        $sql .= $fields;

        //3. 拼接字段值部分
        $fileds = ' VALUES('.implode(',',$values).')';
        $sql .= $fileds;

        //执行sql语句
        $this -> dao -> query($sql);
        //返回刚刚插入的数据的主键值
        return $this -> dao -> lastId();
    }
}