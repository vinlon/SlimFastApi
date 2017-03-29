<?php
/**
 * Controller层基类
 * @author liwenlong
 */

namespace SlimFastAPI;

use Predis\Client;
use Exception;

 /**
  * Controller层基类，定义Controller层的通用方法
  * @author liwenlong
  */
class BaseController extends Base{
    /**
     * 标准返回码的KEY
     */
    const RET_CODE = 'return_code';

    /**
     * 标准返回消息的KEY
     */
    const RET_MSG = 'return_msg';

    /**
     * 标准返回数据的KEY
     */
    const RET_DATA = 'data';

    /**
     * 执行成功的返回码
     */
    const SUCCESS_CODE = 200;

    /**
     * redis静态实例，保存使用getRedis方法手动创建的连接，防止重复连接以及方便统一释放
     */
    private static $redis = array();

    /**
     * 构造函数
     * @author liwenlong
     */
    public function __construct(){
    }

    /**
     * 析构函数中自动释放redis链接
     * @author liwenlong
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
     * 必填参数验证，验证不通过直接抛出异常
     * @param array $required_params 必填项列表
     * @param array $target 目标参数数组
     * @author liwenlong
     */
    public function checkParam($required_params, $target){
        //参数检查
        foreach ($required_params as $param_name) {
            if(!isset($target[$param_name]) || $target[$param_name] === ''){
                throw new Exception('param ['.$param_name.'] cannot be null or empty', GlobalError::PARAM_ERR_CODE);
            }
        }
    }

    /**
     * 抛出参数错误的异常
     * 在业务层不能使用全局错误码PARAM_ERR_CODE，故通过此方法抛出异常
     * @param  string $message 错误信息
     * @author liwenlong
     */
    protected function paramError($message){
        throw new Exception($message, GlobalError::PARAM_ERR_CODE);
    }

    /**
     * 获取请求者身份标识，Ticket不存在直接抛出异常
     * @return string ticket
     * @author liwenlong
     */
    protected function getTicket(){
        if(empty($GLOBALS['ticket'])){
            throw new Exception('ticket not found', GlobalError::AUTH_ERR_CODE);
        }
        return $GLOBALS['ticket'];
    }

    /**
     * 抛出身份认证错误的异常
     * 在业务层不能使用全局错误码AUTH_ERR_CODE，故通过此方法抛出异常
     * @param  string $message 错误信息
     * @author liwenlong
     */
    protected function authError($message){
        throw new Exception($message, GlobalError::AUTH_ERR_CODE);
    }


    /**
     * 生成标准的接口返回数据
     * @param GlobalError 错误信息
     * @param array $return_data 错误数据
     * @return array 接口需要的标准返回值
     * @author liwenlong
     */
    public function returnArray($custom_error, $return_data=[]){
        $result = array();
        $result[self::RET_CODE] = $custom_error[0];
        $result[self::RET_MSG] = $custom_error[1];
        $result[self::RET_DATA] = $return_data;
        return $result;
    }


    /**
     * 返回执行成功的数组数据
     * @param array $return_data 要返回的数据
     * @return array 接口需要的标准返回值
     * @author liwenlong
     */
    public function success($return_data=[]){
        $result = array();
        $result[self::RET_CODE] = self::SUCCESS_CODE;
        $result[self::RET_MSG] = '';
        $result[self::RET_DATA] = $return_data;
        return $result;
    }

    /**
     * 返回执行失败的数组数据，自动在错误码前附加项目前缀
     * @param GlobalError 错误信息
     * @param array $error_data 错误数据
     * @return array 接口需要的标准返回值
     * @author liwenlong
     */
    public function error($custom_error, $error_data=[]){
        $result = array();
        //error_code 添加前缀
        $error_code = ERROR_PREFIX.sprintf("%02d",abs($custom_error[0]));
        $result[self::RET_CODE] = $error_code;
        $result[self::RET_MSG] = $custom_error[1];
        $result[self::RET_DATA] = $error_data;
        return $result;
    }

    /**
     * 初始化Redis连接（因为Redis连接的使用频率并不是很高，所以不需要在构造函数中初始化）
     * @param int $db_index 数据库索引
     * @param object redis连接实例
     * @author liwenlong
     */
    public function getRedis($db_index = DB_INDEX){
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
                throw new Exception($e->getMessage(), GlobalError::REDIS_ERR_CODE);
            }
        }
        return $redis;
    }

    /**
     * 获取请求方IP地址
     * @param  bool $checkProxyHeaders 是否检查代理
     * @param  array $trustedProxies 可信任的代理
     * @return string 客户端IP地址
     * @author liwenlong
     */
    public function getClientIp($checkProxyHeaders = true, $trustedProxies = [])
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
     * 获取服务器IP，使用集群部署时可以获取节点的IP
     * @return string 服务器端IP地址
     * @author liwenlong
     */
    public function getServerIp(){
        return $_SERVER['SERVER_ADDR'];
    }

    /**
     * 验证IP地址是否合法
     * @param  string  $ip IP地址
     * @return boolean 是否合法
     * @author liwenlong
     */
    private function isValidIpAddress($ip)
    {
        $flags = FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6;
        if (filter_var($ip, FILTER_VALIDATE_IP, $flags) === false) {
            return false;
        }
        return true;
    }

}


 
