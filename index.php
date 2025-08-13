<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Usuários</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<style>
    body {
        background-color: #f8f9fa;
    }
    .centralizar {
        max-width: 900px;
        margin: 50px auto;
    }
    .table thead th {
        background-color: #e9ecef;
    }
</style>
<body class="bg-light">

<div class="container text-center">
        <div class="text-center w-fill">
            <h2 class="my-4">Produção SAP</h2>

<?php
// Dados da conexão
$host = "10.46.136.21"; // ou IP do servidor
$port = "5432";
$dbname = "sap";
$user = "postgres";
$password = "adminsap";

// Conecta ao banco
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

// Verifica conexão
if (!$conn) {
    echo "Erro ao conectar ao banco de dados.";
    exit;
}

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

$SUBFASE = buscar_dados("SELECT * FROM macrocontrole.subfase", true);
$LOTE = buscar_dados("SELECT * FROM macrocontrole.lote", true);

//criar select:

// Consulta principal
$query = "
    SELECT 
        dgeo.usuario.login,
        dgeo.usuario.nome,
        dgeo.usuario.nome_guerra,
        dominio.tipo_posto_grad.nome_abrev,
        concat(dominio.tipo_posto_grad.nome_abrev, ' ', dgeo.usuario.nome_guerra) as nome_usuario
    FROM dgeo.usuario
    LEFT JOIN dominio.tipo_posto_grad
        ON dominio.tipo_posto_grad.code = dgeo.usuario.tipo_posto_grad_id
    ORDER BY nome_usuario;
";
$dados = buscar_dados($query);


// Se o usuário escolheu um nome_guerra, filtra os resultados
$nomeFiltro = isset($_GET['nome_guerra']) ? trim($_GET['nome_guerra']) : '';

// Gera o select com nome_guerra
echo "<form method='get'>";
echo "<select class='form-select' name='nome_guerra' onchange='this.form.submit()'>";
echo "<option value=''>-- Escolha --</option>";

if ($dados) {
    foreach ($dados as $linha) {
        $valor = htmlspecialchars($linha['nome_usuario']);
        $selected = ($valor === $nomeFiltro) ? 'selected' : '';
        echo "<option value=\"$valor\" $selected>$valor</option>";
    }
}
echo "</select>";
echo "</form>";

$where = '';
if($nomeFiltro != '') $where = "WHERE s_1_execucao_usuario = '".$nomeFiltro."'";

// 

$query = "SELECT matviewname FROM pg_matviews WHERE schemaname = 'acompanhamento' AND matviewname ILIKE '%_subfase%';";
$dados = buscar_dados($query);

//gerar query
$query = "SELECT * FROM ( ";
for($i=0; $i < count($dados); $i++ ){
    $sub_fase_lote = $dados[$i]['matviewname'];
    $query .= "
            SELECT COUNT(id) AS total,
                lote_id,
                subfase_id,
                bloco,
                s_1_execucao_usuario,
                EXTRACT(WEEK FROM s_1_execucao_data_fim::TIMESTAMP) AS numero_semana,
                EXTRACT(YEAR FROM s_1_execucao_data_fim::TIMESTAMP) as ano,
                MIN(TO_CHAR(DATE_TRUNC('week', s_1_execucao_data_fim::TIMESTAMP + INTERVAL '1 day') - INTERVAL '1 day', 'DD/MM/YY') || ' - ' || 
                    TO_CHAR(DATE_TRUNC('week', s_1_execucao_data_fim::TIMESTAMP + INTERVAL '1 day') + INTERVAL '5 days', 'DD/MM/YY')) AS range_semana,
                '$sub_fase_lote' AS origem_view
            FROM acompanhamento.$sub_fase_lote
            WHERE s_1_execucao_usuario IS NOT NULL
            AND s_1_execucao_data_fim IS NOT NULL
            AND s_1_execucao_data_fim <> ''
            AND s_1_execucao_data_fim <> '-'
            GROUP BY lote_id, subfase_id, bloco, s_1_execucao_usuario,
                EXTRACT(YEAR FROM s_1_execucao_data_fim::TIMESTAMP),
                EXTRACT(WEEK FROM s_1_execucao_data_fim::TIMESTAMP)
        UNION ALL";
}
$query = substr($query, 0, -9);
$query .= ") TABELA 
    $where
    ORDER BY s_1_execucao_usuario, ano desc, NUMERO_SEMANA DESC";

$result = pg_query($conn, $query);

// Converte para array associativo
$dados = pg_fetch_all($result);

// Exibe os dados em uma tabela
if ($dados) {
    echo "<table class='my-5 table table-striped table-bordered table-hover table-responsive'>";
    echo "<tr>";
    echo "<th>Total</th>";
    echo "<th>Lote ID</th>";
    echo "<th>Subfase ID</th>";
    echo "<th>Bloco</th>";
    echo "<th>Operador</th>";
    echo "<th>Ano</th>";
    echo "<th>Nº Semana</th>";
    echo "<th>Período</th>";
    echo "</tr>";

    foreach ($dados as $linha) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($linha['total']) . "</td>";
        echo "<td>" . htmlspecialchars($LOTE[$linha['lote_id']]['nome_abrev']) . "</td>";
        echo "<td>" . htmlspecialchars($SUBFASE[$linha['subfase_id']]['nome']) . "</td>";
        echo "<td>" . htmlspecialchars($linha['bloco']) . "</td>";
        echo "<td>" . htmlspecialchars($linha['s_1_execucao_usuario']) . "</td>";
        echo "<td>" . htmlspecialchars($linha['ano']) . "</td>";
        echo "<td>" . htmlspecialchars($linha['numero_semana']) . "</td>";
        echo "<td>" . htmlspecialchars($linha['range_semana']) . "</td>";
        echo "</tr>";
    }

    echo "</table>";
} else {
    echo "Nenhum resultado encontrado.";
}

// Fecha a conexão
pg_close($conn);
?>

</div>
</div>
</div>

</body>
</html>