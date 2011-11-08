<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Tests extends Controller {
	
	public function action_index()
	{
		$this->request->forward('tests/welcome');
	}

	public function action_welcome(){
		$this->response->body('hello, JKit!');
	}

	public function action_tpl(){
		$view = View::factory('smarty/test');
		$view->set('person','akira');

		$this->response->body($view);
	}

	public function action_json(){
		return $this->response->jsonp(array('err'=>'ok'));
	}

	public function action_stringview(){
		$this->template = View::factory('string:hello <%$person%>!');
		$this->template->set('person', 'Akira');
		//$this->response->body($this->template);
	}

	public function action_config(){
		Kohana::$config->attach(new Config_File('config/sample')); //开发环境可以增加新的config目录
		print_r(Kohana::$config->load('sample.test'));
	}

	public function action_log(){
		JKit::$log->debug('test');
		echo 'done';
	}

	public function action_debug(){
		echo Debug::vars(array('a'=>1,'b'=>2));
	}

	public function action_layout(){
		$this->template = View::factory('lafe/test');
	}

	public function action_render(){
		$this->template->set('a',1);
		$this->template->data = array('c' => 3); //注意：只能赋局部变量
		View::set_global('test',array('b',2)); //全局变量用set_global或bind_global
	}

	public function action_err(){
		$err = $this->request->param('err','sys.default');
		$this->err(array('err'=>$err));
	}

	public function action_ok(){
		$this->err(true);
		$this->ok();
	}

	public function action_valid(){
		$fields = array(
			'username'=>'1111.1',
			'password'=>'121',
			'confirm'=>'121',
			'testrd' => '1d',
			'testrd2' => '350103198112294930',
			'testrd3' => '1981',
			'testrd4' => 'akira.cn@gmail.com',
			'vcode'   => 'om33',
		);
		
		$rules = array(				
					'@username' => array(
						'reqmsg' => ' 不能为空！',
						'datatype' => 'n-7.2',
					),
					'@password' => array(
						'datatype' => 'n-7',
						'reqmsg' => ' 不能为空！',
					),
					'@confirm' => array(
						'datatype' => 'reconfirm',
						'reconfirmfor' => 'password',
					),
					'@testrd' => array(
						'reqmsg' => '不能为空！',
						'datatype' => 'n',
					),
					'@testrd2' => array(
						'datatype' => 'idnumber',
					),
					'@testrd3' => array(
						'datatype' => 'daterange',
					),
					'@testrd4' => array(
						'datatype' => 'magic',
						'magic-pattern' => 'idnumber||email',
					),
				);
		$validation = new Validation($fields, $rules);

		//自定义复杂规则
		function complex($str){
			return Valid::idnumber($str) || Valid::email($str);
		}
		$validation->rule('testrd4','complex');

		$validation->rule('vcode','not_empty');
		$validation->rule('vcode','VCode::check');

		$this->valid($validation) and
			$this->ok();
	}

	public function action_ddos(){
		DDOS::antispam(30);
		$this->ok();
	}

	public function action_csrf(){
		$this->ok();
	}

	public function action_xss(){
		echo HTML::clean('<script>xss</script><b>H</b>ello <em>World');
		echo HTML::clean('<a href="#">abc</a><em>de<b>f</em>', array('HTML.Allowed'=>'em,b'));
	}

	public function action_get(){
		//$this->request->clean_xss()->param();
		print_r($this->request->param());
	}

	public function action_httprpc(){
		$objRpc = new Rpc_Http('baidu_news', array(array('host' => '220.181.112.138', 'port' => 80)));
 		$strContent = $objRpc->call(
  			array(
  				'action' => '/n?cmd=1&class=internews&pn=1&from=tab',
  			)
		);		
		$this->response->headers('content-type','text/html;charset=gbk')->body($strContent);
	}

	public function action_rpccall(){
		$t = Rpc::call('ku6pass', '/nonibw-session-check.htm', array());
		$this->response->headers('content-type','text/html;charset=gbk')->body($t);
	}

	public function action_rerender(){
		$this->response->body(__Template__);
		//echo $this->template->render();
	}
	
	public function action_vcode(){
		//echo VCode::code(true);
		//echo 'done';
		//$response = VCode::response();
		//$response->send();
		//$this->request->send_response($response);
		VCode::response($this->response);
	}

	public function action_profiling(){
		
		Profiler::start('Kohana', __FUNCTION__);
		Profiler::stop('Kohana', __FUNCTION__);

		for($i = 0; $i < 10; $i++){
			JKit::profile('Test', 'my_profile');
		}
		JKit::profile('Test', 'my_profile');
		
		$this->response->body(View::factory('profiler/stats'));
	}
} // End Welcome
