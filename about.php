<?php 
/*
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
session_start();
include_once('getuserdata.inc.php');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Assets - Over</title>
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
    <h1>Waarom deze website?</h1>
    <p>Beheergrenzen zijn voor een weggebruiker niet van belang. Om weggebruikers zo goed mogelijk te informeren is het vaak nodig om DRIPs te gebruiken die worden aangestuurd door verschillende wegbeheerders. Vanuit het samenwerkingsverband BEREIK! (thans <a href="https://www.zuidhollandbereikbaar.nl/" target="_blank">Zuid-Holland Bereikbaar</a>) is daarom deze website gestart voor wegbeheerders om van elkaar te zien wie waar DRIPs heeft staan. Via deze website kan een uniform aanvraagformulier worden gegenereerd dat kan worden ingediend bij de verschillende wegbeheerders. Hierdoor is er voor de aanvrager één centrale plaats met de meest actuele informatie. Gaandeweg is de website uitgebreid met andere wegkantsystemen en wegbeheerders buiten de Zuidvleugel.<p>

    <h1>DRIP-aanvraag</h1>
    <p>De mogelijkheid tot het maken van een DRIP-aanvraag is onderdeel van een afgestemd werkproces binnen <a href="https://www.zuidhollandbereikbaar.nl/" target="_blank">Zuid-Holland Bereikbaar</a>. Hoewel DRIPs van wegbeheerders buiten de Zuidvleugel kunnen worden opgenomen, gelden in andere regio's wellicht andere werkprocessen. Het blijft de verantwoordelijkheid van de aanvrager om zijn of haar aanvraag op de juiste manier bij de juiste partijen in te dienen. Uiteraard is het voor andere regio's mogelijk om op de werkwijze van Zuid-Holland Bereikbaar aan te sluiten.</p>

    <h1>Fouten in locatie/details melden</h1>
    <p>De inhoud van de website, waaronder de locaties van de wegkantsystemen en de detailgegevens worden beheerd vanuit de verschillende organisaties. Functionaliteit om fouten te kunnen melden <a href="https://github.com/VCDH/assetwebsite/issues/1" target="_blank">wordt nog toegevoegd</a>.</p>

    <h1>Beheer website</h1>
    <p>Het technisch beheer van deze website wordt namens Zuid-Holland Bereikbaar gedaan door Jasper Vries. Meld technische problemen of wensen in de <a href="https://github.com/VCDH/assetwebsite/issues" target="_blank">issuetracker op GitHub</a>. Kijk eerst of er al een issue bestaat met dezelfde bug of wens alvorens een nieuw issue aan te maken.</p>

    <h1>Ik wil mijn assets ook op deze website</h1>
    <p>Lijkt het je als wegbeheerder, binnen of buiten Zuid-Holland Bereikbaar, handig om je assets ook op deze website op te nemen? Neem dan contact op met jasper punt vries apenstaart denhaag punt nl voor de mogelijkheden. Het is ook mogelijk om als regio of individuele wegbeheerder gebruik te gaan maken van het DRIP-aanvraagformulier. Een bestaande lijst met assets kan in &eacute;&eacute;n keer worden ingelezen. Je blijft als wegbeheerder zelf verantwoordelijk voor het bijhouden van je eigen assets en krijgt hiervoor dan de nodige bewerkrechten.</p>

    <h1>Broncode</h1>
    <p>De broncode van deze website is open source en is gepubliceerd op <a href="https://github.com/VCDH/assetwebsite" target="_blank">GitHub</a>.</p>

    <h1>Changelog</h1>
    <p>In april 2020 is de website volledig opnieuw opgebouwd. De voornaamste wijzigingen hierbij zijn:</p>
    <ul>
        <li>Overgang van Google Maps naar Leaflet met o.a. OpenStreetMap en Kadastrale kaarten. De performance van de kaart is hiermee significant verbeterd.</li>
        <li>Tabel bevat alle assets en is filterbaar.</li>
        <li>BEREIK! logo verwijderd, behalve van pagina's die specifiek op BEREIK! van toepassing zijn (DRIP-Aanvraagformulier, Procedures). Hiermee is duidelijker welke delen van de website landelijk bruikbaar zijn en welke specifiek voor Zuid-Holland.</li>
        <li>DRIP-aanvraagformulier op aparte pagina met mogelijkheid tot selectie via filterbare tabel.</li>
        <li>DRIP-aanvraagformulier (Word-document) is aangepast n.a.v. de enquete.</li>
        <li>Onder water is het databasemodel compleet herontworpen, waardoor het eenvoudig is om nieuwe typen assets toe te voegen.</li>
        <li>Domeinscheiding doorgevoerd, alles zien maar alleen assets van eigen organisatie bewerken.</li>
        <li>Registratie ondersteunt nu meerdere organisaties met zelfde e-mailadres domein. In het bijzonder voor de verschillende organisatieonderdelen van Rijkswaterstaat. Gebruiker kiest bij registratie zelf het juiste organisatieonderdeel.</li>
    </ul>
    <p>Een overzicht van nieuwe aanpassingen die sinds april 2020 zijn doorgevoerd is <a href="https://github.com/VCDH/assetwebsite/issues?q=is%3Aclosed+label%3Aenhancement" target="_blank">beschikbaar op GitHub</a>.</p>

    <h1>Backlog</h1>
    <p>Er worden een aantal nieuwe functionaliteiten en verbeteringen overwogen. Zie hiervoor de <a href="https://github.com/VCDH/assetwebsite/labels/enhancement" target="_blank">lijst op GitHub</a>.</p>
</div>
</body>
</html>