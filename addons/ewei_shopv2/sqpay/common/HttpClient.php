<?php 


class HttpClient{


        static public function curl($postAction,$postData){
               $ch = curl_init();
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
                curl_setopt($ch, CURLOPT_POST, TRUE); 
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData); 
                curl_setopt($ch, CURLOPT_URL, $postAction);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  false);
                curl_setopt($ch, CURLOPT_TIMEOUT,600);
                $exceMsg = "";
                $json = curl_exec($ch);
                $httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
                $exceMsg .= $httpCode."\r\n";
                if(empty($json)){

                    $curl_errno = curl_errno($ch);  
                    $curl_error = curl_error($ch);  
                    $exceMsg .= "Error:".$curl_errno.$curl_error."\r\n";
                    curl_close($ch);
            
                } else {           
                    
                    curl_close($ch);
           
                }
                //print $exceMsg;

                
                $deJson = json_decode($json);
                if(is_array($deJson)||is_object($deJson))
                    return $deJson;
                else
                    return $json;
        }
        
        static public function htmlForm($postAction,$postData){
                $inputPost = "<form action='".$postAction."' method='post' id='form_action'>";
                foreach ($postData as $key => $value) {
                        $inputPost.= "<input type='hidden' name='".$key."' value='".$value."'>";
                }
                $inputPost .= "<input  style='display: none' type='submit'  value='submit'>";
                $inputPost .= "</form><script>window.onload=function(){document.getElementById('form_action').submit();}</script>";
                print $inputPost;
        }




}