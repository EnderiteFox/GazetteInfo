<?php
/* ------------------------------------------------------------------------------
    Architecture de la page
    - étape 1 : vérifications diverses et traitement des soumissions
    - étape 2 : génération du code HTML de la page
------------------------------------------------------------------------------*/

// chargement des bibliothèques de fonctions
require_once('bibli_gazette.php');
require_once('bibli_generale.php');

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();

/*------------------------- Etape 1 --------------------------------------------
- vérifications diverses et traitement des soumissions
------------------------------------------------------------------------------*/

// si l'utilisateur est déjà authentifié
if (estAuthentifie()){
    header ('Location: ../index.php');
    exit();
}

// si formulaire soumis, traitement de la demande d'inscription
if (isset($_POST['btnInscription'])) {
    $erreurs = traitementInscriptionL(); // ne revient pas quand les données soumises sont valides
}
else{
    $erreurs = null;
}

/*------------------------- Etape 2 --------------------------------------------
- génération du code HTML de la page
------------------------------------------------------------------------------*/

// génération de la page
affEntete('Inscription');

affFormulaireL($erreurs);

affPiedDePage();

// facultatif car fait automatiquement par PHP
ob_end_flush();

// ----------  Fonctions locales du script ----------- //

/**
 * Contenu de la page : affichage du formulaire d'inscription
 *
 * En absence de soumission (i.e. lors du premier affichage), $err est égal à null
 * Quand l'inscription échoue, $err est un tableau de chaînes
 *
 * @param ?array    $err    Tableau contenant les erreurs en cas de soumission du formulaire, null lors du premier affichage
 *
 * @return void
 */
function affFormulaireL(?array $err): void {
    // réaffichage des données soumises en cas d'erreur, sauf les mots de passe
    if (isset($_POST['btnInscription'])){
        $values = htmlProtegerSorties($_POST);
        $values['radSexe'] = (int)($_POST['radSexe'] ?? -1);
        // équivalent à
        // $values['radSexe'] = (int)(isset($_POST['radSexe']) ? $_POST['radSexe'] : -1);
        $values['cbSpam'] = isset($_POST['cbSpam']);
    }
    else{
        $values['pseudo'] = $values['nom'] = $values['prenom'] = $values['email'] = $values['naissance'] = '';
        $values['radSexe'] = -1;
        $values['cbSpam'] = true;
    }

    echo
        '<main>',
            '<section>',
                '<h2>Formulaire d\'inscription</h2>',
                '<p>Pour vous inscrire, remplissez le formulaire ci-dessous.</p>';

    if (is_array($err)) afficherTabErreurs($err, 'Les erreurs suivantes ont été relevées lors de votre inscription :');


    echo
            '<form method="post" action="inscription.php">',
                '<table>';

    affLigneInput(  'Choisissez un pseudo :', array('type' => 'text', 'name' => 'pseudo', 'value' => $values['pseudo'],
                    'placeholder' => LMIN_PSEUDO . ' caractères alphanumériques minimum', 'required' => null));
    affFormInfoPerso($values);
    affLigneInput(  'Choisissez un mot de passe :', array('type' => 'password', 'name' => 'passe1', 'value' => '',
                    'placeholder' => LMIN_PASSWORD . ' caractères minimum', 'required' => null));
    affLigneInput('Répétez le mot de passe :', array('type' => 'password', 'name' => 'passe2', 'value' => '', 'required' => null));

    echo
                    '<tr>',
                        '<td colspan="2">',
                            '<label><input type="checkbox" name="cbCGU" value="1" required>',
                                ' J\'ai lu et j\'accepte les conditions générales d\'utilisation </label>',
                            '<label><input type="checkbox" name="cbSpam" value="1"',
                            $values['cbSpam'] ? ' checked' : '',
                                '> J\'accepte de recevoir des tonnes de mails pourris</label>',
                        '</td>',
                    '</tr>',
                    '<tr>',
                        '<td colspan="2">',
                            '<input type="submit" name="btnInscription" value="S\'inscrire" class="redButton"> ',
                            '<input type="reset" value="Réinitialiser">',
                        '</td>',
                    '</tr>',
                '</table>',
            '</form>',
        '</section>',
    '</main>';
}


/**
 * Traitement d'une demande d'inscription
 *
 * Vérification de la validité des données
 * Si on trouve des erreurs => return un tableau les contenant
 * Sinon
 *     Enregistrement du nouvel inscrit dans la base
 *     Enregistrement du pseudo (et du droit de redacteur fixé à 0) de l'utilisateur dans une variable de session, et redirection vers la page protegee.php
 * FinSi
 *
 * Toutes les erreurs détectées qui nécessitent une modification du code HTML sont considérées comme des tentatives de piratage
 * et donc entraînent l'appel de la fonction em_sessionExit() sauf :
 * - les éventuelles suppressions des attributs required car l'attribut required est une nouveauté apparue dans la version HTML5 et
 *   nous souhaitons que l'application fonctionne également correctement sur les vieux navigateurs qui ne supportent pas encore HTML5
 * - une éventuelle modification de l'input de type date en input de type text car c'est ce que font les navigateurs qui ne supportent
 *   pas les input de type date
 *
 *  @return array    un tableau contenant les erreurs s'il y en a
 */
