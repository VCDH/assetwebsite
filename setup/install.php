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

if (!file_exists('../dbconnect.inc.php')) {
	echo '* Geen ../dbconnect.inc.php beschikbaar.' . PHP_EOL;
	
	if (file_exists('../dbconnect.inc.php.example')) {
		if (copy('../dbconnect.inc.php.example', '../dbconnect.inc.php')) {
			echo '* ../dbconnect.inc.php aangemaakt.' . PHP_EOL;
			echo '  Controleer de instellingen in ../dbconnect.inc.php en voer de installatie opnieuw uit' . PHP_EOL;
		}
	}

	echo '* Installatie afgebroken';
	exit;
}

include('../dbconnect.inc.php');
include_once('../bundled/password_compat/lib/password.php');

$db['link'] = mysqli_connect($db['server'], $db['username'], $db['password']);

if ($db['link'] === FALSE) {
	echo '* Kan niet verbinden met database' . PHP_EOL;
	echo '  Controleer de instellingen in ../dbconnect.inc.php' . PHP_EOL;
	echo '* Installatie afgebroken';
	exit;
}

$qry = "CREATE DATABASE IF NOT EXISTS `".$db['database']."`
CHARACTER SET 'utf8' 
COLLATE 'utf8_general_ci'";
$res = mysqli_query($db['link'], $qry);

if ($res === FALSE) {
	echo '* Kan database niet aanmaken' . PHP_EOL;
	echo '  Oorzaak: ' . mysqli_error($db['link']) . PHP_EOL;
	echo '* Installatie afgebroken';
	exit;
}
else {
	echo '* Database aangemaakt of al beschikbaar' . PHP_EOL;
}

$db['link'] = mysqli_connect($db['server'], $db['username'], $db['password'], $db['database']);
mysqli_set_charset($db['link'], 'utf8');

$qry = array();

$qry[] = "CREATE TABLE `".$db['prefix']."organisation` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`email` VARCHAR(255) NULL,
	`name` VARCHAR(64) NOT NULL,
	`bordercolor` VARCHAR(6),
	`fillcolor` VARCHAR(6),
	`allowsignup` BOOLEAN NOT NULL DEFAULT 0,
	`aanvraagformulier_tekst` TEXT NULL,
	`user_edit` INT NOT NULL,
	`date_edit` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`user_create` INT NOT NULL,
	`date_create` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
	) 
	ENGINE=MyISAM";

$qry[] = "INSERT INTO `".$db['prefix']."organisation` SET
	`id` = 1,
	`email` = 'localhost',
	`name` = 'root organisation',
	`allowsignup` = 1,
	`user_edit` = 0,
	`user_create` = 0";

$qry[] = "CREATE TABLE `".$db['prefix']."user` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE KEY,
	`email` VARCHAR(255) NOT NULL,
	`password` VARCHAR(64) NOT NULL,
	`token` MEDIUMTEXT,
	`name` TINYTEXT,
	`phone` TINYTEXT NULL DEFAULT NULL,
	`organisation` INT UNSIGNED NOT NULL,
	`accesslevel` TINYINT UNSIGNED NOT NULL DEFAULT 0,
	`lastlogin` DATETIME NOT NULL DEFAULT NOW(),
	`user_edit` INT NOT NULL,
	`date_edit` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`user_create` INT NOT NULL,
	`date_create` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`email`)
	)
	ENGINE=MyISAM";

$qry[] = "INSERT INTO `".$db['prefix']."user` SET
	`id` = 1,
	`email` = 'root@localhost',
	`password` = '" . password_hash('password', PASSWORD_DEFAULT) . "',
	`name` = 'root',
	`organisation` = 1,
	`accesslevel` = 255,
	`user_edit` = 0,
	`user_create` = 0";

