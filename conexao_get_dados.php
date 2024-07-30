<?php
    require_once 'conexao.php';

    // Consulta SQL
    if(!isset($_GET['usuario'])) echo json_encode(array( 'error' => 'Dados GET não encontrado!'));
    else{
        $OP = $_GET['usuario'];
        // Preparação e execução da consulta
        try {
            //dados gerais
            $sql = "SELECT NR_FUNCAO, FUNCAO FROM FUNCOES;";
            $funcoes = get_dados_bd_query($sql);
            
            //pegar dados usuario
            $sql = "SELECT id, nome, post_grad, idtmil, nr_funcao, tb.funcao from (
                (SELECT ID, NOME, FUNCAO, POST_GRAD, IDTMIL FROM USUARIOS WHERE USUARIOS.ID = $OP) ta
                    LEFT JOIN 
                (SELECT ID AS ID_FUNC, NR_FUNCAO, FUNCAO from FUNCOES) tb 
                    ON ta.FUNCAO = tb.NR_FUNCAO
            );";
            $r = get_dados_bd_query($sql);
            $dados_usu = count($r) > 0 ? $r[0] : null;

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

            /*$nf = $dados_usu['nr_funcao'];
            $sql = "SELECT EXTRACT(WEEK FROM CURRENT_DATE) AS nr_semana, EXTRACT(WEEK FROM data_start) AS nr_sem_start, EXTRACT(WEEK FROM data_limite) AS nr_sem_limite, 
                    COALESCE(jsonb_exists(METAS_USUARIOS::JSONB, '$OP'), FALSE) AS E_M_U, COALESCE(jsonb_exists(METAS_FUNCOES::JSONB, '$nf'), FALSE) AS E_M_F, 
                    ID, DATA_START, DATA_LIMITE, METAS_QTD, METAS_FUNCOES, METAS_USUARIOS FROM metas 
                    WHERE data_start <= CURRENT_DATE and data_limite >= CURRENT_DATE 
                    ORDER BY E_M_U DESC, E_M_F DESC, ID DESC LIMIT 1;";
            $r = get_dados_bd_query($sql);
            $dados_metas = count($r) > 0 ? $r[0] :  null;*/

            $sql = "SELECT EXTRACT(WEEK FROM CURRENT_DATE) AS nr_semana, EXTRACT(WEEK FROM data_start) AS nr_sem_start, EXTRACT(WEEK FROM data_limite) AS nr_sem_limite, 
                    ID, DATA_START, DATA_LIMITE, METAS_QTD, METAS_FUNCOES, METAS_USUARIOS FROM metas 
                    WHERE data_start <= CURRENT_TIMESTAMP and data_limite >= CURRENT_TIMESTAMP 
                    ORDER BY ID DESC LIMIT 1;";
            $r = get_dados_bd_query($sql);
            $dados_metas = count($r) > 0 ? $r[0] : null;

            $dados = array(
                'usuario'   =>  $dados_usu,
                'hid'       =>  $dados_usu_cartas_hid,
                'tra'       =>  $dados_usu_cartas_tra,
                'int'       =>  $dados_usu_cartas_int,
                'veg'       =>  $dados_usu_cartas_veg,
                'rec'       =>  $dados_usu_cartas_rec,
                'funcoes'   =>  $funcoes,
                'metas'     =>  $dados_metas
            );

            echo json_encode($dados);
            
        } catch (PDOException $e) {
            die("Erro na consulta: " . $e->getMessage());
        }
    }
?>
