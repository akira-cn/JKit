<?php defined('SYSPATH') or die('No direct script access.');
/**
 * 验证码 Helper
 *
 * @package    JKit
 * @category   Controller
 * @author     akira.cn@gmail.com
 * @copyright  (c) 2011 WED Team
 * @license    http://kohanaframework.org/license
 */
class JKit_VCode{
	
	//settings
	protected static $token_name = "security_vcode"; //session存的token名

	//protected static $code_chars = "1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	protected static $code_chars = "1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ";	//验证码字符集

	protected static $code_length = 4;					//验证码长度

	protected static $image_size = array(80,23);		//图片宽高
	protected static $image_color = array(0,112,201);	//图片前景色
	protected static $image_bgcolor = array(255,255,255);	//图片背景色
	
	/**
	 * 获得随机的横向位置
	 *
	 * @param  int 字符位置
	 * @return int 字符出现位置坐标
	 */
	private static function rand_x($idx){
		return ($idx / VCode::$code_length) * VCode::$image_size[0] + rand(1, VCode::$code_length); 
	}

	/**
	 * 获得随机纵向位置
	 *
	 * @param  int 字符位置
	 * @return int 字符出现位置坐标
	 */
	private static function rand_y($idx){
		return rand(2, 2 + VCode::$image_size[1] / 5); 
	}
	
	/**
	 * 读取或生成 code
	 *
	 *     $code = VCode::code();
	 *
	 * @param  boolean	是否更新
	 * @return string	生成的code
	 */
	public static function code($new = false){
		$session = Session::instance();

		// Get the current token
		$code = $session->get(VCode::$token_name);

		if ($new === true OR ! $code)
		{
			$code = '';

			// Generate a new unique code
			for($i = 0; $i < VCode::$code_length; $i++){
				$code .= VCode::$code_chars[rand(0, strlen(VCode::$code_chars)-1)];
			}

			// Store the new code
			$session->set(VCode::$token_name, strtolower($code));
		}

		return $code;
	}
	
	/**
	 * 返回验证码图片(PNG格式)
	 *
	 *     //在action中生成验证码并返回
	 *     //$response = VCode::response(); 
	 *     //$response->send();
	 *     //或者
	 *     VCode::response($this->response);
	 *
	 * @param	Response 要返回的[Response]对象，NULL的话创建一个新的
	 * @return  Response
	 */
	public static function response($response = NULL){
		if(! $response){
			$response = Response::factory();
		}
		
		$code = self::code(true);

		$img = ImageCreate(VCode::$image_size[0], VCode::$image_size[1]);

		$color = ImageColorAllocate($img, VCode::$image_color[0], VCode::$image_color[1], VCode::$image_color[2]);
		$bgcolor = ImageColorAllocate($img, VCode::$image_bgcolor[0], VCode::$image_bgcolor[1], VCode::$image_bgcolor[2]);

		ImageFill($img, 0, 0, $bgcolor);

		$response->headers(array('content-type' => 'image/PNG'));
		
		$len = VCode::$code_length;
		
		// 添加干扰点
		for($i = 0; $i < 150; $i++){
			ImageSetPixel($img, rand()%VCode::$image_size[0], rand()%VCode::$image_size[1], $color);
		}
		
		//	添加干扰线
		for($i = 0; $i < 2; $i++){
			$startpos = (VCode::$image_size[1] / 2) * $i;

			$rand_y = rand($startpos, VCode::$image_size[1]  / 2 - 5);
			$rand_y2 = rand($startpos, $startpos + VCode::$image_size[1] / 2 - 5);

			ImageLine($img, 0, $rand_y, VCode::$image_size[0], $rand_y2, $color);
		} 
		
		//将数字随机显示在画布上,字符的水平间距和位置都按一定波动范围随机生成
		for($i=0; $i<$len;$i++){
			$posx = self::rand_x($i);
			$posy = self::rand_y($i);
			
			ImageString($img, 6, $posx, $posy, substr($code,$i,1), $color);
		}

		// Capture the view output
		ob_start();

		try
		{
			ImagePNG($img);
			ImageDestroy($img);
		}
		catch (Exception $e)
		{
			// Delete the output buffer
			ob_end_clean();

			// Re-throw the exception
			throw $e;
		}

		// Get the captured output and close the buffer
		$response->body(ob_get_clean());

		return $response;
	}

	/*
	 * 提交验证码到服务器
	 *
	 *     <input type="text" name="vcode"/>
	 *	   <!-- 自动验证
	 *     <input type="hidden" name="vcode" value="VCode::code()"/>
	 *     -->
	 *
	 * 使用[Validation]组件检查验证码
	 *
	 *     $validation->rule('vcode','not_empty');
	 *     $validation->rule('vcode','VCode::check');
	 *
	 * @param	string	要匹配的字符串
	 * @return	boolean	
	 */
	public static function check($code){
		if(strtolower($code) == self::code()){
			self::code(true);
			return true;
		}else{
			return false;
		}
	}
}