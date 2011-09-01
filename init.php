<?php defined('SYSPATH') or die('No direct script access.');
/**
 * View/Layout 层模板相关的默认设置
 */
JKit::$template_settings = array(
	'enable_php'		=>  true,	//是否允许使用php，关闭将只允许Smarty语法，加快解析速度
	
	'compile_dir'		=>	MODPATH.'jkit/views/.smarty/tpl_c/',
	'config_dir'		=>	MODPATH.'jkit/views/.smarty/configs/',
	'cache_dir'			=>	MODPATH.'jkit/views/.smarty/cache/',
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

/**
 * Attach a file reader to config. Multiple readers are supported.
 */
JKit::$config->attach(new Config_File);

/**
 * Attach a file reader to config at dev.
 * 开发环境下，优先采用config/dev的配置
 */
if(JKit::$environment == JKit::DEVELOPMENT){
	JKit::$config->attach(new Config_File('config/dev'));
}

//日志
JKit::$log = Log::instance();

//Attach the file write to logging. Multiple writers are supported.
JKit::$log->attach(new Log_File(DOCROOT.'logs', 'JKit_Log',
		JKit::$environment == JKit::DEVELOPMENT ? Log_File::SPLIT_DAY : Log_File::SPLIT_HOUR
	)
	,JKit::$environment == JKit::DEVELOPMENT ? LOG::DEBUG : LOG::ERROR
);