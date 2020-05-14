<?php
/*
 	fietsviewer - grafische weergave van fietsdata
	Copyright (C) 2018 Gemeente Den Haag, Netherlands
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
include('dbconnect.inc.php');
require('functions/bounds_to_sql.php');

//popup contents
if ($_GET['get'] == 'popup') {
	$json = array('html' => '');
	//query om inhoud van tabel te selecteren
	$qry = "SELECT `t_assettype`.`id` AS `assettypeid`, `t_assettype`.`name` AS `assettypename`, `t_aansturing`.`name` AS `aansturing`, `t_wegbeheerder`.`name` AS `wegbeheerder`, `".$db['prefix']."asset`.`id` AS `assetid`, `code`, `naam`, `latitude`, `longitude`, `heading`, `status`, `memo`, `organisation`
	FROM `".$db['prefix']."asset`
	LEFT JOIN `".$db['prefix']."assettype` AS `t_assettype`
	ON `".$db['prefix']."asset`.`assettype` = `t_assettype`.`id`
	LEFT JOIN `".$db['prefix']."organisation` AS `t_aansturing`
	ON `".$db['prefix']."asset`.`aansturing` = `t_aansturing`.`id`
	LEFT JOIN `".$db['prefix']."organisation` AS `t_wegbeheerder`
	ON `".$db['prefix']."asset`.`wegbeheerder` = `t_wegbeheerder`.`id`
	WHERE `".$db['prefix']."asset`.`id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'";
	if (($login !== TRUE) || (getuserdata('organisation') == 0)) {
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
		//html
		$html .= '<h1>' . htmlspecialchars($data['assettypename'] . ' ' . $data['code']) . '</h1>';
		$html .= '<p>' . htmlspecialchars($data['naam']);
		$html .= '<p>Aansturing: ' . htmlspecialchars($data['aansturing']);
		$html .= '<br>Wegbeheerder: ' . htmlspecialchars($data['wegbeheerder']);
		$html .= '<br>Status: ' . htmlspecialchars($data['status']);
		if (!empty($data['memo'])) {
			$html .= '<br>Memo: ' . htmlspecialchars($data['memo']);
		}
		$html .= '</p>';
		$html .= '<ul>';
		$html .= '<li><a id="popup_details">Details bekijken</a></li>';
		$html .= '<li><a href="https://www.google.nl/maps/?q=' . $data['latitude'] . ',' . $data['longitude'] . '&amp;layer=c&cbll=' . $data['latitude'] . ',' . $data['longitude'] . '&amp;cbp=11,' . $data['heading'] . ',0,0,5" target="_blank">Open locatie in Google Street View&trade;</a></li>';
		if (getuserdata() && (accesslevelcheck('beheer_eigen', $data['organisation']) || accesslevelcheck('beheer_alle'))) {
			$html .= '<li><a href="edit.php?id='.$data['assetid'].'">Bewerken</a></li>';
		}
		$html .= '</ul>';
		$json['html'] = $html;
	}
	else {
		$json['html'] = '<p class="error">Geen detailinformatie gevonden' . $qry .'</p>';
	}
}
//coordinates and layer id
elseif ($_GET['get'] == 'coordinates') {
	$json = array();
	//query om inhoud van tabel te selecteren
	$qry = "SELECT `t_assettype`.`id` AS `assettypeid`, `latitude`, `longitude`
	FROM `".$db['prefix']."asset`
	LEFT JOIN `".$db['prefix']."assettype` AS `t_assettype`
	ON `".$db['prefix']."asset`.`assettype` = `t_assettype`.`id`
	WHERE `".$db['prefix']."asset`.`id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'";
	if (($login !== TRUE) || (getuserdata('organisation') == 0)) {
		$qry .= " AND `t_assettype`.`public` = 1";
	}
	$qry .= " LIMIT 1";
	//voer query uit
	$res = mysqli_query($db['link'], $qry);
	//als er een of meer rijen zijn
	if (mysqli_num_rows($res) > 0) {
		$data = mysqli_fetch_assoc($res);
		$json['layer'] = (int) $data['assettypeid'];
		$json['latitude'] = $data['latitude'];
		$json['longitude'] = $data['longitude'];
	}
	else {
		header('HTTP/1.0 401 Unauthorized');
		exit;
	}
}
//hecto layer
elseif ($_GET['layer'] == 'hecto') {

	$qry = "SELECT `wegnummer`, `pos_wol`, `hecto`, `letter`, `latitude`, `longitude` FROM `".$db['prefix']."hectopunten`
	WHERE " . bounds_to_sql($_GET['bounds']);

	if ($_GET['zoom'] >= 17) {
		//full set
	}
	elseif ($_GET['zoom'] >= 16) {
		$qry .= "AND SUBSTR(`hecto`, -1) IN ('0', '5')";
	}
	elseif ($_GET['zoom'] >= 15) {
		$qry .= "AND SUBSTR(`hecto`, -1) IN ('0', '5')
		AND `pos_wol` IN ('R', 'M')
		AND `letter` = ''";
	}
	elseif ($_GET['zoom'] >= 14) {
		$qry .= "AND SUBSTR(`hecto`, -1) = '0'
		AND `pos_wol` IN ('R', 'M')
		AND `letter` = ''";
	}
	elseif ($_GET['zoom'] >= 13) {
		$qry .= "AND SUBSTR(`hecto`, -2) IN ('00', '25', '50', '75')
		AND `pos_wol` IN ('R', 'M')
		AND `letter` = ''";
	}
	elseif ($_GET['zoom'] >= 12) {
		$qry .= "AND SUBSTR(`hecto`, -2) IN ('00', '50')
		AND `pos_wol` IN ('R', 'M')
		AND `letter` = ''";
	}
	else { // <= 11
		$qry .= "AND SUBSTR(`hecto`, -2) = '00'
		AND `pos_wol` IN ('R', 'M')
		AND `letter` = ''";
	}
	
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$json = array();
		while ($data = mysqli_fetch_row($res)) {
			//add to output
			if ($_GET['zoom'] >= 16) {
				$name = $data[0].(($data[1] != 'M') ? ' '.$data[1] : '').' '.number_format($data[2]/10, 1).((!empty($data[3])) ? ' '.$data[3] : '');
			}
			else {
				$name = $data[0].' '.number_format($data[2]/10, 1);
			}
			$json[] = array(
				'id' => $name,
				'lat' => (float) $data[4],
				'lon' => (float) $data[5]
			);
		}
	}
}
//marker layer
else {
	//var_dump($_GET); exit;
	//array(3) { ["layer"]=> string(1) "1" ["bounds"]=> string(73) "4.157295227050782,51.994288319698065,4.486885070800782,52.069588138375366" ["filter"]=> string(28) "{"mtd":[],"org":[],"set":[]}" } 

	//check if logged in
	$qry = "SELECT `public` FROM `".$db['prefix']."assettype`
	WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_GET['layer']) . "'
	AND `public` = 1
	LIMIT 1";
	$res = mysqli_query($db['link'], $qry);
	if ((!mysqli_num_rows($res)) && (($login !== TRUE) || (getuserdata('organisation') == 0))){
		header('HTTP/1.0 401 Unauthorized');
		exit;
	}

	//voor DRIPs selecteer ook standaardtekst
	$qry = "SELECT `id`, `assettype` FROM `".$db['prefix']."addfield`
	WHERE `name` LIKE 'standaardtekst' 
	AND `assettype` = '" . mysqli_real_escape_string($db['link'], $_GET['layer']) . "'
	LIMIT 1";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		$data = mysqli_fetch_row($res);
		$addfieldid = $data[0];
		$qry = "SELECT `".$db['prefix']."asset`.`id` AS `assetid`, `code`, `latitude`, `longitude`, `heading`, `aansturing`, `status`, `content` FROM `".$db['prefix']."asset`
		LEFT JOIN 
		(SELECT * FROM `".$db['prefix']."addfieldcontent`
		WHERE `addfield` = '" . $addfieldid . "') AS `t1`
		ON `".$db['prefix']."asset`.`id` = `t1`.`asset`
		WHERE `assettype` = '" . mysqli_real_escape_string($db['link'], $_GET['layer']) . "' 
		AND `status` IN (1,2,3) 
		AND " . bounds_to_sql($_GET['bounds']);
		
	}
	else {
		$qry = "SELECT `id` AS `assetid`, `code`, `latitude`, `longitude`, `heading`, `aansturing`, `status` FROM `".$db['prefix']."asset`
		WHERE `assettype` = '" . mysqli_real_escape_string($db['link'], $_GET['layer']) . "' 
		AND `status` IN (1,2,3) 
		AND " . bounds_to_sql($_GET['bounds']);
	}
	$res = mysqli_query($db['link'], $qry);
	$json = array();
	while ($data = mysqli_fetch_assoc($res)) {
		$json[] = array('id' => (int) $data['assetid'],
		'code' => $data['code'],
		'lat' => (float) $data['latitude'],
		'lon' => (float) $data['longitude'],
		'heading' => (float) $data['heading'],
		'icon' => (float) $data['aansturing'],
		'itype' => ((empty($data['content'])) ? 0 : 1),
		'status' => (int) $data['status']);
	}
}

header('Content-Type: application/json');
echo json_encode($json);

?>