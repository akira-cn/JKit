# 基本规则与约定 {#rules}

 Kohana依靠规则来管理代码，减少很多配置文件存在的必要。  
 JKit在此基础上更是完善了基本规则和约定，你不需要对付各种配置项，只需要根据习惯，知道并且熟悉“__本该就是如此__”就行了。

[!!] 熟知基本规则能写出较为简洁的代码，极大程度提高效率

## Kohana的基本规则

 1. `find_file` 的优先级为 `APPPATH > MODPATH/somemod > SYSPATH`

        //当 APPPATH/classes/foo/bar.php, 
        //MODPATH/jkit/classes/foo/bar.php, 
        //SYSPATH/classes/foo/bar.php 同时存在时
        
        JKit::find_file('classes', 'foo/bar'); 
        
        //得到的一定是 APPPATH/classes/foo/bar.php

 1. 类名符合规则的类不需要手工require，能自动加载，规则为：

        //自动加载 classes/foo/bar.php
        //其中文件优先级按照上一条规则
        $myObj = new Foo_Bar();

 1. 配置文件、模板的查找优先级也是和第一条规则一样

        //模板的查找起始目录为 views
        $template = View::factory('foo/bar'); //找 views/foo/bar.php

        //config的查找起始目录为 config
        $confArr = JKit::config('foo.bar'); //找 config/foo.php 中的 bar组

 1. Action Controller 和 URL 路径的对应关系是根据路由来的，默认路由为

        Route::set('default', '(<controller>(/<action>(/<id>)))')
                ->defaults(array(
                        'controller' => 'welcome',
                        'action'     => 'index',
                ));

 [!!] 因此 Controller_Foo_Bar::action_test 对应的 URL 为 foo_bar/test

 1. JKit 中 Action Controller 和 View 也存在默认的路径对应关系，规则为：

 [!!] Controller_Foo_Bar::action_test 对应的 View 为 `views/foo/bar/test.php`， Layout 同理，为 `classes/layout/foo/bar/test.php`

 1. JKit 的 Action 中可以不创建模板，直接用 $this->template 进行操作，第一次调用 $this->template 的时候系统会自动创建模板

        //$this->create_template(); //这句可以省略
        $this->template->set('foo', 'bar');

 [!!] 一种不能省略的情况是，希望不用默认路径加载模板

        $this->create_template('my/foo/bar'); //不用默认路径，不走寻常路，所以多写点代码
        $this->template->set('foo', 'bar');

 1. JKit 的 Action 中还可以省略对模板调用渲染方法，如果使用过模板，并且没有渲染过，模板将自动被框架渲染

        $this->template->set('foo', 'bar');
        //$this->response->body($this->template); //这句可以省略，让系统自动帮你做

 [!!] 有几种情况不会自动渲染

      a. 如果一个 Action 中根本没使用过 $this->template，这时需要自己调用，不然系统不能判断你是否需要使用模板

              $this->response->body($this->template); 
              //整个Action只有这一句用到了 `$this->template` 的时候不能省

      b. 如果你不用 `$this->template` 以及 `$this->create_template` 用自己的变量 new 一个 View

              $myTemplate = View::factory('foo/bar');
              //自己创建的对象，这个框架管不了
              $this->response->body($myTemplate);
              //因此上面这句不能省

      c. 你自己在 Action 中已经渲染过模板或者不小心渲染过模板。包括以下情况：

              echo $this->template;  //直接echo，导致渲染

              echo $this->response->body($this->template); //也是echo导致的渲染

              $this->response->body($this->template); 
              $this->response->send_response();                
              //这个也有渲染，不过都send_response了，后面的代码不会被执行

              $this->template->render(); //这个理所当然，直接调用了render

              $this->template.'haha'; 
              //这个估计匪夷所思一点，因为触发了 View::__toString 导致了渲染


 1. 魔术 action
	
 [!!] 如果你在 Action 里面没有对模板做什么特别的，并且模板在默认路径下，你可以省略这个 Action，直接写模板，并且 request->param() 会自动传入成为模板变量
	
        /**
         * 重载魔术方法 __call
         * 支持加载一个不存在于action controller的默认路径模板
         * 缺省动作是将 request参数传入模板中
         * 这样就可以不写action只写模板
         */	
        public function __call($name, $args) {
                $this->template->set_global($this->request->param());
        }

 1. 是否觉得 $this->response->body($this->template) 太难看？ 试试下面的写法：

        $this->response->body(__Template__);


## 有用的约定 {#useful-rules}

 1. Model 中，业务逻辑相关的类放在 `classes/model/logic` 文件夹下，继承 Logic， 数据实体相关的类放在 `classes/model/data` 文件夹下，继承 Data

 1. Logic 业务层返回 true， 表示成功，Action 中 `$this->err(true)` 返回 false，表示没有错误， `$this->ok($data)` 不论true 还是 false 都发送 `{'err':'ok'}`

 1. Logic 业务层返回 false，表示失败，Action 中 `$this->err(false)` 发送 `{'err':'sys.default'}` 

 1. Logic 业务层返回其他结果时， `$this->err($data)` 发送 `{'err':'sys.default', 'data':$data}`， $this->ok($data) 发送 `{'err':'ok', 'data':$data}`

 1. Logic 业务层自己返回包含 err 属性的数组，表示是业务逻辑标准化的结果，Action 中 `$this->err` 会尊重返回的结果

 1. Logic 业务层返回的结果可以通过 [Logic::parseResult] 方法进行标准化

 1. 详细规则参考 [Logic::parseResult] [Controller::err] [Controller::ok]

