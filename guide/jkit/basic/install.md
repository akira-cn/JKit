# 安装和配置 {#install-and-settings}

安装和详细配置 JKit for Kohana 3.2

[!!] JKit 基于 Kohana 3.2 版本开发，请选择正确的 Kohana 版本，以保证 JKit 的正确运行

## 快速安装 Kohana 和 JKit {#quick-set}

 1. 下载并正确配置 Kohana 3.2 版本 （[下载地址](http://kohanaframework.org/download))
 1. 获取JKit最新版 （[github 地址](https://github.com/akira-cn/JKit))
 1. 将JKit所有内容复制到 MODPATH/jkit 目录， copy bootstrap.php.sample 到 APPPATH/ 下，并更名为 bootstrap.php
 1. 访问 http://my.site/myapp/tests/welcome 如果看到 `Hello, Jkit!` 字样，说明安装成功

## bootstrap.php 中的配置项 {#options-in-bootstrap}

  __加载 Kohana 核心__

        // Load the core Kohana class
        require SYSPATH.'classes/kohana/core'.EXT;

  __用户可以在 APPPATH 中自己扩展 Kohana__

        if (is_file(APPPATH.'classes/kohana'.EXT))
        {
                // Application extends the core
                require APPPATH.'classes/kohana'.EXT;
        }
        else
        {
                // Load empty core extension
                require SYSPATH.'classes/kohana'.EXT;
        }

  __加载 JKit 核心__

        // Load the core JKit class
        require MODPATH.'jkit/classes/jkit'.EXT;

  __用户可以在 APPPATH 中自己扩展 JKit__

        if (is_file(APPPATH.'class/jkit'.EXT))
        {
                // Application extends the core
                require APPPATH.'classes/jkit'.EXT;        
        }

  __设置时区和编码__

        /**
         * Set the default time zone.
         *
         * @see  http://kohanaframework.org/guide/using.configuration
         * @see  http://php.net/timezones
         */
        date_default_timezone_set('Asia/Shanghai');

        /**
         * Set the default locale.
         *
         * @see  http://kohanaframework.org/guide/using.configuration
         * @see  http://php.net/setlocale
         */
        setlocale(LC_ALL, 'en_US.utf-8');

  __设置 Kohana 核心加载器__

        /**
         * Enable the Kohana auto-loader.
         *
         * @see  http://kohanaframework.org/guide/using.autoloading
         * @see  http://php.net/spl_autoload_register
         */
        spl_autoload_register(array('Kohana', 'auto_load'));

        /**
         * Enable the Kohana auto-loader for unserialization.
         *
         * @see  http://php.net/spl_autoload_call
         * @see  http://php.net/manual/var.configuration.php#unserialize-callback-func
         */
        ini_set('unserialize_callback_func', 'spl_autoload_call');

  __国际化默认语言__

        /**
         * Set the default language
         */
        I18n::lang('zh-cn');

  __Cookie 加密令牌__

        /**
         * 设置cookie加密令牌
         */
        Cookie::$salt = 'jkit';

  __JKit 环境变量设置__

        /**
         * Set Kohana::$environment if a 'KOHANA_ENV' environment variable has been supplied.
         *
         * Note: If you supply an invalid environment name, a PHP warning will be thrown
         * saying "Couldn't find constant Kohana::<INVALID_ENV_NAME>"
         */
        if (isset($_SERVER['KOHANA_ENV']))
        {
                JKit::$environment = constant('Kohana::'.strtoupper($_SERVER['KOHANA_ENV']));
        }
        else{
                JKit::$environment = JKit::DEVELOPMENT;
        }

[!!] 环境可以是4个不同级别，定义在 Kohana 核心中，Log、Profile等基础类的行为会受这个影响而改变

        const PRODUCTION  = 10;
        const STAGING     = 20;
        const TESTING     = 30;
        const DEVELOPMENT = 40;


  __注册 modules__

        JKit::register_modules(array(
                'jkit'          =>  MODPATH.'jkit',               // the JKit framework
                'tests'         =>  MODPATH.'jkit/tests',         // the tests for JKit
                // 'auth'       =>  MODPATH.'auth',               // Basic authentication
                // 'cache'      =>  MODPATH.'cache',              // Caching with multiple backends
                // 'codebench'  =>  MODPATH.'codebench',          // Benchmarking tool
                // 'database'   =>  MODPATH.'database',           // Database access
                // 'image'      =>  MODPATH.'image',              // Image manipulation
                // 'orm'        =>  MODPATH.'orm',                // Object Relationship Mapping
                // 'unittest'   =>  MODPATH.'unittest',           // Unit testing
                // 'userguide'  =>  MODPATH.'userguide',          // User guide and API documentation
        ));

  __初始化 JKit 核心__

        /**
         * Initialize Kohana, setting the default options.
         *
         * The following options are available:
         *
         * - string   base_url    path, and optionally domain, of your application   NULL
         * - string   index_file  name of your index file, usually "index.php"       index.php
         * - string   charset     internal character set used for input and output   utf-8
         * - string   cache_dir   set the internal cache directory                   APPPATH/cache
         * - boolean  errors      enable or disable error handling                   TRUE
         * - boolean  profile     enable or disable internal profiling               TRUE
         * - boolean  caching     enable or disable internal caching                 FALSE
         */
        JKit::init(array(
                'base_url'   => '/jkit/',
                'cache_dir'  => DOCROOT.'/cache/',
                'index_file' => false,
                'charset'    => 'utf-8',
                'caching'    => DEBUG_MODE ? false : true,
                'errors'     => DEBUG_MODE ? true  : false,
                'profile'    => DEBUG_MODE ? true  : false,
        ));

  __初始化各个模块__

        JKit::init_modules();

  __设置核心路由规则__

        /**
         * Set the routes. Each route must have a minimum of a name, a URI and a set of
         * defaults for the URI.
         */
        Route::set('default', '(<controller>(/<action>(/<id>)))')
                ->defaults(array(
                        'controller' => 'welcome',
                        'action'     => 'index',
                ));

## init.php 中的配置项 {#options-in-init}

[!!] JKit::init_modules() 会加载 MODPATH/jkit/init.php

  __设置模板__

        /**
         * View/Layout 层模板相关的默认设置
         */
        JKit::$template_settings = array(
                'enable_php'                =>  true, //是否允许使用php，关闭将只允许Smarty语法，加快解析速度
                
                'compile_dir'               =>  MODPATH.'jkit/views/.smarty/tpl_c/',
                'config_dir'                =>  MODPATH.'jkit/views/.smarty/configs/',
                'cache_dir'                 =>  MODPATH.'jkit/views/.smarty/cache/',
                'debug_tpl'                 =>  MODPATH.'jkit/views/debug.tpl',
                'template_dir'              =>  array(MODPATH.'jkit/views/',APPPATH.'views/'),
                'left_delimiter'            =>  '<%',
                'right_delimiter'           =>  '%>',
                'cache_lifetime'            =>  30,
                'caching'                   =>  0,    // lifetime is per cache
                
                //'compile_check' => true,
                //this is handy for development and debugging;never be used in a production environment.
                //'force_compile' => true,        
        );

  __CSRF和XSS安全设置__

        //安全设置
        JKit::$security['csrf'] = true;
        JKit::$security['xss']  = true;

[!!] 安全设置打开的话，在Controller中将自动对提交数据进行预处理。这个操作不会侵入系统本身的 $_GET 或 $_POST 等，但会影响 Request::param

  __设置 Config路径__

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

  __Log 文件配置__

        //日志
        JKit::$log = Log::instance();

        //Attach the file write to logging. Multiple writers are supported.
        JKit::$log->attach(new Log_File(DOCROOT.'logs', 'JKit_Log',
                        JKit::$environment == JKit::DEVELOPMENT ? Log_File::SPLIT_DAY : Log_File::SPLIT_HOUR
                )
                ,JKit::$environment == JKit::DEVELOPMENT ? LOG::DEBUG : LOG::ERROR
        );