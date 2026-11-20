<?php // Contenu commun aux pages À propos web et Android. ?>
<section aria-label="Crédits et licence">
    <h2>Crédits et licence</h2>
    <p>Conçu et réalisé par Bruno Coiffier pour les clubs de bridge.<br>
    Contact : <a href="mailto:bridgette@coiffier.org">bridgette@coiffier.org</a><br>
    Copyright © 2022–2026 Bruno Coiffier.</p>
    <p>Le code original de Bridg’ette est un logiciel libre, distribué sous licence GNU GPL,
    version 3 ou toute version ultérieure. Vous pouvez l’utiliser, le copier, le modifier
    et le redistribuer dans les conditions de cette licence.</p>
    <p>Le programme est fourni sans garantie, dans les limites prévues par la licence.
    Les composants tiers conservent leurs licences respectives, notamment l’AGPLv3
    pour le gestionnaire de sessions modifié et sa classe d’accès à la base de données.</p>
    <p><a href="license_bridgette.txt">Notice de licence et crédits des composants tiers</a><br>
    Textes intégraux : <a href="COPYING">GNU GPLv3</a> ·
    <a href="COPYING.AGPL">GNU AGPLv3</a> ·
    <a href="LICENSE.PHPMAILER">GNU LGPLv2.1</a> ·
    <a href="LICENSE.APACHE-2.0">Apache 2.0</a>.</p>
    <p><a href="https://github.com/gbe668/bridgette">Dépôt public du code source de Bridg’ette</a>.<br>
    Le dépôt n’ayant pas été actualisé récemment, son contenu peut différer de la version utilisée.</p>
    <h3>Composants tiers</h3>
    <p>Bridg’ette utilise <a href="https://github.com/PHPMailer/PHPMailer">PHPMailer</a>
    pour l’envoi des courriels et une version modifiée de
    <a href="https://github.com/dominicklee/PHP-MySQL-Sessions">PHP-MySQL-Sessions</a>
    de Dominick Lee pour les sessions en base MySQL. Cette version a été adaptée par
    Bruno Coiffier pour les versions récentes de PHP et les besoins de Bridg’ette.</p>
    <p>L’analyse des donnes utilise le moteur Double Dummy Solver (DDS), développé par
    <a href="https://privat.bahnhof.se/wb758135/index.html">Bo Haglund et ses collaborateurs</a>,
    sous licence Apache 2.0, avec une adaptation en WebAssembly proposée par
    <a href="https://grahamhazel.com/blog/">Graham Hazel</a>.</p>
    <details>
        <summary>Notice détaillée de licence</summary>
        <pre style="white-space: pre-wrap; overflow-wrap: anywhere; text-align: left;"><?php
            echo htmlspecialchars(file_get_contents(__DIR__.'/license_bridgette.txt'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        ?></pre>
    </details>
</section>
