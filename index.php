<?php 
  session_start();
  $id_sessao = "0";
  if( isset($_SESSION['id']) ) $id_sessao = $_SESSION['id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SAPOperador</title>
  <link rel="stylesheet" type="text/css" href="bootstrap.min.css"/>
  <link rel="stylesheet" type="text/css" href="<?php echo "style.css?".time(); ?>"/>
</head>
<body>
  <div class="container-custom">
    <div id="cabecalho" class="d-flex row flex-wrap container-fluid bg-dark text-white justify-content-between mb-3">
      <div class="fs-5 d-flex flex-rown justify-content-end">
        <h1 id="titulo_sapoperador" class="m-0 p-2 w-auto text-secondary">SAPOperador</h1>
        <?php echo ($id_sessao != "0") ? '<a id="logout" class="mx-3 pt-4" href="logout.php">Sair</a>' : '<a id="login" class="mx-3 pt-4" href="">Login</a>'; ?>
      </div>
    </div>
    <div class="content">
      <div id="menu_head" class="d-flex flex-rown w-100 justify-content-around align-items-start">
        
        <div id="menu-left" class="d-flex flex-rown">
          <img id="imagem-usuario" src="user-image.jpg" alt="" class="img-fluid img-center mb-4" />
          <div id="div-controle-usuario" id_usuario="<?php echo $id_sessao; ?>">
            <h1 id="nome_usuario" class="ms-2 text-white">Nome do Usuário</h1>
            <h2 id="funcao_usuario" class="ms-4 text-primary">Função do Usuário</h2>
            <button id="botao_finalizar_carta" type="button" class="botao-controle px-3 py-4 ms-3 btn btn-danger mb-2 fs-4 shadow" miid="" tipo="">Finalizar 1134-1 (21) - HID</button>
            <button id="botao_pedir_carta" type="button" class="botao-controle px-3 py-4 ms-3 btn btn-primary mb-2 fs-4 shadow" tipo="">Pedir</button>
          </div>
        </div>
        
        <div id="menu-right" class="d-flex flex-rown flex-wrap">
          <div id="descricao_em_reserva" class="descricao_carta mx-3 text-center">
            <h3 title="reservadas pelo ADM">Reservada:</h3>
            <div id="cartas_em_reserva" class="fs-4 d-flex flex-column flex-wrap w-100 justify-content-center align-items-center"></div>
          </div>
          
          <div id="descricao_em_erro" class="descricao_carta mx-3 text-center">
            <h3 title="Erro no Banco Fale com o ADM">Erros:</h3>
            <div id="cartas_em_erro" class="fs-4 d-flex flex-column flex-wrap w-100 justify-content-center align-items-center"></div>
          </div>
        </div>
      
      </div>
      <div id="lista_content" class="h-100 row row-custom px-5">
        
        <div id="div_hid" class="col col-custom">
          <h3 id="titulo_hid">Hidrogarfia<span class="qtd_trabalho"></span></h3>
          <ul id="lista_hid" class="list-group"></ul>
        </div>

        <div id="div_tra" class="col col-custom">
          <h3 id="titulo_tra">Rodovias<span class="qtd_trabalho"></span></h3>
          <ul class="list-group"></ul>
        </div>

        <div id="div_int" class="col col-custom">
          <h3 id="titulo_int">Interseções<span class="qtd_trabalho"></span></h3>
          <ul class="list-group"></ul>
        </div>

        <div id="div_veg" class="col col-custom">
          <h3 id="titulo_veg">Vegetação<span class="qtd_trabalho"></span></h3>
          <ul class="list-group"></ul>
        </div>

        <div id="div_rec" class="col col-custom">
          <h3 id="titulo_rec">Reclassificação<span class="qtd_trabalho"></span></h3>
          <ul class="list-group"></ul>
        </div>

      </div>
    </div>
  </div>

  <div id="div-alertas"></div>

  <!-- Modal -->
  <div class="modal fade" id="ModalLogin" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="exampleModalLabel">Login</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                  <button type="submit" class="btn btn-primary w-100">Entrar</button>
              </form>
            </div>
        </div>
    </div>
  </div>

  <div class="modal fade" id="ModalCadastrar" tabindex="-1" aria-labelledby="cadastrarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="cadastrarModalLabel">Cadastrar</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form action="conexao_cadastrar.php" method="POST">
                <label for="cadastrar" class="form-label">Nome:</label>
                <input type="text" id="nome" name="nome" class="form-control mb-2" required>

                <label for="cadastrar" class="form-label">Função:</label>
                <select id="funcao" name="funcao" class="form-select mb-2" required>
                    <option value="1">Opção 1</option>
                    <option value="2">Opção 2</option>
                    <option value="4">Opção 3</option>
                    <option value="8">Opção 4</option>
                    <option value="16">Opção 4</option>
                </select>

                <label for="cadastrar" class="form-label">Posto / Graduação:</label>
                <select id="post_grad" name="post_grad"  class="form-select mb-2" required>
                    <option value="grad1">Graduação 1</option>
                    <option value="grad2">Graduação 2</option>
                    <option value="grad3">Graduação 3</option>
                    <option value="grad4">Graduação 4</option>
                </select>

                <label for="cadastrar" class="form-label">Identidade militar:</label>
                <input type="text" id="idtmil" name="idtmil" class="form-control mb-2" required>

                <label for="cadastrar" class="form-label">Senha:</label>
                <input type="password" id="senha" name="senha" class="form-control mb-2" required>

                <button type="submit" class="btn btn-primary w-100 mt-3 fs-3">Cadastrar</button>
              </form>
            </div>
        </div>
    </div>
  </div>

  <script src="popper.min.js"></script>
  <script src="bootstrap.min.js"></script>
  <script src="jquery-3.5.0.js"></script>
  <script src="<?php echo "script.js?".time(); ?>"></script>
</body>
</html>
