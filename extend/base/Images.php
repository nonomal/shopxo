<?php
// +----------------------------------------------------------------------
// | ShopXO 国内领先企业级B2C免费开源电商系统
// +----------------------------------------------------------------------
// | Copyright (c) 2011~2099 http://shopxo.net All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( https://opensource.org/licenses/mit-license.php )
// +----------------------------------------------------------------------
// | Author: Devil
// +----------------------------------------------------------------------
namespace base;

use app\service\ResourcesService;

/**
 * 图片上传驱动
 * @author   Devil
 * @blog     http://gong.gg/
 * @version  0.0.1
 * @datetime 2018-07-10
 */
class Images
{
	private $i_path;
	private $i_type;
	private $i_is_new_name;
	private $i_quality;
	private $i_data;

	/**
	 * 构造方法
	 * @author  Devil
	 * @blog    http://gong.gg/
	 * @version 1.0.0
	 * @date    2023-02-12
	 * @desc    description
	 * @param   [array]           $params [输入参数]
	 */
	private function __construct($params = [])
	{
		// 检测是否支持gd
		$this->IsGD();

		// 图片类型
		$this->i_type = empty($params['type']) ? $this->GetImageType() : $params['type'];

		// 图片名称是否使用新名称、则使用原图片名称
		$this->i_is_new_name = (isset($params['is_new_name']) && is_bool($params['is_new_name'])) ? $params['is_new_name'] : true;

		// jpg图片的质量值、值越大质量越好
		$this->i_quality = max(75, isset($params['quality']) ? intval($params['quality']) : 75);
	}
	
	/**
	 * 静态方法实例化本类
	 * @author  Devil
	 * @blog    http://gong.gg/
	 * @version 1.0.0
	 * @date    2023-02-12
	 * @desc    description
	 * @param   [array]           $params [输入参数]
	 * @return  [object] 				  [实例对象]
	 */
	public static function Instance($params = [])
	{
		static $object = null;
		if(!is_object($object)) $object = new self($params);
		return $object;
	}

	/**
	 * 参数设置
	 * @author  Devil
	 * @blog    http://gong.gg/
	 * @version 1.0.0
	 * @date    2023-02-12
	 * @desc    description
	 * @param   [array]           $params [输入参数]
	 */
	public function SetParameters($params = [])
	{
		if(empty($params)) return;
		$this->__construct($params);
	}

	/**
	 * 检查是否支持gd库
	 * @author  Devil
	 * @blog    http://gong.gg/
	 * @version 1.0.0
	 * @date    2023-02-12
	 * @desc    description
	 */
	private function IsGD()
	{
		if(!isset(gd_info()['GD Version']) || empty(gd_info()['GD Version']))
		{
			die('not support gd');
		}
	}

	/**
	 * 获取图片后缀类型
	 * @author  Devil
	 * @blog    http://gong.gg/
	 * @version 1.0.0
	 * @date    2023-02-12
	 * @desc    description
	 */
	private function GetImageType()
	{
		return array(
			'gif'	=> 'image/gif',
			'png'	=> 'image/png',
			'jpg'	=> 'image/jpeg',
			'bmp'	=> 'image/bmp'
		);
	}
	
	/**
	 * 基本信息初始化
	 * @author  Devil
	 * @blog    http://gong.gg/
	 * @version 1.0.0
	 * @date    2023-02-12
	 * @desc    description
	 * @param   [array]          $data [临时图片数据]
	 * @param   [string]         $path [图片保存路径]
	 */
	private function Initialization($data, $path)
	{
		if(empty($data)) return false;

		// 图片保存地址
		$this->PathMkdir($path);

		// 初始化返回数据
		$this->i_data = [];

		return true;
	}
	
	/**
	 * 路径校验，不存在则创建
	 * @author  Devil
	 * @blog    http://gong.gg/
	 * @version 1.0.0
	 * @date    2023-02-12
	 * @desc    description
	 * @param   [string]          $path [路径地址]
	 */
	public function PathMkdir($path)
	{
		$path .= (substr($path, -1) == '/') ? '' : '/';
		$this->i_path = empty($path) ? '/tmp/image' : $path;

		// 设置存储路径是否存在
		if(!is_dir($this->i_path)) @mkdir($this->i_path, 0777, true);
		if(!@opendir($this->i_path)) exit('dir not access ['.$this->i_path.']');
	}

