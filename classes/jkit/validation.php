<?php defined('SYSPATH') or die('No direct script access.');

class JKit_Validation extends Kohana_Validation{
	
	public function __construct(array $array, array $rules=null)
	{
		parent::__construct($array);

		if($rules){
			$all_rules = ValidAdaptor::factory()->parse_rules($rules);
			
			foreach($all_rules as $field => $rules){
				$this->rules($field, $rules);
			}
		}
	}

	/**
	 * Returns the error messages. If no file is specified, the error message
	 * will be the name of the rule that failed. When a file is specified, the
	 * message will be loaded from "field/rule", or if no rule-specific message
	 * exists, "field/default" will be used. If neither is set, the returned
	 * message will be "file/field/rule".
	 *
	 * By default all messages are translated using the default language.
	 * A string can be used as the second parameter to specified the language
	 * that the message was written in.
	 *
	 *     // Get errors from messages/forms/login.php
	 *     $errors = $Validation->errors('forms/login');
	 *
	 * @uses    Kohana::message
	 * @param   string  file to load error messages from
	 * @param   mixed   translate the message
	 * @return  array
	 */
	public function errors($file = 'validation', $translate = FALSE)
	{
		if ($file === NULL)
		{
			// Return the error list
			return $this->_errors;
		}

		// Create a new message list
		$messages = array();

		foreach ($this->_errors as $field => $set)
		{
			list($error, $params) = $set;

			// Get the label for this field
			$label = $this->_labels[$field];

			if ($translate)
			{
				if (is_string($translate))
				{
					// Translate the label using the specified language
					$label = __($label, NULL, $translate);
				}
				else
				{
					// Translate the label
					$label = __($label);
				}
			}

			// Start the translation values list
			$values = array(
				':field' => $label,
				':value' => Arr::get($this, $field),
			);

			if (is_array($values[':value']))
			{
				// All values must be strings
				$values[':value'] = implode(', ', Arr::flatten($values[':value']));
			}

			if ($params)
			{
				foreach ($params as $key => $value)
				{
					if (is_array($value))
					{
						// All values must be strings
						$value = implode(', ', Arr::flatten($value));
					}
					elseif (is_object($value))
					{
						// Objects cannot be used in message files
						continue;
					}

					// Check if a label for this parameter exists
					if (isset($this->_labels[$value]))
					{
						// Use the label as the value, eg: related field name for "matches"
						$value = $this->_labels[$value];

						if ($translate)
						{
							if (is_string($translate))
							{
								// Translate the value using the specified language
								$value = __($value, NULL, $translate);
							}
							else
							{
								// Translate the value
								$value = __($value);
							}
						}
					}

					// Add each parameter as a numbered value, starting from 1
					$values[':param'.($key + 1)] = $value;
				}
			}

			if ($message = Kohana::message($file, "{$field}.{$error}"))
			{
				// Found a message for this field and error
			}
			elseif ($message = Kohana::message($file, "{$field}.default"))
			{
				// Found a default message for this field
			}
			elseif ($message = Kohana::message($file, $error))
			{
				// Found a default message for this error
			}
			elseif ($message = Kohana::message($file, 'default'))
			{
				// Found a default message for this error
			}
			else
			{
				// No message exists, display the path expected
				$message = "{$field}.{$error}";
			}

			if ($translate)
			{
				if (is_string($translate))
				{
					// Translate the message using specified language
					$message = __($message, $values, $translate);
				}
				else
				{
					// Translate the message using the default language
					$message = __($message, $values);
				}
			}
			else
			{
				// Do not translate, just replace the values
				$message = strtr($message, $values);
			}

			// Set the message for this field
			$messages[$field] = $message;
		}

		return $messages;
	}
};

/**
 * 前后端统一校验适配器
 */
class ValidAdaptor{

	/*protected static $rule_maps = array(
		'n'				=> array(), //整数
		//'n-7.2'		=> array(), //数值,有效位最多7位,小数点最多两位
		'nrange'		=> array(), //数值范围（前端校验的时候判断一组输入的两个值，后者大于前者，但服务器端不做通用的校验）
		//'nrange-7.2'	=> array(), //数值范围
		'd'				=> array(), //日期
		'daterange'		=> array(), //日期范围
		'text'			=> array(), //文本(用来验证文本的长度)
		'bytetext'		=> array(), //文本(用来验证文本的字节长度)
		'richtext'		=> array(), //文本(用来验证富文本的长度)
		'reconfirm'		=> array(), //确认输入(用来验证两个输入框的值相同)
		'time'			=> array(), //时分秒
		'minute'		=> array(), //时分
		'email'			=> array(), //邮件
		'mobilecode'	=> array(), //手机号码
		'phone'			=> array(), //电话区号+电话号码
		'phonezone'		=> array(), //电话区号
		'phonecode'		=> array(), //电话号码
		'phoneext'		=> array(), //电话分机
		'zipcode'		=> array(), //邮政编码
		'idnumber'		=> array(), //身份证号
		'bankcard'		=> array(), //银行卡号
		'cnname'		=> array(), //中文姓名
		'vcode'			=> array(), //验证码（暂定为' => array(), //数字或字母,长度为4） 
		'imgfile'		=> array(), //图片文件
		'reg'			=> array(), //正则验证---需要配有reg-pattern属性
		//'reg-/^[\w]+$/' => array(), //正则表达
		'uv'			=> array(), //自定义的datatype,需要自写onblur事件.checkAll时也会对它进行验证
		//'magic-phone||mobilecode||email' => array(), //复合datatype
		'magic'			=> array(), //复合datatype
	);*/

	protected $_errors = array();

	public static function factory(){
		return new ValidAdaptor();
	}

