<?php
/**
 * 扩展后的系统日志类，可以通过 [JKit::$log] 或者 `Log::instance()` 获取实例
 *
 *     Kohana::$log->error('Your error info') //在日志中简单输出错误信息
 * 
 *     //输出调试信息的同时，把GET参数也输出在日志中
 *     Kohana::$log->debug('Your debug info', Request::current()->query(), __FILE__, __LINE__);
 * 
 *     //输出调试信息的同时，把GET参数也输出在日志中，并且指定打印这条日志的文件名和行号（这样系统就可以跳过自动检测，提高速度）
 *     Kohana::$log->debug('Your debug info', Request::current()->query(), __FILE__, __LINE__);
 * 
 * [!!] Log必须在Kohana核心类加载前完成注册，具体可参考 `bootstrap.php.sample` 下的配置方法
 *
 * @package    JKit
 * @category   Logging
 * @author     wulijun
 * @copyright  (c) 2011 WED Team
 * @license    http://kohanaframework.org/license
 */
class JKit_Log extends Kohana_Log {
	/**
	 * 提供调试Level的描述字符串
	 *
	 * @var array
	 */
	public static $arrLevelName = array(
		self::EMERGENCY => 'EMERG',
		self::ALERT => 'ALERT',
		self::CRITICAL => 'CRIT',
		self::ERROR => 'ERR',
		self::WARNING => 'WARNING',
		self::NOTICE => 'NOTICE',
		self::INFO => 'INFO',
		self::DEBUG => 'DEBUG',						
	);
	
	/**
	 * @var int
	 */
	protected $_intLogId = 0;
	
	/**
	 * 记录Emergency级别日志
	 *
	 * @param string $strLog 日志信息
	 * @param array $arrParam 附加参数，会输出在日志的$strLog字段后面
	 * @param string $strFile 打印该日志信息的文件名，为null的话会自动检测
	 * @param int $intLine 打印该日志信息的行号
	 * @param int $intTraceLevel 自动检测文件名和行号时使用，即debug_backtrace的索引
	 */		
	public function emerg($strLog, $arrParam = null, $strFile = null, $intLine = 0, $intTraceLevel = 0) {
		return $this->_log(self::EMERGENCY, $strLog, $arrParam, $strFile, $intLine, $intTraceLevel);
	}
	/**
	 * 记录Alert级别日志
	 *
	 * @param string $strLog 日志信息
	 * @param array $arrParam 附加参数，会输出在日志的$strLog字段后面
	 * @param string $strFile 打印该日志信息的文件名，为null的话会自动检测
	 * @param int $intLine 打印该日志信息的行号
	 * @param int $intTraceLevel 自动检测文件名和行号时使用，即debug_backtrace的索引
	 */		
	public function alert($strLog, $arrParam = null, $strFile = null, $intLine = 0, $intTraceLevel = 0) {
		return $this->_log(self::ALERT, $strLog, $arrParam, $strFile, $intLine, $intTraceLevel);
	}	

	/**
	 * 记录Critical级别日志
	 *
	 * @param string $strLog 日志信息
	 * @param array $arrParam 附加参数，会输出在日志的$strLog字段后面
	 * @param string $strFile 打印该日志信息的文件名，为null的话会自动检测
	 * @param int $intLine 打印该日志信息的行号
	 * @param int $intTraceLevel 自动检测文件名和行号时使用，即debug_backtrace的索引
	 */
	public function crit($strLog, $arrParam = null, $strFile = null, $intLine = 0, $intTraceLevel = 0) {
		return $this->_log(self::CRITICAL, $strLog, $arrParam, $strFile, $intLine, $intTraceLevel);
	}
	/**
	 * 记录Error级别日志
	 * 
	 * @param string $strLog 日志信息
	 * @param array $arrParam 附加参数，会输出在日志的$strLog字段后面
	 * @param string $strFile 打印该日志信息的文件名，为null的话会自动检测
	 * @param int $intLine 打印该日志信息的行号
	 * @param int $intTraceLevel 自动检测文件名和行号时使用，即debug_backtrace的索引
	 */		
	public function error($strLog, $arrParam = null, $strFile = null, $intLine = 0, $intTraceLevel = 0) {
		return $this->_log(self::ERROR, $strLog, $arrParam, $strFile, $intLine, $intTraceLevel);
	}

	/**
	 * 记录Warn级别日志
	 * 
	 * @param string $strLog 日志信息
	 * @param array $arrParam 附加参数，会输出在日志的$strLog字段后面
	 * @param string $strFile 打印该日志信息的文件名，为null的话会自动检测
	 * @param int $intLine 打印该日志信息的行号
	 * @param int $intTraceLevel 自动检测文件名和行号时使用，即debug_backtrace的索引
	 */		
	public function warn($strLog, $arrParam = null, $strFile = null, $intLine = 0, $intTraceLevel = 0) {
		return $this->_log(self::WARNING, $strLog, $arrParam, $strFile, $intLine, $intTraceLevel);
	}
	
