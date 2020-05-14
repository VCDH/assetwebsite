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

include_once('getuserdata.inc.php');
include_once('accesslevels.cfg.php');

?>
<h1>Beheer</h1>
<p>Verschillende beheerdingetjes voor de website.</p>

<?php 
if (accesslevelcheck('gebruikers_beheren_eigen')) {
?>
<h2>Gebruikers beheren</h2>
<p>Klik op <em>Gebruikers weergeven</em> om een lijst te tonen van alle geregistreerde gebruikers. Van hier uit kunnen de accountrechten van een gebruiker gewijzigd worden, alsmede de geregistreerde gebruikersgegevens.</p>
<p>Gebruik de zoekfunctie om te filteren op naam of organisatie.</p>
<p>Toegangsrechten werken op basis van een toegangsniveau (<em>clearance level</em>). Voor verschillende functies is vastgelegd welk toegangsniveau minimaal vereist is om er gebruik van te mogen maken. Zie hiervoor onderstaande overzichten.</p>
<?php 
echo '<table style="float:left;">';
echo '<tr><th>Toegangsniveau gebruiker</th><th>Waarde</th></tr>';
foreach ($accesslevel_available as $k => $v) {
	echo '<tr><td>'.htmlspecialchars($v).'</td><td>'.$k.'</td></tr>';
}
echo '</table>';
echo '<table style="float:left; margin-left: 16px;">';
echo '<tr><th>Functie</th><th>Minimaal vereist toegangsniveau</th></tr>';
foreach ($cfg_accesslevel as $k => $v) {
	echo '<tr><td>'.htmlspecialchars($cfg_accessdescription[$k]).'</td><td>'.$v.'</td></tr>';
}
echo '</table>';
}

if (accesslevelcheck('organisaties_beheren_eigen')) {
?>
<h2 style="clear:left;">Organisaties beheren</h2>
<p>Geef een lijst weer van de organisaties die binnen het systeem geregisteerd zijn. Hierbij wordt de naam van de organisatie en het deel van de de e-mailadressen na de @ vastgelegd (<em>E-mail-suffix</em>). Personen in het bezit van een e-mailadres van &eacute;&eacute;n van de geregistreerde organisaties worden automatisch toegevoegd aan de organisatie, wanneer de optie <em>Account aanmaken openstellen</em> voor de organisatie is ingeschakeld.</p>
<p>Het veld <i>Adressen aanvraagformulier</i> wordt opgenomen in het aanvraagformulier in de lijst met organisaties waar de aanvraagformulier bij ingediend moet worden. Het is bedoeld om &eacute;&eacute;n of meerdere e-mailadressen in op te nemen zodat gebruikers weten waar het ingevulde aanvraagformulier naar toe gestuurd moet worden. De inhoud van dit veld wordt alleen opgenomen in aanvraagformulieren die gemaakt worden door geregistreerde gebruikers.</p>
<?php
}
?>