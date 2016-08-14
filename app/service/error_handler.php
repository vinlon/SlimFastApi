<?php
/**
 * Error Handle Service
 */

namespace Service;

use Controller\BaseController;

/**
* Error Handler
*/
class ErrorHandler extends BaseController{
	/**
	 * 错误处理接口地址
	 */
	const ERROR_HANDLER_API = "http://service.aikaka.com.cn/Util/error_handler/handle";


	/**
	 * 调用通用错误处理接口处理程序错误
	 * @param  string $error_code    错误码
	 * @param  string $error_message 错误信息
	 * @param  string $error_file    错误所在文件
	 * @param  string $error_line    错误所在行
	 * @param  string $source        错误来源
	 * @return boolean               接口是否执行成功
	 */
	public function handle($error_code, $error_message, $error_file, $error_line, $source){
		$error = [
	        'project_name' => PROJECT_NAME,
	        'mail_receiver' => PROJECT_OWNER,
	        'error_code' => $error_code,
	        "error_message" => $error_message,
	        "error_file" => $error_file,
	        "error_line" => $error_line,
	        "source" => $source
	    ];

	    try {
	    	$json = $this->post(self::ERROR_HANDLER_API, $error);
		    $result = json_decode($json,true);

		    return $result['return_code'] == 0;
	    } catch (Exception $e) {
	    	//该方法抛出的异常不应该再抛出，防止程序进入死循环
	    	return false;
	    }


	}
}

?>
