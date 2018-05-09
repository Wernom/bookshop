<?php

ob_start('ob_gzhandler'); //démarre la bufferisation, compression du tampon si le client supporte gzip
session_start();    // Lancement de la session
require_once '../php/bibli_generale.php';
require_once '../php/bibli_bookshop.php';

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)

if (!isset($_SESSION['cliID'])){
    fd_redirige('../index.php');
}

$idClient = $_SESSION['cliID'];
$clientConnecte = TRUE;//pour savoir s'il s'agit de la liste de l'utilisateur connecté ou s'il s'agit d'une recherche
$nbPage = 1;//Initialisation à la première page

if ($_GET){
	$nbPage = msls_control_get_livre();
	$valueID = msls_control_get_delete();
	msls_delete($valueID);
}

if ($_POST){
	$valueMail = msls_control_post_client();
	$idClient = msls_verification_mail($valueMail);
}

fd_html_debut('BookShop | Liste de cadeaux', '../styles/bookshop.css');


fd_bookshop_enseigne_entete(isset($_SESSION['cliID']),'../');

if($idClient == -1){
	echo
		'<p class="erreur">',
			'Aucune adresse mail correspondant à votre recherche',
		'</p>';
	$idClient = $_SESSION['cliID'];
}
if($nbPage == 1){
	ms_recup_liste($idClient);
}

if($idClient == $_SESSION['cliID']){
	echo '<h1>Votre liste de cadeaux</h1>';
}else{
	echo '<h1>Liste de voeux de votre recherche</h1>'; //on ne met pas le nom pour ne pas donner des informations sur nos utilisateurs.
	$clientConnecte = FALSE;//indique qu'il ne s'agit pas de la liste de cadeau de l'utilisateur connecté
}

ms_afficher_liste($_SESSION['Liste'], 'Liste', '../', $nbPage, $clientConnecte, $idClient);


fd_bookshop_pied();

fd_html_fin();

ob_end_flush();

// ----------  Fonctions locales au script ----------- //

/**
 *	Récupère la liste de voeux d'un client et la met dans la variable globale $_SESSION['Liste']
 *
 * 	@param		int		$idClient	l'identifiant du client connecté ou du client recherché
 *  @session  	array   $_SESSION
 */
