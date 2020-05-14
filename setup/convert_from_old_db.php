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

/*
Dit script is bedoeld om data van de oude asset website te converteren naar
het nieuwe databasemodel. Je hebt het niet nodig als je met een lege 
setup begint.

Geef hieronder de credentials van de oude database op.
De nieuwe database moet zijn geinstalleerd via install.php voordat je dit script uitvoert.
*/

$old_db['server'] = 'localhost';
$old_db['username'] = 'root';
$old_db['password'] = '';
$old_db['database'] = '58052drips';
$old_db['prefix'] = 'drip_';

//issues in dit script
// - geen check op duplicaten, dus alleen uitvoeren op een lege db!
// - additionele velden zijn volgens install.php: drip=1, cam=2, vri=3, tdi=4
// - organisatie (eigenaar van assetinformatie op website) wordt niet ingevuld
// - database id uit oude db worden niet overgenomen naar nieuwe
// - veld 'code' voor drips uit oude db wordt niet overgenomen
// - historie uit oude db wordt niet overgenomen

//connect met database, dit gaat mis bij uitvoering van setup/install.php, maar wordt daar ondervangen
$old_db['link'] = mysqli_connect($old_db['server'], $old_db['username'], $old_db['password'], $old_db['database']);
//stel karakterset in voor mysqli_real_escape_string
mysqli_set_charset($old_db['link'], 'latin-1');

//verbind ook met nieuwe db
//als beide op dezelfde server staan, zou het met dezelfde connectie kunnen, maar dat is natuurlijk geen gegeven
include('../dbconnect.inc.php');

//importeer organisaties
//dit zou met insert into select kunnen, ware het niet dat het in twee verschillende databases zou kunnen staan en dan dus weer niet, vandaar op deze manier geimplementeerd
$old_qry = "SELECT * FROM `" . $old_db['prefix'] . "organisations`";
$old_res = mysqli_query($old_db['link'], $old_qry);
while ($old_data = mysqli_fetch_assoc($old_res)) {

	$qry = "INSERT INTO `".$db['prefix']."organisation` SET
		`id` = '" . (mysqli_real_escape_string($db['link'], $old_data['id']) + 1) . "',
		`email` = '" . mysqli_real_escape_string($db['link'], $old_data['email']) . "',
		`name`  = '" . mysqli_real_escape_string($db['link'], $old_data['name']) . "',
		`allowsignup` = 1,
		`user_edit` = 0,
		`user_create` = 0";
	if (!mysqli_query($db['link'], $qry)) {
		echo 'Organisation ' . $old_data['id'] . ' failed' . PHP_EOL;
	}
}

//importeer users
//dit zou met insert into select kunnen, ware het niet dat het in twee verschillende databases zou kunnen staan en dan dus weer niet, vandaar op deze manier geimplementeerd
$old_qry = "SELECT * FROM `" . $old_db['prefix'] . "users`";
$old_res = mysqli_query($old_db['link'], $old_qry);
while ($old_data = mysqli_fetch_assoc($old_res)) {

	$qry = "INSERT INTO `".$db['prefix']."user` SET
		`email` = '" . mysqli_real_escape_string($db['link'], $old_data['email']) . "',
		`name`  = '" . mysqli_real_escape_string($db['link'], $old_data['name']) . "',
		`phone`  = '" . mysqli_real_escape_string($db['link'], $old_data['phone']) . "',
		`organisation`  = '" . (mysqli_real_escape_string($db['link'], $old_data['organisation']) + 1) . "',
		`accesslevel`  = '" . mysqli_real_escape_string($db['link'], $old_data['accesslevel']) . "',
		`user_edit` = 0,
		`user_create` = 0";
	if (!mysqli_query($db['link'], $qry)) {
		echo 'User ' . $old_data['id'] . ' failed' . PHP_EOL;
	}
}

$wegbeheerders = array();

