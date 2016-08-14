<?php
/**
 * Controller层基类
 */
namespace Controller;

use CustomError;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Predis\Client;
use Curl\Curl;
use Exception;

 /**
  * Controller层基类，定义Controller层的通用方法
  */
class BaseController{
    const RET_CODE = "return_code";
    const RET_MSG = "return_msg";
    const RET_DATA = "data";
    const SUCCESS_CODE = 200;

    /** redis静态实例，需要使用 getRedis方法手动创建连接 **/
    private static $redis = array();

    /**
     * 构造函数
     */
    public function __construct(){
    }

    /**
     * 析构函数中自动释放redis链接
     */
    public function __destruct(){
        //释放redis链接
        foreach (self::$redis as $instance) {
            if($instance){
                $instance->disconnect();
            }
        }
    }

    /**
     *	必填参数检查
     *	@param array $required_params 必填项列表
     *	@param target
     *  @return array 验证通过返回空,否则返回错误信息
     */
    function checkParam($required_params, $target){
    	//参数检查
    	foreach ($required_params as $param_name) {
    		if(!isset($target[$param_name]) || $target[$param_name] === ""){
                throw new Exception("param [".$param_name."] cannot be null or empty", CustomError::PARAM_ERR_CODE);
            }
    	}
    }

    /**
     * 判断请求者身份是否通过
     * @author liwenlong
     */
    public function authenticate(){
        if(empty($GLOBALS['ticket'])){
            throw new Exception("authentication failed", CustomError::AUTH_ERR_CODE);
        }
    }

    /**
     * 生成标准的接口返回数据
     * @param string $return_code 错误码
     * @param string $return_msg 错误信息
     * @param array $return_data 错误数据
     * @return array 接口需要的标准返回值
     */
    function returnArray($return_code, $return_msg, $return_data=[]){
        $result = array();
        $result[self::RET_CODE] = $return_code;
        $result[self::RET_MSG] = $return_msg;
        $result[self::RET_DATA] = $return_data;
        return $result;
    }


    /**
     * 返回执行成功的数组数据
     * @param array $return_data 要返回的数据
     * @return array 接口需要的标准返回值
     */
    function success($return_data=[]){
        $result = array();
        $result[self::RET_CODE] = self::SUCCESS_CODE;
        $result[self::RET_MSG] = "";
        $result[self::RET_DATA] = $return_data;
        return $result;
    }

    /**
     * 返回执行失败的数组数据
     * @param string $error_code 错误码
     * @param string $error_msg 错误信息
     * @param array $error_data 错误数据
     * @return array 接口需要的标准返回值
     */
    function error($custom_error, $error_data=[]){
        $result = array();
        //error_code 添加前缀
        $error_code = ERROR_PREFIX.sprintf("%02d",abs($custom_error[0]));
        $result[self::RET_CODE] = $error_code;
        $result[self::RET_MSG] = $custom_error[1];
        $result[self::RET_DATA] = $error_data;
        return $result;
    }

    /**
     * 使用Monolog生成Debug日志.
     * 自动生成日期，context转化为json格式，输出到logs/debug目录下
     * @param string $message 日志信息
     * @param array $context 日志上下文
     * @return bool 日志记录是否成功
     */
    function addDebug($message, $context = array()){
        if(DEBUG){
            // Create the logger
            $logger = new Logger("debug");
            // Now add some handlers
            $date = date("Ymd",time());
            $file_path = __DIR__."/../../logs/debug/$date.log";
            $output = "%datetime%\t%message%\n%context%\n";
            $formatter = new LineFormatter($output);
            // Create a handler
            $stream = new StreamHandler($file_path, Logger::DEBUG);
            $stream->setFormatter($formatter);

            $logger->pushHandler($stream);
            // You can now use your logger
            if($context){
                return $logger->addDebug($message, $context);
            }else{
                return $logger->addDebug($message);
            }
        }
    }

    /**
     * 使用Monolog生成跟踪日志.
     * 无任何定义内容，直接将message按行记录，默认输出到logs/trace目录下，可通过folder参数指定其它文件夹
     * @param string $message 日志信息
     * @param string $folder 可指定logs目录下子文件夹名称
     * @return bool 日志记录是否成功
     */
    function addInfo($message, $folder = "trace"){
        // Create the logger
        $logger = new Logger("trace");
        // Now add some handlers
        $date = date("Ymd",time());
        $file_path = __DIR__."/../../logs/$folder/$date.log";

        $output = "%message%\n";
        $formatter = new LineFormatter($output);
        // Create a handler
        $stream = new StreamHandler($file_path, Logger::INFO);
        $stream->setFormatter($formatter);

        $logger->pushHandler($stream);
        // You can now use your logger
        return $logger->addInfo($message);
    }

