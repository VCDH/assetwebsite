================================================================================
    assetwebsite - viewer en aanvraagformulier voor verkeersmanagementassets
                                      README
================================================================================
assetwebsite is een webgebaseerde viewer voor verkeersmanagementassets met 
mogelijkheden voor presentatie van locaties op kaart en in tabelvorm. 
Aanvullende informatie kunnen bij assets worden bewaard en (al dan niet) publiek
ontsloten worden. Voor DRIPs is er de mogelijkheid tot het genereren van 
aanvraagformulieren.

assetwebsite ontwikkeld voor BEREIK! door Gemeente Den Haag, afdeling 
Bereikbaarheid en Verkeersmanagement en aldaar geprogrammeerd door Jasper Vries.
De broncode is als open source software beschikbaar gesteld, om het voor alle 
wegbeheerders mogelijk te maken om gebruik te maken van deze ontwikkeling. Het 
formele auteursrecht berust bij de Gemeente Den Haag.


================================================================================
0. Inhoudsopgave
================================================================================

1. Systeemvereisten en benodigdheden
2. Installatie
3. Licentie
4. Verkrijgen van de broncode


================================================================================
1. Systeemvereisten en benodigdheden
================================================================================

De grafische interface is geschreven in HTML5 in combinatie met JavaScript. 
Hiervoor is een recente webbrowser met ondersteuning voor HTML5 nodig. Primaire 
ontwikkeling vindt plaats in Mozilla Firefox. Er wordt gebruik gemaakt van 
verschillende standaardlibraries die zijn gebundeld met de broncode.

De backend is geschreven in PHP (5.3+) en gebruikt een MySQL (5+) of 
MariaDB (5+) DBMS.


================================================================================
2. Installatie
================================================================================

Maak een kopie van dbconnect.inc.php.example en hernoem dit naar 
dbconnect.inc.php
Open dit met een texteditor en geef de databasecredentials op.

De installatiebestanden zijn ondergebracht in de map setup. Voer via de 
opdrachtregel de opdracht uit:
php install.php

Hectometerpunten kunnen uit bestand worden ingelezen. Hiervoor is 
LOAD DATA LOCAL INFILE nodig. Zie ook Hectopunten uit NWB.txt voor meer info.
php import_hectopunten.php

Voor het converteren van een oude assetwebsite naar deze versie is een script
beschikbaar. De broncode van de oude assetwebsite is nooit publiek geweest, 
dus voor hergebruik zal dit script nutteloos zijn:
php convert_from_old_db.php

Standaardkleuren voor een aantal wegbeheerders kunnen worden ingelezen via
php import_wegbeheerders.php
Anders dan de naam doet vermoeden maakt dit script geen wegbeheerders aan, die
moeten al aanwezig zijn in de database. Het overschrijft enkel de kleuren van
overeenkomstige wegbeheerders die al in de database (organisation tabel)
aanwezig zijn.

Om gebruikers een e-mail met hun wachtwoord te kunnen sturen dient 
mailconfig.inc.php handmatig aangemaakt te worden. Hiervoor kan 
mailconfig.php.example als voorbeeld gebruikt worden.

Overige configuratie staat in config.inc.php. Deze hoeft normaal gesproken niet 
aangepast te worden.


================================================================================
3. Licentie
================================================================================

De broncode van assetwebsite is vrijgegeven onder de voorwaarde van de 
GNU General Public License versie 3 of hoger. Voor gebundelde libraries kunnen 
andere licentievoorwaarden van toepassing zijn. Zie hiervoor de documentatie in 
de betreffende submappen.

Met uitzondering van gebundelde libraries is voor assetwebsite het volgende van 
toepassing:

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


================================================================================
4. Verkrijgen van de broncode
================================================================================

De broncode van assetwebsite is gepubliceerd op GitHub:
https://github.com/VCDH/assetwebsite/
