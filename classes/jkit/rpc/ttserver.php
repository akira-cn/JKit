<?php
/**
 * 使用 [Memcache](http://php.net/manual/en/book.memcache.php) 扩展从 [TTServer](http://www.162cm.com/p/tokyotyrant.html) 获取数据
 *
 *     $objRpc = new Rpc_Ttserver('reco', array(array('host' => '220.181.112.138', 'port' => 20010)));
 *     $strContent = $objRpc->call(
 * 		    array(
 * 			    'action' => 'homepage_spotlight_rec',
 * 		    )
 *     );
 *     $arrContent = $objRpc->call(
 * 		    array(
 * 			    'action' => array('homepage_spotlight_rec', homepage_trend_rec'),
 * 		    )
 *     );
 *
 * [!!] 使用这个类需要安装 Memcache / TTserver 相应扩展
 *
 * @package    JKit
 * @category   RPC
 * @author     wulijun
 * @copyright  (c) 2011 WED Team
 * @license    http://kohanaframework.org/license
 */
class JKit_Rpc_Ttserver extends JKit_Rpc_Abstract {
	/**
	 * @var mixed
	 */
	protected $_objMemcache = null;
	//缓存每个服务的连接，这样一次请求中，同一个服务的服务器只会产生一次连接
	protected static $_arrMemcache = array();

	/**
	 * 执行RPC调用
	 *
	 *     $param = array(
	 *			'action' => strKey | array(strKey1, strKey2, ...)
	 *     );
	 *     $strContent = $objRPC->call($param, 2);
	 *     $this->response->('content-type','text/html;charset=gbk')->send_headers()->body($strContent);
	 *
	 * @param array 调用参数
	 * @param int   重试次数
	 * @return 失败返回false，成功返回从Ttserver取回的数据，如果action是strKey，则返回值是TTServer中该
	 * 		strKey对应的值；如果是array(strKey1, ...)形式，则返回值为key/value形式的数组，参考Memcache::get。
	 * @see Rpc_Abstract::call()
	 */
	public function call($arrInput, $intRetry = 1) {
		$arrOutput = false;
        while ($intRetry--) {
        	$bolConn = $this->connect();
        	if (! $bolConn) {
        		continue;
        	}
			$arrOutput = $this->_objMemcache->get($arrInput['action']);
			if ($arrOutput !== false) {
				break;
			}
        }

        return $arrOutput;	
	}

	/**
	 * 对TTServer做更新相关的操作
	 *
	 * @param  array 操作参数
	 * @param  int   重试次数
	 * @return mixed 操作结果
	 */
	public function doOp($arrInput, $intRetry = 1) {
		$arrOutput = false;
        while ($intRetry--) {
        	$bolConn = $this->connect();
        	if (! $bolConn) {
        		continue;
        	}
			$arrOutput = call_user_func_array(array($this->_objMemcache, $arrInput['action']), $arrInput['args']);
			if ($arrOutput !== false) {
				break;
			}
        }

        return $arrOutput;			
	}
	
	//implements RPC_Abscract::realConnect
	public function realConnect($arrServer) {
	    if (isset(self::$_arrMemcache[$this->_strServerName])) {
	        $arrCache = self::$_arrMemcache[$this->_strServerName];
	        $this->_objMemcache = $arrCache['obj'];
	        $this->_arrNowServer = $arrCache['server'];
	        return true;
	    }
		if (! isset($arrServer['host']) || ! isset($arrServer['port'])) {
			return false;
		}
		$objMemcache = new Memcache();
		$bolConn = $objMemcache->connect($arrServer['host'], $arrServer['port']);
	
		if (! $bolConn) {
			return false;
		} else {
			$this->_objMemcache = $objMemcache;
			$this->_arrNowServer = $arrServer;
			self::$_arrMemcache[$this->_strServerName] = array('obj' => $objMemcache, 'server' => $arrServer);
			return true;
		}	
	}
}
