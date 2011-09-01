<?php
/**
 * RoundRobin 轮询负载均衡算法
 */
class JKit_Rpc_Balance_RoundRobin extends JKit_Rpc_Balance_Abstract {   
	public function getConnection() {
	    $arrServers = (array) $this->_arrServers;
		$intTotalServers = count($arrServers);
		if ($intTotalServers < 1) {
			return false;
		} elseif ($intTotalServers == 1) {
			return $this->_objServer->realConnect($arrServers[0]);
		} else {
			$_intKey = $this->_intBalanceKey % $intTotalServers;
			$_resRet = $this->_objServer->realConnect($arrServers[$_intKey]);
			++$this->_intBalanceKey;
			++$this->_intConnectNum;
			if ($_resRet) return $_resRet;
			//超过最大连接次数，需要断开，不然有可能死循环。
			if ($this->_intConnectNum > $this->_intMaxConnectNum) 
			    return false;
		}
		return false;
	}
}