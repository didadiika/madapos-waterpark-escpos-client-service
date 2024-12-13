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
            
            $connector = ($data->printers->printer_conn == 'USB') ? new WindowsPrintConnector($data->printers->printer_address) : new NetworkPrintConnector($data->printers->printer_address) ;
            if($connector){ #If Connector
                $print = new Printer($connector);#Open Koneksi Printer

                $print->text("---------------MadaPOS-------------\n");
                $print->text("#\n");
                $print->text("#\n");
                $print->text("#\n");
                $print->text("#\n");
                $print->text("#".$data->text."\n");
                if($data->printers->printer_conn == "USB"){
                    $print->text("Connected with Windows USB Printing ".$data->printers->printer_address."\n");
                } else if($data->printers->printer_conn == "Ethernet"){
                    $print->text("Connected with Ethernet on IP : ".$data->printers->printer_address."\n");
                }
                $print->text("#\n");
                $print->text("#\n");
                $print->text("#\n");
                $print->text("#\n");
                $print->text("---------------MadaPOS-------------\n");
                $print->feed();
                $print->cut();
               
                /** JALANKAN PERINTAH PRINTER DISINI**/
            
            $print->close();
            /**LOOPING ORDERS**/
            }
        }
        /**JIKA ADA BILLS**/
        echo "<script>window.close();</script>";
?>