//functie om wegbeheerder id te vinden
function vind_wegbeheerder_id($name) {
	global $wegbeheerders, $db;
	if (in_array($name, $wegbeheerders)) {
		return $wegbeheerders[strtolower($name)];
	}
	else {
		//get from db
		$qry = "SELECT `id` FROM `".$db['prefix']."organisation`
		WHERE `name` LIKE '" . mysqli_real_escape_string($db['link'], $name) . "'
		LIMIT 1";
		$res = mysqli_query($db['link'], $qry);
		if (mysqli_num_rows($res) == 1) {
			$row = mysqli_fetch_row($res);
			$wegbeheerders[strtolower($name)] = $row[0];
			return $row[0];
		}
		else {
			//add to db
			$qry = "INSERT INTO `".$db['prefix']."organisation`
			SET 
			`name` = '" . mysqli_real_escape_string($db['link'], $name) . "',
			`bordercolor` = '000000',
			`fillcolor` = 'CCCCCC',
			`user_edit` = 0,
			`user_create` = 0";
			$res = mysqli_query($db['link'], $qry);
			$insert_id = mysqli_insert_id($db['link']);
			$wegbeheerders[strtolower($name)] = $insert_id;
			return $insert_id;
		}
	}
}
//vind additionele velden
$addfieldid = array();
$qry = "SELECT * FROM `".$db['prefix']."addfield`";
$res = mysqli_query($db['link'], $qry);
while ($data = mysqli_fetch_assoc($res)) {
	$addfieldid[$data['assettype'] . '_' . $data['name']] = $data['id'];
}

function convert_status($status) {
	$status = strtolower($status);
	switch ($status) {
		case 'verwijderd': return 4;
		case 'buiten gebruik': return 3;
		case 'realisatie': return 2;
		case 'bestaand': return 1;
		default: return 0;
	}
}

//converteer DRIP
$old_qry = "SELECT * FROM `" . $old_db['prefix'] . "drip`";
$old_res = mysqli_query($old_db['link'], $old_qry);
while ($old_data = mysqli_fetch_assoc($old_res)) {

	$qry = "INSERT INTO `".$db['prefix']."asset` SET
		`assettype` = 1,
		`code` = '" . mysqli_real_escape_string($db['link'], $old_data['id']) . "',
		`naam`  = '" . mysqli_real_escape_string($db['link'], $old_data['naam']) . "',
		`latitude` = '" . mysqli_real_escape_string($db['link'], $old_data['latitude']) . "',
		`longitude` = '" . mysqli_real_escape_string($db['link'], $old_data['longitude']) . "',
		`heading` = '" . mysqli_real_escape_string($db['link'], $old_data['rotatie']) . "',
		`status` = '" . convert_status($old_data['status']) . "',
		`aansturing` = " . vind_wegbeheerder_id($old_data['aansturing']) . ",
		`wegbeheerder` = " . vind_wegbeheerder_id($old_data['wegbeheerder']) . ",
		`onderhoud` = " . vind_wegbeheerder_id($old_data['onderhoud']) . ",
		`voeding` = " . vind_wegbeheerder_id($old_data['voeding']) . ",
		`verbinding` = " . vind_wegbeheerder_id($old_data['verbinding']) . ",
		`leverancier` = '" . mysqli_real_escape_string($db['link'], $old_data['leverancier']) . "',
		`bouwjaar` = '" . mysqli_real_escape_string($db['link'], $old_data['bouwjaar']) . "',
		`oorspronkelijk_bouwjaar` = '" . mysqli_real_escape_string($db['link'], $old_data['oorspronkelijk_bouwjaar']) . "',
		`memo` = '" . mysqli_real_escape_string($db['link'], $old_data['opmerking']) . "',
		`organisation` = 1,
		`user_edit` = 0,
		`user_create` = 0";
	if (!mysqli_query($db['link'], $qry)) {
		echo 'DRIP ' . $old_data['id'] . ' failed' . PHP_EOL;
	}
	else {
		//additionele velden
		$insert_id = mysqli_insert_id($db['link']);
		$insertable_fields = array('project', 'weg', 'richting', 'hectometer', 'type', 'template', 'standaardtekst', 'bewegwijzering');
		$rows = array();
		foreach ($insertable_fields as $insertable_field) {
			if (!empty($old_data[$insertable_field])) {
				$rows[] = "(" . $insert_id . ", " . $addfieldid['1_' . $insertable_field] . ", '" . mysqli_real_escape_string($db['link'], $old_data[$insertable_field]) . "', 0, 0)";
			}
		}
		if (!empty($rows)) {
			$qry = "INSERT INTO `".$db['prefix']."addfieldcontent` 
				(`asset`, `addfield`, `content`, `user_edit`, `user_create`)
				VALUES" . join(',', $rows);
			mysqli_query($db['link'], $qry);
		}
	}
}

