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
include('config.inc.php');

$url_base = 'http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/'));
$url_index = $url_base.'/index.php';

//redirect if not logged in
if (!getuserdata() || !accesslevelcheck('beheer_eigen')) {
	header('Location:'.$url_index);
	exit;
}

$data = array();
$additionaldata = array();
$assettype = NULL;
//insert new
if (!is_numeric($_GET['id'])) {
	//assettype validity is checked later on
	if (is_numeric($_GET['newtype'])) {
		$data['assettype'] = $_GET['newtype'];
	}
	//get map start position
	$cookiecontent = json_decode($_COOKIE['assetwebsite_map'], TRUE);
	$data['latitude'] = is_numeric($cookiecontent[0]['lat']) ? $cookiecontent[0]['lat'] : 52;
	$data['longitude'] = is_numeric($cookiecontent[0]['lng']) ? $cookiecontent[0]['lng'] : 5;
	$data['heading'] = 0;
	//default organisation
	$data['aansturing'] = getuserdata('organisation');
	$data['wegbeheerder'] = getuserdata('organisation');
	$data['onderhoud'] = getuserdata('organisation');
	$data['voeding'] = getuserdata('organisation');
	$data['verbinding'] = getuserdata('organisation');
}
//edit or report
else {
	//get asset from db
	$qry = "SELECT * FROM `".$db['prefix']."asset` 
	WHERE `id` = '" . mysqli_real_escape_string($db['link'], $_GET['id']) . "'
	LIMIT 1";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res) == 1) {
		$data = mysqli_fetch_assoc($res);
		//check edit rights
		if (!accesslevelcheck('beheer_eigen', $data['organisation']) && !accesslevelcheck('beheer_alle')) {
			header('Location:'.$url_index);
			exit;
		}
	}
	else {
		//this should not be reachable
		echo 'Asset not found';
		exit;
	}
}
//get asset type name
if (is_numeric($data['assettype'])) {
	$qry = "SELECT `id`, `name` FROM `".$db['prefix']."assettype` 
	WHERE `id` = '" . mysqli_real_escape_string($db['link'], $data['assettype']) . "'
	LIMIT 1";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res) == 1) {
		$assettype = mysqli_fetch_assoc($res); //array(id =>, name =>)
	}
	else {
		//no valid assettype
		//this should not be reachable
		echo 'Assettype not found';
		exit;
	}

	//get additional fields by asset type
	$qry = "SELECT `".$db['prefix']."addfield`.`id` AS `id`, `".$db['prefix']."addfield`.`name` AS `name`, `".$db['prefix']."addfield`.`fieldclass` AS `fieldclass`, `".$db['prefix']."addfield`.`properties` AS `properties`, `".$db['prefix']."addfield`.`mandatory` AS `mandatory`, `t_addfieldcontent`.`content` AS `content` FROM `".$db['prefix']."addfield` 
	LEFT JOIN 
			(SELECT `addfield`, `content` FROM `".$db['prefix']."addfieldcontent` 
			WHERE `".$db['prefix']."addfieldcontent`.`asset` = '" . $data['id'] . "')
			AS `t_addfieldcontent`
			ON `t_addfieldcontent`.`addfield` = `".$db['prefix']."addfield`.`id`
	WHERE `assettype` = '" . mysqli_real_escape_string($db['link'], $assettype['id']) . "'
	ORDER BY `sort`";
	$res = mysqli_query($db['link'], $qry);
	$additionalfields = array();
	while ($dat2 = mysqli_fetch_assoc($res)) {
		$additionalfields[] = array('a_'.$dat2['id'], $dat2['name'], $dat2['fieldclass'], $dat2['mandatory'], unserialize($dat2['properties']));
		//unserialize serialized field classes
		if (in_array($dat2['fieldclass'], array('drip_standaardtekst', 'drip_bewegwijzering'))) {
			$dat2['content'] = unserialize($dat2['content']);
		}
		$additionaldata['a_'.$dat2['id']] = $dat2['content'];
	}
	//generic fields
	$genericfields = $cfg['fields']['generic'];
}

