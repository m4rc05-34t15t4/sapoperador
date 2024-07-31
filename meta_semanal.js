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

                        $("#div-content-usuarios").append(`
                            <div id="usuario_${usu['id']}" class="dados-meta-usuario m-3 d-flex">
                                <img src="img/usuarios/${usu['id']}.jpg" alt="" class="img-fluid img-center mb-2" />
                                <div class="dados-metas">
                                    <h2 id="nome_usuario" class="ms-2 text-black">${usu['post_grad']} ${usu['nome']}</h2>
                                    <div class="d-flex flex-rown justify-content-between align-items-center">
                                        <h3 id="funcao_usuario" class="ms-4 text-primary">${usu['funcao']}</h3>
                                        <b class="qtd_meta fs-4">${$meta_usu["qtd_semanal"]["total"]}/0</b>
                                    </div>
                                    ${criar_barra(usu['id'])}
                                </div>
                            </div>`
                        );
                        criar_barra_meta(`#usuario_${usu['id']}`, $meta_usu);
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