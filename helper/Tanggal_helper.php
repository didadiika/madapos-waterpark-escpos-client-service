<?php

function tanggal_time_db_to_id($t_id)
{
    if(!empty($t_id))
    {
        list($d,$t) = explode(" ",$t_id);
        list($ye,$mo,$da) = explode("-",$d);
        return $da."-".$mo."-".$ye." ".$t;
    }
    else
    {
        return "";
    }
    

}

function tanggal_time_id_to_db($t_id)
{
    if(!empty($t_id))
    {
        $d = '';
        $da = '';
        $mo = '';
        $ye = '';
        $t = '';
        if(strpos($t_id,"-") === true && strpos($t_id," ") === true)
        {
        list($d,$t) = explode(" ",$t_id);
        list($da,$mo,$ye) = explode("-",$d);
        return $ye."-".$mo."-".$da." ".$t;
        }
        else if(strpos($t_id,"-") === true && strpos($t_id," ") === false)
        {
        list($da,$mo,$ye) = explode("-",$d);
        return $ye."-".$mo."-".$da;
        }
        else
        {
            return "";
        }
        
    }
    else
    {
        return "";
    }
    

}

function tanggal_id_to_db($t_id)
{
    if(!empty($t_id))
    {
        list($d,$b,$y) = explode("-",$t_id);
        return $y."-".$b."-".$d;
    }
    else
    {
        return "";
    }
    

}

function tanggal_db_to_id($t_db)
{
    if(!empty($t_db))
    {
        list($y,$b,$d) = explode("-",$t_db);
        return $d."-".$b."-".$y;
    }
    else
    {
        return "";
    }

}

function datetime_db_to_date_db($t_db)
{
    if(!empty($t_db) && strlen($t_db) == 19)
    {
        list($t,$w) = explode(" ",$t_db);
        return $t;
    }
    else
    {
        return "";
    }

}

function id_to_indonesia($t_id)
{
    if(!empty($t_id))
    {
        list($d,$b,$t) = explode("-",$t_id);
        $d = (int) $d;
        if(strlen($d) < 2){ $d = "0".$d;}
        $b = (int) $b;
        $t = (int) $t;
        if(isset($b))
        {
            switch($b)
            {
                case"01":
                case"1":
                    $b="Januari";
                break;
                case"2":
                case"02":
                    $b="Februari";
                break;
                case"3":
                case"03":
                    $b="Maret";
                break;
                case"4":
                case"04":
                    $b="April";
                break;
                case"5":
                case"05":
                    $b="Mei";
                break;
                case"6":
                case"06":
                    $b="Juni";
                break;
                case"7":
                case"07":
                    $b="Juli";
                break;
                case"8":
                case"08":
                    $b="Agustus";
                break;
                case"9":
                case"09":
                    $b="September";
                break;
                case"10":
                    $b="Oktober";
                break;
                case"11":
                    $b="November";
                break;
                    case"12":
                    $b="Desember";
                break;
            }
        }
    }

    return $d." ".$b." ".$t;

}


function tanggal_cari_id_to_db($d)
{
    if(strlen($d) == 10)
    {
        #Tanggal Only
        if(substr_count($d, '-') == 2){
            $tanggal = tanggal_id_to_db($d);
        }
        else
        {
            $tanggal = '';
        }
        
    }else if(strlen($d) < 10){
        $tanggal = '';
    }
    else
    {
        if(substr_count($d, ' ') > 0)
        {
            list($tanggal, $waktu) = explode(" ",$d);
            if(substr_count($tanggal, '-') == 2){
                $tanggal = tanggal_id_to_db($tanggal);
            }
            else
            {
                $tanggal = '';
            }

            if($waktu)
            {
                $panjang_tanggal = strlen($tanggal);
                $time = " 00:00:00";
                $panjang_time = strlen($time);
                $panjang = strlen($d);
                $sisa  = $panjang_tanggal + $panjang_time - $panjang;
                $tanggal = $tanggal.' '.$waktu;
            }  
        }
        
    }
    return trim($tanggal);
}

?>