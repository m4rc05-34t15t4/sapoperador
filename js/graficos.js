$(document).ready(function () {
    $.get('conexao_get_dados_graficos.php', {}, function (dados) {
        const $dados_grafico = JSON.parse(dados);
        console.log("dados_grafico", $dados_grafico);

        if ($dados_grafico["grafico_total_semanal_tipo"] != undefined && $dados_grafico["grafico_volume_usuarios_fase"] != undefined) {
            // Configuração para o gráfico de linhas
            const $data_grafico_total_semanal_tipo = {
                labels: [],
                datasets: [
                    {
                        label: 'Hidrografia',
                        data: [],
                        type: 'line',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        fill: false,
                        yAxisID: 'y1'
                    },
                    {
                        label: 'Transporte',
                        data: [],
                        type: 'line',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        fill: false,
                        yAxisID: 'y1'
                    },
                    {
                        label: 'Interseções',
                        data: [],
                        type: 'line',
                        borderColor: 'rgba(255, 206, 86, 1)',
                        backgroundColor: 'rgba(255, 206, 86, 0.2)',
                        fill: false,
                        yAxisID: 'y1'
                    },
                    {
                        label: 'Vegetação',
                        data: [],
                        type: 'line',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        fill: false,
                        yAxisID: 'y1'
                    },
                    {
                        label: 'Reclassificação',
                        data: [],
                        type: 'line',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        backgroundColor: 'rgba(153, 102, 255, 0.2)',
                        fill: false,
                        yAxisID: 'y1'
                    }
                ]
            };

            const $dados_g = {};
            $dados_grafico["grafico_total_semanal_tipo"].forEach(g => {
                if ($dados_g[g['nr_sem']] == undefined) $dados_g[g['nr_sem']] = { 'hid': 0, 'tra': 0, 'int': 0, 'veg': 0, 'rec': 0 };
                $dados_g[g['nr_sem']][g['tipo']] = g['qtd'];
            });

            // Preencher os dados do gráfico de linhas
            const hidValues = Object.values($dados_g).map(item => item.hid);
            const traValues = Object.values($dados_g).map(item => item.tra);
            const intValues = Object.values($dados_g).map(item => item.int);
            const vegValues = Object.values($dados_g).map(item => item.veg);
            const recValues = Object.values($dados_g).map(item => item.rec);

            $data_grafico_total_semanal_tipo['labels'] = Object.keys($dados_g);
            $data_grafico_total_semanal_tipo['datasets'][0]['data'] = hidValues;
            $data_grafico_total_semanal_tipo['datasets'][1]['data'] = traValues;
            $data_grafico_total_semanal_tipo['datasets'][2]['data'] = intValues;
            $data_grafico_total_semanal_tipo['datasets'][3]['data'] = vegValues;
            $data_grafico_total_semanal_tipo['datasets'][4]['data'] = recValues;

            // Preparar os dados para o gráfico de barras empilhadas (volume de usuários)
            const fases = ['Hidrografia', 'Transporte', 'Interseções', 'Vegetação', 'Reclassificação'];
            const cores = ['rgba(255, 99, 132, 0.6)', 'rgba(54, 162, 235, 0.6)', 'rgba(255, 206, 86, 0.6)', 'rgba(75, 192, 192, 0.6)', 'rgba(153, 102, 255, 0.6)'];

            fases.forEach((fase, index) => {
                const volumeFase = {};
                $dados_grafico["grafico_volume_usuarios_fase"].forEach(f => {
                    if (!volumeFase[f.nr_sem]) volumeFase[f.nr_sem] = 0;
                    if (f.fase === fase) {
                        volumeFase[f.nr_sem] = f.qtd_usuarios;
                    }
                });

                const volumeValues = Object.keys($dados_g).map(semana => volumeFase[semana] || 0);
                $data_grafico_total_semanal_tipo['datasets'].push({
                    label: '', // Rótulo removido
                    data: volumeValues,
                    type: 'bar',
                    backgroundColor: cores[index],
                    borderColor: cores[index].replace('0.6', '1'),
                    yAxisID: 'y2',
                    stack: 'volume' // Adiciona ao mesmo "stack" para empilhar
                });
            });

            const configCombined = {
                type: 'bar',
                data: $data_grafico_total_semanal_tipo,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                generateLabels: function (chart) {
                                    // Filtrar apenas os datasets de linha (para ocultar os volumes)
                                    return Chart.defaults.plugins.legend.labels.generateLabels(chart).filter(function (item) {
                                        return item.datasetIndex < 5; // Considera apenas os primeiros 5 datasets (linhas)
                                    });
                                },
                                font: {
                                    size: 24
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Número da Semana',
                                font: {
                                    size: 18
                                }
                            },
                            ticks: {
                                font: {
                                    size: 18
                                }
                            }
                        },
                        y1: {
                            type: 'linear',
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Quantidade - Unidade de trabalho',
                                font: {
                                    size: 18
                                }
                            },
                            ticks: {
                                font: {
                                    size: 18
                                }
                            }
                        },
                        y2: {
                            type: 'linear',
                            position: 'right',
                            stacked: true, // Ativa o empilhamento para o eixo y2
                            title: {
                                display: true,
                                text: 'Quantidade de Operadores',
                                font: {
                                    size: 18
                                }
                            },
                            grid: {
                                drawOnChartArea: false
                            },
                            ticks: {
                                font: {
                                    size: 18
                                }
                            }
                        }
                    }
                }
            };

            const ctxCombined = document.getElementById('grafico_total_semana_tipo').getContext('2d');
            new Chart(ctxCombined, configCombined);
        }
    });
});
