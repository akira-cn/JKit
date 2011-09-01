<?php

require_once dirname(__FILE__)."/Smarty.class.php";

/**
 * [lafe](https://github.com/akira-cn/lafe) SmartyLayout 对象
 *
 * [!!]
 * __Layout__ 是一组描述页面结构的片段，它包含组成页面的拓扑信息，但通常不包括数据  
 * __Module__ 是包含数据的最终页面结构的片段  
 * __SmartyLayout__ 的作用是将 Layout 和 Module 片段拼装成最终的页面  
 *
 * @package    Smarty
 * @category   Base
 * @author     akira.cn@gmail.com
 * @copyright  (c) 2011 WED Team
 * @license    http://kohanaframework.org/license
 */
class SmartyLayout extends Smarty{
	
	/**
	 * 当前 layout 处理器
	 *
	 *     $layout->a->fetch($file);  //隐式设置 $_la_layout = 'a'
	 *
	 * [!!] 通过隐式转换生成，`$layout->a->fetch($file)` 实际调用 `$layout->layout_a($layout->_la_data)->fetch($file);`
	 * 
	 * @var string
	 */
	protected $_la_layout; 

	/**
	 * 实际要解析的 layout 路径  
	 * 用 __LAPATH__ 语法标记了一条由页面到 module 的路径  
	 *
	 *     function layout_a(){
	 *         $this->{'Main b.Left test'} = $data;
	 *         //'a.Main b.Left test' 就是一段 LAPATH
	 *         //LAPATH 可以是绝对路径，也可以是相对路径，绝对路径以 // 开头，相对路径参考 $_la_space
	 *     ｝
	 *
	 * [!!] LAPATH 的规则为 layout_file.layout_part#id layout_file.layout_part#id ... module_file  
	 * 或： (//)?layout_file.layout_part#id/layout_file.layout_part#id/.../module_file  
	 * // 开头表示是绝对路径，否则为相对路径  
	 *
	 * @var string
	 */
	protected $_la_path;  

	/**
	 * 存放_organize之后的结构化数据，包含了模板构建所需要的全部信息和模块用到的数据
	 *
	 * [!!] _organize 的时候会解析 $this->{$_la_path} = $data 自动生成这个结构化数据
	 *
	 * @var array
	 */
	protected $_la_page_struct = array(); 

	/**
	 * 这是一个快捷入口，为指定的 layout 赋某些变量而设置的，通常情况下不推荐使用
	 *
	 *     $this->_la_layout_xmap["a b#2"] += array("myclass" => "test", "css" => "color:red"); 
	 *     //为 layout : a b#2 添加两个变量 $layout.myclass 和 $layout.css
	 *
	 * @var array
	 */
	protected $_la_layout_xmap = array();

	/**
	 * 构造函数执行的时候可以初始化这个变量
	 *
	 *     $layout = new My_Layout(array('foo' => 'bar'));
	 *     $layout->a->fetch($file);
	 *
	 *     //in My_Layout:
	 *     function layout_a($data){
	 *          $this->{'Main test'} = $data;
	 *     }
	 *
	 *     //in test.tpl
	 *     <%$data.foo%> <!-- 显示 bar -->
	 *
	 * [!!] 若这个变量被初始化，$data 参数将被传递给 layout 处理器
	 *
	 * @var array
	 */
	protected $_la_data; 
	
	/**
	 * 模板路径，通常为指定目录下的模板文件  
	 * 这个文件通常的内容为
	 *
	 *     <%extends $layout.file%>
	 *     <%block moduleA%>
	 *     ...
	 *     <%/block%>
	 *     ...
	 *
	 * [!!] 这个路径通常是通过间接调用生成的
	 *
	 * @var string
	 */
	protected $_la_template;		//存放初始化的模板
	
	/**
	 * 用来存放 LAPATH 的前缀，当 LAPATH 为相对路径时，应加上这个前缀获得完整路径
	 *
	 * [!!] 默认的 `$_la_space` 为当前 layout 处理器的名字，可以通过 [SmartyLayout::with] 方法修改
	 *
	 * @var string
	 */
	protected $_la_space;		

	/**
	 * 构造函数，可以初始化传递给 layout 处理器的数据
	 *
	 * @param  array 传递给 layout 处理器的数据
	 * @return void
	 */
	function __construct($data=NULL){
		$this->_la_data = $data;	//渲染前传递给layout_func的数据
		parent::__construct();
	}
	
	/**
	 * 设置传递给 layout 处理器的数据
	 *
	 * @param  string|array 传递给 layout 处理器的数据
	 * @param  mixed		传递的变量的值
	 * @return SmartyLayout self
	 */
	public function assignLayout($varname, $value = null){
		if(is_null($this->_la_data)){
			$this->_la_data = array();
		}
		if(is_array($varname)){
			$this->_la_data = array_merge($this->_la_data, $varname);
		}else{
			$this->_la_data[$varname] = $value;
		}
		return $this;
	}

