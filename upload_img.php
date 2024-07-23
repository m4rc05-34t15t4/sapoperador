<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['SAPO']['id'])) {
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = 'img/usuarios/';
        //$fileInfo = pathinfo($_FILES['avatar']['name']);
        //$extension = $fileInfo['extension'];
        $newFileName = $_SESSION['SAPO']['id'].'.jpg';
        $uploadFile = $uploadDir . $newFileName;
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadFile)) {
            echo 'File is successfully uploaded.';
        } else {
            echo 'File upload failed.';
        }
    } else {
        echo 'No file uploaded or there was an upload error.';
    }
} else {
    echo 'Invalid request method.';
}
?>
