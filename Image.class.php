<?php
/**
 * @version      : $id:2014-07-10
 * @author       : Hongliang shi <shl3807@gmail.com>
 */
class Image {
    /*
    返回一个字符的数组
     */
	public static function chararray($str,$charset="utf-8" ,$isen = true){
		if($isen){
			$re['utf-8']  = "/[a-zA-Z]+|[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
		    $re['gb2312'] = "/[a-zA-Z]+|[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
		    $re['gbk']    = "/[a-zA-Z]+|[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
		    $re['big5']   = "/[a-zA-Z]+|[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
		    preg_match_all($re[$charset], $str, $match);
		}else{
			$re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
		    $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
		    $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
		    $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
		    preg_match_all($re[$charset], $str, $match);
		}
	    
	    return $match;
	}

	/* 返回一个字符串在图片中所占的宽度 */
	public static function charwidth($fontsize,$fontangle,$ttfpath,$char){
	    $box = @imagettfbbox($fontsize,$fontangle,$ttfpath,$char);
	    $width = max($box[2], $box[4]) - min($box[0], $box[6]);
	    if(preg_match("/[a-zA-Z0-9]/i",$char)){
	    	$width += floor($fontsize/1.2) ;
	    }
	    //对中文标点特殊处理
		if(in_array($char,array("；","：","，","。","！","、","？","（","）","“","”"))){
			$width += floor($fontsize/1.2) ;//阀值可以自己修改
		}
	    return $width;
	}

	/* 这几个变量分别是 字体大小, 角度, 字体名称, 字符串, 预设宽度, 编码 */
	public static function autowrap($fontsize,$fontangle,$ttfpath,$str,$width,$charset='utf-8',$isen=true){
	    $_string = "";
	    $_width = 0;
	    $temp = self::chararray($str ,$charset="utf-8" ,$isen);
	    foreach ($temp[0] as $k=>$v){
	        $w = 0;
	        if($v == "\n" || $v== ""){
	            if($v == "\n"){
	                $_string .= "\n";
	                $_width = 0;
	            }
	            continue;
	        }
	        $w = self::charwidth($fontsize,$fontangle,$ttfpath,$v);
	        //防止因为单个字符串长度大于额定宽度
	        if($w > $width){
	        	$_string .= self::autowrap($fontsize,$fontangle,$ttfpath,$v,$width,$charset='utf-8',false);
	        	continue;
	        }
	        $_width += $w;
	        if (($_width+$fontsize > $width) && ($v !== "")){
	            $_string .= "\n";
	            $_width = 0;
	        }
	        $_string .= $v;
	    }
	    return $_string;
	}

	//过滤标签
	public static function cleartag($text){
	   	$text = strip_tags($text);
	   	$text = str_replace("	", "    ", $text);
	   	$text = str_replace("&nbsp;", " ", $text);
	   	$textArr = explode("\n", $text);
	   	foreach ($textArr as $key => $value) {
	   		$tmp_value = trim($value);
	   		if($tmp_value == "\n" || $tmp_value == "\r" || empty($tmp_value) || $tmp_value == " "){
	   			continue;
	   		}
	   		$strArr[] = $value;
	   	}
	   	return implode("\n", $strArr);
	}

	/*根据内容生成文字图片
	* $str : 标题，内容
	* $font_size_big : 标题字体 ，默认为16
	* $font_size :文本大小 ，默认为14
	* $width : 图片文字宽度 ，默认为520
	* $real_width : 图片真实宽度 ，默认为6000
	* author by ：hongliang（shl3807@gamil.com） by:2014-07-10
	*/
	public static function create($str, $font_size = 14 , $font_size_big = 16 ,$font_type = 'font/simhei.ttf' ,$width = 520 ,$real_width = 640){

		//职位标题高度
		$title = self::autowrap($font_size_big,0,$font_type,self::cleartag($str["title"]),$width);
		$title_bounds = imageftbbox($font_size_big, 0, $font_type, $title);
	    //计算高度
	    $title_height = abs($title_bounds[7] - $title_bounds[1])+10;
	   
		$text = self::autowrap($font_size,0,$font_type,self::cleartag($str["content"]),$width);
	    $bounds = imageftbbox($font_size, 0, $font_type, $text);
	    //计算高度
	    $text_height = abs($bounds[7] - $bounds[1])+10;

	    //总高度= 文字高度+标题高度
	    $total_height = $title_height+$text_height+100;
	    $im = imagecreate($real_width, $total_height);

	    #文字颜色
	    $white = imagecolorallocate($im, 255, 255, 255);
	    $text_color = imagecolorallocate ( $im, 0x2D, 0x2D, 0x2D );
	    $grey = imagecolorallocate($im, 128, 128, 128);
	    $blue = imagecolorallocate($im, 6, 103, 174);
	   	//顶部空白高度
	   	$header_width = 40;
	    imagettftext ( $im, $font_size, 0, $offset_y, $header_width,$text_color , $font_type, $title );
	    
	    #添加文字描述
	    $header_width+=30;
	    imagettftext ( $im, $font_size, 0, $offset_y, $header_width,$text_color , $font_type, $text );
	  
	    header("Content-Type: image/png");
	    //ob_start();
		imagepng($im);
		/*$imgdata = ob_get_contents();
		ob_end_clean();
		*/
	}	
}
?>
