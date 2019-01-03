<?php 

require_once("php_java.php");

class CFCARAUtil {

		

		const pfxFile = "0001565_sign.pfx";//商户私钥，测试使用（请替换为商户在商户后台申请的私钥）
		const cerFile = "0001565_sign.cer";//商户公钥，证书--测试使用（请替换为商户在商户后台申请的公钥）
		const cerFileEncrypt_sq = "sq_test_encrypt.cer";//双乾公钥加密公钥证书--测试使用
		const cerFilesign_sq = "sq_test_sign.cer";//双乾公钥验签公钥证书--测试使用

		// const cerFileEncrypt_sq = "sq_formal_encrypt.cer";//双乾公钥加密公钥证书-正式使用
		// const cerFilesign_sq = "sq_formal_sign.cer";//双乾公钥验签公钥证书--正式使用
	
		const signAlg = "sha256WithRSAEncryption";
		const mechanism = "RSA/ECB/PKCS1PADDING";
		const symmetricAlg = "RC4";
		
		const passwordPfx = "200712"; // 商户证书私钥密码

		const path = __dir__;
		const debug = true;

		 public static function signMessageByP1($sourceData){			
			try{
				// 获得私钥 
				$retPFX = lajp_call("cfca.sadk.api.KeyKit::getPrivateKeyIndexFromPFX",base64_encode(file_get_contents(self::path."/".self::pfxFile)),self::passwordPfx);
				self::throwErrorCode($retPFX);

				$retSign = lajp_call("cfca.sadk.api.SignatureKit::P1SignMessage",self::signAlg,base64_encode($sourceData), json_decode($retPFX)->privateKeyIndex);

				self::throwErrorCode($retSign);


				return json_decode($retSign)->Base64SignatureString;
				

			}catch(Exception $e){
			  echo $e->getMessage();
			}
		}



		public static function verifyMessageByP1($sourceData,$signedBase64Data){		
			$cerPFX = lajp_call("cfca.sadk.api.CertKit::getCertInfo",base64_encode(file_get_contents(self::path."/".self::cerFilesign_sq)));
			self::throwErrorCode($cerPFX);
			$retVSign = lajp_call("cfca.sadk.api.SignatureKit::P1VerifyMessage",self::signAlg,base64_encode($sourceData),json_decode($cerPFX)->PublicKey,
				$signedBase64Data);

			return json_decode($retVSign)->Result;
		}



		public static function encryptMessageByRSA_PKCS($sourceData){
			$cerPFX = lajp_call("cfca.sadk.api.CertKit::getCertInfo",base64_encode(file_get_contents(self::path."/".self::cerFileEncrypt_sq)));					
			self::throwErrorCode($cerPFX);
   			$retEn = lajp_call("cfca.sadk.api.EncryptKit::encryptMessageByRSA_PKCS",json_decode($cerPFX)->PublicKey,$sourceData);
   		
   			return $retEn;

		}

		// 	public static function decryptMessageByRSA_PKCS($sourceData){
		// 		// 获得私钥 
		// 		$retPFX = lajp_call("cfca.sadk.api.KeyKit::getPrivateKeyIndexFromPFX",base64_encode(file_get_contents(self::path."/".self::pfxFileDecode)),self::passwordPfx);
		// 		self::throwErrorCode($retPFX);
        //  			$retEn = lajp_call("cfca.sadk.api.EncryptKit::decryptMessageByRSA_PKCS",$sourceData,json_decode($retPFX)->privateKeyIndex);
        //  			return $retEn;
		// }


		private static function throwErrorCode($json_ret){
			if(self::debug&&json_decode($json_ret)->Code!=="90000000"){
				try{
					Throw new Exception("ErrorCode:".json_decode($json_ret)->Code);
				}catch(Exception $e){
    					echo $e->getTraceAsString().json_decode($json_ret)->Code."\r\n";
				}
			}
		}

}
?>