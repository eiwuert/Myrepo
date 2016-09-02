<?php
	
	/*
	 * This library is for creating a CAPTCHA (Completely Automated Public
	 * Turing Test to Tell Computers and Humans Apart) based on
	 * PartnerWeekly's confirmation number widget.  Requires GD support.
	 */
	class Captcha_2
	{
		
		const OUTPUT_PNG = 1;
		const OUTPUT_JPG = 2;
		const OUTPUT_GIF = 3;
		
		const CHARS_ALPHA = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		const CHARS_NUMERIC = '1234567890';
		const CHARS_ALPHANUMERIC = 'ABCDEFGHIJKLMNPQRSTUVWXYZ123456789';
		const CHARS_HEX = 'ABCDEF123456789';
		
		protected $display_string;
		
		protected $font = '/virtualhosts/lib/ttf/4.ttf';
		protected $font_size = 30;
		protected $font_color;
		
		// offset of the shadowy text
		protected $shadow_offset_x = 2;
		protected $shadow_offset_y = 2;
		
		protected $border_width = 1;
		protected $border_color;
		
		protected $margin_top = 10;
		protected $margin_bottom = 10;
		protected $margin_left = 10;
		protected $margin_right = 10;
		
		public function __construct($display_string = NULL)
		{
			
			if (is_null($display_string))
			{
				$this->Generate_String();
			}
			else
			{
				$this->display_string = $display_string;
			}
			
		}
		
		public function Get_String()
		{
			//get the string that is being displayed
			return $this->display_string;
		}
		
		/**
		 * Generates a random display string.
		 *
		 * @param string $allowed Allowed characters
		 * @param int $length The length of the string to generate
		 * @return string
		 */
		public function Generate_String($allowed = self::CHARS_NUMERIC, $length = 5)
		{
			
			$last = (strlen($allowed) - 1);
			$string = '';
			
			for ($i = 0; $i < $length; $i++)
			{
				$string .= substr($allowed, rand(0, $last), 1);
			}
			
			$this->display_string = $string;
			return $string;
			
		}
		
		/**
		 * Sets the CAPTCHA box margins
		 * @param int $top Top margin
		 * @param int $left Left margin
		 * @param int $right Right margin
		 * @param int $bottom Bottom margin
		 * @return void
		 */
		public function Margin($top, $left, $right, $bottom)
		{
			
			$this->margin_top = $top;
			$this->margin_left = $left;
			$this->margin_right = $right;
			$this->margin_bottom = $bottom;
			return;
			
		}
		
		/**
		 * Sets font options
		 *
		 * @param string $file TrueType font file
		 * @param int $size Font size
		 * @param mixed $color Font color (as hex, array[r,g,b], etc.)
		 * @return void
		 */
		public function Font($file, $size, $color)
		{
			
			$this->font = $file;
			$this->font_size = $size;
			$this->font_color = $color;
			return;
			
		}
		
		/**
		 * Sets the shadowy text offset
		 *
		 * @param int $x The x offset
		 * @param int $y The y offset
		 * @return void
		 */
		public function Shadow_Offset($x, $y)
		{
			
			$this->shadow_offset_x = $x;
			$this->shadow_offset_y = $y;
			return;
			
		}
		
		/**
		 * Sets the border options
		 *
		 * @param int $width Border width in pixels
		 * @param mixed $color Border color
		 */
		public function Border($width, $color)
		{
			$this->border_width = $width;
			$this->border_color = $color;
			return;
		}
		
		/**
		 * Displays the CAPTCHA image.
		 *
		 * @param int $output_type The output format (GIF, PNG, etc.)
		 * @return void
		 */
		function Display($output_type = self::OUTPUT_PNG)
		{
			
			// get the dimensions of the string on the image
			$box = imagettfbbox($this->font_size, 0, $this->font, $this->display_string);
			
			// set the width and height of the resulting image
			$width = (abs($box[2] - $box[6]) + $this->margin_left + $this->margin_right);
			$height = (abs($box[3] - $box[7]) + $this->margin_top + $this->margin_bottom);
			
			// initialize the image
			$im = imagecreatetruecolor($width, $height);
			
			// add some background noise
			$this->Add_Noise($im, $width, $height);
			
			// assign our secondary color for the font
			$col = array();
			$col[0] = array(255, 0, 0);
			$col[1] = array(0, 0, 255);
			$col[2] = array(0, 255, 0);
			$col[3] = array(200, 0, 200);
			
			// assign the secondary color
			$font_color = ($this->font_color !== NULL) ? $this->Color($this->font_color) : $col[rand(0, 3)];
			$font_color = imagecolorallocatealpha($im, $font_color[0], $font_color[1], $font_color[2], 100);
			
			// add the primary text
			$x = $this->margin_left;
			$y = $this->margin_top;
			imagettftext($im, $this->font_size, 0, ($x - $box['6']), ($y - $box['7']), $font_color, $this->font, $this->display_string);
			
			// assign the primary color
			$shadow_color = imagecolorallocatealpha($im, 0, 0, 0, 100);
			
			// draw the shadowy text
			$x += $this->shadow_offset_x;
			$y += $this->shadow_offset_y;
			imagettftext($im, $this->font_size, 0, ($x - $box['6']), ($y - $box['7']), $shadow_color, $this->font, $this->display_string);
			
			// draw the border
			$border_color = ($this->border_color !== NULL) ? $this->Color($this->border_color) : array(0, 0, 0);
			$border = imagecolorallocate($im, $border_color[0], $border_color[1], $border_color[2]);
			
			if ($this->border_width !== NULL) imagesetthickness($im, $this->border_width);
			imagerectangle($im, 0, 0, ($width - 1), ($height - 1), $border_color);
			
			// Using imagepng() results in clearer text compared with imagejpeg()
			$this->Render($im, $output_type);
			imagedestroy($im);
			
			return;
			
		}
		
		/**
		 * Allocates a random color in the image.
		 *
		 * @param resource $image The GD image resource
		 * @return void
		 */
		protected function Random_Color($image)
		{
			$color = imagecolorallocate($image, rand(100,255), rand(100,255), rand(100,255));
			return $color;
		}
		
		/**
		 * Renders the image in the given format
		 *
		 * @param resource $image The GD image resource
		 * @param int $type The output format (GIF, PNG, etc.)
		 * @return void
		 */
		protected function Render($image, $type)
		{
			
			switch ($type)
			{
				
				case self::OUTPUT_GIF:
					header("Content-type: image/gif");
					imagegif($image);
					break;
					
				case self::OUTPUT_PNG:
				default:
					header("Content-type: image/png");
					imagepng($image);
					break;
					
			}
			
			return;
			
		}
		
		/**
		 * Adds random background noise to the image.
		 *
		 * @param resource $image The GD image resource
		 * @param int $width Width of the image
		 * @param int $height Height of the image
		 * @return void
		 */
		protected function Add_Noise($image, $width, $height)
		{
			
			$y = 0;
			
			// add noise to the background
			while ($y < $height)
			{
				
				$x = 0;
				
	      while ($x < $width)
	      {
	        imagesetpixel($image, $x++, $y, $this->Random_Color($image));
	      }
	      
				$y++;
				
			}
			
			return;
			
		}
		
		/**
		 * Normalizes various color expressions to an array of R, G, and B.
		 */
		protected static function Color($color)
		{
			
			if (is_string($color))
			{
				
				if ($color{0} === '#')
				{
					$color = substr($color, 1);
				}
				
				$c = array();
				
				$c[] = hexdec(substr($color, 0, 2));
				$c[] = (strlen($color) > 2) ? hexdec(substr($color, 2, 2)) : 0;
				$c[] = (strlen($color) > 4) ? hexdec(substr($color, 4, 2)) : 0;
				
			}
			elseif (is_array($color))
			{
				// make sure it has numeric keys
				$c = array_values($color);
			}
			else
			{
				$c = array((int)$color);
			}
			
			$c = array_pad($c, 3, 0);
			return $c;
			
		}
		
	}
	
?>
