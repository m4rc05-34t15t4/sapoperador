$(document).ready(function(){

    const SIGLAS_FUNCOES = { 'hid' : 1, 'tra' : 2, 'int' : 4, 'veg' : 8, 'rec' : 16 };

    function get_dados(){
        $id_usu = $("#titulo_sapoperador").attr("id_usuario");
        $.get('conexao_get_dados_semanais.php', { usuario: String($id_usu) }, 
            function(dados){
                //console.log(dados);
                $dados_usu = JSON.parse(dados);
                console.log('dados_semanais:', $dados_usu);

                $dados_usu['usuario'].forEach(usu => {
                    $("#div-content-usuarios").append(`
                        <div id="usuario_${usu['id']}" class="dados-meta-usuario m-3 d-flex">
                            <img src="img/usuarios/${usu['id']}.jpg" alt="" class="img-fluid img-center mb-2" />
                            <div>
                                <h2 id="nome_usuario" class="ms-2 text-black">${usu['nome']}</h2>
                                <h3 id="funcao_usuario" class="ms-4 text-primary">${usu['funcao']}</h3>
                            </div>
                        </div>`);
                });


                $("#div-content-usuarios").html();


                
            }
        );
    }

    get_dados();

    //EVENTOS

});