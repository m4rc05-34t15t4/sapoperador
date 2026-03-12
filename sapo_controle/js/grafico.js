// Busca a lista de usuários da API
function grafico(dados, canvas='graficoSemanal') {
    let chartInstance = null;
    const canvasElement = document.getElementById(canvas);
    try {
        // Validação básica do elemento canvas
        if (!canvasElement) {
            console.error("Erro: Elemento canvas "+canvas+" não encontrado.");
            return;
        }
        if (!dados || dados.length === 0) {
            console.warn("Nenhum dado retornado para este usuário.");
            // Opcional: destruir gráfico se não houver dados
            if (chartInstance) chartInstance.destroy();
            return;
        }
        // Preparar dados
        const labelsFormatados = [...new Set(dados.map(item => item.periodo_semana + ' (' + item.numero_semana + ')'))];
        const periodosPuros = [...new Set(dados.map(item => item.periodo_semana))];
        const tipos = [...new Set(dados.map(item => item.tipo))];
        // Definimos cores fixas para os tipos (ajuste conforme sua necessidade)
        const cores = {
            's_1_execucao': 'rgba(54, 162, 235, 0.7)',
            's_2_revisao_1': 'rgba(255, 99, 132, 0.7)',
            's_3_correcao_1': 'rgba(75, 192, 192, 0.7)'
        };
        // Criamos os Datasets (um para cada 'tipo')
        const datasets = tipos.map(tipo => {// 'tipo' é o item atual do loop (ex: 's_1_execucao')
            const labelLimpo = tipo.split("_")[2] || tipo;
            return {
                label: labelLimpo,
                backgroundColor: cores[tipo] || '#ccc', // Cor por tipo ou cinza
                data: periodosPuros.map(p => {
                    const itensFiltrados = dados.filter(d => d.periodo_semana === p && d.tipo === tipo);
                    const somaTotal = itensFiltrados.reduce((acumulador, item) => {
                        return acumulador + parseInt(item.total || 0);
                    }, 0);
                    return somaTotal;
                })
            };
        });
        //console.log('datasets', datasets);
        // --- 2. RENDERIZAÇÃO ---
        if (chartInstance instanceof Chart) chartInstance.destroy();
        const ctx = canvasElement.getContext('2d');
        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: { 
                labels: labelsFormatados, 
                datasets: datasets 
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { 
                        stacked: true, 
                        grid: { display: false } 
                    },
                    y: { 
                        stacked: true, 
                        beginAtZero: true 
                    }
                },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { mode: 'index', intersect: false } // Melhora a visualização ao passar o mouse
                }
            }
        });
    } catch (e) {
        console.error("Erro ao carregar gráfico:", e);
    } finally {
        loader.style.display = 'none';
    }
}