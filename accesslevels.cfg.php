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
$cfg_accesslevel = array();
$cfg_accessdescription = array();

$cfg_accesslevel['beheer_eigen'] = 100;                 $cfg_accessdescription['beheer_eigen'] = 'Assets van eigen organisatie beheren';        
$cfg_accesslevel['beheer_alle'] = 250;                  $cfg_accessdescription['beheer_alle'] = 'Assets van alle organisaties beheren';        
$cfg_accesslevel['gebruikers_beheren_eigen'] = 200;     $cfg_accessdescription['gebruikers_beheren_eigen'] = 'Gebruikers van eigen organisatie beheren';          
$cfg_accesslevel['gebruikers_beheren_alle'] = 250;      $cfg_accessdescription['gebruikers_beheren_alle'] = 'Gebruikers van alle organisaties beheren';           
$cfg_accesslevel['organisaties_beheren_eigen'] = 200;   $cfg_accessdescription['organisaties_beheren_eigen'] = 'Eigen organisatie beheren';      
$cfg_accesslevel['organisaties_beheren_alle'] = 250;    $cfg_accessdescription['organisaties_beheren_alle'] = 'Alle organisaties beheren';        

$accesslevel_available = array(0 => 'Geblokkeerd', 1 => 'Meekijker', 100 => 'Bewerker', 200 => 'Beheerder', 255 => 'Superbeheerder');

?>