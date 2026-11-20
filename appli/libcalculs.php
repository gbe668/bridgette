<?php
// Pure business calculations shared by web, Android and Joomla pages.
// Extraction preserves the historical signatures, types and boundary behavior.
// calculIMP expects a positive difference; callers handle zero separately.
// elabClassement expects notes already sorted in ranking order.

function calculIMP( $delta ) {
	$tabimp = array( 0, 10, 40, 80, 120, 160, 210, 260, 310, 360, 420,
		490, 590, 740, 890, 1090, 1290, 1490, 1740, 1990, 2240,
		2490, 2990, 3490, 10000 );
	$n = sizeof($tabimp);
	$d = intval( $delta );
	for ( $i = 1; $i < $n; $i++ ) {
		$imp = $i-1;
		if ( ($d > $tabimp[$i-1])&&($d <= $tabimp[$i]) ) break;
	}
	return $imp;
};

function elabClassement( $paires, $notes ) {
	$exaequo = "ex";
	$result = array();
	$suffix = array();
	$result[0] = 1;		// le premier
	$suffix[0] = "";	// le premier
	for ($i = 1; $i < $paires; $i++) {
		if ($notes[$i] == $notes[$i-1] ) {
			$result[$i] = $result[$i-1];
			$suffix[$i-1] = $exaequo;
			$suffix[$i] = $exaequo;
		}
		else {
			$result[$i] = $i+1;
			$suffix[$i] = "";
		}
	}
	for ($i = 0; $i < $paires; $i++) {
		$result[$i] .= $suffix[$i];
	}
	return $result;
};
