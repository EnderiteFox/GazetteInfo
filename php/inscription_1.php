<?php

// chargement des bibliothèques de fonctions
require_once('bibli_gazette.php');
require_once('bibli_generale.php');

// génération de la page
affEntete('Réception des données soumises');

affContenuL();

affPiedDePage();


/*********************************************************
 *
 * Définitions des fonctions locales de la page
 *
 *********************************************************/
//_______________________________________________________________
/**
 * Affichage du contenu principal de la page
 *
 * ATTENTION : dans cette fonction, il manque la protection des sorties avec htmlentities()
 *
 * @return  void
 */
function affContenuL() : void {
    echo '<main>',
            '<section>',
                '<h2>Avec une boucle foreach</h2>',
                '<ul>';

    foreach($_POST as $cle => $val){
        echo        '<li>cle = ', $cle, ', valeur = ', $val, '</li>';
    }

    echo        '</ul>',
            '</section>',
            '<section>',
                '<h2>Avec var_dump()</h2>',
                '<pre>';
    var_dump($_POST);
    echo        '</pre>',
            '</section>',
            '<section>',
                '<h2>Avec print_r()</h2>',
                '<pre>', print_r($_POST, true), '</pre>',
            '</section>',
        '</main>';
}
