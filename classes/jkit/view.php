<?php defined('SYSPATH') or die('No direct script access.');
/**
 * View 类用于 [MVC](http://zh.wikipedia.org/wiki/MVC) 框架中初始化页面模板对象以及传递数据
 *
 *     function action_foo(){
 *         $view = View::factory('foo/bar');
 *         $view->set($this->_data);
 *         $this->response->body($view);
 *     }
 *
 * [!!]JKit_View 继承自 [Kohana_View]，提供对 php 模板以及 [Smarty](http://www.smarty.net/) 模板的支持
 *
 * @package    JKit
 * @category   View
 * @author     akira.cn@gmail.com
 * @copyright  (c) 2011 WED Team
 * @license    http://kohanaframework.org/license
 */
class JKit_View extends Kohana_View{
	/**
	 * 整个页面中都可以访问到的全局的数据
	 *
	 * @var array
	 */
	protected static $_global_data = array();

	/**
	 * 用来暂时存放给模板的变量，主要是给同时存在php和smarty两种混合模板的时候将php中的变量传给smarty
	 */
	protected static $_temp_local_data;
	
	/**
	 * 模板文件信息，根据不同情况会触发不同类型的模板对象的实例化，具体详见 [View::set_filename]
	 *
	 * @var array
	 */
	protected $_file = array('type' => 'template');

	/**
	 * 赋给模板的数据，分为两种情况：  
	 * 
	 * - 如果模板是 Template， 那么直接将数据 set 给当前模板
	 * - 如果模板是 Layout，那么将数据传给 Layout 对应的 layout 具体方法
	 *
	 * @var array
	 */
	protected $_data = array();

	/**
	 * 当前模板对象，类型为 [Template] 或 [Layout]，根据不同情况有区别，具体详见 [View::get_template_object]
	 *
	 * @var Template|Layout
	 */
	protected $_template;

	/**
	 * 调试状态，如果调试的话，render后会附带调试信息
	 *
	 * @var boolean
	 */
	public $debugging = false;

