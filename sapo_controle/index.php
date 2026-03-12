<?php
    $v = "?v=".date('YmdHi');
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
    <title>SAPOperador</title>
    <link rel="icon" type="image/gif" href="./img/sapo_reambulador_digitando.ico">
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<style>
    body {
        background-color: #f8f9fa;
        padding-top: 100px;
        min-height: 100vh;
    }
    #div-conteudo{
        min-height: 100vh;
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
                <h1 class="fw-bold text-dark mx-1">Produção SAP<span class="text-success">Operador</span></h1>
                <h2 class="text-muted italic mx-1"><span class="badge bg-success me-2">Semana Atual nº: <?=$numeroSemana?></span> Período: <strong> <?=$exibicaoPeriodo?></strong></h5>
            </div>
        </div>
    </header>

    <div id="div-conteudo" class="text-center w-fill p-3 pb-0 mb-0">
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

                <!-- Select Lote -->
                <div>
                    <label class="form-label small text-center">Lote</label>
                    <select id="loteSelect" class="form-select w-auto" name="lote">
                        <option value="">Todos</option>
                    </select>
                </div>

                <!-- Select Subfase -->
                <div>
                    <label class="form-label small text-center">Subfase</label>
                    <select id="subfaseSelect" class="form-select w-auto" name="subfase">
                        <option value="">Todas</option>
                    </select>
                </div>

                <!-- Select Bloco -->
                <div>
                    <label class="form-label small text-center">Bloco</label>
                    <select id="blocoSelect" class="form-select w-auto" name="bloco">
                        <option value="">Todos</option>
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
    <footer class="container-fluid bg-light border-top shadow-sm p-3 mt-0">
        <div class="row">
            <!-- justify-content-center: Centraliza o conteúdo horizontalmente -->
            <div class="col-12 d-flex justify-content-center align-items-center">
                <p class="text-secondary m-0" style="font-size: 0.9rem;">
                    &copy; <?= date('Y') ?> - <strong>2º Sgt Marcos Batista - Topo /13</strong> 
                    <span class="mx-2 text-muted">|</span> 
                    <span class="badge bg-primary">3º CGEO</span>
                </p>
            </div>
        </div>
    </footer>


    <script src="js/chart.umd.min.js"></script>
    <script src="js/grafico.js<?=$v?>"></script>
    <script src="js/index.js<?=$v?>"></script>
</body>
</html>