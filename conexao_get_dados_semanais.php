<?php
    require_once 'conexao.php';

    $ADM = [1];

    // Consulta SQL
    if(!isset($_GET['usuario'])) echo json_encode(array( 'error' => 'Dados GET não encontrado!'));
    else{
        $OP = $_GET['usuario'];
        // Preparação e execução da consulta
        try {
            if(in_array($OP, $ADM)){
                //pegar dados usuario
                $sql = "SELECT id, nome, post_grad, idtmil, nr_funcao, tb.funcao from (
                    (SELECT ID, NOME, FUNCAO, POST_GRAD, IDTMIL FROM USUARIOS) ta
                        LEFT JOIN 
                    (SELECT ID AS ID_FUNC, NR_FUNCAO, FUNCAO from FUNCOES) tb 
                        ON ta.FUNCAO = tb.NR_FUNCAO
                );";
                $dados_usu = get_dados_bd_query($sql);

                $sql = "SELECT ID, MI, MI_25000, OP_HID, DATA_INI_HID, DATA_FIN_HID FROM aux_moldura_a WHERE NOT OP_HID ISNULL OR NOT OP_TRA ISNULL OR NOT OP_INT ISNULL OR NOT OP_VEG ISNULL OR NOT OP_REC ISNULL;";
                $dados_cartas = get_dados_bd_query($sql);

                $sql = "SELECT EXTRACT(WEEK FROM CURRENT_DATE) AS nr_semana, EXTRACT(WEEK FROM data_start) AS nr_sem_start, EXTRACT(WEEK FROM data_limite) AS nr_sem_limite, 
                        ID, DATA_START, DATA_LIMITE, METAS_QTD, METAS_FUNCOES, METAS_USUARIOS FROM metas 
                        WHERE data_start <= CURRENT_DATE and data_limite >= CURRENT_DATE 
                        ORDER BY ID DESC LIMIT 1;";
                $dados_metas = get_dados_bd_query($sql)[0];

                $dados = array(
                    'usuario'   =>  $dados_usu,
                    'cartas'       =>  $dados_cartas,
                    'metas'     =>  $dados_metas
                );

                echo json_encode($dados);
            }
            else  echo json_encode(array( 'error' => 'Usuário não permitido'));
        } catch (PDOException $e) {
            echo json_encode(array( 'error' => "Erro na consulta: " . $e->getMessage()));
        }
    }
?>
