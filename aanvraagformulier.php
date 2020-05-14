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
    <p class="warning" style="clear:both;">Dit aanvraagformulier is in gebruik bij wegbeheerders in Zuid-Holland die verenigd zijn in het samenwerkingsplatform <a href="https://www.bereiknu.nl/" target="_blank">BEREIK!</a>. Voor andere regio's kunnen andere procedures van toepassing zijn.</p>
    <div style="float:left; clear:both;"><p><input type="search" id="table-filter" placeholder="filtertekst"></p></div>
    <div id="assettable" style="height: calc(100vh - 300px); width:100%; clear: both;"></div>
    <p><input type="button" id="aanvraagformulier-download" value="Aanvraagformulier downloaden"></p>
</div>
<div id="map" style="height:100vh; width: 67%; max-width: calc(100% - 340px); float:left; z-index: 0"></div>

<?php
include('menu.inc.php');
?>

</body>
</html>
