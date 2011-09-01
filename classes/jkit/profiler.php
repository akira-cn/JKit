<?php defined('SYSPATH') or die('No direct script access.');
/**
 * 提供简单的性能监控和分析  
 * 要查看结果，可以通过加载 `profiler/stats` 模板查看
 *
 *     echo View::factory('profiler/stats');
 *
 * [!!] 已集成到debug.tpl，通过 [Request::debug] 以及开发模式下的 `$rdtest=1` 可以直接查看结果
 *
 * @package    JKit
 * @category   Helpers
 * @author     WED Team
 * @copyright  (c) 2009-2011 WED Team
 * @license    http://kohanaframework.org/license
 */
class JKit_Profiler extends Kohana_Profiler{
	/**
	 * 停止所有运行中的 `_marks`
	 *
	 * [!!] Request 在 forward、redirect 的时候停止之前的所有 Profiler
	 *
	 * @return void
	 */
	public static function stop_all(){
		foreach(Profiler::$_marks as $token=>$mark){
			// Stop the benchmark
			Profiler::stop($token);				
		}
	}
	
	/**
	 * 停止某组运行中的 `_marks`
	 * 
	 * [!!] Request 在 模板渲染到浏览器之前停止 Request 组的 Profiler，所以在 [View::render] 中调用 Profiler::stop_by_group('Request');
	 *
	 * @param string 组名
	 * @return void
	 */
	public static function stop_by_group($group){
		$groups = Profiler::groups(false);

		foreach($groups[strtolower($group)] as $name=>$tokens){
			foreach($tokens as $token){
				Profiler::stop($token);
			}
		}
	}
	
	/**
	 * 判断一个Profiler实例是否停止
	 *
	 * @param  string Profiler实例的id
	 * @return boolean
	 */
	public static function is_stopped($token){
		$mark = Profiler::$_marks[$token];

		return $mark['stop_time'] !== FALSE;
	}

	/**
	 * 返回所有的 Profiler 实例组
	 *
	 * [!!] 默认只返回已停止的 Profiler 实例
	 *
	 * @param  boolean 是只否返回已停止的
	 * @return array
	 */
	public static function groups($stopped_only = true){
		$groups = array();

		foreach (Profiler::$_marks as $token => $mark)
		{
			if(!$stopped_only || self::is_stopped($token)){
				// Sort the tokens by the group and name
				$groups[$mark['group']][$mark['name']][] = $token;
			}
		}

		return $groups;
	}

	/**
	 * Stops a benchmark.
	 *
	 *     Profiler::stop($token);
	 *
	 * @param   string  token
	 * @return  void
	 */
	public static function stop($token, $name = null)
	{
		if($name){
			$groups = Profiler::groups(false);

			if($group = $groups[strtolower($token)]){
				$tokens = $group[$name];
				return self::stop($tokens[count($tokens) - 1]);
			}
		}
		// Stop the benchmark
		if(Profiler::$_marks[$token]['stop_time'] === FALSE){
			Profiler::$_marks[$token]['stop_time']   = microtime(TRUE);
			Profiler::$_marks[$token]['stop_memory'] = memory_get_usage();
			
			list($time, $memory) = Profiler::total($token);

			Log::instance()->debug('Profiler:', Profiler::$_marks[$token] + array('time'=>$time, 'memory'=>$memory));
		}
	}
}