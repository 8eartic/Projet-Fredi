<?php
require "db.php";
if(isset($_POST['ok'])){
    $annee_civile = $_POST['annee_civile'];
    $nom = $_POST['nom'];
    $adresse = $_POST['adresse'];
    $asso_lieux = $_POST['asso_lieux'];
    $date = $_POST['date'];
    $motif = $_POST['motif'];
    $trajet = $_POST['trajet'];
    $kms_parcourus = $_POST['kms_parcourus'];
    $péages = $_POST['péages'];
    $repas = $_POST['repas'];
    $hébergement = $_POST['hébergement'];
    $parent1 = $_POST['parent1'];
    $parent2 = $_POST['parent2'];
    $lieux = $_POST['lieux'];
    $date_note_de_frais = $_POST['date_note_de_frais'];
    $signature = $_POST['signature'];
    
    $requete = $bdd->prepare("INSERT INTO users VALUES (")





}


?>