	/**
	 * 获取随机名称
	 * @author  Devil
	 * @blog    http://gong.gg/
	 * @version 1.0.0
	 * @date    2023-02-12
	 * @desc    description
	 */
	public function GetNewFileName()
	{
		return date('YmdHis').str_shuffle(rand());
	}
	
	/**
	 * base64图片存储
	 * @author  Devil
	 * @blog    http://gong.gg/
	 * @version 1.0.0
	 * @date    2023-02-12
	 * @desc    description
	 * @param   [array]           $data [需要存储的base数据、一维数组]
	 * @param   [string]          $path [存储路径]
	 * @return  [array] 		  		[返回图片地址、一维数组]
	 */
	public function SaveBaseImages($data, $path)
	{
		if(!$this->Initialization($data, $path)) return null;

		for($i=0; $i<count($data); $i++)
		{
			if(empty($data[$i])) continue;

			$temp_img = str_replace(array("\n", '\n', ' '), '', $data[$i]);
			$img_info = @getimagesize('data://application/octet-stream;base64,'.$temp_img);
			if(empty($img_info['mime'])) continue;

			$type = $this->Is_ImageType($img_info['mime']);
			if($type == 'no') continue;

			$file_name = $this->GetNewFileName().'.'.$type;
			if(file_put_contents($this->i_path.$file_name, base64_decode($temp_img)) !== false) $this->i_data[] = $file_name;
		}
		return $this->i_data;
	}
	
	/**
	 * 二进制图片保存
	 * @author  Devil
	 * @blog    http://gong.gg/
	 * @version 1.0.0
	 * @date    2023-02-12
	 * @desc    description
	 * @param   [array]          $data [二进制图片数据、一维数组]
	 * @param   [string]         $path [存储路径]
	 * @return  [string] 	           [返回图片地址、一维数组]
	 */
	public function SaveBinaryImages($data, $path)
	{
		if(!$this->Initialization($data, $path)) return null;

		for($i=0; $i<count($data); $i++)
		{
			if(empty($data[$i])) continue;

			$img_info = getimagesizefromstring($data[$i]);
			if(empty($img_info['mime'])) continue;

			$type = $this->Is_ImageType($img_info['mime']);
			if($type == 'no') continue;

			// 安全验证
	        $ret = \base\FileUtil::FileContentSecurityCheck($data[$i], false);
	        if($ret['code'] != 0)
	        {
	            continue;
	        }

        	// 存储文件
			$file_name = $this->GetNewFileName().'.'.$type;
			if(file_put_contents($this->i_path.$file_name, $data[$i]) !== false) $this->i_data[] = $file_name;
		}
		return $this->i_data;
	}

	/**
	 * 获取临时图片文件的原图
	 * @author  Devil
	 * @blog    http://gong.gg/
	 * @version 1.0.0
	 * @date    2023-02-12
	 * @desc    description
	 * @param   [array]           $data [临时图片数据]
	 * @param   [string]          $path [存储路径]
	 * @return  [string] 	            [返回图片地址]
	 */
	public function GetOriginal($data, $path)
	{
		if(!$this->Initialization($data, $path)) return '';
		if(empty($data['tmp_name'])) return '';

		// 是否图片类型
		$type = $this->Is_ImageType($data['type']);
		if($type == 'no') return '';

		// 安全验证
        $ret = \base\FileUtil::FileContentSecurityCheck($data['tmp_name']);
        if($ret['code'] != 0)
        {
            return '';
        }

        // 存储文件
		$file_name = $this->GetNewFileName().'.'.$type;
		return move_uploaded_file($data['tmp_name'], $this->i_path.$file_name) ? $file_name : '';
	}

	/**
	 * 获取指定图片路径的压缩图
	 * @param  [string] $file  [图片地址]
	 * @param  [string] $path  [存储路径]
	 * @param  [int] 	$width [设定图片宽度]
	 * @param  [int] 	$height[指定图片高度, 不指定则高度按照比例自动计算]
	 * @return [string] 	   [返回图片地址]
	 */
	public function GetBinaryCompress($file, $path, $width = 0, $height = 0)
	{
		if(!$this->Initialization($file, $path) || is_array($file) || !file_exists($file)) return '';

		$img_info = pathinfo($file);
		if(empty($img_info['basename'])) return '';

		$type = $this->Is_ImageType($img_info['extension']);
		if($type == 'no') return '';

		$file_name = ($this->i_is_new_name) ? $this->GetNewFileName().'.'.$type : $img_info['basename'];

		if($this->ImageCompress($width, $height, $file, $type, $this->i_path.$file_name)) return $file_name;
		
		return '';
	}

