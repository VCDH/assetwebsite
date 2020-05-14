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

//provide json and csv table content, used for tabulator input and download option
if (($_GET['data'] == 'json') || ($_GET['download'] == 'json') || ($_GET['download'] == 'csv')) {
	//build asset table
	$json = array();

	$qry = "SELECT `t_assettype`.`id` AS `assettypeid`, `t_assettype`.`name` AS `assettypename`, `t_aansturing`.`name` AS `aansturing`, `t_wegbeheerder`.`name` AS `wegbeheerder`, `t_onderhoud`.`name` AS `onderhoud`, `t_voeding`.`name` AS `voeding`, `t_verbinding`.`name` AS `verbinding`, `".$db['prefix']."asset`.`id` AS `assetid`, `code`, `naam`, `latitude`, `longitude`, `heading`, `status`, `leverancier`, `bouwjaar`, `oorspronkelijk_bouwjaar`, `memo`
	FROM `".$db['prefix']."asset`
	LEFT JOIN `".$db['prefix']."assettype` AS `t_assettype`
	ON `".$db['prefix']."asset`.`assettype` = `t_assettype`.`id`
	LEFT JOIN `".$db['prefix']."organisation` AS `t_aansturing`
	ON `".$db['prefix']."asset`.`aansturing` = `t_aansturing`.`id`
	LEFT JOIN `".$db['prefix']."organisation` AS `t_wegbeheerder`
	ON `".$db['prefix']."asset`.`wegbeheerder` = `t_wegbeheerder`.`id`
	LEFT JOIN `".$db['prefix']."organisation` AS `t_onderhoud`
	ON `".$db['prefix']."asset`.`onderhoud` = `t_onderhoud`.`id`
	LEFT JOIN `".$db['prefix']."organisation` AS `t_voeding`
	ON `".$db['prefix']."asset`.`voeding` = `t_voeding`.`id`
	LEFT JOIN `".$db['prefix']."organisation` AS `t_verbinding`
	ON `".$db['prefix']."asset`.`verbinding` = `t_verbinding`.`id`";
	if ($login !== TRUE) {
		$qry .= " WHERE `t_assettype`.`public` = 1";
	}
	$qry .= " ORDER BY `assettypename`, `aansturing`, `code`";
	//voer query uit
	$res = mysqli_query($db['link'], $qry);
	//als er een of meer rijen zijn
	if (mysqli_num_rows($res) > 0) {
		$headerrow = FALSE;
		$additional_fields = array();
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
			$json_this = array(
				'assetid' => (int) $data['assetid'],
				'assettypename' => htmlspecialchars($data['assettypename']),
				'code' => htmlspecialchars($data['code']),
				'naam' => htmlspecialchars($data['naam']),
				'status' => htmlspecialchars($data['status']),
				'aansturing' => htmlspecialchars($data['aansturing']),
				'wegbeheerder' => htmlspecialchars($data['wegbeheerder']),
				'onderhoud' => htmlspecialchars($data['onderhoud']),
				'voeding' => htmlspecialchars($data['voeding']),
				'verbinding' => htmlspecialchars($data['verbinding']),
				'leverancier' => htmlspecialchars($data['leverancier']),
				'bouwjaar' => (int) $data['bouwjaar'],
				'oorspronkelijk_bouwjaar' => (int) $data['oorspronkelijk_bouwjaar']
			);
			//provide extra fields for download
			if (($_GET['download'] == 'json') || ($_GET['download'] == 'csv')) {
				$json_this = array_merge($json_this, 
					array(
						'latitude' => (float) $data['latitude'],
						'longitude' => (float) $data['longitude'],
						'heading' => (int) $data['heading'],
						'memo' => htmlspecialchars($data['memo'])
					)
				);
				if ($headerrow === FALSE) {
					$headerrow = array_keys($json_this);
				}
				//aanvullend per assettype
				$qr2 = "SELECT `name`, `fieldclass`, `content` FROM `".$db['prefix']."addfield`
				LEFT JOIN 
				(SELECT * FROM `".$db['prefix']."addfieldcontent` 
				WHERE `".$db['prefix']."addfieldcontent`.`asset` = '" . mysqli_real_escape_string($db['link'], $data['assetid']) . "')
				AS `t_addfieldcontent`
				ON `t_addfieldcontent`.`addfield` = `".$db['prefix']."addfield`.`id`
				WHERE `".$db['prefix']."addfield`.`assettype` = '" . mysqli_real_escape_string($db['link'], $data['assettypeid']) . "'
				ORDER BY `".$db['prefix']."addfield`.`sort`";
				$re2 = mysqli_query($db['link'], $qr2);
				while ($dat2 = mysqli_fetch_assoc($re2)) {
					if (!in_array($dat2['name'], $additional_fields)) {
						$additional_fields[] = $dat2['name'];
					}
					$json_this[$dat2['name']] = htmlspecialchars($dat2['content']);
				}
			}
			$json[] = $json_this;
		}
		//download headers
		if (($_GET['download'] == 'json') || ($_GET['download'] == 'csv')) {
			header('Content-Description: File Transfer');
			header('Content-Disposition: attachment; filename=assets_'.date('YmdHi').'.' . $_GET['download']);
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			//header('Content-Length: ' . filesize($temp_file));
		}
		//csv conversion
		if ($_GET['download'] == 'csv') {
			header('Content-Type: text/csv');
			$temp_file = tempnam('/tmp', 'asst');
			$temp_handle = fopen($temp_file, 'wb+');
			//header row
			$headerrow = array_merge($headerrow, $additional_fields);
			fputcsv($temp_handle, $headerrow, ';', '"', '\\');
			//content rows
			foreach ($json as $json_this) {
				$csv_this = array_map(function($key) { global $json_this; return $json_this[$key]; }, $headerrow);
				fputcsv($temp_handle, $csv_this, ';', '"', '\\');
			}
			fclose($temp_handle);
			header('Content-Length: ' . filesize($temp_file));
			readfile($temp_file);
			unlink($temp_file); // deletes the temporary file
		}
		//json output
		else {
			header('Content-Type: application/json');
			echo json_encode($json);
		}
	}
	exit;
}
elseif ($_GET['data'] == 'details') {
	$json = array('html' => '', 'title' => '');
	//query om inhoud van tabel te selecteren
	$qry = "SELECT `t_assettype`.`id` AS `assettypeid`, `t_assettype`.`name` AS `assettypename`, `t_aansturing`.`name` AS `aansturing`, `t_wegbeheerder`.`name` AS `wegbeheerder`, `t_onderhoud`.`name` AS `onderhoud`, `t_voeding`.`name` AS `voeding`, `t_verbinding`.`name` AS `verbinding`, `".$db['prefix']."asset`.`id` AS `assetid`, `code`, `naam`, `latitude`, `longitude`, `heading`, `status`, `leverancier`, `bouwjaar`, `oorspronkelijk_bouwjaar`, `memo`, `organisation`, `".$db['prefix']."asset`.`aansturing` AS `aansturingid`
	FROM `".$db['prefix']."asset`
	LEFT JOIN `".$db['prefix']."assettype` AS `t_assettype`
	ON `".$db['prefix']."asset`.`assettype` = `t_assettype`.`id`
	LEFT JOIN `".$db['prefix']."organisation` AS `t_aansturing`
	ON `".$db['prefix']."asset`.`aansturing` = `t_aansturing`.`id`
	LEFT JOIN `".$db['prefix']."organisation` AS `t_wegbeheerder`
	ON `".$db['prefix']."asset`.`wegbeheerder` = `t_wegbeheerder`.`id`
	LEFT JOIN `".$db['prefix']."organisation` AS `t_onderhoud`
	ON `".$db['prefix']."asset`.`onderhoud` = `t_onderhoud`.`id`
	LEFT JOIN `".$db['prefix']."organisation` AS `t_voeding`
	ON `".$db['prefix']."asset`.`voeding` = `t_voeding`.`id`
	LEFT JOIN `".$db['prefix']."organisation` AS `t_verbinding`
	ON `".$db['prefix']."asset`.`verbinding` = `t_verbinding`.`id`
	WHERE `".$db['prefix']."asset`.`id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'";
	if ($login !== TRUE) {
		$qry .= " AND `t_assettype`.`public` = 1";
	}
	$qry .= " LIMIT 1";
	//voer query uit
	$res = mysqli_query($db['link'], $qry);
	//als er een of meer rijen zijn
	if (mysqli_num_rows($res) > 0) {
		$html = '';
		$data = mysqli_fetch_assoc($res);
		//status
		switch($data['status']) {
			case 1: $data['status'] = 'bestaand'; break;
			case 2: $data['status'] = 'realisatie'; break;
			case 3: $data['status'] = 'buiten gebruik'; break;
			case 4: $data['status'] = 'verwijderd'; break;
			default: break;
		}
		//tabel
		$html .= '<div style="float:left; margin-left: 8px;">';
		$html .= '<table>';
		$html .= '<tr><th></th><th></th></tr>';
		$html .= '<tr><th>code</th><td>' . htmlspecialchars($data['code']) . '</td></tr>';
		$html .= '<tr><th>naam</th><td>' . htmlspecialchars($data['naam']) . '</td></tr>';
		$html .= '<tr><th>status</th><td>' . htmlspecialchars($data['status']) . '</td></tr>';
		$html .= '<tr><th>aansturing</th><td>' . htmlspecialchars($data['aansturing']) . '</td></tr>';
		$html .= '<tr><th>wegbeheerder</th><td>' . htmlspecialchars($data['wegbeheerder']) . '</td></tr>';
		$html .= '<tr><th>onderhoud</th><td>' . htmlspecialchars($data['onderhoud']) . '</td></tr>';
		$html .= '<tr><th>voeding</th><td>' . htmlspecialchars($data['voeding']) . '</td></tr>';
		$html .= '<tr><th>verbinding</th><td>' . htmlspecialchars($data['verbinding']) . '</td></tr>';
		$html .= '<tr><th>leverancier</th><td>' . htmlspecialchars($data['leverancier']) . '</td></tr>';
		$html .= '<tr><th>bouwjaar</th><td>' . htmlspecialchars($data['bouwjaar']) . '</td></tr>';
		$html .= '<tr><th>oorspronkelijk_bouwjaar</th><td>' . htmlspecialchars($data['oorspronkelijk_bouwjaar']) . '</td></tr>';
		$html .= '<tr><th></th><th></th></tr>';
		//aanvullend per assettype
		$qr2 = "SELECT `name`, `fieldclass`, `content` FROM `".$db['prefix']."addfield`
		LEFT JOIN 
		(SELECT * FROM `".$db['prefix']."addfieldcontent` 
		WHERE `".$db['prefix']."addfieldcontent`.`asset` = '" . mysqli_real_escape_string($db['link'], $data['assetid']) . "')
		AS `t_addfieldcontent`
		ON `t_addfieldcontent`.`addfield` = `".$db['prefix']."addfield`.`id`
		WHERE `".$db['prefix']."addfield`.`assettype` = '" . mysqli_real_escape_string($db['link'], $data['assettypeid']) . "'
		ORDER BY `".$db['prefix']."addfield`.`sort`";
		$re2 = mysqli_query($db['link'], $qr2);
		while ($dat2 = mysqli_fetch_assoc($re2)) {
			$html .= '<tr><th>' . htmlspecialchars($dat2['name']) . '</th><td>';
			if ($dat2['fieldclass'] == 'bool') {
				$html .= ($dat2['content'] == 1) ? 'ja' : 'nee';
			}
			elseif (($dat2['fieldclass'] == 'drip_standaardtekst') || ($dat2['fieldclass'] == 'drip_bewegwijzering')) {
				if (!empty($dat2['content'])) {
					$fielddata = unserialize($dat2['content']);
					if (!empty($fielddata['image'])) {
						if (is_file('store/'.substr($fielddata['image'],0,1).'/'.$fielddata['image'])) {
							$html .= '<img src="store/'.substr($fielddata['image'],0,1).'/'.$fielddata['image'].'"><br>';
						}
						else {
							$html .= '(kan afbeelding niet vinden)<br>';
						}
					}
					$html .= nl2br(htmlspecialchars($fielddata['text']));
				}
			}
			else { //default text
				$html .= htmlspecialchars($dat2['content']);
			}
			$html .= '</td></tr>';
		}

		//lat/lon/heading
		$html .= '<tr><th></th><th></th></tr>';
		$html .= '<tr><th>latitude</th><td>' . htmlspecialchars($data['latitude']) . '</td></tr>';
		$html .= '<tr><th>longitude</th><td>' . htmlspecialchars($data['longitude']) . '</td></tr>';
		$html .= '<tr><th>heading</th><td>' . htmlspecialchars($data['heading']) . ' graden</td></tr>';
		$html .= '</table>';
		if (!empty($data['memo'])) {
			$html .= '<p><b>Memo:</b> ' . htmlspecialchars($data['memo']) . '</p>';
		}

		$html .= '<input type="hidden" id="assettype" value="' . htmlspecialchars($data['assettypeid']) . '">';
		$html .= '<input type="hidden" id="aansturing" value="' . htmlspecialchars($data['aansturingid']) . '">';
		$html .= '<input type="hidden" id="latitude" value="' . htmlspecialchars($data['latitude']) . '">';
		$html .= '<input type="hidden" id="longitude" value="' . htmlspecialchars($data['longitude']) . '">';
		$html .= '<input type="hidden" id="heading" value="' . htmlspecialchars($data['heading']) . '">';

		$html .= '<p><a href="index.php?id='.$data['assetid'].'">Centreer locatie op kaart</a></p>';

		if (getuserdata() && (accesslevelcheck('beheer_eigen', $data['organisation']) || accesslevelcheck('beheer_alle'))) {
			$html .= '<p><a href="edit.php?id='.$data['assetid'].'">Bewerken</a></p>';
			$html .= '<p><a href="historie.php?id='.$data['assetid'].'">Historie</a></p>';
		}
		$html .= '</div>';

		$html .= '<div style="float:left; margin-left: 8px;">';
		$html .= '	<div id="minimap" style="width: 400px; height: 400px;"></div>';
		$html .= '</div>';
		$html .= '<div style="clear:both;"></div>';

		$json['html'] = $html;
		$json['title'] = htmlspecialchars($data['assettypename'] . ' ' . $data['code']);
	}
	else {
		$json['html'] = '<p class="error">Geen detailinformatie gevonden</p>';
		$json['title'] = 'Geen gegevens beschikbaar';
	}
	header('Content-Type: application/json');
	echo json_encode($json);
	exit;
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Assets - Tabel</title>
<link rel="stylesheet" type="text/css" href="bundled/jquery-ui/jquery-ui.min.css">
<link href="bundled/tabulator/css/tabulator_simple.min.css" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="bundled/leaflet/leaflet.css">
<link rel="stylesheet" type="text/css" href="style/style.css">
<link rel="icon" type="image/png" href="favicon.png">
<script type="text/javascript" src="bundled/jquery/jquery.min.js"></script>
<script type="text/javascript" src="bundled/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="bundled/tabulator/js/tabulator.min.js"></script>
<script type="text/javascript" src="bundled/leaflet/leaflet.js"></script>
<script type="text/javascript" src="bundled/leaflet-rotatedmarker/leaflet.rotatedMarker.js"></script>
<script type="text/javascript" src="help.js"></script>
<script type="text/javascript" src="tabel.js"></script>
</head>
<body>

<?php
include('menu.inc.php');
?>

<div id="content" style="width:calc(100% - 48px);">
	<?php
	if (is_numeric($_GET['update'])) {
		echo '<p class="success">Asset <a id="updatesuccessassetid">' . $_GET['update'] .'</a> opgeslagen. <span class="closeparent">Melding sluiten</span></p>';
	}
	?>
	<div style="float:left;"><p>Asset: <select id="table-asset-filter"><option value="">(alle)</option>
		<?php
		$qry = "SELECT `name` FROM `".$db['prefix']."assettype`";
		if ($login !== TRUE) {
			$qry .= " WHERE `public` = 1";
		}
		$qry .= " ORDER BY `name`";
		$res = mysqli_query($db['link'], $qry);
		while ($data = mysqli_fetch_assoc($res)) {
			echo '<option value="' . htmlspecialchars($data['name']) . '">' . htmlspecialchars($data['name']) . '</option>';
		}
		?>
	</select> Zoeken: <input type="search" id="table-filter" placeholder="filtertekst"></p></div>
	<div style="float:right;"><p>Download: <a href="?download=csv">csv</a> | <a href="?download=json">json</a></p></div>
	
	<div id="assettable" style="height: calc(100vh - 200px); width:100%; clear: both;"><div>
</div>

</body>
</html>
