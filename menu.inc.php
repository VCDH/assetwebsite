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

//base menu items
$menuitems = array(
    array(
        'href' => 'index.php',
        'label' => 'Kaart'
    ),
    array(
        'href' => 'tabel.php',
        'label' => 'Tabel'
    ),
    array(
        'href' => 'aanvraagformulier.php',
        'label' => 'DRIP-Aanvraagformulier'
    ),
    array(
        'href' => 'procedures.php',
        'label' => 'Procedures',
        'login' => TRUE
    ),
    array(
        'href' => 'beheer.php',
        'label' => 'Beheer',
        'login' => TRUE,
        'accesslevel' => 'beheer_eigen'
    ),
    array(
        'href' => 'account.php',
        'label' => 'Account',
        'login' => TRUE
    ),
    array(
        'href' => 'login.php',
        'label' => 'Aanmelden',
        'login' => FALSE
    ),
    array(
        'href' => 'login.php?a=logout',
        'label' => 'Afmelden',
        'login' => TRUE
    ),
    array(
        'href' => 'about.php',
        'label' => 'Over'
    ),
);

$logincheck = getuserdata();
//get help page
$currentpage = substr($_SERVER['SCRIPT_NAME'], strrpos($_SERVER['SCRIPT_NAME'], '/') + 1);
$helppage = substr($currentpage, 0, -4);

?>
<div id="navigation">
    <ul class="toolbartab">
        <?php
        //TODO only map has a searchbox
        /*if ($currentpage == 'index.php') {
            ?>
            <li><span class="searchbox"><input type="text" id="searchbox" placeholder="Zoeken"></span></li>
            <?php
        }*/
        //list pages
        foreach ($menuitems as $item) {
            if ( (!isset($item['login']) || ($item['login'] === $logincheck))
            && (!isset($item['accesslevel']) || (getuserdata('accesslevel') >= $auth[$item['accesslevel']])) ) {
                if ($item['href'] != $currentpage) {
                    echo '<li><a href="' . $item['href'] . '">' . htmlspecialchars($item['label']) . '</a></li>';
                }
                else {
                    echo '<li><span>' . htmlspecialchars($item['label']) . '</span></li>';
                }
            }
        }
        ?>
        <li><a href="help.php" rel="<?php echo htmlspecialchars($helppage); ?>" id="help">Help</a></li>
    </ul>
</div>