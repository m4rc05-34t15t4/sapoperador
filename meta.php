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
  <title>SAPO - Metas</title>
  <link rel="stylesheet" type="text/css" href="bootstrap.min.css"/>
  <link rel="stylesheet" type="text/css" href="<?php echo "style.css?".time(); ?>"/>
</head>
<body>
  <div class="container-custom">
    
    <div id="cabecalho" class="d-flex row flex-wrap container-fluid bg-dark text-white justify-content-between mb-3">
      <div class="fs-5 d-flex flex-rown justify-content-center">
        <a href="index.php"><h1 id="titulo_sapoperador" class="m-0 p-3 w-auto text-secondary" id_usuario="<?php echo $id_sessao; ?>">FORPRON 2024 - SAPO | Meta Semanal</h1></a>
      </div>
    </div>
    
    <div class="content">

      <div id="div-content-usuarios" class="d-flex flex-rown flex-wrap justify-content-center align-items-center">

        <!--<div id="usuario_1" class="dados-meta-usuario m-3 d-flex">
          <img src="<?php echo $id_sessao; ?>.jpg" alt="" class="img-fluid img-center mb-2" />
          <div id_usuario="<?php echo $id_sessao; ?>">
            <h2 id="nome_usuario" class="ms-2 text-black">Nome do Usuário</h2>
            <h3 id="funcao_usuario" class="ms-4 text-primary">Função do Usuário</h3>
          </div>
        </div>-->

      </div>
      
    </div>
  </div>

  <div id="div-alertas"></div>

  <script src="popper.min.js"></script>
  <script src="bootstrap.min.js"></script>
  <script src="jquery-3.5.0.js"></script>
  <script src="<?php echo "funcoes.js?".time(); ?>"></script>
  <script src="<?php echo "meta_semanal.js?".time(); ?>"></script>
</body>
</html>
