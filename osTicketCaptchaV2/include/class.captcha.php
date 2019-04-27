<?php
/*********************************************************************
	class.captcha.php

	Less basic captcha class v1.0 for OSTicket (replaces core class.captcha.php file in the /includes folder)

	By Simon Pittock, (c)2019 Clickteam LLC
	http://www.clickteam.com
	
	Please note this is free software and is not supported at Clickteam - if you have any issues, please
	raise them here on GitHub under Issues - thanks!

	This will not work without uploading the relevant font file (eg. default is arial.ttf) to the directory indicated in $opts['fontPath']
	You can find a compact (304k) version of arial.ttf here:
	https://github.com/JotJunior/PHP-Boleto-ZF2/raw/master/public/assets/fonts/arial.ttf

	You can change the /captcha.php on line 18 to reflect your choice of captcha length and font size, however if you leave the default
	file in place, this script will detect this and default to 6 characters at 18pt (which is my optimal setting)

	Released under the GNU General Public License WITHOUT ANY WARRANTY.

**********************************************************************/
class Captcha{
	var $hash, $length, $opts;
	
	function __construct($hashLengthX = 6, $fontPointsX = 18, $fontFaceX = 'arial.ttf'){
		//Set up the Captcha class and generate hash string of desired length from [0-9][A-Z], plus transfer chosen metrics to options storage array
		
		//Detect default OSTicket class calls from captcha.php and replace incoming arguments to defaults
		if(strpos($fontFaceX, 'images/captcha')){
			$hashLength = 6;
			$fontPoints = 18;
			$fontFace = 'arial.ttf';
			
		}else{
			$hashLength = $hashLengthX;
			$fontPoints = $fontPointsX;
			$fontFace = $fontFaceX;

		}			
		
		$this->hash = strtoupper(substr(md5(rand(0, 9999)), rand(0, 24), $hashLength));
		$this->length = $hashLength;

		$this->opts = array(
			'cellWidth'	=>	40,					//Set the width of each character's cell within which it is positioned randomly
			'cellHeight'	=>	40,					//Set the height for the Captcha
			'cellMargin'	=>	5,					//Margin at the edges of each cell (character will not be drawn in this margin)
			
			'bgPxWidth'	=>	6,					//Set to 1 or above, determines the width of random colour pixels for the background
			'bgPxHeight'	=>	6,					//Set to 1 or above, determines the height of random colour pixels for the background

			'colMode'		=>	true,				//If set to true, Captcha background will generated be colour, otherwise in B+W
			'colLo'		=>	60,					//Lowest RGB/B+W colour level to be used (lower = darker background noise, less contrast with black text)
			'colHi'		=>	255,					//Highest RGB/B+W colour level to be used (higher = lighter background noise, more contrast with black text)

			'colTextMode'	=>	false,				//If set to true, Captcha characters will generated be colour, otherwise in B+W
			'colTextLo'	=>	[20,20,20],			//Lowest RGB/B+W colour of text characters (for B+W, only the first value in the triplet is used)
			'colTextHi'	=>	[60,60,60],			//Highest RGB/B+W colour of text characters (default: [0,0,0] - be careful, coloured/light characters can be hard to read!)
			
			'fontRot'		=>	25,					//If not set to 0, amount in degrees by which each character can randomly rotate left or right
			'fontPath'	=>	'/include/',			//This is the path to your font file, relative to your web root, which must start and end with a / eg. /includes/
			
			'fontPts'		=>	$fontPoints,			//AUTOMATIC - DO NOT ALTER MANUALLY: font size in points
			'fontPx'		=>	$fontPoints * 0.75,		//AUTOMATIC - DO NOT ALTER MANUALLY: calculate rough pixel height from points
			'fontFace'	=>	$fontFace				//AUTOMATIC - DO NOT ALTER MANUALLY: font filename
		
		);
		
		//Run the cleanOpts private function to ensure any incorrect/deleterious settings are corrected.
		$this->cleanOpts();

	}

