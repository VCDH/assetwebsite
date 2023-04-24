<?php
//configuration file
$cfg['cookie']['name'] = 'assetwebsite_login'; //name of the cookie set by assetwebsite login
$cfg['cookie']['expire'] = 30*24*60*60; //maximum life of the cookie

$cfg['account']['pass_minlength'] = 8; //set minimum password length
$cfg['account']['username_regex'] = '/[a-z0-9]+([@-_.]+[a-z0-9]+)*/i'; //regex that a username must pass; implies a username length check
$cfg['account']['email_regex'] = '/.+@.+\.[a-z]{2}/i'; //regex that an e-mail address must pass; by default just a basic check for @ and a tld

$cfg['fields']['generic'] = array (
    array ('g_1', 'code', 'text', 1),
    array ('g_2', 'naam', 'text', 0),
    array ('latitude', 'latitude', 'number', 1, array('min' => 49, 'max' => 54, 'step' => 'any')),
    array ('longitude', 'longitude', 'number', 1, array('min' => 3, 'max' => 7, 'step' => 'any')),
    array ('heading', 'heading', 'number', 1, array('min' => 0, 'max' => 360)),
    array ('g_3', 'status', 'status', 1),
    array ('g_4', 'aansturing', 'wegbeheerder', 1),
    array ('g_5', 'wegbeheerder', 'wegbeheerder', 1),
    array ('g_6', 'onderhoud', 'wegbeheerder', 1),
    array ('g_7', 'voeding', 'wegbeheerder', 1),
    array ('g_8', 'verbinding', 'wegbeheerder', 1),
    array ('g_9', 'leverancier', 'text', 0),
    array ('g_10', 'bouwjaar', 'number', 0, array('min' => 1950, 'max' => date('Y') + 10)),
    array ('g_11', 'oorspronkelijk_bouwjaar', 'number', 0, array('min' => 1950, 'max' => date('Y') + 10)),
    array ('g_12', 'memo', 'mtext', 0),
);

?>