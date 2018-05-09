<?php
ob_start('ob_gzhandler'); //démarre la bufferisation, compression du tampon si le client supporte gzip
session_start();

require_once 'bibli_generale.php';
require_once 'bibli_bookshop.php';

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)

// si $_POST non vide
($_POST) && fdl_control_piratage();

if (!isset($_SESSION['cliID'])){
    $page = '../index.php';
    if (isset($_SERVER['HTTP_REFERER'])){
        $page = $_SERVER['HTTP_REFERER'];
        $nom_page = url_get_nom_fichier($page);
        if (! in_array($nom_page, get_pages_bookshop())){
            $page = '../index.php';
        }
    }
    fd_redirige($page);
}

$err = isset($_POST['btnValider']) ? fdl_inscription() : array();
print_r2($err);
fd_html_debut('BookShop | Compte', '../styles/bookshop.css');

fd_bookshop_enseigne_entete(isset($_SESSION['cliID']),'../');

fdl_contenu($err);

fd_bookshop_pied();

fd_html_fin();

ob_end_flush();


// ----------  Fonctions locales au script ----------- //

/**
 *	Affichage du contenu de la page (formulaire d'inscription)
 *	@param 	array	$err	tableau d'erreurs à afficher
 */
function fdl_contenu($err) {

    $bd = fd_bd_connect();
    $sql = "SELECT * FROM clients WHERE cliID = {$_SESSION['cliID']}";
    $res = mysqli_query($bd,$sql) or fd_bd_erreur($bd,$sql);
    $data = mysqli_fetch_assoc($res);
    $email = $data['cliEmail'];
    $pass = $data['cliPassword'];
    $nomprenom = $data['cliNomPrenom'];
    $naiss_a = substr($data['cliDateNaissance'], 0, 4);
    $naiss_m = substr($data['cliDateNaissance'], 4, 2);
    $naiss_j = substr($data['cliDateNaissance'], 6, 2);
    $ville = $data['cliVille'] == "INVALID" ? "" : $data['cliVille'];
    $adresse = $data['cliAdresse'] == "INVALID" ? "" : $data['cliAdresse'];
    $cp =  $data['cliCP'] == 0 ? "" : $data['cliCP'];
    $pays = $data['cliPays'] == "INVALID" ? "" : $data['cliPays'];



    mysqli_free_result($res);
    mysqli_close($bd);
    boutonHistoriqueCommande();
    echo'<H1>Votre Compte</H1>';


    if (count($err) > 0) {
        echo '<p class="erreur">Votre inscription n\'a pas pu être réalisée à cause des erreurs suivantes : ';
        foreach ($err as $v) {
            echo '<br> - ', $v;
        }
        echo '</p>';
    }else if(isset($_POST['btnValider'])){
        echo '<p class="ComptRequeteOK">Les modifications ont bien été enrgistré</p>';
    }



    echo
        '<div class="Compte"><form class="Compte" method="post" action="compte.php">',
        fd_form_input(FD_Z_HIDDEN, 'passActuel', $pass),
        '<p class="enteteBloc">Mes informations personnels: </p>',
        '<table>',
        fd_form_ligne('Votre adresse email :', fd_form_input(FD_Z_TEXT, 'email', $email, 30)),
        fd_form_ligne('Choisissez un mot de passe :', fd_form_input(FD_Z_PASSWORD, 'pass1', 'despoints', 30)),
        fd_form_ligne('Répétez le mot de passe :', fd_form_input(FD_Z_PASSWORD, 'pass2', '', 30)),
        fd_form_ligne('Nom et prénom :', fd_form_input(FD_Z_TEXT, 'nomprenom', $nomprenom, 30)),
        fd_form_ligne('Date de naissance :', fd_form_date('naiss', NB_ANNEES_DATE_NAISSANCE, $naiss_j, $naiss_m, $naiss_a)),
        '<tr><td colspan="2">', '<hr>', '</td></tr>',
        fd_form_ligne('Ville :', fd_form_input(FD_Z_TEXT, 'ville', $ville, 30)),
        fd_form_ligne('Adresse :', fd_form_input(FD_Z_TEXT, 'adresse', $adresse, 30)),
        fd_form_ligne('Code Postal :', fd_form_input(FD_Z_TEXT, 'cp', $cp, 30)),
        fd_form_ligne('Pays :', fd_form_input(FD_Z_TEXT, 'pays', $pays, 30)),
        '<tr><td colspan="2">', '<hr>', '</td></tr>',

        fd_form_ligne('Mot de passe actuel :', fd_form_input(FD_Z_PASSWORD, 'passVerif', '', 30)),
        '<tr><td colspan="2" style="padding-top: 10px;" class="centered">', fd_form_input(FD_Z_SUBMIT,'btnValider','Valider!'), '</td></tr>',
        '</table>',
        '</form></div>';
}