//converteer cam
$old_qry = "SELECT * FROM `" . $old_db['prefix'] . "cam`";
$old_res = mysqli_query($old_db['link'], $old_qry);
while ($old_data = mysqli_fetch_assoc($old_res)) {

	$qry = "INSERT INTO `".$db['prefix']."asset` SET
		`assettype` = 2,
		`code` = '" . mysqli_real_escape_string($db['link'], $old_data['id']) . "',
		`naam`  = '" . mysqli_real_escape_string($db['link'], $old_data['naam']) . "',
		`latitude` = '" . mysqli_real_escape_string($db['link'], $old_data['latitude']) . "',
		`longitude` = '" . mysqli_real_escape_string($db['link'], $old_data['longitude']) . "',
		`status` = '" . convert_status($old_data['status']) . "',
		`aansturing` = " . vind_wegbeheerder_id($old_data['aansturing']) . ",
		`wegbeheerder` = " . vind_wegbeheerder_id($old_data['wegbeheerder']) . ",
		`onderhoud` = " . vind_wegbeheerder_id($old_data['onderhoud']) . ",
		`voeding` = " . vind_wegbeheerder_id($old_data['voeding']) . ",
		`verbinding` = " . vind_wegbeheerder_id($old_data['verbinding']) . ",
		`leverancier` = '" . mysqli_real_escape_string($db['link'], $old_data['leverancier']) . "',
		`bouwjaar` = '" . mysqli_real_escape_string($db['link'], $old_data['bouwjaar']) . "',
		`oorspronkelijk_bouwjaar` = '" . mysqli_real_escape_string($db['link'], $old_data['bouwjaar']) . "',
		`organisation` = 1,
		`user_edit` = 0,
		`user_create` = 0";
	if (!mysqli_query($db['link'], $qry)) {
		echo 'CAM ' . $old_data['id'] . ' failed' . PHP_EOL;
	}
	else {
		//additionele velden
		$insert_id = mysqli_insert_id($db['link']);
		if (!empty($rows)) {
			$qry = "INSERT INTO `".$db['prefix']."addfieldcontent` 
				(`asset`, `addfield`, `content`, `user_edit`, `user_create`)
				VALUES 
				(" . $insert_id . ", " . $addfieldid['2_PTZ'] . ", '" . mysqli_real_escape_string($db['link'], $old_data['ptz']) . "', 0, 0)";
			mysqli_query($db['link'], $qry);
		}
	}
}

