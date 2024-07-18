<?php

    require_once "conexao.php";

    //$_POST["usuario"] = 1; $_POST["funcao"] = 1;

    if( !isset($_POST["usuario"]) OR !isset($_POST["funcao"]) ) echo 0; //ERRO SERÁ TRATADO NO JS, ERRO: FALTA DE PARÂMETRO
    else{
        
        //DEFINE AS VARIAVEIS
        date_default_timezone_set('America/Recife');
        $data = date('Y') . "-" . date('m') . "-" . date('d') . " " . date("H") . ":" . date("i") . ":" . date("s");
        $usuario = strval($_POST["usuario"]);
        $funcao = strval($_POST["funcao"]);
        $tipo = "";
        $filtro = "";
        $order = "prioridade, mi";
        $atributo = "id";
        $else = "-1";
        switch($funcao){
            case "1":
                $tipo = "hid";
                $order = "prioridade, op_tra DESC, mi";
                break;
            case "2":
                $tipo = "tra";
                $order = "prioridade, op_hid DESC, mi";
                break;
            case "4":
                $tipo = "int";
                $atributo = "mi_25000";
                $order = "prioridade, op_veg DESC, mi";
                $else = "'-1'";
                break;
            case "8":
                $tipo = "veg";
                $order = "prioridade, op_int DESC, mi";
                break;
            case "16":
                $tipo = "rec";
                $atributo = "mi";
                $else = "'-1'";
                break;
        }

        if($funcao == "4" || $funcao == "8" || $funcao == "16") $filtro = " AND NOT data_fin_hid ISNULL AND NOT data_fin_tra ISNULL";
        if($funcao == "16") $filtro .= " AND NOT data_fin_int ISNULL AND NOT data_fin_veg ISNULL";

        //QUERY para Pedir carta 
        $sql = "
            UPDATE public.aux_moldura_a SET op_$tipo = $usuario, data_ini_$tipo = '$data' WHERE $atributo =  
                CASE
                    WHEN (SELECT COUNT($atributo) FROM public.aux_moldura_a WHERE op_$tipo = $usuario AND data_ini_$tipo ISNULL AND data_fin_$tipo ISNULL $filtro) > 0
                    THEN (SELECT $atributo FROM public.aux_moldura_a WHERE op_$tipo = $usuario AND data_ini_$tipo ISNULL AND data_fin_$tipo ISNULL $filtro ORDER BY $order LIMIT 1)
                    
                    WHEN (SELECT COUNT($atributo) FROM public.aux_moldura_a WHERE op_$tipo ISNULL AND data_ini_$tipo ISNULL AND data_fin_$tipo ISNULL $filtro) > 0
                    THEN (SELECT $atributo FROM public.aux_moldura_a WHERE op_$tipo ISNULL AND data_ini_$tipo ISNULL AND data_fin_$tipo ISNULL $filtro ORDER BY $order LIMIT 1)
                    
                    ELSE $else
                END
            RETURNING $atributo AS ID, '$funcao' AS FUNCAO;
        ";

        //echo $sql; 

        $pedir_carta = get_dados_bd_query($sql);

        if(count($pedir_carta) > 0) echo json_encode($pedir_carta);
        else echo 1;
    }

?>