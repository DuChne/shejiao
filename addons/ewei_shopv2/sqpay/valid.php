<?php

require_once("../common/Common.php");
require_once("../common/HttpClient.php");
require_once("../common/CFCARAUtil.php");


$CommonData = array();
$CommonData["code"] =$_POST["code"];
$CommonData["msg"] =$_POST["msg"];
$CommonData["responseType"] =$_POST["responseType"];
$CommonData["responseParameters"] =$_POST["responseParameters"];

$sign = $_POST["sign"];

$dataStr = Common::joinMapValue($CommonData);
echo "未加密数据---".$dataStr;
echo "已加密数据---".$sign;
/*
	成功--True 
	失败-- False
*/
echo "验签结果----".CFCARAUtil::verifyMessageByP1($dataStr,$sign);
