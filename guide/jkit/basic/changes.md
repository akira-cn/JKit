# 和3.1版本不同之处 {#changes}

使用过前一个版本的同学要注意这个

## Kohana的重要变更 {#important-changes-of-kohana}

1. 删掉了 `Kohana::config` 方法

 __将不再能够通过 `Kohana::config('foo.bar');读取配置文件`__
 
 [!!]解决办法： 使用 `Kohana::$config->load('foo.bar');` 或者 `JKit::config('foo.bar');`

1. 修改了Route方式

 __Route中指定的参数不再出现在action的参数列表中__

 [!!]解决办法：通过 `$this->request->param($key)` 读取指定的参数

        Route::set('foo_bar', 'foo(/<bar>)')
        ->defaults(array(
                'controller' => 'foo_bar',
                'action'     => 'index',
        ));     

        
        //in Controller_Foo
        function action_index(){
                echo $this->request->param('bar'); //3.2 way 
        }
        
        /*function action_index($bar){
                echo $bar; //3.1x way abandon
        }*/

## JKit与前版本(enhance + Ssi)的重要变更 {#important-changes-of-jkit}

1. 不再有 Ssi_ 系列的类， 所有的 Controller、 View 都直接 extends Controller、View

1. auto_render 不见了，因为这个烦人的属性其实也没啥用

 [!!] 现在依然是可以auto_render的，规则非常智能  
 几乎任何时候都能够省略 `$this->response->body(__Template__);` 除非你根本没有用到过`$this->template`
 或者你自己直接或间接调用过 `$this->template->render();` （间接调用包括把 $this->template 当作字符串来用）

1. 取消了 `Response::_break` 不需要通过 _break 来中断当前 Request 流程

 [!!] 之前用异常控制流程的机制比较山寨，现在去掉了。

1. Response::json和Response::jsonp、包括Response中的其他任何操作都不会中断当前Request的执行

 __要实现以前的json、jsonp的暴力中断，至少有两种做法，第一种做法：__
 
        $this->response->json(array('foo'=>'bar'));
        $this->request->send_response(); 
        //利用 Request::send_response() 结束当前Request执行

 __第二种做法：__

        $this->json(array('foo'=>'bar')); //直接用Controller::json方法 ^_^

1. `Request::param` 取代 `Request::queryOrPost`

        function action_foo(){
                $bar = $this->param('bar', 'bar'); //3.2 way
                //$bar = $this->queryOrPost('bar', 'bar'); //3.1x way abandon
        }

1. `Request::forward` 取代了 `Controller::forward`

 [!!] 二者的参数也有不同

        $this->request->forward('foo/bar', array('foo' => 'bar'), 301); 
        //use 301 replace with 302 in this case

        //$this->forward('foo', 'bar', array('foo'=>'bar')); // 3.1x way abandon

1. 取消那些乱七八糟的$page对象、$module对象

 [!!] 有了 Smarty 和 LAFE， 一切都很和谐~

1. 集成了LAFE

 [!!] [LAFE](https://github.com/akira-cn/lafe) ：用过的都说好~

1. Validation的变更

 [!!] 用Kohana自己的Validation，并对QWrap前端校验做了适配。  
 取消了`Controller::validErr`，增加了`Controller::valid`

1. 增加对 Logic 和 Data 封装

 [!!] 一个重要方法是 `Logic::parseResult`，取代原来的 `Controller::_parseLogic`

## 高级变更 {#advanced-changes}

1. `Request::forward` 默认返回HTTP_302，而不再像前一版本的 `Controller::forword` 是返回HTTP_200

 [!!] 传说中返回30x状态对搜索引擎更友好

1. 通过Response创建的Request对象里面有个_request属性关联着Request

 [!!] 这个在Response用到当前Request::param的时候有用

1. `Response::body($content)` 将不对 $content内容进行强制(string)

 __因为没有必要在这里做转换，不做转换还可以把模板的解析给延迟到echo的时候__

 [!!]如果有特殊需要解析之后对结果进行别的操作，那么可以手工调用`View::render`

1. `$this->response->body(__Template__)` 可以将当前模板给 `Response::body`

 [!!]Cool! 因为这样的话 `$this->response->body` 的形式统一起来