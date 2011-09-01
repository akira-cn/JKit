<?php defined('SYSPATH') or die('No direct script access.');

require_once Kohana::find_file('vendor', 'smarty3/Smarty.class');

/**
 * 继承 [Smarty]，用于给 View 构建和输出页面
 * 
 *     $tpl = Template::factory();  //创建 Smarty 对象
 *     $tpl->assign('foo', 'bar');  //将数据赋给模板对象
 *     $tpl->display('foo/bar');    //渲染模板
 *     
 *     $layout = Template::factory('My_Layout'); //创建 Layout 对象
 *     $layout->assignLayout('foo','bar'); //传递给 Layout 处理器的变量
 *     $layout->a->display('foo/bar');  //渲染 Layout
 *
 * @package    JKit
 * @category   View
 * @author     akira.cn@gmail.com
 * @copyright  (c) 2011 WED Team
 * @license    http://kohanaframework.org/license
 */
class JKit_Template extends Smarty{

	public $resource_uri;
	
	/**
	 * 模板构造函数，根据 `CONFIG_PATH/smarty.settings` 中的配置和传入的配置数组初始化 Smarty 设置
	 *
	 * @param  array 配置参数数组
	 * @return void
	 * @uses   Arr::mix
	 */
	function __construct($opts=array())
	{
		$config = array_merge(JKit::$template_settings, $opts);
		
		parent::__construct();

		Arr::mix($this, $config, true);
	}

	/**
	 * 模板构造器，根据传入的类型创建不同的模板对象，默认为 `template`
	 *
	 * [!!] 类型可以传 JKit_Layout 的派生类，将生成 Layout 对象
	 *
	 * @param  string 模板对象类型名
	 * @param  array  配置参数数组
	 * @return Template|Layout 与类型名对应的模板对象
	 * @throws Kohana_Exception 指定模板类型不存在
	 */
	public static function factory($type='template', $opts=array()){
		if(class_exists($type)){
			return new $type($opts);
		}else{
			throw new Kohana_Exception('template class :type not found', array(':type'=>$type));
		}
	}

	/**
	 * 输出 Debug 信息
	 * 
	 * 当 [View::$debugging] 为 true 的时候， render 方法会调用这个方法输出 Debug 信息
	 *
	 * @param  Template|Layout
	 * @return string 调试信息
	 * @uses   Profiler::stop_by_group
	 */
	public static function fetch_debug($template){
		// Capture the view output
		ob_start();

		try
		{
			Smarty_Internal_Debug::display_debug($template);
		}
		catch (Exception $e)
		{
			// Delete the output buffer
			ob_end_clean();

			// Re-throw the exception
			throw $e;
		}

		// Get the captured output and close the buffer
		return ob_get_clean();
	}
}