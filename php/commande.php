<?php

ob_start('ob_gzhandler');
session_start();
require_once '../php/bibli_generale.php';
require_once '../php/bibli_bookshop.php';
error_reporting(E_ALL);
if(!isset($_SESSION['cliID'])){
    $_SESSION['err'] = "Veuillez vous connecter";
    fd_redirige('login.php');
}
commande_adresse_valide();
commande_contenu();
fd_redirige($_SERVER['HTTP_REFERER']);
ob_end_flush();



function commande_adresse_valide(){
    $bd = fd_bd_connect();
    $sql = "SELECT cliPays, cliVille, cliCP, cliAdresse FROM clients WHERE cliID = {$_SESSION['cliID']}";
    $res = mysqli_query($bd,$sql) or fd_bd_erreur($bd,$sql);

    while($data = mysqli_fetch_assoc($res)){
        if($data['cliAdresse'] == 'INVALID' || $data['cliVille'] == 'INVALID' || $data['cliPays'] == 'INVALID' || $data['cliCP'] == 0 ){
            $_SESSION['err'][] = "Veuillez renseigner votre adresse dans votre compte";
            mysqli_free_result($res);
            fd_redirige($_SERVER['HTTP_REFERER']);
        }
    }
    mysqli_free_result($res);
}

/**
 * Enregistre la commande dans la base de donnée.
 *
 * @global array $SESSION variable de session.
 *
 */
function commande_contenu(){
    $bd = fd_bd_connect();
    $date = date('Y').date('m').date('d');
    $time = date('H').date('i');
    $sql_commandes = "INSERT INTO commandes(coIDClient, coDate, coHeure) VALUES ({$_SESSION['cliID']}, $date, $time)";
    $sql = fd_bd_protect($bd, $sql_commandes);
    $res = mysqli_query($bd,$sql) or fd_bd_erreur($bd,$sql);


    $sql_coID = "SELECT coID FROM commandes WHERE coIDClient = {$_SESSION['cliID']} ORDER BY coID DESC LIMIT 0,1";
    $sql = fd_bd_protect($bd, $sql_coID);
    $res = mysqli_query($bd,$sql) or fd_bd_erreur($bd,$sql);
    $r = mysqli_fetch_assoc($res);
    mysqli_free_result($res);

    $sql_compo_commandes = "INSERT INTO compo_commande(ccIDCommande, ccIDLivre, ccQuantite) VALUES ";

    foreach ($_SESSION['cart'] as $key=>$value){
        $sql_compo_commandes .= "({$r['coID']}, $key, $value),";
    }
    $sql = fd_bd_protect($bd, substr($sql_compo_commandes, 0, -1));
    $res = mysqli_query($bd,$sql) or fd_bd_erreur($bd,$sql);


    mysqli_close($bd);
    unset($_SESSION['cart']);
    $_SESSION['commande']=1;
}


?>