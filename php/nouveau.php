<?php

// chargement des bibliothèques de fonctions
require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();


if (!parametresControle('post', [],
    ['title', 'resume', 'content', 'btnEditArticle', 'file', 'MAX_FILE_SIZE'])) sessionExit();
if (!estAuthentifie() || !$_SESSION['redacteur']) sessionExit();

$title = $_POST['title'] ?? '';
$resume = $_POST['resume'] ?? '';
$content = $_POST['content'] ?? '';

$err = isset($_POST['btnEditArticle']) ? creerArticle($title, $resume, $content) : false;

affEntete('Nouvel article');

affEditionArticle('Nouvel article', 'nouveau.php', 'Publier', $err, $title, $resume, $content);

affPiedDePage();

/**
 * Récupère les données de l'article dans $_POST et crée l'article si il est valide
 * @return array Le tableau contenant les erreurs présentes dans l'article, ou un tableau vide sinon
 */
function creerArticle(string $title, string $resume, string $content): array {
    $err = [];
    verifierArticle($err, $title, $resume, $content);
    if (sizeof($err) != 0) return $err;

    $bd = bdConnect();
    // Getting next article id
    $sql = 'SELECT MAX(arID) FROM article';
    $result = bdSendRequest($bd, $sql);
    if (mysqli_num_rows($result) == 0) {
        $err[] = 'La base de donnée est injoignable pour le moment';
        return $err;
    }
    $tab = mysqli_fetch_assoc($result);
    $id = $tab['MAX(arID)'] + 1;

    // Move the file
    if (!@move_uploaded_file($_FILES['image']['tmp_name'], '../upload/'.$id.'.jpg')) {
        $err[] = 'Impossible de déplacer le fichier';
        return $err;
    }

    // Inserting article
    $sql = 'INSERT INTO article VALUES ('.$id.', "'
        .mysqli_real_escape_string($bd, $title).'", "'
        .mysqli_real_escape_string($bd, $resume).'", "'
        .mysqli_real_escape_string($bd, $content).'", '
        .date('YmdHi').', NULL, "'
        .mysqli_real_escape_string($bd, $_SESSION['pseudo']).'")';

    bdSendRequest($bd, $sql);

    return $err;
}
