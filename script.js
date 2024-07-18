$(document).ready(function(){

    const SIGLAS_FUNCOES = { 'hid' : 1, 'tra' : 2, 'int' : 4, 'veg' : 8, 'rec' : 16 };

    function verifica_se_em_trabalho(t, $f){
        if(t[`data_ini_${$f}`] != null && t[`data_fin_${$f}`] == null) $em_trabalho.push([t, $f]);
    }

    function verifica_se_em_erro(t, $f){
        if(t[`data_ini_${$f}`] == null && t[`data_fin_${$f}`] != null) $em_erro.push([t, $f]);
    }

    function verifica_se_reservado(t, $f){
        if(t[`data_ini_${$f}`] == null && t[`data_fin_${$f}`] == null) $em_erro.push([t, $f]);
    }

    function popular_cartas($f, $dados){
        $(`#div_${$f} #titulo_${$f} .qtd_trabalho`).html(` (${$dados.length})`);
        $lista =  $("#lista_"+$f);
        $lista.html('');
        $dados.forEach(t => {
            $lista.append(`<li class="fs-5 my-2 text-center">${String(t['mi']).trim()} (${String(t['id']).trim()}) ${t[`data_fin_${$f}`] != null ? t[`data_fin_${$f}`].slice(2,16) : ''}</li>`);
            verifica_se_em_trabalho(t, $f);
            verifica_se_em_erro(t, $f);
            verifica_se_reservado(t, $f);
        });
    }

    function texto_cartas(carta){
        return `${String(carta[0]['mi'])} (${carta[0]['id']}) ${String(carta[1]).toUpperCase()}`;
    }

    function popular_descricao_cartas(array, tipo){
        if(array.length > 0){
            $("#cartas_"+tipo).html('');
            array.forEach(carta => {
                //if( ( ($em_trabalho.length > 0 && carta[0]['mi'] != $em_trabalho[0]['mi']) || $em_trabalho.length == 0) && (tipo == 'int' || tipo == 'rec'))
                $("#cartas_"+tipo).append(`<div class="em_trabalho mx-2 mb-2">${texto_cartas(carta)}</div>`);
            });
            $("#descricao_"+tipo).fadeIn(500);
        }
    }

    function mostrar_botao_controle(FUNCOES){
        if($em_trabalho.length > 0){
            $id_funcao_em_trabalho = SIGLAS_FUNCOES[$em_trabalho[0][1]];
            if(parseInt($id_funcao_em_trabalho) == 4) $("#botao_finalizar_carta").attr("miid", String($em_trabalho[0][0]['mi_25000']).trim());
            else if(parseInt($id_funcao_em_trabalho) == 16) $("#botao_finalizar_carta").attr("miid", String($em_trabalho[0][0]['mi']).trim());
            else $("#botao_finalizar_carta").attr("miid", $em_trabalho[0][0]['id']);
            $("#botao_finalizar_carta").attr("tipo", $id_funcao_em_trabalho);
            $("#botao_finalizar_carta").html(`Finalizar ${texto_cartas($em_trabalho[0])}`);
            $("#botao_pedir_carta").fadeOut(100);
            $("#botao_finalizar_carta").fadeIn(500);
        }
        else {
            $("#botao_pedir_carta").attr("idusu", $dados_usu['usuario']['id']);
            $("#botao_pedir_carta").attr("tipo", $dados_usu['usuario']['nr_funcao']);
            $("#botao_pedir_carta").html(`Pedir ${FUNCOES[$dados_usu['usuario']['nr_funcao']]}`);
            $("#botao_finalizar_carta").fadeOut(100);
            $("#botao_pedir_carta").fadeIn(500);
        }
    }

    function get_dados(){

        $id_usu = parseInt($("#div-controle-usuario").attr("id_usuario"));

        //Opção cadastrar
        if($id_usu == 1 || $id_usu == 2 ){
            console.log("Usuário ADMINISTRADOR!");
            $("#titulo_sapoperador").css("cursor", "pointer");
            $("#titulo_sapoperador").attr("title", "Clique para Cadastrar Novo usuário");
            $("#titulo_sapoperador").click(function(){
                showModalLogin('ModalCadastrar');
            });
        }

        if($id_usu > 0){
            $.get('conexao_get_dados.php', { usuario: String($id_usu) }, 
                function(dados){
                    $dados_usu = JSON.parse(dados);
                    console.log('dados:', $dados_usu);
                    $("#nome_usuario").html($dados_usu['usuario']['nome']);
                    $("#funcao_usuario").html($dados_usu['usuario']['funcao']);
                    $("#imagem-usuario").attr("src", `img/usuarios/${$dados_usu['usuario']['id']}.jpg`);
                    const FUNCOES = $dados_usu['funcoes'].reduce((acc, item) => {
                        acc[item.nr_funcao] = item.funcao;
                        return acc;
                    }, {});
                    console.log('funcoes', FUNCOES);
                    $em_trabalho = [];
                    $em_reserva = [];
                    $em_erro = [];
                    Object.keys(SIGLAS_FUNCOES).forEach(sigla => {
                        popular_cartas(sigla, $dados_usu[sigla]);
                    });
                    if($em_trabalho.length > 1){
                        $em_reserva = $em_trabalho.slice(1).concat($em_reserva);
                        $em_trabalho = [$em_trabalho[0]];
                    }
                    console.log('em_trabalho', $em_trabalho);
                    console.log('em_erro', $em_erro);
                    console.log('em_reserva', $em_reserva);
                    popular_descricao_cartas($em_trabalho, "em_trabalho");
                    //popular_descricao_cartas($em_reserva, "em_reserva");
                    popular_descricao_cartas($em_erro, "em_erro");
                    mostrar_botao_controle(FUNCOES);
                }
            );
        }
        else showModalLogin('ModalLogin');
    }

    function showModalLogin(idmodal) {
        var myModal = new bootstrap.Modal(document.getElementById(idmodal), {
            keyboard: false,
            backdrop: 'static'
        });
        myModal.show();
    }

    const alertPlaceholder = document.getElementById('div-alertas')
        const AddAlert = (message, type) => {
            //success
        const wrapper = document.createElement('div')
        wrapper.innerHTML = [
            `<div class="alert alert-${type} alert-dismissible" role="alert">`,
            `   <div>${message}</div>`,
            '   <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>',
            '</div>'
        ].join('')
        alertPlaceholder.append(wrapper)
    }

    function uploadImage() {
        const fileInput = document.getElementById('fileInput');
        const file = fileInput.files[0];
        if (!file) {
            AddAlert(`Selecione uma imagem!`, 'warning');
            return;
        }
        const formData = new FormData();
        formData.append('avatar', file);
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'upload_img.php', true);
        xhr.onload = function () {
            if (xhr.status === 200) {
                AddAlert(`Imagem carregada com sucesso!`, 'success');
                setTimeout(function() { location.reload(); }, 2000);
            } else AddAlert(`Erro ao carregar imagem!`, 'danger');
        };
        xhr.send(formData);
    }

    get_dados();

    //EVENTOS

    $("#imagem-usuario").click(function() {
        $('#fileInput').click();
    });

    $("#fileInput").change(function(){
        uploadImage();
    });

    $("#login").click(function(){
        showModalLogin('ModalLogin');
    });

    $("#botao_pedir_carta").click(function(){
        $.post('conexao_pedir_carta.php', {
            usuario: $(this).attr("idusu"),
            funcao: $(this).attr("tipo")
            }, 
            function(resp){
                console.log(resp);
                $j = JSON.parse(resp)[0] != undefined ? JSON.parse(resp)[0] : {};
                if($j['id'] != undefined &&  parseInt($j['id']) > 0){
                    AddAlert(`Unidade de trabalho pedida com sucesso! ${$j['id']}`, 'success');
                    setTimeout(function() { location.reload(); }, 2000);
                }
                else if(resp == 1) AddAlert(`Não há unidade de trabalho disponível!`, 'warning');
                else AddAlert(`Erro ao pedir unidade de trabalho! ${String($j)}`, 'danger');
            }
        );
    });

    $("#botao_finalizar_carta").click(function(){
        $.post('conexao_finalizar_carta.php', {
            id: $(this).attr("miid"),
            funcao: $(this).attr("tipo")
            }, 
            function(resp){
                console.log(resp);
                $j = JSON.parse(resp)[0] != undefined ? JSON.parse(resp)[0] : {};
                if($j['id'] != undefined &&  parseInt($j['id']) > 0){
                    AddAlert(`Unidade de trabalho finalizada com sucesso! ${$j['id']}`, 'success');
                    setTimeout(function() { location.reload(); }, 2000);
                }
                else AddAlert(`Erro ao finalizar unidade de trabalho! ${String($j)}`, 'danger');
            }
        );
    });

});