	/**
	 * 获取临时图片文件的压缩图
	 * @param  [array]  $data  [临时图片数据]
	 * @param  [string] $path  [存储路径]
	 * @param  [int] 	$width [设定图片宽度]
	 * @param  [int] 	$height[指定图片高度, 不指定则高度按照比例自动计算]
	 * @return [atring|空字符串] [返回图片存储的名称]
	 */
	public function GetCompress($data, $path, $width = 0, $height = 0)
	{
		if(!$this->Initialization($data, $path)) return '';

		if(empty($data['tmp_name'])) return '';
		
		$type = $this->Is_ImageType($data['type']);
		if($type == 'no') return '';

		$file_name = $this->GetNewFileName().'.'.$type;

		if($this->ImageCompress($width, $height, $data['tmp_name'], $type, $this->i_path.$file_name)) return $file_name;
		return '';
	}

	/**
	 * 临时图像裁剪
	 * @param [array]  	$data       [图像临时数据]
	 * @param [string] 	$path       [图像存储地址]
	 * @param [int] 	$width      [指定存储宽度]
	 * @param [ing] 	$height     [指定存储高度]
	 * @param [ing] 	$src_x      [裁剪x坐标]
	 * @param [ing] 	$src_y      [裁剪y坐标]
	 * @param [ing] 	$src_width  [裁剪区域宽度]
	 * @param [ing] 	$src_height [裁剪区域高度]
	 * @return [atring|空字符串] [返回图片存储的名称]
	 */
	function GetCompressCut($data, $path, $width = 0, $height = 0, $src_x = 0, $src_y = 0, $src_width = 0, $src_height = 0)
	{
		if(!$this->Initialization($data, $path)) return '';

		if(empty($data['tmp_name'])) return '';
		
		$type = $this->Is_ImageType($data['type']);
		if($type == 'no') return '';

		$file_name = $this->GetNewFileName().'.'.$type;

		if($this->ImageCompress($width, $height, $data['tmp_name'], $type, $this->i_path.$file_name, $src_x, $src_y, $src_width, $src_height)) return $file_name;
		return '';
	}

	/**
	 * 图片压缩
	 * @param  [int] 	$width [指定图片宽度]
	 * @param  [int] 	$height[指定图片高度]
	 * @param  [string] $file  [原图片地址]
	 * @param  [string] $type  [类型]
	 * @param  [string] $path  [新图片地址]
	 * @return [boolean] 	   [成功true, 失败false]
	 */
	private function ImageCompress($width, $height, $file, $type, $path, $src_x = 0, $src_y = 0, $src_width = 0, $src_height = 0)
	{
		// 安全验证
        $ret = \base\FileUtil::FileContentSecurityCheck($file);
        if($ret['code'] != 0)
        {
            return false;
        }

		// 获取图片原本尺寸
		list($w, $h) = getimagesize($file);
		
		// 尺寸计算
		$new_width = ($width > 0 && $w > $width) ? $width : $w;
		$new_height = ($width > 0 && $w > $width) ? (round($h/($w/$width))) : $h;
		if($width > 0 && $height > 0) $new_width = $width;
		if($height > 0) $new_height = $height;

		// url创建一个新图象
		switch($type)
	    {
	        case 'gif':
	            $src_im = @imagecreatefromgif($file);
	            break;
	        case 'png':
	            $src_im = @imagecreatefrompng($file);
	            break;
	        default:
	            $src_im = @imagecreatefromjpeg($file);
	    }
	    if(!$src_im) return;

	    // 新建一个真彩色图像
	    $dst_im = imagecreatetruecolor($new_width, $new_height);

	    // 保留透明背景
        switch($type)
        {
            case 'png':
                // 保存完整alpha通道信息
                imagesavealpha($dst_im, true);
                // 上色
                $color = imagecolorallocatealpha($dst_im, 0, 0, 0, 127);
                // 设置透明色
                imagecolortransparent($dst_im, $color);
                // 填充透明色
                imagefill($dst_im, 0, 0, $color);
                break;
            default:;
        }

        // 是否裁剪图片
	    if($src_width > 0 && $src_height > 0)
	    {
	    	// 新建拷贝大小的真彩图像
	    	$cpd_im = imagecreatetruecolor($src_width, $src_height);

            // 保留透明背景
            switch($type)
            {
                case 'png':
                    // 保存完整alpha通道信息
                    imagesavealpha($cpd_im, true);
                    // 上色
                    $color = imagecolorallocatealpha($cpd_im, 0, 0, 0, 127);
                    // 设置透明色
                    imagecolortransparent($cpd_im, $color);
                    // 填充透明色
                    imagefill($cpd_im, 0, 0, $color);
                    break;
                default:;
            }

	    	// 拷贝图片
		    imagecopy($cpd_im, $src_im, 0, 0, $src_x, $src_y, $src_width, $src_height);

		    // 图片缩放
		    $s = imagecopyresampled($dst_im, $cpd_im, 0, 0, 0, 0, $new_width, $new_height, $src_width, $src_height);

		    // 销毁画布
		    imagedestroy($cpd_im);
		} else {
			// 图片缩放
	    	$s = imagecopyresampled($dst_im, $src_im, 0, 0, 0, 0, $new_width, $new_height, $w, $h);
		}
	    if($s)
	    {
		    switch($type)
		    {
		        case 'png':
		            imagepng($dst_im, $path);
		            break;
		        case 'gif':
		            imagegif($dst_im, $path);
		            break;
		        default:
		            imagejpeg($dst_im, $path, $this->i_quality);
		    }
		}
		imagedestroy($dst_im);
		imagedestroy($src_im);
		return $s;
	}
	
