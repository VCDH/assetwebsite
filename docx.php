<?php 
session_start();
include_once('getuserdata.inc.php');
include('dbconnect.inc.php');

//get user details
$sql = "SELECT
`".$db['prefix']."organisation`.`name`, `".$db['prefix']."user`.`phone`, `".$db['prefix']."user`.`name`, `".$db['prefix']."user`.`email`
FROM `".$db['prefix']."user`
LEFT JOIN  `".$db['prefix']."organisation`
ON `".$db['prefix']."user`.`organisation` = `".$db['prefix']."organisation`.`id`
WHERE `".$db['prefix']."user`.`id` = '" . mysqli_real_escape_string($db['link'], getuserdata('id')) . "'        
LIMIT 1";
//voer query uit
$result = mysqli_query($db['link'], $sql);
$result = mysqli_fetch_row($result);
$user_organisation = $result[0];
$user_phone = $result[1];
$user_name = $result[2];
$user_email = $result[3];


require_once __DIR__ . '/bundled/phpword/vendor/autoload.php';

// Creating the new document...
$phpWord = new \PhpOffice\PhpWord\PhpWord();
$phpWord->getSettings()->setThemeFontLang(new \PhpOffice\PhpWord\Style\Language('nl-NL'));

//style definitions
//table
$tableStyle = array(
    'borderColor' => '000000',
    'borderSize' => 1,
    'width' => 100,
    'cellMargin' => \PhpOffice\PhpWord\Shared\Converter::cmToTwip(0.05)
);
$phpWord->addTableStyle('myTable', $tableStyle);
//paragraph
$styleParNospace = array('spaceAfter' => 0);
$styleParH = array('spaceBefore' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(10), 'spaceAfter' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(6));
//font
$styleFontDefault = array('size' => 11, 'name' => 'Calibri');
$styleFontArial = array('size' => 11, 'name' => 'Arial');
$styleFontBold = array('size' => 11, 'name' => 'Calibri', 'bold' => true);
$styleFontLink = array('size' => 11, 'name' => 'Calibri', 'bold' => true, 'underline' => 'single', 'color' => '005091');
$styleFontSmallText = array('size' => 9, 'name' => 'Calibri');
//heading
$styleFontH1 = array('size' => 16, 'name' => 'Calibri Light', 'color' => '9AC61E');
$styleFontH2 = array('size' => 13, 'name' => 'Calibri Light', 'color' => '86C3E7');
$phpWord->addTitleStyle(1, $styleFontH1, $styleParH);
$phpWord->addTitleStyle(2, $styleFontH2, $styleParH);

/* Note: any element you append to a document must reside inside of a Section. */
// Adding an empty Section to the document...
$section = $phpWord->addSection();

//header
$header = $section->addHeader();
$header->addText('Aanvraagformulier DRIP-teksten BEREIK! versie 2.0.1', $styleFontSmallText, array('align' => 'right'));
//footer
$footer = $section->addFooter();
$footer->addPreserveText(htmlspecialchars('Formulier gegenereerd op '.date('d-m-Y') .'. Pagina {PAGE} van {NUMPAGES}'), $styleFontSmallText, array('align' => 'right'));


$section->addTitle('Aanvraagformulier DRIP-teksten BEREIK!', 1);
$section->addText('Dit aanvraagformulier is in gebruik bij wegbeheerders in Zuid-Holland die verenigd zijn in het samenwerkingsplatform BEREIK!. Voor andere regio\'s kunnen andere procedures van toepassing zijn. Het blijft de verantwoordelijkheid van de aanvrager om DRIP-aanvragen op de juiste manier bij de juiste partijen in te dienen.', $styleFontDefault);
$section->addTitle('Gegevens van de aanvrager', 2);

$table = $section->addTable('myTable');
$table->addRow();
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(6))->addText('Naam organisatie/firma:', $styleFontBold, $styleParNospace);
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(10))->addText(htmlspecialchars($user_organisation), $styleFontArial, $styleParNospace);
$table->addRow();
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(6))->addText('Naam aanvrager:', $styleFontBold, $styleParNospace);
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(10))->addText(htmlspecialchars($user_name), $styleFontArial, $styleParNospace);
$table->addRow();
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(6))->addText('Telefoonnummer aanvrager:', $styleFontBold, $styleParNospace);
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(10))->addText(htmlspecialchars($user_phone), $styleFontArial, $styleParNospace);
$table->addRow();
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(6))->addText('E-mailadres aanvrager:', $styleFontBold, $styleParNospace);
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(10))->addText(htmlspecialchars($user_email), $styleFontArial, $styleParNospace);

