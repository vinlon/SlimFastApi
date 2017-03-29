<?php
/**
 * Ip Service
 */

namespace Service;

class IpService extends AppService{
    CONST TAOBAO_IP_ADDR_API = 'http://ip.taobao.com/service/getIpInfo.php';
    CONST BAIDU_IP_ADDR_API = 'http://api.map.baidu.com/location/ip';
    CONST CURL_TIMEOUT_MS = 1000;

    public function __construct(){
        //调用父类构造函数
        parent::__construct();
    }

    /**
     * 查询IP地理位置
     * @author liwenlong
     */
    public function getLocation($ip, $source){
        switch ($source) {
            case 'taobao':
                $ip_location = $this->getLocation_TaoBao($ip);
                break;
            case 'baidu':
                $ip_location = $this->getLocation_Baidu($ip);
                break;
            default:
                throw new Exception('unknown source', ERROR);
                break;
        }
        
        return $ip_location;
    }

    /**
     * 使用淘宝的接口查询IP位置
     * 接口访问频率限制为10qps
     * @author liwenlong
     */
    private function getLocation_TaoBao($ip_address){
        //
        $json_result = $this->httpGet(self::TAOBAO_IP_ADDR_API, ['ip' => $ip_address], [], self::CURL_TIMEOUT_MS);
        
        $result = json_decode($json_result, true);

        $ip_info = [];

        if(isset($result['data']['region'])){
            $ip_info['province'] = $result['data']['region'];
        }

        if(isset($result['data']['city'])){
            $ip_info['city'] = $result['data']['city'];
        }

        if(isset($result['data']['county'])){
            $ip_info['region'] = $result['data']['county'];
        }

        return $ip_info; 
    }

    /**
     * 使用百度的接口查询IP位置
     * @author liwenlong
     */
    private function getLocation_Baidu($ip_address){
        $json_result = $this->httpGet(self::BAIDU_IP_ADDR_API, [
            'ak' => 'F5fc136fe3a156c411e21f2b7a57f326',
            'ip' => $ip_address
        ], [], self::CURL_TIMEOUT_MS);

        $result = json_decode($json_result, true);

        $ip_info = [];

        if(isset($result['content']['address_detail']['province'])){
            $ip_info['province'] = $result['content']['address_detail']['province'];
        }

        if(isset($result['content']['address_detail']['city'])){
            $ip_info['city'] = $result['content']['address_detail']['city'];
        }

        if(isset($result['content']['address_detail']['district'])){
            $ip_info['region'] = $result['content']['address_detail']['district'];
        }

        return $ip_info; 
    }

}

