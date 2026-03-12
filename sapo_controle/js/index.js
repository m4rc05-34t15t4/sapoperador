document.addEventListener('DOMContentLoaded', function() {

    const inputInicio = document.querySelector('input[name="data_inicio"]');
    const inputFim = document.querySelector('input[name="data_fim"]');
    chartInstance = null;

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
            resposta.dados.forEach(item => {
                if (!mapaSemanas.has(item.numero_semana)) {
                    mapaSemanas.set(item.numero_semana, {
                        numero: item.numero_semana,
                        periodo: item.periodo_semana
                    });
                }
            });
            const listaSemanas = Array.from(mapaSemanas.values()).sort((a, b) => a.numero - b.numero);

            // Popular Anos
            anoSelect.innerHTML = '<option value="">Todos</option>';
            listaAnos.forEach(ano => {
                anoSelect.innerHTML += `<option value="${ano}">${ano}</option>`;
            });

            // Popular Semanas
            semanaSelect.innerHTML = '<option value="">Todas</option>';
            Array.from(listaSemanas.values()).sort((a, b) => a.numero - b.numero).reverse().forEach(s => { semanaSelect.innerHTML += `<option value="${s.numero}">S. ${s.numero} (${s.periodo})</option>`;});

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
                <td style="vertical-align: middle;">(${linha.lote_id}) ${resposta.lote[linha.lote_id]['nome_abrev']}</td> 
                <td style="vertical-align: middle;">(${linha.subfase_id}) ${resposta.subfase[linha.subfase_id]['nome']}</td>
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
        console.log(resposta);
        if (!resposta.dados || resposta.dados.length === 0) {
            console.warn("Nenhum dado retornado para este usuário.");
            // Opcional: destruir gráfico se não houver dados
            if (chartInstance) chartInstance.destroy();
            loader.style.display = 'none';
            popularFiltrosBase(resposta);
            return;
        }
        grafico(resposta.dados);
        preencherTabela(resposta);
        popularFiltrosBase(resposta);

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

});