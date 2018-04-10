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
	
	echo 
		'<h1>Bienvenue sur BookShop !</h1>',
		
		'<p>Passez la souris sur le logo et laissez-vous guider pour découvrir les dernières exclusivités de notre site. </p>',
		
		'<p>Nouveau venu sur BookShop ? Consultez notre <a href="./html/presentation.html">page de présentation</a> !',
	
		'<h2>Dernières nouveautés </h2>',
	
		'<p>Voici les 4 derniers articles ajoutés dans notre boutique en ligne :</p>';
	$_SESSION['articles'] = array();
	$derniersAjouts = array(
		array(	'id' => 42, 
				'auteurs' => array(	array('prenom' => 'George', 'nom' => 'Orwell')), 
				'titre'   => '1984'),
		array(	'id' => 41, 
				'auteurs' => array(	array('prenom' => 'Robert', 'nom' => 'Kirkman'),
									array('prenom' => 'Charlie', 'nom' => 'Adlard')), 
				'titre' => 'The Walking Dead - T16 Un vaste monde'),
		array(	'id' => 40, 
				'auteurs' => array(	array('prenom' => 'Ray', 'nom' => 'Bradbury')), 
				'titre'   => 'L\'homme illustré'),	  
		array(	'id' => 39, 
				'auteurs' => array(	array('prenom' => 'Alan', 'nom' => 'Moore'),
									array('prenom' => 'David', 'nom' => 'Lloyd')), 
				'titre' => 'V pour Vendetta'),  
			  ); 

	fdl_afficher_blocs_livres($derniersAjouts);
	
	echo 
		'<h2>Top des ventes</h2>', 
		'<p>Voici les 4 articles les plus vendus :</p>';
	
	$meilleursVentes = array(
		array(	'id' => 20, 
				'auteurs' => array(	array('prenom' => 'Alan', 'nom' => 'Moore'),
									array('prenom' => 'Dave', 'nom' => 'Gibbons')),
				'titre'   => 'Watchmen'),
		array(	'id' => 39, 
				'auteurs' => array(	array('prenom' => 'Alan', 'nom' => 'Moore'),
									array('prenom' => 'David', 'nom' => 'Lloyd')), 
				'titre' => 'V pour Vendetta'), 
		array(	'id' => 27, 
				'auteurs' => array(	array('prenom' => 'Robert', 'nom' => 'Kirkman'),
									array('prenom' => 'Jay', 'nom' => 'Bonansinga')), 
				'titre' => 'The Walking Dead - La route de Woodbury'),
		array(	'id' => 34, 
				'auteurs' => array(	array('prenom' => 'Aldous', 'nom' => 'Huxley')), 
				'titre'   => 'Le meilleur des mondes'),	  
		 
			  ); 
	
	fdl_afficher_blocs_livres($meilleursVentes);	
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
	
?>
