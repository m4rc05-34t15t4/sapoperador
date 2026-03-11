<?php
    // Dados da conexão
    $host = "10.46.136.21"; // ou IP do servidor
    $port = "5432";
    $dbname = "sap";
    $user = "postgres";
    $password = "adminsap";
    $conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");
    if (!$conn) {
        echo "Erro ao conectar ao banco de dados.";
        exit;
    }

    //Semanal atual
    $hoje = new DateTime();
    $numeroSemana = $hoje->format('W');
    $inicioSemana = clone $hoje;
    $inicioSemana->modify('this week'); // No PHP, "this week" começa na segunda
    $fimSemana = clone $inicioSemana;
    $fimSemana->modify('+6 days');
    $exibicaoPeriodo = $inicioSemana->format('d/m') . ' a ' . $fimSemana->format('d/m/Y');

    //Funções
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
?>
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
    .table tr th {
        background-color: #444;
        color: white;
        font-size: 18px;
    }
    table td, table th {
        vertical-align: middle; /* Centraliza verticalmente */
        text-align: center;    /* Centraliza horizontalmente (opcional) */
    }
</style>
<body class="bg-light">

    <div class="container text-center">
        <div class="text-center w-fill">
            <h2 class="m-0 mt-4">Produção SAP</h2>
            <h4 class="m-0 mb-4 text-secundary"><i>Semana Atual nº: <?="$numeroSemana Período: $exibicaoPeriodo"?></i></h4>
            <form method='get' class='mb-3'>
                <select id='userSelect' class='form-select' name='nome_guerra'></select>
            </form>
            <div id='loader' class='spinner-border spinner-border-sm' role='status'></div>
            <div class='chart-container' style='height: 300px; min-width: 1000px;'>
                <canvas id='graficoSemanal'></canvas>
            </div>
        </div>
        <table id='tabela-dados' class='my-5 table table-striped table-bordered table-hover table-responsive'>
            <tr>
                <th>Total</th>
                <th>Lote</th>
                <th>Subfase</th>
                <th>Tipo</th>
                <th>Bloco</th>
                <th>Operador</th>
                <th>Ano</th>
                <th>Nº Semana</th>
                <th>Período Semana</th>
            </tr>
            <tbody id='corpoTabela'></tbody>
        </table>
    </div>
</div>
<script src="chart.umd.min.js"></script>
<script src="grafico.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        async function popularSelectUsuarios(nomeFiltro = '') {
            const select = document.getElementById('userSelect');
            try {
                // Busca a lista de usuários da sua API
                const response = await fetch('api.php?pedido=usuarios');
                const usuarios = await response.json();
                // Limpa as opções atuais (mantendo apenas a primeira)
                select.innerHTML = '<option value="">Todos</option>';
                // Percorre os dados e cria as <option>
                usuarios.forEach(user => {
                    const option = document.createElement('option');
                    const valor = user.nome_usuario; // Ajuste conforme o campo da sua API
                    option.value = valor;
                    option.textContent = valor;
                    // Define como selecionado se for o filtro atual
                    if (valor === nomeFiltro) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            } catch (e) {
                console.error("Erro ao popular select:", e);
            }
        }
        
        function preencherTabela(resposta) {
            const tbody = document.getElementById('corpoTabela');
            tbody.innerHTML = ""; // Limpa a tabela antes de preencher
            resposta.dados.forEach(linha => {
                const tr = document.createElement("tr");
                // Lógica do explode("_", tipo)[2] em JS:
                const tipoFormatado = linha.tipo ? linha.tipo.split("_")[2] : "";
                tr.innerHTML = `
                    <td style="vertical-align: middle; text-align: center;">${linha.total}</td>
                    <td style="vertical-align: middle;">${resposta.lote[linha.lote_id]['nome_abrev']}</td> 
                    <td style="vertical-align: middle;">${resposta.subfase[linha.subfase_id]['nome']}</td>
                    <td style="vertical-align: middle;">${tipoFormatado}</td>
                    <td style="vertical-align: middle;">${linha.bloco}</td>
                    <td style="vertical-align: middle;">${linha.usuario}</td>
                    <td style="vertical-align: middle; text-align: center;">${linha.ano}</td>
                    <td style="vertical-align: middle; text-align: center;">${linha.numero_semana}</td>
                    <td style="vertical-align: middle; text-align: center;">${linha.periodo_semana}</td>
                `;
                tbody.appendChild(tr);
            });
        }

        async function atualizarGrafico() {
            const parametros = window.location.search.replace('?', '&');
            const response = await fetch(`./api.php?pedido=geral_fases_semanal${parametros}`);
            const resposta = await response.json();
            if (!resposta || resposta.length === 0) {
                console.warn("Nenhum dado retornado para este usuário.");
                // Opcional: destruir gráfico se não houver dados
                if (chartInstance) chartInstance.destroy();
                return;
            }
            console.log(resposta);
            grafico(resposta.dados);
            preencherTabela(resposta);

        }

        const urlParams = new URLSearchParams(window.location.search);
        const userDaUrl = urlParams.get('nome_guerra') || '';
        popularSelectUsuarios(userDaUrl);
        atualizarGrafico();

        //EVENTOS

        document.getElementById('userSelect').addEventListener('change', function() {
            const novoUsuario = this.value;
            const url = new URL(window.location.href);
            const params = url.searchParams;
            if (novoUsuario) params.set('nome_guerra', novoUsuario);
            else params.delete('nome_guerra');
            window.location.href = url.pathname + '?' + params.toString();
        });
    });
</script>
</body>
</html>