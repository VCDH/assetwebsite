<?php
/*
 	fietsviewer - grafische weergave van fietsdata
	Copyright (C) 2018 Gemeente Den Haag, Netherlands
	assetwebsite - viewer en aanvraagformulier voor verkeersmanagementassets
    Copyright (C) 2020, 2022 Gemeente Den Haag, Netherlands
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

require_once('getuserdata.inc.php');
require_once('functions/get_token.php');
require_once('functions/reset_password.php');
require('dbconnect.inc.php');

/*
* process logout
*/
function user_logout() {
	require('dbconnect.inc.php');
	require('config.inc.php');

	//invalidate token
	$qry = "DELETE FROM `".$db['prefix']."user_login_tokens`
	WHERE `user_id` = '" . mysqli_real_escape_string($db['link'], getuserdata('id')) . "'
	AND `token` = '" . mysqli_real_escape_string($db['link'], getuserdata('token')) . "'";
	mysqli_query($db['link'], $qry);
	//unset cookie
	setcookie($cfg['cookie']['name'], '', time() - 3600, '/');
	return TRUE;
}

/*
* process login
*/
function user_login($email, $password) {
	require('dbconnect.inc.php');
	require('config.inc.php');
	//hash password
	include_once('bundled/password_compat/lib/password.php');
	//get password by username
	$qry = "SELECT `id`, `password` FROM `".$db['prefix']."user` 
	WHERE `email` = '" . mysqli_real_escape_string($db['link'], $email) . "' 
	AND `accesslevel` >= 1";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res) == 1) {
		//user exists
		$data = mysqli_fetch_assoc($res);
		//check password
		if (password_verify($password, $data['password'])) {
			//generate token
			$token = get_token(32);
			//add token to db
			$qry = "INSERT INTO `".$db['prefix']."user_login_tokens` SET 
			`user_id` = '" . mysqli_real_escape_string($db['link'], $data['id']) . "',
			`token` = '" . mysqli_real_escape_string($db['link'], $token) . "',
			`date_create` = NOW(),
			`date_lastchange` = NOW(),
			`ip` = '" . mysqli_real_escape_string($db['link'], $_SERVER['REMOTE_ADDR']) . "'";
			if (!mysqli_query($db['link'], $qry)) {
				return FALSE;
			}
			//set last login
			$qry = "UPDATE `".$db['prefix']."user` SET 
			`lastlogin` = NOW()
			WHERE `id` = '" . mysqli_real_escape_string($db['link'], $data['id']) . "'";
			mysqli_query($db['link'], $qry);

			//set cookie
			setcookie($cfg['cookie']['name'], serialize(array($data['id'], $token)), time() + $cfg['cookie']['expire'], '/');

			return TRUE;
		}
	}
	return FALSE;
}

/*
* process sign up for new account
*/
function user_signup($email, $organisation, $name, $phone) {
	require('dbconnect.inc.php');
	require('config.inc.php');
	//check if user exits
	$qry = "SELECT `id` FROM `".$db['prefix']."user` WHERE
	`email` LIKE '" . mysqli_real_escape_string($db['link'], $email) . "'
	LIMIT 1";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res) == 1) {
		//there is a user
		return 'user_exists';
	}
	//check if there is a matching organisation or check if the provided organisation id is matching
	if (is_numeric($organisation)) {
		$qry = "SELECT `id` FROM `".$db['prefix']."organisation` WHERE
		`id` = '" . mysqli_real_escape_string($db['link'], $organisation) . "'
		LIMIT 1";
		$res = mysqli_query($db['link'], $qry);
		if (mysqli_num_rows($res) == 1) {
			//there is one organisation
			$row = mysqli_fetch_row($res);
			$organisation_id = $row[0];
		}
		else {
			return 'invalid_organisation';
		}
	}
	else {
		$organisation = substr($email, strpos($email, '@')+1);
		$qry = "SELECT `id` FROM `".$db['prefix']."organisation` WHERE
		`email` = '" . mysqli_real_escape_string($db['link'], $organisation) . "'";
		$res = mysqli_query($db['link'], $qry);
		if (mysqli_num_rows($res) == 1) {
			//there is one organisation
			$row = mysqli_fetch_row($res);
			$organisation_id = $row[0];
		}
		elseif (mysqli_num_rows($res) > 1) {
			//go to signup page 2 if there are multiple matching organisations
			return 'select_organisation';
		}
		else {
			return 'invalid_organisation';
		}
	}
	//signup user
	$qry = "INSERT INTO `".$db['prefix']."user` SET
	`email` = '" . mysqli_real_escape_string($db['link'], $email) . "',
	`name` = '" . mysqli_real_escape_string($db['link'], $name) . "',
	`phone` = '" . mysqli_real_escape_string($db['link'], $phone) . "',
	`organisation` = '" . $organisation_id . "',
	`accesslevel` = 1,
	`user_edit` = 0,
	`user_create` = 0";
	$res = mysqli_query($db['link'], $qry);
	if ($res === FALSE) {
		return FALSE;
	}
	//send email
	reset_password($email, TRUE);
	return TRUE;
}

