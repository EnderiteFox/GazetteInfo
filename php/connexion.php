<?php

require_once('bibli_gazette.php');
require_once('bibli_generale.php');

ob_start();

session_start();

// Stockage dans une variable de session de la page précédente

if (!isset($_SESSION['prevPage'])) $_SESSION['prevPage'] = '../index.php';

if (!preg_match('#($|/)connexion.php#', $_SERVER['HTTP_REFERER'])) {
    $_SESSION['prevPage'] = $_SERVER['HTTP_REFERER'];
}

if (!parametresControle('post', [], ['btnConnexion', 'pseudo', 'password'])) sessionExit();

connexion();

affEntete('Connexion');

affFormulaireL();

affPiedDePage();

/**
 * Gère la connexion au site
 * @return void
 */
function connexion(): void {
    if (!isset($_POST['btnConnexion'])) return;
    $bd = bdConnect();

    // Récupération de l'utilisateur dans la base de donnée
    $pseudo = mysqli_real_escape_string($bd, trim($_POST['pseudo']));
    $sql = 'SELECT utPasse, utRedacteur FROM utilisateur WHERE utPseudo = \''.$pseudo.'\'';
    $result = bdSendRequest($bd, $sql);

    if (mysqli_num_rows($result) != 1) {
        mysqli_free_result($result);
        mysqli_close($bd);
        return;
    }

    $tab = mysqli_fetch_assoc($result);
    $hash = $tab['utPasse'];
    $redacteur = $tab['utRedacteur'];
    mysqli_free_result($result);

    // Vérification du pseudo
    if (!password_verify($_POST['password'], $hash)) return;

    // Définition des variables de session
    $_SESSION['pseudo'] = trim($_POST['pseudo']);
    $_SESSION['redacteur'] = $redacteur == 1;

    mysqli_close($bd);

    // Redirection vers la page précédente
    header('Location: '.$_SESSION['prevPage']);

    mysqli_close($bd);
}

/**
 * Affichage du formulaire
 * @return void
 */
function affFormulaireL(): void {
    if (isset($_POST['btnConnexion'])) $values = htmlProtegerSorties($_POST);
    else $values['pseudo'] = '';

    echo
        '<main>',
            '<section>',
                '<h2>Formulaire de connexion</h2>',
                '<p>Pour vous authentifier, remplissez le formulaire ci-dessous.</p>';

    if (isset($_POST['btnConnexion'])) {
        echo '<div class="erreur">Échec d\'authentification. Utilisateur inconnu ou mot de passe incorrect.</div>';
    }

    echo '<form method="post" action="connexion.php"><table>';

    affLigneInput('Pseudo :', array(
        'type' => 'text', 'name' => 'pseudo', 'value' => $values['pseudo'], 'required' => null
    ));
    affLigneInput('Mot de passe :', array(
        'type' => 'password', 'name' => 'password', 'value' => '', 'required' => null
    ));

    echo
                    '<tr>',
                        '<td colspan="2">',
                            '<input type="submit" name="btnConnexion" value="Se connecter">',
                            '<input type="reset" value="Annuler">',
                        '</td>',
                    '</tr>',
                '</table>',
            '</form>',
            '<p>Pas encore inscrit ? N\'attendez pas, <a href="inscription.php">inscrivez-vous</a> !',
        '</section>',
    '</main>';
}
