<?php 

require_once("../common/Common.php");
require_once("../common/HttpClient.php");
require_once("../common/CFCARAUtil.php");

// 身份证号加密
$idNo = CFCARAUtil::encryptMessageByRSA_PKCS("320321555555559021213");
// 手机号加密
$phone = CFCARAUtil::encryptMessageByRSA_PKCS("111111111");

$postData["merType"] = "per";
$postData["custName"] = "张三";
$postData["idNo"] = $idNo;
$postData["phone"] = $phone;
$postData["merchantName"] = "设计方案";
$postData["customerNo"] = "123131231232";


$CommonData = array();
$CommonData["merNo"] = "0001105";
$CommonData["version"] = "1.1";
$CommonData["notifyUrl"] = "http://shoudan.95epay.com/api/api/account/sendSmsCode";
$CommonData["timestamp"] = "2018-02-01 20:54:45";
$CommonData["apiContent"] = json_encode($postData);
$CommonData["signType"] = "CFCA";


$dataStr = Common::joinMapValue($CommonData);



echo "签名前数据----".$dataStr;
echo "<br>";
echo "<br>";
$CommonData["sign"] = CFCARAUtil::signMessageByP1($dataStr);
echo "签名后数据----".$CommonData["sign"];
echo "<br>";
echo "<br>";

$postActionTest = "http://10.62.22.42:8888/api/account/sendSmsCode";
// $postAction = "https://api.deptg.cqfmbank.com/qdd/merchant/loan/balancequery.action";

 		$resp = HttpClient::curl($postActionTest,$CommonData);
 		print_r($resp);