	function getImage(){
		//Generate and return a captcha image
		$opts = &$this->opts;
		if(!extension_loaded('gd') || !function_exists('gd_info')){
			//GD ext required.
			return;
			
		}

		//Clear the Captcha session value
		$_SESSION['captcha'] ='';

		//Calculate the overall Captcha width and height
		$cWidth = $this->length * $opts['cellWidth'];
		$cHeight = $opts['cellHeight'];

		//Set up image and get font path and pixel size
		$img = imagecreatetruecolor($cWidth, $cHeight);
		$cFont = realpath('.') . $opts['fontPath'] . $opts['fontFace'];
	
		//Generate random noise background
		$cBgPxStepsX = $cWidth / $opts['bgPxWidth'];
		$cBgPxStepsY = $cHeight / $opts['bgPxHeight'];

		for($i = 0; $i < $cBgPxStepsX; $i++) {
			for($i2 = 0; $i2 < $cBgPxStepsY; $i2++) {
				if(!$opts['colMode']){
					//Black and White mode
					$temRand = rand($opts['colLo'],$opts['colHi']);
					$temPixelCol = imagecolorallocate($img, $temRand, $temRand, $temRand);
					
				}else{
					//Colour mode
					$temPixelCol = imagecolorallocate($img, rand($opts['colLo'], $opts['colHi']), rand($opts['colLo'], $opts['colHi']), rand($opts['colLo'], $opts['colHi']));
					
				}
				if($opts['bgPxWidth'] > 1 || $opts['bgPxWidth'] > 1){
					imagefilledrectangle($img, $opts['bgPxWidth'] * $i , $opts['bgPxHeight'] * $i2, $opts['bgPxWidth'] * ($i + 1), $opts['bgPxHeight'] * ($i2 + 1), $temPixelCol);
										
				}else{
					imagesetpixel($img, $opts['bgPxWidth'] * $i , $opts['bgPxHeight'] * $i2, $temPixelCol);
					
				}
				
			}
			
		}	
		
		//Split the hash string into an array which can be iterated to generate characters on image
		$temHash = str_split($this->hash);
		$i = 0;

		//Determine in advance whether text is to be randomly coloured or not
		if(!$opts['colTextMode'] && $opts['colTextLo'][0] != $opts['colTextHi'][0]){
			$cTextRand = true;
			
		}elseif(!$opts['colTextMode']){
			$cTextCol = imagecolorallocate($img, $opts['colTextLo'][0], $opts['colTextLo'][0], $opts['colTextLo'][0]);
			$cTextRand = false;
			
		}
				
		if($opts['colTextMode'] && ($opts['colTextLo'][0] != $opts['colTextHi'][0] || $opts['colTextLo'][1] != $opts['colTextHi'][1] || $opts['colTextLo'][2] != $opts['colTextHi'][2])){
			$cTextRand = true;
			
		}elseif($opts['colTextMode']){
			$cTextCol = imagecolorallocate($img, $opts['colTextLo'][0], $opts['colTextLo'][1], $opts['colTextLo'][2]);
			$cTextRand = false;

		}

		//Calculate the maximum range for the random character placement within its cell, accounting for cell size, padding and character size (from font height)
		$temRandMaxW = $opts['cellWidth'] - ($opts['cellMargin'] * 2) - $opts['fontPx'];
		$temRandMaxH = $opts['cellHeight'] - ($opts['cellMargin'] * 2) - $opts['fontPx'];
		
		//Iterate $temHash character array and generate character for each element
		foreach($temHash as $temHashChar){
			//Generate random position
			$x = ($i * $opts['cellWidth']) + $opts['cellMargin'] + rand(0, $temRandMaxW);
			$y = $opts['cellHeight'] - $opts['cellMargin'] - rand(0, $temRandMaxH);
			
			//Handle character rotations
			if($opts['fontRot'] != 0){
				$temRot = rand(0, $opts['fontRot'] * 2) - $opts['fontRot'];
				
			}else{
				$temRot = 0;
				
			}
			
			//Handle Font colour
			if($cTextRand && !$opts['colTextMode']){
				//B+W mode
				$temRand = rand($opts['colTextLo'][0], $opts['colTextHi'][0]);
				$cTextCol = imagecolorallocate($img, $temRand, $temRand, $temRand);
				
			}elseif($cTextRand && $opts['colTextMode']){
				//Colour mode
				$temRandR = rand($opts['colTextLo'][0], $opts['colTextHi'][0]);
				$temRandG = rand($opts['colTextLo'][1], $opts['colTextHi'][1]);
				$temRandB = rand($opts['colTextLo'][2], $opts['colTextHi'][2]);
				$cTextCol = imagecolorallocate($img, $temRandR, $temRandG, $temRandB);

				
			}
			
			imagefttext($img, $opts['fontPts'], $temRot, $x, $y, $cTextCol, $cFont, $temHashChar);
			$i++;
				
		}

		//Output Captcha image and store md5 hash in session variable
		header("Content-Type: image/png");
		imagepng($img);
		imagedestroy($img);
		$_SESSION['captcha'] = md5($this->hash);
		
	}
	
	private function cleanOpts(){
		//Function to clean up any user input errors in the options
		$opts = &$this->opts;
		
		if($opts['cellMargin'] > $opts['cellWidth'] || $opts['cellMargin'] > $opts['cellHeight']){
			$opts['cellMargin'] = 0;
			
		}
		
		if($opts['cellWidth'] < 1){
			$opts['cellWidth'] = 1;
			
		}
		if($opts['cellWidth'] < ($opts['fontPx'] + (2 * $opts['cellMargin']))){
			$opts['cellWidth'] = $opts['fontPx'] + (2 * $opts['cellMargin']);
			
		}

		if($opts['cellHeight'] < 1){
			$opts['cellHeight'] = 1;
			
		}
		if($opts['cellHeight'] < ($opts['fontPx'] + (2 * $opts['cellMargin']))){
			$opts['cellHeight'] = $opts['fontPx'] + (2 * $opts['cellMargin']);
			
		}

		if($opts['bgPxWidth'] < 1){
			$opts['bgPxWidth'] = 1;
			
		}
		if($opts['bgPxHeight'] < 1){
			$opts['bgPxHeight'] = 1;
			
		}
		if($opts['fontRot'] > 170){
			$opts['fontRot'] = 170;
			
		}

		if(gettype($opts['colMode']) != 'boolean'){
			$opts['colMode'] = true;
			
		}
		if(gettype($opts['colTextMode']) != 'boolean'){
			$opts['colTextMode'] = false;
			
		}
		
	}
	
}
?>
