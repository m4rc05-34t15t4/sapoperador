<?php 
  header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
  header("Cache-Control: no-cache");
  header("Pragma: no-cache"); 
  header("Refresh:1800");//recarrega em 30min
  if ( session_status() !== PHP_SESSION_ACTIVE ) session_start();
  $id_sessao = "0";
  if( isset($_SESSION['SAPO']['id']) ) $id_sessao = $_SESSION['SAPO']['id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SAPOperador</title>
  <link rel="stylesheet" type="text/css" href="../css/bootstrap.min.css"/>
  <link rel="stylesheet" type="text/css" href="<?php echo "../css/style.css?".time(); ?>"/>
</head>
<body>
  <div class="container-custom">
    <div id="cabecalho" class="d-flex row flex-wrap container-fluid bg-dark text-white justify-content-between mb-3">
      <div class="fs-5 d-flex flex-rown justify-content-end">
        <h3 id="nome_adm" class="mt-4 mx-2" title="Administrador"></h3>
        <h1 id="titulo_sapoperador" class="m-0 p-3 w-auto text-secondary">FORPRON 2024 - SAPOperador</h1>
        <div id="links-cabecalho" class="ms-5 d-flex flex-rown justify-content-end">
          <a id="metasemanal" class="mx-3 pt-4" title="Meta Semanal Geral" href="meta.php">Meta</a>
          <?php echo ($id_sessao != "0") ? '<a id="logout" class="mx-3 pt-4" title="Finalizar Acesso" href="logout.php">Sair</a>' : '<a id="login" class="mx-3 pt-4" href="">Login</a>'; ?>
        </div>
      </div>
    </div>
    <div class="content">
      <div id="menu_head" class="d-flex flex-rown w-100 justify-content-between align-items-start">
        
        <div id="menu-left" class="d-flex flex-rown">
          <img id="imagem-usuario" src="../img/usuarios/<?php echo $id_sessao.'.jpg?'.time(); ?>" alt="" class="img-fluid img-center mb-4" />
          <input type="file" id="fileInput">
          <div id="div-controle-usuario" id_adm="0" id_usuario="<?php echo $id_sessao; ?>">
            <h1 id="nome_usuario" class="ms-2 text-white">Nome do Usuário</h1>
            <h2 id="funcao_usuario" class="ms-4 text-primary">Função do Usuário</h2>
            <button id="botao_finalizar_carta" type="button" class="botao-controle px-3 py-4 ms-3 btn btn-danger mb-2 fs-4 shadow" miid="" tipo="">Finalizar 1134-1 (21) - HID</button>
            <button id="botao_pedir_carta" type="button" class="botao-controle px-3 py-4 ms-3 btn btn-primary mb-2 fs-4 shadow" tipo="">Pedir</button>
          </div>
        </div>
        
        <div id="menu-right" class="w-100 d-flex flex-rown flex-wrap justify-content-between align-items-center mt-5 pt-5">

          <div id="barra-progresso-div" class="mt-3 d-flex flex-column">
            <div class="w-100 text-center fs-3">Meta Semanal: <b class="qtd_meta fs-4 text-end"></b></div>
          </div>

          <div id="descricao_em_reserva" class="descricao_carta mx-3 text-center">
            <h3 title="reservadas pelo ADM">Reservada:</h3>
            <div id="cartas_em_reserva" class="fs-4 d-flex flex-column flex-wrap w-100 justify-content-center align-items-center"></div>
          </div>
        
        </div>
      
      </div>
      <div id="lista_content" class="h-100 row row-custom px-5">
        
        <div id="div_hid" class="col col-custom">
          <h3 id="titulo_hid">Hidrogarfia<span class="qtd_trabalho"></span></h3>
          <ul id="lista_hid" class="list-group"></ul>
        </div>

        <div id="div_tra" class="col col-custom">
          <h3 id="titulo_tra">Transporte<span class="qtd_trabalho"></span></h3>
          <ul id="lista_tra" class="list-group"></ul>
        </div>

        <div id="div_int" class="col col-custom">
          <h3 id="titulo_int">Interseções<span class="qtd_trabalho"></span></h3>
          <ul id="lista_int" class="list-group"></ul>
        </div>

        <div id="div_veg" class="col col-custom">
          <h3 id="titulo_veg">Vegetação<span class="qtd_trabalho"></span></h3>
          <ul id="lista_veg" class="list-group"></ul>
        </div>

        <div id="div_rec" class="col col-custom">
          <h3 id="titulo_rec">Reclassificação<span class="qtd_trabalho"></span></h3>
          <ul id="lista_rec" class="list-group"></ul>
        </div>

      </div>
    </div>
  </div>

  <div id="div-alertas"></div>

  <!-- Modal -->
  <div class="modal fade" id="ModalLogin" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-3 w-100 text-center" id="exampleModalLabel">Login - SAPOperador</h1>
                <!--<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>-->
            </div>
            <div class="modal-body">
              <form action="conexao_login.php" method="POST">
                  <div class="mb-3">
                      <label for="inputID" class="form-label">Identidade Militar</label>
                      <input type="text" class="form-control" id="inputID" name="idt" placeholder="Digite seu ID" required>
                  </div>
                  <div class="mb-3">
                      <label for="inputPassword" class="form-label">Senha</label>
                      <input type="password" class="form-control" id="inputPassword" name="senha" placeholder="Digite sua senha" required>
                  </div>
                  <button type="submit" class="btn btn-primary w-100 fs-3">Entrar</button>
              </form>
            </div>
        </div>
    </div>
  </div>

  <div class="modal fade" id="ModalCadastrar" tabindex="-1" aria-labelledby="cadastrarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-3" id="cadastrarModalLabel">Cadastrar - SAPOperador</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form action="conexao_cadastrar.php" method="POST">
                <label for="cadastrar" class="form-label">Nome:</label>
                <input type="text" id="nome" name="nome" class="form-control mb-2" required>

                <label for="cadastrar" class="form-label">Função:</label>
                <select id="funcao" name="funcao" class="form-select mb-2" required>
                    <option value="1">Hidrografia</option>
                    <option value="2">Rodovias</option>
                    <option value="4">Interseções</option>
                    <option value="8">Vegetação</option>
                    <option value="16">Reclassificação</option>
                    <option value="32">Desenvolvedor</option>
                    <option value="64">Administrador</option>
                </select>

                <label for="cadastrar" class="form-label">Posto / Graduação:</label>
                <select id="post_grad" name="post_grad"  class="form-select mb-2" required>
                    <option value="Gen">Gen</option>
                    <option value="Cel">Cel</option>
                    <option value="TCel">TCel</option>
                    <option value="Maj">Maj</option>
                    <option value="Cap">Cap</option>
                    <option value="1º Ten">1º Ten</option>
                    <option value="2º Ten">2º Ten</option>
                    <option value="ST">ST</option>
                    <option value="1º Sgt">1º Sgt</option>
                    <option value="2º Sgt">2º Sgt</option>
                    <option value="3º Sgt">3º Sgt</option>
                    <option value="Cb">Cb</option>
                    <option value="Sd">Sd</option>
                    <option value="SC">SC</option>
                </select>

                <label for="cadastrar" class="form-label">Identidade militar:</label>
                <input type="text" id="idtmil" name="idtmil" class="form-control mb-2" required>

                <label for="cadastrar" class="form-label">Senha:</label>
                <input type="password" id="senha" name="senha" class="form-control mb-2" required>

                <label for="cadastrar" class="form-label">Repetir Senha:</label>
                <input type="password" id="repetir_senha" name="repetir_senha" class="form-control mb-2" required>

                <button type="submit" class="btn btn-primary w-100 mt-3 fs-3">Cadastrar</button>
              </form>
            </div>
        </div>
    </div>
  </div>

  <script src="../js/popper.min.js"></script>
  <script src="../js/bootstrap.min.js"></script>
  <script src="../js/jquery-3.5.0.js"></script>
  <script src="<?php echo "../js/funcoes.js?".time(); ?>"></script>
  <script src="<?php echo "../js/script.js?".time(); ?>"></script>
</body>
</html>
