<?php defined('SYSPATH') or die('No direct script access.');
/**
 * View/Layout 层模板相关的默认设置
 */
JKit::$template_settings = array(
	'enable_php'		=>  true,	//是否允许使用php，关闭将只允许Smarty语法，加快解析速度
	
	'compile_dir'		=>	APPPATH.'views/_smarty/tpl_c/',
	'config_dir'		=>	APPPATH.'views/_smarty/configs/',
	'cache_dir'			=>	APPPATH.'views/_smarty/cache/',
	'debug_tpl'			=>  MODPATH.'jkit/views/debug.tpl',
	'template_dir'		=>	array(MODPATH.'jkit/views/',APPPATH.'views/'),
	'left_delimiter'	=>	'<%',
	'right_delimiter'	=>	'%>',
	'cache_lifetime'	=>	30,
	'caching'			=>	0,	// lifetime is per cache
	
	//'compile_check' => true,
	//this is handy for development and debugging;never be used in a production environment.
	//'force_compile' => true,	
);

//安全设置
JKit::$security['csrf'] = true;
JKit::$security['xss']  = true;
JKit::$security['non-ajax access'] = true;

/**
 * Attach a file reader to config. Multiple readers are supported.
 */
JKit::$config->attach(new Config_File);
