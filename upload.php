<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Définir les identifiants de connexion
$username = "admin";
$password = "86369ard;2099M123";

// Gérer la déconnexion
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: upload.php");
    exit();
}

// Gérer la connexion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    if ($_POST['username'] == $username && $_POST['password'] == $password) {
        $_SESSION['loggedin'] = true;
        header("Location: upload.php");
        exit();
    } else {
        $login_error = "Identifiants incorrects";
    }
}

// Si l'utilisateur n'est pas connecté, afficher le formulaire de connexion
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Login</title>
    </head>
    <body>
        <h1>Login</h1>
        <?php if (isset($login_error)) { echo "<p style='color:red;'>$login_error</p>"; } ?>
        <form action="upload.php" method="post">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username"><br><br>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password"><br><br>
            <input type="submit" name="login" value="Login">
        </form>
    </body>
    </html>
    <?php
    exit();
}

$bucketName = 'sylvain-ard-3f991328'; // Remplacez par le nom de votre bucket S3
$region = 'us-east-1'; // Remplacez par votre région AWS
require 'vendor/autoload.php';

$cloudFrontDomain = 'dxmwt6r36hyb4.cloudfront.net'; // Remplacez par votre domaine CloudFront

require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

$s3Client = new S3Client([
    'region' => $region,
    'version' => 'latest',
    'credentials' => [
        'key' => '[mettez ici votre clé aws]',
        'secret' => '[mettez ici votre clé secrète aws]',
    ],
]);
// Définir la taille maximale des fichiers (5 Mo)
$maxFileSize = 5 * 1024 * 1024; // 5 Mo

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $filePath = $file['tmp_name'];
    $fileName = $file['name'];
	$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if ($fileSize <= $maxFileSize && in_array($fileExtension, $allowedExtensions) && is_uploaded_file($filePath)) {
        try {
            $result = $s3Client->putObject([
                'Bucket' => $bucketName,
                'Key' => $fileName,
                'SourceFile' => $filePath
            ]);
            echo "File uploaded successfully. <a href='https://{$cloudFrontDomain}/{$fileName}'>View file</a>";
        } catch (AwsException $e) {
            echo "Error uploading file: " . $e->getMessage();
        }
    } else {
        echo "Invalid file upload.";
    }
}

// Récupérer la liste des objets dans le bucket
try {
    $objects = $s3Client->listObjectsV2([
        'Bucket' => $bucketName,
    ]);
} catch (AwsException $e) {
    echo "Error listing objects: " . $e->getMessage();
    $objects = null;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload to S3</title>
</head>
<body>
    <h1>Upload a file to S3</h1>
    <form action="upload.php" method="post" enctype="multipart/form-data">
        <input type="file" name="file">
        <input type="submit" value="Upload">
    </form>
    <a href="upload.php?logout=true">Logout</a>

    <?php if ($objects && isset($objects['Contents'])): ?>
        <h2>Uploaded Images</h2>
        <?php foreach ($objects['Contents'] as $object): ?>
            <div>
                <img src="https://<?= $cloudFrontDomain ?>/<?= $object['Key'] ?>" alt="<?= $object['Key'] ?>" style="max-width: 200px;">
                <p><?= $object['Key'] ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