	/**
	 * 捕获 View 的输出，交给 [Controller] 进行处理
	 * 这个方法重载自 [Kohana_View]，会被 [View::render] 方法自动调用
	 *
	 *     $output = View::capture($file, $data);
	 *
	 * [!!] 如果模板是 [Layout]， 将数据传给 Layout 进行处理，否则先解析 php 再解析 smarty
	 *
	 * @param   string  输出的模板文件信息
	 * @param   array   传递给模板的变量
	 * @return  string
	 * @throws  HTTP_Exception_404
	 */
	protected static function capture($kohana_view_file, array $kohana_view_data)
	{
		$file = $kohana_view_file['path'];
		$type = $kohana_view_file['type'];
		$template = $kohana_view_file['template'];

		if(!$file){
			$uri = $kohana_view_file['uri'];
			throw new HTTP_Exception_404('The requested template ":uri" was not found.',
												array(':uri' => $uri));	
		}
		else
		{	
			if($template instanceof Layout){	//是layout
				/**
				 * Layout控制页面的布局，在模块化开发中一个View可以对应一个模板或者一个Layout对象
				 * Layout对象实际上也可以理解为页面布局的集合
				 * 它提供Web前端开发者控制页面布局的能力，并提供加载模块的方式
				 * 假如是Layout开发方式，views的结构为：
				 * views/<file_path>/file -- view_file / main_layout(page)
				 *					/file/*  -- sub_layouts	
				 * in main_layout ——
				 * <%extends file=$layout.file%>
				 * <%block name='a'%>
				 *		...
				 * <%/block%>
				 * ...
				 */
				$template->assignLayout($kohana_view_data);
				
				foreach(View::$_global_data as $key=>$value){
					$template->assignGlobal($key, $value);
				}

				return $template->render($file);
			}
			else{
				//全局变量
				foreach(View::$_global_data as $key=>$value){
					$template->assignGlobal($key, $value);
				}
				if(!JKit::$template_settings['enable_php']){ //直接作为Smarty解析
					//局部变量
					$template->assign($kohana_view_data);

					return $template->fetch($file);	
				}
				else{
					if(strpos($file, 'string:') === 0){ //字符串模板
						return $template->fetch($file);	
					}
					//先把php的内容解析了
					//$kohana_view_source = Kohana_View::capture($file, $kohana_view_data);
					$kohana_view_source = self::capturePHP($file, $kohana_view_data);
					$template->assign(self::$_temp_local_data);
					self::$_temp_local_data = NULL;

					try{
						return $template->fetch('string:'.$kohana_view_source);		
					}catch(Exception $ex){
						return $kohana_view_source;
					}
				}
			}
		}
	}
	// copy from Kohana_View::capture & set varibles to smarty template
	protected static function capturePHP($kohana_view_filename, array $kohana_view_data){
		// Import the view variables to local namespace
		extract($kohana_view_data, EXTR_SKIP);

		if (View::$_global_data)
		{
			// Import the global view variables to local namespace
			extract(View::$_global_data, EXTR_SKIP);
		}

		// Capture the view output
		ob_start();

		try
		{
			// Load the view within the current scope
			include $kohana_view_filename;

			// 删掉全局变量，以免重复加载
			foreach(View::$_global_data as $key=>$value){
				unset($$key);
			}

			// add additional variables to smarty
			unset($kohana_view_filename);
			unset($kohana_view_data);
			
			self::$_temp_local_data = get_defined_vars();
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

	/**
	 * 获得或创建当前模板对象，如果还未创建，先创建  
	 * 创建模板对象的类型根据 $this->_file['type']  
	 * 返回的模板对象可以用来直接操作：
	 *
	 *     $template = $view->get_template_object();
	 *     $template->assign($data);
	 *     $template->display($file);
	 *     exit;
	 *
	 * [!!] 如果对 view 进行 [View::set_filename]，可能会重新生成新的模板对象，那样对老对象的操作将被丢弃
	 *
	 * @return Template|Layout 返回当前模板对象
	 */
	public function get_template_object(){
		if($this->_template){
			return $this->_template;
		}else{
			$this->_template = Template::factory($this->_file['type']);
		}
		return $this->_template;
	}

	/**
	 * 设置模板文件信息
	 *
	 *     $view->set_filename($file);
	 *
	 * 规则如下：
	 *
	 * [!!]如果文件在 `classes/layout` 目录下存在，则将模板类型设置为对应的 [Layout]  
	 * 否则将模板类型设置为 Template
	 *
	 * @param   string  模板文件名
	 * @return  View  当前 View 对象
	 */
	public function set_filename($file)
	{
		if($this->_file && array_key_exists('path', $this->_file) 
		    && $file == $this->_file['path']){
			return $this;
		}

		if(strpos($file, 'string:') === 0){ //直接支持字符串模板
			$path = $file;
		}
		else{
			$path = Kohana::find_file('views', $file);
		}

		if(Kohana::find_file('classes/layout', $file)){ //有layout
			$this->_template = null; //重置模板，因为类型变了
			$this->_file = array(
				'uri' => $file,
				'path' => $path,
				'type' => 'layout_'.preg_replace('/\//','_',$file),
			);
		}
		else{ //是模板
			$this->_file = array(
				'uri' => $file,
				'path' => $path,
				'type' => 'template',
			);				
		}
		
		$this->_file['template'] = $this->get_template_object();
		$this->_file['template']->resource_uri = $this->_file['uri'];

		return $this;
	}
	
	/**
	 * 渲染模板：将数据赋给模板，并将模板对象渲染为字符串
	 * 
	 *     $output = $view->render();
	 *
	 * [!!] 当传递给模板的局部数据和全局数据重名的时候，将优先访问局部数据。如果模板类型为 [Layout]，
	 * 那么局部数据将发送给指定 Layout 对象，而不是模板本身。
	 *
	 * @param    string 模板文件信息
	 * @return   string 文本信息
	 * @throws   View_Exception
	 * @uses     View::capture
	 * @uses	 Template::fetch_debug
	 */
	public function render($file = NULL){
		return parent::render($file).($this->debugging?Template::fetch_debug($this->get_template_object()):'');
	}
} // End View