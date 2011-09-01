<?php defined('SYSPATH') or die('No direct script access.');
/**
 * 继承 [Kohana] 增强某些功能  
 * 提供模块系统级别的全局配置的空间  
 *
 * @package    JKit
 * @category   Base
 * @author     akira.cn@gmail.com
 * @copyright  (c) 2011 WED Team
 * @license    http://kohanaframework.org/license
 */
class JKit extends Kohana{
	
	/**
	 * 默认的模板设置
	 *
	 *		JKit::$template_settings = array(
	 *			'enable_php'		=>  false,									//是否允许使用php，关闭以加快解析速度
	 *
	 *			//Smarty相关的配置
	 *			'compile_dir'		=>	MODPATH.'jkit/views/.smarty/tpl_c/',	
	 *			'config_dir'		=>	MODPATH.'jkit/views/.smarty/configs/',
	 *			'cache_dir'			=>	MODPATH.'jkit/views/.smarty/cache/',
	 *			'template_dir'		=>	array(APPPATH.'views/'),
	 *			'left_delimiter'	=>	'<%',
	 *			'right_delimiter'	=>	'%>',
	 *			'cache_lifetime'	=>	30,
	 *			'caching'			=>	0,	// lifetime is per cache
	 *		);
	 *
	 * @var array
	 */
	public static $template_settings = array();
	
	/**
	 * 安全方面的设置，可在 init 中自动启用 xss 和 csrf 防止跨站攻击
	 *
	 * @var array
	 */
	public static $security = array(
		'xss'  =>  false,
		'csrf' =>  false,
	);
	
	/**
	 * 读取配置的内容
	 *
	 *     $value = JKit::config('foo.bar');
	 *
	 * @param string 配置资源
	 * @return mixed 配置文件中的数据
	 */
	public static function config($source){
		return self::$config->load($source);
	}

	/**
	 * 注册要加载的模块到核心
	 *
	 *     JKit::register_modules(array('modules/foo', MODPATH.'bar'));
	 *
	 * [!!]
	 * 和 [Kohana::modules] 不同，JKit将模块的注册和初始化分开成两步，这样就解决了在模块中想重写 Kohana 核心类的纠结次序问题
	 *
	 * @param   array  list of module paths
	 * @return  array  enabled modules
	 */
	public static function register_modules(array $modules = NULL)
	{
		if ($modules === NULL)
		{
			// Not changing modules, just return the current set
			return self::$_modules;
		}

		// Start a new list of include paths, APPPATH first
		$paths = array(APPPATH);

		foreach ($modules as $name => $path)
		{
			if (is_dir($path))
			{
				// Add the module to include paths
				$paths[] = $modules[$name] = realpath($path).DIRECTORY_SEPARATOR;
			}
			else
			{
				// This module is invalid, remove it
				throw new Kohana_Exception('Attempted to load an invalid or missing module \':module\' at \':path\'', array(
					':module' => $name,
					':path'   => Debug::path($path),
				));
			}
		}

		// Finish the include paths by adding SYSPATH
		$paths[] = SYSPATH;

		// Set the new include paths
		self::$_paths = $paths;

		// Set the current module list
		self::$_modules = $modules;

		return self::$_modules;
	}
	
	/**
	 * 执行注册的模块的 init.php
	 *
	 * @return void
	 */
	public static function init_modules(){

		foreach (self::$_modules as $path)
		{
			$init = $path.'init'.EXT;

			if (is_file($init))
			{
				// Include the module initialization file once
				require_once $init;
			}
		}
	}

	/**
	 * 监测性能参数
	 *
	 * @param  string 组名
	 * @param  string 监测实例名
	 * @return boolean
	 */
	public static function profile($group, $name){
		if(self::$profiling)
		{
			Profiler::stop($group, $name);
			Profiler::start($group, $name);
		}
		return self::$profiling;
	}
}