$section->addText('NB: bovenstaand e-mailadres zal worden gebruikt voor toezending van de bevestiging van de aanvraag.', $styleFontSmallText);


$section->addTitle('Gegevens aanvraag', 2);

$table = $section->addTable('myTable');
$table->addRow();
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(6))->addText('Aanleiding:', $styleFontBold, $styleParNospace);
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(10))->addText('wegwerkzaamheden/evenement/vooraankondiging/etc.', $styleFontArial, $styleParNospace);
$table->addRow(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(2));
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(6))->addText('Omschrijving van de wegafsluiting:', $styleFontBold, $styleParNospace);
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(10))->addText('', $styleFontArial, $styleParNospace);
$table->addRow();
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(6))->addText('SPIN-aanvraag nummer(s) (indien van toepassing):', $styleFontBold, $styleParNospace);
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(10))->addText('', $styleFontArial, $styleParNospace);
$table->addRow(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(2));
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(6))->addText('Omschrijving van de omleiding:', $styleFontBold, $styleParNospace);
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(10))->addText('', $styleFontArial, $styleParNospace);
$table->addRow();
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(6))->addText('Datum van:', $styleFontBold, $styleParNospace);
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(10))->addText('', $styleFontArial, $styleParNospace);
$table->addRow();
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(6))->addText('Tijd van:', $styleFontBold, $styleParNospace);
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(10))->addText('', $styleFontArial, $styleParNospace);
$table->addRow();
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(6))->addText('Datum tot:', $styleFontBold, $styleParNospace);
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(10))->addText('', $styleFontArial, $styleParNospace);
$table->addRow();
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(6))->addText('Tijd tot:', $styleFontBold, $styleParNospace);
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(10))->addText('', $styleFontArial, $styleParNospace);
$table->addRow();
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(6))->addText('Herhaalpatroon:', $styleFontBold, $styleParNospace);
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(10))->addText('geen/dagelijks/wekelijks', $styleFontArial, $styleParNospace);
$table->addRow(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(2));
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(6))->addText('Aanvullende specificatie tijdvenster:', $styleFontBold, $styleParNospace);
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(10))->addText('', $styleFontArial, $styleParNospace);
$section->addText('Wanneer het format voor het opgeven van het tijdvenster ontoereikend is, kan de gewenste inzet in het veld hierboven worden omschreven en de overige velden met betrekking tot het tijdvenster worden leeggelaten.', $styleFontSmallText);

$section->addTitle('Wijze van inzet', 2);
$section->addText('DRIPs kunnen door de aansturende partij(en) tijdgeschakeld worden ingezet, of er kan een DVM-Exchange service worden aangeboden aan de aanvragende partij. Kies hieronder de gewenste optie.', $styleFontDefault);

$table = $section->addTable('myTable');
$table->addRow();
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(0.5))->addText(' ', $styleFontBold, $styleParNospace);
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(6))->addText('Tijdgeschakeld', $styleFontArial, $styleParNospace);
$table->addRow();
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(0.5))->addText(' ', $styleFontBold, $styleParNospace);
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(6))->addText('DVM-Exchange', $styleFontArial, $styleParNospace);

$section->addPageBreak();
$section->addTitle('Gewenste DRIP-teksten', 2);


//haal DRIPs op
$drip_list = json_decode($_GET['s'], TRUE);

