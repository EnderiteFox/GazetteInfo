<?php

// chargement des bibliothèques de fonctions
require_once('./bibli_gazette.php');
require_once('./bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();

if (!estAuthentifie()) sessionExit();

$bd = false;

$err = [];

if (isset($_POST['btnSave'])) editerInformations($bd, $err);
if (isset($_POST['btnSavePass'])) editerMotDePasse($bd, $err);

affEntete('Mon compte');

affFormulaireL($bd, $err);

if ($bd !== false) mysqli_close($bd);

affPiedDePage();

/**
 * Affiche le formulaire de gestion du compte
 * @param mysqli|false $bd La base de données
 * @param array|false $err Le tableau contenant les éventuelles erreurs dans le formulaire
 * @return void
 */
function affFormulaireL(mysqli|false &$bd, array $err): void {
    if ($bd === false) $bd = bdConnect();
    $tab = recupererInformations($bd);
    if ($tab === false) {
        affErreurL('La base de données est injoignable pour le moment');
        return;
    }
    echo
        '<main>',
            '<section>',
                '<h2>Informations personnelles</h2>';
    if (isset($_POST['btnSave'])) {
        if (count($err) != 0) {
            afficherTabErreurs($err, 'Les erreurs suivantes ont été relevées :');
        }
        else echo '<p class="validationText">Les informations ont été mises à jour avec succès.</p>';
    }
    echo
                '<p>Vous pouvez modifier les informations suivantes.</p>',
                '<form method="post" action="compte.php">',
                    '<table>';
    affFormInfoPerso($tab);
    echo
                        '<tr>',
                            '<td colspan="2">',
                                '<label><input type="checkbox" name="cbSpam" value="1"',
                                    $tab['cbSpam'] ? ' checked' : '',
                                        '> J\'accepte de recevoir des tonnes de mails pourris</label>',
                            '</td>',
                        '</tr>',
                        '<tr>',
                            '<td colspan="2">',
                                '<input type="submit" name="btnSave" value="Enregistrer" class="redButton"> ',
                                    '<input type="reset" value="Réinitialiser">',
                            '</td>',
                        '</tr>',
                    '</table>',
                '</form>',
            '</section>',
            '<section>',
                '<h2>Mot de passe</h2>';
    if (isset($_POST['btnSavePass'])) {
        if (count($err) != 0) {
            afficherTabErreurs($err, 'Les erreurs suivantes ont été relevées :');
        }
        else echo '<p class="validationText">Votre mot de passe a été mis à jour avec succès.</p>';
    }
    echo
                '<p>Vous pouvez modifier votre mot de passe ci-dessous</p>',
                '<form method="post" action="compte.php">',
                    '<table>';
    affLigneInput(
        'Choisissez un mot de passe :',
        array('type' => 'password', 'name' => 'passe1',
            'placeholder' => LMIN_PASSWORD.' caractères minimum', 'required' => null
        )
    );
    affLigneInput(
        'Répétez le mot de passe :',
        array('type' => 'password', 'name' => 'passe2', 'required' => null)
    );
    echo
                        '<tr>',
                            '<td colspan="2">',
                                '<input type="submit" name="btnSavePass" value="Enregistrer" class="redButton">',
                            '</td>',
                        '</tr>',
                    '</table>',
                '</form>',
            '</section>',
        '</main>';
}

/**
 * Récupère les informations de l'utilisateur dans la base de données
 * @param mysqli $bd La base de donnée
 * @return array|false Un tableau contenant les informations de l'utilisateurn, ou false si une erreur est survenue
 */
function recupererInformations(mysqli $bd): array|false {
    $sql = 'SELECT utCivilite, utNom, utPrenom, utDateNaissance, utEmail, utMailsPourris FROM utilisateur '.
        'WHERE utPseudo="'.$_SESSION['pseudo'].'"';
    $result = bdSendRequest($bd, $sql);
    if (mysqli_num_rows($result) != 0) {
        $tab = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        return [
            'nom' => $tab['utNom'],
            'prenom' => $tab['utPrenom'],
            'naissance' => $tab['utDateNaissance'],
            'email' => $tab['utEmail'],
            'radSexe' => $tab['utCivilite'],
            'cbSpam' => $tab['utMailsPourris']
        ];
    }
    return false;
}

/**
 * Édite les informations du compte dans la base de données
 * @param mysqli|false $bd La base de données
 * @param array $err Le tableau dans lequel seront ajoutées les éventuelles erreurs dans le formulaire
 * @return void
 */
function editerInformations(mysqli|false &$bd, array &$err): void {
    if (!parametresControle(
        'post',
        ['nom', 'prenom', 'naissance', 'email', 'btnSave'],
        ['radSexe', 'cbSpam'])
    ) sessionExit();
    $values = verifierInfoPerso($err);

    if (count($err) > 0) return;

    extract($values);

    if ($bd === false) $bd = bdConnect();

    $email = mysqli_real_escape_string($bd, $email);
    $nom = mysqli_real_escape_string($bd, $nom);
    $prenom = mysqli_real_escape_string($bd,$prenom);

    $sql = 'UPDATE utilisateur SET '
    .'utNom="'.$nom.'", utPrenom="'.$prenom.'", utEmail="'.$email.'", utDateNaissance='.$dateNaissance.', '
    .'utCivilite="'.$civilite.'", utMailsPourris='.$mailsPourris
        .' WHERE utPseudo="'.$_SESSION['pseudo'].'"';

    bdSendRequest($bd, $sql);
}

/**
 * Édite le mot de passe dans la base de données
 * @param mysqli|false $bd La base de données
 * @param array $err Le tableau dans lequel seront ajoutées les éventuelles erreurs dans le formulaire
 * @return void
 */
function editerMotDePasse(mysqli|false $bd, array &$err): void {
    if (!parametresControle('post', ['btnSavePass', 'passe1', 'passe2'])) sessionExit();
    verifierMotDePasse($err);
    if (sizeof($err) > 0) return;

    // calcul du hash du mot de passe pour enregistrement dans la base.
    $passe = password_hash($_POST['passe1'], PASSWORD_DEFAULT);

    if ($bd === false) $bd = bdConnect();

    $passe = mysqli_real_escape_string($bd, $passe);

    $sql = 'UPDATE utilisateur SET utPasse="'.$passe.'" WHERE utPseudo="'.$_SESSION['pseudo'].'"';

    bdSendRequest($bd, $sql);
}
