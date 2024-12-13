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

if(count($data->printers) > 0){

    foreach($data->printers as $printer){
    
        $connector = ($printer->printer_conn == 'USB') ? new WindowsPrintConnector($printer->printer_address) : new NetworkPrintConnector($printer->printer_address) ;
        if($connector){ #If Connector
            $print = new Printer($connector);#Open Koneksi Printer
            if(count($printer->jobs) > 0){

                foreach($printer->jobs as $job){
                    for($i = 0; $i < $job->autoprint_quantity; $i++){ #Foreach Autoprint Quantity
                    #----------------------------------RECEIPT-------------------------------------#
                    if($job->job == 'Receipt' && $data->waiting->receipt == true){
                        
                        if($printer->printer_cash_drawer == 1 || $printer->printer_cash_drawer == true){
                            $print->pulse(0, 100, 100);
                        }
                    }
                    #----------------------------------END RECEIPT-------------------------------------#
                    }#EndForeach Autoprint Quantity
                }#End Foreach Jobs

            }#End Count Jobs
            $print->close();#Close Koneksi Printer
        }#End If Connector
    }#End Foreach Printers

}#End Count Printers
?>