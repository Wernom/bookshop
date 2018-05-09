<?php

/*********************************************************
 *        Bibliothèque de fonctions spécifiques          *
 *               à l'application BookShop                *
 *********************************************************/

 
 // constantes utilisées pour initialiser certains champs de la table client lors de l'inscription d'un utilisateur
 define('FD_INVALID_STRING', 'INVALID'); //utilisé pour les champs adresse, ville et pays
 define('FD_INVALID_CODE_POSTAL', 0); //utilisé pour le code postal
 
 // Nombre d'années affichées pour la date de naissance du formulaire d'inscription 
 define('NB_ANNEES_DATE_NAISSANCE', 121);

/**
 *	Fonction affichant le canevas général de l'application BookShop 
 *
 *	Affiche bloc page, entête et menu de navigation, enseigne, ouverture du bloc de contenu.
 *
 *  @param 	boolean		$connecte	Indique si l'utilisateur est connecté ou non.
 *	@param 	string		$prefix		Prefixe des chemins vers les fichiers du menu (usuellement "./" ou "../").
 */
function fd_bookshop_enseigne_entete($connecte,$prefix) {
	echo 
		'<div id="bcPage">',
	
		'<aside>',
			'<a href="http://www.facebook.com" target="_blank"></a>',
			'<a href="http://www.twitter.com" target="_blank"></a>',
			'<a href="http://plus.google.com" target="_blank"></a>',
			'<a href="http://www.pinterest.com" target="_blank"></a>',
		'</aside>',
		
		'<header>';
	
	fd_bookshop_menu($connecte,$prefix);
	echo 	'<img src="', $prefix,'images/soustitre.png" alt="sous titre">',
		'</header>',
		'<section>';
}


/**
 *	Fonction affichant le menu de navigation de l'application BookShop 
 *
 *  @param 	boolean		$connecte	Indique si l'utilisateur est connecté ou non.
 *	@param 	string		$prefix		Prefixe des chemins vers les fichiers du menu (usuellement "./" ou "../").
 */
function fd_bookshop_menu($connecte, $prefix) {		
	echo 
		'<nav>',	
			'<a href="', $prefix, 'index.php"></a>';
	
	$liens = array( 'recherche' => array( 'pos' => 1, 'title' => 'Effectuer une recherche'),
					'panier' => array( 'pos' => 2, 'title' => 'Voir votre panier'),
					'liste' => array( 'pos' => 3, 'title' => 'Voir une liste de cadeaux'),
					'compte' => array( 'pos' => 4, 'title' => 'Consulter votre compte'),
					'deconnexion' => array( 'pos' => 5, 'title' => 'Se déconnecter'));
					
	if (!$connecte){
		unset($liens['compte']);
		unset($liens['deconnexion']);
		$liens['recherche']['pos']++;
		$liens['panier']['pos']++;
		$liens['liste']['pos']++;
		/*TODO : 	- peut-on implémenter les 3 incrémentations ci-dessus avec un foreach ? */
		$liens['login'] = array( 'pos' => 5, 'title' => 'Se connecter');
		/* Debug :
		echo '<pre>', print_r($liens, true), '</pre>';
		exit;*/
	}
	
	foreach ($liens as $cle => $elt) {
		echo
			'<a class="lienMenu position', $elt['pos'], '" href="', $prefix, 'php/', $cle, '.php" title="', $elt['title'], '"></a>';
	}
	echo '</nav>';
}


/**
 *	Fonction affichant le pied de page de l'application BookShop.
 */
function fd_bookshop_pied() {
	echo 
		'</section>', // fin de la section
		'<footer>', 
			'BookShop &amp; Partners &copy; ', date('Y'), ' - ',
			'<a href="apropos.html">A propos</a> - ',
			'<a href="confident.html">Emplois @ BookShop</a> - ',
			'<a href="conditions.html">Conditions d\'utilisation</a>',
		'</footer>',
	'</div>'; // fin bcPage
}


/**
 *	Affichage d'un livre.
 *
 *	@param	array		$livre 		tableau associatif des infos sur un livre (id, auteurs(nom, prenom), titre, prix, pages, ISBN13, edWeb, edNom)
 *	@param 	string 		$class		classe de l'élement div  : bcResultat ou bcArticle
 *  @param 	String		$prefix		Prefixe des chemins vers le répertoire images (usuellement "./" ou "../").
 */
function fd_afficher_livre($livre, $class, $prefix) {
	echo 
		'<div class="', $class, '">',
			'<a class="addToCart" href="',$prefix,'php/ajout_panier.php?id=',$livre['id'],'" title="Ajouter au panier"></a>',
			'<a class="addToWishlist" href="#" title="Ajouter à la liste de cadeaux"></a>',
			'<a href="', $prefix, 'php/details.php?article=', $livre['id'], '" title="Voir détails"><img src="', $prefix, 'images/livres/', $livre['id'], '_mini.jpg" alt="', 
			fd_protect_sortie($livre['titre']),'"></a>';
	if ($class == 'bcResultat'){
		echo	'<strong>', fd_protect_sortie($livre['titre']), '</strong> <br>',
			'Ecrit par : ';
	}
	elseif($class == 'bcArticle'){
		echo '<br>';
	}
	$i = 0;
	foreach ($livre['auteurs'] as $auteur) {
		$supportLien = $class == 'bcResultat' ? "{$auteur['prenom']} {$auteur['nom']}" : "{$auteur['prenom']{0}}. {$auteur['nom']}";
		if ($i > 0) {
			echo ', ';
		}
		$i++;
		echo '<a href="', $prefix, 'php/recherche.php?type=auteur&quoi=', urlencode($auteur['nom']), '">',fd_protect_sortie($supportLien), '</a>';
	}
	if ($class == 'bcResultat'){		
		echo	'<br>Editeur : <a class="lienExterne" href="http://', fd_protect_sortie($livre['edWeb']), '" target="_blank">', fd_protect_sortie($livre['edNom']), '</a><br>',
				'Prix : ', $livre['prix'], ' &euro;<br>',
				'Pages : ', $livre['pages'], '<br>',
				'ISBN13 : ', fd_protect_sortie($livre['ISBN13']), '</div>';
	}
	elseif($class == 'bcArticle'){
		echo 
			'<br>', 
			'<strong>', fd_protect_sortie($livre['titre']), '</strong>',
		  '</div>';
	}
}

/** 
 *	Renvoie un tableau contenant les pages du site bookshop
 *
 * 	@return array pages du site
 */
function get_pages_bookshop() {
	return array('index.php', 'login.php', 'inscription.php', 'deconnexion.php', 'recherche.php', 'presentation.html', 'panier.php', 'article.php', 'compte.php', 'recapitulatif.php', 'liste.php');
}



?>
