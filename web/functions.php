<?php

// Usando UPDATE
// $valores é um array associativo de campos e valores da tabela
function inserir($tabela,$dados) {
								
	$fields = array();
	$values = array();
	foreach($dados as $field => $val) {		
	   $fields[] = "$field";
	   $values[] = "'" . $val . "'";	   
	}
	
	$field_list = join(',', $fields);
	$value_list = join(', ', $values);
	$sql = "INSERT INTO `" . $tabela . "` (" . $field_list . ") VALUES (" . $value_list . ")";
	
	return $sql;
}

function atualizar($tabela,$valores,$where = ""){

	$fields = array();
	foreach($valores as $field => $val) {
	   $fields[] = "$field = '$val'";
	}
	
	if($where == ""){
		$sql = "UPDATE ".$tabela." SET ". join(', ', $fields);
	} else {
		$sql = "UPDATE ".$tabela." SET ". join(', ', $fields) ." WHERE ".$where;
	}
	
	return $sql;
} // Fecha Function

function verifyCPF( $cpf )
{
    $cpf = "$cpf";
    if (strpos($cpf, "-") !== false)
    {
        $cpf = str_replace("-", "", $cpf);
    }
    if (strpos($cpf, ".") !== false)
    {
        $cpf = str_replace(".", "", $cpf);
    }
    $sum = 0;
    $cpf = str_split( $cpf );
    $cpftrueverifier = array();
    $cpfnumbers = array_splice( $cpf , 0, 9 );
    $cpfdefault = array(10, 9, 8, 7, 6, 5, 4, 3, 2);
    for ( $i = 0; $i <= 8; $i++ )
    {
        $sum += $cpfnumbers[$i]*$cpfdefault[$i];
    }
    $sumresult = $sum % 11;
    if ( $sumresult < 2 )
    {
        $cpftrueverifier[0] = 0;
    }
    else
    {
        $cpftrueverifier[0] = 11-$sumresult;
    }
    $sum = 0;
    $cpfdefault = array(11, 10, 9, 8, 7, 6, 5, 4, 3, 2);
    $cpfnumbers[9] = $cpftrueverifier[0];
    for ( $i = 0; $i <= 9; $i++ )
    {
        $sum += $cpfnumbers[$i]*$cpfdefault[$i];
    }
    $sumresult = $sum % 11;
    if ( $sumresult < 2 )
    {
        $cpftrueverifier[1] = 0;
    }
    else
    {
        $cpftrueverifier[1] = 11 - $sumresult;
    }
    $returner = false;
    if ( $cpf == $cpftrueverifier )
    {
        $returner = true;
    }


    $cpfver = array_merge($cpfnumbers, $cpf);

    if ( count(array_unique($cpfver)) == 1 || $cpfver == array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 0) )

    {

        $returner = false;

    }
    return $returner;
}

function verifyData($type, $data, $msg) {
    
	if($type == "number")
	{
		$data ? $var = preg_replace("/[\'\"\<\>]/", "", $data) : $var = $msg;
	} 
	else if ($type == "text")
	{
		$data ? $var = addslashes($data) : $var = $msg;
	}
	return $var;	
};

function changeDate($date){
	$date = explode("/", $date);
	$date = $date[2]."-".$date[1]."-".$date[0];
	return $date;
};

date_default_timezone_set('America/Sao_Paulo');

function dataDif($data1, $data2, $intervalo) {

    switch ($intervalo) {
        case 'y':
            $Q = 86400*365;
            break; //ano
        case 'm':
            $Q = 2592000;
            break; //mes
        case 'd':
            $Q = 86400;
            break; //dia
        case 'h':
            $Q = 3600;
            break; //hora
        case 'n':
            $Q = 60;
            break; //minuto
        default:
            $Q = 1;
            break; //segundo
    }

    return round((strtotime($data2) - strtotime($data1)) / $Q);
}
