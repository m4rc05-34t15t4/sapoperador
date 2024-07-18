<?php

    require_once "conexao.php";

    //$_POST["id"] = 1; $_POST["funcao"] = 1;

    if( !isset($_POST["id"]) OR !isset($_POST["funcao"]) ) echo 0; //ERRO SERÁ TRATADO NO JS, ERRO: FALTA DE PARÂMETRO
    else{
        
        //DEFINE AS VARIAVEIS
        date_default_timezone_set('America/Recife');
        $data = date('Y') . "-" . date('m') . "-" . date('d') . " " . date("H") . ":" . date("i") . ":" . date("s");
        $id = strval($_POST["id"]);
        $funcao = strval($_POST["funcao"]);
        $tipo = "";
        $atributo = "id";
        switch($funcao){
            case "1":
                $tipo = "hid";
                break;
            case "2":
                $tipo = "tra";
                break;
            case "4":
                $tipo = "int";
                $atributo = "mi_25000";
                $id = "'".$id."'";
                break;
            case "8":
                $tipo = "veg";
                break;
            case "16":
                $tipo = "rec";
                $atributo = "mi";
                $id = "'".$id."'";
                break;
        }

        //QUERY para Pedir carta 
        $sql = "UPDATE public.aux_moldura_a SET data_fin_$tipo = '$data' WHERE $atributo = $id RETURNING $atributo AS ID, '$funcao' AS FUNCAO;";

        //echo $sql; 

        $pedir_carta = get_dados_bd_query($sql);

        if(count($pedir_carta) > 0) echo json_encode($pedir_carta);
        else echo 1;
    }

?>