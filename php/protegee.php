<?php

require_once 'bibli_generale.php';
require_once 'bibli_gazette.php';

// bufferisation des sorties
ob_start();

// démarrage ou reprise de la session
session_start();


// si l'utilisateur n'est pas authentifié, on le redirige sur la page index.php
if (! estAuthentifie()){
    header('Location: ../index.php');
    exit;
}

// affichage de l'entête
affEntete('Accès restreint');


$bd = bdConnect();

$pseudo = mysqli_real_escape_string($bd, $_SESSION['pseudo']);

$sql = "SELECT *
        FROM utilisateur
        WHERE utPseudo = '$pseudo'";

$res = bdSendRequest($bd, $sql);

$T = mysqli_fetch_assoc($res);

mysqli_free_result($res);
mysqli_close($bd);

$T = htmlProtegerSorties($T);

echo '<main>',
        '<section>',
            '<h2>Accès restreint aux utilisateurs authentifiés</h2>',
            '<ul>',
                '<li><strong>Pseudo : ', htmlProtegerSorties($_SESSION['pseudo']), '</strong></li>',
                '<li>SID : ', session_id(), '</li>';
foreach($T as $cle => $val){
    echo        '<li>', $cle, ' : ', $val, '</li>';
}
echo        '</ul>',
        '</section>',
    '</main>';

// affichage du pied de page
affPiedDePage();

// facultatif car fait automatiquement par PHP
ob_end_flush();
