<?php
/**
 * Base DAO
 * @author liwenlong
 */

namespace SlimFastAPI;

use RedBeanPHP\R;
use Exception;

/**
 * DAO层基类
 */
class BaseDAO extends Base{
    /**
     * 构造函数，初始化数据库连接
     * @author liwenlong
     */
    public function __construct(){
        if(!R::hasDatabase('default')){
            //初始化数据库连接
            $dns = 'mysql:host=' . DBHOST . '; port=' . PORT . ';dbname=' . DBNAME;
            $user = DBUSER;
            $pwd = DBPASSWORD;
            R::setup($dns, $user, $pwd);
            //测试数据库连接
            if(!R::testConnection()){
                throw new Exception('Could not connect to database (' . 'host=' . DBHOST . ';port=' . PORT . ';dbname=' . DBNAME . ')', 
                    GlobalError::MYSQL_ERR_CODE);
            }
            //冻结数据库
            R::freeze(TRUE);
        }
    }

    /**
     * 析构函数，释放数据库连接
     */
    public function __destruct(){
        //关闭数据库连接
        R::close();
    }

    /**
     */
    
    /**
     * 切换数据库
     * @param  string $host   数据库服务器地址
     * @param  string $port   服务器端口
     * @param  string $dbname 数据库名称
     * @param  string $user   数据库登录用户
     * @param  string $pass   数据库登录密码
     * @author liwenlong
     */
    protected function switchDatabase($host, $port, $dbname, $user, $pass){
        try {
            R::startLogging();
            $key = md5($host . $port . $dbname . $use . $pass);
            if(!R::hasDatabase($key)){
                $dsn = "mysql:host=$host;port=$port,dbname=$dbname";
                R::addDatabase($key, $dsn, $user, $pass);
                R::selectDatabase($key);
                R::freeze(TRUE);
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), GlobalError::MYSQL_ERR_CODE);
        } finally {
            $this->queryLog();
        }
    }
    
    /**
     * 插入记录
     * @param  string $table_name   表名称
     * @param  array $properties    需要创建的属性的键值对
     * @return int                  插入记录的ID
     * @author liwenlong
     */
    public function insertRow($table_name, $properties){
        try {
            R::startLogging();
            $bean = R::dispense($table_name);
            foreach ($properties as $key => $value) {
                $bean->$key = $value;
            }
            return R::store($bean);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), GlobalError::MYSQL_ERR_CODE);
        } finally {
            $this->queryLog();
        }
    }

    /**
     * 批量插入记录
     * @param  string $table_name   表名称
     * @param  array $rows          需要插入的行
     * @return array                插入记录的ID列表
     * @author liwenlong
     */
    public function insertRows($table_name, $rows){
        try {
            R::startLogging();
            if(count($rows) === 0){
                throw new Exception('nothing to insert', GlobalError::MYSQL_ERR_CODE);
            }
            foreach ($rows as $row) {
                $bean = R::dispense($table_name);
                foreach ($row as $key => $value) {
                    $bean->$key = $value;
                }
                $beans[] = $bean;
            }
            return R::storeAll($beans);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), GlobalError::MYSQL_ERR_CODE);
        } finally {
            $this->queryLog();
        }
    }


    /**
     * 根据ID查询单行记录
     * @param  string $table_name   表名称
     * @param  int $id              记录ID
     * @param  array $fields        查询的字段
     * @param  array                查询结果
     * @author liwenlong
     */
    public function selectRow($table_name, $id, $fields){
        return $this->selectByFields($table_name, ['id' => $id], $fields);
    }

    /**
     * 根据指定的条件查询单行记录
     * @param  string $table_name   表名称
     * @param  array $filters       查询条件
     * @param  array $fields        查询的字段
     * @param  array                查询结果
     * @author liwenlong
     */
    public function selectByFields($table_name, $filters, $fields){
        try {
            R::startLogging();
            if(!is_array($filters) || count($filters) === 0){
                throw new Exception('select filters must be non-empty array', GlobalError::MYSQL_ERR_CODE);
            }

            if(!is_array($fields) || count($fields) === 0){
              throw new Exception('select fields must be non-empty array', GlobalError::MYSQL_ERR_CODE);
            }

            $fields_str = join(',', $fields);
            $sql = "SELECT $fields_str FROM $table_name WHERE ";

            $bindings = [];

            foreach ($filters as $key => $value) {
                $filter_list[] = "$key = ?";
                $bindings[] = $value;
            }
            $sql .= join(' AND ', $filter_list);

            $result = R::getRow($sql, $bindings);
            if($result === null){
                $result = [];
            }
            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), GlobalError::MYSQL_ERR_CODE);
        } finally {
            $this->queryLog();
        }
    }

    /**
     * 根据主键更新记录
     * @param  string $table_name   表名称
     * @param  int $id              记录ID
     * @param  array $properties    待更新的信息
     * @return int                  更新的记录行数
     * @author liwenlong
     */
    public function updateRow($table_name, $id, $properties){
        return $this->updateByFields($table_name, $properties, ['id' => $id]);
    }

    /**
     * 根据指定的条件更新记录
     * @param  string $table_name   表名称
     * @param  array $properties    待更新的信息
     * @param  array $filters       更新条件
     * @return int                  更新的记录行数
     */
    public function updateByFields($table_name, $properties, $filters){
        try {
            R::startLogging();
            if(!is_array($properties) || count($properties) === 0){
              throw new Exception('update properties must be non-empty array', GlobalError::MYSQL_ERR_CODE);
            }

            if(!is_array($filters) || count($filters) === 0){
                throw new Exception('update filters must be non-empty array', GlobalError::MYSQL_ERR_CODE);
            }

            $sql = "UPDATE $table_name SET ";

            $update_bindings = [];
            $count_bindings = [];
            foreach ($properties as $key => $value) {
                $set_list[] = "$key = ?";
                $update_bindings[] = $value;
            }
            $sql .= join(',', $set_list) . ' WHERE ';
            foreach ($filters as $key => $value) {
                $filter_list[] = "$key = ?";
                $update_bindings[] = $value;
                $count_bindings[] = $value;
            }

            //先查询符合条件的记录数量
            $sql_count = "SELECT COUNT(1) FROM $table_name WHERE ";
            $sql_count .= join(' AND ', $filter_list);
            $count = R::getCell($sql_count, $count_bindings);

            if($count === 0){
                return 0;
            }

            //执行更新语句
            $sql .= join(' AND ', $filter_list);
            R::exec($sql, $update_bindings);

            return $count;
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), GlobalError::MYSQL_ERR_CODE);
        } finally {
            $this->queryLog();
        }
    }


    /**
     * 根据主键删除记录
     * @param  string $table_name   表名称
     * @param  int $id              记录ID
     * @return int                  删除的行数
     * @author liwenlong
     */
    public function deleteRow($table_name, $id){
        return $this->updateByFields($table_name, ['id' => $id]);
    }

    /**
     * 根据指定的条件删除记录
     * @param  string $table_name   表名称
     * @param  array $filters       删除条件
     * @return int                  删除的记录行数
     */
    public function deleteByFields($table_name, $filters){
        try {
            R::startLogging();
            if(!is_array($filters) || count($filters) === 0){
                throw new Exception('delete filters must be non-empty array', GlobalError::MYSQL_ERR_CODE);
            }

            $sql = "DELETE FROM $table_name WHERE ";

            $bindings = [];
            $count_bindings = [];
            foreach ($filters as $key => $value) {
                $filter_list[] = "$key = ?";
                $bindings[] = $value;
                $count_bindings[] = $value;
            }

            //先查询符合条件的记录数量
            $sql_count = "SELECT COUNT(*) FROM $table_name WHERE ";
            $sql_count .= join(' AND ', $filter_list);
            $count = R::getCell($sql_count, $count_bindings);

            if($count === 0){
                return 0;
            }

            //执行删除语句
            $sql .= join(' AND ', $filter_list);
            R::exec($sql, $bindings);

            return $count;
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), GlobalError::MYSQL_ERR_CODE);
        } finally {
            $this->queryLog();
        }
    }


    /**
     * 执行SQL语句,多态支持，第一个参数为数组时，使用build_query模式，第一个参数为字符串时使用原生的参数绑定模式
     * @param  string $sql      要执行的SQL语句
     * @param  array $bindings  参数绑定列表
     * @author liwenlong
     */
    public function exec($arg0, $arg1 = array()){
        try {
            R::startLogging();
            if(is_array($arg0)){
                //第一个参数为数组时，使用build_query模式
                $pieces = $arg0;
                $query_builder = $this->build_query($pieces);
                $sql = $query_builder['sql'];
                $bindings = $query_builder['binding'];
            }else{
                //否则使用参数绑定模式
                $sql = $arg0;
                $bindings = $arg1;
            }
            $this->sqlCheck($sql);
            R::exec($sql, $bindings);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), GlobalError::MYSQL_ERR_CODE);
        } finally {
            $this->queryLog();
        }
    }


    /**
     * 查询多行数据，多态支持，第一个参数为数组时，使用build_query模式，第一个参数为字符串时使用原生的参数绑定模式
     * @param  mix $arg0        SQL语句或动态SQL语句列表
     * @param  array $arg1        当arg0为字符串时代表参数绑定列表
     * @return array            查询结果
     * @author liwenlong
     */
    public function getAll($arg0, $arg1 = []){
        try {
            R::startLogging();
            if(is_array($arg0)){
                //第一个参数为数组时，使用build_query模式
                $pieces = $arg0;
                $query_builder = $this->build_query($pieces);
                $sql = $query_builder['sql'];
                $binding = $query_builder['binding'];
            }else{
                //否则使用参数绑定模式
                $sql = $arg0;
                $binding = $arg1;
            }
            $this->sqlCheck($sql);
            return R::getAll($sql, $binding);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), GlobalError::MYSQL_ERR_CODE);
        } finally {
            $this->queryLog();
        }
    }



    
    /**
     * 查询单行数据，多态支持，第一个参数为数组时，使用build_query模式，第一个参数为字符串时使用原生的参数绑定模式
     * @param  mix $arg0        SQL语句或动态SQL语句列表
     * @param  array $arg1        当arg0为字符串时代表参数绑定列表
     * @return array            查询结果
     * @author liwenlong
     */
    public function getRow($arg0, $arg1 = []){
        try {
            R::startLogging();
            if(is_array($arg0)){
                //第一个参数为数组时，使用build_query模式
                $pieces = $arg0;
                $query_builder = $this->build_query($pieces);
                $sql = $query_builder['sql'];
                $binding = $query_builder['binding'];
            }else{
                //否则使用参数绑定模式
                $sql = $arg0;
                $binding = $arg1;
            }
            $this->sqlCheck($sql);
            return R::getRow($sql, $binding);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), GlobalError::MYSQL_ERR_CODE);
        } finally {
            $this->queryLog();
        }
    }

    /**
     * 查询单列数据，多态支持，第一个参数为数组时，使用build_query模式，第一个参数为字符串时使用原生的参数绑定模式
     * @param  mix $arg0        SQL语句或动态SQL语句列表
     * @param  array $arg1        当arg0为字符串时代表参数绑定列表
     * @return array            查询结果
     * @author liwenlong
     */
    public function getCol($arg0, $arg1 = []){
        try {
            R::startLogging();
            if(is_array($arg0)){
                //第一个参数为数组时，使用build_query模式
                $pieces = $arg0;
                $query_builder = $this->build_query($pieces);
                $sql = $query_builder['sql'];
                $binding = $query_builder['binding'];
            }else{
                //否则使用参数绑定模式
                $sql = $arg0;
                $binding = $arg1;
            }
            $this->sqlCheck($sql);
            return R::getCol($sql, $binding);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), GlobalError::MYSQL_ERR_CODE);
        } finally {
            $this->queryLog();
        }
    }

    /**
     * 查询单元格数据，多态支持，第一个参数为数组时，使用build_query模式，第一个参数为字符串时使用原生的参数绑定模式
     * @param  mix $arg0        SQL语句或动态SQL语句列表
     * @param  array $arg1        当arg0为字符串时代表参数绑定列表
     * @return array            查询结果
     * @author liwenlong
     */
    public function getCell($arg0, $arg1 = []){
        try {
            R::startLogging();
            if(is_array($arg0)){
                //第一个参数为数组时，使用build_query模式
                $pieces = $arg0;
                $query_builder = $this->build_query($pieces);
                $sql = $query_builder['sql'];
                $binding = $query_builder['binding'];
            }else{
                //否则使用参数绑定模式
                $sql = $arg0;
                $binding = $arg1;
            }
            $this->sqlCheck($sql);
            return R::getCell($sql, $binding);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), GlobalError::MYSQL_ERR_CODE);
        } finally {
            $this->queryLog();
        }
    }

    /**
     * 查询数据（SELECT语句的第一个项将作为返回数组的KEY)，多态支持，第一个参数为数组时，使用build_query模式，第一个参数为字符串时使用原生的参数绑定模式
     * @param  mix $arg0        SQL语句或动态SQL语句列表
     * @param  array $arg1        当arg0为字符串时代表参数绑定列表
     * @return array            查询结果
     * @author liwenlong
     */
    public function getAssoc($arg0, $arg1 = []){
        try {
            R::startLogging();
            if(is_array($arg0)){
                //第一个参数为数组时，使用build_query模式
                $pieces = $arg0;
                $query_builder = $this->build_query($pieces);
                $sql = $query_builder['sql'];
                $binding = $query_builder['binding'];
            }else{
                //否则使用参数绑定模式
                $sql = $arg0;
                $binding = $arg1;
            }
            $this->sqlCheck($sql);
            return R::getAssoc($sql, $binding);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), GlobalError::MYSQL_ERR_CODE);
        } finally {
            $this->queryLog();
        }
    }

    /**
     * 查询单行数据（SELECT语句的第一个项将作为返回数组的KEY)，多态支持，第一个参数为数组时，使用build_query模式，第一个参数为字符串时使用原生的参数绑定模式
     * @param  mix $arg0        SQL语句或动态SQL语句列表
     * @param  array $arg1        当arg0为字符串时代表参数绑定列表
     * @return array            查询结果
     * @author liwenlong
     */
    public function getAssocRow($arg0, $arg1 = []){
        try {
            R::startLogging();
            if(is_array($arg0)){
                //第一个参数为数组时，使用build_query模式
                $pieces = $arg0;
                $query_builder = $this->build_query($pieces);
                $sql = $query_builder['sql'];
                $binding = $query_builder['binding'];
            }else{
                //否则使用参数绑定模式
                $sql = $arg0;
                $binding = $arg1;
            }
            $this->sqlCheck($sql);
            return R::getAssocRow($sql, $binding);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), GlobalError::MYSQL_ERR_CODE);
        } finally {
            $this->queryLog();
        }
    }

    

    /**
     * 为数组类型的参数生成参数绑定占位符(?,?,?)
     * @param  array $array     参数数组
     * @return string           占位符字符串
     * @author liwenlong
     */
    public function genSlots($array){
        try {
            return R::genSlots($array);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), GlobalError::MYSQL_ERR_CODE);
        }
    }

    /**
     * 拼接查询语句
     * 参考链接：http://gabordemooij.com/index.php?p=/tiniest_query_builder
     *
     * Demo:
     *  $query_and_binding = build_query([
     *      [           'SELECT * FROM book'],
     *      [$title     , 'WHERE' , 'title = ?'],
     *      [$price     , 'AND' , 'price < ?'],
     *      [$order     , 'ORDER BY ? ASC'],
     *      [$limit     , 'LIMIT ?']
     *  ]);
     *
     * 注意：
     *    只有存在多个可选查询条件的情况下才有必要使用该方法
     *    慎用OR查询
     *    
     * @param  array $pieces  动态SQL语句列表
     * @return array          SQL语句和绑定参数
     * @author liwenlong
     */
    private function build_query($pieces) {
        $sql = '';
        $result = [
            'sql' => '',
            'binding' => []
        ];

        $glue = null;
        foreach($pieces as $piece){
            $n = count($piece);
            $target = '';
            $exp = '';
            switch($n) {
                case 1:
                    $exp = " {$piece[0]} ";
                    $this->gen_query_and_binding($result, '', $exp);
                    break;
                case 2:
                    $glue = null;
                    $target = $piece[0];
                    if(!is_null($target)){
                        $exp .= " {$piece[1]} ";
                        $this->gen_query_and_binding($result, $target, $exp);
                    }
                    break;
                case 3:
                    $glue = (is_null($glue)) ? strtoupper($piece[1]) : $glue;
                    $target = $piece[0];
                    if(!is_null($target)){
                        $exp .= " {$glue} {$piece[2]} ";
                        $this->gen_query_and_binding($result, $target, $exp);
                        $glue = null;
                    }
                    //$glue不等于WHERE时同样要清空
                    if($glue !== 'WHERE'){
                        $glue = null;
                    }
                    break;
            }
            
        }
        return $result;
    }

    /**
     * @author liwenlong
     */
    
    /**
     * 生成SQL和绑定的参数
     * @param  array &$result    生成结果，引用传递
     * @param  string | array $target     目标参数
     * @param  string $expression 表达式
     */
    private function gen_query_and_binding(&$result, $target, $expression){
        if(is_array($target)){
            $slot = $this->genSlots($target);
            $result['sql'] .= str_replace('?', $slot, $expression);
            //generate bindings
            for ($i=0; $i < substr_count($expression, '?'); $i++){
                $result['binding'] = array_merge($result['binding'], $target);
            } 
        }else{
            $result['sql'] .= $expression;
            //generate bindings
            for ($i=0; $i < substr_count($expression, '?'); $i++){
                $result['binding'][] = $target;
            }  
        }
        
    }

    /**
     * 在开发模式下记录Redbean执行过的SQL语句
     * @author liwenlong
     */
    private function queryLog(){
        $logs = R::getLogs();

        //只在调试模式下输出query logs
        if(!defined('DEBUG') || DEBUG){
            if(empty($logs)){
                $this->addInfo($this->isoDateTime() . "\t" . 'Nothing Executed', 'DAO');
            }else{
                $this->addInfo($this->isoDateTime() . "\t" . join(PHP_EOL, $logs) . PHP_EOL, 'DAO');
            }
        }
    }

    /**
     * 检查SQL语句
     * @author liwenlong
     */
    private function sqlCheck($sql){
        //SQL语句中不允许使用 *，包括SELECT * 和 COUNT(*) 
        if(strpos($sql, '*') !== false){
            throw new Exception('charactor [*] not allowed in sql', GlobalError::MYSQL_ERR_CODE);
        }
    }
}



