<?php
/**
 * Created by PhpStorm.
 * User: wernom
 * Date: 15/04/18
 * Time: 17:57
 */


ob_start('ob_gzhandler');
session_start();
require_once '../php/bibli_generale.php';
require_once '../php/bibli_bookshop.php';
error_reporting(E_ALL);
if(!isset($_SESSION['cliID'])){
    fd_redirige('login.php');
}
commande_contenu($_SESSION['cliID']);
fd_redirige($_SERVER['HTTP_REFERER']);
ob_end_flush();

/**
 * Enregistre la commande dans la base de donnée.
 *
 * @param  int $id identifiant du livre
 */
function commande_contenu($id){
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