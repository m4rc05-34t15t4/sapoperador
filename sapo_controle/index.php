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
    <link rel="stylesheet" href="./css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="./css/styles.css?<?=$v?>">
</head>
<body class="bg-light">
    <!-- Container Principal do Cabeçalho -->
    <header class="fixed-top container-fluid p-2 mb-2">
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
            <form id="formFiltros" method="get" class="d-flex flex-wrap justify-content-center align-items-center gap-3 mb-5">
                <!-- Select Usuário -->
                <div>
                    <label class="form-label text-center">Operador</label>
                    <select id="userSelect" class="form-select w-auto" name="nome_guerra"></select>
                </div>

                <!-- Select Ano -->
                <div>
                    <label class="form-label text-center">Ano</label>
                    <select id="anoSelect" class="form-select w-auto" name="ano">
                        <option value="">Todos</option>
                    </select>
                </div>

                <!-- Select Mês -->
                <div>
                    <label class="form-label text-center">Mês</label>
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
                    <label class="form-label text-center">Semana</label>
                    <select id="semanaSelect" class="form-select w-auto" name="semana">
                        <option value="">Todas</option>
                    </select>
                </div>

                <!-- Select Lote -->
                <div>
                    <label class="form-label text-center">Lote</label>
                    <select id="loteSelect" class="form-select w-auto" name="lote">
                        <option value="">Todos</option>
                    </select>
                </div>

                <!-- Select Subfase -->
                <div>
                    <label class="form-label text-center">Subfase</label>
                    <select id="subfaseSelect" class="form-select w-auto" name="subfase">
                        <option value="">Todas</option>
                    </select>
                </div>

                <!-- Select Bloco -->
                <div>
                    <label class="form-label text-center">Bloco</label>
                    <select id="blocoSelect" class="form-select w-auto" name="bloco">
                        <option value="">Todos</option>
                    </select>
                </div>

                <!-- Datas (Período) -->
                <div>
                    <label class="form-label">Início</label>
                    <input type="date" name="data_inicio" class="form-control w-auto">
                </div>
                <div>
                    <label class="form-label">Fim</label>
                    <input type="date" name="data_fim" class="form-control w-auto">
                </div>
                
                <!-- Redireciona para a página atual sem nenhum parâmetro GET -->
                <div title="Limpar Filtros">
                    <label class="form-label"></label>
                    <a href="http://10.46.137.15/sapo_controle/" class="btn btn-secondary w-100 mx-2 rounded">Limpar Filtros</i></a>
                </div>

            </form>

            <div id='loader' class='spinner-border spinner-border-sm' role='status'></div>
            
            <hr/>
            <h1 id="graficos">Gráfico</h1>
            
            <div class='chart-container' style='height: 300px; min-width: 1000px;'>
                <canvas id='graficoSemanal'></canvas>
            </div>
        </div>

        <hr/>
        <h1 id="usuarios">Usuários</h1>

        <table id='tabela-usuarios' class='my-2 mb-5 table table-striped table-bordered table-hover table-responsive border border-black'>
            <thead>
                <tr style="cursor: pointer;">
                    <th id="th_usuarios_id"><span>Id</span> <i></i></th>
                    <th id="th_usuarios_operador"><span>Operador</span> <i></i></th>
                    <th id="th_usuarios_atv"><span>Com Atividade</span> <i></i></th>
                    <th id="th_usuarios_login"><span>Último Login</span> <i></i></th>
                    <th id="th_usuarios_execucao" mediana_cor="rgba(54, 162, 235, 0.5)"><span>Execução</span> <i></i></th>
                    <th id="th_usuarios_correcao" mediana_cor="rgba(72, 240, 189, 0.5)"><span>Correção</span> <i></i></th>
                    <th id="th_usuarios_revisao" mediana_cor="rgba(255, 99, 132, 0.5)"><span>Revisão</span> <i></i></th>
                    <th id="th_usuarios_ganchos_rec"><span class="subtitulo_coluna"><div class="texto_header"><span>Ganchos Recebidos</span><span>Corrigidos / Total</span></div><i></i></span></th>
                    <th id="th_usuarios_ganchos_apl"><span class="subtitulo_coluna"><div class="texto_header"><span>Ganchos Aplicados</span><span>Corrigidos / Total</span></div><i></i></span></th>
                    <th id="th_usuarios_total_atv"><span>Total Atividades</span> <i></i></th>
                </tr>
            </thead>
            <tbody id='corpoTabela-usuarios'></tbody>
        </table>

        <hr/>
        <h1 id="unidades">Unidades de Tranalho</h1>

        <table id='tabela-dados' class='my-2 mb-5 table table-striped table-bordered table-hover table-responsive border border-black'>
            <thead>
                <tr style="cursor: pointer;">
                    <th id="th_unidades_lote"><span>Lote</span> <i></i></th>
                    <th id="th_unidades_subfase"><span>Subfase</span> <i></i></th>
                    <th id="th_unidades_bloco"><span>Bloco</span> <i></i></th>
                    <th id="th_unidades_tipo"><span>Tipo</span> <i></i></th>
                    <th id="th_unidades_operador"><span>Operador</span> <i></i></th>
                    <th id="th_unidades_total"><span>Total</span> <i></i></th>
                    <th id="th_unidades_ganchos"><span>Ganchos</span> <i></i></th>
                    <th id="th_unidades_periodo_semanal"><span>Período Semana</span> <i></i></th>
                </tr>
            </thead>
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

    <div id="meu-loader" class="loading-overlay">
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-center">
            <div class="text-center">
                <img src="./img/sapo_reambulador_digitando_video.gif" 
                    alt="Sapo Digitando"  
                    style="width: 150px; height: 150px; object-fit: cover; border-radius: 10px; border: 1px solid black;">
            </div>
        </div>
        <div class="d-flex flex-rown">
            <div class="spinner-border text-light" style="width: 3rem; height: 3rem;" role="status"></div>
            <h4 class="m-3">Carregando dados...</h4>
        </div>
         <!-- BARRA DE PROGRESSO -->
        <div class="progress mt-2" style="width: 250px; height: 10px; background-color: rgba(255,255,255,0.2);">
            <div id="barra-loader" class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                 role="progressbar" style="width: 0%;"></div>
        </div>
    </div>

    <div class="fab-container">
        <button class="fab-btn" data-target="graficos" title="Gráficos">
            <i class="bi bi-bar-chart-fill"></i>
        </button>
        <button class="fab-btn" data-target="usuarios" title="Usuários">
            <i class="bi bi-people-fill"></i>
        </button>
        <button class="fab-btn" data-target="unidades" title="Unidades">
            <i class="bi bi-briefcase-fill"></i>
        </button>
    </div>

    <script src="js/chart.umd.min.js"></script>
    <script src="js/grafico.js<?=$v?>"></script>
    <script src="js/index.js<?=$v?>"></script>
</body>
</html>