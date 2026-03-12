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
        padding-top: 120px
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
    .input-fit {
        width: auto !important;
        min-width: fit-content;
        display: inline-block;
    }
</style>
<body class="bg-light">

    <!-- Container Principal do Cabeçalho -->
    <header class="fixed-top container-fluid border-bottom shadow-sm p-2 mb-2 rounded-3 bg-white">
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-center">
            <!-- Lado Esquerdo: O GIF Estilizado -->
            <div class="text-center">
                <img src="./img/sapo_reambulador_digitando_video.gif" 
                    alt="Sapo Digitando"  
                    style="width: 75px; height: 75px; object-fit: cover; border-radius: 10px; border: 1px solid #AAA;">
            </div>
            <!-- Lado Direito: Os Textos -->
            <div class="text-center text-md-start d-flex flex-rown">
                <h1 class="fw-bold text-dark mx-1">Produção SAP<span class="text-success">O</span></h1>
                <h2 class="text-muted italic mx-1"><span class="badge bg-success me-2">Semana Atual nº: <?=$numeroSemana?></span> Período: <strong><?=$exibicaoPeriodo?></strong></h5>
            </div>
        </div>
    </header>

    <div class="container text-center">
        <div class="text-center w-fill">
            <form id="formFiltros" method="get" class="d-flex flex-wrap justify-content-center align-items-center g-2 mb-3">
                <!-- Select Usuário -->
                <div>
                    <label class="form-label small text-center">Operador</label>
                    <select id="userSelect" class="form-select w-auto" name="nome_guerra"></select>
                </div>

                <!-- Select Ano -->
                <div>
                    <label class="form-label small text-center">Ano</label>
                    <select id="anoSelect" class="form-select w-auto" name="ano">
                        <option value="">Todos</option>
                    </select>
                </div>

                <!-- Select Mês -->
                <div>
                    <label class="form-label small text-center">Mês</label>
                    <select id="mesSelect" class="form-select w-auto" name="mes">
                        <option value="">Todos</option>
                        <option value="1">Janeiro</option>
                        <option value="2">Fevereiro</option>
                        <option value="3">Março</option>
                        <option value="4">Abril</option>
                        <option value="5">Maio</option>
                        <option value="6">Junho</option>
                        <option value="7">Julho</option>
                        <option value="8">Agosto</option>
                        <option value="9">Setembro</option>
                        <option value="10">Outubro</option>
                        <option value="11">Novembro</option>
                        <option value="12">Dezembro</option>
                    </select>
                </div>

                <!-- Select Semana -->
                <div>
                    <label class="form-label small text-center">Semana</label>
                    <select id="semanaSelect" class="form-select w-auto" name="semana">
                        <option value="">Todas</option>
                    </select>
                </div>

                <!-- Datas (Período) -->
                <div>
                    <label class="form-label small">Início</label>
                    <input type="date" name="data_inicio" class="form-control w-auto">
                </div>
                <div>
                    <label class="form-label small">Fim</label>
                    <input type="date" name="data_fim" class="form-control w-auto">
                </div>
                <!-- Botão Filtrar <div><button type="submit" class="btn btn-success w-100 fs-3 mx-2">Filtrar</button></div>-->
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

        const inputInicio = document.querySelector('input[name="data_inicio"]');
        const inputFim = document.querySelector('input[name="data_fim"]');

        //FUNÇÕES
        
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

        async function popularFiltrosBase(dados) {
            try {

                const anoSelect = document.getElementById('anoSelect');
                const semanaSelect = document.getElementById('semanaSelect');
                const urlParams = new URLSearchParams(window.location.search);
                const userDaUrl = urlParams.get('nome_guerra') || '';
                popularSelectUsuarios(userDaUrl);

                // Busca anos e semanas da sua API (ex: pedido=filtros_disponiveis)
                const listaAnos = [...new Set(dados.map(item => item.ano))].sort((a, b) => b - a);
                const mapaSemanas = new Map();
                dados.forEach(item => {
                    if (!mapaSemanas.has(item.numero_semana)) {
                        mapaSemanas.set(item.numero_semana, {
                            numero: item.numero_semana,
                            periodo: item.periodo_semana
                        });
                    }
                });
                const listaSemanas = Array.from(mapaSemanas.values()).sort((a, b) => a.numero - b.numero);

                // Popular Anos
                anoSelect.innerHTML = '<option value="">Ano (Todos)</option>';
                listaAnos.forEach(ano => {
                    anoSelect.innerHTML += `<option value="${ano}">${ano}</option>`;
                });

                // Popular Semanas
                semanaSelect.innerHTML = '<option value="">Semana (Todas)</option>';
                listaSemanas.forEach(s => {
                    // Exibe: "Semana 5 (26/01 - 30/01/26)"
                    semanaSelect.innerHTML += `<option value="${s.numero}">Semana ${s.numero} (${s.periodo})</option>`;
                });

                // Seleciona todos os campos de filtro que possuem um atributo 'name'
                const filtros = document.querySelectorAll('#formFiltros select, #formFiltros input');
                filtros.forEach(campo => {
                    const nomeParametro = campo.name;
                    const valorNaUrl = urlParams.get(nomeParametro);
                    // Se o parâmetro existir na URL, aplica o valor ao campo
                    if (valorNaUrl !== null) {
                        campo.value = valorNaUrl;
                    }
                });

            } catch (e) {
                console.error("Erro ao popular filtros:", e);
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
            popularFiltrosBase(resposta.dados);

        }

        //EXECUÇÃO
        atualizarGrafico();

        //EVENTOS

        document.querySelectorAll('#formFiltros select, #formFiltros input[type="date"]').forEach(campo => {
            campo.addEventListener('change', function() {
                const url = new URL(window.location.href);
                const params = url.searchParams;
                // Verifica se o valor é vazio ou se é a string "Todos"
                if (this.value && this.value !== "" && this.value !== "") {
                    params.set(this.name, this.value);
                } else {
                    // Se cair aqui, o parâmetro é removido da URL
                    params.delete(this.name);
                }
                // Redireciona apenas se a URL mudou (evita refresh desnecessário)
                const novaUrl = url.pathname + (params.toString() ? '?' + params.toString() : '');
                window.location.href = novaUrl;
            });
        });
        // 1. Verifica se já existem datas na URL (GET) ao carregar a página
        const urlParams = new URLSearchParams(window.location.search);
        const dataInicioUrl = urlParams.get('data_inicio');
        const dataFimUrl = urlParams.get('data_fim');
        if (inputInicio && inputFim) {
            // Seta os valores nos campos (caso seu outro script ainda não tenha feito)
            if (dataInicioUrl) {
                inputInicio.value = dataInicioUrl;
                inputFim.min = dataInicioUrl; // TRAVA OS DIAS ANTES NO LOAD
            }
            if (dataFimUrl) inputFim.value = dataFimUrl;
            // 2. Evento de mudança (o que gera o recarregamento)
            inputInicio.addEventListener('change', function() {
                const dataSelecionada = this.value;
                // Aplica o mínimo antes mesmo de recarregar (feedback visual)
                inputFim.min = dataSelecionada;
                // Se a data de fim for menor que a nova data de início, limpa antes de enviar
                if (inputFim.value && inputFim.value < dataSelecionada) {
                    inputFim.value = "";
                }
            });
        }

    });
</script>
</body>
</html>