<?php

// download.php
$hostname  = '{mail.site.com:993/imap/ssl}INBOX';
$username = 'info@site.com';
$password = 'sifre';

$inbox = imap_open($hostname, $username, $password) or die('Cannot connect to mail server: ' . imap_last_error());

$email_number = $_GET['email'];
$part_number = $_GET['part'];

$structure = imap_fetchstructure($inbox, $email_number);

$params = array();
if ($structure->parts[$part_number - 1]->ifdparameters) {
    foreach ($structure->parts[$part_number - 1]->dparameters as $object) {
        $params[strtolower($object->attribute)] = $object->value;
    }
}

$file_data = imap_fetchbody($inbox, $email_number, $part_number);
if ($structure->parts[$part_number - 1]->encoding == 3) {
    $file_data = base64_decode($file_data);
} elseif ($structure->parts[$part_number - 1]->encoding == 4) {
    $file_data = quoted_printable_decode($file_data);
}

header("Content-Disposition: attachment; filename=\"" . $params['filename'] . "\"");
header("Content-Type: " . $structure->parts[$part_number - 1]->subtype);
header("Content-Transfer-Encoding: " . $structure->parts[$part_number - 1]->encoding);

echo $file_data;

imap_close($inbox);

?>
