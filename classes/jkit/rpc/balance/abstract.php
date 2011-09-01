<?php
abstract class JKit_Rpc_Balance_Abstract {
	protected $_objServer = null; //Rpc_Abstract
	protected $_arrServers = array();
	protected $_intMaxConnectNum = 0; //最大连接次数
	protected $_intConnectNum    = 0; //已连接次数
    protected $_intBalanceKey    = null;	
	
    public function __construct($intBalanceKey = null) {
        if ($intBalanceKey !== null) {
            $this->setBalanceKey($intBalanceKey);
        } else {
            $this->_intBalanceKey = mt_rand();
        }
    }  
    
    public function setBalanceKey($intBalanceKey) {
        $this->_intBalanceKey = intval($intBalanceKey);
        return $this;
    }
    	
	public function setServers($objServer, $arrServers) {
		$this->_objServer = $objServer;
		$this->_arrServers = $arrServers;
		$this->_intMaxConnectNum = count($arrServers);
		return $this;
	}
	
    public function setMaxConnectNum($intNum) {
        $this->_intMaxConnectNum = intval($intNum);
        return $this;
    }

	/**
	 * 负载均衡获取一个有效的连接对象。
	 * 返回false，说明获取失败。
	 */
	abstract public function getConnection();
}