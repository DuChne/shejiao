<?php 

ini_set("display_errors","On");
error_reporting(E_ALL);

require_once("CFCARAUtil.php");

 $dataStr = "123456";




echo CFCARAUtil::verifySignature($dataStr,CFCARAUtil::signData($dataStr));


// $enData = CFCARAUtil::encryptData($dataStr);
// print "enData:".$enData;
// print "\r\n";
// print CFCARAUtil::decryptData($enData);

