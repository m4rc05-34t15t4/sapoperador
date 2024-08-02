<?php

    $host = '10.46.136.21'; // endereço do servidor PostgreSQL
    $port = '5432'; // porta do servidor PostgreSQL
    $dbname = 'combater_2024_2'; // nome do banco de dados
    $user = 'postgres'; // usuário do banco de dados
    $password = 'adminsap'; // senha do banco de dados
    
    try {
        $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$dbname;user=$user;password=$password");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Erro ao conectar ao banco de dados: " . $e->getMessage());
    }

    function get_dados_bd_query($sql){
        global $pdo;
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
?>
