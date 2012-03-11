<?php
/**
 * 将日志写入文件，其中warning级别（含）以下的写入一个文件，当日志切割类型为`Log_File::SPLIT_DAY`（即按天切割）时，
 * 该文件为：`{$strDir}/{$strFileName}.wf.YYYYmmdd`，notice级别及以上的日志作为另外一个文件：`{$strDir}/{$strFileName}.YYYYmmdd`。
 * 当切割类型为按小时切割时，则文件为：`{$strDir}/YYYYmmdd/{$strFileName}.wf.YYYYmmddHH`，另外一个文件类似。
 * 
 * @package    JKit
 * @category   Logging
 * @author     wulijun
 * @copyright  (c) 2011 WED Team
 * @license    http://kohanaframework.org/license
 */
class JKit_Log_File extends Kohana_Log_File {
	/**
	 * 按小时切割
	 */
	const SPLIT_HOUR = 1;
	/**
	 * 按天切割
	 */
	const SPLIT_DAY = 2;
	
	/**
	 * 普通日志文件名前缀
	 *
	 * @var string
	 */
	protected $_strNormalFile = '';

	/**
	 * Warning以上级别日志文件名前缀
	 *
	 * @var string
	 */	
	protected $_strWarningFile = '';
	
	/**
	 * 每段日志附加信息之间的分割符
	 *
	 * @var string
	 */
	protected $_strSep = ' ';
	
	/**
	 * 每行日志的分割符
	 *
	 * @var string
	 */
	protected $_strSepOfLine = "\n";
	
	/**
	 * Log_File 工厂，调用 [Log_File::__construct] 创建 Log_File 对象
	 *
	 * @param string $strDir 日志存放目录
	 * @param string $strFileName 日志文件名前缀
	 * @param int $intSplitType 日志切割类型，支持按天或者按小时切割
	 */
	public static function factory($strDir, $strFileName = 'JKit_Log', $intSplitType = self::SPLIT_HOUR){
		return new Log_File($strDir, $strFileName, $intSplitType);
	}

	/**
	 * 构造函数
	 *
	 * @param string $strDir 日志存放目录
	 * @param string $strFileName 日志文件名前缀
	 * @param int $intSplitType 日志切割类型，支持按天或者按小时切割
	 */
	public function __construct($strDir, $strFileName = 'JKit_Log', $intSplitType = self::SPLIT_HOUR) {
		//parent::__construct($strDir); //把父类的赋值操作直接在_setFilePath实现了
		$this->_setFilePath($strDir, $strFileName, $intSplitType);
	}
	
	/**
	 * 设置参数
	 *
	 *     $arrConf = array('sep' => '&nbsp;', 'sepOfLine' => '<br/>');
	 *     Log::instance()->attach(Log_File::factory('/logs/')->setOptions($arrConf));
	 *
	 * [!!] 当前版本支持 `sep` 和 `sepOfLine` 两个参数
	 *
	 * @param  array	参数数组
	 * @return Log_File 当前对象
	 */
	public function setOptions($arrConfig) {
		if (isset($arrConfig['sep'])) {
			$this->_strSep = $arrConfig['sep'];
		}
		if (isset($arrConfig['sepOfLine'])) {
			$this->_strSepOfLine = $arrConfig['sepOfLine'];
		}
		
		return $this;
	}
	
	/**
	 * Writes each of the messages into the log file. 
	 *
	 *     $writer->write($messages);
	 *
	 * @param   array   messages
	 * @return  void
	 */
	public function write(array $messages) {
		$arrLevelName = JKit_Log::$arrLevelName;
		$strWarningLog = null;
		$strNormalLog = null;
		$i = 0;
		foreach ($messages as $message) {
		    if (is_array($message['param'])) {
		        $strParam = http_build_query($message['param']);
		    } elseif (is_object($message['param'])) {
		        $strParam = http_build_query(get_object_vars($message['param']));
		    } elseif (! empty($message['param'])) {
		        $strParam = http_build_query(array('myparam' => (string) $message['param']));
		    } else {
		        $strParam = null;
		    }
			$strLogType = isset($arrLevelName[$message['level']]) ? $arrLevelName[$message['level']] : 'NOTICE';
			$arrTmp = array($strLogType, $message['logid'], $message['time'], $message['pos'],
				$message['body'],
			);
			if ($strParam !== null) {
				$arrTmp[] = "param[{$strParam}]";
			}
			/**
			 * 日志格式：日志类型{strSep}请求ID{strSep}日志打印时间{strSep}打印该日志的文件:行数{strSep}日志内容{strSep}param[key/value形式的附加参数]
			 * 如：NOTICE 311609541 2011-06-28 19:11:11 welcome.php:5 this is notice param[p1=2&p2=a%25%23c]
			 */
			$strLog = implode($this->_strSep, $arrTmp);
			if ($message['level'] <= Log::WARNING) {
				$strWarningLog .= $strLog . $this->_strSepOfLine;
			} else {
				$strNormalLog  .= $strLog . $this->_strSepOfLine;
			}
			$i++;
			if ($i >= 50) {
				if ($strNormalLog !== null) {
					file_put_contents($this->_strNormalFile, $strNormalLog, FILE_APPEND);
				}
				if ($strWarningLog !== null) {
					file_put_contents($this->_strWarningFile, $strWarningLog, FILE_APPEND);
				}
				$strNormalLog = null;
				$strWarningLog = null;
				$i = 0;
			}
		}
		
		if ($strNormalLog !== null) {
			file_put_contents($this->_strNormalFile, $strNormalLog, FILE_APPEND);
		}
		if ($strWarningLog !== null) {
			file_put_contents($this->_strWarningFile, $strWarningLog, FILE_APPEND);
		}				
	}
	
	/**
	 * 根据log目录、文件名前缀、切割类型计算文件路径
	 *
	 * @param string log目录
	 * @param string 文件名前缀
	 * @param int	 切割类型
	 * @return void
	 */
	protected function _setFilePath($strDir, $strFileName, $intSplitType) {
		if ($intSplitType == self::SPLIT_HOUR) {
			$strDir .= '/' . date('Ymd');			
			$strSuffix = date('YmdH');
		} else {
			$strSuffix = date('Ymd');
		}
		if (! is_dir($strDir)) {
			@mkdir($strDir, 0755, true);
		}
		
		$this->_directory = $strDir;
		$this->_strNormalFile = "{$this->_directory}/{$strFileName}.{$strSuffix}";
		$this->_strWarningFile = "{$this->_directory}/{$strFileName}.wf.{$strSuffix}";
	}
}