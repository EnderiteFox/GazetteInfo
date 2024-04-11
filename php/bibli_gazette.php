<?php
/*********************************************************
 *        Bibliothèque de fonctions spécifiques          *
 *        à l'application La gazette de L-INFO           *
 *********************************************************/

// Force l'affichage des erreurs
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting( E_ALL );

// Phase de développement (IS_DEV = true) ou de production (IS_DEV = false)
define ('IS_DEV', true);

/** Constantes : les paramètres de connexion au serveur MariaDB */
define ('BD_NAME', 'gazette_bd');
define ('BD_USER', 'gazette_user');
define ('BD_PASS', 'gazette_pass');
define ('BD_SERVER', 'mariadb-hostname');

// Définit le fuseau horaire par défaut à utiliser. Disponible depuis PHP 5.1
date_default_timezone_set('Europe/Paris');

// limites liées aux tailles des champs de la table utilisateur
define('LMAX_PSEUDO', 20);    // taille du champ usLogin de la table utilisateur
define('LMAX_NOM', 50);      // taille du champ usNom de la table utilisateur
define('LMAX_PRENOM', 60);   // taille du champ usPrenom de la table utilisateur
define('LMAX_EMAIL', 255);   // taille du champ usMail de la table utilisateur

define('LMIN_PSEUDO', 4);

define('AGE_MINIMUM', 18);

define('LMIN_PASSWORD', 4);

//_______________________________________________________________
/**
 * Affichage du début de la page HTML (head + menu + header).
 *
 * @param  string  $titre       le titre de la page (<head> et <h1>)
 * @param  string  $prefixe     le préfixe du chemin relatif vers la racine du site
 *
 * @return void
 */
function affEntete(string $titre, string $prefixe = '..') : void {

    echo
        '<!doctype html>',
        '<html lang="fr">',
            '<head>',
                '<meta charset="UTF-8">',
                '<title>La gazette de L-INFO | ', $titre, '</title>',
                '<link rel="stylesheet" type="text/css" href="', $prefixe,'/styles/gazette.css">',
            '</head>',
            '<body>';

    affMenu($prefixe);

    echo        '<header>',
                    '<img src="', $prefixe, '/images/titre.png" alt="Image du titre | La gazette de L-INFO" width="780" height="83">',
                    '<h1>', $titre, '</h1>',
                '</header>';
}

//_______________________________________________________________
/**
 * Affichage du menu de navigation.
 *
 * @param  string  $prefixe     le préfixe du chemin relatif vers la racine du site
 *
 * @return void
 */
function affMenu(string $prefixe = '..') : void {

    echo    '<nav><ul>',
                '<li><a href="', $prefixe, '/index.php">Accueil</a></li>',
                '<li><a href="', $prefixe, '/php/actus.php">Toute l\'actu</a></li>',
                '<li><a href="', $prefixe, '/php/recherche.php">Recherche</a></li>',
                '<li><a href="', $prefixe, '/php/redaction.php">La rédac\'</a></li>';
    if (estAuthentifie()){
        echo    '<li><a href="#">', htmlProtegerSorties($_SESSION['pseudo']),'</a>',
                    '<ul>',
                        '<li><a href="', $prefixe, '/php/compte.php">Mon profil</a></li>',
                        $_SESSION['redacteur'] ? "<li><a href='$prefixe/php/nouveau.php'>Nouvel article</a></li>" : '',
                        '<li><a href="', $prefixe, '/php/deconnexion.php">Se déconnecter</a></li>',
                    '</ul>',
                '</li>';
    }
    else {
        echo    '<li><a href="', $prefixe, '/php/connexion.php">Se connecter</a></li>';
    }
    echo    '</ul></nav>';
}

//_______________________________________________________________
/**
 * Affichage du pied de page.
 *
 * @return  void
 */
function affPiedDePage() : void {

    echo        '<footer>&copy; Licence Informatique - Février 2024 - Tous droits réservés</footer>',
            '</body></html>';
}

//_______________________________________________________________
/**
* Détermine si l'utilisateur est authentifié
*
* @return bool     true si l'utilisateur est authentifié, false sinon
*/
function estAuthentifie(): bool {
    return  isset($_SESSION['pseudo']);
}


//_______________________________________________________________
/**
 * Termine une session et effectue une redirection vers la page transmise en paramètre
 *
 * Cette fonction est appelée quand l'utilisateur se déconnecte "normalement" et quand une
 * tentative de piratage est détectée. On pourrait améliorer l'application en différenciant ces
 * 2 situations. Et en cas de tentative de piratage, on pourrait faire des traitements pour
 * stocker par exemple l'adresse IP, etc.
 *
 * @param string    $page URL de la page vers laquelle l'utilisateur est redirigé
 *
 * @return void
 */
function sessionExit(string $page = '../index.php'): void {

    // suppression de toutes les variables de session
    $_SESSION = array();

    if (ini_get("session.use_cookies")) {
        // suppression du cookie de session
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 86400,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();

    header("Location: $page");
    exit();
}

