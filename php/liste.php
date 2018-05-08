<?php

ob_start('ob_gzhandler'); //démarre la bufferisation, compression du tampon si le client supporte gzip
session_start();    // Lancement de la session
require_once '../php/bibli_generale.php';
require_once '../php/bibli_bookshop.php';

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)

$nbLivre = 0;

($_GET && $_POST) && fd_exit_session();

if ($_GET){
	$nbLivre = ms_control_get ();
}

fd_html_debut('BookShop | Liste des Voeux', '../styles/bookshop.css');


fd_bookshop_enseigne_entete(isset($_SESSION['cliID']),'../');
echo '<h1>Liste des Voeux</h1>';

if($nbLivre == 0){
	ms_recup_liste();
}

ms_afficher_liste($_SESSION['Liste'], 'Liste', '../', $nbLivre);


fd_bookshop_pied();

fd_html_fin();

ob_end_flush();

// ----------  Fonctions locales au script ----------- //

/**
 *	Récupère la liste de voeux d'un client et la met dans la variable globale $_SESSION['Liste']
 *
 * @session  array     $_SESSION
 */
function ms_recup_liste() {
	
	$bd = fd_bd_connect();
	$valueID = $_SESSION['cliID'];
	$q = fd_bd_protect($bd, $valueID); 
	$sql = 	"SELECT liID, liTitre, liPrix, liPages, liResume, edNom, auNom, auPrenom 
			FROM livres INNER JOIN editeurs ON liIDEditeur = edID 
						INNER JOIN listes ON listIDLivre = liID
						INNER JOIN aut_livre ON al_IDLivre = liID 
						INNER JOIN auteurs ON al_IDAuteur = auID
						
			WHERE listIDClient = $valueID";

	$res = mysqli_query($bd, $sql) or fd_bd_erreur($bd,$sql);

	$lastID = -1;
	$NbLivre = 0;
	$_SESSION['Liste'] = array();
	while ($t = mysqli_fetch_assoc($res)) {
		if ($t['liID'] != $lastID) {
			$lastID = $t['liID'];
			$_SESSION['Liste'][$NbLivre] = array('id' => $t['liID'], 
							'titre' => $t['liTitre'],
							'edNom' => $t['edNom'],
							'resume' => $t['liResume'],
							'pages' => $t['liPages'],
							'prix' => $t['liPrix'],
							'auteurs' => array(array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']))
						);
			++$NbLivre;	
		}
		else {
			$_SESSION['Liste'][$NbLivre]['auteurs'][] = array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']);
		}		
	}
    // libération des ressources
	mysqli_free_result($res);
	mysqli_close($bd);   		
}



/**
 *	Affichage d'un livre.
 *
 *	@param	array		$livre 		tableau associatif des infos sur un livre (id, auteurs(nom, prenom), titre, prix, pages, ISBN13, resumé, edWeb, edNom)
 *	@param 	string 		$class		classe de l'élement div 
 *  @param 	String		$prefix		Prefixe des chemins vers le répertoire images (usuellement "./" ou "../")
 *  @session  array     $_SESSION
 */
function ms_afficher_liste($livre, $class, $prefix, $nbLivre) {
	if(count($_SESSION['Liste']) == 0){
		echo 'Aucun Livre à afficher';
		return;
	}
	$count = 1;
	echo '<div class="', $class, '">';
	foreach($livre as $key => $data){
		if($key < $nbLivre){
			continue;
		}
		if(($count % 3) == 1){
			echo
				'<div>';
		}
		ms_afficher_livre($data, $class, $prefix, $nbLivre, $count);
		if(($count % 3) == 0 ){
			echo
				'</div>';
		}
		if($count == 15){
			break;
		}
		++$count;
	}
	if($nbLivre % 3 != 0){
				echo 
					'</div>';
	}
			echo 
				'</div>';
}

function ms_afficher_livre($livre, $class, $prefix, $nbLivre, $count){
	echo 
	'<div id="', $count,'">',
		'<img src="', $prefix, 'images/livres/', $livre['id'], '.jpg" alt="', 
		fd_protect_sortie($livre['titre']),'">',
		'<a class="addToCart" href="',$prefix,'php/ajout_panier.php?id=',$livre['id'],'" title="Ajouter au panier"></a>',
		'<span>',
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
		echo 
			'<br>',
			'Editeur : <a class="lienExterne" href="http://', fd_protect_sortie($livre['edWeb']), '" target="_blank">', fd_protect_sortie($livre['edNom']), '</a><br>',
			'Prix : ', $livre['prix'], ' &euro;<br>';
	echo
		'</span>',
	'</div>';
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
	
	(! isset($_GET['nbListe'])) && fd_exit_session();
	
    $valueQ = trim($_GET['nbListe']);
    (! is_numeric($valueQ)) && fd_exit_session(); 
    
    $notags = strip_tags($valueQ);
    (mb_strlen($notags, 'UTF-8') != mb_strlen($valueQ, 'UTF-8')) && fd_exit_session();
  
	return $valueQ;
}

?>
