<?php
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
require __DIR__ . '/../../vendor/autoload.php';
use Mike42\Escpos\ImagickEscposImage;#Butuh Ekstensi Imagick
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;

date_default_timezone_set("Asia/Jakarta");
require __DIR__ . '/../../helper/Tanggal_helper.php';
require __DIR__ . '/../../helper/Uang_helper.php';
require __DIR__ . '/../../config.php';


$json = $_POST['json'];
$data = json_decode($json);
$jumlah_print = $_POST['jumlah_print'];


#----------------------------------IMAGE SETTING FIRST-------------------------------------#
$logo_image = 'default.png';
$urlArray = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = explode('/', $urlArray);
$numSegments = count($segments); 
$environment = $segments[$numSegments - 3];
if($environment == 'windows')
{
    $image_directory = $data->print_setting->windows_images_directory;
} else if($environment == 'android'){
    $image_directory = $data->print_setting->android_images_directory;
}
#----------------------------------IMAGE SETTING FIRST-------------------------------------#
        

        /**JIKA ADA ORDERS**/
        if($data->tickets){
            /**LOOPING ORDERS**/
            $pr_conn = array();
            $pr_usb = array();
            $pr_ip = array();
            $nump = 0;
            $qty_sum = array();
            foreach($data->tickets as $ticket){
            $pr_conn[$nump] = $ticket->conn;
            $pr_usb[$nump] = $ticket->usb;
            $pr_ip[$nump] = $ticket->ip;
                /**INISIALIASASI SETTING KERTAS DAN ALIGMENT**/
                if(($ticket->paper == '58mm' && $ticket->type == '58') || ($ticket->paper == '80mm' && $ticket->type == '80'))
                {
                    if($ticket->paper == '58mm'){$lebar_pixel = 32;}else {$lebar_pixel = 48; }
                    $center = 'On';
                    $right = 'On';
                }
                else{
                    $lebar_pixel = 32;
                    $center = 'Off';
                    $right = 'Off';
                }
                /**INISIALIASASI SETTING KERTAS DAN ALIGMENT**/

                /**KONEKSI PRINTER**/
                if($nump == 0){
                    if($ticket->conn == "USB"){  
                        $connector = new WindowsPrintConnector($ticket->usb); 
                    } else if($ticket->conn == "Ethernet"){  
                        $connector = new NetworkPrintConnector($ticket->ip); 
                    } else {
                        $connector = '';/**BLUETOOTH CONNECTOR JIKA SUDAH SUPPORT**/
                    }
                    $printer = new Printer($connector);
                } else {
                    if($ticket->conn == "USB"){  
                        
                            $printer->close();
                            $connector = new WindowsPrintConnector($ticket->usb);
                            $printer = new Printer($connector);
                        
                    } else if($ticket->conn == "Ethernet"){  
                        if($pr_conn[$nump - 1] == "Ethernet" && $ticket->ip !=  $pr_ip[$nump - 1])
                        {
                            /** JIKA CONNECTOR SEBELUMNYA MENGGUNAKAN ETHERNET DAN IP YANG SAMA MAKA SETELAH DI CLOSE CONNECTOR TIDAK DAPAT
                             * DIBUKA LAGI (UNTUK KSWEB ANDROID) MAKA DIATASI DG SCRIPT INI **/
                            $printer->close();
                            $connector = new NetworkPrintConnector($ticket->ip);
                            $printer = new Printer($connector);
                        } 
                         
                    } else {
                        $connector = '';/**BLUETOOTH CONNECTOR JIKA SUDAH SUPPORT**/
                    }
                }
                
                
                /**KONEKSI PRINTER**/

                //try {
                /** JALANKAN PERINTAH PRINTER DISINI**/
                if($ticket->contents){

                    foreach($ticket->contents as $content){
                       
                    // Logo
                    if($center == 'On')
                    {
                        $printer -> setJustification(Printer::JUSTIFY_CENTER);
                    }
                    $logo = EscposImage::load($image_directory.'/default.png');
                    $printer->bitImage($logo);

                        
                    if($ticket->beep == "On")
                    {
                        $printer -> getPrintConnector() -> write(PRINTER::ESC . "B" . chr(4) . chr(1));
                    }    
                    
                    /** JUMLAH PRINT **/
                    for($i= 0; $i < $jumlah_print; $i++ ){
                        /** LOOPING MAKANAN **/
                        // $logo = EscposImage::load($images_path."/".$data->store->photo);
                        if($center == 'On')
                        {
                        $printer -> setJustification(Printer::JUSTIFY_CENTER);
                        }
                        // $printer->bitImage($logo);
                        // $printer -> feed();
                        $printer->selectPrintMode(Printer::MODE_FONT_A);
                        $printer -> setJustification(Printer::JUSTIFY_LEFT);
                        $printer -> text("#".$ticket->category."\n");

                        if($center == 'On')
                        {
                        $printer -> setJustification(Printer::JUSTIFY_CENTER);
                        }
                        
                        $printer->text($data->store->header_bill."\n");
                    #Toko
                    if($center == 'On')
                    {
                        $printer -> setJustification(Printer::JUSTIFY_CENTER);
                    }
                    $printer->selectPrintMode(Printer::MODE_FONT_A | Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH);
                    
                    $printer -> setTextSize(1, 1);
                    $printer->setEmphasis(true);//berguna mempertebal huruf
                   
                    if($center == 'On')
                    {
                    $printer -> setJustification(Printer::JUSTIFY_CENTER);
                    }
                    $printer -> text(str_repeat('=',$lebar_pixel)."\n");
                    $printer -> setJustification(Printer::JUSTIFY_LEFT);
                    $printer->text("Tanggal : ".$data->customer->date."\n");
                    $printer -> setJustification(Printer::JUSTIFY_CENTER);
                    $printer -> setTextSize(2, 1);
                    if($data->customer->customer_is_default == 1){
                        $printer->text(strtoupper(substr($data->customer->customer_alias,0,24))."\n");
                    } else {
                        $printer->text(strtoupper(substr($data->customer->customer_name,0,24))."\n");
                    }
                    
                    
                    $printer -> setTextSize(1, 1);
                    #Judul
                    $printer -> text(str_repeat('-', $lebar_pixel)."\n");
                    $batas = $lebar_pixel;
        
                  
                    
            if($center == 'On')
            {
                $printer -> setJustification(Printer::JUSTIFY_CENTER);
            }
            $printer -> text(strtoupper($content->name)."\n");
            $printer -> text("IDR ".uang($content->price)."\n");
            $size = 5;
            $printer -> qrCode($content->ticket_id, Printer::QR_ECLEVEL_L,$size);
            $printer -> feed();
            $printer -> text($content->ticket_id."\n");
                    
                
            $printer -> setJustification(Printer::JUSTIFY_LEFT);
            $printer -> text(str_repeat('=', $lebar_pixel)."\n");
        
            if($center == 'On')
            {
                $printer -> setJustification(Printer::JUSTIFY_CENTER);
            }
            // $printer -> text("TERIMA KASIH \n");
            
            $mada_footer = EscposImage::load($image_directory.'/'.$data->app_logo);
            $printer->bitImage($mada_footer);
            if($ticket->space_footer > 0){$printer -> feed($ticket->space_footer); }
            if($ticket->cutter == "On")
            {
                $printer->cut();#Memotong kertas
            }
                
                
            
            /** LOOPING MAKANAN **/
            }
            /** JUMLAH PRINT **/
                
            }
            
                // } catch (Exception $e) {
                //     echo $e->getMessage();
                // } finally {
                //     $printer->close();
                // }
                /** JALANKAN PERINTAH PRINTER DISINI**/
                $nump++;
            }
            }//END FOREACH TICKET
            $printer->close();
            /**LOOPING ORDERS**/

            //var_dump($pr_conn);
        }
        /**JIKA ADA ORDERS**/

    echo "<script>window.close();</script>";
?>