$qry[] = "CREATE TABLE `".$db['prefix']."user_login_tokens` (
	`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE KEY,
	`user_id` INT UNSIGNED NOT NULL,
	`token` TINYTEXT NOT NULL,
	`date_create` DATETIME NOT NULL,
	`date_lastchange` DATETIME NOT NULL,
	`ip` TINYTEXT NOT NULL,
	`device` TINYTEXT,
	PRIMARY KEY (`id`)
	)
	ENGINE=MyISAM";

$qry[] = "CREATE TABLE `".$db['prefix']."asset` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`assettype` INT NOT NULL,
	`code` TINYTEXT NOT NULL,
	`naam` TINYTEXT NULL,
	`latitude` DOUBLE SIGNED NOT NULL DEFAULT 52,
	`longitude` DOUBLE SIGNED NOT NULL DEFAULT 4,
	`heading` INT(3) DEFAULT 0,
	`status` INT NOT NULL DEFAULT 0,
	`aansturing` INT NOT NULL DEFAULT 0,
	`wegbeheerder` INT NOT NULL DEFAULT 0,
	`onderhoud` INT NOT NULL DEFAULT 0,
	`voeding` INT NOT NULL DEFAULT 0,
	`verbinding` INT NOT NULL DEFAULT 0,
	`leverancier` TINYTEXT NULL,
	`bouwjaar` YEAR(4) NULL,
	`oorspronkelijk_bouwjaar` YEAR(4) NULL,
	`memo` TEXT NULL,
	`organisation` INT UNSIGNED NOT NULL,
	`user_edit` INT NOT NULL,
	`date_edit` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`user_create` INT NOT NULL,
	`date_create` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
	)
	ENGINE=MyISAM";

$qry[] = "CREATE TABLE `".$db['prefix']."asset_history` (
	`i` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`id` INT UNSIGNED NOT NULL,
	`assettype` INT NOT NULL,
	`code` TINYTEXT NOT NULL,
	`naam` TINYTEXT NULL,
	`latitude` DOUBLE SIGNED NOT NULL DEFAULT 52,
	`longitude` DOUBLE SIGNED NOT NULL DEFAULT 4,
	`heading` INT(3) DEFAULT 0,
	`status` INT NOT NULL DEFAULT 0,
	`aansturing` INT NOT NULL DEFAULT 0,
	`wegbeheerder` INT NOT NULL DEFAULT 0,
	`onderhoud` INT NOT NULL DEFAULT 0,
	`voeding` INT NOT NULL DEFAULT 0,
	`verbinding` INT NOT NULL DEFAULT 0,
	`leverancier` TINYTEXT NULL,
	`bouwjaar` YEAR(4) NULL,
	`oorspronkelijk_bouwjaar` YEAR(4) NULL,
	`memo` TEXT NULL,
	`organisation` INT UNSIGNED NOT NULL,
	`user_edit` INT NOT NULL ,
	`date_edit` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`user_create` INT NOT NULL,
	`date_create` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`i`)
	)
	ENGINE=MyISAM";

$qry[] = "CREATE TABLE `".$db['prefix']."assettype` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`name` TINYTEXT NULL,
	`public` BOOLEAN NOT NULL DEFAULT 1,
	`lineshape` TINYTEXT NULL,
	`user_edit` INT NOT NULL,
	`date_edit` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`user_create` INT NOT NULL,
	`date_create` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
	)
	ENGINE=MyISAM";

$qry[] = "INSERT INTO `".$db['prefix']."assettype` 
	(`id`, `name`, `public`, `lineshape`, `user_edit`, `user_create`)
	VALUES
	(1, 'DRIP', 1, '1,4 1,15; 0,15 15,15; 15,4 15,15; 0,5 2,5; 3,5 8,0; 7,0 12,5; 4,5 7,2; 8,2 11,5; 13,5 15,5', 0, 0), 
	(2, 'CAM', 0, '1,8 7,2; 8,2 14,8; 14,8 8,14; 7,14 1,8', 0, 0), 
	(3, 'VRI', 1, '1,0 1,15; 0,1 15,1; 15,1, 15,15; 1,15 15,15', 0, 0), 
	(4, 'TDI', 1, '', 0, 0)";

$qry[] = "CREATE TABLE `".$db['prefix']."addfield` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`assettype` INT UNSIGNED NOT NULL,
	`name` VARCHAR(64) NOT NULL,
	`fieldclass` VARCHAR(32) NOT NULL,
	`properties` TEXT NULL,
	`mandatory` BOOLEAN,
	`sort` INT UNSIGNED NOT NULL DEFAULT 0,
	`user_edit` INT NOT NULL,
	`date_edit` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`user_create` INT NOT NULL,
	`date_create` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY (`assettype`, `name`)
	)
	ENGINE=MyISAM";

$qry[] = "CREATE TABLE `".$db['prefix']."addfieldcontent` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`asset` INT UNSIGNED NOT NULL,
	`addfield` INT UNSIGNED NOT NULL,
	`content` TEXT NULL,
	`user_edit` INT NOT NULL,
	`date_edit` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`user_create` INT NOT NULL,
	`date_create` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	UNIQUE KEY (`asset`, `addfield`)
	)
	ENGINE=MyISAM";

$qry[] = "CREATE TABLE `".$db['prefix']."addfieldcontent_history` (
	`i` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`id` INT UNSIGNED NOT NULL,
	`asset` INT UNSIGNED NOT NULL,
	`addfield` INT UNSIGNED NOT NULL,
	`content` TEXT NULL,
	`user_edit` INT NOT NULL,
	`date_edit` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`user_create` INT NOT NULL,
	`date_create` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`i`)
	)
	ENGINE=MyISAM";

$qry[] = "INSERT INTO `".$db['prefix']."addfield` 
	(`assettype`, `name`, `fieldclass`, `properties`, `mandatory`, `sort`, `user_edit`, `user_create`)
	VALUES
	(1, 'project', 'text', 'autocomplete_own', 0, 1, 0, 0), 
	(1, 'weg', 'text', NULL, 0, 2, 0, 0), 
	(1, 'richting', 'text', NULL, 0, 3, 0, 0), 
	(1, 'hectometer', 'text', NULL, 0, 4, 0, 0), 
	(1, 'type', 'text', 'autocomplete', 1, 5, 0, 0), 
	(1, 'template', 'text', 'autocomplete', 0, 6, 0, 0), 
	(1, 'standaardtekst', 'drip_standaardtekst', NULL, 0, 7, 0, 0), 
	(1, 'bewegwijzering', 'drip_bewegwijzering', NULL, 0, 8, 0, 0), 
	(2, 'PTZ', 'bool', NULL, 1, 1, 0, 0), 
	(3, 'iVRI', 'bool', NULL, 1, 1, 0, 0), 
	(3, 'TLC-ID', 'text', NULL, 0, 2, 0, 0), 
	(4, 'iVRI', 'bool', NULL, 1, 1, 0, 0), 
	(4, 'TLC-ID', 'text', NULL, 0, 2, 0, 0)";

$qry[] = "CREATE TABLE IF NOT EXISTS `".$db['prefix']."requestforms` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`name` TINYTEXT NOT NULL,
	`shared` BOOLEAN NOT NULL DEFAULT 0,
	`archive` BOOLEAN NOT NULL DEFAULT 0,
	`organisation` INT UNSIGNED NOT NULL,
	`user_edit` INT NOT NULL,
	`date_edit` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`user_create` INT NOT NULL,
	`date_create` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
	)
	ENGINE=MyISAM";

$qry[] = "CREATE TABLE IF NOT EXISTS `".$db['prefix']."requestformassets` (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`requestform` INT UNSIGNED NOT NULL,
	`asset` INT UNSIGNED NULL,
	`image` TINYTEXT NOT NULL,
	`user_edit` INT NOT NULL,
	`date_edit` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`user_create` INT NOT NULL,
	`date_create` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
	)
	ENGINE=MyISAM";

foreach($qry as $qry_this) {
	$res = @mysqli_query($db['link'], $qry_this);
	//get table name
	preg_match('/(.*)\h+.+`(.+)`.+/U', $qry_this, $table_name);
	$qry_type = strtoupper($table_name[1]);
	$table_name = $table_name[2];
	//echo result
	if ($res !== TRUE) {
		switch ($qry_type) {
			case 'CREATE':
				echo '* Kan tabel `' . $table_name . '` niet aanmaken.' . PHP_EOL;
				break;
			case 'INSERT':
				echo '* Kan rijen op `' . $table_name . '` niet invoegen.' . PHP_EOL;
				break;
			default:
				echo '* ' . $qry_type .' op `' . $table_name . '` niet uitgevoerd.' . PHP_EOL;
		}
		echo '  Oorzaak: ' . mysqli_error($db['link']) . PHP_EOL;
	}
	else {
		switch ($qry_type) {
			case 'CREATE':
				echo '* Tabel `' . $table_name . '` aangemaakt.' . PHP_EOL;
				break;
			case 'INSERT':
				echo '* Rijen op `' . $table_name . '` ingevoegd.' . PHP_EOL;
				break;
			default:
				echo '* ' . $qry_type .' op `' . $table_name . '` uitgevoerd.' . PHP_EOL;
		}
	}
}


//create store dir structure
if (!is_dir('../store')) {
	mkdir('../store');
	$subdirs = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F');
	foreach ($subdirs as $subdir) {
		$subdir = '../store/'.$subdir;
		if (!is_dir($subdir)) {
		mkdir($subdir);
		}
	}
	echo '* Store-mappen aangemaakt' . PHP_EOL;
}
else {
	echo '* Store-mappen niet aangemaakt' . PHP_EOL;
}
/*
//create attachments dir structure
if (!is_dir('attachments')) {
	mkdir('attachments');
	$subdirs = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F');
	foreach ($subdirs as $subdir) {
		mkdir('attachments/'.$subdir);
	}
	echo 'created attachments directories'.PHP_EOL;
}
file_put_contents('attachments/.htaccess', 'deny from all');
*/

echo '* Done' . PHP_EOL;
?>