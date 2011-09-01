<?php
/**
 * 描述RPC的抽象类
 *
 * @package    JKit
 * @category   RPC
 * @author     wulijun
 * @copyright  (c) 2011 WED Team
 * @license    http://kohanaframework.org/license
 */
abstract class JKit_Rpc_Abstract {
	/**
	 * @var string
	 */
    protected $_strServerName = '';

	/**
	 * @var mixed
	 */
    protected $_objBalance = null;

	/**
	 * @var array
	 */
    protected $_arrServers = array();

	/**
	 * @var mixed
	 */
    protected $_arrNowServer = null;

	/**
	 * @var array
	 */
    protected $_arrOption = array(
    	'ctimeout' => 200, //连接超时，单位毫秒
    	'rtimeout' => 1000, //读超时，单位毫秒
    	'wtimeout' => 1000, //写超时，单位毫秒
    	'balance' => null, //负载均衡对象
	);

	/**
	 * 执行RPC调用
	 * 
	 * @param array $arrInput key/value形式的参数
	 * @param int $intRetry 重试次数
	 * @return 执行成功返回调用的结果数据，失败返回false
	 */
    abstract public function call($arrInput, $intRetry = 1);
    /**
     * 负载均衡类会回调这个函数，并传入选中的服务器配置信息
     * 
     * @param array $arrServer 服务器配置信息，如IP、PORT等
     * @return mixed 连接参数指定的服务器失败时返回false，否则返回true或者连接对象
     */
    abstract public function realConnect($arrServer);
    
	/**
	 * 构造函数 
	 *
	 * @param string RPC Server 名字
	 * @param string RPC Server 配置信息（数组，因为可以配多个Server）
	 * @return void
	 */
    public function __construct($strServerName, $arrServers = array()) {
        $this->_strServerName = $strServerName;
        $this->_arrServers = $arrServers;
    }   
	
	/**
	 * 设置负载均衡等多个配置项
	 *
	 * @param array 配置项
	 */
    public function setOptions(array $arrConfig) {
    	if (isset($arrConfig['balance']) && is_string($arrConfig['balance'])) {
    		$arrConfig['balance'] = new $arrConfig['balance']();
    	}
    	$this->_arrOption = array_merge($this->_arrOption, $arrConfig);
    }
    
	/**
	 * 建立连接
	 *
	 * @param int 尝试重试次数
	 * return boolean 是否连接成功
	 */
    public function connect($intConnectRetry = 1) {
    	$_bolConnected = false;
    	
		$objBalance = $this->_getBalance();
		$objBalance->setServers($this, $this->_arrServers);
		while ($intConnectRetry > 0) {
			$_bolConnected = $objBalance->getConnection();
			if ($_bolConnected) {
				break;
			} else {
				JKit::$log->warn('connect to '. $this->_strServerName . ' fail! retry=' . $intConnectRetry);
			}
			$intConnectRetry--; 
		}

		return $_bolConnected;
    }
    
	/**
	 * 获得负载均衡器
	 *
	 * @return mixed
	 */
    protected function _getBalance() {
        if (! $this->_arrOption['balance']) {
            $this->_arrOption['balance'] = new JKit_Rpc_Balance_RoundRobin();
        }
        return $this->_arrOption['balance'];
    }
    
	/**
	 * 析构函数
	 *
	 * [!!]析构时删除负载均衡器
	 *
	 * @return void
	 */
    public function __destruct() {
    	unset($this->_arrOption['balance']);
    }
}