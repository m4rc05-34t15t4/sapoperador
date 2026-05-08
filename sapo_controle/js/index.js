function ajustarAlturaHeader() {
    // Substitua 'header' pelo seletor correto do seu cabeçalho (ex: '.navbar' ou '#meuHeader')
    const header = document.querySelector('header'); 
    
    if (header) {
        const altura = header.offsetHeight;
        // Define a variável no root do HTML para o CSS acessar
        document.documentElement.style.setProperty('--header-height', altura + 'px');
    }
}

document.addEventListener('DOMContentLoaded', function() {

    ajustarAlturaHeader();

    const inputInicio = document.querySelector('input[name="data_inicio"]');
    const inputFim = document.querySelector('input[name="data_fim"]');
    chartInstance = null;
    let ordemAscendente = true;
    let ultimaColunaIdx = -1;
    let ultimaTabela = '';
    let processosConcluidos = 0;
    const totalProcessos = 5;
    var ganchosAgrupados = {'execucao' : null, 'revisao' : null, 'usuarios' : {}};
    const cor_revisao = 'rgba(255, 99, 132, 0.5)';
    const cor_execucao = 'rgba(54, 162, 235, 0.5)';
    const cor_correcao = 'rgba(72, 240, 189, 0.5)';

    //FUNÇÕES

    function calcularMediana(numeros) {
        // 1. Ordenar o array de forma numérica (Crescente)
        const ordenados = [...numeros].sort((a, b) => a - b);
        const meio = Math.floor(ordenados.length / 2);
        const min = ordenados[0];
        const max = ordenados[ordenados.length -1];
        // 2. Verificar se o tamanho é par ou ímpar
        if (ordenados.length % 2 !== 0) {
            // Se for ímpar, retorna o valor do meio
            return [ordenados[meio], min, max];
        } else {
            // Se for par, faz a média dos dois valores centrais
            return [(ordenados[meio - 1] + ordenados[meio]) / 2, min, max];
        }
    }

    function aplicarBarrasProgresso(filtroAtivo = null, corBase = '#a5d6a7') {
        const sufixo = filtroAtivo ? `_${filtroAtivo}` : "";
        const seletor = `th[min${sufixo}][max${sufixo}]`;
        const colunasRef = document.querySelectorAll(seletor);

        colunasRef.forEach((th) => {

            if (filtroAtivo) {
                const filtrosPermitidos = th.getAttribute('mediana_filtros') || "";
                const listaFiltros = filtrosPermitidos.split(',').map(f => f.trim());
                if (!listaFiltros.includes(filtroAtivo)) return;
            }

            const corFinal = th.getAttribute('mediana_cor') || corBase;
            const index = th.cellIndex;
            const min = parseFloat(th.getAttribute(`min${sufixo}`));
            const max = parseFloat(th.getAttribute(`max${sufixo}`));
            const mediana = parseFloat(th.getAttribute(`mediana${sufixo}`));
            const range = max - min;

            const tabela = th.closest('table');
            const linhas = tabela.querySelectorAll('tbody tr');

            linhas.forEach((tr) => {
                const td = tr.cells[index];
                if (td) {
                    // Filtra por tipo_mediana se houver filtro ativo
                    if (filtroAtivo && td.getAttribute('tipo_mediana') !== filtroAtivo) return; 

                    const rawValue = td.hasAttribute('sort') ? td.getAttribute('sort') : td.innerText;
                    const valorAtual = parseFloat(String(rawValue).replace(',', '.')) || 0;

                    let percentual = 0;
                    if (range > 0) {
                        percentual = ((valorAtual - min) / range) * 100;
                        percentual = Math.min(Math.max(percentual, 0), 100);
                    }

                    // Se o valor for >= mediana, escurecemos a cor base em 20% usando CSS brightness
                    // Caso contrário, usa a corBase pura
                    const filtroCor = valorAtual >= mediana ? 'brightness(0.8)' : 'none';

                    td.style.backgroundImage = `linear-gradient(to right, ${corFinal} ${percentual}%, transparent ${percentual}%)`;
                    td.style.backgroundRepeat = 'no-repeat';
                    td.style.backgroundSize = '100% 100%';
                    td.style.backdropFilter = filtroCor; // Aplica o destaque na cor da barra
                    td.style.position = 'relative';
                }
            });
        });
    }


    function ajustarAlturaHeader() {
        // Substitua 'header' pelo seletor correto do seu cabeçalho (ex: '.navbar' ou '#meuHeader')
        const header = document.querySelector('header'); 
        
        if (header) {
            const altura = header.offsetHeight;
            // Define a variável no root do HTML para o CSS acessar
            document.documentElement.style.setProperty('--header-height', altura + 'px');
        }
    }

    function verificarCarregamento() {
        processosConcluidos++;
        
        // Calcula o percentual: (1/3 = 33%, 2/3 = 66%, 3/3 = 100%)
        const percentual = (processosConcluidos / totalProcessos) * 100;
        
        // Atualiza a largura da barra
        const barra = document.getElementById('barra-loader');
        if (barra) {
            barra.style.width = percentual + "%";
        }

        if (processosConcluidos >= totalProcessos) {
            // Pequeno delay para o usuário ver a barra em 100% antes de sumir
            setTimeout(() => {
                document.getElementById('meu-loader').style.display = 'none';
                // Reseta a barra e o contador
                processosConcluidos = 0;
                barra.style.width = "0%";
            }, 500);
        }
    }

    function atualizarIcones(colunaAtiva=-1, id_tabela='tabela-dados', ascendente=false ) {
        // 1. Resetar todos os ícones para o estado neutro
        document.querySelectorAll('#'+id_tabela+' tr th i').forEach((icon, idx) => {
            icon.className = "bi bi-arrow-down-up small float-end mt-1 text-white"; // Classe padrão
            // 2. Se for a coluna clicada, muda o ícone e a cor
            if (idx === colunaAtiva) {
                if (ascendente) icon.className = "bi bi-caret-up-fill text-success float-end mt-1"; // Seta pra cima azul
                else icon.className = "bi bi-caret-down-fill text-success float-end mt-1"; // Seta pra baixo azul
            }
        });
    }

    function ordenarTabela(colunaIdx, id_tabela='corpoTabela') {
        const tabela = document.getElementById(id_tabela);
        const linhas = Array.from(tabela.rows);
        // Lógica de inversão
        if (ultimaColunaIdx === colunaIdx && ultimaTabela == id_tabela) ordemAscendente = !ordemAscendente;
        else {
            ordemAscendente = true;
            ultimaColunaIdx = colunaIdx;
            ultimaTabela = id_tabela;
        }
        // Ordenação (mantendo sua lógica de números e texto)
        const linhasOrdenadas = linhas.sort((a, b) => {
            // 1. Captura básica dos valores
            let valA = a.cells[colunaIdx].innerText?.trim() || "";
            let valB = b.cells[colunaIdx].innerText?.trim() || "";

            // 2. Lógica para atributos de sort específicos
            if (id_tabela == 'corpoTabela' && colunaIdx == 7) {
                valA = a.cells[colunaIdx].getAttribute("sort") || "";
                valB = b.cells[colunaIdx].getAttribute("sort") || "";
            }

            // 3. Lógica para Datas (ISO: YYYY-MM-DD)
            if ((id_tabela === 'corpoTabela-usuarios' && (colunaIdx == 2 || colunaIdx == 3)) && valA.includes('-')) {
                return ordemAscendente 
                    ? valA.localeCompare(valB) 
                    : valB.localeCompare(valA);
            }

            // 4. Lógica para ganchos
            if (id_tabela === 'corpoTabela-usuarios' && (colunaIdx == 7 || colunaIdx == 8)) {
                valA = a.cells[colunaIdx].getAttribute("sort") || "";
                valB = b.cells[colunaIdx].getAttribute("sort") || "";
            }

            // 5. Lógica para Números
            // Remove pontos de milhar e troca vírgula por ponto para o parseFloat funcionar
            const numA = parseFloat(valA.replace(/\./g, '').replace(',', '.'));
            const numB = parseFloat(valB.replace(/\./g, '').replace(',', '.'));

            if (!isNaN(numA) && !isNaN(numB)) {
                return ordemAscendente ? numA - numB : numB - numA;
            }

            // 6. Fallback para Texto (localeCompare) - Garantindo que nunca seja null
            return ordemAscendente 
                ? valA.localeCompare(valB, 'pt-BR', { numeric: true }) 
                : valB.localeCompare(valA, 'pt-BR', { numeric: true });
        });
        tabela.append(...linhasOrdenadas);
        //atualizarIcones(colunaIdx, id_tabela, ordemAscendente);
    }
    
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

    function sort_periodo_valor(item){
        const [d, m, a] = item.periodo_semana.split(" - ")[0].split("/");
        return `${`${a}${m}${d}`}${String(item.numero_semana).padStart(2, '0')}`;
    }

    async function popularFiltrosBase(resposta) {
        try {

            const anoSelect = document.getElementById('anoSelect');
            const semanaSelect = document.getElementById('semanaSelect');
            const loteSelect = document.getElementById('loteSelect');
            const subfaseSelect = document.getElementById('subfaseSelect');
            const blocoSelect = document.getElementById('blocoSelect');
            const urlParams = new URLSearchParams(window.location.search);
            const userDaUrl = urlParams.get('nome_guerra') || '';
            popularSelectUsuarios(userDaUrl);

            const listaAnos = [...new Set(resposta.dados.map(item => item.ano))].sort((a, b) => b - a);
            const listaLote = [...new Set(resposta.dados.map(item => item.lote_id))].sort((a, b) => b - a);
            const listaSubfase = [...new Set(resposta.dados.map(item => item.subfase_id))].sort((a, b) => b - a);
            const listaBloco = [...new Set(resposta.dados.map(item => item.bloco))].sort((a, b) => b - a);
            const mapaSemanas = new Map();
            let total_por_usuario = [];
            resposta.dados.forEach(item => {
                if (!mapaSemanas.has(item.numero_semana)) {
                    mapaSemanas.set(item.numero_semana, {
                        numero: item.numero_semana,
                        periodo: item.periodo_semana,
                        ano_ini: item.ano,
                        sortKey: parseInt(sort_periodo_valor(item)) // Usaremos isso para ordenar
                    });
                }
                if(total_por_usuario[item.usuario_id] == undefined) total_por_usuario[item.usuario_id] = {"execucao" : 0, "correcao" : 0, "revisao" : 0, "total" : 0};
                total_por_usuario[item.usuario_id][item.tipo.split("_")[2]] += parseInt(item.total);
                total_por_usuario[item.usuario_id]["total"] += parseInt(item.total);
            });
            const listaSemanas = Array.from(mapaSemanas.values()).sort((a, b) => b.sortKey - a.sortKey);

            // Popular Anos
            anoSelect.innerHTML = '<option value="">Todos</option>';
            listaAnos.forEach(ano => {
                anoSelect.innerHTML += `<option value="${ano}">${ano}</option>`;
            });

            // Popular Semanas
            semanaSelect.innerHTML = '<option value="">Todas</option>';
            Array.from(listaSemanas.values()).forEach(s => { semanaSelect.innerHTML += `<option value="${s.numero}-${s.ano_ini}">S. ${s.numero} (${s.periodo})</option>`;});

            // Popular Lote
            loteSelect.innerHTML = '<option value="">Todos</option>';
            listaLote.forEach(l => { loteSelect.innerHTML += `<option value="${l}">(${l}) ${resposta.lote[l]['nome_abrev']}</option>`; });

            // Popular Subfase
            subfaseSelect.innerHTML = '<option value="">Todas</option>';
            listaSubfase.forEach(s => { subfaseSelect.innerHTML += `<option value="${s}">(${s}) ${resposta.subfase[s]['nome']}</option>`; });

            // Popular Bloco
            blocoSelect.innerHTML = '<option value="">Todos</option>';
            listaBloco.forEach(b => { blocoSelect.innerHTML += `<option value="${b}">${b}</option>`; });

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

            await atualizar_atividade(total_por_usuario);

        } catch (e) {
            console.error("Erro ao popular filtros:", e);
        }
    }

    function preencherTabela(resposta) {
        const tbody = document.getElementById('corpoTabela');
        tbody.innerHTML = ""; // Limpa a tabela antes de preencher
        const mediana_total_execucao = [];
        const mediana_total_revisao = [];
        const mediana_total_correcao = [];
        const mediana_ganchos_execucao = [];
        const mediana_ganchos_revisao = [];
        resposta.dados.forEach(linha => {
            const tr = document.createElement("tr");
            // Lógica do explode("_", tipo)[2] em JS:
            const tipoFormatado = linha.tipo ? linha.tipo.split("_")[2] : "";
            const seletor = `${linha.lote_id}-${linha.subfase_id}-${linha.bloco}-${linha.usuario}-${linha.numero_semana}`;
            const qtd_ganchos = ganchosAgrupados?.[tipoFormatado]?.[seletor]?.['TotalPontos'] ?? '-';
            const dicionario = ganchosAgrupados?.[tipoFormatado]?.[seletor]?.['Title'];
            const title = dicionario ? Object.entries(dicionario).map(([key, value]) => `${key}: ${value}`).join(', ') : '-';
            const back_cor = {"execucao" : cor_execucao, "correcao" : cor_correcao, "revisao" : cor_revisao};
            tr.innerHTML = `
                <td style="vertical-align: middle;">(${linha.lote_id}) ${resposta.lote[linha.lote_id]['nome_abrev']}</td> 
                <td style="vertical-align: middle;">(${linha.subfase_id}) ${resposta.subfase[linha.subfase_id]['nome']}</td>
                <td style="vertical-align: middle;">${linha.bloco}</td>
                <td style="vertical-align: middle; background-color: ${back_cor[tipoFormatado]};">${tipoFormatado}</td>
                <td style="vertical-align: middle;">${linha.usuario}</td>
                <td style="vertical-align: middle; text-align: center;" tipo_mediana="${tipoFormatado}">${linha.total}</td>
                <td style="vertical-align: middle; text-align: center;" title="${title}" tipo_mediana="${tipoFormatado}">${qtd_ganchos}</td>
                <td style="vertical-align: middle; text-align: center;" sort="${sort_periodo_valor(linha)}">(${linha.numero_semana}) ${linha.periodo_semana}</td>
            `;
            tbody.appendChild(tr);
            if (linha.total !== undefined && linha.total !== null && typeof parseInt(linha.total) === 'number' && parseInt(linha.total) > 0 && tipoFormatado == 'execucao') mediana_total_execucao.push(parseInt(linha.total));
            if (linha.total !== undefined && linha.total !== null && typeof parseInt(linha.total) === 'number' && parseInt(linha.total) > 0 && tipoFormatado == 'correcao') mediana_total_correcao.push(parseInt(linha.total));
            if (linha.total !== undefined && linha.total !== null && typeof parseInt(linha.total) === 'number' && parseInt(linha.total) > 0 && tipoFormatado == 'revisao') mediana_total_revisao.push(parseInt(linha.total));
            if (qtd_ganchos !== undefined && qtd_ganchos !== null && typeof qtd_ganchos === 'number' && qtd_ganchos > 0 && tipoFormatado == 'execucao') mediana_ganchos_execucao.push(qtd_ganchos);
            if (qtd_ganchos !== undefined && qtd_ganchos !== null && typeof qtd_ganchos === 'number' && qtd_ganchos > 0 && tipoFormatado == 'revisao') mediana_ganchos_revisao.push(qtd_ganchos);
        });

        const valor_mediana_total_execucao = calcularMediana(mediana_total_execucao);
        const valor_mediana_total_correcao = calcularMediana(mediana_total_correcao);
        const valor_mediana_total_revisao = calcularMediana(mediana_total_revisao);
        const valor_mediana_ganchos_execucao = calcularMediana(mediana_ganchos_execucao);
        const valor_mediana_ganchos_revisao = calcularMediana(mediana_ganchos_revisao);

        document.getElementById('th_unidades_total').setAttribute('mediana_filtros', 'execucao,revisao,correcao');
        document.getElementById('th_unidades_total').setAttribute('mediana_execucao', valor_mediana_total_execucao[0]);
        document.getElementById('th_unidades_total').setAttribute('min_execucao', valor_mediana_total_execucao[1]);
        document.getElementById('th_unidades_total').setAttribute('max_execucao', valor_mediana_total_execucao[2]);
        document.getElementById('th_unidades_total').setAttribute('mediana_correcao', valor_mediana_total_correcao[0]);
        document.getElementById('th_unidades_total').setAttribute('min_correcao', valor_mediana_total_correcao[1]);
        document.getElementById('th_unidades_total').setAttribute('max_correcao', valor_mediana_total_correcao[2]);
        document.getElementById('th_unidades_total').setAttribute('mediana_revisao', valor_mediana_total_revisao[0]);
        document.getElementById('th_unidades_total').setAttribute('min_revisao', valor_mediana_total_revisao[1]);
        document.getElementById('th_unidades_total').setAttribute('max_revisao', valor_mediana_total_revisao[2]);

        document.getElementById('th_unidades_ganchos').setAttribute('mediana_filtros', 'execucao,revisao');
        document.getElementById('th_unidades_ganchos').setAttribute('mediana_execucao', valor_mediana_ganchos_execucao[0]);
        document.getElementById('th_unidades_ganchos').setAttribute('min_execucao', valor_mediana_ganchos_execucao[1]);
        document.getElementById('th_unidades_ganchos').setAttribute('max_execucao', valor_mediana_ganchos_execucao[2]);
        document.getElementById('th_unidades_ganchos').setAttribute('mediana_revisao', valor_mediana_ganchos_revisao[0]);
        document.getElementById('th_unidades_ganchos').setAttribute('min_revisao', valor_mediana_ganchos_revisao[1]);
        document.getElementById('th_unidades_ganchos').setAttribute('max_revisao', valor_mediana_ganchos_revisao[2]);
    }

    function agrupar_ganchos(ganchos){
        //Executor
        const ganchosAgrupadosExecucao = ganchos.reduce((acc, item) => {
            const chave = `${item.lote_id}-${item.subfase_id}-${item.bloco}-${item.executor}-${item.execucao_numero_semana}`;
            if (!acc[chave]) {
                acc[chave] = {
                    Lote: item.lote_id,
                    Subfase: item.subfase_id,
                    Bloco: item.bloco,
                    Executor: item.executor,
                    Revisores: [], 
                    Title: {},
                    Nr_semana: item.execucao_numero_semana, 
                    Periodo_semana: item.execucao_periodo_semana,
                    TotalPontos: 0,
                    TotalCorrigidos: 0
                };
            }
            const tot_pontos = parseInt(item.total_pontos || 0);
            const tot_pontos_corrigidos = (item.corrigido === true || item.corrigido === "t") ? tot_pontos : 0;
            acc[chave].TotalPontos += tot_pontos;
            acc[chave].TotalCorrigidos += tot_pontos_corrigidos;
            acc[chave].Revisores.push({
                revisor: item.revisor,
                ganchos: item.total_pontos,
                corrigidos: item.corrigido,
                nr_semana: item.revisao_numero_semana, 
                periodo_semana: item.revisao_periodo_semana,
                revisao_inicio: item.revisao_inicio,
                revisao_fim: item.revisao_fim,
                id_unidade: item.id_unidade
            });
            if(!acc[chave].Title[item.revisor]) acc[chave].Title[item.revisor] = 0;
            acc[chave].Title[item.revisor] += tot_pontos;
            //qtd_por_usuario
            if(!ganchosAgrupados['usuarios'][item.executor]) {
                ganchosAgrupados['usuarios'][item.executor] = { 
                    ganchos_recebidos: 0, 
                    ganchos_recebidos_corrigidos: 0, 
                    ganchos_criados: 0, 
                    ganchos_criados_corrigidos: 0, 
                    revisores: {}
                };
            }
            if (!ganchosAgrupados['usuarios'][item.executor].revisores) ganchosAgrupados['usuarios'][item.executor].revisores = {};
            let refExecutores = ganchosAgrupados['usuarios'][item.executor].revisores;
            if (item.revisor) {
                if (!refExecutores[item.revisor]) refExecutores[item.revisor] = 0;
                refExecutores[item.revisor] += tot_pontos;
            }
            ganchosAgrupados['usuarios'][item.executor]['ganchos_recebidos'] += tot_pontos;
            ganchosAgrupados['usuarios'][item.executor]['ganchos_recebidos_corrigidos'] += tot_pontos_corrigidos;
            return acc;
        }, {});
        ganchosAgrupados['execucao'] = ganchosAgrupadosExecucao;
        //Revisor
        const ganchosAgrupadosRevisao = ganchos.reduce((acc, item) => {
            const chave = `${item.lote_id}-${item.subfase_id}-${item.bloco}-${item.revisor}-${item.revisao_numero_semana}`;
            if (!acc[chave]) {
                acc[chave] = {
                    Lote: item.lote_id,
                    Subfase: item.subfase_id,
                    Bloco: item.bloco,
                    Revisor: item.revisor,
                    Executores: [], 
                    Title: {},
                    Nr_semana: item.revisao_numero_semana, 
                    Periodo_semana: item.revisao_periodo_semana,
                    TotalPontos: 0,
                    TotalCorrigidos: 0
                };
            }
            const tot_pontos = parseInt(item.total_pontos || 0);
            const tot_pontos_corrigidos = (item.corrigido === true || item.corrigido === "t") ? tot_pontos : 0;
            acc[chave].TotalPontos += tot_pontos;
            acc[chave].TotalCorrigidos += tot_pontos_corrigidos;
            acc[chave].Executores.push({
                executor: item.executor,
                ganchos: item.total_pontos,
                corrigidos: item.corrigido,
                nr_semana: item.execucao_numero_semana, 
                periodo_semana: item.execucao_periodo_semana,
                revisao_inicio: item.execucao_inicio,
                revisao_fim: item.execucao_fim,
                id_unidade: item.id_unidade
            });
            if(!acc[chave].Title[item.executor]) acc[chave].Title[item.executor] = 0;
            acc[chave].Title[item.executor] += tot_pontos;
            if(!ganchosAgrupados['usuarios'][item.revisor]) {
                ganchosAgrupados['usuarios'][item.revisor] = { 
                    ganchos_recebidos: 0, 
                    ganchos_recebidos_corrigidos: 0, 
                    ganchos_criados: 0, 
                    ganchos_criados_corrigidos: 0, 
                    executores: {}
                };
            } 
            if (!ganchosAgrupados['usuarios'][item.revisor].executores) ganchosAgrupados['usuarios'][item.revisor].executores = {};
            let refExecutores = ganchosAgrupados['usuarios'][item.revisor].executores;
            if (item.executor) {
                if (!refExecutores[item.executor]) refExecutores[item.executor] = 0;
                refExecutores[item.executor] += tot_pontos;
            }
            ganchosAgrupados['usuarios'][item.revisor]['ganchos_criados'] += tot_pontos;
            ganchosAgrupados['usuarios'][item.revisor]['ganchos_criados_corrigidos'] += tot_pontos_corrigidos;
            return acc;
        }, {});
        ganchosAgrupados['revisao'] = ganchosAgrupadosRevisao;
        console.log('ganchosAgrupados', ganchosAgrupados);
    }

    async function atualizarGrafico() {
        try{
            const parametros = window.location.search.replace('?', '&');
            const response = await fetch(`./api.php?pedido=geral_fases_semanal${parametros}`);
            const resposta = await response.json();
            if (!resposta.dados || resposta.dados.length === 0) {
                console.warn("Nenhum dado retornado para este usuário.");
                // Opcional: destruir gráfico se não houver dados
                if (chartInstance) chartInstance.destroy();
                loader.style.display = 'none';
                popularFiltrosBase(resposta);
                return;
            }
            
            verificarCarregamento(); //1

            agrupar_ganchos(resposta.ganchos);
            grafico(resposta.dados);
            preencherTabela(resposta);
            atualizarIcones(-1, 'tabela-dados', false);

            verificarCarregamento(); //2
            
            await popularFiltrosBase(resposta);
        } finally {
            verificarCarregamento(); //5
        }

    }

    function formatarDataHora(dataISO) {
        if (!dataISO) return '-';
        
        const data = new Date(dataISO);
        
        // Formata cada parte individualmente para garantir os zeros à esquerda
        const ano = data.getFullYear();
        const mes = String(data.getMonth() + 1).padStart(2, '0');
        const dia = String(data.getDate()).padStart(2, '0');
        const hora = String(data.getHours()).padStart(2, '0');
        const min = String(data.getMinutes()).padStart(2, '0');

        return `${ano}-${mes}-${dia} ${hora}:${min}`;
    }

    // Exemplo de saída: "2026-03-25 14:30"


    function popularTabelaUsuarios(dados, totais) {
        const corpoTabela = document.getElementById('corpoTabela-usuarios');
        corpoTabela.innerHTML = '';
        const em_atividade = dados.em_atividade.reduce((acc, item) => {
            // A key será o usuario_id do item atual
            acc[item.usuario_id] = item;
            return acc;
        }, {});
        const mediana_execucao = [];
        const mediana_correcao = [];
        const mediana_revisao = [];
        const mediana_ganchos_rec = [];
        const mediana_ganchos_apl = [];
        const mediana_total_atv = [];
        dados.usuarios.forEach(item => {
            const tr = document.createElement('tr');
            //const isAdmin = item.administrador === 't' ? '<b class="text-success">Sim</b>' : '<b class="text-danger">Não</b>';
            const iconAdmin = item.administrador === 't' ? '<i class="bi bi-shield-lock-fill text-primary" title="Administrador"></i>' : '';
            const nome = `${item.patente_abrev} ${item.nome_guerra}`;
            const usuario = ganchosAgrupados['usuarios']?.[nome];
            const ganchos_recebidos = usuario?.['ganchos_recebidos'] ?? '-'; 
            const ganchos_recebidos_corrigidos = usuario?.['ganchos_recebidos_corrigidos'] ?? '-'; 
            const ganchos_criados = usuario?.['ganchos_criados'] ?? '-';
            const ganchos_criados_corrigidos = usuario?.['ganchos_criados_corrigidos'] ?? '-';
            let gancho_rec = `${ganchos_recebidos_corrigidos} / ${ganchos_recebidos}`;
            if(gancho_rec == '- / -' || gancho_rec == '0 / 0') gancho_rec = '-';
            const title_gancho_rec = usuario?.['revisores'] ? Object.entries(usuario?.['revisores']).map(([key, value]) => `${key}: ${value}`).join(', ') : '';
            const title_gancho_cri = usuario?.['executores'] ? Object.entries(usuario?.['executores']).map(([key, value]) => `${key}: ${value}`).join(', ') : '';
            let gancho_apl = `${ganchos_criados_corrigidos} / ${ganchos_criados}`;
            if(gancho_apl == '- / -' || gancho_apl == '0 / 0') gancho_apl = '-';
            tr.innerHTML = `
                <td>${item.id}</td>
                <td>${nome} ${iconAdmin}</td>
                <td>${formatarDataHora(em_atividade?.[item.id]?.data_inicio) || '-'}</td>
                <td>${formatarDataHora(item.data_login)}</td>
                <td>${totais?.[item.id]?.execucao || '-'}</td>
                <td>${totais?.[item.id]?.correcao || '-'}</td>
                <td>${totais?.[item.id]?.revisao || '-'}</td>
                <td title="${title_gancho_rec}" sort="${ganchos_recebidos}">${gancho_rec}</td>
                <td title="${title_gancho_cri}" sort="${ganchos_criados}">${gancho_apl}</td>
                <td>${totais?.[item.id]?.total || '-'}</td>
            `;
            corpoTabela.appendChild(tr);
            if (totais?.[item.id]?.execucao !== undefined && totais?.[item.id]?.execucao !== null && typeof totais?.[item.id]?.execucao === 'number' && totais?.[item.id]?.execucao > 0) mediana_execucao.push(totais?.[item.id]?.execucao);
            if (totais?.[item.id]?.correcao !== undefined && totais?.[item.id]?.correcao !== null && typeof totais?.[item.id]?.correcao === 'number' && totais?.[item.id]?.correcao > 0) mediana_correcao.push(totais?.[item.id]?.correcao);
            if (totais?.[item.id]?.revisao !== undefined && totais?.[item.id]?.revisao !== null && typeof totais?.[item.id]?.revisao === 'number' && totais?.[item.id]?.revisao > 0) mediana_revisao.push(totais?.[item.id]?.revisao);
            if (usuario?.['ganchos_recebidos'] !== undefined && usuario?.['ganchos_recebidos'] !== null && typeof usuario?.['ganchos_recebidos'] === 'number' && usuario?.['ganchos_recebidos'] > 0) mediana_ganchos_rec.push(usuario?.['ganchos_recebidos']);
            if (usuario?.['ganchos_criados'] !== undefined && usuario?.['ganchos_criados'] !== null && typeof usuario?.['ganchos_criados'] === 'number' && usuario?.['ganchos_criados'] > 0) mediana_ganchos_apl.push(usuario?.['ganchos_criados']);
            if (totais?.[item.id]?.total !== undefined && totais?.[item.id]?.total !== null && typeof totais?.[item.id]?.total === 'number' && totais?.[item.id]?.total > 0) mediana_total_atv.push(totais?.[item.id]?.total);
        });

        const valor_mediana_execucao = calcularMediana(mediana_execucao);
        const valor_mediana_correcao = calcularMediana(mediana_correcao);
        const valor_mediana_revisao = calcularMediana(mediana_revisao);
        const valor_mediana_ganchos_rec = calcularMediana(mediana_ganchos_rec);
        const valor_mediana_ganchos_apl = calcularMediana(mediana_ganchos_apl);
        const valor_mediana_total_atv = calcularMediana(mediana_total_atv);

        document.getElementById('th_usuarios_execucao').setAttribute('mediana', valor_mediana_execucao[0]);
        document.getElementById('th_usuarios_execucao').setAttribute('min', valor_mediana_execucao[1]);
        document.getElementById('th_usuarios_execucao').setAttribute('max', valor_mediana_execucao[2]);

        document.getElementById('th_usuarios_correcao').setAttribute('mediana', valor_mediana_correcao[0]);
        document.getElementById('th_usuarios_correcao').setAttribute('min', valor_mediana_correcao[1]);
        document.getElementById('th_usuarios_correcao').setAttribute('max', valor_mediana_correcao[2]);

        document.getElementById('th_usuarios_revisao').setAttribute('mediana', valor_mediana_revisao[0]);
        document.getElementById('th_usuarios_revisao').setAttribute('min', valor_mediana_revisao[1]);
        document.getElementById('th_usuarios_revisao').setAttribute('max', valor_mediana_revisao[2]);

        document.getElementById('th_usuarios_ganchos_rec').setAttribute('mediana', valor_mediana_ganchos_rec[0]);
        document.getElementById('th_usuarios_ganchos_rec').setAttribute('min', valor_mediana_ganchos_rec[1]);
        document.getElementById('th_usuarios_ganchos_rec').setAttribute('max', valor_mediana_ganchos_rec[2]);

        document.getElementById('th_usuarios_ganchos_apl').setAttribute('mediana', valor_mediana_ganchos_apl[0]);
        document.getElementById('th_usuarios_ganchos_apl').setAttribute('min', valor_mediana_ganchos_apl[1]);
        document.getElementById('th_usuarios_ganchos_apl').setAttribute('max', valor_mediana_ganchos_apl[2]);

        document.getElementById('th_usuarios_total_atv').setAttribute('mediana', valor_mediana_total_atv[0]);
        document.getElementById('th_usuarios_total_atv').setAttribute('min', valor_mediana_total_atv[1]);
        document.getElementById('th_usuarios_total_atv').setAttribute('max', valor_mediana_total_atv[2]);
    }

    async function atualizar_atividade(totais) {
        try{
            const response = await fetch(`./api.php?pedido=usuarios_ativos`);
            const resposta = await response.json();
            console.log('usuarios', resposta);
            popularTabelaUsuarios(resposta, totais);
            ordenarTabela(2, 'corpoTabela-usuarios');
            atualizarIcones(2, 'tabela-usuarios', true);
        }
        finally{
            verificarCarregamento(); //4
        }
    }

    async function fluxoDeAtualizacao() {
        try {
            await atualizarGrafico();
            aplicarBarrasProgresso();
            aplicarBarrasProgresso('revisao', 'rgba(255, 99, 132, 0.5)');
            aplicarBarrasProgresso('execucao', 'rgba(54, 162, 235, 0.5)');
            aplicarBarrasProgresso('correcao', 'rgba(75, 192, 192, 0.5)');
        } catch (error) {
            console.error("Erro no fluxo atualizar gráfico:", error);
        } finally {
            verificarCarregamento(); //6
        }
    }

    //EXECUÇÃO
    fluxoDeAtualizacao();

    //EVENTOS

    const header = document.querySelector('header.fixed-top');
    if (header) {
        const ajustarPaddingBody = () => {
            const altura = header.offsetHeight;
            document.body.style.paddingTop = `${altura}px`;
        };
        ajustarPaddingBody();
        const observer = new ResizeObserver(() => ajustarPaddingBody());
        observer.observe(header);
    }

    // Executa se a janela for redimensionada (importante para responsividade)
    window.addEventListener('resize', ajustarAlturaHeader);

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

    //Ordenar
    document.querySelectorAll('#tabela-dados tr th').forEach((th, index) => {
        th.addEventListener('click', () => { 
            ordenarTabela(index);
            atualizarIcones(index, 'tabela-dados', ordemAscendente);
        });
    });

    document.querySelectorAll('#tabela-usuarios tr th').forEach((th, index) => {
        th.addEventListener('click', () => { 
            ordenarTabela(index, 'corpoTabela-usuarios');
            atualizarIcones(index, 'tabela-usuarios', ordemAscendente);
        });
    });

    document.querySelectorAll('.fab-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            // 1. Pega o ID do destino através do atributo data-target
            const targetId = this.getAttribute('data-target');
            const targetElement = document.getElementById(targetId);

            if (targetElement) {
            // 2. Faz o scroll suave até o elemento com compensação de 100px
            const offset = 100;
            const elementPosition = targetElement.getBoundingClientRect().top + window.scrollY;
            const offsetPosition = elementPosition - offset;

            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });
            }

            // 3. Dispara a animação de piscar do ring
            this.classList.add('ring-blink');
            setTimeout(() => {
            this.classList.remove('ring-blink');
            }, 500);
        });
    });


});