function ms_recup_liste($idClient) {
	
	$bd = fd_bd_connect();
	$sql = 	"SELECT liID, liTitre, liPrix, liPages, liResume, edWeb, edNom, auNom, auPrenom 
			FROM livres INNER JOIN editeurs ON liIDEditeur = edID 
						INNER JOIN listes ON listIDLivre = liID
						INNER JOIN aut_livre ON al_IDLivre = liID 
						INNER JOIN auteurs ON al_IDAuteur = auID
						
			WHERE listIDClient = $idClient";

	$res = mysqli_query($bd, $sql) or fd_bd_erreur($bd,$sql);

	$lastID = -1;
	$_SESSION['Liste'] = array();//TODO expliquer pour la pagination
	while ($t = mysqli_fetch_assoc($res)) {
		if ($t['liID'] != $lastID) {
			$lastID = $t['liID'];
			$_SESSION['Liste'][$lastID] = array('id' => $t['liID'], 
							'titre' => $t['liTitre'],
							'edNom' => $t['edNom'],
							'edWeb' => $t['edWeb'],
							'resume' => $t['liResume'],
							'pages' => $t['liPages'],
							'prix' => $t['liPrix'],
							'auteurs' => array(array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']))
						);
		}
		else {
			$_SESSION['Liste'][$lastID]['auteurs'][] = array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']);
		}		
	}
    // libération des ressources
	mysqli_free_result($res);
	mysqli_close($bd);   		
}



/**
 *	Affichage de la liste de voeux d'un utilisateur
 *
 *	@param	array		$livre 		tableau associatif des infos sur un livre (id, auteurs(nom, prenom), titre, prix, pages, ISBN13, resumé, edWeb, edNom)
 *	@param 	string 		$class		classe de l'élement div 
 *  @param 	String		$prefix		Prefixe des chemins vers le répertoire images (usuellement "./" ou "../")
 *  @session  array     $_SESSION
 */
function ms_afficher_liste($livre, $class, $prefix, $nbPage, $clientConnecte, $idClient) {
	if(count($_SESSION['Liste']) == 0){
		if($clientConnecte){
			echo '<h3>Vous n\'avez pas de livre dans votre liste</h3>';
		}else{
			echo '<h3>L\'utilisateur n\'a pas de livre dans sa liste</h3>';
		}
		return;
	}
	$count = 0;
	$nbLivre = 0;
	echo '<div class="', $class, '">';
	foreach($livre as $data){
		if($nbLivre < 15*($nbPage-1)){
			++$nbLivre;
			continue;
		}
		++$count;
		if(($count % 3) == 1){
			echo
				'<div>';
		}
		ms_afficher_livre($data, $class, $prefix, $nbPage, $clientConnecte);
		if(($count % 3) == 0 ){
			echo
				'</div>';
		}
		if($count == 15){
			break;
		}
	}
	if($count % 3 != 0){
			echo 
				'</div>';
	}
	if(15*($nbPage-1) + $count < count($livre)){
		echo
			'<a href="', $prefix, 'php/liste.php?nbListe=', $nbPage+1, '&cliID=', $idClient,'" ><img id="droite" src="', $prefix, 'images/ajouts/suivant.jpg" alt="suivant" height="35" width="30"></a>';
	}
	if($nbPage > 1){
		echo 
		'<a href="', $prefix, 'php/liste.php?nbListe=', $nbPage-1 , '&cliID=', $idClient,'" ><img id="gauche" src="', $prefix, 'images/ajouts/precedent.jpg" alt="precedent" height="35" width="30"></a>';
	}

	echo '<form action="liste.php" method="post">',
			'<p class="centered">Rechercher par adresse e-mail <input type="text" name="email" value=" ">', 
			'<input type="submit" value="Rechercher" name="btnRechercher"></p></form></div>';
}

/**
 *	Affichage d'un livre dans la liste des voeux d'un utilisateur
 *
 *	@param	array		$livre 		tableau associatif des infos sur un livre (id, auteurs(nom, prenom), titre, prix, pages, ISBN13, resumé, edWeb, edNom)
 *	@param 	string 		$class		classe de l'élement div 
 *  @param 	String		$prefix		Prefixe des chemins vers le répertoire images (usuellement "./" ou "../")	
 * 	@session  array     $_SESSION
 */
function ms_afficher_livre($livre, $class, $prefix, $nbPage, $clientConnecte){
	echo 
		'<div>',
			'<a href="', $prefix, 'php/details.php?article=', $livre['id'], '" title="Voir détails">',
			'<img src="', $prefix, 'images/livres/', $livre['id'], '.jpg" alt="', 
			fd_protect_sortie($livre['titre']),'">',
			'</a>',
			'<a class="addToCart" href="',$prefix,'php/ajout_panier.php?id=',$livre['id'],'" title="Ajouter au panier"></a>';
		if($clientConnecte){
			echo
			'<a class="delete" href="',$prefix,'php/liste.php?nbListe=',$nbPage,'&liID=',$livre['id'],'" title="Supprimer de la liste"></a>';
		}
			echo
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
 * @global  array	$_GET
 *
 * @return	int		L'ID du livre à afficher            
 */
function msls_control_get_livre(){
	(count($_GET) > 2) && fd_exit_session();

	if(isset($_GET['nbListe'])){
		$valueL = trim($_GET['nbListe']);
		(! is_numeric($valueL)) && fd_exit_session(); 
		
		$notags = strip_tags($valueL);
		(mb_strlen($notags, 'UTF-8') != mb_strlen($valueL, 'UTF-8')) && fd_exit_session();

		return $valueL;
	}
	
	!isset($_GET['liID']) && fd_exit_session();
	
	return 0;
}


/**
 *	Contrôle de la validité des informations reçues via la query string 
 *
 * En cas d'informations invalides, la session de l'utilisateur est arrêtée et il est redirigé vers la page index.php
 *
 * @global  array	$_GET
 *
 * @return	int		L'ID du livre à enlever de la liste ou -1 si rien à supprimer           
 */
function msls_control_get_delete(){
	if(!isset($_GET['liID']) || !isset($_SESSION['Liste'])){
		return -1;
	}

	$valueID = trim($_GET['liID']);
    (!is_numeric($valueID)) && fd_exit_session(); 
    $notags = strip_tags($valueID);
	(mb_strlen($notags, 'UTF-8') != mb_strlen($valueID, 'UTF-8')) && fd_exit_session();

	return $valueID;
}

/**
 *	Supprime de la liste de cadeau le livre d'ID $valueID
 *
 *
 * @param	int		L'ID du livre à enlever de la liste ou -1 si rien à supprimer  
 * @global  array	$_GET
 * @session array	$_SESSION
 *  
 */
function msls_delete($valueID){
	if($valueID == -1){
		return;
	}

	$bd = fd_bd_connect();
	$idClient = $_SESSION['cliID'];
	if (isset($_SESSION['listeCadeau'][$valueID])) {
		$sql = "DELETE FROM listes
				WHERE listIDLivre = $valueID
				AND listIDClient = $idClient";
		$res = mysqli_query($bd, $sql) or fd_bd_erreur($bd,$sql);
		ms_recup_liste($_SESSION['cliID']);
	}
	mysqli_close($bd);
	unset($_SESSION['listeCadeau'][$valueID]);
}

/**
 *	Contrôle de la validité des informations reçues via la query string 
 *
 * En cas d'informations invalides, la session de l'utilisateur est arrêtée et il est redirigé vers la page index.php
 *
 * @global  array	$_POST
 *
 * @return	string	l'adresse mail de l'utilisateur recherché           
 */
function msls_control_post_client(){
	(count($_POST) != 2) && fd_exit_session();
	(! isset($_POST['btnRechercher']) || $_POST['btnRechercher'] != 'Rechercher') && fd_exit_session();
	(! isset($_POST['email'])) && fd_exit_session();
    $valueMail = trim($_POST['email']);
    $notags = strip_tags($valueMail);
	(mb_strlen($notags, 'UTF-8') != mb_strlen($valueMail, 'UTF-8')) && fd_exit_session();
	return $valueMail;
}


/**
 *	Contrôle la validité de l'adresse mail recherché
 *
 *
 * @param   string	$valueMail		l'adresse mail de l'utilisateur recherché 
 * @return	int		$cliID			l'id de l'utilisateur si l'adresse mail est valide, -1 sinon	          
 */
function msls_verification_mail($valueMail){
	$cliID = -1;
	if($valueMail == ''){
		return -1;
	}
	$bd = fd_bd_connect();
	$sql = "SELECT cliID, cliEmail
			FROM clients";
	$res = mysqli_query($bd, $sql) or fd_bd_erreur($bd,$sql);
	while($t = mysqli_fetch_assoc($res)){
		if(strcmp($t['cliEmail'], $valueMail) == 0){
			echo 'salut';
			$cliID = $t['cliID'];
		}
	}
	mysqli_free_result($res);
	mysqli_close($bd);

    return $cliID;
}

?>
