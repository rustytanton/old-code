<?php
$src = $_GET['src'] ? $_GET['src'] : 'js01.src';
$lang = $_GET['lang'] ? $_GET['lang'] : 'javascript';
include_once('geshi/geshi.php');
$source = file_get_contents($src);
$geshi = new GeSHi($source, $lang);
echo $geshi->parse_code();
?>