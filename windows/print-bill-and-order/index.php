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
            foreach($data->orders as $order){
            $pr_conn[$nump] = $bill->conn;
            $pr_usb[$nump] = $bill->usb;
            $pr_ip[$nump] = $bill->ip;
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
                
                /** JALANKAN PERINTAH PRINTER DISINI**/
                if($order->contents){
                    if($order->beep == "On")
                    {
                        $printer -> getPrintConnector() -> write(PRINTER::ESC . "B" . chr(4) . chr(1));
                    }    
                    
                    /** JUMLAH PRINT **/
                    for($i= 0; $i < $jumlah_print; $i++ ){
                        /** LOOPING MAKANAN **/
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
                    $printer->text("Tanggal   : ".$data->customer->date."\n");
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
                    foreach($order->contents as $content){
                    $no++;
                        $nama_produk = $content->name;#12
                        $qty = $content->qty;#1
                        $note = $content->note;#6
                        $addition = $content->addition;#6
                        
                        $printer -> setJustification(Printer::JUSTIFY_LEFT);
                        $printer -> text(str_repeat(' ',$spasi_max_qty - strlen($qty)).$qty.str_repeat(' ',$spasi_between_qty_items).$nama_produk."\n");
                        $printer -> setJustification(Printer::JUSTIFY_LEFT);
                        if($addition == 'Yes'){
                            $printer -> text("     *Tambahan\n");
                        }
                        if($note != "" && $note != null){
                            $printer -> text("      **".$note."\n");
                        }
                        
                    }
                        
                    
                
            $printer -> text(str_repeat('-', $lebar_pixel)."\n");
            $printer -> setJustification(Printer::JUSTIFY_LEFT);
            $printer -> text($no." ITEM(S)\n");
            $printer -> text(str_repeat('=', $lebar_pixel)."\n");
        
            if($center == 'On')
            {
                $printer -> setJustification(Printer::JUSTIFY_CENTER);
            }
            $printer -> text("TERIMA KASIH \n");
            
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
            }
            $printer->close();
            /**LOOPING ORDERS**/
        }
        /**JIKA ADA ORDERS**/
        /**JIKA ADA BILLS**/
        $pr_conn2 = array();
        $pr_usb2 = array();
        $pr_ip2 = array();
        $nump2 = 0;
        if($data->bills){
            /**LOOPING ORDERS**/
            foreach($data->bills as $bill){
        $pr_conn2[$nump2] = $bill->conn;
        $pr_usb2[$nump2] = $bill->usb;
        $pr_ip2[$nump2] = $bill->ip;
                /**INISIALIASASI SETTING KERTAS DAN ALIGMENT**/
                if(($bill->paper == '58mm' && $bill->type == '58') || ($bill->paper == '80mm' && $bill->type == '80'))
                {
                    if($bill->paper == '58mm'){$lebar_pixel = 32;}else {$lebar_pixel = 48; }
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
                
                if($nump2 == 0){
                    if($bill->conn == "USB"){  
                        $connector = new WindowsPrintConnector($bill->usb); 
                    } else if($bill->conn == "Ethernet"){  
                        $connector = new NetworkPrintConnector($bill->ip); 
                    } else {
                        $connector = '';/**BLUETOOTH CONNECTOR JIKA SUDAH SUPPORT**/
                    }
                    $printer = new Printer($connector);
                } else {
                    if($bill->conn == "USB"){  
                        
                            $printer->close();
                            $connector = new WindowsPrintConnector($bill->usb);
                            $printer = new Printer($connector);
                        
                    } else if($bill->conn == "Ethernet"){  
                        if($pr_conn2[$nump2 - 1] == "Ethernet" && $bill->ip !=  $pr_ip2[$nump2 - 1])
                        {
                            /** JIKA CONNECTOR SEBELUMNYA MENGGUNAKAN ETHERNET DAN IP YANG SAMA MAKA SETELAH DI CLOSE CONNECTOR TIDAK DAPAT
                             * DIBUKA LAGI (UNTUK KSWEB ANDROID) MAKA DIATASI DG SCRIPT INI **/
                            $printer->close();
                            $connector = new NetworkPrintConnector($bill->ip);
                            $printer = new Printer($connector);
                        } 
                         
                    } else {
                        $connector = '';/**BLUETOOTH CONNECTOR JIKA SUDAH SUPPORT**/
                    }
                }
                /**KONEKSI PRINTER**/

                //try {
                /** JALANKAN PERINTAH PRINTER DISINI**/
                if($bill->contents){
                    if($bill->beep == "On")
                    {
                        $printer -> getPrintConnector() -> write(PRINTER::ESC . "B" . chr(4) . chr(1));
                    }    
                    
                    /** JUMLAH PRINT **/
                    for($i= 0; $i < $jumlah_print; $i++ ){
                        /** LOOPING MAKANAN **/
                        $printer->selectPrintMode(Printer::MODE_FONT_A);
                        $printer -> setJustification(Printer::JUSTIFY_LEFT);
                        $printer -> setTextSize(2, 2);
                        $printer->text("#BILL\n");
                        $printer -> setTextSize(1, 1);
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
                    $printer -> text(str_repeat('=', floor(($lebar_pixel - strlen("BILL") )/2) )."BILL".str_repeat('=', floor(($lebar_pixel - strlen("BILL"))/2) )."\n");
                    $printer -> setJustification(Printer::JUSTIFY_LEFT);
                    $printer->text("Pelanggan   : ".$data->customer->customer_name."\n");
                    if($center == 'On')
                    {
                    $printer -> setJustification(Printer::JUSTIFY_CENTER);
                    }
                    $printer -> setTextSize(2, 1);
                    $printer->text(strtoupper("#UNPAID")."\n");
                    $printer -> setTextSize(1, 1);
                    #Judul
                    $printer -> text(str_repeat('-', $lebar_pixel)."\n");
                    $batas = $lebar_pixel;
        
                    #Item
                    $no = 0;
                    $spasi_from_qty = 4;
                    foreach($bill->contents as $content){
                    $no++;
                        $nama_produk = $content->name;#12
                        $qty = $content->qty;#1
                        $harga = uang($content->price);#6
                        $opr = " x ";#3
                        $sub_total = uang($total[] = $content->qty*$content->price);#6

                        $panjang_tengah = strlen($harga);
                        $sisa_batas = $batas - $panjang_tengah;
                        $sisa_batas_kiri = floor($sisa_batas/2);
                        $sisa_batas_kanan = ceil($sisa_batas/2) - strlen($sub_total);
                        
                        $printer -> setJustification(Printer::JUSTIFY_LEFT);
                        $printer -> text($qty."x".str_repeat(' ',$spasi_from_qty - strlen($qty)).$nama_produk."\n");
                        $printer -> text(str_repeat(' ', $sisa_batas_kiri).$harga.str_repeat(' ', $sisa_batas_kanan).$sub_total."\n");
                        }
                        $total = array_sum($total);
                        
                    
            $batas = 10;
            $panjang_total = strlen(uang($total));
            
            if($data->customer->disc_number > 0){
                $disc = "-".uang((int)$data->customer->disc_number);
                $grand_total = $total - (int)$data->customer->disc_number;
            } else if($data->customer->disc_percent > 0){
                $disc = "-".$data->customer->disc_percent."%";
                $grand_total = $total - ($total * (int)$data->customer->disc_percent/100);
            } else {
                $disc = "-";
                $grand_total = $total;
            }
            
            $panjang_discb = strlen($disc);
            $panjang_grand = strlen(uang($grand_total));


            if($bill->type == '80'){
            $batas_kanan = 48 - $lebar_pixel;
            } else { $batas_kanan = 0; }
            $printer -> text(str_repeat('-', $lebar_pixel)."\n");
            $printer -> setJustification(Printer::JUSTIFY_RIGHT);
            $printer -> text("SUB TOTAL: ".str_repeat(' ', $batas - $panjang_total + 2).uang($total).str_repeat(' ', $batas_kanan)."\n");
            $printer -> text("DISC     : ".str_repeat(' ', $batas - $panjang_discb + 2).$disc.str_repeat(' ', $batas_kanan)."\n");
            $printer -> text("TOTAL    : ".str_repeat(' ', $batas - $panjang_grand + 2).uang($grand_total).str_repeat(' ', $batas_kanan)."\n");
            $printer -> setJustification(Printer::JUSTIFY_LEFT);
            $printer -> text(str_repeat('=', $lebar_pixel)."\n");
            if($center == 'On')
            {
                $printer -> setJustification(Printer::JUSTIFY_CENTER);
            }
            $mada_footer = EscposImage::load($image_directory.'/'.$data->app_logo);
            $printer->bitImage($mada_footer);
            if($bill->space_footer > 0){$printer -> feed($bill->space_footer); }
            if($bill->cutter == "On")
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
            }
            $printer->close();
            /**LOOPING ORDERS**/
        }
        /**JIKA ADA BILLS**/

    echo "<script>window.close();</script>";
?>