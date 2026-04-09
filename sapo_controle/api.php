<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
ini_set('display_errors', 0);

$ano = date('Y');
$mes = date('n');
$semana = date('W');

$config = [
    "host" => "10.46.136.21", "port" => "5432", "dbname" => "sap",
    "user" => "postgres", "password" => "adminsap"
];

$conn = pg_connect("host={$config['host']} port={$config['port']} dbname={$config['dbname']} user={$config['user']} password={$config['password']}");

if (!$conn) {
    echo json_encode(['error' => 'Falha na conexão com o banco']);
    exit;
}

function validarData($data) {
    $d = DateTime::createFromFormat('Y-m-d', $data);
    return $d && $d->format('Y-m-d') === $data;
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

$pedido  = $_GET['pedido']  ?? '';
$usuario = $_GET['usuario'] ?? '';

switch ($pedido) {

    case 'usuarios':
        $sql = "SELECT concat(p.nome_abrev, ' ', u.nome_guerra) as nome_usuario 
                FROM dgeo.usuario u 
                LEFT JOIN dominio.tipo_posto_grad p ON p.code = u.tipo_posto_grad_id 
                ORDER BY nome_usuario";
        $res = pg_query($conn, $sql);
        echo json_encode(pg_fetch_all($res) ?: []);
        break;
    
    case 'usuarios_ativos':
        $resposta = [];
        $sql = "SELECT DISTINCT ON (u.id)
                u.id, 
                u.nome, 
                u.nome_guerra,
                u.tipo_posto_grad_id,
                p.nome AS patente,
                p.nome_abrev as patente_abrev, 
                u.ativo,
                u.administrador,
                l.data_login
            FROM dgeo.usuario u
            LEFT JOIN acompanhamento.login l ON u.id = l.usuario_id
            LEFT JOIN dominio.tipo_posto_grad p ON u.tipo_posto_grad_id = p.code
            WHERE u.ativo = true 
            AND l.data_login >= now() - INTERVAL '45 days'
            ORDER BY u.id, l.id DESC;";
        $res = pg_query($conn, $sql);
        $resposta['usuarios'] = pg_fetch_all($res) ?: [];
        //echo json_encode(pg_fetch_all($res) ?: []);

        //em atividade
        $query = "SELECT * FROM ( ";
        foreach (["s_1_execucao", "s_2_revisao_1", "s_3_correcao_1"] as $j) {
            $q = "SELECT matviewname FROM pg_matviews WHERE schemaname = 'acompanhamento' AND matviewname ILIKE '%_subfase%' AND definition ILIKE '%".$j."_usuario%';";
            $dados = buscar_dados($q);
            for($i=0; $i < count($dados); $i++ ){
                $sub_fase_lote = $dados[$i]['matviewname'];
                $query .= "
                    SELECT 
                        a.id,
                        a.lote_id,
                        a.subfase_id,
                        a.bloco,
                        a.".$j."_usuario as usuario, 
                        '$j' as tipo, 
                        a.".$j."_data_inicio as data_inicio,
                        a.".$j."_data_fim as data_fim,
                        EXTRACT(WEEK FROM a.".$j."_data_inicio::TIMESTAMP) AS numero_semana,
                        '$sub_fase_lote' AS origem_view,
                        l.usuario_id as usuario_id 
                    FROM acompanhamento.$sub_fase_lote a 
                    LEFT JOIN macrocontrole.atividade l ON a.".$j."_atividade_id::integer = l.id 
                    WHERE a.".$j."_usuario IS NOT NULL
                    AND a.".$j."_data_inicio IS NOT NULL
                    AND a.".$j."_data_inicio <> ''
                    AND a.".$j."_data_inicio<> '-'
                    AND ( a.".$j."_data_fim IS NULL OR a.".$j."_data_fim = '' OR a.".$j."_data_fim = '-' )
                UNION ALL";
            }
        }
        $query = substr($query, 0, -9);
        $query .= ") TABELA 
            $W
            ORDER BY data_inicio desc, tipo, usuario";
        $res = pg_query($conn, $query);
        $resposta['em_atividade'] = pg_fetch_all($res) ?: [];
        //echo json_encode($resposta ?: []);

        echo json_encode($resposta ?: []);
        break;
    
    case 'geral_fases':
        //gerar query
        $array = [];
        $query = "SELECT matviewname FROM pg_matviews WHERE schemaname = 'acompanhamento' AND matviewname ILIKE '%_subfase%';";
        $dados = buscar_dados($query);
        for($i=0; $i < count($dados); $i++ ){
            $sub_fase_lote = $dados[$i]['matviewname'];
            $query = "SELECT * , '$sub_fase_lote' AS origem_view FROM acompanhamento.$sub_fase_lote";
            $res = pg_query($conn, $query);
            $array[$sub_fase_lote] = pg_fetch_all($res);
        }
        echo json_encode($array ?: []);
        break;

    case 'lotes':
        $query = "SELECT matviewname, * FROM pg_matviews WHERE schemaname = 'acompanhamento' AND matviewname ILIKE '%_subfase%';";
        $result = pg_query($conn, $query);
        echo json_encode(pg_fetch_all($result) ?: []);
        break;

    case 'geral_fases_semanal':
        $resposta = [];
        $resposta['lote'] = buscar_dados("SELECT * FROM macrocontrole.lote", true);
        $resposta['subfase'] = buscar_dados("SELECT * FROM macrocontrole.subfase", true);
        
        //filtros
        $WHERE = [];
        if(isset($_GET['ano'])) $WHERE[] = $_GET['ano'] == '' ? ' ano = '.$ano : " ano = ".$_GET['ano'];
        if(isset($_GET['mes'])) $WHERE[] = $_GET['mes'] == '' ? ' mes = '.$mes : " mes = ".$_GET['mes'];
        if(isset($_GET['semana']) && str_contains($_GET['semana'], '-')) $WHERE[] = $_GET['semana'] == '' ? ' numero_semana = '.$semana.' AND ano = '.$ano : " numero_semana = ".explode("-", $_GET['semana'])[0]." AND ano = ".explode("-", $_GET['semana'])[1];
        if(isset($_GET['data_inicio']) && validarData($_GET['data_inicio'])) $WHERE[] = ' ano >= '.date('Y', strtotime($_GET['data_inicio'])).' AND numero_semana >= '.date('W', strtotime($_GET['data_inicio']));
        if(isset($_GET['data_fim']) && validarData($_GET['data_fim'])) $WHERE[] = ' ano <= '.date('Y', strtotime($_GET['data_fim'])).' AND numero_semana <= '.date('W', strtotime($_GET['data_fim']));
        if(isset($_GET['nome_guerra']) && $_GET['nome_guerra'] != '') $WHERE[] = " usuario = '".trim($_GET['nome_guerra'])."'";
        if(isset($_GET['lote']) && $_GET['lote'] != '') $WHERE[] = " lote_id = ".$_GET['lote'];
        if(isset($_GET['subfase']) && $_GET['subfase'] != '') $WHERE[] = " subfase_id = ".$_GET['subfase'];
        if(isset($_GET['bloco']) && $_GET['bloco'] != '') $WHERE[] = " bloco = '".$_GET['bloco']."'";
        $W = count($WHERE) > 0 ? 'WHERE '.implode(" AND", $WHERE) : '';

        //consulta
        $query = "SELECT * FROM ( ";
        foreach (["s_1_execucao", "s_2_revisao_1", "s_3_correcao_1"] as $j) {
            $q = "SELECT matviewname FROM pg_matviews WHERE schemaname = 'acompanhamento' AND matviewname ILIKE '%_subfase%' AND definition ILIKE '%".$j."_usuario%';";
            $dados = buscar_dados($q);
            for($i=0; $i < count($dados); $i++ ){
                $sub_fase_lote = $dados[$i]['matviewname'];
                $query .= "
                    SELECT COUNT(a.id) AS total,
                        a.lote_id,
                        a.subfase_id,
                        a.bloco,
                        a.".$j."_usuario as usuario, 
                        '$j' as tipo, 
                        EXTRACT(WEEK FROM a.".$j."_data_fim::TIMESTAMP) AS numero_semana,
                        EXTRACT(YEAR FROM a.".$j."_data_fim::TIMESTAMP) as ano,
                        EXTRACT(MONTH FROM a.".$j."_data_fim::TIMESTAMP) as mes,
                        MIN(TO_CHAR(a.".$j."_data_fim::TIMESTAMP - (EXTRACT(ISODOW FROM a.".$j."_data_fim::TIMESTAMP) - 1) * INTERVAL '1 day', 'DD/MM/YY') || ' - ' || 
                        TO_CHAR(a.".$j."_data_fim::TIMESTAMP + (5 - EXTRACT(ISODOW FROM a.".$j."_data_fim::TIMESTAMP)) * INTERVAL '1 day', 'DD/MM/YY')) AS periodo_semana,
                        '$sub_fase_lote' AS origem_view,
                        min(l.usuario_id) as usuario_id 
                    FROM acompanhamento.$sub_fase_lote a 
                    LEFT JOIN macrocontrole.atividade l ON a.".$j."_atividade_id::integer = l.id 
                    WHERE a.".$j."_usuario IS NOT NULL
                    AND a.".$j."_data_fim IS NOT NULL
                    AND a.".$j."_data_fim <> ''
                    AND a.".$j."_data_fim <> '-'
                    GROUP BY a.lote_id, a.subfase_id, a.bloco, usuario, numero_semana, ano, mes
                UNION ALL";
            }
        }
        $query = substr($query, 0, -9);
        $query .= ") TABELA 
            $W
            ORDER BY ano desc, mes::integer desc, NUMERO_SEMANA DESC, usuario, tipo";
        $result = pg_query($conn, $query);
        $resposta['dados'] = pg_fetch_all($result);
        echo json_encode($resposta ?: []);
        break;
    
    case 'em_atividade':
        
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Rota inválida.']);
        break;
}

pg_close($conn);
