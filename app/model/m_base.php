<?php
/**
 * Base Model
 */

namespace Model;

use R;

class BaseModel{
    /**
     * MySql抛出异常时的自定义错误码
     */

    /**
     *
     * 数据库初始化标识
     * @var boolean
     */
    private static $is_setup = false;

    /**
     * 构造函数
     */
    function __construct(){
        if(!self::$is_setup){
            //初始化数据库连接
            R::setup(
                "mysql:host=" . DBHOST . "; port=" . PORT . ";dbname=" . DBNAME,
                DBUSER,
                DBPASSWORD
            );
            R::freeze(TRUE);
            self::$is_setup = true;
        }
    }

    /**
     * 析构函数
     */
    function __destruct(){
        //关闭数据库连接
        R::close();
    }

    /**
     * 查询多行数据
     * @param string $query_stem 查询语句的主体
     * @param array $condition 查询条件
     * @param string $orderby 排序
     * @param string $limit 限制
     * @return array 查询结果
     * @author wenlong
     */
    function getAll($query_stem, $condition = [[]], $orderby=false, $limit = false){
        $query_builder = $this->build_query($query_stem, $condition, $orderby, $limit);
        return R::getAll($query_builder['sql'], $query_builder['binding']);
    }

    /**
     * 查询单元格数据
     * @param string $query_stem 查询语句的主体
     * @param array $condition 查询条件
     * @param string $orderby 排序
     * @return string 查询结果
     * @author wenlong
     */
    function getCell($query_stem, $condition = [[]], $orderby=false){
        $query_builder = $this->build_query($query_stem, $condition, $orderby, false);
        return R::getCell($query_builder['sql'], $query_builder['binding']);
    }

    /**
     * 查询单行数据
     * @param string $query_stem 查询语句的主体
     * @param array $condition 查询条件
     * @param string $orderby 排序
     * @return array 查询结果
     * @author wenlong
     */
    function getRow($query_stem, $condition = [[]], $orderby=false){
        $query_builder = $this->build_query($query_stem, $condition, $orderby, false);
        return R::getRow($query_builder['sql'], $query_builder['binding']);
    }

    /**
     * 拼接查询语句
     * @param string $query_stem 查询语句的主体
     * @param array $condition 查询条件
     * @param string $orderby 排序
     * @param string $limit 限制
     * @return array SQL语句和绑定的参数
     * @author wenlong
     */
    function build_query($query_stem, $condition, $orderby, $limit) {
        $pieces = $condition;
        //查询主体(query_stem)
        array_unshift($pieces, [$query_stem." WHERE 1=1"]);
        //排序(orderby)
        if($orderby) array_push($pieces, [$orderby]);
        //limit附加到pieces的最后
        if($limit) array_push($pieces, [$limit]);
        $sql = "";
        $binding = [];
        foreach($pieces as $piece){
            $n = count($piece);
            switch($n) {
                case 1:
                    $sql .= " {$piece[0]} ";
                    break;
                case 2:
                    $sql .= " {$piece[0]} {$piece[1]} ";
                    break;
                case 3:
                    $glue = $piece[1];
                    $target = $piece[0];
                    if ($target !== false){
                        if(is_array($target)){
                            $arr = [];
                            //如果第一个参数为数组（处理IN查询）
                            foreach ($target as $val){
                                $arr[] = $piece[2];
                                for ($i=0; $i < substr_count($piece[2],"?"); $i++){
                                    $binding[] = $val;
                                }
                            }
                            $str = join(" OR ",$arr);
                            $sql .= " {$glue} ({$str})";
                        }else{
                            for ($i=0; $i < substr_count($piece[2],"?"); $i++){
                                $binding[] = $target;
                            }
                            $sql .= " {$glue} {$piece[2]} ";
                        }
                    }
                break;
            }
        }
        return [
            'sql' => $sql,
            'binding' => $binding
        ];
    }
}


?>
