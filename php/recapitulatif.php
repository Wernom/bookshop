<?php
ob_start('ob_gzhandler'); //démarre la bufferisation, compression du tampon si le client supporte gzip
session_start();    // Lancement de la session

require_once '../php/bibli_generale.php';
require_once '../php/bibli_bookshop.php';

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)

fd_html_debut('BookShop | Commandes', '../styles/bookshop.css');

fd_bookshop_enseigne_entete(isset($_SESSION['cliID']),'../');

ms_contenu();

fd_bookshop_pied();

fd_html_fin();

ob_end_flush();


// ----------  Fonctions locales au script ----------- //

/**
 *	Contenu de la page : formulaire de recherche + résultats éventuels 
 *
 */
function ms_contenu() {
	
	echo '<h1>Récapitulatif de vos commandes</h1>'; 
	
	$bd = fd_bd_connect();

	$sql = 	"SELECT liID, liTitre, liPrix, ccQuantite, coID, coDate, coHeure
			FROM livres INNER JOIN compo_commande ON liID = ccIDLivre
						INNER JOIN commandes ON ccIDCommande = coID ";

	$res = mysqli_query($bd, $sql) or fd_bd_erreur($bd,$sql);
	$commandes=array();
	$lastIDCommande = -1;
	while ($t = mysqli_fetch_assoc($res)) {
		if ($t['coID'] != $lastIDCommande) {
            if ($lastIDCommande != -1) {
                ms_afficher_commande($commandes, '../');	
            }
			$lastIDCommande = $t['coID'];
            $commandes = array(	'id' => $t['coID'],
                                'date' => $t['coDate'],
                                'heure' => $t['coHeure'],
                                'livres' => array(array('idLivre' => $t['liID'], 
                                                        'titre' => $t['liTitre'],
                                                        'prix' => $t['liPrix'],
                                                        'quantite' => $t['ccQuantite']))
						);
		}
		else {
			$commandes['livres'][] = array('idLivre' => $t['liID'], 
                                            'titre' => $t['liTitre'],
                                            'prix' => $t['liPrix'],
                                            'quantite' => $t['ccQuantite']);
		}		
	}
    // libération des ressources
	mysqli_free_result($res);
	mysqli_close($bd);
	if ($lastIDCommande != -1) {
		ms_afficher_commande($commandes, '../');	
	}
	else{
		echo '<p>Aucune commande effectuée</p>';
	}
}

function ms_afficher_commande($commande, $prefix){
    $prix = 0;
    echo 
    '<div class="Commande">',
        '<h2>Commande effectuée le ', 
        substr($commande['date'], 6, 7), '/', substr($commande['date'], 4, 5), '/',  substr($commande['date'],0, 3),
        ' à ', substr($commande['heure'], 0, 1), 'H', substr($commande['heure'], 2, 3),'</h2>';
        //print_r2($commande['livres']);
        foreach($commande['livres'] as $livre){
            $cout = $livre['prix']*$livre['quantite'];
            $prix = $prix + $cout;
            echo 
                '<div>',
                '<a href="', $prefix, 'php/details.php?article=', $livre['idLivre'], '" title="Voir détails">','
                <img src="', $prefix, 'images/livres/', $livre['idLivre'], '_mini.jpg" alt="', 
                fd_protect_sortie($livre['titre']),'">',
                '</a>',
                '<strong>', fd_protect_sortie($livre['titre']), '</strong><br>',
                'Prix : ', $livre['prix'], ' &euro;<br>',
                'Quantité : ', $livre['quantite'], '<br>',
                'Prix total : ', $cout, ' &euro;<br>',
                '</div>';
        }
    echo
        '<p><strong>Total de la commande : ', $prix, ' &euro;</strong></p>',
    '</div>';
}
?>