	/**
	 * 记录Notice级别日志
	 * 
	 * @param string $strLog 日志信息
	 * @param array $arrParam 附加参数，会输出在日志的$strLog字段后面
	 * @param string $strFile 打印该日志信息的文件名，为null的话会自动检测
	 * @param int $intLine 打印该日志信息的行号
	 * @param int $intTraceLevel 自动检测文件名和行号时使用，即debug_backtrace的索引
	 */		
	public function notice($strLog, $arrParam = null, $strFile = null, $intLine = 0, $intTraceLevel = 0) {
		return $this->_log(self::NOTICE, $strLog, $arrParam, $strFile, $intLine, $intTraceLevel);
	}

	/**
	 * 记录Info级别日志
	 * 
	 * @param string $strLog 日志信息
	 * @param array $arrParam 附加参数，会输出在日志的$strLog字段后面
	 * @param string $strFile 打印该日志信息的文件名，为null的话会自动检测
	 * @param int $intLine 打印该日志信息的行号
	 * @param int $intTraceLevel 自动检测文件名和行号时使用，即debug_backtrace的索引
	 */		
	public function info($strLog, $arrParam = null, $strFile = null, $intLine = 0, $intTraceLevel = 0) {
		return $this->_log(self::INFO, $strLog, $arrParam, $strFile, $intLine, $intTraceLevel);
	}
	
	/**
	 * 记录Debug级别日志
	 * 
	 * @param string $strLog 日志信息
	 * @param array $arrParam 附加参数，会输出在日志的$strLog字段后面
	 * @param string $strFile 打印该日志信息的文件名，为null的话会自动检测
	 * @param int $intLine 打印该日志信息的行号
	 * @param int $intTraceLevel 自动检测文件名和行号时使用，即debug_backtrace的索引
	 */	
	public function debug($strLog, $arrParam = null, $strFile = null, $intLine = 0, $intTraceLevel = 0) {
		return $this->_log(self::DEBUG, $strLog, $arrParam, $strFile, $intLine, $intTraceLevel);
	}	
	
	public function getLogId() {
		if ($this->_intLogId === 0) {
			if (defined('SSI_REQUEST_ID')) {
				$this->_intLogId = SSI_REQUEST_ID;
			} else {
				$this->_intLogId = intval(gettimeofday(true) * 100000) & 0x7FFFFFFF;
			}
		}
		
		return $this->_intLogId;
	}

	/**
	 * Adds a message to the log. Replacement values must be passed in to be
	 * replaced using [strtr](http://php.net/strtr).
	 *
	 *     $log->add(Log::ERROR, 'Could not locate user: :user', array(
	 *         ':user' => $username,
	 *     ));
	 *
	 * @param   string  level of message
	 * @param   string  message body
	 * @param   array   values to replace in the message
	 * @return  Log
	 */
	public function add($level, $message, array $values = NULL) {
		if ($values) {
			// Insert the values into the message
			$message = strtr($message, $values);
		}
		return $this->_log($level, $message);		
	}

	/**
	 * 记录日志
	 * 
	 * @param int $intLevel 日志级别，请使用Log::ERROR这样的常量
	 * @param string $strLog 日志信息
	 * @param array $arrParam 附加参数，会输出在日志的$strLog字段后面
	 * @param string $strFile 打印该日志信息的文件名，为null的话会自动检测
	 * @param int $intLine 打印该日志信息的行号
	 * @param int $intTraceLevel 自动检测文件名和行号时使用，即debug_backtrace的索引
	 */
	protected function _log($intLevel, $strLog, $arrParam = null, $strFile = null, $intLine = 0, $intTraceLevel = 0) {	
		if ($strFile !== null) {
			$strFile = basename($strFile);
		} else {						
			$arrRet = $this->_traceFileAndLine($intTraceLevel);
			if (isset($arrRet['file'])) {
				$strFile = basename($arrRet['file']);
			}
			if (isset($arrRet['line'])) {
				$intLine = $arrRet['line'];
			}
		}
		
		//这里不用 Date::formatted_time 是因为Log会在很多类加载之前调用，而且Kohana::log_file中也可能hook到Data（通过Profiler)
		//如果依赖 Date，那么在 Kohana::find_file -> Kohana::auto_load -> Kohana::find_file 会出现循环，结果导致Date类不能被加载
		$time = new DateTime('now', new DateTimeZone(
			Log::$timezone ? Log::$timezone : date_default_timezone_get()
		));

		// Create a new message and timestamp it
		$this->_messages[] = array(
			'time'  => $time->format(Log::$timestamp),
			'logid' => $this->_intLogId !== 0 ? $this->_intLogId : $this->getLogId(),
			'level' => $intLevel,
			'pos' => "{$strFile}:{$intLine}",
			'body'  => $strLog,
			'param' => $arrParam,
		);

		if (Log::$write_on_add) {
			// Write logs as they are added
			$this->write();
		}

		return $this;						
	}
	
	/**
	 * 利用debug跟踪日志所在的文件和行号
	 *
	 * @param int debug_backtrace的索引
	 */
	protected function _traceFileAndLine($intLevel = 0) {
		$arrTrace = debug_backtrace();
		$intDepth = 2 + $intLevel;
        $intTraceDepth = count($arrTrace);
        if ($intDepth > $intTraceDepth) {
            $intDepth = $intTraceDepth;
        }
        $arrRet = $arrTrace[$intDepth];
        
        return $arrRet;
	}
}