	/**
	 * 组织当前的模板的数据, 将数据组织好准备拼合模块  
	 *
	 * @return void
	 */
	protected function _organize(){
		if(isSet($this->_la_layout)){ //如果有layout，渲染layout
			$this->_la_space = $this->_la_layout;
			$this->{"layout_".$this->_la_layout}($this->_la_data);

			$this->_la_page_struct = array_pop($this->_la_page_struct);
			
			if($this->_la_page_struct){
				$_data = $this->_la_page_struct['layout'] + array('file' => $this->_la_page_struct['url']);
				$this->assignGlobal('layout', $_data);
			}
		} //如果没有，什么也不做，直接渲染模板
	}
	
	/**
	 * 重载 Samrty 的fetch，_organize 完再 fetch
	 *
	 * @param string|Smarty_Internal_Template 要解析的模板
	 * @param mixed 模板的 cache id
	 * @param mixed 模板的 compile id
	 * @param object 
	 * @param boolean 是否要立即输出结果
	 * @return string 渲染过的模板
	 */
	public function fetch($template, $cache_id = null, $compile_id = null, $parent = null, $display = false){
		$this->_la_template = $template;
		if(!$display)
			$this->_organize();
		return parent::fetch($template, $cache_id, $compile_id, $parent, $display);
	}

	/**
	 * 重载 Smarty 的 display， _organize 完再 display
	 *
	 * @param string|Smarty_Internal_Template 要解析的模板
	 * @param mixed 模板的 cache id
	 * @param mixed 模板的 compile id
	 * @param object 
	 * @return string 渲染过的模板输出
	 */
	public function display($template, $cache_id = null, $compile_id = null, $parent = null){
		$this->_la_template = $template;
		$this->_organize();
		parent::display($template, $cache_id, $compile_id, $parent);
	}
	
	/**
	 * 查找指定的文件  
	 *
	 * [!!]
	 * 根据文件名、类型、扩展名进行查找  
	 * 会先查找当前应用的模板下面的对应类型的路径  
	 * 找不到会查找用户定义的路径下面对应类型的路径  
	 * 再找不到会从模板根目录进行查找  
	 *
	 * @param  string 文件路径
	 * @param  string 文件类型
	 * @param  string 文件扩展名
	 * @return string 找到的文件完整路径
	 * @throws SmartyException 找不到文件
	 */
	protected function _find($file, $type, $ext='tpl'){
		/*
			eg: 
				template_dir : views/
				_la_template : sample/test.php 
				_find : a/b module

				=> views/sample/module/a/b.php
				=> views/module/a/b.php
		*/
		$tplpath = dirname($this->_la_template)."/";
		$subpath = "{$type}/{$file}".".{$ext}"; 

		foreach($this->template_dir as $dir){
			//先在应用的当前路径下找
			$fullpath = $dir.$tplpath.$subpath;  
			
			if(file_exists($fullpath)){
				break;
			}
		}

		//找不到去系统路径下找
		if(!file_exists($fullpath)){
			foreach($this->template_dir as $dir){
				//先在应用的当前路径下找
				$fullpath = $dir.$subpath;  
				
				if(file_exists($fullpath)){
					break;
				}
			}
		}
		if(!file_exists($fullpath)){ 
			throw new SmartyException("can't find layout files. {$file} in ".join('|',$this->template_dir));
		}
		return $fullpath;
	}

	/**
	 * 查找对应的 Layout
	 *
	 * @param  string 模板文件名
	 * @return string 找到的文件完整路径
	 * @throws SmartyException 找不到文件
	 */
	protected function find_layout($name){
		return $this->_find($name, 'layout');
	}
	
	/**
	 * 查找对应的 Module
	 *
	 * @param  string 模板文件名
	 * @return string 找到的文件完整路径
	 * @throws SmartyException 找不到文件
	 */
	protected function find_module($name){
		return $this->_find($name, 'module');
	}
	
	/**
	 * 查找对应的内联 css
	 *
	 * @param  string 模板文件名
	 * @return string 找到的文件完整路径
	 * @throws SmartyException 找不到文件
	 */
	protected function find_css($name){
		return $this->_find($name, 'module/css', 'css');
	}

	/**
	 * 查找对应的内联 js
	 *
	 * @param  string 模板文件名
	 * @return string 找到的文件完整路径
	 * @throws SmartyException 找不到文件
	 */
	protected function find_js($name){
		return $this->_find($name, 'module/js', 'js');
	}

