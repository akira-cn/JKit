<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Array helper.
 *
 * [!!] 扩展 [Kohana_Arr] 方法
 *
 * @package    JKit
 * @category   Helpers
 * @author     akira.cn@gmail.com
 * @copyright  (c) 2011 WED Team
 * @license    http://kohanaframework.org/license
 */
class JKit_Arr extends Kohana_Arr{
	/**
	 * 将关联数组的属性-值copy到对象上去
	 *
	 *     Arr::mix($myobj, array('foo' => 'bar'), true);
	 *
	 * @param mixed   被copy到的对象
	 * @param array   关联数组
	 * @param boolean 是否覆盖对象上已有属性
	 * @return mixed  被copy到的对象
	 */
	public static function mix($obj, $hash, $override=false){
		foreach($hash as $key => $value){
			if($override || !isset($obj->{$key})){
				$obj->{$key} = $value;
			}
		}
		return $obj;
	}
}