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
require_once 'getuserdata.inc.php';
logincheck();
require 'dbconnect.inc.php';
require 'config.inc.php';

/*
* process password change
*/
if (($_GET['do'] == 'password') && (!empty($_POST))) {
    //check if both new passwords are equal
    if ($_POST['new_password1'] == $_POST['new_password2']) {
        //check new password length
		if (strlen($_POST['new_password1']) >= $cfg['account']['pass_minlength']) {
            //check old password
            include('bundled/password_compat/lib/password.php');
            //get password by username
            $qry = "SELECT `id`, `password` FROM `".$db['prefix']."user` WHERE
            `id` = '" . getuserdata('id') . "'";
            $res = mysqli_query($db['link'], $qry);
            if (mysqli_num_rows($res) == 1) {
                //user exists
                $data = mysqli_fetch_assoc($res);
                //check password
                if (!password_verify($_POST['old_password'], $data['password'])) {
                    //password incorrect
                    $oud_wachtwoord_fout = TRUE;
                }
                else {
                    //store new password
                    $new_password = password_hash($_POST['new_password1'], PASSWORD_DEFAULT);
                    //query
                    $sql = "UPDATE `".$db['prefix']."user`
                    SET `password` = '" . mysqli_real_escape_string($db['link'], $new_password) . "'
                    WHERE `id` = '" . getuserdata('id') . "'";
                    $wachtwoord_gewijzigd = mysqli_query($db['link'], $sql);
                }
            }
		}
		else {
			//password length insufficient
			$wachtwoord_lengte = TRUE;
		}
    }
    else {
        //passwords don't match
        $nieuw_wachtwoord_fout = TRUE;
    }
}
/*
* process user details change
*/
elseif (($_GET['do'] == 'userprofile') && (!empty($_POST))) {
	$fieldcheck = TRUE;
	//check fields
	if (empty($_POST['name'])) $fieldcheck = FALSE;
	//save data
	if ($fieldcheck == TRUE) {
		//query om rij aan te passen
		$qry = "UPDATE `".$db['prefix']."user`
		SET `name` = '" . mysqli_real_escape_string($db['link'], $_POST['name']) . "',
		`phone` = '" . mysqli_real_escape_string($db['link'], $_POST['phone']) . "'
        WHERE `id` = '" . getuserdata('id') . "'";
		//voer query uit
		$userprofile_gewijzigd = mysqli_query($db['link'], $qry);
	}
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Assets - Account</title>
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
    //change password
    if (($_GET['do'] == 'password') && ($wachtwoord_gewijzigd !== TRUE)) {
        echo '<h1>Wachtwoord wijzigen</h1>';
        echo '<p>Geef je huidige wachtwoord en tweemaal het nieuwe wachtwoord op om je wachtwoord te wijzigen. Het nieuwe wachtwoord moet minstens ' . $cfg['account']['pass_minlength'] . ' tekens lang zijn.';
        //messages
        if ($nieuw_wachtwoord_fout === TRUE) {
			echo '<p class="error">De ingevulde nieuwe wachtwoorden zijn niet gelijk.</p>';
		}
		if ($oud_wachtwoord_fout === TRUE) {
			echo '<p class="error">De oude wachtwoord is niet juist.</p>';
		}
		if ($wachtwoord_lengte === TRUE) {
			echo '<p class="error">Het nieuwe wachtwoord is te kort.</p>';
		}
		?>
		
		<form method="post">
		<table class="invisible">
            <tr><td>Oud wachtwoord:</td><td><input type="password" name="old_password"></td></tr>
            <tr><td>Nieuw wachtwoord:</td><td><input type="password" name="new_password1"></td></tr>
            <tr><td>Herhaal wachtwoord:</td><td><input type="password" name="new_password2"></td></tr>
            <tr><td></td><td><input type="submit" value="Wijzig wachtwoord"> <a href="?">Annuleren</a></td></tr>
		</table>
		</form>
		<?php
    }

    //change personal details
    elseif (($_GET['do'] == 'userprofile') && ($userprofile_gewijzigd !== TRUE)) {
        echo '<h1>Gebruikersprofiel wijzigen</h1>';
        echo '<p>Wijzig via deze pagina de gegevens in je gebruikersprofiel. Deze worden op dit moment nergens voor gebruikt, maar kunnen handig zijn voor de beheerder binnen jouw organisatie.</p>';
        //check if post data or get form database
		if (!empty($_POST)) {
			$data['name'] = htmlspecialchars($_POST['name']);
			$data['phone'] = htmlspecialchars($_POST['phone']);
		}
		else {
            //get data from database
			$sql = "SELECT
			`name`, `phone`
			FROM `".$db['prefix']."user`
			WHERE `id` = '" . getuserdata('id') . "'";
			$result = mysqli_query($db['link'], $sql);
			if (mysqli_num_rows($result)) {
				$row = mysqli_fetch_row($result);
				$data['name'] = htmlspecialchars($row[0]);
				$data['phone'] = htmlspecialchars($row[1]);
			}
		}
        //messages
		if ($naam_fout === TRUE) {
			echo '<p class="error">Naam kan niet leeg zijn</p>';
		}
		?>
		
		<form method="post">
		<table>
		<tr><td>Naam:</td><td><input type="text" name="name" value="<?php echo $data['name']; ?>"></td></tr>
		<tr><td>Telefoonnummer:</td><td><input type="tel" name="phone" value="<?php echo $data['phone']; ?>"></td></tr>
		<tr><td></td><td><input type="submit" value="Opslaan"> <a href="?">Annuleren</a></td></tr>
		</table>
		</form>
		<?php
	}


    //query user info for main view
    else {
        echo '<h1>Account</h1>';
        echo '<p>Via deze pagina kun je je wachtwoord en andere gegevens wijzigen.</p>';
        
        $qry = "SELECT `".$db['prefix']."user`.`email` AS `email`, `".$db['prefix']."user`.`phone` AS `phone`, `".$db['prefix']."user`.`name` AS `name`, `".$db['prefix']."organisation`.`name` AS `organisation` FROM `".$db['prefix']."user`
        LEFT JOIN `".$db['prefix']."organisation`
        ON `".$db['prefix']."user`.`organisation` = `".$db['prefix']."organisation`.`id`
		WHERE `".$db['prefix']."user`.`id` = '" . getuserdata('id') . "'";
        $res = mysqli_query($db['link'], $qry);
        if ($data = mysqli_fetch_assoc($res)) {
            ?>
                <table>
                    <tr><td>e-mailadres</td><td><?php echo htmlspecialchars($data['email']); ?></td><td></td></tr>
                    <tr><td>wachtwoord</td><td>********</td><td><a href="?do=password">wachtwoord wijzigen</a></td></tr>
                    <tr><td>naam</td><td><?php echo htmlspecialchars($data['name']); ?></td><td rowspan="2"><a href="?do=userprofile">gegevens wijzigen</a></td></tr>
                    <tr><td>telefoonnummer</td><td><?php echo htmlspecialchars($data['phone']); ?></td></tr>
                    <tr><td>organisatie</td><td><?php echo htmlspecialchars($data['organisation']); ?></td><td></td></tr>
                </table>
            <?php
        }
        else {
            echo '<p class="error">Er is een fout opgetreden bij het ophalen van gebruikersdata.</p>';
        }
    }
	?>
</div>
</body>
</html>
