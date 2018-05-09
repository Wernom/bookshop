<?php

ob_start('ob_gzhandler');
session_start();
require_once '../php/bibli_generale.php';
require_once '../php/bibli_bookshop.php';
error_reporting(E_ALL);

$valueQuoi = '';


if ($_GET){
	$valueQuoi = fdl_control_get ();
}

if(!isset($_SESSION['cliID'])){
    fd_redirige('login.php');
}
ms_ajout_liste_contenu();
fd_redirige($_SERVER['HTTP_REFERER']);
ob_end_flush();

/**
 * Ajoute un livre à la liste de voeux d'un client
 *
 *
 * @global  array     $_GET
 *
 */
function ms_ajout_liste_contenu(){
    $bd = fd_bd_connect();
    $sql_ajout_liste = "INSERT INTO listes(listIDClient, listIDLivre) VALUES ({$_SESSION['cliID']}, {$_GET['id']})";
    $sql = fd_bd_protect($bd, $sql_ajout_liste);
    $res = mysqli_query($bd,$sql_ajout_liste) or fd_bd_erreur($bd,$sql_ajout_liste);
    mysqli_close($bd);
    $_SESSION['listeCadeau'][$_GET['id']] = $_GET['id'];
}

/**
 *	Contrôle de la validité des informations reçues via la query string 
 *
 * En cas d'informations invalides, la session de l'utilisateur est arrêtée et il redirigé vers la page index.php
 *
 * @global  array     $_GET
 *
 * @return string     Id du livre à ajouter
 */
function fdl_control_get (){
	(count($_GET) != 1) && fd_exit_session();
	(! isset($_GET['id'])) && fd_exit_session();

    $valueQ = trim($_GET['id']);
    $notags = strip_tags($valueQ);
    (mb_strlen($notags, 'UTF-8') != mb_strlen($valueQ, 'UTF-8')) && fd_exit_session();
    
	return $valueQ;
}


?>