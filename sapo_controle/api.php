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
                        a.dado_producao, 
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
                    GROUP BY a.lote_id, a.subfase_id, a.bloco, a.dado_producao, usuario, numero_semana, ano, mes
                UNION ALL";
            }
        }
        $query = substr($query, 0, -9);
        $query .= ") TABELA 
            $W
            ORDER BY ano desc, mes::integer desc, NUMERO_SEMANA DESC, usuario, tipo";
        $result = pg_query($conn, $query);
        $resposta['dados'] = pg_fetch_all($result);
        //echo json_encode($resposta ?: []);
    
    case 'ganchos':

        /*

        CREATE EXTENSION IF NOT EXISTS postgres_fdw;
        CREATE EXTENSION IF NOT EXISTS unaccent;

        CREATE SERVER server_pit_2026_topo_25k
        FOREIGN DATA WRAPPER postgres_fdw
        OPTIONS (host '10.46.136.21', dbname 'pit_2026_topo_25k', port '5432');

        CREATE USER MAPPING FOR current_user 
        SERVER pit_2026_topo_25k -- Usando o nome que você definiu
        OPTIONS (user 'seu_usuario', password 'sua_senha');

        CREATE SCHEMA pit;

        IMPORT FOREIGN SCHEMA edgv 
        LIMIT TO (aux_revisao_p) 
        FROM SERVER pit_2026_topo_25k 
        INTO pit_edgv;

        CREATE OR REPLACE FUNCTION criar_conexao_pit(
            p_banco TEXT,
            p_host TEXT DEFAULT '10.46.136.21',
            p_port TEXT DEFAULT '5432',
            p_user TEXT DEFAULT 'postgres',
            p_pass TEXT DEFAULT 'adminsap',
            p_schema_remoto TEXT DEFAULT 'edgv',
            p_tabela_remota TEXT DEFAULT 'aux_revisao_p',
            p_schema_local TEXT DEFAULT 'pit'
        )
        RETURNS TEXT AS $$
        DECLARE
            v_tabela_nova TEXT;
        BEGIN
            v_tabela_nova := p_banco || '_' || p_tabela_remota;

            EXECUTE 'CREATE EXTENSION IF NOT EXISTS postgres_fdw';

            EXECUTE format('CREATE SERVER IF NOT EXISTS server_%I 
                            FOREIGN DATA WRAPPER postgres_fdw 
                            OPTIONS (host %L, dbname %L, port %L)', 
                            p_banco, p_host, p_banco, p_port);

            EXECUTE format('DROP USER MAPPING IF EXISTS FOR current_user SERVER server_%I', p_banco);
            EXECUTE format('CREATE USER MAPPING FOR current_user 
                            SERVER server_%I 
                            OPTIONS (user %L, password %L)', 
                            p_banco, p_user, p_pass);

            EXECUTE format('CREATE SCHEMA IF NOT EXISTS %I', p_schema_local);

            -- LIMPEZA CRÍTICA: Remove tanto o nome novo quanto o original para evitar conflito no IMPORT
            EXECUTE format('DROP FOREIGN TABLE IF EXISTS %I.%I', p_schema_local, v_tabela_nova);
            EXECUTE format('DROP FOREIGN TABLE IF EXISTS %I.%I', p_schema_local, p_tabela_remota);

            -- Agora o IMPORT passará sem erros
            EXECUTE format('IMPORT FOREIGN SCHEMA %I 
                            LIMIT TO (%I) 
                            FROM SERVER server_%I 
                            INTO %I', 
                            p_schema_remoto, p_tabela_remota, p_banco, p_schema_local);
            
            -- Renomeia para o formato desejado
            EXECUTE format('ALTER FOREIGN TABLE %I.%I RENAME TO %I', 
                            p_schema_local, p_tabela_remota, v_tabela_nova);

            RETURN format('Sucesso! Tabela disponível em: %s.%s', p_schema_local, v_tabela_nova);
        END;
        $$ LANGUAGE plpgsql;

        SELECT criar_conexao_pit('pit_2026_topo_25k');
        SELECT criar_conexao_pit('outro_banco_25k', p_schema_local := 'reserva');
        */
        
        //filtros
        $WHERE = [];
        if(isset($_GET['ano'])) $WHERE[] = $_GET['ano'] == '' ? ' revisao_ano = '.$ano : " revisao_ano = ".$_GET['ano'];
        if(isset($_GET['mes'])) $WHERE[] = $_GET['mes'] == '' ? ' revisao_mes = '.$mes : " revisao_mes = ".$_GET['mes'];
        if(isset($_GET['semana']) && str_contains($_GET['semana'], '-')) $WHERE[] = $_GET['semana'] == '' ? ' revisao_numero_semana = '.$semana.' AND revisao_ano = '.$ano : " revisao_numero_semana = ".explode("-", $_GET['semana'])[0]." AND revisao_ano = ".explode("-", $_GET['semana'])[1];
        if(isset($_GET['data_inicio']) && validarData($_GET['data_inicio'])) $WHERE[] = ' revisao_ano >= '.date('Y', strtotime($_GET['data_inicio'])).' AND revisao_numero_semana >= '.date('W', strtotime($_GET['data_inicio']));
        if(isset($_GET['data_fim']) && validarData($_GET['data_fim'])) $WHERE[] = ' revisao_ano <= '.date('Y', strtotime($_GET['data_fim'])).' AND revisao_numero_semana <= '.date('W', strtotime($_GET['data_fim']));
        if(isset($_GET['nome_guerra']) && $_GET['nome_guerra'] != '') $WHERE[] = " executor = '".trim($_GET['nome_guerra'])."'";
        if(isset($_GET['revisor']) && $_GET['revisor'] != '') $WHERE[] = " revisor = '".trim($_GET['revisor'])."'";
        if(isset($_GET['lote']) && $_GET['lote'] != '') $WHERE[] = " lote_id = ".$_GET['lote'];
        if(isset($_GET['subfase']) && $_GET['subfase'] != '') $WHERE[] = " subfase_id = ".$_GET['subfase'];
        if(isset($_GET['bloco']) && $_GET['bloco'] != '') $WHERE[] = " bloco = '".$_GET['bloco']."'";
        $W = count($WHERE) > 0 ? 'WHERE '.implode(" AND", $WHERE) : '';

        //consulta
        $query = "
            WITH lista_postos AS (
                -- Gera o padrão de regex uma única vez
                SELECT '\m(' || string_agg(nome_abrev, '|') || ')\M' as padrao 
                FROM dominio.tipo_posto_grad
            ),
            todos_os_pontos AS (
                SELECT *, 'pit_2026_topo_100k' as banco_ref FROM pit.pit_2026_topo_100k_aux_revisao_p
                UNION ALL
                SELECT *, 'pit_2026_topo_25k' as banco_ref FROM pit.pit_2026_topo_25k_aux_revisao_p
            )
            SELECT * FROM (
                SELECT 
                    id_unidade, 
                    bloco, 
                    lote_id, 
                    subfase_id,
                    t_subfase.nome, 
                    banco, 
                    nome_gancho,
                    executor, 
                    execucao_fim, 
                    revisor, 
                    revisao_inicio, 
                    revisao_fim,  
                    total_pontos,
                    operador_criacao,
                    corrigido, 
                    EXTRACT(WEEK FROM execucao_fim) AS execucao_numero_semana,
                    EXTRACT(YEAR FROM execucao_fim) as execucao_ano,
                    EXTRACT(MONTH FROM execucao_fim) as execucao_mes,
                    TO_CHAR(execucao_fim - (EXTRACT(ISODOW FROM execucao_fim) - 1) * INTERVAL '1 day', 'DD/MM/YY') || ' - ' || 
                    TO_CHAR(execucao_fim + (5 - EXTRACT(ISODOW FROM execucao_fim)) * INTERVAL '1 day', 'DD/MM/YY') AS execucao_periodo_semana, 
                    EXTRACT(WEEK FROM revisao_fim) AS revisao_numero_semana,
                    EXTRACT(YEAR FROM revisao_fim) as revisao_ano,
                    EXTRACT(MONTH FROM revisao_fim) as revisao_mes,
                    TO_CHAR(revisao_fim - (EXTRACT(ISODOW FROM revisao_fim) - 1) * INTERVAL '1 day', 'DD/MM/YY') || ' - ' || 
                    TO_CHAR(revisao_fim + (5 - EXTRACT(ISODOW FROM revisao_fim)) * INTERVAL '1 day', 'DD/MM/YY') AS revisao_periodo_semana 
                FROM ( ";
        $q = "SELECT matviewname FROM pg_matviews WHERE schemaname = 'acompanhamento' AND matviewname ILIKE '%_subfase%' AND definition ILIKE '%s_1_execucao_usuario%' AND definition ILIKE '%s_2_revisao_1_usuario%';";
        $dados = buscar_dados($q);
        for($i=0; $i < count($dados); $i++ ){
            $sub_fase_lote = $dados[$i]['matviewname'];
            $query .= "
                SELECT *, '$sub_fase_lote' as sub_fase_lote FROM ( 
                    SELECT 
                        pol.id as id_unidade, 
                        pol.bloco, 
                        pol.lote_id, 
	                    pol.subfase_id, 
                        REPLACE(pol.dado_producao, '10.46.136.21:5432/', '') as banco, 
                        replace( lower( unaccent( trim( regexp_replace( pol.s_2_revisao_1_usuario,  (SELECT padrao FROM lista_postos), 'sap', 'gi' ) ) ) ), ' ', '_' ) AS nome_gancho,
                        pol.s_1_execucao_usuario as executor, 
                        max(pol.s_1_execucao_data_fim::timestamp) as execucao_fim, 
                        pol.s_2_revisao_1_usuario as revisor, 
                        min(pol.s_2_revisao_1_data_inicio::timestamp) as revisao_inicio, 
                        max(pol.s_2_revisao_1_data_fim::timestamp) as revisao_fim, 
                        pol.geom as poligono, 
                        COUNT(p.id) as total_pontos,
                        p.operador_criacao,
                        p.corrigido = 1 as corrigido 
                    FROM acompanhamento.$sub_fase_lote pol
                    LEFT JOIN todos_os_pontos p 
                        ON ST_Intersects(ST_Transform(p.geom, 4326), pol.geom)
                        AND p.banco_ref = REPLACE(pol.dado_producao, '10.46.136.21:5432/', '')
                    WHERE 
                        pol.s_1_execucao_usuario IS NOT NULL AND 
                        pol.s_2_revisao_1_usuario IS NOT NULL AND
                        pol.s_2_revisao_1_data_inicio::timestamp <= p.data_criacao::timestamp AND
                        pol.s_2_revisao_1_data_fim::timestamp >= p.data_criacao::timestamp 
                    GROUP BY
                        pol.id,
                        pol.bloco, 
                        pol.lote_id, 
	                    pol.subfase_id,
                        banco,
                        pol.s_1_execucao_usuario, -- Usar o nome original da coluna no GROUP BY
                        revisor,
                        poligono,
                        p.operador_criacao,
                        p.corrigido = 1 
                    ORDER BY executor, total_pontos DESC
                ) Tabela_subfaselote
                WHERE OPERADOR_CRIACAO = NOME_GANCHO
            UNION ALL";
        }
        $query = substr($query, 0, -9);
        $query .= ") TABELA 
            LEFT JOIN macrocontrole.subfase t_subfase ON t_subfase.id = TABELA.subfase_id
            ) t 
            $W
            ORDER BY revisao_ano desc, revisao_mes desc, revisao_numero_semana desc, executor, corrigido, total_pontos desc;";
        //echo json_encode([$query]);
        $result = pg_query($conn, $query);
        $resposta['ganchos'] = pg_fetch_all($result);
        echo json_encode($resposta ?: []);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Rota inválida.']);
        break;
}

pg_close($conn);
