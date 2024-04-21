<?php

// chargement des bibliothèques de fonctions
require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();

if (!parametresControle('get', [], ['page'])) sessionExit();

$numPage = $_GET['page'] ?? 1;

affEntete("L'actu");

affContenuL($numPage);

affPiedDePage();

/**
 * Affichage de la page
 * @param int $numPage Le numéro de la page à afficher
 * @return void
 */
function affContenuL(int $numPage): void {
    $bd = bdConnect();
    $sql = 'SELECT COUNT(arID) FROM article';
    $result = bdSendRequest($bd, $sql);
    $arCount = 0;
    if (mysqli_num_rows($result) == 1) {
        $arCount = mysqli_fetch_assoc($result)['COUNT(arID)'];
        mysqli_free_result($result);
    }

    echo '<main>';
    affPaginationL($arCount);

    $sql = 'SELECT arID, arTitre, arDatePubli, arDateModif, arResume FROM article '.
        'ORDER BY arDatePubli DESC '.
        'LIMIT 4 OFFSET '.(($numPage - 1) * 4);
    $result = bdSendRequest($bd, $sql);

    $prevMonth = null;
    $prevYear = null;

    while ($tab = mysqli_fetch_assoc($result)) {
        $date = $tab['arDateModif'] != null ? $tab['arDateModif'] : $tab['arDatePubli'];
        $month = mb_substr($date, 4, 2);
        $year = mb_substr($date, 0, 4);
        if ($month != $prevMonth || $year != $prevYear) {
            if ($month != null) echo '</section>';
            echo
                '<section>',
                    '<h2>', getArrayMonths()[$month - 1], ' ', $year, '</h2>';
            $prevMonth = $month;
            $prevYear = $year;
        }
        echo '<article class="resume">';
        if (is_file('../upload/'.$tab['arID'].'.jpg')) {
            echo '<img src="../upload/', $tab['arID'], '.jpg" alt="Image d\'illustration">';
        }
        else echo '<img src="../images/none.jpg" alt="Pas d\'image disponible">';
        echo '<h3>', htmlProtegerSorties($tab['arTitre']), '</h3>';
        echo '<p>', htmlProtegerSorties($tab['arResume']), '</p>';
        echo '<footer><a href="article.php?id=', $tab['arID'], '">Lire l\'article</a></footer></article>';
    }

    if ($prevMonth != null) echo '</section>';

    mysqli_free_result($result);

    mysqli_close($bd);

    echo '</main>';
}

/**
 * Affiche le menu de pagination
 * @param int $arCount Le nombre d'articles présent dans la base de données
 * @return void
 */
function affPaginationL(int $arCount): void {
    echo
            '<section id="pagination">',
                '<table>',
                    '<tr>',
                        '<td>Pages : </td>';
    for ($i = 1; $i <= ($arCount / 4) + 1; $i++) {
        echo
            '<td>',
                '<form method="get" action="actus.php">',
                    '<input type="submit" name="page" value="', $i, '">',
                '</form>',
            '</td>';
    }
    echo
                    '</tr>',
                '</table>',
            '</section>';
}
