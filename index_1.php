<?php

// chargement des bibliothèques de fonctions
require_once('./php/bibli_gazette.php');
require_once('./php/bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();

affEntete('Le site de désinformation n°1 des étudiants en Licence Info', '.');

// génération du contenu de la page
affContenuL();

affPiedDePage();

// envoi du buffer
ob_end_flush();


/*********************************************************
 *
 * Définitions des fonctions locales de la page
 *
 *********************************************************/
//_______________________________________________________________
/**
 * Affichage du contenu principal de la page
 *
 * @return  void
 */
function affContenuL() : void {
    echo '<main>';

    // génération des 3 derniers articles
    $tab0 = [
                10 => 'Un mouchard dans un corrigé de Langages du Web',
                9 => 'Votez pour l\'hymne de la Licence',
                8 => 'L\'amphi Sciences Naturelles bientôt renommé amphi Mélenchon'
            ];
    affBlocTroisArticlesL('&Agrave; la Une', $tab0);

    // génération des 3 articles les plus commentés

    // ATTENTION : pour que la page soit valide, dans le titre de l'article 2, remplacement des " par leur entité HTML
    // Autre possiblité : remplacer les doubles quotes par des simples quotes
    $tab1 = [
                2 => 'Il leur avait annoncé &quot;Je vais vous défoncer&quot; l\'enseignant relaxé',
                7 => 'Une famille de pingouins s\'installe dans l\'amphi B',
                9 => 'Votez pour l\'hymne de la Licence'
            ];
    affBlocTroisArticlesL('L\'info brûlante', $tab1);

    // génération des 3 articles parmi les articles restants
    $tab2 = [
                5 => 'Le calendier des Dieux de la Licence bientôt disponible',
                6 => 'Une arnarque au corrigé de TL mise à jour',
                3 => 'Qui se cache derrière la Gazette de L-INFO ?'
            ];
    affBlocTroisArticlesL('Les incontournables', $tab2);

    affHoroscopeL();

    echo '</main>';
}

//_______________________________________________________________
/**
 * Affiche une vignette
 *
 * @param   int         id de l'article
 * @param   string      titre de l'article
 *
 * @return  void
 */
function affUnArticleL(int $id, string $titre) : void {
    echo
            '<a href="./php/article.php?id=', $id, '">',
                '<img src="upload/', $id, '.jpg" alt="Photo d\'illustration | ', $titre, '"><br>',
                $titre,
            '</a>';
}
//_______________________________________________________________
/**
 * Affiche un bloc de 3 vignettes.
 *
 * @param   string      titre du bloc de 3 vignettes
 * @param   array       ids et titres des articles (clé : id de l'article, valeur associée à la clé : titre de l'article)
 *
 * @return  void
 */
function affBlocTroisArticlesL(string $titreBloc, array $articles) : void {
    echo    '<section class="centre">',
                '<h2>', $titreBloc, '</h2>';
    foreach($articles as $id => $titre){
        affUnArticleL($id, $titre);
    }
    echo    '</section>';
}

//_______________________________________________________________
/**
 * Affichage de l'horoscope
 *
 * @return  void
 */
function affHoroscopeL() : void {
    echo
    '<section>',
            '<h2>Horoscope de la semaine</h2>',

            '<p>Vous l\'attendiez tous, voici l\'horoscope du semestre pair de l\'année 2023-2024. Sans surprise, il n\'est pas terrible...</p>',

            '<table id="horoscope">',
                '<tr>',
                    '<td>Signe</td>',
                    '<td>Date</td>',
                    '<td>Votre horoscope</td>',
                '</tr>',
                '<tr>',
                    '<td>&#9800; Bélier</td>',
                    '<td>du 21 mars<br>au 19 avril</td>',
                    '<td rowspan="4">',
                        '<p>Après des vacances bien méritées, l\'année reprend sur les chapeaux de roues. Tous les signes sont concernés. </p>',
                        '<p>Jupiter s\'aligne avec Saturne, péremptoirement à Venus, et nous promet un semestre qui ne sera pas de tout repos. ',
                        'Février sera le mois le plus tranquille puisqu\'il ne comporte que 29 jours.</p>',
                        '<p>Les fins de mois seront douloureuses pour les natifs du 2e décan au moment où tomberont les tant-attendus résultats ',
                            'du module d\'<em>Algorithmique et Structures de Données</em> du semestre 3.</p>',
                    '</td>',
                '</tr>',
                '<tr>',
                    '<td>&#9801; Taureau</td>',
                    '<td>du 20 avril<br>au 20 mai</td>',
                '</tr>',
                '<tr>',
                    '<td>...</td>',
                    '<td>...</td>',
                '</tr>',
                '<tr>',
                    '<td>&#9811; Poisson</td>',
                    '<td>du 20 février<br>au 20 mars</td>',
                '</tr>',
            '</table>',

            '<p>Malgré cela, notre équipe d\'astrologues de choc vous souhaite à tous un bon semestre.</p>',
        '</section>';
}