/**
 * Objectif : détecter les tentatives de piratage
 *
 * Si une telle tentative est détectée, la session est détruite et l'utilisateur est redirigée
 * vers la page d'accueil du site
 *
 * @global  array     $_POST
 *
 */
function fdl_control_piratage(){
    $nb = count($_POST);
    if ($nb == 1){
        (!isset($_POST['btnValider']) || $_POST['btnValider'] != 'Valider!') && fd_exit_session();
        return;     // => ok, pas de problème détecté
    }if ($nb == 14){
        (! isset($_POST['btnValider']) || $_POST['btnValider'] != 'Valider!') && fd_exit_session();
        //(! isset($_POST['source'])) && fd_exit_session();
        //(strip_tags($_POST['source']) != $_POST['source']) && fd_exit_session();
        (! isset($_POST['email'])) && fd_exit_session();
        (! isset($_POST['pass1'])) && fd_exit_session();
        (! isset($_POST['pass2'])) && fd_exit_session();
        (! isset($_POST['passVerif'])) && fd_exit_session();
        (! isset($_POST['passVerif'])) && fd_exit_session();
        (! isset($_POST['nomprenom'])) && fd_exit_session();
        (! isset($_POST['ville'])) && fd_exit_session();
        (! isset($_POST['adresse'])) && fd_exit_session();
        (! isset($_POST['cp'])) && fd_exit_session();
        (! isset($_POST['pays'])) && fd_exit_session();
        (! isset($_POST['naiss_j'])) && fd_exit_session();
        (! isset($_POST['naiss_m'])) && fd_exit_session();
        (! isset($_POST['naiss_a'])) && fd_exit_session();
        (!est_entier($_POST['naiss_a']) || !est_entier($_POST['naiss_m']) || !est_entier($_POST['naiss_j'])) && fd_exit_session();
        $aa = date('Y');
        ($_POST['naiss_j'] < 1 || $_POST['naiss_j'] > 31 || $_POST['naiss_m'] < 1 || $_POST['naiss_m'] > 12 ||
            $_POST['naiss_a'] > $aa || $_POST['naiss_a'] <= $aa - NB_ANNEES_DATE_NAISSANCE) && fd_exit_session();
        return;     // => ok, pas de problème détecté
    }
    fd_exit_session();
}

/**
 *	Traitement de la modification
 *
 *		Etape 1. vérification de la validité des données
 *					-> return des erreurs si on en trouve
 *		Etape 2. enregistrement des nouvelles information dans la base
 *
 * @global  array     $_POST
 *
 * @return array 	tableau assosiatif contenant les erreurs
 */
