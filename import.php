<?php 
/*
 	assetwebsite - viewer en aanvraagformulier voor verkeersmanagementassets
    Copyright (C) 2023 Gemeente Den Haag, Netherlands
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
session_start();
include_once('getuserdata.inc.php');
$login = getuserdata();
//include database gegevens
include('dbconnect.inc.php');
include('config.inc.php');
include('accesslevels.cfg.php');

$url_base = 'http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/'));
$url_index = $url_base.'/index.php';

$uploadmap = 'upload/'; //TODO hier een generieke setting van maken

//redirect if not logged in
if (!getuserdata() || !accesslevelcheck($cfg_accesslevel['beheer_import'])) {
	header('Location:'.$url_index);
	exit;
}

//handle upload from step 0
if ($_POST['formstep'] == '1') {

	//if there is a type
	$qry = "SELECT `id`, `name` FROM `".$db['prefix']."assettype` 
	WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_POST['assettypeid']) . "'
	LIMIT 1";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res) == 1) {
		$assettype = mysqli_fetch_assoc($res); //array(id =>, name =>)
	}
	else {
		//no valid assettype
		//this should not be reachable
		echo 'Assettype not found';
		exit;
	}

	//if there is a file
	if (!empty($_FILES['file']) && ($_FILES['file']['error'] !== UPLOAD_ERR_NO_FILE)) {
		//check upload errors
		if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
			if ($debug === TRUE) echo '(uplerr ' . $_FILES['file']['error'] . ' https://www.php.net/manual/en/features.file-upload.errors.php)<br>';
			$_POST['formstep'] = '0';
		}
		else {
			//store file
			$file_name = strtolower(md5_file($_FILES['file']['tmp_name']));
			//controleer of bestand al bestaat
			if (!file_exists($uploadmap . $file_name)) {
				//bestand bestaat nog niet
				if (!@move_uploaded_file($_FILES['file']['tmp_name'], $uploadmap . $file_name)) {
					$_POST['formstep'] = '0';
				}
			}
		}
	}

	//get csv field names
	if ($_POST['formstep'] == '1') {
		require_once('functions/csv_functions.php');
		$csv_fields = csv_get_column_names($uploadmap . $file_name);
	}
}

//handle import
elseif ($_POST['formstep'] == '2') {
	//if there is a type
	$qry = "SELECT `id`, `name` FROM `".$db['prefix']."assettype` 
	WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_POST['assettypeid']) . "'
	LIMIT 1";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res) == 1) {
		$assettype = mysqli_fetch_assoc($res); //array(id =>, name =>)
	}
	else {
		//no valid assettype
		//this should not be reachable
		echo 'Assettype not found';
		exit;
	}
	//loop csv
	require_once('functions/csv_functions.php');
	$file_name = $uploadmap . $_POST['file_name'];
	if (!is_file($file_name)) {
		echo 'no valid file';
		exit;
	}
	//open file
    $handle = fopen($file_name, 'rb');
    if ($handle == FALSE) {
        echo 'error file_open';
		exit;
    }
    //get header row
    $line = fgets($handle);
    if ($line == FALSE) {
        echo 'error file_read';
		exit;
    }
    //detect delimiter
    $delimiter = csv_delimiter_from_string($line);
    if ($delimiter == FALSE) {
        echo 'error delimiter';
		exit;
    }

	//get additional fields by asset type
	$qry = "SELECT `".$db['prefix']."addfield`.`id` AS `id`, `".$db['prefix']."addfield`.`name` AS `name`, `".$db['prefix']."addfield`.`fieldclass` AS `fieldclass`, `".$db['prefix']."addfield`.`properties` AS `properties`, `".$db['prefix']."addfield`.`mandatory` AS `mandatory` FROM `".$db['prefix']."addfield` 
	WHERE `assettype` = '" . mysqli_real_escape_string($db['link'], $assettype['id']) . "'
	ORDER BY `sort`";
	$res = mysqli_query($db['link'], $qry);
	$additionalfields = array();
	while ($dat2 = mysqli_fetch_assoc($res)) {
		$additionalfields[] = array('a_'.$dat2['id'], $dat2['name'], $dat2['fieldclass'], $dat2['mandatory'], unserialize($dat2['properties']));
	}
	//generic fields
	$genericfields = $cfg['fields']['generic'];
	//function to check submitted fields for validity
	require_once('functions/assets.php');

	//function to search and replace field contents
	function asset_strreplace($str, $replacestr) {
		if (empty($replacestr)) {
			return $str;
		}
		//convert string to year
		if ($replacestr == 'YEAR') {
			if (is_numeric($str) && ($str >= 1950) && ($str <= date('Y') + 10)) {
				return $str;
			}
			if (date('Y', strtotime($str)) != 1970) {
				return date('Y', strtotime($str));
			}
			else {
				return '';
			}
		}
		//convert degree, minute, second to decimal (example 52° 01' 02.3")
		if ($replacstr == 'DMS') {
			if (preg_match('/(\d+)°\s*(\d+)\'\s*(\d+(\.\d+)?)"/', $str, $matches) === 1) {
				return $matches[1]+((($matches[2]*60)+($matches[3]))/3600);
			}
			else {
				return $str;
			}
		}
		//regular search replace search1=replace1;search2=replace2;search...=replace...;[DEFAULT=defaultvalue]
		$replacestr = explode(';', $replacestr);
		$search = array();
		$replace = array();
		foreach ($replacestr as $fromto) {
			$fromto = explode('=', $fromto);
			$search[] = $fromto[0];
			$replace[] = $fromto[1];
		}
		//perform replace
		$str = str_replace($search, $replace, $str);
		//check default
		$default_key = array_search('DEFAULT', $search);
		if ($default_key !== FALSE) {
			//check if value is not allowed
			if (array_search($str, $replace) === FALSE) {
				//set default value
				$str = $replace[$default_key];
			}
		}
		return $str;
	}
	//set organisation
	$organisation = getuserdata('organisation');
	if (is_numeric($_POST['organisation'])) {
		//check if is allowed
		if (!accesslevelcheck($cfg_accesslevel['beheer_alle']) && ($_POST['organisation'] != getuserdata('organisation'))) {
			//should not be reachable
			echo 'inlezen voor andere organisatie niet toegestaan';
			exit;
		}
		//check if organisation exists
		$qry2 = "SELECT `id` FROM `".$db['prefix']."organisation` 
		WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_POST['organisation']) . "'";
		$res2 = mysqli_query($db['link'], $qry2);
		if (mysqli_num_rows($res2) != 1) {
			//should not be reachable
			echo 'organisatie bestaat niet';
			exit;
		}
		$organisation = $_POST['organisation'];
	}

	//get cache of organisation for wegbeheerder column type
	$organisation_list = array();
	$qry2 = "SELECT `id`, `name` FROM `".$db['prefix']."organisation`";
	$res2 = mysqli_query($db['link'], $qry2);
	while ($data2 = mysqli_fetch_row($res2)) {
		$organisation_list[$data2[0]] = $data2[1];
	}

    //get column names
    $colnames = str_getcsv($line, $delimiter);
	//process each row
	$num_assets = 0;
	$num_assets_failed = 0;
	$code_assets_failed = array();
	$num_assets_updated = 0;
	$num_assets_updated_failed = 0;
	$code_assets_updated_failed = array();
	$num_assets_inserted = 0;
	$num_assets_inserted_failed = 0;
	$code_assets_inserted_failed = array();
    while ($line = fgetcsv($handle, NULL, $delimiter)) {
		$num_assets++;
		//get asset id
		$qry = "SELECT `id`, `assettype` FROM `".$db['prefix']."asset` 
		WHERE `code` = '" . mysqli_real_escape_string($db['link'], $line[$_POST['code']]) . "' 
		AND `assettype` = '" . $assettype['id'] . "'
		AND `organisation` = '" . $organisation . "'
		LIMIT 1";
		$res = mysqli_query($db['link'], $qry);
		if (mysqli_num_rows($res) == 1) {
			$data = mysqli_fetch_assoc($res);
		}
		else {
			$data = array(
				'id' => NULL,
				'assettype' => $assettype['id']
			);
		}
		//check fields and create query
		$fieldcheck = TRUE;
		$genericfields_updateqry = array();
		$additionalfields_updateqry = array();
		//generic fields
		foreach ($genericfields as $field) {
			if ($fieldcheck == TRUE) {
				//for updates, check if field is imported; it must be assigned a csv column and must not be 'code' as that is already checked above
				if (($data['id'] != NULL) && (!is_numeric($_POST[$field[1]]) || (strlen($_POST[$field[1]]) < 1) || ($field[1] == 'code'))) {
					continue;
				}
				//string replace value
				$line[$_POST[$field[1]]] = asset_strreplace($line[$_POST[$field[1]]], $_POST['strreplace_' . $field[1]]);
				//convert RD to WGS84
				if (($field[1] == 'latitude') && ($_POST['strreplace_latitude'] == 'RD') && ($_POST['strreplace_longitude'] == 'RD') && is_numeric($_POST['latitude']) && is_numeric($_POST['longitude'])) {
					include_once('functions/convertRD.php');
					$wgs84 = rd2wgs84($line[$_POST['longitude']],$line[$_POST['latitude']]);
					$line[$_POST['latitude']] = $wgs84[0];
					$line[$_POST['longitude']] = $wgs84[1];
				}

				//wegbeheerder lookup (field type)
				if ($field[2] == 'wegbeheerder') {
					$org_res = array_search($line[$_POST[$field[1]]], $organisation_list);
					if ($org_res !== FALSE) {
						$line[$_POST[$field[1]]] = $org_res;
					}
				}
				//check field validity
				$fieldcheck = check_submitted_field($field[0], $field[1], $field[2], $line[$_POST[$field[1]]], $field[3], $field[4]);
				//build update/insert query
				//set empty string as null
				if (strlen($line[$_POST[$field[1]]]) == 0) {
					$genericfields_updateqry[] =  "`" . $field[1] . "` = NULL";
				}
				else {
					$genericfields_updateqry[] =  "`" . $field[1] . "` = '" . mysqli_real_escape_string($db['link'], $line[$_POST[$field[1]]]) . "'";
				}
			}
			else {
				break;
			}
		}
		//additional fields
		foreach ($additionalfields as $field) {
			if ($fieldcheck == TRUE) {
				//do not check these, as these cannot be imported
				if (($field[2] == 'drip_standaardtekst') || ($field[2] == 'drip_bewegwijzering')) { 
					continue;
				}
				//for updates, check if field is imported; it must be assigned a csv column
				if (($data['id'] != NULL) && (!is_numeric($_POST[$field[1]]) || (strlen($_POST[$field[1]]) < 1))) {
					continue;
				}
				//string replace value
				$line[$_POST[$field[1]]] = asset_strreplace($line[$_POST[$field[1]]], $_POST['strreplace_' . $field[1]]);
				//check field validity
				$fieldcheck = check_submitted_field($field[0], $field[1], $field[2], $line[$_POST[$field[1]]], $field[3], $field[4]);
				//don't add new empty rows
				if (($data['id'] != NULL) || !empty($line[$_POST[$field[1]]])) {
					$additionalfields_updateqry[] = array('addfield' => substr($field[0], 2), 'content' => $line[$_POST[$field[1]]]);
				}
			}
			else {
				break;
			}
		}
		//if checks passed, prepare to update database
		if ($fieldcheck === TRUE) {
			//only update when there is a valid asset
			if ($data['id'] != NULL) {
				//copy old data to history
				$qry = "INSERT INTO `".$db['prefix']."asset_history` (
					`id`,
					`assettype`, ";
				foreach ($genericfields as $field) { //gets list of fields from array
					$qry .= "`" . $field[1] . "`, ";
				}
				$qry .= "`organisation`,
					`user_edit`,
					`date_edit`,
					`user_create`,
					`date_create`
					)
					SELECT
					`id`,
					`assettype`, ";
				foreach ($genericfields as $field) {
					$qry .= "`" . $field[1] . "`, ";
				}
				$qry .= "`organisation`,
					`user_edit`,
					`date_edit`,
					`user_create`,
					`date_create`
					FROM `".$db['prefix']."asset`
					WHERE `id` = '" . $data['id'] . "'";
				$res = mysqli_query($db['link'], $qry);
				//copy additional fields into history
				if ($res === TRUE) {
					$qry = "INSERT INTO `".$db['prefix']."addfieldcontent_history` (
						`id`,
						`asset`,
						`addfield`,
						`content`,
						`user_edit`,
						`date_edit`,
						`user_create`,
						`date_create`
						)
						SELECT
						`id`,
						`asset`,
						`addfield`,
						`content`,
						`user_edit`,
						`date_edit`,
						`user_create`,
						`date_create`
						FROM `".$db['prefix']."addfieldcontent`
						WHERE `asset` = '" . $data['id'] . "'";
					$res = mysqli_query($db['link'], $qry);
				}
				//update generic fields
				if ($res === TRUE) {
					$qry = "UPDATE `".$db['prefix']."asset` SET ";
					if (!empty($genericfields_updateqry)) {
						$qry .= join(', ', $genericfields_updateqry);
						$qry .= ", ";
					}
					$qry .= "`date_edit` = NOW(),
					`user_edit` = '" . getuserdata('id') . "'
					WHERE `id` = '" . $data['id'] . "'";
					$res = mysqli_query($db['link'], $qry);
				}
				//update additional fields
				if ($res === TRUE) {
					foreach ($additionalfields_updateqry as $field) {
						//remove if empty
						//TODO: bepalen of verwijderen wenselijk is, of liever een lege cel bewaren omdat er ooit iets in heeft gestaan
						//update if value
						if (strlen($field['content']) == 0) {
							//set empty string as null
							$content = 'NULL';
						}
						else {
							$content = "'".mysqli_real_escape_string($db['link'], $field['content'])."'";
						}
						$qry = "INSERT INTO `".$db['prefix']."addfieldcontent` SET 
						`content` = " . $content . ", 
						`date_create` = NOW(),
						`user_create` = '" . getuserdata('id') . "',
						`date_edit` = NOW(),
						`user_edit` = '" . getuserdata('id') . "',
						`asset` = '" . $data['id'] . "',
						`addfield` = '" . mysqli_real_escape_string($db['link'], $field['addfield']) . "'
						ON DUPLICATE KEY UPDATE 
						`content` = " . $content . ", 
						`date_edit` = NOW(),
						`user_edit` = '" . getuserdata('id') . "'";
						$res = mysqli_query($db['link'], $qry);
					}
					
				}
				if ($res === TRUE) {
					$num_assets_updated++;
				}
				else {
					$num_assets_updated_failed++;
					$code_assets_updated_failed[] = $line[$_POST['code']];
					var_dump($line[$_POST['code']]);
					echo 'here';
				}
			}
			//insert new
			elseif ($_POST['newassets'] == 'true') {
				//generic fields
				$qry = "INSERT INTO `".$db['prefix']."asset` SET 
				`assettype` = '" . $data['assettype'] . "', ";
				$qry .= join(', ', $genericfields_updateqry);
				$qry .= ", `organisation` = '" . $organisation . "',
				`date_edit` = NOW(),
				`user_edit` = '" . getuserdata('id') . "', 
				`date_create` = NOW(),
				`user_create` = '" . getuserdata('id') . "'";
				$res = mysqli_query($db['link'], $qry);
				$data['id'] = mysqli_insert_id($db['link']);

				//additional fields
				if (($res === TRUE) && is_numeric($data['id'])) {
					foreach ($additionalfields_updateqry as $field) {
						//insert if value
						if (!empty($field['content'])) {
							$qry = "INSERT INTO `".$db['prefix']."addfieldcontent` SET 
							`asset` = '" . $data['id'] . "',
							`addfield` = '" . mysqli_real_escape_string($db['link'], $field['addfield']) . "',
							`content` = '" . mysqli_real_escape_string($db['link'], $field['content']) . "',
							`date_edit` = NOW(),
							`user_edit` = '" . getuserdata('id') . "',
							`date_create` = NOW(),
							`user_create` = '" . getuserdata('id') . "'";
							$res = mysqli_query($db['link'], $qry);
						}
					}
				}
				else {
					$res = FALSE;
				}
				if ($res === TRUE) {
					$num_assets_inserted++;
				}
				else {
					$num_assets_inserted_failed++;
					$code_assets_inserted_failed[] = $line[$_POST['code']];
				}
			}
		}
		else {
			$num_assets_failed++;
			$code_assets_failed[] = $line[$_POST['code']];
		}
	}
	//unlink file
	unlink($file_name);
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Assets - Importeren</title>
<link rel="stylesheet" type="text/css" href="bundled/jquery-ui/jquery-ui.min.css">
<link rel="stylesheet" type="text/css" href="style/style.css">
<link rel="icon" type="image/png" href="favicon.png">
<script src="bundled/jquery/jquery.min.js"></script>
<script src="bundled/jquery-ui/jquery-ui.min.js"></script>
<script src="bundled/js-cookie/js.cookie.min.js"></script>
<script src="help.js"></script>
<script src="import.js"></script>
</head>
<body>

<?php
include('menu.inc.php');
?>

<div id="content">
	<?php
	//step 0
	if (($_POST['formstep'] != '1') && ($_POST['formstep'] != '2')) {
		?>
		<h1>Assets importeren uit CSV-bestand - stap 1</h1>
		<p>Via deze wizard kunnen assets uit een CSV-bestand worden ge&iuml;mporteerd. De eerste regel van het CSV-bestand moet veldnamen bevatten. Er mag maar &eacute;&eacute;n type asset in het bestand aanwezig zijn.</p>
		<form method="post" enctype="multipart/form-data">
		<input type="hidden" name="formstep" value="1">
		<fieldset>
			<legend>Assets importeren</legend>
			<p>
			<label for="assettypeid">Type asset: </label>
			<select name="assettypeid" id="assettypeid">
			<option value="" disabled selected>(kies type)</option>
				<?php
				$qry = "SELECT `id`, `name` FROM `".$db['prefix']."assettype` ORDER BY `name`";
				$res = mysqli_query($db['link'], $qry);
				while ($data = mysqli_fetch_assoc($res)) {
					echo '<option value="' . $data['id'] . '">' . htmlspecialchars($data['name']) . '</option>';
				}
				?>
			</select>
			</p><p>
			<label for="file">Selecteer bestand: </label><input type="file" name="file" id="file" accept=".csv,text/csv">
			</p><p>
			<input type="submit" value="Volgende">
			</p>
		</fieldset>
		</form>
		<?php
	}
	//step 1
	elseif ($_POST['formstep'] == '1') {
		?>
		<h1>Assets importeren uit CSV-bestand - stap 2</h1>
		<form method="post">
		<input type="hidden" name="formstep" value="2">
		<input type="hidden" name="assettypeid" value="<?php echo $assettype['id']; ?>">
		<input type="hidden" name="file_name" value="<?php echo $file_name; ?>">
		<fieldset>
		<legend>Velden koppelen</legend>
		<p>Selecteer hieronder welk veld uit het CSV-bestand moet worden ingelezen in welk assetveld. Het is mogelijk om assetvelden niet in te lezen. De bestaande waarde blijft dan behouden. Het veld <i>code</i> geldt als sleutelveld: wanneer een code uit het CSV-bestand overeen komt met een bestaande asset, dan wordt deze bijgewerkt.</p>

		<?php
		function generate_field_select($field, $mandatory = false) {
			global $csv_fields;
			$html = '<select name="' . $field . '" id="select_' . $field . '">';
			$html .= '<option value=""' . ($mandatory ? ' disabled selected' : '') . '>(overslaan)</option>';
			foreach ($csv_fields as $i => $csv_field) {
				if (!empty($csv_field)) {
					$html .= '<option value="' . $i . '"' . ((strtolower($field) == strtolower($csv_field)) ? ' selected' : '') . '>' . htmlspecialchars($csv_field) . '</option>';
				}
			}
			$html .= '</select>';
			return $html;
		}

		echo '<table>';
		echo '<tr><th>Assetveld</th><th>veld uit CSV</th><th>tekenreeksvervanging</th></tr>';
		//generic fields
		foreach ($cfg['fields']['generic'] as $field) {
			echo '<tr><th>' . $field[1] . '</th><td>' . generate_field_select($field[1], (($field[1] == 'code') ? true : false)) . '</td><td>'; 
			if ($field[1] != 'code') {
				echo '<input type="text" name="strreplace_' . $field[1] . '"';
				if (($field[1] == 'bouwjaar') || ($field[1] == 'oorspronkelijk_bouwjaar')) {
					echo ' value="YEAR"';
				}
				else if ($field[1] == 'status') {
					echo ' value="bestaand=1;realisatie=2;buiten gebruik=3;verwijderd=4"';
				}
				else if ($field[1] == 'heading') {
					echo ' value="DEFAULT=0"';
				}
				echo ">";
			}
			echo '</td></tr>';
		}
		echo '<tr><th></th><th></th><th></th></tr>';
		//aanvullend per assettype
		$qr2 = "SELECT `name`, `fieldclass` FROM `".$db['prefix']."addfield`
		WHERE `".$db['prefix']."addfield`.`assettype` = '" . mysqli_real_escape_string($db['link'], $_POST['assettypeid']) . "'
		ORDER BY `".$db['prefix']."addfield`.`sort`";
		$re2 = mysqli_query($db['link'], $qr2);
		while ($dat2 = mysqli_fetch_assoc($re2)) {
			if (($dat2['fieldclass'] != 'drip_standaardtekst') && ($dat2['fieldclass'] != 'drip_bewegwijzering')) {
				echo '<tr><th>' . $dat2['name'] . '</th><td>' . generate_field_select($dat2['name']) . '</td><td>';
				echo '<input type="text" name="strreplace_' . $dat2['name'] . '"';
				if ($dat2['name'] == 'iVRI') {
					echo ' value="ja=1;iVRI ready=1;iVRI=1;DEFAULT=0"';
				}
				if ($dat2['name'] == 'PTZ') {
					echo ' value="ja=1;DEFAULT=0"';
				}
				echo ">";
				echo '</td></tr>';
			}
		}

		echo '</table>';
		echo '</fieldset>';
		?>
		<fieldset>
			<legend>Inlezen voor organisatie</legend>
			<?php
			echo '<label for="organisation">Organisatie:</label>';
			echo '<select name="organisation"' . (accesslevelcheck($cfg_accesslevel['beheer_alle']) ? '' : ' disabled') . ' id="organisation">';
			$qry2 = "SELECT `id`, `name` FROM `".$db['prefix']."organisation` 
			ORDER BY `name`";
			$res2 = mysqli_query($db['link'], $qry2);
			while ($dat2 = mysqli_fetch_assoc($res2)) {
				echo '<option value="'.$dat2['id'].'"';
				if (getuserdata('organisation') == $dat2['id']) echo ' selected="selected"';
				echo '>'.$dat2['name'].'</option>';	
			}
			echo '</select>';
			?>
		</fieldset>
		<fieldset>
			<legend>Onbekende code afhandelen</legend>
			<p>Wat moet er gebeuren als een code uit het CSV-bestand niet overeen komt met de code van een bestaande asset? Let op: assets worden ingelezen voor bovenvermelde organisatie, het is dus niet mogelijk om assets bij te werken die door een andere wegbeheerder worden beheerd. Wanneer dit toch wordt geprobeerd, dan zal dit resulteren in dubbele assets die niet meer zelfstandig verwijderd kunnen worden!</p>
			<input type="radio" name="newassets" id="newassets_0" value="false" checked><label for="newassets_0">Alleen bestaande assets bijwerken</label><br>
			<input type="radio" name="newassets" id="newassets_1" value="true"><label for="newassets_1">Bestaande assets bijwerken en nieuwe assets aanmaken</label>
		</fieldset>
		<p>
		<input type="submit" value="Start import">
		</p>
		</form>

		<?php
	}
	//result
	elseif ($_POST['formstep'] == '2') {
		?>
		<h1>Assets importeren uit CSV-bestand - voltooid</h1>
		<p>Het importeren is voltooid.<br>
		<?php echo $num_assets; ?> rijen in CSV<br>
		<?php echo $num_assets_failed; ?> ongeldige rijen<br>
		<?php if (count($code_assets_failed) > 0 ) {
			echo htmlspecialchars(join(', ', $code_assets_failed)); 
			echo '<br>';
		}
		?>
		<?php echo $num_assets_updated; ?> assets bijgewerkt<br>
		<?php echo $num_assets_updated_failed; ?> assets bijwerken mislukt<br>
		<?php if (count($code_assets_updated_failed) > 0 ) {
			echo htmlspecialchars(join(', ', $code_assets_updated_failed)); 
			echo '<br>';
		}
		?>
		<?php echo $num_assets_inserted; ?> nieuwe assets toevoegoegd<br>
		<?php echo $num_assets_inserted_failed; ?> nieuwe assets mislukt<br>
		<?php if (count($code_assets_inserted_failed) > 0 ) {
			echo htmlspecialchars(join(', ', $code_assets_inserted_failed)); 
			echo '<br>';
		}
		?>
		</p>
		<?php
	}
	?>

</div>
</body>
</html>
