<?php 

class Common{


	public static function getRandomNum($len){


		return substr(time(),strlen(time())-$len,$len);
	}
	
	public static function joinMapValue($sign_params){
        $sign_str = "";
        //ksort($sign_params);
        foreach ($sign_params as $key => $val) {

                $sign_str .= sprintf("%s=%s&", $key, $val);

        }
        return substr($sign_str, 0, -1);
    }



}