function fdl_inscription(){

    $err = array();

    $email = trim($_POST['email']);
    $pass1 = trim($_POST['pass1']);
    $pass2 = trim($_POST['pass2']);
    $passVerif = trim($_POST['passVerif']);
    $passActuel = trim($_POST['passActuel']);
    $nomprenom = trim($_POST['nomprenom']);
    $ville = trim($_POST['ville']);
    $adresse = trim($_POST['adresse']);
    $pays = trim($_POST['pays']);
    $cp = (int)$_POST['cp'];
    $naiss_j = (int)$_POST['naiss_j'];
    $naiss_m = (int)$_POST['naiss_m'];
    $naiss_a = (int)$_POST['naiss_a'];

    // vérification email
    $noTags = strip_tags($email);
    if ($noTags != $email) {
        $err['email'] = 'L\'email ne peut pas contenir de code HTML.';
    } else {
        $i = mb_strpos($email, '@', 0, 'UTF-8');
        $j = mb_strpos($email, '.', 0, 'UTF-8');
        if ($i === FALSE || $j === FALSE) {
            $err['email'] = 'L\'adresse email ne respecte pas le bon format.';
        } // le test suivant rend inutile celui qui précède
        else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err['email'] = 'L\'adresse email ne respecte pas le bon format.';
        }
    }

    // vérification des mots de passe

    if($pass2 != ''){
        if($pass1 == $pass2){
            $nb = mb_strlen($pass1, 'UTF-8');
            $noTags = strip_tags($pass1);
            if (mb_strlen($noTags, 'UTF-8') != $nb) {
                $err['pass1'] = 'La zone Mot de passe ne peut pas contenir de code HTML.';
            }
            else if ($nb < 4 || $nb > 20){
                $err['pass1'] = 'Le mot de passe doit être constitué de 4 à 20 caractères.';
            }
        }else{
            $err['pass1'] = 'Les mots de passe doivent être identique';
        }
    }else{
        $pass2 = $passVerif;
    }

    //Verification du mot de pass pour validation

    $nb = mb_strlen($passActuel, 'UTF-8');
    $noTags = strip_tags($passActuel);
    if (mb_strlen($noTags, 'UTF-8') != $nb) {
        $err['passActuel'] = 'La zone Mot de passe actuel ne peut pas contenir de code HTML.';
    }else if($passActuel != md5($passVerif)){
        $err['passActuel'] = 'Vous devez renseigner votre mot de passe actuel dans le champ Mot de passe actuel';
    }



    // vérification des noms et prenoms
    $noTags = strip_tags($nomprenom);
    if ($noTags != $nomprenom){
        $err['nomprenom'] = 'Le nom et le prénom ne peuvent pas contenir de code HTML.';
    }
    else if (empty($nomprenom)) {
        $err['nomprenom'] = 'Le nom et le prénom doivent être renseignés.';
    }
    /*elseif (! preg_match("/^[[:alpha:]][[:alpha:]\- ']{1,99}$/", $nomprenom)) { // ne fct pas avec les accents
        $err['nomprenom'] = 'Le nom et le prénom ne sont pas Valider!r!s.';
    }*/
    elseif (mb_regex_encoding ('UTF-8') && ! mb_ereg_match("^[[:alpha:]][[:alpha:]\- ']{1,99}$", $nomprenom)) {
        $err['nomprenom'] = 'Le nom et le prénom ne sont pas Valider!r!s.';
    }

    //Verification  de la ville.

    $noTags = strip_tags($ville);
    if ($noTags != $ville){
        $err['ville'] = 'Le nom de la ville ne peut pas contenir de code HTML.';
    }else if (empty($ville)) {
        $ville = FD_INVALID_STRING;
    }else if (mb_regex_encoding ('UTF-8') && ! mb_ereg_match("^[[:alpha:]][[:alpha:]\- ']{1,99}$", $ville)) {
        $err['nomprenom'] = 'Le nom de la ville n\'est pas Valider!r!s.';
    }


    //verification de l'addresse
    $noTags = strip_tags($adresse);
    if ($noTags != $adresse){
        $err['adresse'] = 'Le nom de la ville ne peut pas contenir de code HTML.';
    }else if (empty($adresse)) {
        $adresse = FD_INVALID_STRING;
    }



    //verification du pays

    $noTags = strip_tags($pays);
    if ($noTags != $pays){
        $err['ville'] = 'Le nom de la ville ne peut pas contenir de code HTML.';
    }else if (empty($pays)) {
        $pays = FD_INVALID_STRING;
    }elseif (mb_regex_encoding ('UTF-8') && ! mb_ereg_match("^[[:alpha:]][[:alpha:]\- ']{1,99}$", $ville)) {
        $err['pays'] = 'Le pays n\'est pas Valider!r!s.';
    }



    //verification du code postale
    if(!is_numeric($cp)){
        $err['cp'] = 'Le code postal n\'est pas Valider!r!';
    }

    // vérification de la date de naissance
    if (! checkdate($naiss_m, $naiss_j, $naiss_a)) {
        $err['date'] = 'La date de naissance est incorrecte.';
    }
    else {
        $dateDuJour = getDate();
        if (($naiss_a < $dateDuJour['year'] - 100) ||
            ($naiss_a == $dateDuJour['year'] - 100 && $naiss_m < $dateDuJour['mon']) ||
            ($naiss_a == $dateDuJour['year'] - 100 && $naiss_m == $dateDuJour['mon'] && $naiss_j <= $dateDuJour['mday'])) {
            $err['date'] = 'Vous êtes trop vieux pour trainer sur BookShop.';
        }
        else if (($naiss_a > $dateDuJour['year'] - 18) ||
            ($naiss_a == $dateDuJour['year'] - 18 && $naiss_m > $dateDuJour['mon']) ||
            ($naiss_a == $dateDuJour['year'] - 18 && $naiss_m == $dateDuJour['mon'] && $naiss_j > $dateDuJour['mday'])) {
            $err['date'] = 'Votre date de naissance indique vous n\'êtes pas majeur.';
        }
    }

    if (count($err) == 0) {
        // vérification de l'unicité de l'adresse email
        // (uniquement si pas d'autres erreurs, parce que ça coûte un bras)
        $bd = fd_bd_connect();

        // pas utile, car l'adresse a déjà été vérifiée, mais tellement plus sécurisant...
        $email = fd_bd_protect($bd, $email);
        $sql = "SELECT cliID FROM clients WHERE cliEmail = '$email' AND cliID != {$_SESSION['cliID']}";

        $res = mysqli_query($bd,$sql) or fd_bd_erreur($bd,$sql);

        if (mysqli_num_rows($res) != 0) {
            $err['email'] = 'L\'adresse email spécifiée existe déjà.';
            // libération des ressources
            mysqli_free_result($res);
            mysqli_close($bd);
        }
        else{
            // libération des ressources
            mysqli_free_result($res);
        }

    }

    // s'il y a des erreurs ==> on retourne le tableau d'erreurs
    if (count($err) > 0) {
        return $err;
    }

    // pas d'erreurs ==> enregistrement de l'utilisateur
    $nomprenom = fd_bd_protect($bd, $nomprenom);
    $pass = fd_bd_protect($bd, md5($pass2));
    $aaaammjj = $naiss_a*10000  + $naiss_m*100 + $naiss_j;
    $adresse = fd_bd_protect($bd, $adresse);
    $cp = fd_bd_protect($bd, $cp);
    $ville = fd_bd_protect($bd, $ville);
    $pays = fd_bd_protect($bd, $pays);

    $sql = "UPDATE clients 
            SET cliNomPrenom='$nomprenom', cliEmail='$email',cliDateNaissance='$aaaammjj', cliPassword='$pass', cliAdresse='$adresse', 
                  cliCP='$cp', cliVille='$ville', cliPays='$pays'
            WHERE  cliID='{$_SESSION['cliID']}'";


    mysqli_query($bd, $sql) or fd_bd_erreur($bd, $sql);

    // libération des ressources
    mysqli_close($bd);
    return $err;
}

/**
 * Affiche le bouton pour acceder à la page historique des commandes.
 */
function boutonHistoriqueCommande(){
    echo '<button class="Compte" formaction="./recapitulatif.php">Historique des commandes</button>';
}


?>