function traitementInscriptionL(): array {

    if( !parametresControle('post', ['pseudo', 'nom', 'prenom', 'naissance',
                                     'passe1', 'passe2', 'email', 'btnInscription'], ['radSexe', 'cbCGU', 'cbSpam'])) {
        sessionExit();
    }

    $erreurs = [];

    // vérification du pseudo
    $pseudo = $_POST['pseudo'] = trim($_POST['pseudo']);

    if (!preg_match('/^[0-9a-zA-Z]{' . LMIN_PSEUDO . ',' . LMAX_PSEUDO . '}$/u', $pseudo)) {
        $erreurs[] = 'Le pseudo doit contenir entre '. LMIN_PSEUDO .' et '. LMAX_PSEUDO . ' caractères alphanumériques, sans signe diacritique.';
    }

    $valeurs = verifierInfoPerso($erreurs);

    verifierMotDePasse($erreurs);

    // vérification de la valeur de l'élément cbCGU
    if (! isset($_POST['cbCGU'])){
        $erreurs[] = 'Vous devez accepter les conditions générales d\'utilisation .';
    }
    else if ($_POST['cbCGU'] !== '1'){
        sessionExit();
    }

    // si erreurs --> retour
    if (count($erreurs) > 0) {
        return $erreurs;   //===> FIN DE LA FONCTION
    }

    extract($valeurs);

    // on vérifie si le pseudo et l'adresse email ne sont pas encore utilisés que si tous les autres champs
    // sont valides car ces 2 dernières vérifications nécessitent une connexion au serveur de base de données
    // consommatrice de ressources système

    // ouverture de la connexion à la base
    $bd = bdConnect();

    // protection des entrées
    $pseudo2 = mysqli_real_escape_string($bd, $pseudo); // fait par principe, mais inutile ici car on a déjà vérifié que le pseudo
                                                        // ne contenait que des caractères alphanumériques
    $email = mysqli_real_escape_string($bd, $email);

    $sql = "SELECT utPseudo, utEmail FROM utilisateur WHERE utPseudo = '$pseudo2' OR utEmail = '$email'";
    $res = bdSendRequest($bd, $sql);

    while($tab = mysqli_fetch_assoc($res)) {
        if ($tab['utPseudo'] == $pseudo){
            $erreurs[] = 'Le pseudo choisi est déjà utilisé.';
        }
        if ($tab['utEmail'] == $email){
            $erreurs[] = 'L\'adresse email est déjà utilisée.';
        }
    }
    // Libération de la mémoire associée au résultat de la requête
    mysqli_free_result($res);


    // si erreurs --> retour
    if (count($erreurs) > 0) {
        // fermeture de la connexion à la base de données
        mysqli_close($bd);
        return $erreurs;   //===> FIN DE LA FONCTION
    }

    // calcul du hash du mot de passe pour enregistrement dans la base.
    $passe = password_hash($_POST['passe1'], PASSWORD_DEFAULT);

    $passe = mysqli_real_escape_string($bd, $passe);

    $nom = mysqli_real_escape_string($bd, $nom);
    $prenom = mysqli_real_escape_string($bd, $prenom);

    // les valeurs sont écrites en respectant l'ordre de création des champs dans la table usager
    $sql = "INSERT INTO utilisateur (utPseudo, utNom, utPrenom, utEmail, utPasse, utDateNaissance, utRedacteur, utCivilite, utMailsPourris)
            VALUES ('$pseudo2', '$nom', '$prenom', '$email', '$passe', $dateNaissance, 0, '$civilite', $mailsPourris)";

    bdSendRequest($bd, $sql);


    // fermeture de la connexion à la base de données
    mysqli_close($bd);

    // mémorisation du pseudo dans une variable de session (car affiché dans la barre de navigation sur toutes les pages)
    // enregistrement dans la variable de session du pseudo avant passage par la fonction mysqli_real_escape_string()
    // car, d'une façon générale, celle-ci risque de rajouter des antislashs
    // Rappel : ici, elle ne rajoutera jamais d'antislash car le pseudo ne peut contenir que des caractères alphanumériques
    $_SESSION['pseudo'] = $pseudo;

    $_SESSION['redacteur'] = false; // utile pour l'affichage de la barre de navigation

    header('Location: ../index.php');
    exit(); //===> Fin du script
}
