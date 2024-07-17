<?php

require_once 'conexao.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idt = $_POST['idt'];
    $senha = base64_encode($_POST['senha']);
    //$senha = $_POST['senha'];
    
    try {
        $sql = "SELECT * FROM USUARIOS WHERE idtmil = '$idt' AND senha = '$senha';";
        $resp = get_dados_bd_query($sql);

        if (count($resp) > 0 && isset($resp[0]['id'])) {
            //var_dump($resp);
            // Senha verificada, iniciar sessão
            $_SESSION['id'] = $resp[0]['id'];
            echo "Login bem-sucedido!";
            header("Refresh: 1; URL=index.php");
        } else {
            // Senha ou ID incorretos
            echo "ID ou senha incorretos!";
            header("Refresh: 5; URL=index.php");
        }
    } catch (PDOException $e) {
        // Tratar erros de conexão
        echo "Erro de conexão: " . $e->getMessage();
        header("Refresh: 5; URL=index.php");
    }
}
?>