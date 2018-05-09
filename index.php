<?php
ob_start('ob_gzhandler'); //démarre la bufferisation, compression du tampon si le client supporte gzip
session_start();    // Lancement de la session

require_once './php/bibli_generale.php';
require_once './php/bibli_bookshop.php';

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)

fd_html_debut('BookShop | Bienvenue', './styles/bookshop.css');

fd_bookshop_enseigne_entete(isset($_SESSION['cliID']),'./');

fdl_contenu();

fd_bookshop_pied();

fd_html_fin();

ob_end_flush();


// ----------  Fonctions locales au script ----------- //

/** 
 *	Affichage du contenu de la page (i.e. contenu de l'élément section)
 */
function fdl_contenu() {
	$bd = fd_bd_connect();
	
	echo 
		'<h1>Bienvenue sur BookShop !</h1>',
		
		'<p>Passez la souris sur le logo et laissez-vous guider pour découvrir les dernières exclusivités de notre site. </p>',
		
		'<p>Nouveau venu sur BookShop ? Consultez notre <a href="./html/presentation.html">page de présentation</a> !',
	
		'<h2>Dernières nouveautés </h2>',
	
		'<p>Voici les 4 derniers articles ajoutés dans notre boutique en ligne :</p>';

	affiche_livre(requete_nouveaute($bd));

	echo 
		'<h2>Top des ventes</h2>', 
		'<p>Voici les 4 articles les plus vendus :</p>';

	affiche_livre(requete_populaire($bd));
	mysqli_close($bd);
}


/** 
 *	Affichage d'une liste de livres sous la forme de blocs
 *
 *	@param 	array 	$tLivres	tableau contenant un élément (tableau associatif) pour chaque livre (id, auteurs(nom, prenom), titre)
 */
function fdl_afficher_blocs_livres($tLivres) {

	foreach ($tLivres as $livre) {
		fd_afficher_livre($livre, 'bcArticle', './');
	}
}

/**
 * Effectue la requette vers la base de donnée pour récuperer les 4 livres les plus recents.
 * @param  object $bd connecteur à la base de données.
 * @return bool|mysqli_result résultat de la requette si elle à réussi?
 */
function requete_nouveaute($bd){
    $sql = 	'SELECT liID, liTitre, auNom, auPrenom
			FROM livres INNER JOIN editeurs ON liIDEditeur = edID 
						INNER JOIN aut_livre ON al_IDLivre = liID 
						INNER JOIN auteurs ON al_IDAuteur = auID
			ORDER BY liID DESC';
    $res = mysqli_query($bd, $sql) or fd_bd_erreur($bd,$sql);

	return $res;
}

function requete_populaire($bd){
	$sql = 'SELECT liID, liTitre, auNom, auPrenom FROM livres INNER JOIN editeurs ON liIDEditeur = edID INNER JOIN aut_livre ON al_IDLivre = liID INNER JOIN auteurs ON al_IDAuteur = auID INNER JOIN (SELECT ccIDLivre FROM compo_commande GROUP BY ccIDLivre ORDER BY SUM(ccQuantite) DESC) AS T ON ccIDLivre = liID';
    $res = mysqli_query($bd, $sql) or fd_bd_erreur($bd,$sql);

    return $res;
}

/**
 * Affiche les livres
 * @param mysqli_result $resSQL Résultat de la fonction mysqli_query.
 */
function affiche_livre ($resSQL){
    $livre=array();
    $lastID = -1;
    $i=0;
    while ($t = mysqli_fetch_assoc($resSQL)) {
        if ($t['liID'] != $lastID) {
            if ($lastID != -1) {
                ++$i;
                fd_afficher_livre($livre, 'bcArticle', './');
            }
            $lastID = $t['liID'];
            $livre = array(	'id' => $t['liID'],
                'titre' => $t['liTitre'],
                'auteurs' => array(array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']))
            );
        }
        else {
            $livre['auteurs'][] = array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']);
        }
        if($i>2)break;
    }
    fd_afficher_livre($livre, 'bcArticle', './');
    mysqli_free_result($resSQL);
}


	
?>