	/**
	 * 将一组数据绑定到当前 `$_la_path` 下的指定 `$module_name` 的模块上  
	 * 	
	 *     $this->{'Main test'} = array(...);
	 *     // 隐式触发 $this->_la_path = 'Main'; $this->_bind("test", array(...));
	 *
	 * [!!] 这个方法由 layout 处理器中的 `$this->{$_la_path}` 赋值动作触发
	 *
	 * @param  $module_name string 模块名
	 * @param  $module_data array  绑定到模块的数据
	 * @return SmartyLayout self
	 * @throws SmartyException 没有合法的 `$_la_path` 路径
	 */
	protected function _bind($module_name, $module_data){
		
		if(!isSet($this->_la_path)){
			throw new SmartyException('you must spicified a part of layout!');
		}
		
		$file = $this->find_module($module_name);
		$module = array('url'=>$file,'data'=>$module_data);
		
		//layout可能级联，所以_la_path要explode
		/*
			_la_path的结构为 LayoutPart(.layout_name::LayoutPart)*
		 */
		$parts = explode(' ',$this->_la_path); //模块前面的部分
		$count = count($parts);

		$page_struct = &$this->_la_page_struct;
		
		$_layout_xpath = array();

		//寻路
		for($i = 0; $i < $count; $i++){
			
			$part = $parts[$i]; //part也是唯一标识
			list($layout_name, $layout_body) = explode('.', $part);
			$layout_file = explode('#', $layout_name);
			list($layout_file, $layout_id) = explode('#', $layout_name);
			
			array_push($_layout_xpath, $layout_name);

			$found = null;
			foreach($page_struct as &$part_struct){
				$_id = $part_struct['_id'];

				if(!strcmp($layout_name, $_id)){ //找到相同的name， 准备往里面插入数据
					$found = &$part_struct;
					unset($part_struct);
					break;
				}
			}
			
			if(!$found){ // 如果没找到layout建立一个
				$layout_info = array(
					'_id' => $layout_name,
					'url' => $this->find_layout($layout_file),
					'id' => $layout_id,
					'layout' => array(),
				);
				
				$this->_la_layout_xmap[join(' ',$_layout_xpath)] = &$layout_info['layout'];

				array_push($page_struct, $layout_info);
				$found = &$layout_info;

				unset($layout_info);
			}

			if(!isSet($found['layout'][$layout_body])) //如果没有设置过这个body
			{
				$found['layout'][$layout_body] = array(); //设置这个body，以供插入数据
			}

			$page_struct = &$found['layout'][$layout_body];
			
			unset($found);	
		}

		array_push($page_struct, $module);
		
		unset($page_struct);

		return $this;	
	}

	/**
	 * 魔术方法，支持 LAPATH 语法
	 *
	 *     $this->{'PartA layoutB.PartB moduleC'} = $data;
	 *     //相当于： $this->_la_path = 'PartA layout_b.PartB'; $this->_bind('moduleC', $data);
	 *
	 * @param string LAPATH串
	 * @param array  要绑定的数据
	 * @return void
	 */
	public function __set($key, $data){
		$la_path = trim($key);
		
		if(preg_match('/^\/\//', $la_path)){ //绝对路径用 // 开头
			$prefix = '';
			$la_path = trim(substr($la_path, 2));
		}else{
			$prefix = $this->_la_space.'.';
		}

		$tokens = preg_split('/\s|\//',$la_path); //路径以空格或 / 分割

		if(count($tokens) >= 2){
			$module_name = array_pop($tokens);
			$la_path = $prefix.join(' ', $tokens);
			$this->_la_path = strtolower($la_path);
			$this->_bind($module_name, $data);
		}
	}

	/**
	 * 魔术方法， 隐式调用 layout 处理器
	 *
	 *     $layout->a->display($file);  //相当于 $layout->_la_layout = 'a'; $layout->display($file);
	 *
	 * @param string layout 处理器的名字
	 * @return SmartyLayout self
	 */
	public function  & __get($key){
		if(!empty($key)){
			$this->_la_layout = $key;
			return $this;
		}
	}

	/**
	 * 给 layout 添加 css 样式
	 *
	 * [!!]
	 * css 样式统一交给 layout 管理  
	 * 如果是 modules 的 css 也是一样会给最外层的 layout 管理  
	 *
	 * @param string css 内联样式模板的名称
	 * @return void
	 */
	protected function css($name){
		$file = $this->find_css($name);
		$this->{"Css {$name}"} = array('src' => $file);
	}

	/**
	 * 给 layout 添加 js  
	 *
	 * [!!]
	 * js 统一交给 layout 管理  
	 * 如果是 modules 的 js 也是一样会给最外层的 layout 管理  
	 *
	 * @param string  内联 js 模板的名称
	 * @param boolean 是否插入在页面的 head 部分，若否，则插入在页面的最后一个模块
	 * @return void
	 */
	protected function js($name, $header = TRUE){
		$file = $this->find_js($name);
		if($header) $this->{"Js_header {$name}"} = array('src' => $file);
		else $this->{"Js_footer {$name}"} = array('src' => $file);
	}
	
	/**
	 * 设置 LAPATH 的前缀
	 *
	 * @param string 要设置的前缀空间
	 * @return void
	 */
	public function with($space){
		$this->_la_space = $space;
	}

	/**
	 * 恢复 LAPATH 的前缀为默认前缀
	 *
	 * @return void
	 */	
	public function endwith(){
		$this->_la_space = $this->_la_layout;
	}
}