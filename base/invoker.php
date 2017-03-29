<?php
/**
 * Invoker
 */

namespace SlimFastAPI;

use Exception;

/**
 * Method Invoker，Slim中间件
 */
class Invoker extends Base{

    /**
     * @param $request $response $next
     */
    
    /**
     * Slim Middleware 魔术方法，检查用户身份和数据类型
     * @param  object $request  request
     * @param  object $response response
     * @param  object $next     next
     * @return response         response
     */
    public function __invoke($request, $response, $next){
        //请求者身份
        $ticket = $request ->getHeaderLine('ticket');
        //请求方法
        $route = $request->getAttributes('routeInfo');
        $method = strtoupper($route['routeInfo']['request'][0]);
        $content_type = $request->getContentType();

        //非GET请求content-type检查
        if($method !== 'GET' && strtolower($content_type) != 'application/json'){
            //如果有请求中包括上传文件，则允许multipart/form-data类型的请求
            $files = $request->getUploadedFiles();
            if(empty($files) || strpos($content_type, 'multipart/form-data') === false){
                throw new Exception("content-type[$content_type] is invalid", GlobalError::PARAM_ERR_CODE);
            }
        }
        
        //记录ticket
        $GLOBALS['ticket'] = $ticket;

        //继续请求服务主体
        $response = $next($request, $response);

        $uri = $request->getUri();
        
        //提取请求的输入和输出
        if($method !== 'GET'){
            $input = (string)$request->getBody();
        }else{
            $input = $uri->getQuery();
        }
        $output_json = (string)$response->getBody();
        $output = json_decode($output_json, TRUE);

        //输出DEBUG日志
        $this->addDebug('debug', [
            'input' => $input,
            'output' => $output
        ]);

        return $response;
    }
}


