<?php
//Cette page ajoute un seul article au panier.
ob_start('ob_gzhandler');
session_start();
require_once '../php/bibli_generale.php';
require_once '../php/bibli_bookshop.php';
print_r2($_GET);
error_reporting(E_ALL);

control_get();
if(isset($_SESSION['cart'][$_GET['id']]) && $_GET['quantite'] < 1){
   unset($_SESSION['cart'][$_GET['id']]);
}else if(isset($_SESSION['cart'][$_GET['id']])){
    $_SESSION['cart'][$_GET['id']] = $_GET['quantite'];
}else{
    $_SESSION['cart'][$_GET['id']] = 1;
}

fd_redirige($_SERVER['HTTP_REFERER']);
ob_end_flush();

/**
 *	Contrôle de la validité des informations reçues via la query string
 * En cas d'informations invalides, la session de l'utilisateur est arrêtée et il est redirigé vers la page index.php
 *
 * @global  array     $_GET
 * @return string           partie du nom de l'auteur à rechercher
 */
function control_get (){
    (count($_GET) != 3) && fd_exit_session();
    ! isset($_GET['id']) && fd_exit_session();
    (!isset($_GET['valide']) || $_GET['valide'] != 'V') && fd_exit_session();
    !isset($_GET['id']) && fd_exit_session();


    $valueQ = trim($_GET['id']);
    $notags = strip_tags($valueQ);
    (mb_strlen($notags, 'UTF-8') != mb_strlen($valueQ, 'UTF-8')) && fd_exit_session();

    return $valueQ;
}


?>