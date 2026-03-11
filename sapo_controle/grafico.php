<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produção SAP | Dashboard</title>
    
    <!-- Bootstrap para o Layout -->
    <link href="https://cdn.jsdelivr.net" rel="stylesheet">
    
    <style>
        :root { --primary: #4f46e5; --dark: #0f172a; --bg: #f8fafc; }
        body { background-color: var(--bg); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #334155; }
        
        /* Topbar */
        .navbar { background: var(--dark); color: white; padding: 1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .navbar-brand { font-weight: 800; letter-spacing: 1px; color: white !important; }

        /* Sidebar de Filtros */
        .sidebar-card { border: none; border-radius: 16px; background: white; padding: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .filter-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 8px; display: block; }
        .form-select { border-radius: 10px; border: 1px solid #e2e8f0; padding: 12px; font-size: 0.95rem; }
        
        /* Card do Gráfico */
        .chart-card { border: none; border-radius: 16px; background: white; padding: 25px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); min-height: 550px; }
        .chart-container { position: relative; height: 450px; width: 100%; }

        .btn-sync { 
            background: var(--primary); color: white; border: none; font-weight: 600; 
            padding: 12px; border-radius: 10px; width: 100%; transition: 0.3s;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4);
        }
        .btn-sync:hover { background: #4338ca; transform: translateY(-2px); }
        
        #loader { display: none; color: var(--primary); }
    </style>
</head>
<body>

<nav class="navbar mb-5">
    <div class="container-fluid px-4">
        <span class="navbar-brand">PRODUÇÃO <span style="color: #818cf8;">SAP</span></span>
        <div id="loader" class="spinner-border spinner-border-sm" role="status"></div>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="row g-4">
        <!-- Filtros (Lateral) -->
        <div class="col-lg-3">
            <div class="sidebar-card">
                <h6 class="fw-bold mb-4">Parâmetros</h6>
                
                <div class="mb-4">
                    <label class="filter-label">Operador Técnico</label>
                    <select id="userSelect" class="form-select" onchange="atualizarGrafico()">
                        <option value="">Carregando usuários...</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="filter-label">Modo de Exibição</label>
                    <select id="pedidoSelect" class="form-select" onchange="atualizarGrafico()">
                        <option value="por_fase">Detalhado por Subfase</option>
                        <option value="geral">Total Consolidado</option>
                    </select>
                </div>

                <button class="btn-sync" onclick="atualizarGrafico()">Atualizar Dashboard</button>
            </div>
        </div>

        <!-- Dashboard (Principal) -->
        <div class="col-lg-9">
            <div class="chart-card">
                <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                    <h5 class="fw-bold m-0" id="userTitle">Desempenho Geral</h5>
                    <span class="badge bg-light text-primary border border-primary-subtle px-3 py-2">Frequência Semanal</span>
                </div>
                
                <div class="chart-container">
                    <canvas id="graficoSemanal"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="chart.umd.min.js"></script>
<script>

// Busca a lista de usuários da API
async function inicializar() {
    try {
        const res = await fetch('api.php?pedido=usuarios');
        const usuarios = await res.json();
        const select = document.getElementById('userSelect');
        select.innerHTML = '<option value="">Selecione o Operador</option>';
        usuarios.forEach(u => {
            select.add(new Option(u.nome_usuario, u.nome_usuario));
        });
    } catch (e) {
        console.error("Erro ao buscar usuários:", e);
    }
}

// 1. Defina a variável da instância FORA da função
let chartInstance = null;

async function atualizarGrafico() {
    const user = document.getElementById('userSelect').value;
    const loader = document.getElementById('loader');
    const canvasElement = document.getElementById('graficoSemanal');

    // Validação básica do elemento canvas
    if (!canvasElement) {
        console.error("Erro: Elemento canvas 'graficoSemanal' não encontrado.");
        return;
    }

    loader.style.display = 'inline-block';

    try {
        const response = await fetch(`api.php?pedido=geral_fases_semanal&usuario=${encodeURIComponent(user)}`);
        const dados = await response.json();
        if (!dados || dados.length === 0) {
            console.warn("Nenhum dado retornado para este usuário.");
            // Opcional: destruir gráfico se não houver dados
            if (chartInstance) chartInstance.destroy();
            return;
        }
        // Preparar dados
        const labels = dados.map(item => item.periodo_semana);
        const valores = dados.map(item => parseInt(item.total));
        // 2. Destruição segura da instância anterior
        if (chartInstance instanceof Chart) {
            chartInstance.destroy();
        }
        const ctx = canvasElement.getContext('2d');
        // 3. Criar a nova instância e salvar na variável global
        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Produção por Semana',
                    data: valores,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false, // Ajuda a controlar o tamanho via CSS
                plugins: {
                    legend: { display: true },
                    tooltip: {
                        callbacks: {
                            afterLabel: (context) => {
                                const i = context.dataIndex;
                                return `Bloco: ${dados[i].bloco}\nUsuário: ${dados[i].usuario}`;
                            }
                        }
                    }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
        
        document.getElementById('userTitle').innerText = `Operador: ${user}`;

    } catch (e) {
        console.error("Erro ao carregar gráfico:", e);
    } finally {
        loader.style.display = 'none';
    }
}


window.onload = inicializar;
</script>
</body>
</html>
