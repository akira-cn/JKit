<?php
require_once Kohana::find_file('vendor', 'thrift/Thrift');
require_once($GLOBALS['THRIFT_ROOT'] . '/protocol/TBinaryProtocol.php');
require_once($GLOBALS['THRIFT_ROOT'] . '/transport/TSocket.php');

/**
 * 使用 [thrift](http://thrift.apache.org/) 接口获取数据
 * 
 *     require_once('thrift/Thrift.php'); //Thrift生成的PHP接口文件会用到其中的类型，所以需要在接口文件前包含
 *     //手动include Thrift生成的PHP接口文件
 *     $objReq = new RecReq(array('vid' => $strVid, 'taglist' => $strTag));
 *     $objRpc = new Rpc_Thrift('reco_video', array(array('host' => '10.1.1.2', 'port' => 8090)));
 *     $strContent = $objRpc->call(
 * 		    array(
 * 			    'action' => array('RecSvrClient', 'getRecDebug'),
 * 			    'args' => array($objReq, true),
 * 		    )
 *     );
 * 
 *     //Thrift 接口文件为：
 *     service RecSvr {
 *       RecResp getRec(1: RecReq req);
 *       RecResp getRecDebug(1: RecReq req,2: bool is_quickly);
 *     }
 * 
 * @package    JKit
 * @category   RPC
 * @author     wulijun
 * @copyright  (c) 2011 WED Team
 * @license    http://kohanaframework.org/license
 */
class JKit_Rpc_Thrift extends JKit_Rpc_Abstract {
	protected $_objTrans = null;

	/**
	 * 执行RPC调用
	 *
	 *     $param = array(
	 * 		    'action' => array(要调用的服务类名，要调用的方法名),
	 * 		    'args' => array(), 方法的参数
	 *     );
	 *     $strContent = $objRPC->call($param, 2);
	 *     $this->response->('content-type','text/html;charset=gbk')->send_headers()->body($strContent);
	 *
     * @param array  调用参数
	 * @param int    重试次数
     * @return mixed 成功返回请求的响应字符串，失败返回false
	 */
	public function call($arrInput, $intRetry = 1) {
		list($strClass, $strMethod) = $arrInput['action'];
		if (! $strClass || ! $strMethod) {
			JKit::$log->warn('Rpc call param action error', $arrInput);
			return false;
		}
		
        $arrOutput = false;
        while ($intRetry--) {
        	$bolConn = $this->connect();
        	if (! $bolConn) {
        		continue;
        	}
			if ($this->_arrOption['protocol'] !== null) {
				$strProtocolClass = $this->_arrOption['protocol'];
				if (! class_exists($strProtocolClass, false)) {
					include_once($GLOBALS['THRIFT_ROOT'] . "/transport/{$strProtocolClass}.php");
				}
				$objProto = new $strProtocolClass($this->_objTrans);
			} else {
				$objProto = new TBinaryProtocolAccelerated($this->_objTrans);
			}	
			$objClient = new $strClass($objProto);
			try {
				$arrOutput = call_user_func_array(array($objClient, $strMethod), $arrInput['args']);			
			} catch (Exception $e) {
				$strErrMsg = 'Exception ' . get_class($e) . '(' . $e->getFile() . ':' . $e->getLine()
					. ') ' . $e->getMessage();
				JKit::$log->warn($strErrMsg, $arrInput);
				$arrOutput = false;
			}
			$this->_objTrans->close();
			if ($arrOutput !== false) {
				break;
			}
        }

        return $arrOutput;	
	}
	
	//implements RPC_Abscract::realConnect
	public function realConnect($arrServer) {
		if (! isset($arrServer['host']) || ! isset($arrServer['port'])) {
			return false;
		}
		$objSocket = new TSocket($arrServer['host'], $arrServer['port']);
		//连接超时最短时间是1秒
		$intConnTimeout = (int) $this->_arrOption['ctimeout'];
		if ($intConnTimeout < 1000) {
			$intConnTimeout = 1000;			
		}
		$objSocket->setSendTimeout($intConnTimeout);		
		if ($this->_arrOption['transport'] !== null) {
			$strTransportClass = $this->_arrOption['transport'];
			if (! class_exists($strTransportClass, false)) {
				include_once($GLOBALS['THRIFT_ROOT'] . "/transport/{$strTransportClass}.php");
			}
			$objTrans = new $strTransportClass($objSocket);
		} else {
			$objTrans = new TBufferedTransport($objSocket);
		}	
		try {
			$objTrans->open();				
		} catch (Exception $e) {
			$objTrans = null;
			JKit::$log->warn($e->getMessage(), $arrServer);
		}
		
		if (! $objTrans) {
			return false;
		} else {
			if ($this->_arrOption['rtimeout']) {
				$objSocket->setRecvTimeout($this->_arrOption['rtimeout']);
			}
			if ($this->_arrOption['wtimeout']) {
				$objSocket->setSendTimeout($this->_arrOption['wtimeout']);
			}
			$this->_objTrans = $objTrans;
			$this->_arrNowServer = $arrServer;
			return true;
		}	
	}
}