//function to check submitted fields for validity
require_once('functions/assets.php');

//function to process file upload
//returns FALSE on failure or image filename on success
function upload_file($fieldid) {
	$uploadmap = 'store/'; //TODO hier een generieke setting van maken
	$ext = strtolower(substr($_FILES[$fieldid . '_file']['name'], strrpos($_FILES[$fieldid . '_file']['name'], '.') + 1));
	$file_name = strtolower(md5_file($_FILES[$fieldid . '_file']['tmp_name'])) . '.' . $ext;
	//check trailing slash
	if (substr($uploadmap, -1) != '/') {
		$uploadmap = $uploadmap . '/';
	}
	//controleer of bestand al bestaat
	if (!file_exists($uploadmap . substr($file_name,0,1) . '/' . $file_name)) {
		//bestand bestaat nog niet
		if (!@move_uploaded_file($_FILES[$fieldid . '_file']['tmp_name'], $uploadmap . substr($file_name,0,1) . '/' . $file_name)) {
			return FALSE;
		}
	}
	return $file_name;
}

//process posted data
if (!empty($_POST)) {
	//check fields
	$fieldcheck = TRUE;
	$genericfields_updateqry = array();
	$additionalfields_updateqry = array();
	//generic fields
	foreach ($genericfields as $field) {
		if ($fieldcheck == TRUE) {
			//check field validity
			$fieldcheck = check_submitted_field($field[0], $field[1], $field[2], $_POST[$field[0]], $field[3], $field[4]);
			//build update/insert query
			//set empty string as null
			if (strlen($_POST[$field[0]]) == 0) {
				$genericfields_updateqry[] =  "`" . $field[1] . "` = NULL";
			}
			else {
				$genericfields_updateqry[] =  "`" . $field[1] . "` = '" . mysqli_real_escape_string($db['link'], $_POST[$field[0]]) . "'";
			}
		}
		else {
			break;
		}
	}
	//additional fields
	foreach ($additionalfields as $field) {
		if ($fieldcheck == TRUE) {
			//check field validity
			if (($field[2] == 'drip_standaardtekst') || ($field[2] == 'drip_bewegwijzering')) { 
				$fielddata = array('file' => $_POST[$field[0] . '_file'], 'unsetfile' => $_POST[$field[0] . '_unsetfile'], 'text' => $_POST[$field[0] . '_text']);
			}
			else {
				$fielddata = $_POST[$field[0]];
			}
			$fieldcheck = check_submitted_field($field[0], $field[1], $field[2], $fielddata, $field[3], $field[4]);
			//upload files
			if (($field[2] == 'drip_standaardtekst') || ($field[2] == 'drip_bewegwijzering')) { 
				//if there is a file
				if (($fielddata['unsetfile'] != 'true') && !empty($_FILES[$field[0] . '_file']) && ($_FILES[$field[0] . '_file']['error'] !== UPLOAD_ERR_NO_FILE)) {
					$upload_filename = upload_file($field[0]);
					if ($upload_filename === FALSE) {
						$fieldcheck = FALSE;
						$upload_error = TRUE;
						break;
					}
					else {
						$fielddata['image'] = $upload_filename;
					}
				}
				//keep existing file
				elseif (($fielddata['unsetfile'] != 'true') && !empty($additionaldata[$field[0]]['image'])) {
					$fielddata['image'] = $additionaldata[$field[0]]['image'];
				}
				//cleanup
				else {
					unset($fielddata['image']);
				}
				//cleanup
				unset($fielddata['file']);
				unset($fielddata['unsetfile']);
				if (empty($fieddata['text'])) {
					unset($fielddata['text']);
				}

			}
			//build update/insert query
			if (is_array($fielddata)) {
				if (empty($fielddata)) {
					//do not store an empty array
					$fielddata = NULL;
				}
				else {
					$fielddata = serialize($fielddata);
				}
			}
			$additionalfields_updateqry[] = array('addfield' => substr($field[0], 2), 'content' => $fielddata);
		}
		else {
			break;
		}
	}
	//if checks passed, prepare to update database
	if ($fieldcheck === TRUE) {
		//redirect if not logged in
		if (($_GET['do'] != 'report') && (!getuserdata() || !accesslevelcheck('beheer_eigen'))) {
			header('Location:'.$url_index);
			exit;
		}
		//report false information
		elseif ($_GET['do'] == 'report') {
			//create email body
			$reportbody = '<table>';
			$reportbody .= '<tr><th>veld</th><th>was</th><th>wordt</th></tr>';
			foreach ($genericfields as $field) {
				if (($data[$field[1]] != $_POST[$field[0]]) || ($field[1] == 'code')) {
					$reportbody .= '<tr><td>';
					$reportbody .= htmlspecialchars($field[1]);
					$reportbody .= '</td><td>';
					$reportbody .= htmlspecialchars($data[$field[1]]);
					$reportbody .= '</td><td>';
					$reportbody .= htmlspecialchars($_POST[$field[0]]);
					$reportbody .= '</td></tr>';
				}
			}
			foreach ($additionalfields as $field) {
				if (in_array($field[2], array('drip_standaardtekst', 'drip_bewegwijzering'))) {
					$additionaldata[$field[0]] = $additionaldata[$field[0]]['text'];
					$_POST[$field[0]] = $_POST[$field[0] . '_text'];
				}
				if ($additionaldata[$field[0]] != $_POST[$field[0]]) {
					$reportbody .= '<tr><td>';
					$reportbody .= htmlspecialchars($field[1]);
					$reportbody .= '</td><td>';
					$reportbody .= htmlspecialchars($additionaldata[$field[0]]);
					$reportbody .= '</td><td>';
					$reportbody .= htmlspecialchars($_POST[$field[0]]);
					$reportbody .= '</td></tr>';
				}
			}
			$reportbody .= '</table>';
			$reportbody .= '<p>Opmerking: ' . htmlspecialchars($_POST['comment']) . '</p>';

			//get userdata
			$qry = "SELECT `name`, `email`, `phone` FROM `".$db['prefix']."user`
			WHERE `id` = " . getuserdata('id');
			$res = mysqli_query($db['link'], $qry);
			$userdata = mysqli_fetch_assoc($res);

			$reportbody .= '<p>Ingestuurd door:<br>
			naam: ' . htmlspecialchars($userdata['name']) .
			'<br>e-mail: ' . htmlspecialchars($userdata['email']) .
			'<br>telefoon: ' . htmlspecialchars($userdata['phone']) . '</p>';

			//edit url
			$editurl = $url_base . '/edit.php?id=' . $data['id'];

			//send emails
			if (file_exists('mailconfig.inc.php')) {
				require_once 'mailconfig.inc.php';
				require_once 'functions/send_mail.php';
			}
			else {
				echo 'kan geen e-mails verzenden';
				exit;
			}

			//get users with organisation
			include ('accesslevels.cfg.php');
			$qry = "SELECT `name`, `email` FROM `".$db['prefix']."user`
			WHERE `organisation` = " . $data['organisation'] . "
			AND `accesslevel` >= " . $cfg_accesslevel['beheer_eigen'];
			$res = mysqli_query($db['link'], $qry);
			while ($dat3 = mysqli_fetch_assoc($res)) {
				//send to user who filed report
				$to = $userdata['email'];
				$cfg['mail']['subject']['errorreport'];
				$message = $cfg['mail']['message']['errorreport'];
				$message = str_replace(array('{{NAME}}', '{{USERNAME}}', '{{ERRORREPORT}}', '{{EDITURL}}', '{{SITE_URL}}'), array(htmlspecialchars($userdata['name']), $email, $reportbody, $editurl, $url_base), $message);
				//send email
				send_mail($to, $subject, $message);
			}

			//send to user who filed report
			$to = $userdata['email'];
			$cfg['mail']['subject']['errorreportconfirmation'];
			$message = $cfg['mail']['message']['errorreportconfirmation'];
			$message = str_replace(array('{{NAME}}', '{{USERNAME}}', '{{ERRORREPORT}}', '{{SITE_URL}}'), array(htmlspecialchars($userdata['name']), $email, $reportbody, $url_base), $message);
			//send email
			send_mail($to, $subject, $message);

			//redirect
			header('Location:'.$url_base.'/tabel.php?report=' . urlencode($data['id']));
			exit;
		}
		//update entry
		elseif (is_numeric($data['id'])) {
			//copy old data to history
			$qry = "INSERT INTO `".$db['prefix']."asset_history` (
				`id`,
				`assettype`, ";
			foreach ($genericfields as $field) { //gets list of fields from array
				$qry .= "`" . $field[1] . "`, ";
			}
			$qry .= "`organisation`,
				`user_edit`,
				`date_edit`,
				`user_create`,
				`date_create`
				)
				SELECT
				`id`,
				`assettype`, ";
			foreach ($genericfields as $field) {
				$qry .= "`" . $field[1] . "`, ";
			}
			$qry .= "`organisation`,
				`user_edit`,
				`date_edit`,
				`user_create`,
				`date_create`
				FROM `".$db['prefix']."asset`
				WHERE `id` = '" . $data['id'] . "'";
			$res = mysqli_query($db['link'], $qry);
			//copy additional fields into history
			if ($res === TRUE) {
				$qry = "INSERT INTO `".$db['prefix']."addfieldcontent_history` (
					`id`,
					`asset`,
					`addfield`,
					`content`,
					`user_edit`,
					`date_edit`,
					`user_create`,
					`date_create`
					)
					SELECT
					`id`,
					`asset`,
					`addfield`,
					`content`,
					`user_edit`,
					`date_edit`,
					`user_create`,
					`date_create`
					FROM `".$db['prefix']."addfieldcontent`
					WHERE `asset` = '" . $data['id'] . "'";
				$res = mysqli_query($db['link'], $qry);
			}
			//update generic fields
			if ($res === TRUE) {
				$qry = "UPDATE `".$db['prefix']."asset` SET ";
				$qry .= join(', ', $genericfields_updateqry);
				$qry .= ", `date_edit` = NOW(),
				`user_edit` = '" . getuserdata('id') . "'
				WHERE `id` = '" . $data['id'] . "'";
				$res = mysqli_query($db['link'], $qry);
			}
			//update additional fields
			if ($res === TRUE) {
				foreach ($additionalfields_updateqry as $field) {
					//remove if empty
					//TODO: bepalen of verwijderen wenselijk is, of liever een lege cel bewaren omdat er ooit iets in heeft gestaan
					//update if value
					if (strlen($field['content']) == 0) {
						//set empty string as null
						$content = 'NULL';
					}
					else {
						$content = "'".mysqli_real_escape_string($db['link'], $field['content'])."'";
					}
					$qry = "INSERT INTO `".$db['prefix']."addfieldcontent` SET 
					`content` = " . $content . ", 
					`date_create` = NOW(),
					`user_create` = '" . getuserdata('id') . "',
					`date_edit` = NOW(),
					`user_edit` = '" . getuserdata('id') . "',
					`asset` = '" . $data['id'] . "',
					`addfield` = '" . mysqli_real_escape_string($db['link'], $field['addfield']) . "'
					ON DUPLICATE KEY UPDATE 
					`content` = " . $content . ", 
					`date_edit` = NOW(),
					`user_edit` = '" . getuserdata('id') . "'";
					$res = mysqli_query($db['link'], $qry);
				}
			}
		}
		//insert new
		else {
			//generic fields
			$qry = "INSERT INTO `".$db['prefix']."asset` SET 
			`assettype` = '" . $data['assettype'] . "', ";
			$qry .= join(', ', $genericfields_updateqry);
			$qry .= ", `organisation` = '" . getuserdata('organisation') . "',
			`date_edit` = NOW(),
			`user_edit` = '" . getuserdata('id') . "', 
			`date_create` = NOW(),
			`user_create` = '" . getuserdata('id') . "'";
			$res = mysqli_query($db['link'], $qry);
			$data['id'] = mysqli_insert_id($db['link']);

			//additional fields
			if (($res === TRUE) && is_numeric($data['id'])) {
				foreach ($additionalfields_updateqry as $field) {
					//insert if value
					if (!empty($field['content'])) {
						$qry = "INSERT INTO `".$db['prefix']."addfieldcontent` SET 
						`asset` = '" . $data['id'] . "',
						`addfield` = '" . mysqli_real_escape_string($db['link'], $field['addfield']) . "',
						`content` = '" . mysqli_real_escape_string($db['link'], $field['content']) . "',
						`date_edit` = NOW(),
						`user_edit` = '" . getuserdata('id') . "',
						`date_create` = NOW(),
						`user_create` = '" . getuserdata('id') . "'";
						$res = mysqli_query($db['link'], $qry);
					}
				}
			}
			else {
				$res = FALSE;
			}
		}
		//redirect on completion
		if ($res === TRUE) {
			header('Location:http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/tabel.php?update=' . urlencode($data['id']));
			exit;
		}
	}
	//overload posted data
	if (($fieldcheck !== TRUE) || ($res !== TRUE)) {
		//overload generic fields
		foreach ($genericfields as $field) {
			if (strlen(trim($_POST[$field[0]])) > 0) {
				$data[$field[1]] = $_POST[$field[0]];
			}
		}
		//additional fields
		foreach ($additionalfields as $field) {
			//boolean fields
			if (($field[2] == 'bool') && ($_POST[$field[0]] == 'true')) {
				$additionaldata[$field[0]] = 1;
			}
			//image fields
			elseif (($field[2] == 'drip_standaardtekst') || ($field[2] == 'drip_bewegwijzering')) { 
				$additionaldata[$field[0]] = array('text' => $_POST[$field[0] . '_text']);
			}
			//other fields
			elseif (strlen(trim($_POST[$field[0]])) > 0) {
				$additionaldata[$field[0]] = $_POST[$field[0]];
			}
		}
	}
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Assets - Bewerken</title>
<link rel="stylesheet" type="text/css" href="bundled/jquery-ui/jquery-ui.min.css">
<link rel="stylesheet" type="text/css" href="bundled/leaflet/leaflet.css">
<link rel="stylesheet" type="text/css" href="style/style.css">
<link rel="icon" type="image/png" href="favicon.png">
<script src="bundled/jquery/jquery.min.js"></script>
<script src="bundled/jquery-ui/jquery-ui.min.js"></script>
<script src="bundled/js-cookie/js.cookie.min.js"></script>
<script src="bundled/leaflet/leaflet.js"></script>
<script src="bundled/leaflet-rotatedmarker/leaflet.rotatedMarker.js"></script>
<script src="help.js"></script>
<script src="edit.js"></script>
</head>
<body>

