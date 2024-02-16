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

//function to check submitted fields for validity
function check_submitted_field($fieldid, $fieldname, $fieldclass, $fielddata, $fieldmandatory = 1, $fieldproperties = '') {
	global $db;
	$debug = TRUE;
	//check mandatory fields
	if (($fieldmandatory == TRUE) && (strlen(trim($fielddata)) <= 0)) {
		if ($debug === TRUE) echo $fieldid . ' (empty)<br>';
		return FALSE;
	}
	//number
	if (($fieldclass == 'number') && !empty($fielddata)) {
		if (!is_numeric($fielddata)) {
			if ($debug === TRUE) echo $fieldid . '<br>';
			return FALSE;
		}
		else {
			//check min if any
			if (is_array($fieldproperties) && array_key_exists('min', $fieldproperties)) {
				if ($fielddata <= $fieldproperties['min']) {
					if ($debug === TRUE) echo $fieldid . '<br>';
					return FALSE;
				}
			}
			//check max if any
			if (is_array($fieldproperties) && array_key_exists('max', $fieldproperties)) {
				if ($fielddata >= $fieldproperties['max']) {
					if ($debug === TRUE) echo $fieldid . '<br>';
					return FALSE;
				}
			}
			//TODO: check any if any
		}
	}
	//text
	if (($fieldclass == 'text') && !empty($fielddata)) {
		if (strlen(trim($fielddata)) < 1) {
			if ($debug === TRUE) echo $fieldid . '<br>';
			return FALSE;
		}
	}
	//multiline text
	if (($fieldclass == 'mtext') && !empty($fielddata)) {
		if (strlen(trim($fielddata)) < 1) {
			if ($debug === TRUE) echo $fieldid . '<br>';
			return FALSE;
		}
	}
	//status
	if (($fieldclass == 'status') && !empty($fielddata)) {
		$stati = array(1 => 'bestaand', 2 => 'realisatie', 3 => 'buiten gebruik', 4 => 'verwijderd');
		if (!array_key_exists($fielddata, $stati)) {
			if ($debug === TRUE) echo $fieldid . '<br>';
			return FALSE;
		}
	}
	//wegbeheerder
	if (($fieldclass == 'wegbeheerder') && !empty($fielddata)) {
		$qry2 = "SELECT `id` FROM `".$db['prefix']."organisation`
		WHERE `id` = '" . mysqli_real_escape_string($db['link'], $fielddata) . "'";
		$res2 = mysqli_query($db['link'], $qry2);
		if (!mysqli_num_rows($res2)) {
			if ($debug === TRUE) echo $fieldid . ': ' . htmlspecialchars($fielddata) . '<br>';
			return FALSE;
		}
	}
	if (($fieldclass == 'drip_standaardtekst') || ($fieldclass == 'drip_bewegwijzering')) {
		//check image file
		if ($fielddata['unsetfile'] !== 'true') {
			$max_filesize = 100*1024; //bytes
			$ext_allowed = array('png', 'jpg', 'jpeg');
			//if there is a file
			if (!empty($_FILES[$fieldid . '_file']) && ($_FILES[$fieldid . '_file']['error'] !== UPLOAD_ERR_NO_FILE)) {
				//check upload errors
				if ($_FILES[$fieldid . '_file']['error'] !== UPLOAD_ERR_OK) {
					if ($debug === TRUE) echo $fieldid . '(uplerr ' . $_FILES[$fieldid . '_file']['error'] . ' https://www.php.net/manual/en/features.file-upload.errors.php)<br>';
					return FALSE;
				}
				//check size
				if ($_FILES[$fieldid . '_file']['size'] > $max_filesize) {
					if ($debug === TRUE) echo $fieldid . '(size)<br>';
					return FALSE;
				}
				//check file extension
				$ext = strtolower(substr($_FILES[$fieldid . '_file']['name'], strrpos($_FILES[$fieldid . '_file']['name'], '.') + 1));
				//controleer of extensie toegestaan
				if (!in_array($ext, $ext_allowed)) {
					if ($debug === TRUE) echo $fieldid . '(ext)<br>';
					return FALSE;
				}
			}
		}
	}
	//if all passed, field is valid:
	return TRUE;
}
?>