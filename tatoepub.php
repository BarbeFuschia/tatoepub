<?php

require_once('ebook.php');

/**
 * Fonction permettant de créer les fichiers html nécessaires à la 
 *   création d'un livre électronique EPUB à partir d'une page html du
 *   site teraelis.fr.
 * 
 * Paramètres :
 *   string $path : chaine de caractère représentant le chemin d'un 
 *     sujet, le nom de domaine est rajouté à l'intérieur de la fonction.
 * 
 *     Exemple : "/litterature/sujet/les-industries-du-cauchemar"
 *     La page appelée sera "http://teraelis.fr/litterature/sujet/les-industries-du-cauchemar"
 *     Pour les sujets sur plusieurs pages, le dernier antislash suivit du numéro de la page sera retiré
 *     Exemple : "/litterature/sujet/divagation-les-courants-d-air/30"
 *     La page appelée sera "http://teraelis.fr/litterature/sujet/divagation-les-courants-d-air"
 *   
 * 
 *   boolean $multi : indique si il faut prendre en compte tous les 
 *     posts du sujet (dans le cas des tomes long ou des sujets rôlistes).
 *     La valeur par défaut est false.
 * 
 * Renvoie le nombre de fichiers créés, 0 si rien n'est créé.
 * Les fichiers créés se nommeront $derniere-partie-du-chemin.$numéro-d-ordre.".html"
 * 
 */
function extractHtml($path, $multi = false) {
    
    // le répertoire actuel
    $dir = getcwd();
    echo "répertoire de travail : ".$dir."<br/>";
    
    // test création de fichier
    /*
    $desc = fopen("testfichier.txt", "w");
    if ($desc == false) {
        echo "fichier test non créé<br/>";
    } else {
    fwrite($desc, $path);
    fclose($desc);
    }*/
    // s'il n'y a pas le '/' au début, on le rajoute (pour faciliter l'usage)
    if ($path[0] != '/') {
        $path = "/".$path;
    }
    
    // chemin sans numéro de page
    $pos = strpos($path, "/");
    $bkshNb = 0;
    while ($pos !== false) {
        $bkshNb++;
        $pos = strpos($path, "/", $pos + 1);
    }
    if ($bkshNb == 4) {
        // On retire le numéro de la page.
        $path = substr($path, 0, $pos);
    } else if ($bkshNb != 3) {
        // là, ça veut dire qu'on a passé une connerie en paramètres
        return false;
    }
    
    // nom de l'oeuvre
    $pos = strrpos($path, "/");
    $name = substr($path, $pos + 1, strlen($path) - $pos - 1);
    $name = str_replace("-", "_", $name);
    echo "nom : ".$name."<br/>";
  
        
    // construction de l'url (peut-être pas nécessaire pour la suite, ou alors en localhost)
    $path = "http://teraelis.fr".$path;
    // test
    echo $path."<br/>";
    
    // chargement du document
    $doc = new DOMDocument();
    echo "DOMDocument construit<br/>";
    $reussito = $doc->LoadHTMLFile($path);
    if ($reussito == false) {
        echo "le chargement a échoué<br/>";
        return false;
    } else {
        echo "le chargement a bien fonctionné<br/>";
    }
    $domPath = new DOMXPath($doc);
    
    // trouver le titre de l'oeuvre (titre du sujet) : <div class="title"> l'unique élément avec cette classe dans chaque page
    $nodeList = $domPath->query('//div[@class=\'title\']/h1/a');
    $title = $nodeList->item(0)->textContent;
    // On supprime les balises parce que c'est moche de chez moche
    $pos = strrpos($title, "]");
    $title = substr($title, $pos + 1, strlen($title) - $pos - 1);
    echo "titre : ".$title."</br>";
    
    // trouver l'auteur : le premier username
    $nodeList = $domPath->query('//div[@class=\'username\'][1]/a');
    $author = $nodeList->item(0)->textContent;
    echo "auteur : ".$author."</br>";
  
    // trouver le texte du premier post
    $contentNode = $domPath->query('//div[@class=\'js-content js-book-content\'][1]')->item(0);
    
    // On fait un nouveau document
    $newDoc = new DOMDocument();
    // avec une structure de contenu de base
    $reussito = $newDoc->LoadHTMLFile("contentBase.html");
    if ($reussito == false) {
        echo "le chargement du fichier de base a échoué<br/>";
        return false;
    }
    echo "le chargement du fichier de base a bien fonctionné<br/>";
    // on ajoute le titre
    $titleNode = $newDoc->getElementsByTagName("title")->item(0);
    $titleNode->textContent = $title;
    // on rattache une copie du noeud précédent au nouveau document
    $clonedNode = $newDoc->importNode($contentNode, true);
    if ($clonedNode === false) {
        echo "la copie a échoué<br/>";
        return false;
    }
    echo "la copie a fonctionné<br/>";
  
    // on insère le noeud copié dans l'arborescence du document
    $bodyNode = $newDoc->getElementsByTagName("body")->item(0);
    $bodyNode->appendChild($clonedNode);
    
    // $savePath = "/home/m1ita/vimarsyl/TPWeb/".$name.".html";
    $savePath = $name.".html";
    echo $savePath."<br/>";
    // $filecreated = $newDoc->saveHTMLFile($savePath); // rajoute probablement un doctype malvenu
    // $filecreated = $newDoc->save($savepath); // test pour voir si c'est l'emplacement qui ne convient pas.
    /*
    if ($filecreated == false) {
        echo "fichier non créé<br/>"; // renvoie en vrai 0 mais false dans la doc
        return false;
    } else {
        echo "fichier de taille ".$filecreated." créé<br/>"; // crée un fichier de taille 0
    }
    */
    $stringXML = $newDoc->saveXML(); // On essaie en XML
    $desc = fopen($savePath, "w");
    if ($desc == false) {
        echo "fichier test non créé<br/>";
        return false;
    } else {
        fwrite($desc, $stringXML);
        fclose($desc);
    }
    
    // à partir de là on essaye de créer un ebook
    
    $leLivre = new ebook();
    echo "on a construit le livre<br/>";
    
    $spine = array();
    
    $leLivre->addContentFile($savePath, "item1", "text/html");
    echo "ajout du fichier<br/>";
    
    $spine[0] = "item1";
    
    $leLivre->setDcCreator($author);
    echo "ajout de l'auteur<br/>";
    
    $leLivre->setDcPublisher("Ter Aelis");
    echo "ajout de l'éditeur<br/>";
    
    $leLivre->setDcTitle($title);
    echo "ajout du titre<br/>";
    
    $leLivre->setDcLanguage("FR");
    echo "ajout de la langue<br/>";
    
    // $leLivre->setSpine($leLivre->getManifest("id")); // On doit mettre les $id qu'on a utilisé avec addContentFile
    $leLivre->setSpine($spine);
    echo "normalement ajout du spine<br/>";
    
    $leLivre->setDcIdentifier($path, "URL");
    echo "ajout d'un identifiant<br/>";
    
    $leLivre->buildEPUB($name,"/var/www/html/tatoepub/"); // on essaye spécifiquement avec un path absolu
    echo "construction de l'EPUB<br/>";
    
    echo "fin de la fonction<br/>";
    return 1;
    
}

?>
