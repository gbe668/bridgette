function refreshPositions(n) {
	$.get( relpgm + "lstpositions.php?", {idtournoi:idtournoi, numtable:n}, function(strjson) {
		$("#currpos").html( strjson.str );
		let pos = strjson.positions[n];
		//console.log( "notour: ", notour, "mintour", strjson.mintour, "pos", pos );
		if ( notour < pos ) {
			goto62();	// rechargement page
		}
		// test avancée / précédent
		if ( (mintour < strjson.mintour)&&(notour == strjson.mintour) ) {
			goto62();	// rechargement page
		}
		setTimeout(function() { refreshPositions(n); }, 3000);
	}, "json")
	.done( function() {  } )
	.fail( function( jqxhr,settings,ex ) {
		if ( jqxhr.status === 0 ) {
			$('#currpos').html('chargement en cours');
			refreshPositions(n);
		}
		else $('#currpos').html('Status: ' + jqxhr.status + ' Erreur: '+ ex );
	} );
}
