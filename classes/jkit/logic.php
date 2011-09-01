<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Model中业务逻辑的基类，其他Logic类继承这个类
 *
 * [!!]JKit_Logic 是 JKit 的业务逻辑层
 *
 * @package    JKit
 * @category   Model
 * @author     akira.cn@gmail.com
 * @copyright  (c) 2011 WED Team
 * @license    http://kohanaframework.org/license
 */
class JKit_Logic extends Model{
	/**
	 * 这个函数控制 [Controller::err] 和 [Controller::ok] 的逻辑，在自己的Logic里面可以根据情况Override，补充新的规则
	 *
	 * [!!] 
	 *  若 $data 为 true ， 得到 {'err' : 'ok'}  
	 *  若 $data 为 false 或 null， 得到 {'err' : $default_status}  
	 *  若 $data 为 {'err' : 'foo', 'data' : 'bar'} 得到 {'err' : 'foo', 'data' : 'bar'}  
	 *  若 $data 为其他对象，得到 {'err' : $default_status, 'data' : $data}  
	 *  若有 $msg 参数，设置 msg 属性到返回值  
	 *  若有 $forward 参数， 设置 forward 属性到返回值  
	 *
	 * @param  mixed  任意数据
	 * @param  string 默认结果状态码
	 * @param  string 错误信息
	 * @param  string 跳转 url
	 * @return array  逻辑结果
	 */
	public static function parseResult($data, $msg = null, $forward=null, $default_status='sys.default'){
		//如果$data为true说明成功
		if($data === true){	
			$result = array(
				'err' => 'ok',
			);
		}
		//-- 其他有错误信息的逻辑
		else if(is_null($data) || $data === false){
			$result = array(
				'err' => $default_status,
			);
		}
		//--Data中返回的err优先
		else if(is_array($data) && isSet($data['err'])){ //packed data
			$result = $data;
		}
		else{
			$result = array(
				'err' => $default_status,
			);
			$result['data'] = $data;
		}

		if(isSet($msg)){
			$result += array(
				'msg' => $msg,
			);
		}
		if(isSet($forward)){
			$result += array(
				'forward' => $forward,
			);
		}
		return $result;	
	}
}