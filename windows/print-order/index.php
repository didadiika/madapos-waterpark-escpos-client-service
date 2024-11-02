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
        if($data->orders){
            /**LOOPING ORDERS**/
            $pr_conn = array();
            $pr_usb = array();
            $pr_ip = array();
            $nump = 0;
            $qty_sum = array();
            foreach($data->orders as $order){
            $pr_conn[$nump] = $order->conn;
            $pr_usb[$nump] = $order->usb;
            $pr_ip[$nump] = $order->ip;
                /**INISIALIASASI SETTING KERTAS DAN ALIGMENT**/
                if(($order->paper == '58mm' && $order->type == '58') || ($order->paper == '80mm' && $order->type == '80'))
                {
                    if($order->paper == '58mm'){$lebar_pixel = 32;}else {$lebar_pixel = 48; }
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
                    if($order->conn == "USB"){  
                        $connector = new WindowsPrintConnector($order->usb); 
                    } else if($order->conn == "Ethernet"){  
                        $connector = new NetworkPrintConnector($order->ip); 
                    } else {
                        $connector = '';/**BLUETOOTH CONNECTOR JIKA SUDAH SUPPORT**/
                    }
                    $printer = new Printer($connector);
                } else {
                    if($order->conn == "USB"){  
                        
                            $printer->close();
                            $connector = new WindowsPrintConnector($order->usb);
                            $printer = new Printer($connector);
                        
                    } else if($order->conn == "Ethernet"){  
                        if($pr_conn[$nump - 1] == "Ethernet" && $order->ip !=  $pr_ip[$nump - 1])
                        {
                            /** JIKA CONNECTOR SEBELUMNYA MENGGUNAKAN ETHERNET DAN IP YANG SAMA MAKA SETELAH DI CLOSE CONNECTOR TIDAK DAPAT
                             * DIBUKA LAGI (UNTUK KSWEB ANDROID) MAKA DIATASI DG SCRIPT INI **/
                            $printer->close();
                            $connector = new NetworkPrintConnector($order->ip);
                            $printer = new Printer($connector);
                        } 
                         
                    } else {
                        $connector = '';/**BLUETOOTH CONNECTOR JIKA SUDAH SUPPORT**/
                    }
                }
                
                
                /**KONEKSI PRINTER**/

                //try {
                /** JALANKAN PERINTAH PRINTER DISINI**/
                if($order->contents){
                    if($order->beep == "On")
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
                        if($data->customer->order_printed > 0){
                        $printer -> setJustification(Printer::JUSTIFY_LEFT);
                        $printer -> text("#Copied".$data->customer->order_printed."\n");
                        }
                        $printer -> setJustification(Printer::JUSTIFY_LEFT);
                        $printer -> text("#".$order->category."\n");

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
                    
                    if($data->customer->dine_type == "Dine In"){
                        $printer -> setTextSize(3, 2);
                        $printer -> text("#".$data->customer->numb_desk."\n");
                        $printer -> setTextSize(2, 1);
                        $printer -> text($data->customer->area."\n");
                    } else {
                        $printer -> text("#".$data->customer->dine_type."\n");
                    }
                    
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
                    $printer->text(strtoupper($data->customer->customer_name)."\n");
                    $printer -> setTextSize(1, 1);
                    #Judul
                    $printer -> text(str_repeat('-', $lebar_pixel)."\n");
                    $batas = $lebar_pixel;
        
                    #Item
                    $no = 0;
                    $spasi_max_qty = 3;
                    $spasi_between_qty_items = 1;
                    $printer -> setJustification(Printer::JUSTIFY_LEFT);
                    $printer -> text("Qty".str_repeat(' ',$spasi_between_qty_items)."Item"."\n");
                    $printer -> text(str_repeat('-', $lebar_pixel)."\n");
                    $qty_sum[$nump] = array();
                    foreach($order->contents as $content){
                    $no++;
                        $nama_produk = ucwords(strtolower($content->name));#12
                        $qty_sum[$nump][] = $qty = $content->qty;#1
                        $note = $content->note;#6
                        $addition = $content->addition;#6
                        
                        $printer -> setJustification(Printer::JUSTIFY_LEFT);
                        $printer -> text(str_repeat(' ',$spasi_max_qty - strlen($qty)).$qty.str_repeat(' ',$spasi_between_qty_items).$nama_produk."\n");
                        $printer -> setJustification(Printer::JUSTIFY_LEFT);
                        if($addition == 'Yes'){
                            $printer -> text("    *Tambahan\n");
                        }
                        if($note != "" && $note != null){
                            $printer -> text("     **".$note."\n");
                        }
                        //$printer -> text("\n");
                    }
                        
                    
                
            $printer -> text(str_repeat('-', $lebar_pixel)."\n");
            $printer -> setJustification(Printer::JUSTIFY_LEFT);
            $printer -> text(str_repeat(' ',$spasi_max_qty - strlen(array_sum($qty_sum[$nump]))).array_sum($qty_sum[$nump])." QTY(S) ".$no." ITEM(S)\n");
            //$printer -> text($no." ITEM(S)\n");
            $printer -> text(str_repeat('=', $lebar_pixel)."\n");
        
            if($center == 'On')
            {
                $printer -> setJustification(Printer::JUSTIFY_CENTER);
            }
            // $printer -> text("TERIMA KASIH \n");
            
            $mada_footer = EscposImage::load($image_directory.'/'.$data->app_logo);
            $printer->bitImage($mada_footer);
            if($order->space_footer > 0){$printer -> feed($order->space_footer); }
            if($order->cutter == "On")
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
            $printer->close();
            /**LOOPING ORDERS**/

            //var_dump($pr_conn);
        }
        /**JIKA ADA ORDERS**/

    echo "<script>window.close();</script>";
?>