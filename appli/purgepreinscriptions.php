<?php
require 'configuration.php';
require 'bridgette_bdd.php';
function purgeTournoisPreinscription() {
	global $tab_tournois, $tab_pairesNS, $tab_pairesEO, $st_preinscription;
	$today = date('Y-m-d');
	$dbh = connectBDD();
	
	try {
		$dbh->beginTransaction();

		$sql = "DELETE FROM $tab_pairesNS
			WHERE idtournoi IN ( SELECT id FROM $tab_tournois WHERE etat = :etat AND tournoi < :today )";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([
			':etat' => $st_preinscription,
			':today' => $today
		]);

		$sql = "DELETE FROM $tab_pairesEO
			WHERE idtournoi IN ( SELECT id FROM $tab_tournois WHERE etat = :etat AND tournoi < :today )";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([
			':etat' => $st_preinscription,
			':today' => $today
		]);

		$sql = "DELETE FROM $tab_tournois WHERE etat = :etat AND tournoi < :today";
		$stmt = $dbh->prepare($sql);
		$stmt->execute([
			':etat' => $st_preinscription,
			':today' => $today
		]);

		$dbh->commit();
	} catch (PDOException $e) {
		$dbh->rollBack();
		throw $e;
	} finally {
		$dbh = null;
	}
}
if (!existeTournoiVivant()) purgeTournoisPreinscription();
http_response_code(204);
