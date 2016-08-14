<?php 
/**
 * Service Request Log
 */

namespace Service;

use Controller\BaseController;

/**
 * Service Log
 */
class ServiceLog extends BaseController{
	/**
	 * 异步请求发送接口
	 */
	const RESQUE_HTTP_API = "http://service.aikaka.com.cn/Util/resque/httpRequest";
	/**
	 * 服务请求日志记录接口
	 */
	const SERVICE_RECORD_API = "http://service.aikaka.com.cn/Report/record/add";

	/**
	 * 记录服务请求日志
	 * @param  string $service_name 服务名称
	 * @param  array $input         输入
	 * @param  array $output        输出
	 * @param  float $cost          请求耗时
	 * @return boolean              是否记录成功
	 */
	public function log($service_name, $input, $output, $cost){
		$service_record = [
            'client_ip' => $this->getClientIp(true),
            'server_ip' => $this->getServerIp(),
            'service_domain' => SERVICE_DOMAIN,
            'service_namespace' => SERVICE_NAMESPACE,
            'service_name' => $service_name,
            'request_time' => date("Y-m-d H:i:s",time()),
            'cost' => $cost,
            'return_code' => isset($output[self::RET_CODE])?$output[self::RET_CODE]:"unknown",
            'input' => $input,
            'output' => json_encode($output)
        ];

        //发送异步请求
        $json = $this->post(self::RESQUE_HTTP_API,[
            'type' => "POST",
            'url' => self::SERVICE_RECORD_API, 
            'json_data' => json_encode($service_record) 
        ]);

        $result = json_decode($json, true);
        return $result['return_code'] == 0;
	}



}



 ?>