<?php
include('menu.inc.php');
?>

<div id="content">
	<?php
	//edit or new
	if (!is_numeric($data['id']) && empty($assettype)) {
		//new
		?>
		<h1>Nieuwe asset toevoegen</h1>
		<fieldset>
			<legend>Type asset</legend>
			<p>Kies type asset om toe te voegen:</p>
			<ul>
				<?php
				$qry = "SELECT `id`, `name` FROM `".$db['prefix']."assettype` ORDER BY `name`";
				$res = mysqli_query($db['link'], $qry);
				while ($data = mysqli_fetch_assoc($res)) {
					echo '<li><a href="?newtype=' . $data['id'] . '">' . htmlspecialchars($data['name']) . '</a></li>';
				}
				?>
			</ul> 
		</fieldset>
		<?php
	}
	else {
		//editable interface
		if ($fieldcheck === FALSE) {
			echo '<p class="error">Niet alle verplichte velden zijn ingevuld.</p>';
		}
		if (!empty($upload_error)) {
			echo '<p class="error">Kan bestand niet uploaden</p>';
		}
		//title
		if (!is_numeric($data['id']) && !empty($assettype)) {
			?>
			<h1>Nieuwe asset toevoegen</h1>
			<p class="warning">Let op: Eenmaal toegevoegd kan een asset niet meer verwijderd worden! <br>Voeg alleen een nieuwe asset toe als je zeker weet dat deze nog niet op de kaart staat. <br>Controleer daarom eerst in de <a href="tabel.php">tabel</a> of de toe te voegen asset er al is en misschien op de verkeerde plek staat.</p>
			<?php
		}
		elseif ($_GET['do'] == 'report') {
			?>
			<h1>Foutieve informatie melden voor &quot;<?php echo htmlspecialchars($data['code']); ?>&quot;</h1>
			<p class="info">Klopt er iets niet? Pas dan de foutieve informatie aan in onderstaand formulier. Gebruik het opmerkingenveld onderaan als je iets over dit asset wilt melden dat je niet kwijt kunt in de standaardvelden. Klik onderaan op de knop <i>Fout melden</i> om je rapport naar de functioneel beheerder(s) te sturen die aan dit asset gekoppeld zijn. Hierbij wordt je e-mailadres en telefoonnummer (als je dat in je account hebt opgeslagen) kenbaar gemaakt bij deze beheerder(s), zodat ze contact met je kunnen opnemen als dat nodig is. Je ontvangt zelf ook een afschrift van de melding. </p>
			<?php
		}
		else {
			?>
			<h1>Asset &quot;<?php echo htmlspecialchars($data['code']); ?>&quot; bewerken</h1>
			<?php
		}
		//form

		function insert_editable_field($fieldid, $fieldname, $fieldclass, $fielddata, $fieldmandatory = 1, $fieldproperties = '') {
			global $db;
			echo '<tr><td>'.$fieldname;
			if ($fieldmandatory == TRUE) echo '*';
			echo ':</td><td>';
			//TODO: autocomplete toevoegen
			//text
			if (in_array($fieldclass, array('text', 'number', 'latitude', 'longitude', 'heading'))) {
				echo '<input type="'.$fieldclass.'" name="'.$fieldid.'" value="'.htmlspecialchars($fielddata).'"';
				if (in_array($fieldclass, array('latitude', 'longitude', 'heading'))) {
					echo ' id="'.$fieldname.'"';
				}
				if (is_array($fieldproperties)) {
					foreach ($fieldproperties as $key => $val) {
						echo ' ' . $key . '="' . $val . '"';
					}
				}
				echo '>';
			}
			//multiline text
			if ($fieldclass == 'mtext') {
				echo '<textarea name="' . $fieldid . '">' . htmlspecialchars($fielddata) . '</textarea>';
			}
			//status
			if ($fieldclass == 'status') {
				echo '<select name="'.$fieldid.'">';
					$stati = array(1 => 'bestaand', 2 => 'realisatie', 3 => 'buiten gebruik', 4 => 'verwijderd');
					foreach ($stati as $id => $name) {
						echo '<option value="'.$id.'"';
						if ($fielddata == $id) echo ' selected="selected"';
						echo '>'.$name.'</option>';	
					}
				echo '</select>';
			}
			//wegbeheerder
			if ($fieldclass == 'wegbeheerder') {
				echo '<select name="'.$fieldid.'">';
					$qry2 = "SELECT `id`, `name` FROM `".$db['prefix']."organisation` 
					ORDER BY `name`";
					$res2 = mysqli_query($db['link'], $qry2);
					while ($dat2 = mysqli_fetch_assoc($res2)) {
						echo '<option value="'.$dat2['id'].'"';
						if ($fielddata == $dat2['id']) echo ' selected="selected"';
						echo '>'.$dat2['name'].'</option>';	
					}
				echo '</select>';
			}
			//boolean
			if ($fieldclass == 'bool') {
				echo '<input type="radio" value="1" name="'.$fieldid.'" id="radio_'.$fieldid.'_true"';
				if ($fielddata == '1') echo ' checked="checked"';
				echo '><label for="radio_'.$fieldid.'_true">Ja<label> ';
				echo '<input type="radio" value="0" name="'.$fieldid.'" id="radio_'.$fieldid.'_false"';
				if ($fielddata != '1') echo ' checked="checked"';
				echo '><label for="radio_'.$fieldid.'_false">Nee<label> ';
			}
			//vms text
			if (($fieldclass == 'drip_standaardtekst') || ($fieldclass == 'drip_bewegwijzering')) {
				?>
				<span class="defaulttextcontrols">
					<?php
					if ($_GET['do'] != 'report') {
						//we don't want file uploads in a report
						?>
						Afbeelding (PNG-bestand max. 100 kB):<br>
						<input type="file" name="<?php echo $fieldid; ?>_file" accept=".png, .jpg, .jpeg"><br>
						<?php
					}
					if ((!empty($fielddata['image'])) && (is_file('store/'.substr($fielddata['image'],0,1).'/'.$fielddata['image']))) {
						if ($_GET['do'] != 'report') {
							//same as above
							?>
							<input type="checkbox" name="<?php echo $fieldid; ?>_unsetfile" id="<?php echo $fieldid; ?>_unsetfile" value="true"> <label for="<?php echo $fieldid; ?>_unsetfile">Afbeelding verwijderen</label><br>
							<?php
						}
						?>
						<img src="<?php echo 'store/'.substr($fielddata['image'],0,1).'/'.$fielddata['image']; ?>" style="max-width: 300px"><br>
						<?php
					}
					?>
					<textarea name="<?php echo $fieldid; ?>_text"><?php if (!empty($fielddata)) echo htmlspecialchars($fielddata['text']); ?></textarea>
				</span>
				<?php
			}
			echo '</td></tr>';
		}
		?>
		<form method="post" enctype="multipart/form-data">
		<div style="float:left;">
		<table>
		<tr><td>type asset*:</td><td><input type="text" disabled value="<?php echo htmlspecialchars($assettype['name']); ?>"><?php if (!is_numeric($data['id']) && !empty($assettype)) echo '<br><a href="?">type wijzigen</a>'; ?></td></tr>
		<?php
		//generic fields
		foreach ($genericfields as $field) {
			insert_editable_field($field[0], $field[1], $field[2], $data[$field[1]], $field[3], $field[4]);
		}
		//additional fields
		echo '<tr><td></td><td></td></tr>';
		foreach ($additionalfields as $field) {
			insert_editable_field($field[0], $field[1], $field[2], $additionaldata[$field[0]], $field[3], $field[4]);
		}
		
		?>
		</table>
		<p>*) geeft een verplicht veld aan.</p>
		</div>
		<div style="float:left; margin-left: 8px; margin-bottom: 16px;">
			<p>Locatie op de kaart:</p>
			<div id="minimap" style="width: 400px; height: 400px;"></div>
		</div>
		<div style="clear:both;"></div>
		<input type="hidden" id="assettype" value="<?php echo $data['assettype']; ?>">
		<?php
		//comment field for reports
		if ($_GET['do'] == 'report') {
			echo 'Opmerkingen:<br>';
			echo '<textarea name="comment">';
			echo htmlspecialchars($_POST['comment']);
			echo '</textarea><br>';
		}
		?>
		<input type="submit" value="<?php echo ($_GET['do'] == 'report') ? 'Fout melden' : 'Opslaan'; ?>"> <a href="tabel.php">Annuleren (terug naar tabel)</a>
		</form> 
		<?php
		}
	?>

</div>
</body>
</html>
