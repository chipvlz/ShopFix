<?php
	/**
	* ImageResizer
	*/
	class ImageResizer {
		static private $instance = null;

		static public function i() {
			if (is_null(static::$instance)) {
				static::$instance = new self();
			}
			return static::$instance;
		}

		private function openImage($file) {
		    $imageFileType = strtolower(pathinfo($file,PATHINFO_EXTENSION));
		    switch($imageFileType) {
		        case 'jpg':
		        case 'jpeg':
		            $img = @imagecreatefromjpeg($file);
		            break;
		        case 'gif':
		            $img = @imagecreatefromgif($file);
		            break;
		        case 'png':
		            $img = @imagecreatefrompng($file);
		            break;
		        default:
		            $img = false;
		            break;
		    }
		    return $img;
		}

		public function resizeImage($imagePath,$new_width,$new_height) {
			$fileName = pathinfo($imagePath,PATHINFO_FILENAME);
    		$fullPath = pathinfo($imagePath,PATHINFO_DIRNAME)."/".$fileName."_small.png";
    		if (file_exists($fullPath)) {
    			return str_replace($_SERVER['DOCUMENT_ROOT'], "", $fullPath);
    		}
			$image = $this->openImage($imagePath);
			if ($image == false) {
				return null;
			}
			$width = imagesx($image);
			$height = imagesy($image);
    		$imageResized = imagecreatetruecolor($width, $height);
    		if ($imageResized == false) {
    			return null;
    		}
		    $image = imagecreatetruecolor($width , $height);
		    $imageResized = imagescale($image,$new_width,$new_heigh,IMG_BICUBIC_FIXED);
    		touch($fullPath);
    		$write = imagepng($imageResized,$fullPath);
    		if (!$write) {
    			imagedestroy($imageResized);	
    			return null;
    		}
			imagedestroy($imageResized);
			return str_replace($_SERVER['DOCUMENT_ROOT'], "", $fullPath);
		}

		function __construct() {
			
		}
	}
?>