<?php

require_once 'conexao.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $funcao = $_POST['funcao'];
    $post_grad = $_POST['post_grad'];
    $idtmil = $_POST['idtmil'];
    $senha = base64_encode($_POST['senha']);
    $repetir_senha = base64_encode($_POST['repetir_senha']);

    try {
        if($senha != $repetir_senha){
            echo "Senhas diferentes";
            header("Refresh: 5; URL=index.php");
        }
        else{
            $sql = "INSERT INTO USUARIOS (nome, funcao, post_grad, idtmil, senha) VALUES ('$nome', $funcao, '$post_grad', '$idtmil', '$senha') RETURNING *;";
            $resp = get_dados_bd_query($sql);
            if (count($resp) > 0 && isset($resp[0]['id'])) {
                //var_dump($resp);
                echo "Usuário ".$resp[0]['nome']." Cadastrado com sucesso!";
                header("Refresh: 3; URL=index.php");
            } else {
                // Senha ou ID incorretos
                echo "Erro ao cadastrar usuário!";
                header("Refresh: 5; URL=index.php");
            }
        }
    } catch (PDOException $e) {
        // Tratar erros de conexão
        echo "Erro de conexão: " . $e->getMessage();
        header("Refresh: 5; URL=index.php");
    }
}
?>