<?php
    require_once 'conexao.php';

    $CONF = json_decode(file_get_contents('../conf.json'), true);

    // Consulta SQL
    try {
        //if(in_array($OP, $CONF['administrador'])){
            //pegar dados usuario
            $sql = "select * from (
                    select count(op_hid) as qtd, EXTRACT(week FROM data_fin_hid) AS nr_sem, 'hid' as tipo from aux_moldura_a group by nr_sem
                    union
                    select count(op_tra) as qtd, EXTRACT(week FROM data_fin_tra) AS nr_sem, 'tra' as tipo from aux_moldura_a group by nr_sem
                    union
                    select count(op_int) as qtd, EXTRACT(week FROM data_fin_int) AS nr_sem, 'int' as tipo from aux_moldura_a group by nr_sem
                    union
                    select count(op_veg) as qtd, EXTRACT(week FROM data_fin_veg) AS nr_sem, 'veg' as tipo from aux_moldura_a group by nr_sem
                    union
                    select count(op_rec) as qtd, EXTRACT(week FROM data_fin_rec) AS nr_sem, 'rec' as tipo from aux_moldura_a group by nr_sem
                    ) t_u where nr_sem > 0 order by nr_sem, tipo;";
            $grafico_total_semanal_tipo = get_dados_bd_query($sql);

            $sql = "select * from (
                    select count(op_hid) as qtd_usuarios, nr_sem, 'Hidrografia' as fase from (select op_hid, EXTRACT(week FROM data_fin_hid) AS nr_sem from aux_moldura_a group by nr_sem, op_hid order by nr_sem) t_hid group by nr_sem
                    union
                    select count(op_tra) as qtd_usuarios, nr_sem, 'Transporte' as fase from (select op_tra, EXTRACT(week FROM data_fin_tra) AS nr_sem from aux_moldura_a group by nr_sem, op_tra order by nr_sem) t_tra group by nr_sem
                    union
                    select count(op_int) as qtd_usuarios, nr_sem, 'Interseções' as fase from (select op_int, EXTRACT(week FROM data_fin_int) AS nr_sem from aux_moldura_a group by nr_sem, op_int order by nr_sem) t_int group by nr_sem
                    union
                    select count(op_veg) as qtd_usuarios, nr_sem, 'Vegetação' as fase from (select op_veg, EXTRACT(week FROM data_fin_veg) AS nr_sem from aux_moldura_a group by nr_sem, op_veg order by nr_sem) t_veg group by nr_sem
                    union
                    select count(op_rec) as qtd_usuarios, nr_sem, 'Reclassificação' as fase from (select op_rec, EXTRACT(week FROM data_fin_rec) AS nr_sem from aux_moldura_a group by nr_sem, op_rec order by nr_sem) t_rec group by nr_sem
                    ) t_u where nr_sem > 0 order by nr_sem, fase;";
            $grafico_volume_usuarios_fase = get_dados_bd_query($sql);

            $dados = array(
                'grafico_total_semanal_tipo'   =>  $grafico_total_semanal_tipo,
                'grafico_volume_usuarios_fase' =>  $grafico_volume_usuarios_fase
            );

            echo json_encode($dados);
        //}
        //else  echo json_encode(array( 'error' => 'Usuário não permitido'));
    } catch (PDOException $e) {
        echo json_encode(array( 'error' => "Erro na consulta: " . $e->getMessage()));
    }
?>
