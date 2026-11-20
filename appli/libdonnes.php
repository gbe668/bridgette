<?php
// Enregistrement des donnes : une connexion et le verrouillage historiques.
// Les fonctions internes reçoivent la connexion déjà verrouillée.
function insertDonneResultat($dbh, $idt, $dd, $ns, $eo, $contrat, $jouepar, $entame, $resultat, $points, $hweo) {
    global $tab_donnes;
    $sql = "INSERT into $tab_donnes ( idtournoi, etui, ns, eo, contrat, jouepar, entame, resultat, points, hweo ) values ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ? );";
    securityQuery($dbh, $sql, [$idt, $dd, $ns, $eo, $contrat, $jouepar, $entame, $resultat, $points, $hweo]);
    _setRang($idt, $dd, $dbh, $hweo, $tab_donnes);
}

function insertDonneAvancerPaire($dbh, $paire, $adversaire, $dd, $paquet) {
    global $tab_connexions, $cnx_ok;
    $sth = securityQuery($dbh, "SELECT * FROM $tab_connexions where id=?", [$paire]);
    $row = $sth->fetch(PDO::FETCH_ASSOC);
    $cpt = $row['cpt'];
    $cpt++;
    $change = $cpt % $paquet;
    $pos = $row['rdy'];
    if ($change == 0) $pos++;
    $sql = "UPDATE $tab_connexions SET stconnexion=?, numEO=?, numdonne=?, cpt=?, rdy =? where id = ?;";
    securityQuery($dbh, $sql, [$cnx_ok, $adversaire, $dd, $cpt, $pos, $paire]);
    return $change;
}

function insertDonneAvancerEO($dbh, $ns, $eo, $dd) {
    global $tab_connexions;
    // Compteurs EO Mitchell, notamment utilisés par Android.
    $sth = securityQuery($dbh, "SELECT * FROM $tab_connexions where id=?", [$eo]);
    $row = $sth->fetch(PDO::FETCH_ASSOC);
    $eocpt = $row['eocpt'];
    $eocpt++;
    $sql = "UPDATE $tab_connexions SET eonumNS=?, eodonne=?, eocpt=? where id = ?;";
    securityQuery($dbh, $sql, [$ns, $dd, $eocpt, $eo]);
}

function insertDonneRelaisGueridon($dbh, $t, $ns, $eo, $change) {
    global $tab_connexions;
    // Avec guéridon, le catalogue prévoit uniquement un relais EO.
    // Conserver le critère historique de sélection EO pour les installations existantes.
    if ($t['gueridon'] <= 0 || $change != 0) return;
    $relaisEO = ($t['relais'] > 0 && $t['pairesNS'] <= $t['pairesEO']) ? $t['relais'] : 0;
    if ($ns != $relaisEO - 1) return;
    $sth = securityQuery($dbh, "SELECT * FROM $tab_connexions where id=?", [$eo]);
    $row = $sth->fetch(PDO::FETCH_ASSOC);
    $eocpt = $row['eocpt'];
    $eocpt += $t['paquet'];
    $sql = "UPDATE $tab_connexions SET eocpt =? where id = ?;";
    securityQuery($dbh, $sql, [$eocpt, $eo]);
}

function insertDonneActualiserTour($dbh, $idt, $t) {
    global $tab_connexions, $tab_tournois, $t_howell, $st_phase_fini, $parametres;
    $n = ($t['genre'] == $t_howell) ? $t['pairesNS'] : min($t['pairesNS'], $t['pairesEO']);
    $res = securityQuery($dbh, "SELECT MIN(rdy) FROM $tab_connexions where id<=?;", [$n]);
    $tour = $res->fetchColumn();
    if ($tour > $t['npositions']) {
        $sql = "UPDATE $tab_tournois SET etat = ? where id = ?;";
        securityQuery($dbh, $sql, [$st_phase_fini, $idt]);
    } elseif ($tour > $t['notour']) {
        $date = new DateTime();
        $startseq = $date->getTimestamp();
        $endofseq = $startseq + $t['paquet'] * $parametres['dureedonne'] * 60;
        $sql = "UPDATE $tab_tournois SET notour = ?, endofseq = ? where id = ?;";
        securityQuery($dbh, $sql, [$tour, $endofseq, $idt]);
    }
    return $tour;
}

