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
        if($data->receipts){
            $pr_conn = array();
            $pr_usb = array();
            $pr_ip = array();
            $nump = 0;
            /**LOOPING ORDERS**/
            foreach($data->receipts as $receipt){
            $pr_conn[$nump] = $receipt->conn;
            $pr_usb[$nump] = $receipt->usb;
            $pr_ip[$nump] = $receipt->ip;
                
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

                $printer->pulse(0, 100, 100);
               
                /** JALANKAN PERINTAH PRINTER DISINI**/
            }
            $printer->close();
            /**LOOPING ORDERS**/
        }
        /**JIKA ADA BILLS**/
        echo "<script>window.close();</script>";
?>