    /**
     * Curl get请求
     * @param string $url 请求地址
     * @param array $data 请求数据
     * @param array $headers 请求头数据
     */
    function get($url, $data, $headers = []){
        $curl = new Curl();
         foreach ($headers as $key => $value) {
            $curl->setHeader($key, $value);
        }
        $curl->get($url, $data);
        $curl->close();
        if($curl->error){
            throw new Exception("cUrl get error:".$curl->error_code.",url:$url,response:".$curl->response, CustomError::CURL_ERR_CODE);
        }else{
            return $curl->response;
        }
    }
    /**
     * Curl post请求
     * @param string $url 请求地址
     * @param array $data 请求数据
     * @param array $headers 请求头数据
     */
    function post($url, $data, $headers = []){
        $curl = new Curl();
        foreach ($headers as $key => $value) {
            $curl->setHeader($key, $value);
        }
        $curl->post($url, $data);
        $curl->close();
        if($curl->error){
            throw new Exception("cUrl post error:".$curl->error_code.",url:$url,response:".$curl->response, CustomError::CURL_ERR_CODE);
        }else{
            return $curl->response;
        }
    }

    /**
     * Curl post请求(JSON格式)
     * @param string $url 请求地址
     * @param array $data 请求数据
     * @param array $headers 请求头数据
     */
    function postJSON($url, $json, $headers = []){
        $curl = new Curl();
        foreach ($headers as $key => $value) {
            $curl->setHeader($key, $value);
        }
        $curl->setHeader("Content-type", "application/json");
        $curl->post($url, $json);
        $curl->close();
        if($curl->error){
            throw new Exception("cUrl post error:".$curl->error_code.",url:$url,response:".$curl->response, CustomError::CURL_ERR_CODE);
        }else{
            return $curl->response;
        }
    }

    /**
     * 初始化Redis连接（因为Redis连接的使用频率并不是很高，所以不需要在蕨类的构造函数中初始化）
     * @param int $db_index 数据库索引
     * @param object redis连接实例
     */
    function getRedis($db_index = DB_INDEX){
        //同一个database只可以创建一个实例
        if(isset(self::$redis[$db_index])){
            $redis = self::$redis[$db_index];
        }else{
            try {
                $redis = new Client(array(
                    'host' => REDISHOSTNAME,
                    'port' => REDISPORT,
                    'password' => REDISPASSWORD
                ));
                $redis->select($db_index);
                self::$redis[$db_index]  = $redis;
            } catch (Exception $e) {
                throw new Exception($e->getMessage(), CustomError::REDIS_ERR_CODE);
            }
        }
        return $redis;
    }

    /**
     * 获取请求方IP地址
     * @param  bool $checkProxyHeaders 是否检查代理
     * @param  array $trustedProxies 可信任的代理
     * @return string
     */
    public function getClientIp($checkProxyHeaders = false, $trustedProxies = [])
    {
        $headersToInspect = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_CLIENT_IP'
        ];

        $ipAddress = null;
        if (isset($_SERVER['REMOTE_ADDR']) && $this->isValidIpAddress($_SERVER['REMOTE_ADDR'])) {
            $ipAddress = $_SERVER['REMOTE_ADDR'];
        }
        if ($checkProxyHeaders && !empty($trustedProxies)) {
            if (!in_array($ipAddress, $trustedProxies)) {
                $checkProxyHeaders = false;
            }
        }
        if ($checkProxyHeaders) {
            foreach ($headersToInspect as $header) {
                if (isset($_SERVER[$header])) {
                    $ip = trim(current(explode(',', $_SERVER[$header])));
                    if ($this->isValidIpAddress($ip)) {
                        $ipAddress = $ip;
                        break;
                    }
                }
            }
        }
        return $ipAddress;
    }

    /**
     * 获取服务器IP
     */
    public function getServerIp(){
        return $_SERVER['SERVER_ADDR'];
    }

    /**
     * Check that a given string is a valid IP address
     *
     * @param  string  $ip
     * @return boolean
     */
    private function isValidIpAddress($ip)
    {
        $flags = FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6;
        if (filter_var($ip, FILTER_VALIDATE_IP, $flags) === false) {
            return false;
        }
        return true;
    }

    /**
     * 从数组中选择指定的key
     * @param array $list 目标数组
     * @param array $field_list 筛选字段
     * @return array 筛选后的数据
     * @author gaoyuan
     */
    public function array_select($list, $field_list){
        foreach($field_list as $field){
            $return_list[$field] = $list[$field];
        }
        return $return_list;
    }

}


 ?>