	public function parse_rules($rules){
		$ret = array();

		foreach($rules as $field => $rule){
			if(strpos($field, '@') === 0){ //有用的规则是根据名字来的
				$field_name = substr($field, 1);
				$sections = array();
				
				//先解析 $datatype
				if($section = $rule['datatype']){ //如果有datatype
					list($section_name, $pattern) = explode('-', $section);
					
					//解析跟在datatype后面的pattern，现在有三种格式 -m.n -/reg/ -a||b
					if($pattern){	
						$rule[$section_name.'-pattern'] = $pattern;
					}

					//解析datatype
					if($trans_rules = $this->datetype($section_name, $rule, $field_name))	
					{
						$sections  = array_merge($sections, $trans_rules); 
					}
					
					//解析reqmsg
					if($rule['reqmsg']){
						array_push($sections, array('not_empty'));
						unset($rule['reqmsg']);
					}	
				}

				if($sections){
					$ret[substr($field,1)] = $sections;
				}
			}else{
				array_push($this->_errors, "unknown field: $field");
			}
		}

		return $ret;
	}
	
	public function datetype($datatype, $rule, $field){
		unset($rule['datatype']);

		switch($datatype){
			case 'n'				: 
			case 'nrange'			:	if($pattern = $rule['n-pattern']){
											list($digit, $decimal) = explode('.',$pattern);
											$digit = intval($digit) - intval($decimal);

											$ret = array(array('decimal', array(':value',intval($decimal), $digit)));
											unset($rule['n-pattern']);
										}else{
											$ret = array(array('digit'));
										}
										$min = $rule['minvalue'];
										$max = $rule['maxvalue'];

										if(!isset($min)){
											$min = 0;
										}
										if(!isset($max)){
											$max = PHP_INT_MAX;
										}
										array_push($ret, array('range', array(':value', floatval($min), floatval($max))));
										unset($rule['minvalue']);
										unset($rule['maxvalue']);
										return $ret;
			
			case 'd'				:
			case 'daterange'		:	$ret = array(array('date'));

										$min = $rule['minvalue'];
										$max = $rule['maxvalue'];

										if($min || $max){
											array_push($ret, array('date_range', array(':value', $min, $max)));
										}
										unset($rule['minvalue']);
										unset($rule['maxvalue']);
										return $ret;
			
			case 'text'				:  	
			case 'bytetext'			:   
			case 'richtext'			:   $ret = array();									
										$min = $rule['minlength'];
										$max = $rule['maxlength'];

										if(isset($min)){
											array_push($ret, array('min_length',array(':value',$datatype))); //文本(用来验证文本的长度)
										}
										if(isset($max)){
											array_push($ret, array('max_length',array(':value',$datatype)));
										}
										unset($rule['minvalue']);
										unset($rule['maxvalue']);
										return $ret; 

			case 'reconfirm'		:	$ret = array(array('matches', array(':validation',$field,$rule['reconfirmfor'])));
										unset($rule['reconfirmfor']);
										return $ret;
			
			case 'time'				:   return array(array('regex', array(':value', '/^(([0-1]\d)|(2[0-3])):[0-5]\d:[0-5]\d$/')));
			
			case 'minute'			:	return array(array('regex', array(':value', '/^(([0-1]\d)|(2[0-3])):[0-5]\d$/')));
			
			case 'email'			:	return array(array('email'));

			case 'mobilecode'		:	return array(array('regex', array(':value', '/^(13|15|18|14)\d{9}$/')));

			case 'phone'			:	return array(array('regex', array(':value', '/^0(10|2\d|[3-9]\d\d)[1-9]\d{6,7}$/'))); //电话区号+电话号码
			case 'phonewithext'		:	return array(array('regex', array(':value', '/^0(10|2\d|[3-9]\d\d)[1-9]\d{6,7}(-\d{1,7})?$/'))); //含分机的号码
			case 'phonezone'		:	return array(array('regex', array(':value', '/^0(10|2\d|[3-9]\d\d)$/'))); //电话区号
			case 'phonecode'		:	return array(array('regex', array(':value', '/^[1-9]\d{6,7}$/'))); //电话号码
			case 'phoneext'			:	return array(array('regex', array(':value', '/^\d{1,6}$/'))); //电话分机

			case 'zipcode'			:   return array(array('regex', array(':value', '/^\d{6}$/'))); //邮政编码
										
			case 'idnumber'			:	return array(array('idnumber')); //身份证号
			
			case 'bankcard'			:	return array(array('credit_card')); //银行卡号
			
			case 'cnname'			:	return array(array('regex', array(':value', '/^[\u4e00-\u9fa5a-zA-Z.\u3002\u2022]{2,32}$/'))); //中文姓名
	
			//case 'vcode'			:	return array(array('regex', array(':value', '/^\d{6}$/'))); //验证码（暂定为' => array(), //数字或字母,长度为4） 
			
			case 'imgfile'			:	return array(array('regex', array(':value', '/^(jpg|jpeg|png|gif|tif|bmp)$/i'))); //图片文件
			
			case 'reg'				:	$ret = array(array('regex', array(':value', $rule['reg-pattern']))); //正则验证---需要配有reg-pattern属性	
										unset($rule['reg-pattern']);
										return $ret;
			
			//自定义的datatype,需要自写onblur事件.checkAll时也会对它进行验证
			//case 'uv'				:	array(array('regex', array(':value', '/^\d{6}$/'))); 
			
			//case 'magic'			:	$ret = array(array('complex', array(':value', $rule['magic-pattern']))); //复合datatype
			//							unset($rule['magic-pattern']);
			//							return $ret;

			default					:	array_push($this->_errors, "unknown datatype: $datatype");
										return false;
		}
	}
}