if (is_array($drip_list)) {
    $drip_list_clean = array();
    $drip_list_aansturing = array();
    foreach ($drip_list as $drip_this) {
        if (is_numeric($drip_this)) {
            $drip_list_clean[] = $drip_this;
        }
    }
    
    $qry = "SELECT `code`, `t_aansturing`.`name` AS `aansturing`, `t_type`.`content` AS `type`, `t_template`.`content` AS `template`, `".$db['prefix']."asset`.`id` AS `assetid`, `t_aansturing`.`id` AS `aansturing_id` 
    FROM `".$db['prefix']."asset`
    LEFT JOIN `".$db['prefix']."organisation` AS `t_aansturing`
	ON `".$db['prefix']."asset`.`aansturing` = `t_aansturing`.`id`
    LEFT JOIN (SELECT `asset`, `content` FROM `".$db['prefix']."addfieldcontent`
    WHERE `addfield` = 5) AS `t_type`
    ON `t_type`.`asset` = `".$db['prefix']."asset`.`id`
    LEFT JOIN (SELECT `asset`, `content` FROM `".$db['prefix']."addfieldcontent`
    WHERE `addfield` = 6) AS `t_template`
    ON `t_template`.`asset` = `".$db['prefix']."asset`.`id`
    WHERE `".$db['prefix']."asset`.`id` IN (".join(',', $drip_list_clean).")
    ORDER BY `aansturing`, `code`";
	$res = mysqli_query($db['link'], $qry);
	if (mysqli_num_rows($res)) {

        //link to map
        /* not supported by map
        $textrun = $section->addTextRun();
        $textrun->addText('Bekijk op de kaart: ', $styleFontDefault);
        $textrun->addLink(htmlspecialchars('http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/').'?s=['.join(',', $drip_list_clean).']', htmlspecialchars('http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/').'?s=['.join(',', $drip_list_clean).']', $styleFontDefault);
        */
        
        //table header
        $table = $section->addTable('myTable');
        $table->addRow();
        $table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(4))->addText('Aansturing', $styleFontBold, $styleParNospace);
        $table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(5))->addText('Naam DRIP', $styleFontBold, $styleParNospace);
        $table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(7))->addText('Gewenste tekst', $styleFontBold, $styleParNospace);
        
        while ($data = mysqli_fetch_row($res)) {
            //populate aansturing id list
            if (!in_array($data[5], $drip_list_aansturing)) {
                $drip_list_aansturing[] = $data[5];
            } 
            //table contents
            $table->addRow(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(2));
            //aansturing
            $table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(4))->addText(htmlspecialchars($data[1]), $styleFontDefault, $styleParNospace);
            //naam
			$cell = $table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(5));
            //$cell->addText(htmlspecialchars($data[0]), $styleFontBold, $styleParNospace);
            $textrun = $cell->addTextRun($styleParNospace);
            $textrun->addLink(htmlspecialchars('http://'.$_SERVER["SERVER_NAME"].substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')).'/').'?id='.$data[4], htmlspecialchars($data[0]), $styleFontLink);
            //(type)
            $cell->addText('(' . (empty($data[3]) ? htmlspecialchars($data[2]) : htmlspecialchars($data[3])) . ')', $styleFontDefault, $styleParNospace);
            //gewenste tekst
            $table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(7))->addText('', $styleFontArial, $styleParNospace); 
        }
        $section->addText('Tip: Ctrl+klik op de naam van een DRIP om de locatie te bekijken op de kaart van de assetwebsite.', $styleFontSmallText);
	}
    //no rows found
    else {
        $section->addText('Geen DRIPs geselecteerd.', $styleFontDefault);
    }
}
//no selected
else {
    $section->addText('Geen DRIPs geselecteerd.', $styleFontDefault);
}


$section->addTitle('Opmerkingen', 2);
$table = $section->addTable('myTable');
$table->addRow(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(2));
$table->addCell(\PhpOffice\PhpWord\Shared\Converter::cmToTwip(16))->addText('', $styleFontArial, $styleParNospace);


$section->addTitle('Mee te sturen bijlagen', 2);
$section->addText('Indien beschikbaar vragen we u om de volgende bijlagen bij uw aanvraag mee te sturen:', $styleFontDefault);
$section->addListItem('Tekening van de wegafzetting.', 0, $styleFontDefault, NULL, $styleParNospace);
$section->addListItem('Tekening van de omleidingen.', 0, $styleFontDefault);
$section->addText('Tip: U kunt ook weblinks naar tekeningen toevoegen in het opmerkingenveld.', $styleFontSmallText);

$section->addTitle('Dien uw aanvraag in bij', 2);


if (count($drip_list_aansturing) > 0) {
    $qry = "SELECT `name`, `aanvraagformulier_tekst` 
    FROM  `".$db['prefix']."organisation`
    WHERE `id` IN (".join(',', $drip_list_aansturing).")
    ORDER BY `name`, `id`";
    $res = mysqli_query($db['link'], $qry);
    while($data = mysqli_fetch_row($res)) {
        $section->addListItem($data[0], 0, $styleFontDefault, NULL, $styleParNospace);
        if (getuserdata()) {
            //split newlines
            foreach (preg_split('/(\\r\\n?+|\\n)/', $data[1], -1, PREG_SPLIT_NO_EMPTY) as $part) {
                $section->addListItem($part, 1, $styleFontDefault, NULL, $styleParNospace);
            }
        }
    }
}
else {
	$section->addText('Kan lijst met adressen niet samenstellen.', $styleFontDefault);
}


// Saving the document as Word2007 file...
$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
// save as a random file in temp file
$temp_file = tempnam('/tmp', 'phpw');
$objWriter->save($temp_file);
// output to browser
header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename=Aanvraagformulier_DRIPs_'.date('YmdHi').'.docx');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($temp_file));
ob_clean();
flush();
readfile($temp_file);
unlink($temp_file); // deletes the temporary file
exit;
?>