/*
* process logout request
*/
if ($_GET['a'] == 'logout') {
	user_logout();
	header('Location: index.php');
}

/*
* process post requests
*/
$messages = array();
if (!empty($_POST)) {
	/*
	* process lost password request
	*/
	if ($_GET['do'] == 'lostpass') {
		//check if not an empty field
		if (empty($_POST['email'])) {
			$messages[] = 'empty';
			$lostpasssuccess = FALSE;
		}
		else {
			//send email
			$lostpasssuccess = reset_password($_POST['email']);
		}
	}
	/*
	* process signup request
	*/
	elseif ($_GET['do'] == 'signup') {
		//check if not an empty field
		if (empty($_POST['email'])) {
			$messages[] = 'empty';
			$signupresult = FALSE;
		}
		else {
			$signupresult = user_signup($_POST['email'], $_POST['organisation'], $_POST['name'], $_POST['phone']);
			if ($signupresult === TRUE) {
				$messages[] = 'signup';
			}
		}
	}
	/*
	* process login request
	*/
	elseif (user_login($_POST['email'], $_POST['password']) === TRUE) {
		//redirect to index
		header('Location: index.php');
	}
	else {
		//show error
		$messages[] = 'login';
	}
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Assets - Aanmelden</title>
<link rel="stylesheet" type="text/css" href="bundled/jquery-ui/jquery-ui.min.css">
<link rel="stylesheet" type="text/css" href="style/style.css">
<link rel="icon" type="image/png" href="favicon.png">
<script src="bundled/jquery/jquery.min.js"></script>
<script src="bundled/jquery-ui/jquery-ui.min.js"></script>
<script src="bundled/js-cookie/js.cookie.min.js"></script>
<script src="help.js"></script>
</head>
<body>
	
<?php
include('menu.inc.php');
?>

<div id="content">

<?php
//signup page
if (($_GET['do'] == 'signup') && (!in_array('signup', $messages))) {
	echo '<h1>Registreren</h1>'; 

	if ($signupresult === 'select_organisation') {
		echo '<p class="info">Er zijn meerdere organisatie(onderdelen) beschikbaar. Kies het juiste organisatieonderdeel; na registratie kun je dit zelf niet meer wijzigen.</p>';
	}
	if ($signupresult === 'invalid_organisation') {
		echo '<p class="error">Kan niet registreren. E-mailadres behoort niet tot een toegelaten organisatie.</p>';
	}
	if ($signupresult === 'user_exists') {
		echo '<p class="error">Er bestaat al een account met het opgegeven e-mailadres. Gebruik de optie <a href="?do=lostpass">wachtwoord vergeten</a> op een nieuw wachtwoord aan te vragen.</p>';
	}
	if ($signupresult === FALSE) {
		echo '<p class="error">Er kon geen e-mail met wachtwoord worden verzonden. Probeer het later nogmaals. Neem contact op met de beheerder van deze website als het probleem zich blijft voordoen.</p>';
	}
	?> 
	
	<form method="post">
	<table>
	<tr><td>Naam:</td><td><input type="text" name="name"<?php echo ' value="' . htmlspecialchars($_POST['name']) . '"'; if ($signupresult === 'select_organisation') { echo ' readonly'; }?>></td></tr>
	<tr><td>E-mailadres:</td><td><input type="text" name="email"<?php echo ' value="' . htmlspecialchars($_POST['email']) . '"'; if ($signupresult === 'select_organisation') { echo ' readonly'; }?>></td></tr>
	<tr><td>Telefoonnummer:</td><td><input type="text" name="phone"<?php echo ' value="' . htmlspecialchars($_POST['phone']) . '"'; if ($signupresult === 'select_organisation') { echo ' readonly'; }?>></td></tr>
	<?php
	//provide possible organisation list
	if ($signupresult === 'select_organisation') {
		echo '<tr><td>Organisatie:</td><td>';
		echo '<select name="organisation">';
		echo '<option value="0">(geen)</option>';
		$organisation = substr($_POST['email'], strpos($_POST['email'], '@')+1);
		$qry = "SELECT `id`, `name` FROM `".$db['prefix']."organisation` WHERE
		`email` = '" . mysqli_real_escape_string($db['link'], $organisation) . "'";
		
		$res = mysqli_query($db['link'], $qry);
		while ($row = mysqli_fetch_row($res)) {
			echo '<option value="' . $row[0] . '">' . htmlspecialchars($row[1]) . '</option>';
		}
		echo '</select>';
		echo '</td></tr>';
	}
	?>
	<tr><td></td><td><input type="submit" value="Registreren"></td></tr>
	</table>
	</form> 
	<p><a href="?">Annuleren</a></p>
	<?php 
	
}
//lost password page
elseif ($_GET['do'] == 'lostpass') {
	echo '<h1>Wachtwoord vergeten</h1>';
	
	if (in_array('empty', $messages)) {
		echo '<p class="info">Vul een gebruikersnaam in.</p>';
	}
	elseif ($lostpasssuccess === FALSE) {
		echo '<p class="error">Kan wachtwoord niet aanvragen.</p>';
	}
	elseif ($lostpasssuccess === TRUE) {
		echo '<p class="success">Wanneer er een account bij het opgegeven e-mailadres is geregisteerd, is een nieuw wachtwoord naar dit e-mailadres gezonden. Het kan enkele minuten duren voordat het nieuwe wachtwoord wordt ontvangen. Niets ontvangen? Kijk dan ook even in je spam-map!</p>';
	}
	
	?>
	<p>Wachtwoord vergeten? Vul hieronder je e-mailadres in om een nieuw wachtwoord toegestuurd te krijgen.</p>
	<form method="POST">
	<table class="invisible">
		<tr><td>E-mailadres</td><td><input type="text" name="email"></td></tr>
		<tr><td></td><td><input type="submit" value="Nieuw wachtwoord aanvragen"></td></tr>
	</table>
	</form>
	<p><a href="?">Annuleren</a></p>
	<?php
}

//main login page
else {
	echo '<h1>Aanmelden</h1>';

	if (in_array('login', $messages)) {
		echo '<p class="error">E-mailadres/wachtwoord onjuist, het account bestaat niet of het account is geblokkeerd.</p>';
	}
	if (in_array('signup', $messages)) {
		echo '<p class="success">Registratie succesvol. Er is een e-mail met wachtwoord gestuurd naar het opgegeven e-mailadres. Na aanmelden kan het gegenereerde wachtwoord desgewenst worden gewijzigd.</p>';
	}
	
	?>
	<form method="POST">
	<table class="invisible">
		<tr><td>E-mailadres</td><td><input type="text" name="email"></td></tr>
		<tr><td>Wachtwoord</td><td><input type="password" name="password"></td></tr>
		<tr><td></td><td><input type="submit" value="Aanmelden"></td></tr>
	</table>
	</form>
	<p><a href="?do=signup">Registreren</a></p>
	<p><a href="?do=lostpass">Wachtwoord vergeten</a></p>

	<?php
}
?>

</div>
</body>
</html>