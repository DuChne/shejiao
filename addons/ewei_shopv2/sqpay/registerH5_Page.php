
<?php
header("Content-Type:text/html;charset=utf-8"); //设置编码

require_once("../common/Common.php");
require_once("../common/HttpClient.php");
require_once("../common/CFCARAUtil.php");

// 必填选项
$postData["type"] = "web"; // 页面类型（web/h5）
$postData["returnUrl"] = "https://www.baidu.com/"; // 成功页面跳转地址
$postData["seraialNumber"] = "2222222222";// 请求流水号（用于标识用户）
$postData["merType"] = "pcy"; // 注册类型 可选值  com（公司）per（个人）pcy（个体工商户）
// 选填项
//$postData["legalPersonName"] = "张三";
//$postData["legalPersonPhone"] = "18752123430";
//$postData["typeOfID"] = "0"; //暂时只支持身份证号
//$postData["legalPersonIdnum"] = "123131231232";
//$postData["merchantName"] = "123131231232";
//$postData["merchantNameSimple"] = "123131231232";
//$postData["merchantPhone"] = "123131231232";
//$postData["address"] = "123131231232";
//$postData["longTimeOrNoPer"] = "0";
//$postData["IDValidity"] = "2018-05-25";
//$postData["industry"] = "生活/家居";
//$postData["businessLicenceType"] = "0";
//$postData["longTimeOrNo"] = "1";
//$postData["businessLicenceNo"] = "123131231232";
//$postData["businessLicenceValidity"] = "123131231232";


// 顺序不可变
$CommonData = array();
$CommonData["merNo"] = "0001105"; // 平台商户号
$CommonData["version"] = "1.1"; // 固定 1.1
$CommonData["notifyUrl"] = "http://10.62.22.42:8888/api/test/getNotify"; // 异步通知地址，需要在商户端添加由双乾审核
$CommonData["timestamp"] = "2018-02-01 20:54:45";
$CommonData["apiContent"] = json_encode($postData);
$CommonData["signType"] = "CFCA"; // 默认CFCA

$dataStr = Common::joinMapValue($CommonData);

echo "签名前数据>>>>>>>>>";
echo  $dataStr;
print "<br>";
$CommonData["sign"] = CFCARAUtil::signMessageByP1($dataStr);
print "签名后数据>>>>>>>>".$CommonData["sign"];
print "<br>";
print "<br>";
// 请求地址
//$postActionTest = "http://10.62.22.42:8888/api/page/registerPage";
$postActionTest = "http://shoudan.95epay.com:9000/api/api/page/registerPage";
// 返回页面，采用表单请求
HttpClient::htmlForm($postActionTest,$CommonData);
?>








