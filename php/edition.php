<?php

// chargement des bibliothèques de fonctions
require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();

if (!parametresControle('post', [],
    ['title', 'resume', 'content', 'btnEditArticle', 'file', 'MAX_FILE_SIZE', 'btnSupprArticle'])) sessionExit();
if (!parametresControle('get', ['id'], ['suppr'])) sessionExit();
if (!estAuthentifie() || !$_SESSION['redacteur']) sessionExit();

$_GET['id'] = dechiffrerURL($_GET['id']);
if ($_GET['id'] === false) sessionExit();

if (isset($_GET['suppr'])) {
    $_GET['suppr'] = dechiffrerURL($_GET['suppr']);
    if ($_GET['suppr'] === false) sessionExit();

    if ($_GET['suppr'] == 1) supprimerArticle();
}

$title = $_POST['title'] ?? '';
$resume = $_POST['resume'] ?? '';
$content = $_POST['content'] ?? '';

// Récupération des informations de l'article, si la page est chargée pour la première fois (en cas d'erreur
// les informations modifiées sont réaffichées)
$bd = false;
if (!isset($_POST['btnEditArticle'])) {
    $bd = bdConnect();
    $sql = 'SELECT arTitre, arResume, arTexte FROM article WHERE arID='.$_GET['id'];
    $resultat = bdSendRequest($bd, $sql);
    if (mysqli_num_rows($resultat) != 0) {
        $tab = mysqli_fetch_assoc($resultat);
        $title = $tab['arTitre'];
        $resume = $tab['arResume'];
        $content = $tab['arTexte'];
    }
    mysqli_free_result($resultat);
}

$err = isset($_POST['btnEditArticle']) ? editerArticle($bd, $title, $resume, $content) : false;

if ($bd !== false) mysqli_close($bd);

affEntete('Modifier l\'article');

affEditionArticle(
    'Modifier l\'article', 'edition.php?id='.chiffrerURL($_GET['id']), 'Modifier', $err,
    true, 'L\'article a bien été modifié',
    $title, $resume, $content
);

affPiedDePage();

/**
 * Applique les modifications faites à l'article, si l'article est valide
 * @param mysqli|false $bd La base de donnée, si déjà connectée
 * @param string $title Le titre de l'article
 * @param string $resume Le résumé de l'article
 * @param string $content Le contenu de l'article
 * @return array Un tableau contenant les éventuelles erreurs
 */
function editerArticle(mysqli|false $bd, string $title, string $resume, string $content): array {
    $err = [];
    verifierArticle($err, $title, $resume, $content);
    traitementImage($err);
    if (sizeof($err) != 0) return $err;

    // Ouverture de la base de données si elle n'est pas déjà ouverte
    $bd = $bd !== false ? $bd : bdConnect();

    // Déplacement du fichier
    if (
        isset($_FILES['image'])
        && strlen($_FILES['image']['name']) > 0
        && !@move_uploaded_file($_FILES['image']['tmp_name'], '../upload/'.$_GET['id'].'.jpg')
    ) {
        $err[] = 'Impossible de déplacer le fichier';
        return $err;
    }

    // Modification de l'article
    $sql = 'UPDATE article SET arTitre="'
        .mysqli_real_escape_string($bd, $title).'", arResume="'
        .mysqli_real_escape_string($bd, $resume).'", arTexte="'
        .mysqli_real_escape_string($bd, $content).'", arDateModif='
        .date('YmdHi').' '.
        'WHERE arID='.$_GET['id'];

    bdSendRequest($bd, $sql);

    return $err;
}

/**
 * Envoie la requête à la base de donnée afin de supprimer l'article
 * @return void
 */
function supprimerArticle(): void {
    $bd = bdConnect();
    $sql = 'DELETE FROM article WHERE arID='.$_GET['id'];
    bdSendRequest($bd, $sql);
    mysqli_close($bd);
    unlink('../upload/'.$_GET['id'].'.jpg');
    header('Location: ../index.php');
}
