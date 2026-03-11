<?php
// Dados da conexão
$host = "10.46.136.21"; // ou IP do servidor
$port = "5432";
$dbname = "sap";
$user = "postgres";
$password = "adminsap";

// Conecta ao banco
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");
function buscar_dados($query, $dic=false){
    global $conn;
    $result = pg_query($conn, $query);
    if (!$result) {
        echo "Erro ao executar a query.";
        exit;
    }
    $dados = [];
    if($dic) {
        while ($row = pg_fetch_assoc($result)) {
            $id = $row['id']; // Define a chave do dicionário
            $dados[$id] = $row; // Associa o array da linha ao ID
        }
    }
    else $dados = pg_fetch_all($result);
    return $dados;
}

$query = "SELECT * FROM ( ";
    foreach (["s_1_execucao", "s_2_revisao_1", "s_3_correcao_1"] as $j) {
        $q = "SELECT matviewname FROM pg_matviews WHERE schemaname = 'acompanhamento' AND matviewname ILIKE '%_subfase%' AND definition ILIKE '%".$j."_usuario%';";
        $dados = buscar_dados($q);
        for($i=0; $i < count($dados); $i++ ){
            $sub_fase_lote = $dados[$i]['matviewname'];
            $query .= "
                SELECT COUNT(id) AS total,
                    lote_id,
                    subfase_id,
                    bloco,
                    ".$j."_usuario as usuario, 
                    '$j' as tipo, 
                    EXTRACT(WEEK FROM ".$j."_data_fim::TIMESTAMP) AS numero_semana,
                    EXTRACT(YEAR FROM ".$j."_data_fim::TIMESTAMP) as ano,
                    '$sub_fase_lote' AS origem_view
                FROM acompanhamento.$sub_fase_lote
                WHERE ".$j."_usuario IS NOT NULL
                AND ".$j."_data_fim IS NOT NULL
                AND ".$j."_data_fim <> ''
                AND ".$j."_data_fim <> '-'
                GROUP BY lote_id, subfase_id, bloco, ".$j."_usuario, numero_semana, ano 
            UNION ALL";
        }
    }
    $query = substr($query, 0, -9);
    $query .= ") TABELA ORDER BY ano desc, NUMERO_SEMANA DESC, usuario, tipo";
echo $query;
?>