	/**
	 * 验证后缀名是否合法
	 * @author  Devil
	 * @blog    http://gong.gg/
	 * @version 1.0.0
	 * @date    2023-02-12
	 * @desc    description
	 * @param   [string]          $image_type [图片后缀类型]
	 * @return  [string] 			          [后缀名或no]
	 */
	private function Is_ImageType($image_type)
	{
		if(empty($image_type)) return 'no';
		if(array_key_exists($image_type, $this->i_type)) return $image_type;

		foreach($this->i_type as $key=>$val)
		{
			if($val == $image_type) return $key;
		}
		return 'no';
	}

	/**
	 * 远程图片保存
	 * @author   Devil
	 * @blog     http://gong.gg/
	 * @version  1.0.0
	 * @datetime 2017-10-28T23:34:19+0800
	 * @param    [atring]                   $url       [远程图片url地址]
	 * @param    [string]                   $path      [存储地址]
	 * @param    [string]                   $file_name [文件名称]
	 * @return   [string]                              [成功文件名称, 失败空]
	 */
	public function DownloadImageSave($url, $path = '', $file_name = '')
    {
    	$this->PathMkdir($path);
    	if(empty($file_name))
    	{
    		$file_name = $this->GetNewFileName().'.jpg';
    	}

    	// 下载文件
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        $file = curl_exec($ch);
        curl_close($ch);

        // 安全验证
        $ret = \base\FileUtil::FileContentSecurityCheck($file, false);
        if($ret['code'] != 0)
        {
            return '';
        }

       	// 存储文件
        if(file_put_contents($this->i_path.$file_name, $file) !== false)
        {
        	return $file_name;
        }
        return '';
    }

    /**
     * 图片转base64
     * @author  Devil
     * @blog    http://gong.gg/
     * @version 1.0.0
     * @date    2020-11-20
     * @desc    description
     * @param   [string]          $image_file [图片地址（不支持url形式）]
     */
    public function ImageToBase64($image_file)
    {
		$content = '';
		$dir_file = ROOT.'public'.ResourcesService::AttachmentPathHandle($image_file);
		if(file_exists($dir_file))
		{
			$info = getimagesize($dir_file);
			$image_data = fread(fopen($dir_file, 'r'), filesize($dir_file));
			$content = 'data:'.$info['mime'].';base64,'.chunk_split(base64_encode($image_data));
		} else {
			$content = 'data:image/jpg/png/gif;base64,'.chunk_split(base64_encode(RequestGet($image_file)));
		}
		return $content;
	}
}
?>