$(document).ready(function(){

    $.get('conexao_get_dados_graficos.php', { }, function(dados){
        $dados_grafico = JSON.parse(dados);
        console.log("dados_grafico", $dados_grafico);
        if($dados_grafico["grafico_total_semanal_tipo"] != undefined){
            $data_grafico_total_semanal_tipo = {
                labels: null,
                datasets: [
                    {
                        label: 'Hidrografia',
                        data: [],
                        borderColor: 'rgba(255, 99, 132, 1)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        fill: false
                    },
                    {
                        label: 'Transporte',
                        data: [],
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        fill: false
                    },
                    {
                        label: 'Interseções',
                        data: [],
                        borderColor: 'rgba(255, 206, 86, 1)',
                        backgroundColor: 'rgba(255, 206, 86, 0.2)',
                        fill: false
                    },
                    {
                        label: 'Vegetação',
                        data: [],
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        fill: false
                    },
                    {
                        label: 'Reclassificação',
                        data: [],
                        borderColor: 'rgba(153, 102, 255, 1)',
                        backgroundColor: 'rgba(153, 102, 255, 0.2)',
                        fill: false
                    }
                ]
            };

            $dados_g = {};
            $dados_grafico["grafico_total_semanal_tipo"].forEach(g => {
                if($dados_g[g['nr_sem']] == undefined ) $dados_g[g['nr_sem']] = { 'hid' : 0, 'tra' : 0, 'int' : 0, 'veg' : 0, 'rec' : 0 };
                $dados_g[g['nr_sem']][g['tipo']] = g['qtd'];
            }); 
            console.log('dados_g', $dados_g);

            const hidValues = Object.values($dados_g).map(item => item.hid);
            const traValues = Object.values($dados_g).map(item => item.tra);
            const intValues = Object.values($dados_g).map(item => item.int);
            const vegValues = Object.values($dados_g).map(item => item.veg);
            const recValues = Object.values($dados_g).map(item => item.rec);
            console.log(hidValues, traValues, intValues, vegValues, recValues);

            $data_grafico_total_semanal_tipo['labels'] = Object.keys($dados_g);
            $data_grafico_total_semanal_tipo['datasets'][0]['data'] = hidValues;
            $data_grafico_total_semanal_tipo['datasets'][1]['data'] = traValues;
            $data_grafico_total_semanal_tipo['datasets'][2]['data'] = intValues;
            $data_grafico_total_semanal_tipo['datasets'][3]['data'] = vegValues;
            $data_grafico_total_semanal_tipo['datasets'][4]['data'] = recValues;

            const config = {
                type: 'line',
                data: $data_grafico_total_semanal_tipo,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                font: {
                                    size: 24 // Tamanho da fonte das legendas
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.raw;
                                },
                                titleFont: {
                                    size: 24 // Tamanho da fonte do título do tooltip
                                },
                                bodyFont: {
                                    size: 24 // Tamanho da fonte do corpo do tooltip
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
                                    size: 18 // Tamanho da fonte do título do eixo X
                                }
                            },
                            ticks: {
                                font: {
                                    size: 18 // Tamanho da fonte dos ticks do eixo Y
                                }
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Quantidade - Unidade de trabalho',
                                font: {
                                    size: 18 // Tamanho da fonte do título do eixo X
                                }
                            },
                            ticks: {
                                font: {
                                    size: 18 // Tamanho da fonte dos ticks do eixo Y
                                }
                            }
                        }
                    }
                }
            };
            const ctx = document.getElementById('grafico_total_semana_tipo').getContext('2d');
            new Chart(ctx, config);
        }
    });

});