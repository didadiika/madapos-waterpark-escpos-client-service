<?php
$isWin = is_numeric(strpos(strtolower($_SERVER["HTTP_USER_AGENT"]), "windows")); 
$isAndroid = is_numeric(strpos(strtolower($_SERVER["HTTP_USER_AGENT"]), "android")); 
$isIPhone = is_numeric(strpos(strtolower($_SERVER["HTTP_USER_AGENT"]), "iphone")); 
$isIPad = is_numeric(strpos(strtolower($_SERVER["HTTP_USER_AGENT"]), "ipad")); 
$isIOS = $isIPhone || $isIPad; 

if($isIOS){ 
    $device = 'ios';
}elseif($isAndroid){ 
    $device = 'android';
}elseif($isWin){ 
    $device = 'windows';
}

$app_name = "madapos-restaurant-escpos-client-service";



?>