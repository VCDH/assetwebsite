<?php 
/*
 	assetwebsite - viewer en aanvraagformulier voor verkeersmanagementassets
    Copyright (C) 2023 Gemeente Den Haag, Netherlands
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
<h1>Import</h1>
<p>Met de importfunctie kunnen assets in bulk worden bijgewerkt via een CSV bestand.</p>

<h2>tekenreeksvervanging</h2>
<p>Middels de tekenreeksvervanging kunnen ingelezen waarden worden vervangen. Wanneer het veld leeg gelaten wordt, wordt er geen tekenreeksvervanging toegepast.</p>
<p>Het format voor tekenreeksvervanging is als volgt: <i>zoeken=vervangen</i>. Iedere keer dat <i>zoeken</i> voorkomt in de ingelezen waarde voor het veld, wordt dit vervangen door <i>vervangen</i>. Hierbij is <i>zoeken</i> hoofdlettergevoelig. Er kunnen meerdere <i>zoeken=vervangen</i> worden opgegeven door deze te scheiden door een puntkomma, bijvoorbeeld: zoeken1=vervangen1;zoeken2=vervangen2;zoeken3=vervangen3.</p>
<p>Er kan een standaardwaarde worden opgegeven voor tekenreeksvervanging, door <i>DEFAULT</i> te gebruiken als sleutelwoord voor <i>zoeken</i>. In dat geval wordt na alle andere zoeken=vervangen toegepast te hebben, gekeken of de resulterende waarde gelijk is aan een van de waarden die is opgegeven als <i>vervangen</i>. Indien dit niet het geval is, wordt de resulterende waarde vervangen door de waarde opgegeven voor <i>DEFAULT</i>. Bijvoorbeeld zoeken1=vervangen1;zoeken2=vervangen2;zoeken3=vervangen3;DEFAULT=standaardwaarde. Stel de oorspronkelijke waarde is <i>zoeken2</i>, dan zal de resulterende waarde zijn <i>vervangen2</i>. Echter wanneer de resulterende waarde iets anders is dan <i>vervangen1</i>, <i>vervangen2</i> of <i>vervangen3</i>, dan zal deze worden vervangen door <i>standaardwaarde</i>. Zo zou oorspronkelijke waarde <i>zoeken4</i> resulteren in <i>standaardwaarde</i>.</p>
<p>Er is een speciale tekenreeksvervanging <i>YEAR</i>. Wanneer exact dit wordt opgegeven als tekenreeksvervanging, dan zal geprobeerd worden om de oorspronkelijke waarde te converteren naar een jaartal. Hiermee kan bijvoorbeeld een datum uit het bronbestand worden omgezet in een jaartal. In de bouwjaar-velden zijn enkel jaartallen toegestaan. Daarom is deze functie ook standaard actief voor de bouwjaarvelden.</p>
<p>Er is een speciale tekenreeksvervanging <i>DMS</i>. Wanneer exact dit wordt opgegeven als tekenreeksvervanging, dan zal geprobeerd worden om de oorspronkelijke waarde in graden, minuten en seconden (bijvoorbeeld 52° 01' 02.3") om te zetten in een decimaal co&ouml;rdinaat. </p>

<h2>Inlezen voor organisatie</h2>
<p>Standaard worden assets ingelezen voor de eigen organisatie. Een technisch beheerder kan assets ook voor een andere organisatie inlezen. Neem hiervoor contact op met de technisch beheerder.</p>

<h2>Nieuwe assets afhandelen</h2>
<p>Standaard worden alleen bestaande assets bijgewerkt. Als een ingelezen asset nog niet bestaat, dan wordt deze overgeslagen. Dit is om te voorkomen dat fouten in het CSV-bestand onbedoeld leiden tot nieuwe assets, die vervolgens niet meer verwijderd kunnen worden. Een technisch beheerder kan nieuwe assets ook in bulk inlezen. Neem hiervoor contact op met de technisch beheerder.</p>
