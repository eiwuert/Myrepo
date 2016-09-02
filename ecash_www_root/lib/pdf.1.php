<?php
	define ("FPDF_FONTPATH", "/virtualhosts/lib/fpdf151/font/");
	define ("ON", 1);
	define ("OFF", 0);
	define ("POSITION_RIGHT", 0);
	define ("POSITION_NEXT", 1);
	define ("POSITION_BELOW", 2);
	define ("LEFT", "L");
	define ("RIGHT", "R");
	define ("TOP", "T");
	define ("BOTTOM", "B");
	define ("CENTER", "C");
	define ("JUSTIFY", "J");
	define ("TRANSPARENT", 0);
	define ("PAINTED", 1);
	define ("BOLD", "B");
	define ("ITALIC", "I");
	define ("UNDERLINE", "U");
	define ("NORMAL", NULL);

	require_once ("/virtualhosts/lib/fpdf151/fpdf.php");

	class StyleDefinition
	{
		var $cell = array ();
		var $font = array ();
		var $fill = array ();
		var $line = array ();

		function StyleDefinition ()
		{
			$this->cell ["width"] = NULL;
			$this->cell ["height"] = 6;
			$this->cell ["border"] = OFF;
			$this->cell ["next_position"] = POSITION_NEXT;
			$this->cell ["align"] = LEFT;
			$this->cell ["fill"] = TRANSPARENT;

			$this->font ["size"] = 10;
			$this->font ["family"] = "Helvetica";
			$this->font ["style"] = NORMAL;
			$this->font ["color"]["red"] = 0;
			$this->font ["color"]["green"] = 0;
			$this->font ["color"]["blue"] = 0;

			$this->fill ["red"] = 0;
			$this->fill ["green"] = 0;
			$this->fill ["blue"] = 0;

			$this->line ["width"] = 0.25;

			return TRUE;
		}

		function DefineCell ($width = NULL, $height = 6, $border = OFF, $next_position = POSITION_NEXT, $align = LEFT, $fill = TRANSPARENT)
		{
			$this->cell ["width"] = $width;
			$this->cell ["height"] = $height;
			$this->cell ["border"] = $border;
			$this->cell ["next_position"] = $next_position;
			$this->cell ["align"] = $align;
			$this->cell ["fill"] = $fill;

			return TRUE;
		}

		function DefineFill ($red = 0, $green = 0, $blue = 0)
		{
			$this->fill ["red"] = $red;
			$this->fill ["green"] = $green;
			$this->fill ["blue"] = $blue;

			return TRUE;
		}

		function DefineFont ($size = 10, $family = "Helvetica", $style = NULL, $color_red = 0, $color_green = 0, $color_blue = 0)
		{
			$this->font ["size"] = $size;
			$this->font ["family"] = $family;
			$this->font ["style"] = $style;
			$this->font ["color"]["red"] = $color_red;
			$this->font ["color"]["green"] = $color_green;
			$this->font ["color"]["blue"] = $color_blue;

			return TRUE;
		}

		function DefineLine ($width = 0.25)
		{
			$this->line ["width"] = $width;

			return TRUE;
		}
	}


	class PDF_1 extends FPDF
	{
		function Output($stream_type = "BROWSER", $file='',$download=false)
		{
			//Output PDF to file or browser
			global $HTTP_ENV_VARS;

			if($this->state<3)
				$this->Close();

			switch ($stream_type)
			{
				case "BROWSER":
					//Send to browser
					Header('Content-Type: application/pdf');
					if(headers_sent())
						$this->Error('Some data has already been output to browser, can\'t send PDF file');
					Header('Content-Length: '.strlen($this->buffer));
					Header('Content-disposition: inline; filename=doc.pdf');
					echo $this->buffer;
					break;

				case "REMOTE":
					//Download file
					if(isset($HTTP_ENV_VARS['HTTP_USER_AGENT']) and strpos($HTTP_ENV_VARS['HTTP_USER_AGENT'],'MSIE 5.5'))
						Header('Content-Type: application/dummy');
					else
						Header('Content-Type: application/octet-stream');
					if(headers_sent())
						$this->Error('Some data has already been output to browser, can\'t send PDF file');
					Header('Content-Length: '.strlen($this->buffer));
					Header('Content-disposition: attachment; filename='.$file);
					echo $this->buffer;
					break;

				case "LOCAL":
					//Save file locally
					$f=fopen($file,'wb');
					if(!$f)
						$this->Error('Unable to create output file: '.$file);
					fwrite($f,$this->buffer,strlen($this->buffer));
					fclose($f);
					break;

				case "RAW":
					return $this->buffer;
					break;
			}
		}

		function StyleCell ($style, $text = NULL, $link = NULL)
		{
			$this->SetFillColor ($style->fill ["red"], $style->fill ["green"], $style->fill ["blue"]);
			$this->SetFont ($style->font ["family"], $style->font ["style"], $style->font ["size"]);

			$this->SetTextColor ($style->font ["color"]["red"], $style->font ["color"]["green"], $style->font ["color"]["blue"]);

			if ($style->cell ["border"] == ON)
			{
				$this->SetLineWidth ($style->line ["width"]);
			}

			$this->Cell
			(
				$style->cell ["width"],
				$style->cell ["height"],
				$text,
				$style->cell ["border"],
				$style->cell ["next_position"],
				$style->cell ["align"],
				$style->cell ["fill"],
				$link
			);

			return TRUE;
		}

		function StyleWrite ($style, $text = NULL, $link = NULL)
		{
			$this->SetFont ($style->font ["family"], $style->font ["style"], $style->font ["size"]);

			$this->SetTextColor ($style->font ["color"]["red"], $style->font ["color"]["green"], $style->font ["color"]["blue"]);

			$this->Write
			(
				$style->cell ["height"],
				$text,
				$link
			);

			return TRUE;
		}

		function StyleMultiCell ($style, $text = NULL, $link = NULL)
		{
			$this->SetFillColor ($style->fill ["red"], $style->fill ["green"], $style->fill ["blue"]);
			$this->SetFont ($style->font ["family"], $style->font ["style"], $style->font ["size"]);

			$this->SetTextColor ($style->font ["color"]["red"], $style->font ["color"]["green"], $style->font ["color"]["blue"]);

			if ($style->cell ["border"] == ON)
			{
				$this->SetLineWidth ($style->line ["width"]);
			}

			$this->MultiCell
			(
				$style->cell ["width"],
				$style->cell ["height"],
				$text,
				$style->cell ["border"],
				$style->cell ["align"],
				$style->cell ["fill"],
				$link
			);

			return TRUE;
		}

		function EAN13($x,$y,$barcode, $h=16,$w=.35)
		{
			$this->Barcode($x,$y,$barcode, $h,$w,13);
		}

		function UPC_A($x,$y,$barcode, $h=16,$w=.35)
		{
			$this->Barcode($x,$y,$barcode, $h,$w,12);
		}

		function GetCheckDigit($barcode)
		{
			//Compute the check digit
			$sum=0;
			for($i=1;$i<=11;$i+=2)
			{
				$sum+=3*$barcode{$i};
			}

			for($i=0;$i<=10;$i+=2)
			{
				$sum+=$barcode{$i};
			}

			$r=$sum%10;

			if($r>0)
			{
				$r=10-$r;
			}

			return $r;
		}

		function TestCheckDigit($barcode)
		{
			//Test validity of check digit
			$sum=0;

			for($i=1;$i<=11;$i+=2)
			{
				$sum+=3*$barcode{$i};
			}

			for($i=0;$i<=10;$i+=2)
			{
				$sum+=$barcode{$i};
			}

			return (($sum+$barcode{12})%10==0);
		}

		function Barcode($x,$y,$barcode, $h,$w,$len)
		{
			//Padding
			$barcode=str_pad($barcode,$len-1,'0',STR_PAD_LEFT);
			if($len==12)
			{
				$barcode='0'.$barcode;
			}

			//Add or control the check digit
			if(strlen($barcode)==12)
			{
				$barcode.=$this->GetCheckDigit($barcode);
			}
			elseif(!$this->TestCheckDigit($barcode))
			{
				$this->Error('Incorrect check digit');
			}

			//Convert digits to bars
			$codes=array
			(
				'A'=>array
				(
					'0'=>'0001101','1'=>'0011001','2'=>'0010011','3'=>'0111101','4'=>'0100011',
					'5'=>'0110001','6'=>'0101111','7'=>'0111011','8'=>'0110111','9'=>'0001011'
				),
				'B'=>array
				(
					'0'=>'0100111','1'=>'0110011','2'=>'0011011','3'=>'0100001','4'=>'0011101',
					'5'=>'0111001','6'=>'0000101','7'=>'0010001','8'=>'0001001','9'=>'0010111'
				),
				'C'=>array
				(
					'0'=>'1110010','1'=>'1100110','2'=>'1101100','3'=>'1000010','4'=>'1011100',
					'5'=>'1001110','6'=>'1010000','7'=>'1000100','8'=>'1001000','9'=>'1110100'
				)
			);

			$parities=array
			(
				'0'=>array('A','A','A','A','A','A'),
				'1'=>array('A','A','B','A','B','B'),
				'2'=>array('A','A','B','B','A','B'),
				'3'=>array('A','A','B','B','B','A'),
				'4'=>array('A','B','A','A','B','B'),
				'5'=>array('A','B','B','A','A','B'),
				'6'=>array('A','B','B','B','A','A'),
				'7'=>array('A','B','A','B','A','B'),
				'8'=>array('A','B','A','B','B','A'),
				'9'=>array('A','B','B','A','B','A')
			);

			$code='101';
			$p=$parities[$barcode{0}];
			for($i=1;$i<=6;$i++)
			{
				$code.=$codes[$p[$i-1]][$barcode{$i}];
			}

			$code.='01010';
			for($i=7;$i<=12;$i++)
			{
				$code.=$codes['C'][$barcode{$i}];
			}

			$code.='101';

			//Draw bars
			for($i=0;$i<strlen($code);$i++)
			{
				if($code{$i}=='1')
				{
					$this->Rect($x+$i*$w,$y,$w,$h,'F');
				}
			}
/*
			//Print text under barcode
			$start_y = $y + $h + 11/$this->k;
			list ($url, $promo) = explode (":", $source);
			$this->SetFont('Courier','',9);
			$this->Text($x,$start_y, (float)substr ($barcode, 0, -1));
			//$this->SetFont('Courier','',9);
			$this->Text($x,$start_y + 3,$url);
			$this->Text($x,$start_y + 6,$promo);
*/
		}
	}
?>
