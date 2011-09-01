# 快速上手指南 {#quickstart}

JKit 基于 [Kohana] 3.2，作为她的一个模块加载。 但因为JKit对Kohana底层的一些类(如Log)进行了扩展，所以要对配置文件进行一些特别的处理。

## 安装与基本配置 {#installing-and-setting}

1. [安装 Kohana](../kohana/install) ：参照文档正确安装和部署Kohana框架

1. 将JKit目录下的文件Copy到 MODPATH/JKit （MODPATH 是配置的模块安装目录，默认为 modules）

1. 修改 `application/bootstrap.php`

 [!!] 在Kohana核心加载完之后添加 JKit 核心类加载

        // Load the core Kohana class
        require SYSPATH.'classes/kohana/core'.EXT;

        // Load the core JKit class
        require MODPATH.'jkit/classes/jkit'.EXT;       
  
 [!!] 用JKit的 register_modules、init、init_modules 替代 Kohana 原先的操作

        //注册模块
        JKit::register_modules(array(
                'JKit'        =>  MODPATH.'JKit',             // the JKit framework
                // 'auth'       => MODPATH.'auth',            // Basic authentication
                // 'cache'      => MODPATH.'cache',           // Caching with multiple backends
                // 'codebench'  => MODPATH.'codebench',       // Benchmarking tool
                // 'database'   => MODPATH.'database',        // Database access
                // 'image'      => MODPATH.'image',           // Image manipulation
                // 'orm'        => MODPATH.'orm',             // Object Relationship Mapping
                //'unittest'   => MODPATH.'unittest',          // Unit testing
                //'sample'        =>        APPPATH.'sample',  //default sample
                //'userguide'  => MODPATH.'userguide',         // User guide and API documentation
        ));

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
                'base_url'   => '/',
                'cache_dir'  => DOCROOT.'/cache/',
                'index_file' => false,
                'charset' => 'utf-8',
                'caching' => DEBUG_MODE ? false : true,
                'errors' => DEBUG_MODE ? true : false,
                'profile' => DEBUG_MODE ? true : false,
        ));

        //初始化各个模块
        JKit::init_modules();

 [!!] 在JKit目录下有一份 `bootstrap.php.sample` 提供了例子

