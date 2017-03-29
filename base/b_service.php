<?php  
/**
 * Base Service
 */

namespace SlimFastAPI;

use Curl\Curl;
use Exception;

/**
  * Service层基类
  */
class BaseService extends Base{
    /**
     * 构造函数
     */
    public function __construct(){
    }

    /**
     * Curl get请求
     * @param string $url 请求地址
     * @param array $data 请求数据
     * @param array $headers 请求头数据
     */
    public function httpGet($url, $data, $headers = [], $timeout_ms = 3000){
        $curl = new Curl();
         foreach ($headers as $key => $value) {
            $curl->setHeader($key, $value);
        }
        $curl->setOpt(CURLOPT_CONNECTTIMEOUT_MS, $timeout_ms);
        $curl->get($url, $data);
        $curl->close();
        if($curl->error){
            throw new Exception("CURL GET ERROR : ".$curl->error_code." , url : $url , response : ".$curl->response, GlobalError::CURL_ERR_CODE);
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
    public function httpPost($url, $data, $headers = [], $timeout_ms = 3000){
        $curl = new Curl();
        foreach ($headers as $key => $value) {
            $curl->setHeader($key, $value);
        }
        $curl->setOpt(CURLOPT_CONNECTTIMEOUT_MS, $timeout_ms);
        
        $curl->post($url, $data);
        $curl->close();
        if($curl->error){
            throw new Exception("CURL CPOST ERROR : ".$curl->error_code." , url : $url , response : ".$curl->response, GlobalError::CURL_ERR_CODE);
        }else{
            return $curl->response;
        }
    }

    /**
     * Curl post 请求 (数据格式为application/json)
     * @param string $url 请求地址
     * @param array $data 请求数据
     * @param array $headers 请求头数据
     */
    public function httpPostJson($url, $data, $headers = [], $timeout_ms = 3000){
        $curl = new Curl();
        foreach ($headers as $key => $value) {
            $curl->setHeader($key, $value);
        }
        $curl->setHeader("Content-type", "application/json");
        $curl->setOpt(CURLOPT_CONNECTTIMEOUT_MS, $timeout_ms);
        
        //数组转化成JSON字符串
        if(is_array($data)){
            $data = json_encode($data);
        }

        $curl->post($url, $data);
        $curl->close();
        if($curl->error){
            throw new Exception("CURL CPOST ERROR : ".$curl->error_code." , url : $url , response : ".$curl->response, GlobalError::CURL_ERR_CODE);
        }else{
            return $curl->response;
        }
    }

    /**
     * Curl put请求
     * @param string $url 请求地址
     * @param array $data 请求数据
     * @param array $headers 请求头数据
     */
    public function httpPut($url, $data, $headers = [], $timeout_ms = 3000){
        $curl = new Curl();
        foreach ($headers as $key => $value) {
            $curl->setHeader($key, $value);
        }
        $curl->setOpt(CURLOPT_CONNECTTIMEOUT_MS, $timeout_ms);
        
        $curl->put($url, $data);
        $curl->close();
        if($curl->error){
            throw new Exception("CURL PUT ERROR : ".$curl->error_code." , url : $url , response : ".$curl->response, GlobalError::CURL_ERR_CODE);
        }else{
            return $curl->response;
        }
    }
    /**
     * Curl delete请求
     * @param string $url 请求地址
     * @param array $data 请求数据
     * @param array $headers 请求头数据
     */
    public function httpDelete($url, $data, $headers = [], $timeout_ms = 3000){
        $curl = new Curl();
        foreach ($headers as $key => $value) {
            $curl->setHeader($key, $value);
        }
        $curl->setOpt(CURLOPT_CONNECTTIMEOUT_MS, $timeout_ms);
        
        $curl->delete($url, $data);
        $curl->close();
        if($curl->error){
            throw new Exception("CURL DELETE ERROR : ".$curl->error_code." , url : $url , response : ".$curl->response, GlobalError::CURL_ERR_CODE);
        }else{
            return $curl->response;
        }
    }
}



