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




$json = $_POST['json'];
$data = json_decode($json);
        
        /**JIKA ADA BILLS**/
        if($data->printers){
            $pr_conn = array();
            $pr_usb = array();
            $pr_ip = array();
            $nump = 0;
            /**LOOPING ORDERS**/
            
            $pr_conn[$nump] = $data->printers->printer_conn;
            $pr_usb[$nump] = $data->printers->printer_usb_name;
            $pr_ip[$nump] = $data->printers->printer_ip_address;
                
                /**INISIALIASASI SETTING KERTAS DAN ALIGMENT**/

                /**KONEKSI PRINTER**/
                
                if($nump == 0){
                    if($data->printers->printer_conn == "USB"){  
                        $connector = new WindowsPrintConnector($data->printers->printer_usb_name); 
                    } else if($data->printers->printer_conn == "Ethernet"){  
                        $connector = new NetworkPrintConnector($data->printers->printer_ip_address); 
                    } else {
                        $connector = '';/**BLUETOOTH CONNECTOR JIKA SUDAH SUPPORT**/
                    }
                    $printer = new Printer($connector);
                } else {
                    if($data->printers->printer_conn == "USB"){  
                        
                            $printer->close();
                            $connector = new WindowsPrintConnector($data->printers->printer_usb_name);
                            $printer = new Printer($connector);
                        
                    } else if($data->printers->printer_conn == "Ethernet"){  
                        if($pr_conn[$nump - 1] == "Ethernet" && $data->printers->printer_ip_address !=  $pr_ip[$nump - 1])
                        {
                            /** JIKA CONNECTOR SEBELUMNYA MENGGUNAKAN ETHERNET DAN IP YANG SAMA MAKA SETELAH DI CLOSE CONNECTOR TIDAK DAPAT
                             * DIBUKA LAGI (UNTUK KSWEB ANDROID) MAKA DIATASI DG SCRIPT INI **/
                            $printer->close();
                            $connector = new NetworkPrintConnector($data->printers->printer_ip_address);
                            $printer = new Printer($connector);
                        } 
                         
                    } else {
                        $connector = '';/**BLUETOOTH CONNECTOR JIKA SUDAH SUPPORT**/
                    }
                }

                $printer->text("---------------MadaPOS-------------\n");
                $printer->text("#\n");
                $printer->text("#\n");
                $printer->text("#\n");
                $printer->text("#\n");
                $printer->text("#".$data->text."\n");
                if($data->printers->printer_conn == "USB"){
                    $printer->text("Connected with Windows USB Printing ".$data->printers->printer_usb_name."\n");
                } else if($data->printers->printer_conn == "Ethernet"){
                    $printer->text("Connected with Ethernet on IP : ".$data->printers->printer_ip_address."\n");
                }
                $printer->text("#\n");
                $printer->text("#\n");
                $printer->text("#\n");
                $printer->text("#\n");
                $printer->text("---------------MadaPOS-------------\n");
                $printer->feed();
                $printer->cut();
               
                /** JALANKAN PERINTAH PRINTER DISINI**/
            
            $printer->close();
            /**LOOPING ORDERS**/
        }
        /**JIKA ADA BILLS**/
        echo "<script>window.close();</script>";
?>