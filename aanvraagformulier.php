<?php 
/*
 	assetwebsite - viewer en aanvraagformulier voor verkeersmanagementassets
    Copyright (C) 2020,2022 Gemeente Den Haag, Netherlands
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

//provide json table content
if ($_GET['data'] == 'json') {
    //build asset table
    //TODO: er wordt verondersteld dat DRIP asset type id 1 heeft
	$json = array();
	$qry = "SELECT `t_aansturing`.`name` AS `aansturing`, `".$db['prefix']."asset`.`id` as `assetid`, `code`, `status`
	FROM `".$db['prefix']."asset`
	LEFT JOIN `".$db['prefix']."organisation` AS `t_aansturing`
	ON `".$db['prefix']."asset`.`aansturing` = `t_aansturing`.`id`
    WHERE `assettype` = 1
    AND `status` IN (1,2,3)
    ORDER BY `aansturing`, `code`";
	//voer query uit
	$res = mysqli_query($db['link'], $qry);
	//als er een of meer rijen zijn
	if (mysqli_num_rows($res) > 0) {
		//lus om alle rijen weer te geven
		while ($data = mysqli_fetch_assoc($res)) {
			//status
			switch($data['status']) {
				case 1: $data['status'] = 'bestaand'; break;
				case 2: $data['status'] = 'realisatie'; break;
				case 3: $data['status'] = 'buiten gebruik'; break;
				case 4: $data['status'] = 'verwijderd'; break;
				default: break;
			}
			$json[] = array(
				'assetid' => htmlspecialchars($data['assetid']),
				'code' => htmlspecialchars($data['code']),
				'status' => htmlspecialchars($data['status']),
                'aansturing' => htmlspecialchars($data['aansturing'])
			);
		}

		header('Content-Type: application/json');
		echo json_encode($json);
	}
	exit;
}
//provide json table content
elseif ($_GET['data'] == 'open-list') {
    //build asset table
    //TODO: er wordt verondersteld dat DRIP asset type id 1 heeft
	$json = array();
	$qry = "SELECT `id`, `name`, `date_edit`
	FROM `".$db['prefix']."requestforms`
	WHERE `user_create` = " . getuserdata('id') . "
	ORDER BY `date_edit` DESC, `name`";
	//voer query uit
	$res = mysqli_query($db['link'], $qry);
	//als er een of meer rijen zijn
	if (mysqli_num_rows($res) > 0) {
		//lus om alle rijen weer te geven
		while ($data = mysqli_fetch_assoc($res)) {
			$json[] = array(
				'id' => htmlspecialchars($data['id']),
				'name' => htmlspecialchars($data['name']),
				'date_edit' => htmlspecialchars($data['date_edit'])
			);
		}

		header('Content-Type: application/json');
		echo json_encode($json);
	}
	exit;
}
//save selection
elseif ($_GET['do'] == 'save') {
	//check login
	if ($login !== TRUE) {
		exit;
	}

	$json = false;
	if (is_numeric($_GET['id'])) {
		$requestform_id = (int) $_GET['id'];
	}
	else {
		$requestform_id = 0;
	}
	//check if there is a save with this id
	$qry = "SELECT `id`
	FROM `".$db['prefix']."requestforms`
	WHERE `id` = '" . $requestform_id . "'
	AND `user_create` = " . getuserdata('id');
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		//update existing
		$qry = "UPDATE `".$db['prefix']."requestforms`
		SET `name` =  '" . mysqli_real_escape_string($db['link'], $_GET['name']) . "'
		WHERE `id` = '" . $requestform_id . "'";
		$res = mysqli_query($db['link'], $qry);
		if ($res == FALSE) {
			//check provided ids
			$requestform_id = 0;
		}
	}
	if ($requestform_id == 0) {
		//create new
		$qry = "INSERT INTO `".$db['prefix']."requestforms`
		SET `name` =  '" . mysqli_real_escape_string($db['link'], $_GET['name']) . "',
		`organisation` = '" . getuserdata('organisation') . "',
		`user_edit` = '" . getuserdata('id') . "',
		`user_create` = '" . getuserdata('id') . "'";
		mysqli_query($db['link'], $qry);
		$requestform_id = mysqli_insert_id($db['link']);
	}

	//insert ids
	if ($requestform_id > 0) {
		$assets = json_decode($_GET['assets'], TRUE);
		$assets_checked = array();
		if (is_array($assets)) {
			foreach ($assets as $asset) {
				if (is_numeric($asset) && !in_array($asset, $assets_checked)) {
					$assets_checked[] = $asset;
				}
			}
			if (count($assets_checked) > 0) {
				//insert into database
				$qry = "DELETE FROM `".$db['prefix']."requestformassets`
				WHERE `requestform` = '" . $requestform_id . "'";
				mysqli_query($db['link'], $qry);
				foreach ($assets_checked as $asset) {
					$qry = "INSERT INTO `".$db['prefix']."requestformassets`
					SET `requestform` = '" . $requestform_id . "',
					`asset` = '" . $asset . "',
					`user_edit` = '" . getuserdata('id') . "',
					`user_create` = '" . getuserdata('id') . "'";
					mysqli_query($db['link'], $qry);
				}
			}
		}
	}
	//return save id
	header('Content-Type: application/json');
	echo json_encode(array('id' => $requestform_id));
	exit;
}
//save selection
elseif ($_GET['do'] == 'load') {
	//check login
	if ($login !== TRUE) {
		exit;
	}
	
	$json = array('id' => 0, 'name' => 'Nieuw Aanvraagformulier', 'assetids' => array());

	if (is_numeric($_GET['id'])) {
		$requestform_id = (int) $_GET['id'];
	}
	else {
		$requestform_id = 0;
	}
	//check if there is a save with this id
	$qry = "SELECT `id`, `name`
	FROM `".$db['prefix']."requestforms`
	WHERE `id` = '" . $requestform_id . "'
	AND `user_create` = " . getuserdata('id');
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$data = mysqli_fetch_assoc($res);
		$json['id'] = (int) $data['id'];
		$json['name'] = htmlspecialchars($data['name']);
		//get asset ids
		$qry = "SELECT `asset` 
		FROM `".$db['prefix']."requestformassets`
		WHERE `requestform` = '" . $requestform_id . "'";
		$res = mysqli_query($db['link'], $qry);
		if (mysqli_num_rows($res)) {
			while ($data = mysqli_fetch_assoc($res)) {
				$json['assetids'][] = (int) $data['asset'];
			}
		}
	}
	//return json
	header('Content-Type: application/json');
	echo json_encode($json);
	exit;
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Assets - DRIP-Aanvraagformulier</title>
<link rel="stylesheet" type="text/css" href="bundled/jquery-ui/jquery-ui.min.css">
<link href="bundled/tabulator/css/tabulator_simple.min.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="bundled/leaflet/leaflet.css">
<link rel="stylesheet" type="text/css" href="style/style.css">
<link rel="icon" type="image/png" href="favicon.png">
<script src="bundled/jquery/jquery.min.js"></script>
<script src="bundled/jquery-ui/jquery-ui.min.js"></script>
<script src="bundled/js-cookie/js.cookie.min.js"></script>
<script src="bundled/tabulator/js/tabulator.min.js"></script>
<script src="bundled/leaflet/leaflet.js"></script>
<script src="bundled/leaflet-rotatedmarker/leaflet.rotatedMarker.js"></script>
<script src="help.js"></script>
<script src="aanvraagformulier.js"></script>
</head>
<body>

<div id="content" style="height:100vh; width: calc(33% - 40px); min-width: 300px; margin:0; padding: 0 20px; float:left;">
    <img style="float:left;" src="style/logo.png" width="195" height="101" alt="Logo BEREIK!">
    <h1 style="float:left; padding-top: 20px">Aanvraagformulier DRIP-teksten</h1>
    <p class="warning" style="clear:both;">Dit aanvraagformulier is in gebruik bij wegbeheerders die verenigd zijn in het samenwerkingsverband <a href="https://www.zuidhollandbereikbaar.nl/" target="_blank">Zuid-Holland Bereikbaar</a>. Voor andere regio's kunnen andere procedures van toepassing zijn.</p>
	<fieldset>
		Aantal geselecteerd: <span id="requestform-count">0</span> <input type="button" id="aanvraagformulier-download" value="Aanvraagformulier downloaden">
		<?php
		if ($login === TRUE) {
			?>
			<br><br>
			<input type="button" id="requestform-new" value="Nieuw"> <input type="button" id="requestform-open" value="Openen"> <input type="button" id="requestform-save" value="Opslaan"> 
			<?php
		}
		?>
	</fieldset>
    <div style="float:left; clear:both;"><p><input type="search" id="table-filter" placeholder="filtertekst"></p></div>
    <div id="assettable" style="height: calc(100vh - 440px); width:100%; clear: both;"></div>
</div>
<div id="map" style="height:100vh; width: 67%; max-width: calc(100% - 340px); float:left; z-index: 0"></div>

<?php
include('menu.inc.php');
?>

<div id="requestform-save-gui" style="display: none;">
	<label for="requestform-name">Naam:</label> <input type="text" value="Nieuw Aanvraagformulier" name="requestform-name" id="requestform-name">
</div>

<div id="requestform-open-gui" style="display: none;">
	<div id="requestform-saved-list" style="height: 300px; width:400px; clear: both;"></div>
</div>

<div id="requestform-new-gui" style="display: none;">
	<p>Eventuele niet-opgeslagen wijzigingen zullen verloren gaan.</p>
</div>

</body>
</html>
