<?php
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
require __DIR__ . '/../../vendor/autoload.php';
use Mike42\Escpos\ImagickEscposImage;#Butuh Ekstensi Imagick
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\PrintConnectors\RawbtPrintConnector;
use Mike42\Escpos\PrintConnectors\CupsPrintConnector;
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

$nump = 0;
$pr_conn = array();
$pr_usb = array();
$pr_ip = array();

        /***----------------------------------------BARIS RECEIPT-----------------------------------------------*/
        if($data->receipts){
            
            
            /**LOOPING ORDERS**/
            foreach($data->receipts as $receipt){
            
            if($receipt->waiting_for_printing == "true"){
            
            $pr_conn[$nump] = $receipt->conn;
            $pr_usb[$nump] = $receipt->usb;
            $pr_ip[$nump] = $receipt->ip;
                /**INISIALIASASI SETTING KERTAS DAN ALIGMENT**/
                if($receipt->type == '58' || ($receipt->paper == '80mm' && $receipt->type == '80'))
                {
                    $lebar_pixel = 48; 
                    if($receipt->paper == '58mm'){
                        $lebar_pixel = 32;
                    } 
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
                    if($receipt->conn == "USB"){  
                        $connector = new WindowsPrintConnector($receipt->usb); 
                    } else if($receipt->conn == "Ethernet"){  
                        $connector = new NetworkPrintConnector($receipt->ip); 
                    } else {
                        $connector = '';/**BLUETOOTH CONNECTOR JIKA SUDAH SUPPORT**/
                    }
                    $printer = new Printer($connector);
                } else {
                    if($receipt->conn == "USB"){  
                        
                            $printer->close();
                            $connector = new WindowsPrintConnector($receipt->usb);
                            $printer = new Printer($connector);
                        
                    } else if($receipt->conn == "Ethernet"){  
                        if($pr_conn[$nump - 1] == "Ethernet" && $receipt->ip !=  $pr_ip[$nump - 1])
                        {
                            /** JIKA CONNECTOR SEBELUMNYA MENGGUNAKAN ETHERNET DAN IP YANG SAMA MAKA SETELAH DI CLOSE CONNECTOR TIDAK DAPAT
                             * DIBUKA LAGI (UNTUK KSWEB ANDROID) MAKA DIATASI DG SCRIPT INI **/
                            $printer->close();
                            $connector = new NetworkPrintConnector($receipt->ip);
                            $printer = new Printer($connector);
                        } 
                         
                    } else {
                        $connector = '';/**BLUETOOTH CONNECTOR JIKA SUDAH SUPPORT**/
                    }
                }

                /** JALANKAN PERINTAH PRINTER DISINI**/
                if($receipt->contents){
                    if($receipt->beep == "On")
                    {
                        $printer -> getPrintConnector() -> write(PRINTER::ESC . "B" . chr(4) . chr(1));
                    }    
                    
                    /** JUMLAH PRINT **/
                    for($i= 0; $i < $jumlah_print; $i++ ){
                   

                    // Logo
                    if($center == 'On')
                    {
                        $printer -> setJustification(Printer::JUSTIFY_CENTER);
                    }
                    $logo = EscposImage::load($image_directory.'/default.png');
                    if($center == 'On')
                    {
                    $printer -> setJustification(Printer::JUSTIFY_CENTER);
                    }
                    $printer->bitImage($logo);
                    $printer->selectPrintMode(Printer::MODE_FONT_A);
                    $printer->setEmphasis(true);//berguna mempertebal huruf
                    $printer->text($data->store->header_bill."\n");
                    $printer->text($data->store->address."\n");
                    $printer->text($data->store->city."\n");
                    $printer->text($data->store->phone."\n");
                    // Logo
                    


                    $printer->selectPrintMode(Printer::MODE_FONT_A | Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH);



                    // Dine In / Take Away
                    $printer -> setTextSize(1, 1);
                    $printer->setEmphasis(true);//berguna mempertebal huruf
                    if($center == 'On')
                    {
                    $printer -> setJustification(Printer::JUSTIFY_CENTER);
                    }
                    $printer -> text(str_repeat('=',$lebar_pixel)."\n");
                    // Dine In / Take Away
                    
                    
                    // Header
                    $printer -> setBarcodeHeight(40);
                    $printer -> setBarcodeWidth(2);
                    $printer->barcode($data->customer->sale_uid, Printer::BARCODE_CODE39);
                    $printer -> setJustification(Printer::JUSTIFY_LEFT);
                    $printer->text("UID      : ".substr($data->customer->sale_uid,0,$lebar_pixel - 11)."\n");
                    if($data->customer->customer_is_default == 1){
                        $printer->text("Pelanggan: ".substr($data->customer->customer_alias,0,$lebar_pixel - 11)."\n");
                    } else {
                        $printer->text("Pelanggan: ".substr($data->customer->customer_name,0,$lebar_pixel - 11)."\n");
                    }
                    $printer->text("Tanggal  : ".substr($data->customer->date,0,$lebar_pixel - 11)."\n");
                    $printer->text("Kasir    : ".substr($data->customer->cashier,0,$lebar_pixel - 11)."\n");
                    // Header





                // Items
                    if($center == 'On')
                    {
                        $printer -> setJustification(Printer::JUSTIFY_CENTER);
                    }
                    $printer -> text(str_repeat('-', $lebar_pixel)."\n");
        
                    $no = 0;
                    $max_qty = 3;
                    $space_between_qty_item = 1;
                    $max_item = $lebar_pixel - $max_qty - $space_between_qty_item;
                    $max_price = 10;
                    $max_sub_total = 10;
                        
                    $printer -> setJustification(Printer::JUSTIFY_LEFT);
                    if($receipt->paper == "80mm"){

                        
                        $printer -> text("Qty".str_repeat(' ',$space_between_qty_item)."Item".str_repeat(' ',13)."Price".str_repeat(' ',13)."Sub Total\n");
                    } else if($receipt->paper == "58mm"){
                        $printer -> text("Qty".str_repeat(' ',$space_between_qty_item)."Item".str_repeat(' ',5)."Price".str_repeat(' ',5)."Sub Total\n");
                    }
                    $printer -> text(str_repeat('-', $lebar_pixel)."\n");
                    foreach($receipt->contents as $content){
                    $no++;
                        $item = substr(ucwords(strtolower($content->name)),0,$lebar_pixel - $max_qty - $space_between_qty_item);#12
                        $qty = $content->qty;#1
                        $note = $content->note;
                        $price = uang($content->price);#6
                        $sub_total = uang($total[] = $content->qty*$content->price);#6

                        $space_before_price = 16;
                        $space_between_price_sub_total = 12;
                        if($receipt->paper == "58mm") {  $space_before_price = 8; $space_between_price_sub_total = 4;}
                        
                        $printer -> setJustification(Printer::JUSTIFY_LEFT);
                        $printer -> text(str_repeat(' ',$max_qty - strlen($qty)).$qty.str_repeat(' ',$space_between_qty_item).$item."\n");
                        $printer -> text(str_repeat(' ',$space_before_price).str_repeat(' ',$max_price - strlen($price)).$price.str_repeat(' ',$space_between_price_sub_total).str_repeat(' ',$max_sub_total - strlen($sub_total)).$sub_total."\n");
                        }
                $total = array_sum($total);
               // Items         
                    

            
            $total_length = strlen(uang($total));
            
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
            
            $discb_length = strlen($disc);
            $grand_length = strlen(uang($grand_total));
            $paid = (int)$data->customer->paid;
            $paid_length = strlen(uang($paid));
            $change = ((int)$data->customer->changed == 0) ? '-' : uang((int)$data->customer->changed);
            $change_length = strlen($change);
            $payment = $data->customer->payment;
            $payment_length = strlen($payment);

            $printer -> text(str_repeat('-', $lebar_pixel)."\n");
            $printer -> setJustification(Printer::JUSTIFY_LEFT);

            $space_before = 25;
            if($receipt->paper == "58mm") {  $space_before = 9; }
            $max_center = 8;
            $max_right = 15;

            $space_between = $lebar_pixel - 11 - $discb_length;
            $printer -> text(str_repeat(' ',$space_before)."DISC".str_repeat(' ',$max_center - strlen("DISC:")).":".str_repeat(' ',$max_right - strlen($disc)).$disc."\n");
            $space_total = $lebar_pixel - 11 - $grand_length;
            $printer -> text(str_repeat(' ',$space_before)."TOTAL".str_repeat(' ',$max_center - strlen("TOTAL:")).":".str_repeat(' ',$max_right - strlen(uang($grand_total))).uang($grand_total)."\n");
            $space_paid = $lebar_pixel - 11 - $paid_length;
            $printer -> text(str_repeat(' ',$space_before)."BAYAR".str_repeat(' ',$max_center - strlen("BAYAR:")).":".str_repeat(' ',$max_right - strlen(uang($paid))).uang($paid)."\n");
            $space_change = $lebar_pixel - 11 - $change_length;
            $printer -> text(str_repeat(' ',$space_before)."KEMBALI".str_repeat(' ',$max_center - strlen("KEMBALI:")).":".str_repeat(' ',$max_right - strlen($change)).$change."\n");
            $space_payment = $lebar_pixel - 11 - $payment_length;
            $printer -> text(str_repeat(' ',$space_before)."PAYMENT".str_repeat(' ',$max_center - strlen("PAYMENT:")).":".str_repeat(' ',$max_right - strlen($payment)).$payment."\n");
            $printer -> setJustification(Printer::JUSTIFY_LEFT);
            $printer -> text(str_repeat('=', $lebar_pixel)."\n");
            $printer -> setJustification(Printer::JUSTIFY_LEFT);
            $printer->text($data->print_setting->printer_cashier_footer_info."\n");
            if($center == 'On')
            {
                $printer -> setJustification(Printer::JUSTIFY_CENTER);
            }
            $printer -> text("TERIMA KASIH \n");
            $mada_footer = EscposImage::load($image_directory.'/'.$data->app_logo);
            $printer->bitImage($mada_footer);
            if($receipt->space_footer > 0){$printer -> feed($receipt->space_footer); }
            if($receipt->cutter == "On")
            {
                $printer->cut();#Memotong kertas
            }
                
                
            
            /** LOOPING MAKANAN **/
            }
            /** JUMLAH PRINT **/
                
            }
            
        $nump++;       
                /** JALANKAN PERINTAH PRINTER DISINI**/
        }//end if waiting for printing
        
            }
            
            /**LOOPING ORDERS**/
        }
        /***----------------------------------------END BARIS RECEIPT-----------------------------------------------*/



        /***----------------------------------------BARIS ORDER-------------------------------------------------*/
        if($data->orders){
            $qty_sum = array();
            foreach($data->orders as $order){
            
            if($order->waiting_for_printing == "true"){
            
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
                        
                        $printer -> setJustification(Printer::JUSTIFY_LEFT);
                        $printer -> text(str_repeat(' ',$spasi_max_qty - strlen($qty)).$qty.str_repeat(' ',$spasi_between_qty_items).$nama_produk."\n");
                        $printer -> setJustification(Printer::JUSTIFY_LEFT);
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
                }// end if waiting for printing
            }
            /**LOOPING ORDERS**/
        }
        /***----------------------------------------END BARIS ORDER-------------------------------------------------*/

        /***----------------------------------------BARIS TIKET-------------------------------------------------*/
        if($data->tickets){
            /**LOOPING ORDERS**/
            foreach($data->tickets as $ticket){
 
            if($ticket->waiting_for_printing == "true"){
 
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
                
            }
            $nump++;
            }//end if waiting for printing
            }//END FOREACH TICKET
        }
        /***----------------------------------------END BARIS TIKET-------------------------------------------------*/

    $printer->close();
    //echo "<script>window.close();</script>";
?>