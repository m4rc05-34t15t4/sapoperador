<?php
    require_once 'conexao.php';

    // Consulta SQL
    if(!isset($_GET['usuario'])) echo json_encode(array( 'error' => 'Dados GET não encontrado!'));
    else{
        $OP = $_GET['usuario'];
        // Preparação e execução da consulta
        try {

            if(isset($_GET['adm'])){
                $sql = "SELECT ID, NOME, FUNCAO FROM USUARIOS WHERE ID = ".$_GET['adm'].";";
                $r = get_dados_bd_query($sql);
                $adm = count($r) > 0 ? $r[0] : null;
            }

            //dados gerais
            $sql = "SELECT NR_FUNCAO, FUNCAO FROM FUNCOES;";
            $funcoes = get_dados_bd_query($sql);

            $sql = "SELECT id, nome, post_grad, idtmil, nr_funcao, USU.funcao, QTD_HID, QTD_TRA, QTD_INT, QTD_VEG, QTD_REC FROM
                    (SELECT id, nome, post_grad, idtmil, nr_funcao, tb.funcao from (
                        (SELECT ID, NOME, FUNCAO, POST_GRAD, IDTMIL FROM USUARIOS WHERE ID = $OP) ta
                            LEFT JOIN 
                        (SELECT ID AS ID_FUNC, NR_FUNCAO, FUNCAO from FUNCOES) tb 
                            ON ta.FUNCAO = tb.NR_FUNCAO)) USU
                    LEFT JOIN
                        (SELECT OP_HID, COUNT(DATA_FIN_HID) AS QTD_HID FROM aux_moldura_a WHERE 
                        NOT OP_HID ISNULL AND 
                        NOT DATA_FIN_HID ISNULL AND 
                        EXTRACT(WEEK FROM DATA_FIN_HID) >= EXTRACT(WEEK FROM CURRENT_DATE)
                        GROUP BY OP_HID) HID
                    ON USU.id = HID.OP_HID
                    LEFT JOIN
                        (SELECT OP_TRA, COUNT(DATA_FIN_TRA) AS QTD_TRA FROM aux_moldura_a WHERE 
                        NOT OP_TRA ISNULL AND 
                        NOT DATA_FIN_TRA ISNULL AND 
                        EXTRACT(WEEK FROM DATA_FIN_TRA) >= EXTRACT(WEEK FROM CURRENT_DATE)
                        GROUP BY OP_TRA) TRA
                    ON USU.id = TRA.OP_TRA
                    LEFT JOIN
                        (SELECT OP_INT, COUNT(DATA_FIN_INT) AS QTD_INT FROM aux_moldura_a WHERE 
                        NOT OP_INT ISNULL AND 
                        NOT DATA_FIN_INT ISNULL AND 
                        EXTRACT(WEEK FROM DATA_FIN_INT) >= EXTRACT(WEEK FROM CURRENT_DATE)
                        GROUP BY OP_INT) INT
                    ON USU.id = INT.OP_INT
                    LEFT JOIN
                        (SELECT OP_VEG, COUNT(DATA_FIN_VEG) AS QTD_VEG FROM aux_moldura_a WHERE 
                        NOT OP_VEG ISNULL AND 
                        NOT DATA_FIN_VEG ISNULL AND 
                        EXTRACT(WEEK FROM DATA_FIN_VEG) >= EXTRACT(WEEK FROM CURRENT_DATE)
                        GROUP BY OP_VEG) VEG
                    ON USU.id = VEG.OP_VEG
                    LEFT JOIN
                        (SELECT OP_REC, COUNT(DATA_FIN_REC) AS QTD_REC FROM aux_moldura_a WHERE 
                        NOT OP_REC ISNULL AND 
                        NOT DATA_FIN_REC ISNULL AND 
                        EXTRACT(WEEK FROM DATA_FIN_REC) >= EXTRACT(WEEK FROM CURRENT_DATE)
                        GROUP BY OP_REC) REC
                    ON USU.id = REC.OP_REC	";
            $r = get_dados_bd_query($sql);
            $dados_usu = count($r) > 0 ? $r[0] : null;


            //pegar dados usuario cartas
            $sql = "SELECT ID, MI, MI_25000, OP_HID, DATA_INI_HID, DATA_FIN_HID FROM aux_moldura_a WHERE OP_HID = $OP ORDER BY DATA_FIN_HID DESC, MI_25000, MI, ID;";
            $dados_usu_cartas_hid = get_dados_bd_query($sql);

            $sql = "SELECT ID, MI, MI_25000, OP_TRA, DATA_INI_TRA, DATA_FIN_TRA FROM aux_moldura_a WHERE OP_TRA = $OP ORDER BY DATA_FIN_TRA DESC, MI_25000, MI, ID;;";
            $dados_usu_cartas_tra = get_dados_bd_query($sql);

            $sql = "SELECT ID, MI, MI_25000, OP_INT, DATA_INI_INT, DATA_FIN_INT FROM aux_moldura_a WHERE OP_INT = $OP ORDER BY DATA_FIN_INT DESC, MI_25000, MI, ID;;";
            $dados_usu_cartas_int = get_dados_bd_query($sql);

            $sql = "SELECT ID, MI, MI_25000, OP_VEG, DATA_INI_VEG, DATA_FIN_VEG FROM aux_moldura_a WHERE OP_VEG = $OP ORDER BY DATA_FIN_VEG DESC, MI_25000, MI, ID;;";
            $dados_usu_cartas_veg = get_dados_bd_query($sql);

            $sql = "SELECT ID, MI, MI_25000, OP_REC, DATA_INI_REC, DATA_FIN_REC FROM aux_moldura_a WHERE OP_REC = $OP ORDER BY DATA_FIN_REC DESC, MI_25000, MI, ID;;";
            $dados_usu_cartas_rec = get_dados_bd_query($sql);

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
                'metas'     =>  $dados_metas,
                'adm'       =>  $adm
            );

            echo json_encode($dados);
            
        } catch (PDOException $e) {
            die("Erro na consulta: " . $e->getMessage());
        }
    }
?>
