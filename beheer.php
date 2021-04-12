<?php 
/*
 	assetwebsite - viewer en aanvraagformulier voor verkeersmanagementassets
    Copyright (C) 2016-2020 Gemeente Den Haag, Netherlands
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
include_once('accesslevels.cfg.php');
//redirect if not logged in
logincheck();
//include database gegevens
include('dbconnect.inc.php');

//verwerk gebruikerswijziging
if (($_GET['do'] == 'useredit') && (!empty($_POST))) {
	$fieldcheck = TRUE;
	$gebruiker_gewijzigd = FALSE;
	//check fields
	if (empty($_POST['name'])) $fieldcheck = FALSE;
	if (empty($_POST['email'])) $fieldcheck = FALSE;

	//krijg organisatie van gebruiker
	$qry = "SELECT `organisation` FROM `".$db['prefix']."user`
	WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {
		//bestaande gebruiker
		$row = mysqli_fetch_row($res);
		$vorige_organisatie = $row[0];
	}
	else {
		$vorige_organisatie = 0;
	}
	//controleer of bewerkt mag worden
	if (
		!accesslevelcheck('gebruikers_beheren_alle') && //mag altijd wijzigen
		(
			!accesslevelcheck('gebruikers_beheren_eigen') && //mag wijzigen als
			(
				(($vorige_organisatie == 0) && ($_POST['organisation'] == getuserdata('organisation'))) || //gebruiker geen organisatie had en naar eigen organisatie wordt verplaatst, of
				($vorige_organisatie == getuserdata('organisation')) //gebruiker in eigen organisatie zat
			)
		)
	) {
		$fieldcheck = FALSE;
	}
	//check organisation
	if (!accesslevelcheck('gebruikers_beheren_alle') && ($_POST['organisation'] != getuserdata('organisation'))) {
		$fieldcheck = FALSE;
	}
	//sta niet toe om gebruikers te bewerken met hoger accesslevel dan eigen
	if (!is_numeric($_POST['accesslevel']) || ($_POST['accesslevel'] < 0) || ($_POST['accesslevel'] > getuserdata('accesslevel'))) {
		$fieldcheck = FALSE;
	}

	//save data
	if ($fieldcheck == TRUE) {
		if (is_numeric($_GET['id'])) {
			//query om rij aan te passen
			$qry = "UPDATE `".$db['prefix']."user`
			SET `name` = '" . mysqli_real_escape_string($db['link'], $_POST['name']) . "',
			`email` = '" . mysqli_real_escape_string($db['link'], $_POST['email']) . "',
			`phone` = '" . mysqli_real_escape_string($db['link'], $_POST['phone']) . "',
			`organisation` = '" . mysqli_real_escape_string($db['link'], $_POST['organisation']) . "',
			`accesslevel` = '" . mysqli_real_escape_string($db['link'], $_POST['accesslevel']) . "'
			WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'";
		}
		else {
			$qry = "INSERT INTO `".$db['prefix']."user`
			SET `name` = '" . mysqli_real_escape_string($db['link'], $_POST['name']) . "',
			`email` = '" . mysqli_real_escape_string($db['link'], $_POST['email']) . "',
			`phone` = '" . mysqli_real_escape_string($db['link'], $_POST['phone']) . "',
			`organisation` = '" . mysqli_real_escape_string($db['link'], $_POST['organisation']) . "',
			`accesslevel` = '" . mysqli_real_escape_string($db['link'], $_POST['accesslevel']) . "'";
		}
		//voer query uit
		$gebruiker_gewijzigd = mysqli_query($db['link'], $qry);
	}
}
//verwerk organisatiewijziging
if (($_GET['do'] == 'organisationedit') && (!empty($_POST))) {
	$fieldcheck = TRUE;
	//check fields
	if (empty($_POST['name'])) $fieldcheck = FALSE;
	if (!preg_match('/[0-9A-Z]{6}/i', $_POST['bordercolor']) && !empty($_POST['bordercolor'])) $fieldcheck = FALSE;
	if (!preg_match('/[0-9A-Z]{6}/i', $_POST['fillcolor']) && !empty($_POST['fillcolor'])) $fieldcheck = FALSE;
	//check organisation
	$qry = "SELECT `id`, `bordercolor`, `fillcolor` FROM `".$db['prefix']."organisation`
	WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'
	LIMIT 1";
	$res = mysqli_query($db['link'], $qry);
	$data = mysqli_fetch_assoc($res);
	if (!accesslevelcheck('organisaties_beheren_alle') && !accesslevelcheck('organisaties_beheren_eigen', $data['id'])) {
		$fieldcheck = FALSE;
		$organisatie_gewijzigd = FALSE;
	}
	
	//save data
	if ($fieldcheck == TRUE) {
		//check if colors changed, if so delete icons from cache
		if (($_POST['bordercolor'] != $data['bordercolor']) || ($_POST['fillcolor'] != $data['fillcolor'])) {
			$dir = 'images';
			$files = scandir($dir);
			foreach ($files as $file) {
				if (is_file($dir . '/' . $file)) {
					if (preg_match('/^\d+_' . $data['id'] . '(_\d+)?\.png$/', $file)) {
						unlink($dir . '/' . $file);
					}
				}
			}
		}
		
		if (is_numeric($_GET['id'])) {
			//query om rij aan te passen
			$qry = "UPDATE `".$db['prefix']."organisation`
			SET `name` = '" . mysqli_real_escape_string($db['link'], $_POST['name']) . "',
			`email` = '" . mysqli_real_escape_string($db['link'], $_POST['email']) . "',
			`bordercolor` = '" . mysqli_real_escape_string($db['link'], strtoupper($_POST['bordercolor'])) . "',
			`fillcolor` = '" . mysqli_real_escape_string($db['link'], strtoupper($_POST['fillcolor'])) . "',
			`aanvraagformulier_tekst` = '" . mysqli_real_escape_string($db['link'], $_POST['aanvraagformulier_tekst']) . "',
			`allowsignup` = " . (($_POST['allowsignup'] == 'true') ? 'TRUE' : 'FALSE') . "
			WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'";
		}
		else {
			$qry = "INSERT INTO `".$db['prefix']."organisation`
			SET `name` = '" . mysqli_real_escape_string($db['link'], $_POST['name']) . "',
			`email` = '" . mysqli_real_escape_string($db['link'], $_POST['email']) . "',
			`bordercolor` = '" . mysqli_real_escape_string($db['link'], strtoupper($_POST['bordercolor'])) . "',
			`fillcolor` = '" . mysqli_real_escape_string($db['link'], strtoupper($_POST['fillcolor'])) . "',
			`aanvraagformulier_tekst` = '" . mysqli_real_escape_string($db['link'], $_POST['aanvraagformulier_tekst']) . "',
			`allowsignup` = " . (($_POST['allowsignup'] == 'true') ? 'TRUE' : 'FALSE');
		}
		//voer query uit
		$organisatie_gewijzigd = mysqli_query($db['link'], $qry);
	}
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Assets - Beheer</title>
<script type="text/javascript" src="bundled/jquery/jquery.min.js"></script>
<script type="text/javascript" src="bundled/jquery-ui/jquery-ui.min.js"></script>
<script type="text/javascript" src="bundled/js-cookie/js.cookie.min.js"></script>
<script type="text/javascript" src="help.js"></script>
<link rel="stylesheet" type="text/css" href="bundled/jquery-ui/jquery-ui.min.css">
<link rel="stylesheet" type="text/css" href="style/style.css">
<link rel="icon" type="image/png" href="favicon.png">
</head>
<body>

<?php
include('menu.inc.php');
?>

<div id="content">
<?php 
if (accesslevelcheck('gebruikers_beheren_eigen') && (($_GET['do'] == 'userlist') || ($gebruiker_gewijzigd === TRUE))) {
	echo '<a href="?">Terug</a>';
	echo '<h1>Gebruikers</h1>';
	if ($gebruiker_gewijzigd === TRUE) {
		echo '<p class="success">Gebruikersgegevens gewijzigd!</p>';
	}
	//query om inhoud van tabel te selecteren
	$sql = "SELECT `".$db['prefix']."user`.`id` AS `id`, `".$db['prefix']."user`.`name` AS `name`, `".$db['prefix']."user`.`email` AS `email`, `".$db['prefix']."user`.`phone` AS `phone`, `".$db['prefix']."user`.`accesslevel` AS `accesslevel`, `".$db['prefix']."organisation`.`name` AS `organisation`, `".$db['prefix']."user`.`organisation` AS `organisation_id`, `".$db['prefix']."user`.`lastlogin` AS `lastlogin`
	FROM `".$db['prefix']."user`
	LEFT JOIN `".$db['prefix']."organisation`
	ON `".$db['prefix']."user`.`organisation` = `".$db['prefix']."organisation`.`id`";
	if (!accesslevelcheck('gebruikers_beheren_alle')) {
		$sql .= " WHERE `organisation` = '" . getuserdata('organisation') . "'";
	}
	$sql .= " ORDER BY `name`";
	//voer query uit
	$result = mysqli_query($db['link'], $sql);
	//als er een of meer rijen zijn
	if (mysqli_num_rows($result) > 0) {
        echo '<div id="userlist">';
		echo '<input class="search" placeholder="Zoeken">';
		//start html tabel
		echo '<table>';
		echo '<tr><th>Naam</th><th>Organisatie</th><th>E-mailadres</th><th>Telefoonnummer</th><th>Toegangsniveau</th><th>Laatste login</th><th></th></tr>';
		echo '<tbody class="list">';
		//lus om alle rijen weer te geven
		while ($data = mysqli_fetch_assoc($result)) {
			echo '<tr>';
			echo '<td class="list-name">' . htmlspecialchars($data['name']) . '</td>';
			echo '<td class="list-org">' . htmlspecialchars($data['organisation']) . '</td>';
			echo '<td>' . htmlspecialchars($data['email']) . '</td>';
			echo '<td>' . htmlspecialchars($data['phone']) . '</td>';
			echo '<td>' . ((array_key_exists($data['accesslevel'], $accesslevel_available)) ? $accesslevel_available[$data['accesslevel']] : htmlspecialchars($data['accesslevel'])) . '</td>';
			echo '<td>' . htmlspecialchars($data['lastlogin']) . '</td>';
			echo '<td>';
			if ((accesslevelcheck('gebruikers_beheren_alle') || accesslevelcheck('gebruikers_beheren_eigen', $data['organisation_id'])) && ($data['accesslevel'] <= getuserdata('accesslevel'))) echo '<a href="?do=useredit&amp;id=' . $data['id'] . '">Bewerk</a><!-- <a href="?do=userdelete&amp;id=' . $data['id'] . '">Verwijder</a>-->';
			echo '</td>';
			echo '</tr>';
		}
		//eind html tabel
		echo '</tbody>';
		echo '</table>';
		echo '</div>';
		?>
		<script type="text/javascript" src="bundled/list-js/list.min.js"></script>
		<script type="text/javascript">
		var userList = new List('userlist', {
			valueNames: [ 'list-name', 'list-org' ]
		});
		</script>
		<?php
	}
	//geen resultaten
	else {
		echo '<p>Er zijn geen gebruikers.</p>';
	} 
	if (accesslevelcheck('gebruikers_beheren_eigen')) {
		echo '<p><a href="?do=useredit">Gebruiker toevoegen</a></p>';
	}
}
//edit user
elseif (accesslevelcheck('gebruikers_beheren_eigen') && ($_GET['do'] == 'useredit') && ($gebruiker_gewijzigd != TRUE)) {
	if (!is_numeric($_GET['id'])) {
		echo '<h1>Nieuwe gebruiker toevoegen</h1>';
	}
	else {
		echo '<h1>Bewerk gebruiker</h1>';
	}
	//query om inhoud van tabel te selecteren
	$sql = "SELECT * FROM `".$db['prefix']."user`
	WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'";
	//voer query uit
	$result = mysqli_query($db['link'], $sql);
	//als er een of meer rijen zijn
	if (mysqli_num_rows($result) > 0) {
		$data = mysqli_fetch_assoc($result);
	}
		
	if ($fieldcheck === FALSE) {
		echo '<p class="error">Niet alle velden zijn ingevuld.</p>';
	}
	if ($gebruiker_gewijzigd === FALSE) {
		echo '<p class="error">Kan gebruiker niet opslaan.</p>';
	}
	?>
	<form method="post">
	<table>
	<tr><td>Naam:</td><td><input type="text" name="name" value="<?php echo htmlspecialchars($data['name']); ?>"></td></tr>
	<tr><td>E-mail:</td><td><input type="text" name="email" value="<?php echo htmlspecialchars($data['email']); ?>"></td></tr>
	<tr><td>Telefoon:</td><td><input type="text" name="phone" value="<?php echo htmlspecialchars($data['phone']); ?>"></td></tr>
    <tr><td>Organisatie:</td><td><select name="organisation"><?php
	$qry = "SELECT `id`, `name` FROM `".$db['prefix']."organisation`";
	if (!accesslevelcheck('gebruikers_beheren_alle')) {
		$qry .= " WHERE `id` = '" . mysqli_real_escape_string($db['link'], getuserdata('organisation')) . "'";
	}
	$res = mysqli_query($db['link'], $qry);
	while ($data2 = mysqli_fetch_assoc($res)) {
		echo '<option value="'.$data2['id'].'"';
		if ($data['organisation'] == $data2['id']) echo ' selected="selected"';
		echo '>'.htmlspecialchars($data2['name']).'</option>';	
	}
	?></select></td></tr>
	<tr><td>Toegangsniveau:</td><td><select name="accesslevel"><?php
	foreach ($accesslevel_available as $accesslevel_this => $description_this) {
		echo '<option value="'.$accesslevel_this.'"';
		if ($data['accesslevel'] == $accesslevel_this) echo ' selected="selected"';
		echo '>'.$description_this.'</option>';	
	}
	?></select></td></tr>
	<tr><td></td><td><input type="submit" value="Opslaan"> <a href="?do=userlist">Annuleren</a></td></tr>
	</table>
	</form>
	<?php
}
//organisations list
elseif (accesslevelcheck('organisaties_beheren_eigen') && (($_GET['do'] == 'organisationlist') || ($organisatie_gewijzigd === TRUE))) {
	echo '<a href="?">Terug</a>';
	echo '<h1>Organisaties</h1>';
	if ($organisatie_gewijzigd === TRUE) {
		echo '<p class="success">Organisatiegegevens gewijzigd!</p>';
	}
	//query om inhoud van tabel te selecteren
	$sql = "SELECT * FROM `".$db['prefix']."organisation`
	ORDER BY `name`";
	//voer query uit
	$result = mysqli_query($db['link'], $sql);
	//als er een of meer rijen zijn
	if (mysqli_num_rows($result) > 0) {
		//start html tabel
		echo '<div id="userlist">';
		echo '<input class="search" placeholder="Zoeken">';
		echo '<table>';
		echo '<tr><th>Naam</th><th>Randkleur</th><th>Vulkleur</th><th>E-mail-suffix</th><th>Reg</th><th></th></tr>';
		echo '<tbody class="list">';
		//lus om alle rijen weer te geven
		while ($data = mysqli_fetch_assoc($result)) {
			echo '<tr>';
			echo '<td class="list-name">' . htmlspecialchars($data['name']) . '</td>';
			echo '<td>' . htmlspecialchars($data['bordercolor']) . '</td>';
			echo '<td>' . htmlspecialchars($data['fillcolor']) . '</td>';
			echo '<td>' . htmlspecialchars($data['email']) . '</td>';
			echo '<td>' . (($data['allowsignup'] == '1') ? 'Ja' : 'Nee') . '</td>';
			echo '<td>';
			if (accesslevelcheck('organisaties_beheren_alle') || accesslevelcheck('organisaties_beheren_eigen', $data['id'])) echo '<a href="?do=organisationedit&amp;id=' . $data['id'] . '">Bewerk</a>';
			echo '</td>';
			echo '</tr>';
		}
		//eind html tabel
		echo '</tbody>';
		echo '</table>';
		echo '</div>';
		?>
		<script type="text/javascript" src="bundled/list-js/list.min.js"></script>
		<script type="text/javascript">
		var userList = new List('userlist', {
			valueNames: [ 'list-name' ]
		});
		</script>
		<?php
	}
	//geen resultaten
	else {
		echo '<p>Er zijn geen organisaties. Da\'s op zich best gek gegeven dat je wel ingelogd bent. Misschien maar eens naar kijken dan...</p>';
	}
	if (accesslevelcheck('organisaties_beheren_alle')) {
		echo '<p><a href="?do=organisationedit">Organisatie toevoegen</a></p>';
	}
}
//edit organisation
elseif (accesslevelcheck('organisaties_beheren_eigen') && ($_GET['do'] == 'organisationedit') && ($organisatie_gewijzigd != TRUE)) {
	if (!is_numeric($_GET['id'])) {
		echo '<h1>Organisatie toevoegen</h1>';
		$data['allowsignup'] = '1';
	}
	else {
		echo '<h1>Bewerk organisatie</h1>';
		//query om inhoud van tabel te selecteren
		$sql = "SELECT * FROM `".$db['prefix']."organisation`
		WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'";
		//voer query uit
		$result = mysqli_query($db['link'], $sql);
		//als er een of meer rijen zijn
		if (mysqli_num_rows($result) > 0) {
			$data = mysqli_fetch_assoc($result);
		}
	}
	if (!empty($_POST)) {
		$data = $_POST;
	}
	if ($fieldcheck === FALSE) {
		echo '<p class="error">Niet alle velden zijn ingevuld.</p>';
	}
	if ($gebruiker_gewijzigd === FALSE) {
		echo '<p class="error">Kan organisatie niet opslaan.</p>';
	}
	?>
	<form method="post">
	<table>
	<tr><td>Naam:</td><td><input type="text" name="name" value="<?php echo htmlspecialchars($data['name']); ?>"></td></tr>
	<tr><td>Randkleur:</td><td><input type="text" name="bordercolor" value="<?php echo htmlspecialchars($data['bordercolor']); ?>"></td></tr>
	<tr><td>Vulkleur:</td><td><input type="text" name="fillcolor" value="<?php echo htmlspecialchars($data['fillcolor']); ?>"></td></tr>
	<tr><td>E-mail-suffix*:</td><td><input type="text" name="email" value="<?php echo htmlspecialchars($data['email']); ?>"></td></tr>
	<tr><td></td><td><input type="checkbox" name="allowsignup" id="inp-cb-allowsignup" value="true"<?php if ($data['allowsignup'] == '1') echo ' checked="checked"' ?>><label for="inp-cb-allowsignup">Account aanmaken openstellen</label></td></tr>
	<tr><td>Adressen aanvraagformulier:</td><td><textarea name="aanvraagformulier_tekst"><?php echo htmlspecialchars($data['aanvraagformulier_tekst']); ?></textarea></td></tr>
	<tr><td></td><td><input type="submit" value="Opslaan"> <a href="?do=organisationlist">Annuleren</a></td></tr>
	</table>
	</form>
	<p>*) Gedeelte van e-mailadres na @. Gebruikt om te bepalen of gebruikers zichzelf mogen registreren of niet.</p>
	<?php
}
//main page
else {
	echo '<h1>Beheer</h1>';
	if (accesslevelcheck('beheer_eigen')) {
	?>
		<p><a href="edit.php">Asset toevoegen</a></p>
		<p><a href="historie.php">Historie bekijken</a></p>
	<?php 
	}
	//user mgmt
	if (accesslevelcheck('gebruikers_beheren_eigen')) {
	?>
		<p><a href="?do=userlist">Gebruikers weergeven</a></p>
	<?php 
	}
	if (accesslevelcheck('organisaties_beheren_eigen')) { 
	?>
		<p><a href="?do=organisationlist">Organisaties weergeven</a></p>
	<?php
	}
}
?>
</div>
</body>
</html>
