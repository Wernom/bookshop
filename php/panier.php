<?php

ob_start('ob_gzhandler'); //démarre la bufferisation, compression du tampon si le client supporte gzip
session_start();    // Lancement de la session
require_once '../php/bibli_generale.php';
require_once '../php/bibli_bookshop.php';

error_reporting(E_ALL); // toutes les erreurs sont capturées (utile lors de la phase de développement)

fd_html_debut('BookShop | Panier', '../styles/bookshop.css');


fd_bookshop_enseigne_entete(isset($_SESSION['cliID']),'../');
echo '<h1>Panier</h1>';


panier_contenu();

fd_bookshop_pied();
//On utilise se formulaire pour que les boutons placé dans un formulaire puisse fonctionner correctement.
echo '<form id="none"></form>';
fd_html_fin();

ob_end_flush();

/**
 * Contenu de la page panier.php.
 * On calcule egalement le prix total de la commande.
 */
function panier_contenu(){
    $prix_total = 0;
    if(isset($_SESSION['commande'])){
        echo '<h1>La commande à bien été enregistré</h1>';
        unset($_SESSION['commande']);
        return;
    }
    if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0) {
        $bd = fd_bd_connect();
        $critere = '';
        foreach ($_SESSION['cart'] as $key=>&$value) {
            if($critere == ''){
                $critere .= "WHERE liID = $key ";
            }else {
                $critere .= "OR liID = $key ";
            }
        }
        unset($value);


        $sql = "SELECT liID, liTitre, liPrix, edNom, auNom, auPrenom, edWeb
            FROM livres INNER JOIN editeurs ON liIDEditeur = edID
                        INNER JOIN aut_livre ON al_IDLivre = liID
                        INNER JOIN auteurs ON al_IDAuteur = auID
            $critere";

        $res = mysqli_query($bd, $sql) or fd_bd_erreur($bd, $sql);
        $livre = array();
        $lastID = -1;
        while ($t = mysqli_fetch_assoc($res)) {
            if ($t['liID'] != $lastID) {
                if ($lastID != -1) {
                    $prix_total += $livre['prix']*$_SESSION['cart'][$livre['id']];
                    panier_afficher_livre($livre, '../');
                }
                $lastID = $t['liID'];
                $livre = array('id' => $t['liID'],
                    'titre' => $t['liTitre'],
                    'edNom' => $t['edNom'],
                    'edWeb' => $t['edWeb'],
                    'prix' => $t['liPrix'],
                    'auteurs' => array(array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']))
                );
            } else {
                $livre['auteurs'][] = array('prenom' => $t['auPrenom'], 'nom' => $t['auNom']);
            }
        }
        mysqli_free_result($res);
        mysqli_close($bd);

        if ($lastID != -1) {
            $prix_total += $livre['prix']*$_SESSION['cart'][$livre['id']];
            panier_afficher_livre($livre, '../');
        } else {
            echo '<p>Aucun livre trouvé</p>';
        }
    }else{
        echo '<h3>Votre panier est vide.</h3>';
    }
    if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0){
        echo '<hr/>';
        echo '<h3 class="Panier">Prix Total : ',number_format($prix_total, 2) , ' &euro;</h3>';
        echo '<form style="text-align: center"><button  type="submit" formaction="commande.php">Commander</button></form>';
    }
}


/**
 * Afficher les livres du panier
 *
 * @param array $livre Tableau contenant les information su les livres.
 * @param string $prefix Préfixe du chemin vers le répertoire image.
 */
function panier_afficher_livre($livre, $prefix){
    echo
    '<div class="bcResultat Panier">',
    '<a href="', $prefix, 'php/details.php?article=', $livre['id'], '" title="Voir détails"><img src="', $prefix, 'images/livres/', $livre['id'], '_mini.jpg" alt="',
    fd_protect_sortie($livre['titre']),'"></a>';
    panier_formulaire($livre, $prefix);
     echo	'<strong>', fd_protect_sortie($livre['titre']), '</strong> <br>',
    'Ecrit par : ';

    $i = 0;
    foreach ($livre['auteurs'] as $auteur) {
        $supportLien ="{$auteur['prenom']} {$auteur['nom']}";
        if ($i > 0) {
            echo ', ';
        }
        $i++;
        echo '<a href="', $prefix, 'php/recherche.php?type=auteur&quoi=', urlencode($auteur['nom']), '">',fd_protect_sortie($supportLien), '</a>';
    }

    echo	'<br>Editeur : <a class="lienExterne" href="http://', fd_protect_sortie($livre['edWeb']), '" target="_blank">', fd_protect_sortie($livre['edNom']), '</a><br>',
    'Prix : ', $livre['prix'], ' &euro;<br>';

    echo '</div>';
}


/**
 * Affiche le formulaire du panier.
 * @param array $livre Tableau contenant les information su les livres.
 * @param string $prefix Préfixe du chemin vers le répertoire image.
 */
function panier_formulaire($livre, $prefix){

    echo
    '<form action="ajouter_plusieur_livre_panier.php" method="get" class="Panier">',

    '<table class="Panier">',
    '<tr>',
        '<th>',
            'Prix',
        '</th>',
        '<th>',
            'Quantité',
        '</th>',
        '<th>',
            'Total',
        '</th>',
        '<th>',
            'Valider',
        '</th>',
        '<th>',
            'Supprimer',
        '</th>',
    '</tr>',
    '<tr>',
        '<td>',
            $livre['prix'], ' &euro;',
        '</td>',
        '<td>',
            '<button form="none" type="submit" name="id" value="',$livre['id'],'" formmethod="get" formaction="', $prefix, 'php/moins_panier.php">-</button>',
            '<textarea name="quantite" rows="1" cols="2">', $_SESSION['cart'][$livre['id']],'</textarea>',
            '<button form="none" type="submit" name="id" value="',$livre['id'],'" formmethod="get" formaction="', $prefix, 'php/ajout_panier.php">+</button>',
        '</td>',
        '<td>',
            number_format($livre['prix']*$_SESSION['cart'][$livre['id']], 2) , ' &euro;',
        '</td>',
        '<td>',
            '<input type="hidden" name="id" value="',$livre['id'],'">',
            '<input class="PanierValider"  type="submit" name="valide" value="">',
        '</td>',
        '<td>',
            '<button class="PanierSupprimer" form="none" type="submit" name="id" value="',$livre['id'],'" formmethod="get" formaction="', $prefix, 'php/supprimer_livre_panier.php" ></button>',
        '</td>',
    '</tr>',
    '</table></form>';
}

/**
 * Calcul le prix totla d'un livre.
 *
 * @param array $livre le livre dont on veut connaitre le prix
 * @return float Le prix des livres.
 */
function panier_prix_total($livre){
    $res = 0;

    foreach ($livre as $value) {
        $res += $value['prix']*$_SESSION['cart'][$value['id']];
    }

    return $res;
}
?>