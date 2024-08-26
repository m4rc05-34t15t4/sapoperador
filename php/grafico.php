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
  <title>SAPO - Gráfico</title>
  <link rel="stylesheet" type="text/css" href="../css/bootstrap.min.css"/>
  <link rel="stylesheet" type="text/css" href="<?php echo "../css/style.css?".time(); ?>"/>
</head>
<body>
  <div class="container-custom">
    
    <div id="cabecalho" class="d-flex row flex-wrap container-fluid bg-dark text-white justify-content-between mb-3">
      <div class="fs-5 d-flex flex-rown justify-content-center">
        <h1 id="titulo_sapoperador" class="m-0 p-3 w-auto text-secondary" id_usuario="<?php echo $id_sessao; ?>">FORPRON 2024 - SAPO | Gráfico</h1>
        <div id="links-cabecalho" class="ms-5 d-flex flex-rown justify-content-end">
          <a href="index.php" class="mx-3 pt-4" title="Voltar">Home</a>
        </div>
      </div>
    </div>
    
    <div class="content">

      <div id="div-content-graficos" class="w-100 d-flex flex-rown flex-wrap justify-content-center align-items-center">
        <h3>Gráfico - Quantidade Finalizada Semanal por Fases</h3>
        <canvas id="grafico_total_semana_tipo" class="p-3"></canvas>

      </div>
      
    </div>
  </div>

  <div id="div-alertas"></div>

  <script src="../js/popper.min.js"></script>
  <script src="../js/bootstrap.min.js"></script>
  <script src="../js/jquery-3.5.0.js"></script>
  <script src="../js/chart.js"></script>
  <script src="<?php echo "../js/funcoes.js?".time(); ?>"></script>
  <script src="<?php echo "../js/graficos.js?".time(); ?>"></script>
</body>
</html>
