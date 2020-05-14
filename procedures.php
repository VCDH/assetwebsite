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
//redirect if not logged in
logincheck();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Assets - Procedures</title>
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
    <img src="style/logo.png" width="195" height="101" alt="Logo BEREIK!">
    <h1>Procedures</h1>
    <p class="warning">Op deze pagina zijn procedures opgenomen die in gebruik zijn bij wegbeheerders in Zuid-Holland die verenigd zijn in het samenwerkingsplatform <a href="https://www.bereiknu.nl/" target="_blank">BEREIK!</a> in gebruik zijn. Voor andere regio's kunnen andere procedures van toepassing zijn.</p>
    <ul>
    <?php
    $dir_list = scandir('docs/');
    foreach ($dir_list as $item) {
        if (is_file('docs/'.$item)) {
            echo '<li><a href="docs/'.rawurlencode($item).'">'.htmlspecialchars($item).'</a></li>';
        }
    }
    ?>
    </ul>
</div>
</body>
</html>