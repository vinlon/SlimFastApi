<?php
/**
 * Invoker
 */

namespace Controller;

use Service\ServiceLog;
use CustomError;
use Exception;
/**
 * Method Invoker
 */
class Invoker extends BaseController{

    /**
     * Slim Middleware 魔术方法，检查用户身份和数据类型
     * @param $request $response $next
     */
    public function __invoke($request, $response, $next){
        //开始时间
        $start = microtime(true);

        //请求者身份
        $ticket = $request ->getHeaderLine("ticket");
        //请求方法
        $route = $request->getAttributes("routeInfo");
        $method = strtoupper($route['routeInfo']['request'][0]);
        $content_type = $request->getContentType();

        if($method !== "GET" && strtolower($content_type) != "application/json"){
            //除GET请求外，其它请求Content-type要求为application/json
            throw new Exception("content-type[$content_type] is invalid", CustomError::PARAM_ERR_CODE);
        }else{
            //记录ticket
            $GLOBALS['ticket'] = $ticket;

            //继续请求服务主体
            $response = $next($request, $response);
        }




        //从地址中提取服务名称
        $uri = $request->getUri();
        $path = $uri->getPath();
        $service_name =  str_replace("/", ":", trim($path, "/"));
        //提取请求的输入和输出
        if($method !== "GET"){
            $input = (string)$request->getBody();
        }else{
            $input = $uri->getQuery();
        }
        $output_json = (string)$response->getBody();
        $output = json_decode($output_json, TRUE);

        //计算服务执行花费的时间（秒）
        $cost = microtime(true) - $start;

        //根据LOG_ENABLE的值决定是否开启日志记录
        if(LOG_ENABLE){
            $service_log = new ServiceLog();
            $service_log->log($service_name, $input, $output, $cost);
        }
        //DEBUG开启时将日志输出到本地
        if(DEBUG){
            $this->addDebug('debug', [
                'input' => $input,
                'output' => $output,
                'cost' => $cost
            ]);
        }

        return $response;
    }
}

?>
