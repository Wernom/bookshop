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
	$valueQuoi = control_get();
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


	if (! $_POST && isset($_GET['page'])){
        if(isset($_SESSION['livre'])){
            pagination();
            return;
        }
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

	//Pour la pagination on ne peut pas juste rajouter LIMIT a OFFSET b car on à pour un  livre plusieur résultat dans
    //la requette, on risque donc de se retrouver avec des erreurs lors de l'affichage des livres.
	$sql = 	"SELECT liID, liTitre, liPrix, liPages, liISBN13, edNom, edWeb, auNom, auPrenom 
			FROM livres INNER JOIN editeurs ON liIDEditeur = edID 
						INNER JOIN aut_livre ON al_IDLivre = liID 
						INNER JOIN auteurs ON al_IDAuteur = auID 
			$critere";

	$res = mysqli_query($bd, $sql) or fd_bd_erreur($bd,$sql);
	$livre=array();
	$lastID = -1;
    $_SESSION['articles'] = array();
    $_SESSION['livre'] = array();
    //$nb_livre_a_aficher =
	while ($t = mysqli_fetch_assoc($res)) {
		if ($t['liID'] != $lastID) {
			if ($lastID != -1) {
                $_SESSION['livre'][] = $livre;
				//fd_afficher_livre($livre, 'bcResultat', '../');
			}
			$lastID = $t['liID'];
			$livre = array(	'id' => $t['liID'], 
							'titre' => $t['liTitre'],
							'edNom' => $t['edNom'],
							'edWeb' => $t['edWeb'],
							'pages' => $t['liPages'],
							'ISBN13' => $t['liISBN13'],
							'prix' => $t['liPrix'],
							'auteurs' => array(array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']))
						);
			$_SESSION['articles'][] = $t['liID'];
		}
		else {
			$livre['auteurs'][] = array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']);
		}		
	}
    // libération des ressources
	mysqli_free_result($res);
	mysqli_close($bd);
    $_SESSION['livre'][] = $livre;
	if ($lastID != -1) {
		//fd_afficher_livre($livre, 'bcResultat', '../');
        pagination();
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
 */
function control_get (){
    if(count($_GET) == 1){
        !isset($_GET['page']) && fd_exit_session();
        (!is_numeric($_GET['page'])) && fd_exit_session();
        return $_GET['page'];
    }else{
        (count($_GET) != 2) && fd_exit_session();
        (! isset($_GET['type']) || $_GET['type'] != 'auteur') && fd_exit_session();
        (! isset($_GET['quoi'])) && fd_exit_session();

        $valueQ = trim($_GET['quoi']);
        $notags = strip_tags($valueQ);
        (mb_strlen($notags, 'UTF-8') != mb_strlen($valueQ, 'UTF-8')) && fd_exit_session();

        return $valueQ;
    }




}

/**
 *	Contrôle de la validité des informations lors de la soumission du formulaire  
 *
 * En cas d'informations invalides, la session de l'utilisateur est arrêtée et il redirigé vers la page index.php
 *
 * @param   string    $valueT   type de recherche (auteur ou titre)
 * @global  array     $_POST
 *
 * @return  string          partie du nom de l'auteur ou du titre à rechercher
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

/**
 * Gere la pagination. Affiche le bon nombre de livre par page et en bas de page le moyen pour naviguer d'une page à
 * l'autre.
 * @global array $_GET la page courent
 */
function pagination(){
    $nb_livre = count($_SESSION['livre']);
    $livre_par_page = 5;
    $nb_page = $nb_livre/$livre_par_page;

    if(!isset($_GET['page'])){
        $page = 1;
    }else{
        $page = $_GET['page'];
        if($page < 1){
            $page = 1;
        }else if($page > $nb_page){
            $page = $nb_page;
        }
    }
    $premier_livre_affiche = $livre_par_page*($page-1);
    $dernier_livre_affiche = $premier_livre_affiche + 5;
    $i=0;
    foreach ($_SESSION['livre'] as $livre){
        if($i < $premier_livre_affiche){
            ++$i;
            continue;
        }else if($i < $dernier_livre_affiche){
            fd_afficher_livre($livre, 'bcResultat', '../');
        }else{
            break;
        }
        ++$i;
    }

    echo '<div class="pagination">',
        '<a href="./recherche.php?page=',$page-1,'">&laquo;</a>';
    for($j = 1; $j < $nb_page + 1; ++$j){
        if($j == $page){
            echo '<a class="active" href="./recherche.php?page=',$j,'">',$j,'</a>';
        }else{
            echo '<a href="./recherche.php?page=',$j,'">',$j,'</a>';
        }
    }
    echo '<a href="./recherche.php?page=',$page+1,'">&raquo;</a>',
       '</div>';
}


?>
