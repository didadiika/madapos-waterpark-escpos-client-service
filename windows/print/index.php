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



if(count($data->printers) > 0){

    foreach($data->printers as $printer){
    #----------Setting Paper-----------#
    if($printer->printer_type == '58' || ($printer->printer_paper_size == '80mm' && $printer->printer_type == '80'))
    {
        $max_width = 48; 
        if($printer->printer_paper_size == '58mm'){
            $max_width = 32;
        } 
        $center = 'On';
        $right = 'On';
    }
    else{
        $max_width = 32;
        $center = 'Off';
        $right = 'Off';
    }
    #----------Setting Paper-----------#

        $connector = ($printer->printer_conn == 'USB') ? new WindowsPrintConnector($printer->printer_address) : new NetworkPrintConnector($printer->printer_address) ;
        if($connector){ #If Connector
            $print = new Printer($connector);#Open Koneksi Printer
            if(count($printer->jobs) > 0){

                foreach($printer->jobs as $job){
                    for($i = 0; $i < $job->autoprint_quantity; $i++){ #Foreach Autoprint Quantity
                    #----------------------------------RECEIPT-------------------------------------#
                    if($job->job == 'Receipt' && $data->waiting->receipt == true){
                        
                        if($data->waiting->push_drawer == true && ($printer->printer_cash_drawer == 1 || $printer->printer_cash_drawer == true)){
                            $print->pulse(0, 100, 100);
                        }

                        if($center == 'On')
                        {
                            $print -> setJustification(Printer::JUSTIFY_CENTER);
                        }
                        $logo = EscposImage::load($image_directory.'/default.png');
                        $print->bitImage($logo);
                        $print->selectPrintMode(Printer::MODE_FONT_A);
                        $print->setEmphasis(true);
                        $print->text($data->store->header_bill."\n");
                        $print->text($data->store->address."\n");
                        $print->text($data->store->city."\n");
                        $print->text($data->store->phone."\n");

                        $print->selectPrintMode(Printer::MODE_FONT_A | Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH);
                                               
                        $print -> setTextSize(1, 1);
                        $print->setEmphasis(true);//berguna mempertebal huruf
                        if($center == 'On')
                        {
                        $print -> setJustification(Printer::JUSTIFY_CENTER);
                        }
                        $print -> text(str_repeat('=',$max_width)."\n");
                        $print -> setBarcodeHeight(40);
                        $print -> setBarcodeWidth(2);
                        $print->barcode($data->receipt->sale_uid, Printer::BARCODE_CODE39);
                        $print->setEmphasis(true);//berguna mempertebal huruf
                        $print -> setJustification(Printer::JUSTIFY_LEFT);
                        $print->text("UID      : ".substr($data->receipt->sale_uid,0,$max_width - 11)."\n");
                        $customer_name = ($data->receipt->customer->is_default == true) ? $data->receipt->customer_alias : $data->receipt->customer->name;
                        $print->text("Pelanggan: ".substr($customer_name,0,$max_width - 11)."\n");
                        $print->text("Tanggal  : ".substr(tanggal_time_db_to_id($data->receipt->date),0,$max_width - 11)."\n");
                        $print->text("Kasir    : ".substr($data->receipt->cashier->name,0,$max_width - 11)."\n");

                        $max_qty = 4;
                        $space_between_qty_item = 1;
                        $max_item = $max_width - $max_qty - $space_between_qty_item;
                        $max_price = 10;
                        $max_sub_total = 10;
                        $print -> setJustification(Printer::JUSTIFY_LEFT);
                        
                        $print -> text(str_repeat('-', $max_width)."\n");
                        if($printer->printer_paper_size == "80mm"){
                            $print -> text(" Qty ".str_repeat(' ',$space_between_qty_item)."Item".str_repeat(' ',11)."Price".str_repeat(' ',12)."Sub Total\n");
                        } else if($printer->printer_paper_size == "58mm"){
                            $print -> text(" Qty ".str_repeat(' ',$space_between_qty_item)."Item".str_repeat(' ',3)."Price".str_repeat(' ',4)."Sub Total\n");
                        }
                        $print -> text(str_repeat('-', $max_width)."\n");

                        #CARTS
                        $no = 0;
                        foreach($data->carts as $cart){
                            $no++;
                                $item = substr(ucwords(strtolower($cart->product->name)),0,$max_width - $max_qty - $space_between_qty_item);#12
                                $qty = $cart->qty;#1
                                $note = $cart->note;
                                $price = uang((int) $cart->price_after_disc);#6
                                $sub_total = uang($cart->qty * (int)$cart->price_after_disc);#6
                                $space_before_note= 6;
                                $space_before_price = 16;
                                $space_between_price_sub_total = 12;
                                if($printer->printer_paper_size == "58mm") {  $space_before_price = 8; $space_between_price_sub_total = 4;}
                                
                                $print -> setJustification(Printer::JUSTIFY_LEFT);
                                $print -> text(str_repeat(' ',$max_qty - strlen($qty)).$qty.str_repeat(' ',$space_between_qty_item).$item."\n");
                                if($note){
                                    $print -> text(str_repeat(' ',$space_before_note)."*".$note."\n");
                                }
                                $print -> text(str_repeat(' ',$space_before_price).str_repeat(' ',$max_price - strlen($price)).$price.str_repeat(' ',$space_between_price_sub_total).str_repeat(' ',$max_sub_total - strlen($sub_total)).$sub_total."\n");
                        }
                        $total = $data->receipt->total_before_disc;
                        $total_length = strlen(uang($total));
                        if($data->receipt->disc_number > 0){
                            $disc = "-".uang((int)$data->receipt->disc_number);
                            $grand_total = $total - (int)$data->receipt->disc_number;
                        } else if($data->receipt->disc_percent > 0){
                            $disc = "-".$data->receipt->disc_percent."%";
                            $grand_total = $total - ($total * (int)$data->receipt->disc_percent/100);
                        } else {
                            $disc = "-";
                            $grand_total = $total;
                        }
                        $discb_length = strlen($disc);
                        $grand_length = strlen(uang($grand_total));
                        $paid = (int)$data->receipt->paid;
                        $paid_length = strlen(uang($paid));
                        $change = ((int)$data->receipt->changed == 0) ? '-' : uang((int)$data->receipt->changed);
                        $change_length = strlen($change);
                        $payment = $data->payments;
                        $payment_length = strlen($payment);
                        

                        $print -> text(str_repeat('-', $max_width)."\n");
                        $print -> setJustification(Printer::JUSTIFY_LEFT);

                        $space_before = 25;
                        if($printer->printer_paper_size == "58mm") {  $space_before = 9; }
                        $max_center = 8;
                        $max_right = 15;

                        $space_between = $max_width - 11 - $discb_length;
                        $print -> text(str_repeat(' ',$space_before)."DISC".str_repeat(' ',$max_center - strlen("DISC:")).":".str_repeat(' ',$max_right - strlen($disc)).$disc."\n");
                        $space_total = $max_width - 11 - $grand_length;
                        $print -> text(str_repeat(' ',$space_before)."TOTAL".str_repeat(' ',$max_center - strlen("TOTAL:")).":".str_repeat(' ',$max_right - strlen(uang($grand_total))).uang($grand_total)."\n");
                        $space_paid = $max_width - 11 - $paid_length;
                        $print -> text(str_repeat(' ',$space_before)."BAYAR".str_repeat(' ',$max_center - strlen("BAYAR:")).":".str_repeat(' ',$max_right - strlen(uang($paid))).uang($paid)."\n");
                        $space_change = $max_width- 11 - $change_length;
                        $print -> text(str_repeat(' ',$space_before)."KEMBALI".str_repeat(' ',$max_center - strlen("KEMBALI:")).":".str_repeat(' ',$max_right - strlen($change)).$change."\n");
                        $space_payment = $max_width - 11 - $payment_length;
                        $print -> text(str_repeat(' ',$space_before)."PAYMENT".str_repeat(' ',$max_center - strlen("PAYMENT:")).":".str_repeat(' ',$max_right - strlen($payment)).$payment."\n");
                        $print -> setJustification(Printer::JUSTIFY_LEFT);
                        $print -> text(str_repeat('=', $max_width)."\n");
                        $print -> setJustification(Printer::JUSTIFY_LEFT);
                        $print->text($data->print_setting->printer_cashier_footer_info."\n");
                        if($center == 'On')
                        {
                            $print -> setJustification(Printer::JUSTIFY_CENTER);
                        }
                        $print -> text("TERIMA KASIH \n");
                        $mada_footer = EscposImage::load($image_directory.'/'.$data->app_logo);
                        $print->bitImage($mada_footer);
                        if($printer->printer_footer_space > 0){$print -> feed($printer->printer_footer_space); }
                        $print->cut();#Memotong kertas
                        
                        
                        
                    }
                    #----------------------------------END RECEIPT-------------------------------------#

                    #----------------------------------TICKET-------------------------------------#
                    if($job->job == 'Ticket' && $data->waiting->ticket == true){

                        foreach($printer->ticket_receipts as $ticket_receipt){# Foreach Ticket Receipts #}
                            
                        
                        if(count($ticket_receipt->tickets) > 0){# Jika Tiket Ada#
                            
                            foreach($ticket_receipt->tickets as $ticket){# Membuka Tiket #

                                $print -> getPrintConnector() -> write(PRINTER::ESC . "B" . chr(4) . chr(1));
                                
                                for($i = 0; $i < $ticket->qty; $i++){# Quantitas Tiket

                                    $print->selectPrintMode(Printer::MODE_FONT_A);
                                    $print -> setJustification(Printer::JUSTIFY_LEFT);
                                    $print -> text("#".$ticket_receipt->category_name."\n");

                                    if($center == 'On')
                                    {
                                    $print -> setJustification(Printer::JUSTIFY_CENTER);
                                    }
                                    $print->text($data->store->header_bill."\n");
                                    if($center == 'On')
                                    {
                                        $print -> setJustification(Printer::JUSTIFY_CENTER);
                                    }
                                    $print->selectPrintMode(Printer::MODE_FONT_A | Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH);
                                    
                                    $print -> setTextSize(1, 1);
                                    $print->setEmphasis(true);//berguna mempertebal huruf
                                
                                    if($center == 'On')
                                    {
                                    $print -> setJustification(Printer::JUSTIFY_CENTER);
                                    }
                                    $print -> text(str_repeat('=',$max_width)."\n");
                                    $print -> setJustification(Printer::JUSTIFY_LEFT);
                                    $print->text("Tanggal : ".$data->receipt->date."\n");
                                    $print -> setJustification(Printer::JUSTIFY_CENTER);
                                    $print -> setTextSize(2, 1);
                                    $customer_name = ($data->receipt->customer->is_default == true) ? $data->receipt->customer_alias : $data->receipt->customer->name;
                                    $print->text(substr($customer_name,0,24)."\n");
                                    $print -> setTextSize(1, 1);
                                    #Judul
                                    $print -> text(str_repeat('-', $max_width)."\n");
                                    $batas = $max_width;

                                    if($center == 'On')
                                    {
                                        $print -> setJustification(Printer::JUSTIFY_CENTER);
                                    }
                                    $print -> text(strtoupper($ticket->product_name)."\n");
                                    $print -> text("IDR ".uang($ticket->price_after_disc)."\n");
                                    $size = 5;
                                    $print -> qrCode($ticket->id, Printer::QR_ECLEVEL_L,$size);
                                    $print -> feed();
                                    $print -> text($ticket->id."\n");
                                            
                                        
                                    $print -> setJustification(Printer::JUSTIFY_LEFT);
                                    $print -> text(str_repeat('=', $max_width)."\n");
                                
                                    if($center == 'On')
                                    {
                                        $print -> setJustification(Printer::JUSTIFY_CENTER);
                                    }
                                    
                                    $mada_footer = EscposImage::load($image_directory.'/'.$data->app_logo);
                                    $print->bitImage($mada_footer);
                                    if($printer->printer_footer_space > 0){$print -> feed($printer->printer_footer_space); }
                                    $print->cut();#Memotong kertas
                                    

                                }#End Quantitas Tiket

                            }# Membuka Tiket #
                        }#End Jika Tiket Ada#
                        }# End Foreach Ticket Receipts #}

                    }
                    #----------------------------------END TICKET-------------------------------------#

                    #----------------------------------ORDER-------------------------------------#
                    else if($job->job == 'Order' && $data->waiting->order == true){
                        
                        if(count($printer->order_per_category) > 0){#If Count Categories
                            foreach($printer->order_per_category as $category){#Foreach Category
                                if(count($category->orders) > 0){ #If Count Order
                                    $print -> getPrintConnector() -> write(PRINTER::ESC . "B" . chr(4) . chr(1));
                                    
                                    $print->selectPrintMode(Printer::MODE_FONT_A);
                                    $print -> setJustification(Printer::JUSTIFY_LEFT);
                                    $print -> text("#".$category->category_name."\n");

                                    if($center == 'On')
                                    {
                                    $print -> setJustification(Printer::JUSTIFY_CENTER);
                                    }
                                    
                                    $print->text($data->store->header_bill."\n");
                                    
                                    $print->selectPrintMode(Printer::MODE_FONT_A | Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH);
                    
                                    $print -> setTextSize(1, 1);
                                    $print->setEmphasis(true);
                                    if($center == 'On')
                                    {
                                    $print -> setJustification(Printer::JUSTIFY_CENTER);
                                    }
                                    $print -> text(str_repeat('=',$max_width)."\n");
                                    $print -> setJustification(Printer::JUSTIFY_LEFT);
                                    if(strpos($data->receipt->date, ' ') == true){
                                        $print->text("Tanggal : ".tanggal_time_db_to_id($data->receipt->date)."\n");
                                    } else {
                                        $print->text("Tanggal : ".tanggal_db_to_id($data->receipt->date)."\n");
                                    }
                                    
                                    $print -> setJustification(Printer::JUSTIFY_CENTER);
                                    $print -> setTextSize(2, 1);
                                    $customer_name = ($data->receipt->customer->is_default == true) ? $data->receipt->customer_alias : $data->receipt->customer->name;
                                    $print->text(substr($customer_name,0,24)."\n");
                                    $print -> setTextSize(1, 1);
                                    #Judul
                                    $print -> text(str_repeat('-', $max_width)."\n");

                                    $no = 0;
                                    $spasi_max_qty = 4;
                                    $spasi_between_qty_items = 1;
                                    $print -> setJustification(Printer::JUSTIFY_LEFT);
                                    $print -> text(" Qty".str_repeat(' ',$spasi_between_qty_items)."Item"."\n");
                                    $print -> text(str_repeat('-', $max_width)."\n");
                                    
                                    foreach($category->orders as $order){
                                    $no++;
                                        $product_name = ucwords(strtolower($order->product_name));#12
                                        $qty = $order->qty;#1
                                        $note = $order->note;#6
                                        
                                        $print -> setJustification(Printer::JUSTIFY_LEFT);
                                        $print -> text(str_repeat(' ',$spasi_max_qty - strlen($qty)).$qty.str_repeat(' ',$spasi_between_qty_items).$product_name."\n");
                                        $print -> setJustification(Printer::JUSTIFY_LEFT);
                                        
                                        if(isset($order->addition)){
                                            if($order->addition == 'Yes'){
                                                $print -> text("     *Tambahan\n");
                                            }
                                        }

                                        if($note){
                                            $print -> text("      **".$note."\n");
                                        }
                                    }
                                    $print -> text(str_repeat('-', $max_width)."\n");
                                    $print -> setJustification(Printer::JUSTIFY_LEFT);
                                    $print -> text($no." ITEM(S)\n");
                                    $print -> text(str_repeat('=', $max_width)."\n");
                                
                                    if($center == 'On')
                                    {
                                        $print -> setJustification(Printer::JUSTIFY_CENTER);
                                    }
                                    $print -> text("TERIMA KASIH \n");
                                    
                                    $mada_footer = EscposImage::load($image_directory.'/'.$data->app_logo);
                                    $print->bitImage($mada_footer);
                                    if($printer->printer_footer_space > 0){$print -> feed($printer->printer_footer_space); }
                                    $print->cut();#Memotong kertas
                                    

                                }#End If Count Order
                            
                            }#End Foreach Category
                        }#End If Count Categories
                    }
                    #----------------------------------END ORDER-------------------------------------#
                    }#EndForeach Autoprint Quantity
                }#End Foreach Jobs

            }#End Count Jobs
            $print->close();#Close Koneksi Printer
        }#End If Connector
    }#End Foreach Printers

}#End Count Printers
?>