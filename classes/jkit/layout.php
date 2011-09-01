<?php defined('SYSPATH') or die('No direct script access.');

require_once Kohana::find_file('vendor', 'smarty3/Smarty.layout');

/**
 * 抽象类，定义 Layout 模板对象，在 View 中初始化和使用
 *
 * [!!]JKit_Layout 继承 [lafe](https://github.com/akira-cn/lafe) 的 [SmartyLayout] ，作为 Layout 的基类，提供 Layout 基础操作
 *
 * @package    JKit
 * @category   View
 * @author     akira.cn@gmail.com
 * @copyright  (c) 2011 WED Team
 * @license    http://kohanaframework.org/license
 */
abstract class JKit_Layout extends SmartyLayout{
	/**
	 * View对象调用此方法，在Layout子类里边重载
	 *
	 *     class Layout_Foo extends JKit_Layout{
	 *         function render(){
	 *             if(Client::user_agent()->is_pc()){
	 *                 return $this->pc->fetch();
	 *             }
	 *			   if(Client::user_agent()->is_iphone()){
	 *                 return $this->iphone->fetch();
	 *             }
	 *         }
	 *         protected function layout_pc($data){
	 *             //render pc
	 *         }
	 *         protected function layout_iphone($data){
	 *             //render iphone
	 *         }
	 *         private function common(){
	 *             //...
	 *         }
	 *     }
	 *
	 * [!!] Layout 的 render 方法是 Layout 的入口，在这个方法里，用户可以针对不同的设备和数据环境调用实际的 layout 处理方法
	 *
	 * @param string 默认的 view 文件路径
	 */
	abstract public function render($default_view_file);

	/**
	 * 标识文件搜索的根目录，详见 [Layout::_find]
	 *
	 * @var string
	 */
	public $resource_uri;

	/**
     * 构造函数，根据配置和参数生成 Layout 对象
	 *
	 * @param  array 配置参数
	 * @return void
	 * @uses   Arr::mix
	 */
	function __construct($opts){
		parent::__construct();

		$config = array_merge(JKit::$template_settings, $opts);

		Arr::mix($this, $config, true);
	}

	/**
	 * 重载 [SmartyLayout::_find] 方法
	 *
	 *     //in Controller_Sample::action_test
	 *     $this->_find('foo/bar', 'layout', 'php'); 
	 *     //先找 views/sample/test/layout/foo/bar.php
	 *     //如果找不到，会继续向上找 views/layout/foo/bar.php
	 *
	 * [!!] 优先 [Layout::$resource_uri] 所在目录下的对应模板文件  
	 * 如果找不到，查找 views 目录下的对应模板文件  
	 * 通用的子目录规则为 `$type/$file`  
	 *
	 * @param  string 要查找的文件路径
	 * @param  string 要查找的文件类型
	 * @param  string 要查找的文件扩展名
	 * @return string 文件完整路径
	 * @throws HTTP_Exception_404
	 */
	protected function _find($file, $type, $ext=NULL){
		$tplpath = dirname($this->resource_uri)."/";
		$subpath = "{$type}/{$file}"; 
		
		$path = Kohana::find_file('views', $tplpath.$subpath, $ext);

		if(!$path)
			$path = Kohana::find_file('views', $subpath, $ext); //当前路径下查找不到，找系统路径

		if(!$path){
			throw new HTTP_Exception_404('The requested URL :uri was not found on this server.',
											array(':uri' => $subpath));
		}
		return $path;
	}
}