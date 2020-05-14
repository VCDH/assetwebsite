<?php
//configuration file
$cfg['cookie']['name'] = 'assetwebsite_login'; //name of the cookie set by assetwebsite login
$cfg['cookie']['expire'] = 30*24*60*60; //maximum life of the cookie

$cfg['account']['pass_minlength'] = 8; //set minimum password length
$cfg['account']['username_regex'] = '/[a-z0-9]+([@-_.]+[a-z0-9]+)*/i'; //regex that a username must pass; implies a username length check
$cfg['account']['email_regex'] = '/.+@.+\.[a-z]{2}/i'; //regex that an e-mail address must pass; by default just a basic check for @ and a tld

?>