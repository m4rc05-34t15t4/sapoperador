<?php
    if (isset($_GET['senha'])) {
        $senha = base64_encode($_GET['senha']);
        echo "SENHA: " . $senha;
    }
    else header("Refresh: 1; URL=php/gerar_senha.php?senha=");
?>