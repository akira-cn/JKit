<?php defined('SYSPATH') or die('No direct script access.');
/**
 * 抽象类，在框架的 [Request] 流程中使用
 *
 *     $controller = new Controller_Foo($request);
 *     $controller->before();
 *     $controller->action_bar();
 *     $controller->after();
 *
 * [!!]JKit_Controller 继承 [Kohana_Controller] ，作为 Controller 的基类，是 JKit 的核心控制器
 *
 * @package    JKit
 * @category   Controller
 * @author     akira.cn@gmail.com
 * @copyright  (c) 2011 WED Team
 * @license    http://kohanaframework.org/license
 */
abstract class JKit_Controller extends Kohana_Controller{
	/**
	 * 当前框架流的_template
	 *
	 * [!!]这里作为静态的，是为了Response中可以获取到这个对象
	 *
	 * @var mixed
	 */
	protected static $_template;

	/**
	 * 获得当前框架的默认模板
	 *
	 * [!!]在before的时候初始化了这个模板
	 *
	 * @return mixed
	 */
	public static function template(){
		return self::$_template;
	}

	/**
	 * 在 Controller 的 action 被调用前自动运行： 重载了 [Kohana_Controller::before]  
	 * 如果设置了 `JKit::$environment=JKit::DEVELOPMENT` 并且在 url 中传递了 rdtest 参数，那么设置 [View::$debugging] 为 true，打开调试信息
	 *
	 * @return  void
	 */
	function before(){
		parent::before();

		self::$_template = View::factory(str_replace('_', '/', $this->request->controller()) . '/' . $this->request->action());

		if (JKit::$security['csrf'] && count($this->request->post()))
		{ //防止跨站请求伪造
			if(!Security::check($this->request->post('csrf_token'))){
				$this->handle_err(array('err'=>'sys.security.csrf'),'csrf detected');
			}
		}

		if(JKit::$security['xss'])
		{ //防止跨站脚本攻击
			$this->request->xss_clean();
		}
	}
	
	/**
	 * 在 Controller 的 action 被调用后自动运行： 重载了 [Kohana_Controller::after]    
	 * 如果设置了 [Controller::$template] 并且还未输出过，那么自动渲染模板输出  
	 *
	 * @return  void
	 */
	function after(){
		//如果模板存在并且还没渲染过，那么自动渲染一下
		//这个设计节省了一个 auto_render 参数，并且逻辑简单了
		//这里用 property_exists 避免了在这里触发 __get
		//判断一下 instanceof JKit_View 是避免别人继承其他 View 也来用这个变量导致误调用 $this->template->rendered
		if(property_exists($this, 'template') && $this->template instanceof JKit_View && !$this->template->rendered()){
			$this->response->body($this->template);
		}

		//追加调试信息
		if(JKit::$environment == JKit::DEVELOPMENT && $this->request->param('rdtest')){
			$this->response->debug();
		}
		
		parent::after();		
	}

	/**
	 * 重载魔术方法 __get，如果未创建过`$this->template`，那么创建之
	 *
	 *     functions action_bar(){
	 *         $data = $someLogic->someMethod();
	 *         $this->template->set($data); //这里触发$this->template的创建
	 *     }
	 *
	 * @param string 要获得的属性 key
	 * @return View  默认的模板，路径默认为 <controller:路径>/<action>.php
	 */
	public function & __get($key){
		if($key == 'template'){
			return $this->create_template();
		}
	}
	
	/**
	 * 建立一个模板赋给 `$this->template`，模板文件名可缺省。如缺省则 controller 转为路径 action 为文件名  
	 *
	 * [!!] 当前 controller 为 sample_foo， action 为 bar，那么 $this->create_template() 默认模板路径为: views/sample/foo/bar.php
	 * 
	 * @param  string 模板文件名
	 * @return mixed  当前模板
	 */
	protected function create_template($file=null){
		$this->template = self::$_template;
		if($file){
			$this->template->set_filename($file);
		}
		return $this->template;
	}

