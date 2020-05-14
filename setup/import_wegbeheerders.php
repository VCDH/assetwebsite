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
Dit script is bedoeld om wegbeheerders.json te importeren in het nieuwe databasemodel
Het overschrijft de kleuren in de organisatietabel met gegevens uit wegbeheerders.json
Voer dit uit na het converteren uit de oude database
*/

//verbind ook met nieuwe db
include('../dbconnect.inc.php');

$json = json_decode(file_get_contents('wegbeheerders.json'), TRUE);
if ($json === NULL) {
	echo '* Kan JSON niet lezen' . PHP_EOL;
}
foreach ($json as $wegbeheerder) {
	$bordercolor = strtoupper(substr($wegbeheerder['strokecolour'], 1));
	if (strlen($bordercolor) == 3) {
		$bordercolor = substr($bordercolor, 0, 1) . substr($bordercolor, 0, 1) . substr($bordercolor, 1, 1) . substr($bordercolor, 1, 1) . substr($bordercolor, 2, 1) . substr($bordercolor, 2, 1);
	}
	$fillcolor = strtoupper(substr($wegbeheerder['fillcolour'], 1));
	if (strlen($fillcolor) == 3) {
		$fillcolor = substr($fillcolor, 0, 1) . substr($fillcolor, 0, 1) . substr($fillcolor, 1, 1) . substr($fillcolor, 1, 1) . substr($fillcolor, 2, 1) . substr($fillcolor, 2, 1);
	}

	$qry = "UPDATE `".$db['prefix']."organisation` SET
	`bordercolor` = '" . mysqli_real_escape_string($db['link'], $bordercolor) . "', 
	`fillcolor` = '" . mysqli_real_escape_string($db['link'], $fillcolor) . "'
	WHERE `name` LIKE '" . mysqli_real_escape_string($db['link'], $wegbeheerder['name']) . "'";
	mysqli_query($db['link'], $qry);
}
?>