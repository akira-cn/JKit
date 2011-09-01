<?php
/**
 * 使用方法示例（需要先在config/rpc.php中配置好相应的服务）
 * 
 *      //HTTP类型
 *      $t = Rpc::call('baidu_news', '/', array()); //获取百度新闻的首页
 *      
 *      //Thrift类型
 *      include_once('/home/wulijun/tmp/gen-php/ku6reco/ku6reco_types.php');
 *      include_once(''/home/wulijun/tmp/gen-php/ku6reco/RecSvr.php');
 *      $objReq = new RecReq(array('vid' => 'vid1', 'taglist' => 'tag1 tag2'));
 *      //调用RecSvrClient::getRecDebug(RecReq $req, $bolIsQuick), 重试次数为2次
 *      $t = Rpc::call('reco_video', array('RecSvrClient', 'getRecDebug'), array($objReq, true), 2);
 *       
 *      TTServer类型
 *      $t = Rpc::call('reco', 'homepage_spotlight_rec'); //从TTServer中获取首页热点视频
 *      //从TTServer中获取首页热点视频和趋势视频，返回值如array('homepage_spotlight_rec' => ..., 'homepage_trend_rec' => ...)
 *      $t = Rpc::call('reco', array('homepage_spotlight_rec', 'homepage_trend_rec'));
 * 
 * @package    JKit
 * @category   RPC
 * @author     wulijun
 * @copyright  (c) 2011 WED Team
 * @license    http://kohanaframework.org/license
 */
class JKit_Rpc {
	/**
	 * 执行Rpc调用
	 * 
	 * 使用JKit::config("rpc.{$strServerName}")获取指定RPC的配置信息，然后执行调用
	 * 
	 * @param string $strServerName 服务名，对应与配置文件rpc.php中的数组key
	 * @param mixed $mixedAction 要执行的方法
	 * @param array $arrInput 调用方法的参数
	 * @param int $intRetry 重试次数
	 * @return 失败返回false，成功返回方法的结果
	 */
	public static function call($strServerName, $mixedAction, $arrInput = array(), $intRetry = 1) {
		$arrConf = JKit::config("rpc.{$strServerName}");
		
		if (! is_array($arrConf)) {
			$arrInput['action'] = $mixedAction;
			JKit::$log->warn("Rpc {$strServerName} config not exist", $arrInput);
			return false;
		}
		
		$objRealRpc = null;
		switch ($arrConf['type']) {
			case RPC_TYPE_HTTP:
				$objRealRpc = new Rpc_Http($strServerName, (array) $arrConf['server']);
				$arrInput['action'] = $mixedAction;
				$arrCallParam = $arrInput;
				break;
			case RPC_TYPE_THRIFT:
				$objRealRpc = new Rpc_Thrift($strServerName, (array) $arrConf['server']);
				$arrCallParam = array('action' => $mixedAction, 'args' => $arrInput);
				break;
			case RPC_TYPE_TTSERVER:
				$objRealRpc = new Rpc_Ttserver($strServerName, (array) $arrConf['server']);
				$arrInput['action'] = $mixedAction;
				$arrCallParam = $arrInput;
				break;				
			default:
				JKit::$log->warn("Rpc {$strServerName} type not support", $arrInput);
				break;
		}
		
		$mixedRes = false;
		if ($objRealRpc !== null) {
			$objRealRpc->setOptions((array) $arrConf['option']);
//			JKit::$log->debug(json_encode($arrCallParam));
			$mixedRes = $objRealRpc->call($arrCallParam, $intRetry);
//			JKit::$log->debug(json_encode($mixedRes));
		}
		
		return $mixedRes;
	}
}