<?php
/**
 * 使用 [curl] (http://en.wikipedia.org/wiki/CURL)扩展获取HTTP请求的数据
 * 
 *     $objRpc = new Rpc_Http('baidu_news', array(array('host' => '220.181.112.138', 'port' => 80)));
 *     $strContent = $objRpc->call(
 * 		    array(
 * 			    'action' => '/n?cmd=1&class=internews&pn=1&from=tab',
 * 		    )
 *     );
 * 
 * @package    JKit
 * @category   RPC
 * @author     wulijun
 * @copyright  (c) 2011 WED Team
 * @license    http://kohanaframework.org/license
 */
class JKit_Rpc_Http extends JKit_Rpc_Abstract {
	/**
	 * @var CURL curl对象
	 */
    protected $_resConn = null;
    
    /**
	 * 构造函数
	 *
     * @param string $strServerName 服务名称
     * @param array $arrServers 服务器数组，如array(array('host' => '10.1.1.2', 'port' => 2), array('host' => '10.1.1.2', 'port' => 2))
     * @throws Exception
     */
    public function __construct($strServerName, $arrServers = array()) {
        if (! function_exists('curl_init')) {
            throw new Exception('curl extension must be loaded for using Rpc_Http!');
        }
        parent::__construct($strServerName, $arrServers);
    }
    
    /**
     * 进行HTTP交互
	 *
	 *     $param = $arrInput {
     * 		    action : $url,
     * 		    method : 'POST',
     * 		    post_vars : array(...),
     * 		    curl_opts : array(...),
     * 		    cookie : array(...),
     *     }
	 *     $strContent = $objRPC->call($param, 2);
	 *     $this->response->('content-type','text/html;charset=gbk')->send_headers()->body($strContent);
	 *
     * @param array  调用参数
	 * @param int    重试次数
     * @return mixed 成功返回HTTP请求的响应字符串，失败返回false
     */
    public function call($arrInput, $intRetry = 1) {
        $this->_init();
        if (isset($arrInput['curl_opts'])) {
            curl_setopt_array($this->_resConn, $arrInput['curl_opts']);
        }
        if (isset($arrInput['cookie']) && ! empty($arrInput['cookie'])) {
            $strCookie = ''; 
            foreach($arrInput['cookie'] as $key => $value) {
                $strCookie .= sprintf('%s=%s;', $key, $value);
            }
            curl_setopt($this->_resConn, CURLOPT_COOKIE, $strCookie);
        }
        if (isset($arrInput['method']) && (strtolower($arrInput['method']) == 'post') ) {
            curl_setopt($this->_resConn, CURLOPT_POST, 1);
            if(isset($arrInput['post_vars'])) {
                curl_setopt($this->_resConn, CURLOPT_POSTFIELDS, $arrInput['post_vars']);
            }
        }
        $strUrl = isset($arrInput['action']) ? $arrInput['action'] : '/index.html';
        $arrOutput = false;
        while ($intRetry--) {
        	$this->connect();
        	$arrNowServer = $this->_arrNowServer;
        	if (! $arrNowServer) {
        	    //找不到服务器配置 
        	    JKit::$log->warn('no server config: ' . $this->_strServerName);
        	    break;
        	}        	
        	if (isset($arrNowServer['port'])) {
        	    curl_setopt($this->_resConn, CURLOPT_URL, 
        	        $arrNowServer['host'] . ':' . $arrNowServer['port'] . $strUrl);
        	    curl_setopt($this->_resConn, CURLOPT_PORT, intval($arrNowServer['port']));
        	} else {
        	    curl_setopt($this->_resConn, CURLOPT_URL, $arrNowServer['host'] . $strUrl);
        	}
        	$dblStart = gettimeofday(true);
        	$arrOutput = curl_exec($this->_resConn);
        	$dblEnd = gettimeofday(true);
		    JKit::$log->debug('http call ' . $this->_strServerName . ', time ' . ($dblEnd - $dblStart)); 
        	$intErrno  = curl_errno($this->_resConn);
        	if ($arrOutput === false && $intErrno != 0) {
        		$strErr = sprintf('http request %s:%s failed,errno[%d],errmsg[%s]',
        	    	$this->_strServerName, $strUrl, $intErrno, curl_error($this->_resConn));
        	    JKit::$log->warn($strErr, $this->_arrNowServer);
        	} else {
        	    break;
        	}
        }

        return $arrOutput;
    }
    
	/**
	 * 得到错误号
	 *
	 * @return int
	 */
    public function getErrno() {
        if (!is_null($this->_resConn)) {
            return curl_errno($this->_resConn);
        }
        return 0;
    }
    
	/**
	 * 得到错误信息
	 *
	 * @return string
	 */
    public function getError() {
        if (!is_null($this->_resConn)) {
            return curl_error($this->_resConn);
        }
        return '';
    }
    
	/**
	 * 调用前初始化
	 *
	 * [!!] `$objRPC->call` 会自动调用该方法
	 *
	 * @return void
	 */
    protected function _init() {
        $this->_resConn = curl_init();
        $strUserAgent = Request::$user_agent;
        if (! empty($strUserAgent)) {
            curl_setopt($this->_resConn, CURLOPT_USERAGENT, $strUserAgent);
        }
        curl_setopt($this->_resConn, CURLOPT_HEADER, 0);
        curl_setopt($this->_resConn, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->_resConn, CURLOPT_RETURNTRANSFER, 1);
        if (defined('CURLOPT_CONNECTTIMEOUT_MS')) {
            //支持ms超时
            curl_setopt($this->_resConn, CURLOPT_NOSIGNAL, 1);
            if (is_numeric($this->_arrOption['ctimeout'])) {
                curl_setopt($this->_resConn, CURLOPT_CONNECTTIMEOUT_MS, $this->_arrOption['ctimeout']);
            }
            if (is_numeric($this->_arrOption['rtimeout'])) {
                curl_setopt($this->_resConn, CURLOPT_TIMEOUT_MS, $this->_arrOption['rtimeout']);
            } 
        } else {
            //不支持ms超时
            if (is_numeric($this->_arrOption['ctimeout'])) {
                $intTimeout = ceil($this->_arrOption['ctimeout'] / 1000);
                if ($intTimeout <1) $intTimeout = 1;
                curl_setopt($this->_resConn, CURLOPT_CONNECTTIMEOUT, $intTimeout);
            }
            if (is_numeric($this->_arrOption['rtimeout'])) {
                $intTimeout = ceil($this->_arrOption['rtimeout'] / 1000);
                if ($intTimeout <1) $intTimeout = 1;
                curl_setopt($this->_resConn, CURLOPT_TIMEOUT, $intTimeout);
            }
        }
    }
    
	//implements RPC_Abscract::realConnect
    public function realConnect($arrServer) {
    	if (is_array($arrServer)) {
	    	$this->_arrNowServer = $arrServer;
	    	return true;
    	} else {
    		$this->_arrNowServer = null;
    		return false;
    	}	    
	}

	/**
	 * 析构函数
	 *
	 * @return void
	 */
	public function __destruct() {
	    if (! is_null($this->_resConn)) {
	        curl_close($this->_resConn);
	    }
	    parent::__destruct();
	}
}