   /**
	* 格式化返回json, 提供基本默认规则，这个是用来通知页面执行PageLogic的，你可以在自己的Controller提供err处理逻辑
	*
	*     $this->err($data = $someLogic->someMethod()) or			 //handler logic error
	*	      $this->err($data += $anotherLogic->anotherMethod()) or //handler another logic error
	*		      $this->ok($data);									 //success!
	*
	* [!!]
	* 如果结果是 `{'err' : 'ok' [, ..]}`, 返回 false，否则：  
	*     如果是 alax 请求，返回 json 格式的结果  
	*     否则，调用错误处理函数  
	*
	* @param  mixed				任意数据
	* @param  string			错误信息
	* @param  string			默认错误状态码
	* @param  string			跳转 url
	* @return Response|boolean  如果 {'err' : 'ok'} 返回 false，否则根据情况返回 Response 或 处理错误
	*/
	protected function err($data=null, $msg = null, $default_err='sys.default', $forward=null){
		$result = Logic::parseResult($data, $msg, $forward, 'sys.default');

		//产生错误，处理错误逻辑
		if($result['err'] != 'ok'){
			//如果是ajax请求
			if($this->request->is_ajax()){ 
				//直接返回json结果
				$this->response->json($result, $callback)->send();
			}
			else{
				//不允许非ajax返回
				$this->handle_err((string)$this->response->json($result, $callback), 'non-ajax access deny');
				return true;
			}
		}

		return false;
	}
	
	/**
	 * 返回处理成功的结果
	 *
	 * [!!]
	 * 如果是 ajax 请求， 返回 `{'err' : 'ok' [,...]}`  
	 * 否则，如果有 forward， 跳转到 forward 指向的 url  
	 * 否则，处理成功结果
	 *
	 * @param  mixed			任意数据
	 * @param  string			跳转 url
	 * @param  string			回调函数
	 * @return Response|boolean 返回 Response 或者处理错误
	 */
	protected function ok($data=NULL, $forward=NULL, $callback=NULL){
		$result = Logic::parseResult($data, NULL, $forward, 'ok');

		if(!$this->err($result)){
			//如果是ajax请求
			if($this->request->is_ajax()){ 
				//直接返回json结果
				$this->response->json($result, $callback)->send();
			}else{
				//不允许非ajax返回
				$this->handle_err((string)$this->response->json($result, $callback), 'non-ajax access deny');
				return true;		
			}
		}
	}
	
	/**
	 * [Response::json] 的直接输出版本
	 *
	 * @uses Response::json
	 */
	protected function json($data, $callback=null){
		$this->response->json($result, $callback)->send();
	}

	/**
	 * [Response::jsonp] 的直接输出版本
	 *
	 * @uses Response::jsonp
	 */
	protected function jsonp($data, $callback=null){
		$this->response->jsonp($result, $callback)->send();
	}


	/**
	 * 表单校验函数，对表单进行校验
	 * 
	 *     if($this->valid($this->param(), $rules)){
	 *         $this->ok(); //提交成功
	 *     }
	 *
	 * [!!]表单校验成功返回 true， 失败处理 `{'err' : 'usr.submit.valid'}`
	 * 
	 * @param  Validation		要校验的对象
	 * @param  string			跳转 url
	 * @return Response|boolean 返回 Response 或者校验结果
	 */
	protected function valid($validation, $forward=NULL){
		return !$this->err($validation->check(), $validation->errors(),"usr.submit.valid",$forward);
	}

	/**
	 * 通用的错误处理函数，可重载，默认规则为：
	 *
	 * [!!]
	 * 如果 `JKit::$environment == JKit::DEVELOPMENT`， 那么输出 $err_result 的 debug 结果  
	 * 否则抛出 403 错误
	 *
	 * @param  array  错误结果
	 * @param  string 错误原因
     * @param  int    HTTP状态码
	 * @return void
	 * @throws HTTP_Exception_xxx
	 */
	protected function handle_err($err_result, $reason='some reason', $status=403){
		if(JKit::$environment == JKit::DEVELOPMENT){
			if($this->request->param('rdtest')){
				$this->response->debug(is_string($err_result) ? $err_result : json_encode($err_result));
			}
			$this->request->send_response();
		}else{
			$class = "HTTP_Exception_{$status}";
			throw new $class('Request to ":controller/:action" cause error for :reason.', array(
				':controller' => $this->request->controller(),
				':action' => $this->request->action(),
				':reason' => $reason,
			));
		}
	}
}