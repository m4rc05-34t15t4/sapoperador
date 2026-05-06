document.addEventListener('DOMContentLoaded', function() {

    const inputInicio = document.querySelector('input[name="data_inicio"]');
    const inputFim = document.querySelector('input[name="data_fim"]');
    chartInstance = null;
    let ordemAscendente = true;
    let ultimaColunaIdx = -1;
    let ultimaTabela = '';
    let processosConcluidos = 0;
    var ganchosAgrupados = {'execucao' : null, 'revisao' : null, 'usuarios' : {}};

    //FUNÇÕES

    function verificarCarregamento() {
        processosConcluidos++;
        
        // Quando chegar a 3, esconde o spinner
        if (processosConcluidos >= 2) {
            document.getElementById('meu-loader').style.display = 'none';
            // Resetar para a próxima vez que precisar carregar
            processosConcluidos = 0; 
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
            let valA = a.cells[colunaIdx].innerText.trim();
            let valB = b.cells[colunaIdx].innerText.trim();
            if(id_tabela == 'corpoTabela' && colunaIdx == 6){
                valA = a.cells[colunaIdx].getAttribute("sort");
                valB = b.cells[colunaIdx].getAttribute("sort");
            }
            // Se for a coluna de data (formato 2026-03-25) ou campo de sort especial
            else if ((id_tabela === 'corpoTabela-usuarios' && (colunaIdx == 2 || colunaIdx == 3 )) && valA.includes('-')) {
                return ordemAscendente 
                    ? valA.localeCompare(valB) 
                    : valB.localeCompare(valA);
            }
            const numA = parseFloat(valA.replace(',', '.'));
            const numB = parseFloat(valB.replace(',', '.'));
            if (!isNaN(numA) && !isNaN(numB)) return ordemAscendente ? numA - numB : numB - numA;
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

            atualizar_atividade(total_por_usuario);

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
            const seletor = `${linha.lote_id}-${linha.subfase_id}-${linha.bloco}-${linha.usuario}-${linha.numero_semana}`;
            const qtd_ganchos = ganchosAgrupados?.[tipoFormatado]?.[seletor]?.['TotalPontos'] ?? '-';
            const dicionario = ganchosAgrupados?.[tipoFormatado]?.[seletor]?.['Title'];
            const title = dicionario ? Object.entries(dicionario).map(([key, value]) => `${key}: ${value}`).join(', ') : '-';
            tr.innerHTML = `
                <td style="vertical-align: middle;">(${linha.lote_id}) ${resposta.lote[linha.lote_id]['nome_abrev']}</td> 
                <td style="vertical-align: middle;">(${linha.subfase_id}) ${resposta.subfase[linha.subfase_id]['nome']}</td>
                <td style="vertical-align: middle;">${linha.bloco}</td>
                <td style="vertical-align: middle;">${tipoFormatado}</td>
                <td style="vertical-align: middle;">${linha.usuario}</td>
                <td style="vertical-align: middle; text-align: center;">${linha.total}</td>
                <td style="vertical-align: middle; text-align: center;" title="${title}">${qtd_ganchos}</td>
                <td style="vertical-align: middle; text-align: center;" sort="${sort_periodo_valor(linha)}">(${linha.numero_semana}) ${linha.periodo_semana}</td>
            `;
            tbody.appendChild(tr);
        });
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
            ganchosAgrupados['usuarios'][item.executor]['ganchos_recebidos_corrigidos'] = tot_pontos_corrigidos;
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
            acc[chave].TotalPontos += parseInt(item.total_pontos || 0);
            acc[chave].TotalCorrigidos += (item.corrigido === true || item.corrigido === "t") ? parseInt(item.total_pontos || 0) : 0;
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
                    executores: {} // O problema estava aqui!
                };
            } 
            if (!ganchosAgrupados['usuarios'][item.revisor].executores) ganchosAgrupados['usuarios'][item.revisor].executores = {};
            let refExecutores = ganchosAgrupados['usuarios'][item.revisor].executores;
            if (item.executor) {
                if (!refExecutores[item.executor]) refExecutores[item.executor] = 0;
                refExecutores[item.executor] += tot_pontos;
            }
            ganchosAgrupados['usuarios'][item.revisor]['ganchos_criados'] += tot_pontos;
            ganchosAgrupados['usuarios'][item.revisor]['ganchos_criados_corrigidos'] = tot_pontos_corrigidos;
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
            console.log(resposta);
            if (!resposta.dados || resposta.dados.length === 0) {
                console.warn("Nenhum dado retornado para este usuário.");
                // Opcional: destruir gráfico se não houver dados
                if (chartInstance) chartInstance.destroy();
                loader.style.display = 'none';
                popularFiltrosBase(resposta);
                return;
            }
            agrupar_ganchos(resposta.ganchos);
            grafico(resposta.dados);
            preencherTabela(resposta);
            atualizarIcones(-1, 'tabela-dados', false);
            popularFiltrosBase(resposta);
        } finally {
            verificarCarregamento();
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
                <td title="${title_gancho_rec}">${gancho_rec}</td>
                <td title="${title_gancho_cri}">${gancho_apl}</td>
                <td>${totais?.[item.id]?.total || '-'}</td>
            `;
            corpoTabela.appendChild(tr);
        });
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
            verificarCarregamento();
        }
    }

    //EXECUÇÃO
    atualizarGrafico();

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

});