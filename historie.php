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
accesscheck('beheer_eigen');
//include database gegevens
include('dbconnect.inc.php');


?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Assets - Historie</title>
<link rel="stylesheet" type="text/css" href="bundled/jquery-ui/jquery-ui.min.css">
<link rel="stylesheet" type="text/css" href="style/style.css">
<link rel="icon" type="image/png" href="favicon.png">
<script type="text/javascript" src="bundled/jquery/jquery.min.js"></script>
<script type="text/javascript" src="bundled/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="help.js"></script>
</head>
<body>

<?php
include('menu.inc.php');
?>

<div id="content">
	<?php
	//get asset by id
	$qry = "SELECT `id` FROM `".$db['prefix']."asset`
	WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'";
	//voer query uit
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res) != 1) {
		//laatste wijzigingen
		echo '<h1>Laatste 100 wijzingen</h1>'; 
		$qry = "SELECT `".$db['prefix']."asset`.`id` AS `id`, `t_assettype`.`name` AS `assettype`, `code`, `t_aansturing`.`name` AS `aansturing`, `".$db['prefix']."asset`.`date_edit`, `t_user`.`name` AS `user_edit` , `t_edit`.`name` AS `organisation_edit`
		FROM `".$db['prefix']."asset`
		LEFT JOIN `".$db['prefix']."assettype` AS `t_assettype`
		ON `".$db['prefix']."asset`.`assettype` = `t_assettype`.`id`
		LEFT JOIN `".$db['prefix']."organisation` AS `t_aansturing`
		ON `".$db['prefix']."asset`.`aansturing` = `t_aansturing`.`id`
		LEFT JOIN `".$db['prefix']."user` AS `t_user`
		ON `".$db['prefix']."asset`.`user_edit` = `t_user`.`id`
		LEFT JOIN `".$db['prefix']."organisation` AS `t_edit`
		ON `t_user`.`organisation` = `t_edit`.`id`
		ORDER BY `".$db['prefix']."asset`.`date_edit` DESC
		LIMIT 100";
		$res = mysqli_query($db['link'], $qry);
		echo mysqli_error($db['link']);
		echo '<table class="tabulator">';
		echo '<tr><th>type</th><th>code</th><th>aansturing</th><th>gewijzigd</th><th>gewijzigd door</th></tr>';
		while ($data = mysqli_fetch_assoc($res)) {
			echo '<tr><td>';
			echo htmlspecialchars($data['assettype']);
			echo '</td><td><a href="index.php?id=' . $data['id'] . '">';
			echo htmlspecialchars($data['code']);
			echo '</a></td><td>';
			echo htmlspecialchars($data['aansturing']);
			echo '</td><td>';
			echo $data['date_edit'];
			echo '</td><td>';
			echo htmlspecialchars($data['user_edit'] . ' (' . $data['organisation_edit'] . ')');
			echo '</td></tr>';
		}
		echo '</table>';
	}
	else {
		$data = mysqli_fetch_assoc($res);
		echo '<h1>Historie voor asset ' . $data['id'] . '</h1>';
		echo '<table>';
		echo '<tr><th></th><th>Versie van</th><th>Gewijzigd door</th></tr>';

		echo '</table>';
	}

	?>
</div>

</body>
</html>
