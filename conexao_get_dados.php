<?php
    require_once 'conexao.php';

    // Consulta SQL
    $OP = 1;

    // Preparação e execução da consulta
    try {
        //dados gerais
        $sql = "SELECT NR_FUNCAO, FUNCAO FROM FUNCOES;";
        $funcoes = get_dados_bd_query($sql);
        
        //pegar dados usuario
        $sql = "SELECT * FROM USUARIOS LEFT JOIN FUNCOES ON USUARIOS.FUNCAO = FUNCOES.NR_FUNCAO WHERE USUARIOS.ID = $OP;";
        $dados_usu = get_dados_bd_query($sql)[0];

        //pegar dados usuario cartas
        $sql = "SELECT ID, MI, MI_25000, OP_HID, DATA_INI_HID, DATA_FIN_HID FROM aux_moldura_a WHERE OP_HID = $OP;";
        $dados_usu_cartas_hid = get_dados_bd_query($sql);

        $sql = "SELECT ID, MI, MI_25000, OP_TRA, DATA_INI_TRA, DATA_FIN_TRA FROM aux_moldura_a WHERE OP_TRA = $OP;";
        $dados_usu_cartas_tra = get_dados_bd_query($sql);

        $sql = "SELECT ID, MI, MI_25000, OP_INT, DATA_INI_INT, DATA_FIN_INT FROM aux_moldura_a WHERE OP_INT = $OP;";
        $dados_usu_cartas_int = get_dados_bd_query($sql);

        $sql = "SELECT ID, MI, MI_25000, OP_VEG, DATA_INI_VEG, DATA_FIN_VEG FROM aux_moldura_a WHERE OP_VEG = $OP;";
        $dados_usu_cartas_veg = get_dados_bd_query($sql);

        $sql = "SELECT ID, MI, MI_25000, OP_REC, DATA_INI_REC, DATA_FIN_REC FROM aux_moldura_a WHERE OP_REC = $OP;";
        $dados_usu_cartas_rec = get_dados_bd_query($sql);

        $dados = array(
            'usuario'   =>  $dados_usu,
            'hid'       =>  $dados_usu_cartas_hid,
            'tra'       =>  $dados_usu_cartas_tra,
            'int'       =>  $dados_usu_cartas_int,
            'veg'       =>  $dados_usu_cartas_veg,
            'rec'       =>  $dados_usu_cartas_rec,
            'funcoes'   =>  $funcoes
        );

        echo json_encode($dados);
        
    } catch (PDOException $e) {
        die("Erro na consulta: " . $e->getMessage());
    }
?>
