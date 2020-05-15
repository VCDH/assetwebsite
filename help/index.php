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
?>
<h1>Kaartlagen</h1>
<p>Verschillende soorten assets zijn ingedeeld in afzonderlijke kaartlagen. Schakel &eacute;&eacute;n of meerder kaartlagen in om assets op de kaart te tonen. Door met de linkermuisknop op een asset op de kaart te klikken wordt een popup met aanvullende informatie geopend, vanuit deze popup kan een venster met detailinformatie worden geopend en kan de locatie worden geopend in Google Street View&trade;. Het venster met detailinformatie kan ook rechtstreeks worden geopend door met de rechtermuisknop op een asset te klikken.</p>

<h1>Kaartachtergrond en -weergave</h1>
<p>Er zijn verschillende kaartachtergronden beschikbaar die naar wens kunnen worden gekozen. Hierbij is het ook mogelijk om de weergave van de kaart aan te passen. De gekozen kaartachtergrond en -weergave worden ook toegepast op de pagina <a href="aanvraagformulier.php">DRIP-aanvraagformulier</a>, maar zijn daar niet te wijzigen.</p>

<h1>Legenda</h1>
<?php
include('dbconnect.inc.php');
$qry = "SELECT `id`, `name` FROM `".$db['prefix']."assettype`
ORDER BY `name`";
$res = mysqli_query($db['link'], $qry);
if (mysqli_num_rows($res)) {
    echo '<table>';
    while($data = mysqli_fetch_row($res)) {
        echo '<tr><td><img src="image.php?t=' . $data[0] . '&amp;w=0" width="16" heigth=16"></td><td>' . htmlspecialchars($data[1]) . '</td></tr>';
        //TODO: aanname dat DRIP type 1 is en VRI type 3
        if ($data[0] == 1) {
            //DRIP met standaardtekst
            echo '<tr><td><img src="image.php?t=' . $data[0] . '&amp;w=0&amp;i=1" width="16" heigth=16"></td><td>' . htmlspecialchars($data[1]) . ' met standaardtekst</td></tr>';
        }
        if ($data[0] == 3) {
            //iVRI
            echo '<tr><td><img src="image.php?t=' . $data[0] . '&amp;w=0&amp;i=1" width="16" heigth=16"></td><td>iVRI</td></tr>';
        }
    }
    echo '</table>';
}
?>

<h1>Wegbeheerders</h1>
<p>De kleur van een pictogram geeft aan door welke wegbeheerder een asset wordt aangestuurd/bediend.</p>
<?php
include('dbconnect.inc.php');
$qry = "SELECT `id`, `name` FROM `".$db['prefix']."organisation`
ORDER BY `name`";
$res = mysqli_query($db['link'], $qry);
if (mysqli_num_rows($res)) {
    echo '<table>';
    while($data = mysqli_fetch_row($res)) {
        echo '<tr><td><img src="image.php?t=0&amp;w=' . $data[0] . '" width="16" heigth=16"></td><td>' . htmlspecialchars($data[1]) . '</td></tr>';
    }
    echo '</table>';
}
?>


<!--
<h1>Zoeken</h1>
<p>De zoekfunctie kan gebruikt worden om assets en hectometerposities te zoeken. De zoekfunctie is niet hoofdlettergevoelig.</p>

<h2>Hectometerposities</h2>
<p>Er wordt automatisch gezocht op hectometerposities wanneer een wegnummer (inclusief A/N), optionele rijrichting en hectometerpunt worden opgegeven. De rijrichting kan geschreven worden als <em>Li</em> en <em>Re</em>, maar ook <em>links</em>, <em>rechts</em> en letters van afritten, parallelbanen of verbindingsbogen kunnen gebruikt worden. Wanneer de rijrichting wordt weggelaten, worden alle rijbanen weergegeven. Het hectometerpunt wordt exact ge&iuml;nterpreteerd en mag &eacute;&eacute;n decimaal bevatten. Spaties tussen letters en cijfers mogen weggelaten worden. Format: <span style="font-family: monospace;">&lt;wegnummer&gt; [li|links|re|rechts|a-z] &lt;hectometer&gt; [a-z]</span></p>
<p>Enkele voorbeelden:</p>
<ul>
<li>A4 Li 46,3</li>
<li>A4 rechts 46.3</li>
<li>A4re46.3</li>
<li>A4 f 46.3</li>
<li>A4 46.3 f</li>
<li>A4 li f 46.3</li>
<li>A4f li46.3</li>
<li>A4li46.3f</li>
</ul>
-->