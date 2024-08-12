$(document).ready(function(){

    function get_dados(){
        $id_usu = $("#titulo_sapoperador").attr("id_usuario");
        $.get('conexao_get_dados_semanais.php', { usuario: String($id_usu) }, 
            function(dados){
                //console.log(dados);
                $dados_usu = JSON.parse(dados);
                console.log('dados_semanais:', $dados_usu);
                if($dados_usu['error']) alert($dados_usu['error']);
                else{
                    $dados_usu['usuario'].forEach(usu => {
                        $meta_usu = Verificar_meta_usuario($dados_usu['metas'], usu);
                        $invisivel = verifica_permissao_mostrar_controle(usu['nr_funcao']) ? "" : " invisible";
                        $qtd_semanal_total = 0;
                        $qtd_semanal_total += usu['qtd_hid'] != null ? usu['qtd_hid'] : 0;
                        $qtd_semanal_total += usu['qtd_tra'] != null ? usu['qtd_tra'] : 0;
                        $qtd_semanal_total += usu['qtd_int'] != null ? usu['qtd_int'] : 0;
                        $qtd_semanal_total += usu['qtd_veg'] != null ? usu['qtd_veg'] : 0;
                        $qtd_semanal_total += usu['qtd_rec'] != null ? usu['qtd_rec'] : 0;
                        $adm = $CONF['administrador'].indexOf(usu['id']) >= 0 ? ' <img src="../img/gear-fill.svg" class="ms-1 img-simbolo-adm" title="Administrador"/>' : '';
                        $("#div-content-usuarios").append(`
                            <div id="usuario_${usu['id']}" class="dados-meta-usuario m-3 d-flex">
                                <a href="perfil.php?usuario=${usu['id']}" ><img src="../img/usuarios/${usu['id']}.jpg?${Date.now()}" alt="" class="dados-meta-usuario-img img-fluid img-center mb-2" /></a>
                                <div class="dados-metas">
                                    <h2 id="nome_usuario" class="ms-2 text-black">${usu['post_grad']} ${usu['nome']}${$adm}</h2>
                                    <div class="d-flex flex-rown justify-content-between align-items-center">
                                        <h3 id="funcao_usuario" class="ms-4 text-primary">${usu['funcao']}</h3>
                                        <b class="qtd_meta fs-4${$invisivel}">${$meta_usu != null ? $meta_usu["qtd_semanal"]["total"] : $qtd_semanal_total}/0</b>
                                    </div>
                                    ${verifica_permissao_mostrar_controle(usu['nr_funcao']) ? criar_barra(usu['id']) : ''}
                                </div>
                            </div>`
                        );
                        if($meta_usu != null) criar_barra_meta(`#usuario_${usu['id']}`, $meta_usu);
                    });
                }
            }
        );
    }

    async function main() {
        $CONF = await loadJSON();
        if ($CONF) {

            get_dados();

        }
    }
    main();

    //EVENTOS

});