<?php

// chargement des bibliothèques de fonctions
require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();

if (!parametresControle('post', [], ['btnSearch', 'search'])) sessionExit();

affEntete("Recherche");

affContenuL();

affPiedDePage();

/**
 * Affichage de la page
 * @return void
 */
function affContenuL(): void {
    echo '<main>';

    $args = isset($_POST['search']) ? splitSearchArgs($_POST['search']) : [];

    affMenuRecherche($args);

    searchAffArticles($args);

    echo '</main>';
}

/**
 * Sépare les arguments de la recherche
 * @param string $args Le string de la recherche
 * @return array La list des arguments valides
 */
function splitSearchArgs(string $args): array {
    $split = preg_split('/ /', $args, 0, PREG_SPLIT_NO_EMPTY);
    $result = [];
    foreach ($split as $string) if (strlen(trim($string)) >= 3) $result[] = trim($string);
    return $result;
}

/**
 * Affiche le menu de recherche d'article
 * @param array $valid La liste d'arguments valides de recherche
 * @return void
 */
function affMenuRecherche(array $valid): void {
    echo
        '<section id="recherche">',
            '<h2>Rechercher des articles</h2>',
            '<p>Les critères de recherche doivent faire au moins 3 caractères pour être pris en compte.</p>';

    if (sizeof($valid) == 0 && isset($_POST['btnSearch'])) {
        echo '<div class="erreur">Le ou les critères de recherche ne sont pas valides.</div>';
    }
    else if (sizeof($valid) != 0) {
        $text = '<p class="centre">Critères de recherche utilisés : "';
        for ($i = 0; $i < sizeof($valid); $i++) {
            $text .= $valid[$i];
            if ($i < sizeof($valid) - 1) $text .= ' ';
        }
        echo $text, '".</p>';
    }

    echo
            '<form method="post" action="recherche.php" id="recherche" class="centre">',
                '<input type="text" name="search" value="', $_POST['search'] ?? '', '">',
                '<input type="submit" name="btnSearch" value="Rechercher">',
            '</form>',
        '</section>';
}

/**
 * Recherche et affiche les articles
 * @param array $args La liste des arguments de la recherche
 * @return void
 */
function searchAffArticles(array $args): void {
    if (sizeof($args) == 0) return;

    $bd = bdConnect();
    $sql = 'SELECT arID, arTitre, arDatePubli, arDateModif, arResume FROM article '.
        'WHERE';
    for ($i = 0; $i < sizeof($args); $i++) {
        $str = mysqli_escape_string($bd, $args[$i]);
        $sql .= $i != 0 ? ' AND' : '';
        $sql .= ' (arTitre LIKE \'%'.$str.'%\' OR arResume LIKE \'%'.$str.'%\')';
    }
    $sql .= ' ORDER BY arDatePubli DESC';
    $result = bdSendRequest($bd, $sql);

    $articles = [];
    while ($tab = mysqli_fetch_assoc($result)) $articles[] = $tab;
    mysqli_free_result($result);

    if (sizeof($articles) != 0) affArticles($articles);
    else {
        echo
            '<section>',
                '<h2>Résultats</h2>',
                '<p>Aucun article ne correspond à vos critères de recherche.</p>',
            '</section>';
    }

    mysqli_close($bd);
}