1. 配置 JKit/init.php

        <?php defined('SYSPATH') or die('No direct script access.');
        //开发环境配置
        JKit::$environment = JKit::DEVELOPMENT;

        /**
         * View/Layout 层模板相关的默认设置
         */
        JKit::$template_settings = array(
                'enable_php'            =>       true,        //是否允许使用php，关闭以加快解析速度
                
                'compile_dir'           =>        MODPATH.'jkit/views/.smarty/tpl_c/',
                'config_dir'            =>        MODPATH.'jkit/views/.smarty/configs/',
                'cache_dir'             =>        MODPATH.'jkit/views/.smarty/cache/',
                'debug_tpl'             =>        MODPATH.'jkit/views/debug.tpl',
                'template_dir'          =>        array(MODPATH.'jkit/views/',APPPATH.'views/'),
                'left_delimiter'        =>        '<%',
                'right_delimiter'       =>        '%>',
                'cache_lifetime'        =>        30,
                'caching'               =>        0,        //lifetime is per cache
                
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


## 几个必须知道的常量 {#consts-have-to-know} 
        
   - DOCROOT JKit的根目录
   - APPPATH 当前应用的根目录，默认为 `DOCROOT/application`
   - MODPATH 加载模块的根目录，框架可以加载很多第三方开发的模块，都放在这个目录下，默认为 `DOCROOT/modules`
   - SYSPATH Kohana框架系统目录，默认为 `DOCROOT/system`

[!!] 这些路径在Kohana安装之后的index.php中定义，一份index.php文件大概的内容如下：

        <?php
        /**
         * The directory in which your application specific resources are located.
         * The application directory must contain the bootstrap.php file.
         *
         * @see  http://kohanaframework.org/guide/about.install#application
         */
        $application = 'application';

        /**
         * The directory in which your modules are located.
         *
         * @see  http://kohanaframework.org/guide/about.install#modules
         */
        $modules = 'modules';

        /**
         * The directory in which the Kohana resources are located. The system
         * directory must contain the classes/kohana.php file.
         *
         * @see  http://kohanaframework.org/guide/about.install#system
         */
        $system = 'system';

        /**
         * The default extension of resource files. If you change this, all resources
         * must be renamed to use the new extension.
         *
         * @see  http://kohanaframework.org/guide/about.install#ext
         */
        define('EXT', '.php');

        /**
         * Set the PHP error reporting level. If you set this in php.ini, you remove this.
         * @see  http://php.net/error_reporting
         *
         * When developing your application, it is highly recommended to enable notices
         * and strict warnings. Enable them by using: E_ALL | E_STRICT
         *
         * In a production environment, it is safe to ignore notices and strict warnings.
         * Disable them by using: E_ALL ^ E_NOTICE
         *
         * When using a legacy application with PHP >= 5.3, it is recommended to disable
         * deprecated notices. Disable with: E_ALL & ~E_DEPRECATED
         */
        //error_reporting(E_ALL | E_STRICT);

        /**
         * End of standard configuration! Changing any of the code below should only be
         * attempted by those with a working knowledge of Kohana internals.
         *
         * @see  http://kohanaframework.org/guide/using.configuration
         */

        // Set the full path to the docroot
        define('DOCROOT', realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR);

        // Make the application relative to the docroot, for symlink'd index.php
        if ( ! is_dir($application) AND is_dir(DOCROOT.$application))
                $application = DOCROOT.$application;

        // Make the modules relative to the docroot, for symlink'd index.php
        if ( ! is_dir($modules) AND is_dir(DOCROOT.$modules))
                $modules = DOCROOT.$modules;

        // Make the system relative to the docroot, for symlink'd index.php
        if ( ! is_dir($system) AND is_dir(DOCROOT.$system))
                $system = DOCROOT.$system;

        // Define the absolute paths for configured directories
        define('APPPATH', realpath($application).DIRECTORY_SEPARATOR);
        define('MODPATH', realpath($modules).DIRECTORY_SEPARATOR);
        define('SYSPATH', realpath($system).DIRECTORY_SEPARATOR);

        // Clean up the configuration vars
        unset($application, $modules, $system);

        if (file_exists('install'.EXT))
        {
                // Load the installation check
                return include 'install'.EXT;
        }

        /**
         * Define the start time of the application, used for profiling.
         */
        if ( ! defined('KOHANA_START_TIME'))
        {
                define('KOHANA_START_TIME', microtime(TRUE));
        }

        /**
         * Define the memory usage at the start of the application, used for profiling.
         */
        if ( ! defined('KOHANA_START_MEMORY'))
        {
                define('KOHANA_START_MEMORY', memory_get_usage());
        }

        // Bootstrap the application
        require APPPATH.'bootstrap'.EXT;

        /**
         * Execute the main request. A source of the URI can be passed, eg: $_SERVER['PATH_INFO'].
         * If no source is specified, the URI will be automatically detected.
         */
        echo Request::factory()
                ->execute()
                ->send_headers()
                ->body();


## 文件目录 {#filepaths}

[!!] JKit的一个应用目录结构如下：  

  - APPPATH - 用户配置的应用路径，默认为 application
       - classes - 存放php类的目录
           - controller - MVC控制器
           - layout - Layout控制器
           - model - 数据Model
               - logic - 业务逻辑层
               - data - 数据实体层
               - sdk - Server端SDK
       - config - 数据配置文件
       - messages - 存放文本消息配置
       - views - 存放页面模板
           - layout - 通用的页面Layout
           - module - 通用的页面模块
       - bootstrap.php - 启动配置文件

## 文件名约定 {#filenames}

[!!] JKit沿用Kohana文件名约定:  
 `classes/foo/bar.php` 对应的类名是 Foo_Bar， `class/a/b/c.php` 对应的类名是 A_B_C  
 以这样命名方式命名的类，在文件中使用不需要 require，框架会自动加载  
 `MODPATH/{somemod}`和SYSPATH中的classes目录下可能会有同名的文件，这个时候，框架如果在加载APPPATH下找到了，就会加载APPPATH下的，找不到会加载MODPATH下的，再找不到，就会加载SYSPATH下的


## 开发第一个应用 {#first-app}

1. 在 APPPATH/classes/controller 下建立 welcome.php，输入如下代码：

        <?php defined('SYSPATH') or die('No direct script access.');

        class Controller_Welcome extends Controller {

                public function action_index()
                {
                        $this->response->body('hello, world!');
                }

        } // End Welcome

1. 访问 http://my.site/myapp/ （根据配置）可以看到 `hello world` 页面

1. 修改 welcome.php 增加一个名为sample的action

 [!!] action 是那些在 Controller 中以 action_ 开头的方法

        <?php defined('SYSPATH') or die('No direct script access.');

        class Controller_Welcome extends Controller {

                public function action_index()
                {
                        $this->response->body('hello, world!');
                }
                
                public function action_sample()
                {
                        $this->response->body(__Template__);
                }
        } // End Welcome

1. 访问 http://my.site/myapp/welcome/sample

 [!!] 华丽丽滴报错了

        HTTP_Exception_404 [ 404 ]: The requested template "welcome/sample" was not found.

1. 报错是正常的，因为我们还没有写view文件，添加之：

 [!!] 在 APPPATH/views/welcome/ 目录增加 sample.php 文件，输入：

        <h1>This is a sample view.</h1>

1. 刷新页面，这次看到正确的输出结果

 __This is a sample view.__

1. 这是渲染静态模板的方法，只有一行代码：`$this->response->body(__Template__);`， __Template__ 表示当前模板，它的目录规则对应 controller/action 的目录规则。

 [!!] Controller 所在目录决定 URL 和默认模板路径  
 例如 classes/controller/foo/bar.php 中名为 sample 的 action  
 对应的 url 为 http://my.site/foo_bar/sample  
 对应的模板路径为 views/foo/bar/sample.php

1. 如果只能渲染静态模板，那么代码再简单也不好玩，我现在想要渲染一个包含变量的模板

 [!!] 修改 action_sample 设置一个变量 `person`

        public function action_sample()
        {
                $this->template->set('person', 'akira');
                
                //$this->response->body(__Template__); 
                //你可以偷懒连上面一行都不写，因为框架会自动帮你做
        }

 [!!] 修改对应的 view：

        <h1>This is a sample view.</h1>
        <div>Hello <%$person|capitalize%>!</div>

1. 可以看到我们通过 `$this->template->set('person', 'akira')` 设置了一个变量，并且在view中利用 `<%$person|capitalize%>` 将这个变量显示到页面上。

 [!!] `$person|captalize` 对于熟悉 [Smarty](http://www.smarty.net/) 的同学相信并不陌生。  
 没错，JKit采用Smarty作为前端模板。只不过在init.php中把默认分割符替换成了 <% 和 %>

1. 我们现在已经完成了一个基础的action，现在如果我们想把默认的action设为这个，至少有三种不同办法。

    - 第一是将刚才的 action_sample 改成 action_index 删掉原来的 action_index，但同时记得将view的名字从sample.php改成index.php
    - 第二是将 action_index 修改，让它 forward 到 action_sample
    - 第三是修改路由规则，注意到bootstrap.php中最后几行：

                /**
                 * Set the routes. Each route must have a minimum of a name, a URI and a set of
                 * defaults for the URI.
                 */
                Route::set('default', '(<controller>(/<action>(/<id>)))')
                        ->defaults(array(
                                'controller' => 'welcome',
                                'action'     => 'index',
                        ));
        
        [!!] 将defaults的controller、action修改成自己希望的，可改变整个应用的首页，并且还能修改更复杂的规则，详细参考进阶应用的文档

1. 第一种方式要改文件名太麻烦，第三种方式改默认Route太暴力，所以我们用第二种方式：

    - 修改 action_index :

                public function action_index()
                {
                        //forward到welcome/sample，因为是当前controller，所以welcome被省略了
                        $this->request->forward('sample');
                }

        [!!] 这样我们就能直接通过 http://my.site/myapp/ 访问刚才的action了

1. 哦啦~ 我们的体验结束了，是不是很简单呢？ ^_^