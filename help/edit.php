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
?>

<h1>Asset bewerken/toevoegen</h1>
<p>Assets beschikken over een groot aantal velden met informatie. Er zijn generieke velden die voor ieder type asset gelijk zijn en specifieke velden die per type asset afwijkend zijn. Eenmaal vastgelegd kan het type van een asset daarom niet meer gewijzigd worden. Verplichte velden zijn aangemerkt met een sterretje.</p>
<p>Bekijk voor het toevoegen van een nieuwe asset of deze niet al voorkomt. Dit om dubbele vermeldingen te voorkomen. Eenmaal toegevoegd kan een asset namelijk niet meer verwijderd worden (wel kan de status van een asset worden gewijzigd naar <i>verwijderd</i> als deze fysiek van straat verwijderd is). Let op dat assets die gemarkeerd zijn als <i>verwijderd</i> niet meer op de kaart worden weergegeven. Raadpleeg dus ook altijd de tabel.</p>
<h2>Locatie op de kaart</h2>
<p>De geografische locatie van een asset kan worden vastgelegd door het opgeven van WGS-84 co&ouml;rdinaten of door het aanklikken/verschuiven van de locatie op de kaart. Het opgeven van een heading is alleen relevant voor richtinggevoelige assets zoals DRIPs waarbij de rijrichting relevant is. In dat geval wordt een heading opgegeven in de rijrichting. 0 graden is hierbij gelijk aan noord, verdere draaiing met de klok mee. Oost is dus 90 graden, etc. Voor niet-richtinggevoelige assets wordt de heading op 0 gehouden.</p>
<h2>Standaardtekst</h2>
<p>Voor DRIPs is het mogelijk een standaardtekst op te geven. Met standaardtekst wordt bedoeld de verkeerskundige boodschap die de DRIP toont wanneer er geen specifiek scenario actief is. In veel gevallen is dit reistijdinformatie. Mottoteksten worden niet onder standaardtekst verstaan. Op grond van de standaardtekst kan het nodig zijn om bepaalde DRIPs aan een scenario of DRIP-aanvraag toe te voegen. De standaardtekst kan in de vorm van een afbeelding worden ge&uuml;pload, in tekst worden omschreven/ingevoerd, of beide.</p>
<h2>Bewegwijzering</h2>
<p>Voor DRIPs is het mogelijk de relevante bewegwijzering toe te voegen. Dit is (een afbeelding van) de bewegwijzering op het keuzepunt waar de DRIP betrekking op heeft. Deze informatie helpt bij het opzoeken van de bestemmingen waarover de DRIP kan/moet informeren en of het inzetten van een volgDRIP wel of niet nodig is. De bewegwijzering kan in de vorm van een afbeelding worden ge&uuml;pload, in tekst worden omschreven/ingevoerd, of beide. Er kan &eacute;&eacute;n afbeelding worden toegevoegd, dus bij meerdere bewegwijzeringsborden dienen deze eerst tot &eacute;&eacute;n afbeelding te worden samengevoegd.</p>
<h2>Memo</h2>
<p>Het memoveld biedt de mogelijkheid om vrije tekst bij het asset op te slaan, voor informatie die niet kan worden opgenomen in de andere velden.</p>