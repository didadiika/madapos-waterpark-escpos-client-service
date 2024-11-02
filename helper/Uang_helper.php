<?php
function uangInt($a)
{
	return (int) $a;
}

function uangRp($angka)
{
	$hasil = "Rp ".number_format($angka,0,',','.');
	
	return $hasil;
}

function uangRpDecimal($uangRp)
{
	$hasil = "Rp ".number_format($uangRp,2,',','.');
	
	return $hasil;
}

function uang($angka)
{
	$hasil = number_format($angka,0,',','.');
	if($angka < 0)
	{
		$hasil = "-".number_format(abs($angka),0,',','.');
	}
	
	return $hasil;
}

function uangDecimal($angka)
{
	$hasil = number_format($angka,2,',','.');
	if($angka < 0)
	{
		$hasil = "-".number_format(abs($angka),0,',','.');
	}
	
	return $hasil;
}

function uangPecah($uang){

$hasil = str_replace(".","",$uang);

return $hasil;	
}

function uangPecahInternasional($uang){

	$hasil = str_replace(",","",$uang);
	
	return $hasil;	
}

function uangRpBiasa($uangRp)
{
	list($matauang,$uang) = explode(" ",$uangRp);
	
	$hasil = str_replace(".","",$uang);
	
	return $hasil;
}

function uangDbDecimal($uangRp)
{
	list($matauang,$uang) = explode(" ",$uangRp);
	$uang = str_replace(".","",$uang);
	$uang = str_replace(",",".",$uang);
	return $uang;
}

function angkaDbDecimal($uang)
{
	$uang = str_replace(".",",",$uang);
	return $uang;
}

function decimalToDb($angka_decimal)
{
	$hasil = str_replace(",",".",$angka_decimal);
	
	return $hasil;
}

function dbToDecimal($angka_db)
{
	$hasil = str_replace(".",",",$angka_db);
	
	return $hasil;
}
?>