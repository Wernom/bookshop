<?php
ob_start('ob_gzhandler'); //démarre la bufferisation, compression du tampon si le client supporte gzip
session_start();    // Lancement de la session

require_once '../php/bibli_generale.php';
require_once '../php/bibli_bookshop.php';

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)
     
$valueID = '';

($_GET && $_POST) && fd_exit_session();

if ($_GET){
	$valueID = ms_control_get ();
}

fd_html_debut('BookShop | Article', '../styles/bookshop.css');

fd_bookshop_enseigne_entete(isset($_SESSION['cliID']),'../');

ms_contenu($valueID);

fd_bookshop_pied();

fd_html_fin();

ob_end_flush();


// ----------  Fonctions locales au script ----------- //

/**
 *	Contenu de la page : Photo du livre + Détails + Résumé
 *
 * @param    string    $valueID   L'ID de l'article à afficher
 * @global   array     $_GET
 * @session  array     $_SESSION
 */
function ms_contenu($valueID) {
	
	if($valueID == ''){
		echo '<p><strong>Aucun livre à afficher</strong></p>';
		return; // ===> Fin de la fonction
	}

	// ouverture de la connexion, requête
	$bd = fd_bd_connect();
	
	$q = fd_bd_protect($bd, $valueID); 

	$sql = 	"SELECT liID, liTitre, liPrix, liPages, liISBN13, liResume, edNom, edWeb, auNom, auPrenom 
			FROM livres INNER JOIN editeurs ON liIDEditeur = edID 
						INNER JOIN aut_livre ON al_IDLivre = liID 
						INNER JOIN auteurs ON al_IDAuteur = auID 
			WHERE liID = $valueID";

	$res = mysqli_query($bd, $sql) or fd_bd_erreur($bd,$sql);

	$nbAuteur = 1;
	while ($t = mysqli_fetch_assoc($res)) {
		if ($nbAuteur == 1) {
			++$nbAuteur;
			$livre = array(	'id' => $t['liID'], 
							'titre' => $t['liTitre'],
							'edNom' => $t['edNom'],
							'edWeb' => $t['edWeb'],
							'resume' => $t['liResume'],
							'pages' => $t['liPages'],
							'ISBN13' => $t['liISBN13'],
							'prix' => $t['liPrix'],
							'auteurs' => array(array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']))
						);
		}
		else {
			$livre['auteurs'][] = array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']);
		}		
	}
    // libération des ressources
	mysqli_free_result($res);
	mysqli_close($bd);
   	ms_afficher_detail($livre, 'Detail', '../');	
}

/**
 *	Contrôle de la validité des informations reçues via la query string 
 *
 * En cas d'informations invalides, la session de l'utilisateur est arrêtée et il est redirigé vers la page index.php
 *
 * @global  array     $_GET
 *
 * @return            L'ID du livre à afficher            
 */
function ms_control_get (){
	(count($_GET) != 1) && fd_exit_session();
	(! isset($_GET['article'])) && fd_exit_session();
    $valueQ = trim($_GET['article']);
    $notags = strip_tags($valueQ);
    (mb_strlen($notags, 'UTF-8') != mb_strlen($valueQ, 'UTF-8')) && fd_exit_session();
    
	return $valueQ;
}

/**
 *	Affichage d'un livre.
 *
 *	@param	array		$livre 		tableau associatif des infos sur un livre (id, auteurs(nom, prenom), titre, prix, pages, ISBN13, resumé, edWeb, edNom)
 *	@param 	string 		$class		classe de l'élement div 
 *  @param 	String		$prefix		Prefixe des chemins vers le répertoire images (usuellement "./" ou "../").
 */
function ms_afficher_detail($livre, $class, $prefix) {
	echo  
		'<div class="', $class, '">', 
			'<h1>', $livre['titre'], '</h1>', 
			'<div>',
				'<img src="', $prefix, 'images/livres/', $livre['id'], '.jpg" alt="', 
				fd_protect_sortie($livre['titre']),'">',
				'<br>',
				'<div>',
					'<a class="addToCart" href="',$prefix,'php/ajout_panier.php?id=',$livre['id'],'" title="Ajouter au panier"></a>';
			if(!ms_isset_liste($livre['id'])){
				echo
					'<a class="addToWishlist" href="',$prefix,'php/ajout_liste.php?id=',$livre['id'],'" title="Ajouter à la liste de cadeaux"></a>';

			}
				echo
					'Détails du livre : <br><br>',
					'<strong>', fd_protect_sortie($livre['titre']), '</strong><br>';
				$i = 0;
				foreach ($livre['auteurs'] as $auteur) {
					$supportLien = $class == 'bcResultat' ? "{$auteur['prenom']} {$auteur['nom']}" : "{$auteur['prenom']{0}}. {$auteur['nom']}";
					if ($i > 0) {
						echo ', ';
					}
					$i++;
					echo '<a href="', $prefix, 'php/recherche.php?type=auteur&quoi=', urlencode($auteur['nom']), '">',fd_protect_sortie($supportLien), '</a>';
				}	
				echo	'<br>',
					'Editeur : <a class="lienExterne" href="http://', fd_protect_sortie($livre['edWeb']), '" target="_blank">', fd_protect_sortie($livre['edNom']), '</a><br>',
					'Prix : ', $livre['prix'], ' &euro;<br>',
					'Pages : ', $livre['pages'], '<br>',
					'ISBN13 : ', fd_protect_sortie($livre['ISBN13']), '<br>',
				'</div>',
			'</div>',
			'<p>Resumé : <br>', fd_protect_sortie($livre['resume']), '</p>';
	if(!isset($_SESSION['article'])){
		echo '<div>';
		$nb_articles = count($_SESSION['articles']);
		foreach($_SESSION['articles'] as $key => $articles){
			if($articles == $livre['id']){
				if($key != 0){
					echo
						'<a href="', $prefix, 'php/details.php?article=', $_SESSION['articles'][($key-1)] , '" ><img id="gauche" src="', $prefix, 'images/ajouts/precedent.jpg" alt="precedent" height="35" width="30"></a>';
				}if($key != $nb_articles - 1){
					echo
						'<a href="', $prefix, 'php/details.php?article=', $_SESSION['articles'][($key+1)] , '" ><img id="droite" src="', $prefix, 'images/ajouts/suivant.jpg" alt="suivant" height="35" width="30"></a>';
				}
			}
		}
		echo '</div>';
	}
	echo
		'</div>';
}

?>