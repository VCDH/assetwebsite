<?php 
/*
 	assetwebsite - viewer en aanvraagformulier voor verkeersmanagementassets
    Copyright (C) 2020 Gemeente Den Haag, Netherlands
    Developed by Jasper Vries
 
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/
include('dbconnect.inc.php');

//t=assettype id, w=wegbeheerder id
if (is_numeric($_GET['t'])) {
	if (!is_numeric($_GET['w'])) {
		$_GET['w'] = 0;
	}
	do {
		//check if image exists
		if ($_GET['i'] == 1) {
			$imagefile = 'images/'.$_GET['t'].'_'.$_GET['w'].'_'.$_GET['i'].'.png';
		}
		else {
			$imagefile = 'images/'.$_GET['t'].'_'.$_GET['w'].'.png';
		}
		$imgsize = 16; //(px)
		//render file
		if (!file_exists($imagefile)) {
			$lineshape = NULL;
			//allow assettype=0 for generic icon
			if ($_GET['t'] != 0) {
				//check if assettype exists
				$qry = "SELECT `id`, `lineshape` FROM `".$db['prefix']."assettype` 
				WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_GET['t']) . "'
				LIMIT 1";
				$res = mysqli_query($db['link'], $qry);
				if (mysqli_num_rows($res) != 1) {
					break;
				}
				$assettype = mysqli_fetch_row($res);
				$assettype = $assettype[0];
				$lineshape = $assettype[1];
			}
			else {
				$assettype = 0;
			}
			//default lineshape
			if (empty($lineshape)) { //default lineshape is a square, if none is defined in the database
				$lineshape = '1,0 1,15; 0,1 15,1; 15,1, 15,15; 1,15 15,15';
				//lineshape format is x0,y0 x1,y1[; x0,y0 x1,y1[; ...]] spaces, commas and semicolons are strict
			}
			if ($_GET['i'] == 1) {
				$lineshape .= '; 4,8 11,8; 4,12 11,12';
			}
			//default colors
			$bordercolor = '000000';
			$fillcolor = 'CCCCCC';
			$wegbeheerder = 0;
			//get colors
			$qry = "SELECT `id`, `bordercolor`, `fillcolor` FROM `".$db['prefix']."organisation` 
			WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_GET['w']) . "'
			LIMIT 1";
			$res = mysqli_query($db['link'], $qry);
			if (mysqli_num_rows($res) == 1) {
				$wegbeheerder = mysqli_fetch_row($res);
				$bordercolor = $wegbeheerder[1];
				$fillcolor = $wegbeheerder[2];
				$wegbeheerder = $wegbeheerder[0];
			}
			
			$image = imagecreatetruecolor($imgsize, $imgsize); //square image
			$transp = imagecolorallocatealpha($image, 0, 0, 255, 127);
			imagefill($image, 0, 0, $transp); //with a transparent background
			//create colors as gd allocation
			$bordercolor = imagecolorallocate($image, hexdec(substr($bordercolor, 0, 2)), hexdec(substr($bordercolor, 2, 2)), hexdec(substr($bordercolor, 4, 2)));
			$fillcolor = imagecolorallocate($image, hexdec(substr($fillcolor, 0, 2)), hexdec(substr($fillcolor, 2, 2)), hexdec(substr($fillcolor, 4, 2)));
			//set line width
			imagesetthickness($image, 2);
			//draw outline
			$lines = explode('; ', $lineshape);
			foreach ($lines as $line) {
				$line = explode(' ', $line);
				$start = explode(',', $line[0]);
				$end = explode(',', $line[1]);
				imageline($image, $start[0], $start[1], $end[0], $end[1], $bordercolor);
			}
			//fill
			imagefill($image, floor($imgsize/2), floor($imgsize/2)+2, $fillcolor);
			//store image
			imagesavealpha($image, true);
			imagepng($image, $imagefile, 9);
		}
		//serve file
		$expires = 2592000;
		header('Cache-Control: max-age=' . $expires);
		header('Expires: ' . gmdate("D, d M Y H:i:s", time() + $expires) . ' GMT');
		header('Content-Type: image/png');
		echo file_get_contents($imagefile);
		exit;
	}
	while (0);
}
//false request
header('HTTP/1.0 404 Not Found');
exit;
?>