//converteer vri
$old_qry = "SELECT * FROM `" . $old_db['prefix'] . "vri`";
$old_res = mysqli_query($old_db['link'], $old_qry);
while ($old_data = mysqli_fetch_assoc($old_res)) {

	$qry = "INSERT INTO `".$db['prefix']."asset` SET
		`assettype` = 3,
		`code` = '" . mysqli_real_escape_string($db['link'], $old_data['id']) . "',
		`naam`  = '" . mysqli_real_escape_string($db['link'], $old_data['naam']) . "',
		`latitude` = '" . mysqli_real_escape_string($db['link'], $old_data['latitude']) . "',
		`longitude` = '" . mysqli_real_escape_string($db['link'], $old_data['longitude']) . "',
		`status` = '" . convert_status($old_data['status']) . "',
		`aansturing` = " . vind_wegbeheerder_id($old_data['aansturing']) . ",
		`wegbeheerder` = " . vind_wegbeheerder_id($old_data['wegbeheerder']) . ",
		`onderhoud` = " . vind_wegbeheerder_id($old_data['onderhoud']) . ",
		`voeding` = " . vind_wegbeheerder_id($old_data['voeding']) . ",
		`verbinding` = " . vind_wegbeheerder_id($old_data['verbinding']) . ",
		`leverancier` = '" . mysqli_real_escape_string($db['link'], $old_data['leverancier']) . "',
		`bouwjaar` = '" . mysqli_real_escape_string($db['link'], $old_data['bouwjaar']) . "',
		`oorspronkelijk_bouwjaar` = '" . mysqli_real_escape_string($db['link'], $old_data['bouwjaar']) . "',
		`organisation` = 1,
		`user_edit` = 0,
		`user_create` = 0";
	if (!mysqli_query($db['link'], $qry)) {
		echo 'VRI ' . $old_data['id'] . ' failed' . PHP_EOL;
	}
	else {
		//additionele velden
		$insert_id = mysqli_insert_id($db['link']);
		if (!empty($rows)) {
			$qry = "INSERT INTO `".$db['prefix']."addfieldcontent` 
				(`asset`, `addfield`, `content`, `user_edit`, `user_create`)
				VALUES 
				(" . $insert_id . ", " . $addfieldid['3_iVRI'] . ", '" . mysqli_real_escape_string($db['link'], $old_data['ivri']) . "', 0, 0)";
			if (!empty($old_data['ttid'])) {
				$qry .= ", (" . $insert_id . ", " . $addfieldid['3_TLC-ID'] . ", '" . mysqli_real_escape_string($db['link'], $old_data['ttid']) . "', 0, 0)";
			}
			mysqli_query($db['link'], $qry);
		}
	}
}

//converteer tdi
$old_qry = "SELECT * FROM `" . $old_db['prefix'] . "tdi`";
$old_res = mysqli_query($old_db['link'], $old_qry);
while ($old_data = mysqli_fetch_assoc($old_res)) {

	$qry = "INSERT INTO `".$db['prefix']."asset` SET
		`assettype` = 4,
		`code` = '" . mysqli_real_escape_string($db['link'], $old_data['id']) . "',
		`naam`  = '" . mysqli_real_escape_string($db['link'], $old_data['naam']) . "',
		`latitude` = '" . mysqli_real_escape_string($db['link'], $old_data['latitude']) . "',
		`longitude` = '" . mysqli_real_escape_string($db['link'], $old_data['longitude']) . "',
		`status` = '" . convert_status($old_data['status']) . "',
		`aansturing` = " . vind_wegbeheerder_id($old_data['aansturing']) . ",
		`wegbeheerder` = " . vind_wegbeheerder_id($old_data['wegbeheerder']) . ",
		`onderhoud` = " . vind_wegbeheerder_id($old_data['onderhoud']) . ",
		`voeding` = " . vind_wegbeheerder_id($old_data['voeding']) . ",
		`verbinding` = " . vind_wegbeheerder_id($old_data['verbinding']) . ",
		`leverancier` = '" . mysqli_real_escape_string($db['link'], $old_data['leverancier']) . "',
		`bouwjaar` = '" . mysqli_real_escape_string($db['link'], $old_data['bouwjaar']) . "',
		`oorspronkelijk_bouwjaar` = '" . mysqli_real_escape_string($db['link'], $old_data['bouwjaar']) . "',
		`organisation` = 1,
		`user_edit` = 0,
		`user_create` = 0";
	if (!mysqli_query($db['link'], $qry)) {
		echo 'TDI ' . $old_data['id'] . ' failed' . PHP_EOL;
	}
	else {
		//additionele velden
		$insert_id = mysqli_insert_id($db['link']);
		if (!empty($rows)) {
			$qry = "INSERT INTO `".$db['prefix']."addfieldcontent` 
				(`asset`, `addfield`, `content`, `user_edit`, `user_create`)
				VALUES 
				(" . $insert_id . ", " . $addfieldid['4_iVRI'] . ", '" . mysqli_real_escape_string($db['link'], $old_data['ivri']) . "', 0, 0)";
			if (!empty($old_data['ttid'])) {
				$qry .= ", (" . $insert_id . ", " . $addfieldid['4_TLC-ID'] . ", '" . mysqli_real_escape_string($db['link'], $old_data['ttid']) . "', 0, 0)";
			}
			mysqli_query($db['link'], $qry);
		}
	}
}
?>