function insertDonne( $idt, $dd, $ns, $eo, $contrat, $jouepar, $entame, $resultat, $points ) {
    global $tab_donnes, $tab_connexions, $tab_tournois, $st_phase_jeu, $t_howell;
    global $parametres, $min_type_affimp;
    $result = array('ok'=>0, 'display'=>" ");
    $t = readTournoi($idt);
    if ($t['etat'] != $st_phase_jeu) {
        return ['ok'=>-1, 'display'=>"Ignoré, tournoi terminé !"];
    }

    $dbh = connectBDD();
    $dbh->query("LOCK TABLES $tab_donnes WRITE, $tab_connexions WRITE, $tab_tournois WRITE;");
    $sql = "SELECT count(*) from $tab_donnes where idtournoi=? and etui=? and ns=? and eo=?;";
    $res = securityQuery($dbh, $sql, [$idt, $dd, $ns, $eo]);
    if ($res->fetchColumn() == 0) {
        insertDonneResultat($dbh, $idt, $dd, $ns, $eo, $contrat, $jouepar, $entame, $resultat, $points, 0);
        $result = ['ok'=>1, 'display'=>"Enregistrement terminé."];
        $change = insertDonneAvancerPaire($dbh, $ns, $eo, $dd, $t['paquet']);
        if ($t['genre'] == $t_howell) {
            insertDonneResultat($dbh, $idt, $dd, $eo, $ns, $contrat, $jouepar, $entame, $resultat, -$points, 1);
            $result = ['ok'=>1, 'display'=>"Enregistrements terminés."];
            // Garder le dernier compteur de changement utilisé par le parcours historique.
            $change = insertDonneAvancerPaire($dbh, $eo, $ns, $dd, $t['paquet']);
        } else {
            insertDonneAvancerEO($dbh, $ns, $eo, $dd);
        }
        insertDonneRelaisGueridon($dbh, $t, $ns, $eo, $change);
        $tour = insertDonneActualiserTour($dbh, $idt, $t);
    } else {
        $result = ['ok'=>0, 'display'=>"Enregistrement déjà réalisé."];
    }
    $dbh->query("UNLOCK TABLES;");
    $dbh = null;
    if (isset($tour) && $tour > $t['npositions']) {
        if ($t['idtype'] <= $min_type_affimp && $parametres['affimp'] == 1) setTournoiIMP($idt);
        else setTournoi($idt);
    }
    return $result;
}

function updateDonneResultat($dbh, $idt, $dd, $ns, $eo, $contrat, $jouepar, $entame, $resultat, $points, $hweo) {
    global $tab_donnes;
    if ($hweo === 0) {
        $sql = "UPDATE $tab_donnes SET contrat = ?, jouepar = ?, entame = ?, resultat = ?, points = ? where idtournoi=? and etui=? and ns=? and eo=? and hweo=0;";
    } else {
        $sql = "UPDATE $tab_donnes SET contrat = ?, jouepar = ?, entame = ?, resultat = ?, points = ? where idtournoi=? and etui=? and ns=? and eo=? and hweo=1;";
    }
    securityQuery($dbh, $sql, [$contrat, $jouepar, $entame, $resultat, $points, $idt, $dd, $ns, $eo]);
    _setRang($idt, $dd, $dbh, $hweo, $tab_donnes);
}

function updateDonne( $idt, $dd, $ns, $eo, $contrat, $jouepar, $entame, $resultat, $points ) {
    global $tab_donnes;
    $dbh = connectBDD();
    $locked = false;
    try {
        // Exclusion commune avec insertDonne : résultats et rangs sous le même verrou.
        if ($dbh->query("LOCK TABLES $tab_donnes WRITE;") === false) throw new RuntimeException('Impossible de verrouiller les donnes pour correction.');
        $locked = true;
        $sql = "SELECT count(*) from $tab_donnes where idtournoi=? and etui=? and ns=? and eo=? and hweo=0;";
        $res = securityQuery($dbh, $sql, [$idt, $dd, $ns, $eo]);
        if ($res->fetchColumn() == 1) {
            updateDonneResultat($dbh, $idt, $dd, $ns, $eo, $contrat, $jouepar, $entame, $resultat, $points, 0);
            $sql = "SELECT count(*) from $tab_donnes where idtournoi=? and etui=? and ns=? and eo=? and hweo=1;";
            $res = securityQuery($dbh, $sql, [$idt, $dd, $eo, $ns]);
            if ($res->fetchColumn() == 1) {
                updateDonneResultat($dbh, $idt, $dd, $eo, $ns, $contrat, $jouepar, $entame, $resultat, -$points, 1);
                $display_string = "Deux enregistrements mis à jour.";
            } else {
                $display_string = "Un enregistrement mis à jour.";
            }
        } else {
            $display_string = "Enregistrement non trouvé.";
        }
        return $display_string;
		// En PHP, finally s’exécute même lorsqu’un return est rencontré dans le try.
    } finally {
        try {
            if ($locked && $dbh->query("UNLOCK TABLES;") === false) throw new RuntimeException('Impossible de déverrouiller les donnes après correction.');
        } finally {
            $dbh = null;
        }
    }
}
