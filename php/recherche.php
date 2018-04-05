<?php
ob_start('ob_gzhandler'); //démarre la bufferisation, compression du tampon si le client supporte gzip
session_start();    // Lancement de la session

require_once '../php/bibli_generale.php';
require_once '../php/bibli_bookshop.php';

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)
     
$valueType = 'auteur';
$valueQuoi = '';

($_GET && $_POST) && fd_exit_session();

if ($_GET){
	$valueQuoi = fdl_control_get ();
}
else if ($_POST){
	$valueQuoi = fdl_control_post ($valueType);
}

fd_html_debut('BookShop | Recherche', '../styles/bookshop.css');

fd_bookshop_enseigne_entete(isset($_SESSION['cliID']),'../');

fdl_contenu($valueType, $valueQuoi);

fd_bookshop_pied();

fd_html_fin();

ob_end_flush();


// ----------  Fonctions locales au script ----------- //

/**
 *	Contenu de la page : formulaire de recherche + résultats éventuels 
 *
 * @param   string    $valueType type de recherche (auteur ou titre)
 * @param   string    $valueQuoi partie du nom de l'auteur ou du titre à rechercher
 * @global  array     $_POST
 * @global  array     $_GET
 */
function fdl_contenu($valueType, $valueQuoi) {
	
	echo '<h3>Recherche par une partie du nom d\'un auteur ou du titre</h3>'; 
	
	/** 3ème version : version "formulaire de recherche" */
	echo '<form action="recherche.php" method="post">',
			'<p class="centered">Rechercher <input type="text" name="quoi" value="', fd_protect_sortie($valueQuoi), '">', 
			' dans ', 
				'<select name="type">', 
					'<option value="auteur" ', $valueType == 'auteur' ? 'selected' : '', '>auteurs</option>', 
					'<option value="titre" ', $valueType == 'titre' ? 'selected' : '','>titre</option>', 
				'</select>', 
			'<input type="submit" value="Rechercher" name="btnRechercher"></p></form>'; 
	
	if (! $_GET && ! $_POST){
        return; // ===> Fin de la fonction (ni soumission du formulaire, ni query string)
    }
	if ( mb_strlen($valueQuoi, 'UTF-8') < 2){
        echo '<p><strong>Le mot recherché doit avoir une longueur supérieure ou égale à 2</strong></p>';
		return; // ===> Fin de la fonction
	}
	
	// affichage des résultats
	
	// ouverture de la connexion, requête
	$bd = fd_bd_connect();
	
	$q = fd_bd_protect($bd, $valueQuoi); 
	
	if ($valueType == 'auteur') {
        $critere = " WHERE liID in (SELECT al_IDLivre FROM aut_livre INNER JOIN auteurs ON al_IDAuteur = auID WHERE auNom LIKE '%$q%')";
	} 
	else {
		$critere = " WHERE liTitre LIKE '%$q%'";	
	}

	$sql = 	"SELECT liID, liTitre, liPrix, liPages, liISBN13, edNom, edWeb, auNom, auPrenom 
			FROM livres INNER JOIN editeurs ON liIDEditeur = edID 
						INNER JOIN aut_livre ON al_IDLivre = liID 
						INNER JOIN auteurs ON al_IDAuteur = auID 
			$critere";

	$res = mysqli_query($bd, $sql) or fd_bd_erreur($bd,$sql);

	$lastID = -1;
	while ($t = mysqli_fetch_assoc($res)) {
		if ($t['liID'] != $lastID) {
			if ($lastID != -1) {
				fd_afficher_livre($livre, 'bcResultat', '../');	
			}
			$lastID = $t['liID'];
			$livre = array(	'id' => $t['liID'], 
							'titre' => $t['liTitre'],
							'edNom' => $t['edNom'],
							'edWeb' => $t['edWeb'],
							//'resume' => $t['liResume'],
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
    
	if ($lastID != -1) {
		fd_afficher_livre($livre, 'bcResultat', '../');	
	}
	else{
		echo '<p>Aucun livre trouvé</p>';
	}
}

/**
 *	Contrôle de la validité des informations reçues via la query string 
 *
 * En cas d'informations invalides, la session de l'utilisateur est arrêtée et il redirigé vers la page index.php
 *
 * @global  array     $_GET
 *
 * @return            partie du nom de l'auteur à rechercher            
 */
function fdl_control_get (){
	(count($_GET) != 2) && fd_exit_session();
	(! isset($_GET['type']) || $_GET['type'] != 'auteur') && fd_exit_session();
	(! isset($_GET['quoi'])) && fd_exit_session();

    $valueQ = trim($_GET['quoi']);
    $notags = strip_tags($valueQ);
    (mb_strlen($notags, 'UTF-8') != mb_strlen($valueQ, 'UTF-8')) && fd_exit_session();
    
	return $valueQ;
}

/**
 *	Contrôle de la validité des informations lors de la soumission du formulaire  
 *
 * En cas d'informations invalides, la session de l'utilisateur est arrêtée et il redirigé vers la page index.php
 *
 * @param   string    $valueT   type de recherche (auteur ou titre)
 * @global  array     $_POST
 *
 * @return            partie du nom de l'auteur ou du titre à rechercher            
 */
function fdl_control_post (&$valueT){
	(count($_POST) != 3) && fd_exit_session();
	(! isset($_POST['btnRechercher']) || $_POST['btnRechercher'] != 'Rechercher') && fd_exit_session();
	(! isset($_POST['type'])) && fd_exit_session();
	($_POST['type'] != 'auteur' && $_POST['type'] != 'titre') && fd_exit_session();
	(! isset($_POST['quoi'])) && fd_exit_session();
	
	$valueT = $_POST['type'] == 'auteur' ? 'auteur' : 'titre';
	
    $valueQ = trim($_POST['quoi']);
    $notags = strip_tags($valueQ);
    (mb_strlen($notags, 'UTF-8') != mb_strlen($valueQ, 'UTF-8')) && fd_exit_session();
    
    return $valueQ;
}
?>
