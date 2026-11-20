<?php
// Characterization tests: no configuration, session or database is loaded.
require_once __DIR__.'/../appli/libcalculs.php';
require_once __DIR__.'/../appli/libcalculs.php';
$checks = 0;
function same($actual, $expected, $label) {
    global $checks;
    $checks++;
    if ($actual !== $expected) throw new RuntimeException($label.': '.var_export($actual, true));
}
// Upper limits of each IMP band, including one point on either side.
$limits = [10,40,80,120,160,210,260,310,360,420,490,590,740,890,1090,1290,1490,1740,1990,2240,2490,2990,3490,10000];
foreach ($limits as $imp => $limit) {
    same(calculIMP($limit - 1), $imp, 'below '.$limit);
    same(calculIMP($limit), $imp, 'at '.$limit);
    same(calculIMP($limit + 1), min($imp + 1, 23), 'above '.$limit);
}
same(calculIMP(1), 0, 'small positive difference');
same(calculIMP('420'), 9, 'numeric string');
same(calculIMP(420.9), 9, 'historical integer conversion');
// Existing out-of-domain behavior is preserved by this extraction.
// Callers handle a zero difference separately and pass positive magnitudes.
same(calculIMP(0), 23, 'legacy zero');
same(calculIMP(-10), 23, 'legacy negative');
same(calculIMP(100000), 23, 'legacy saturation');
same(elabClassement(1, [50]), ['1'], 'one pair');
same(elabClassement(4, [70,60,50,40]), ['1','2','3','4'], 'distinct scores');
same(elabClassement(4, [70,70,50,40]), ['1ex','1ex','3','4'], 'first tie');
same(elabClassement(5, [70,60,60,60,40]), ['1','2ex','2ex','2ex','5'], 'middle tie');
same(elabClassement(4, [70,60,50,50]), ['1','2','3ex','3ex'], 'last tie');
same(elabClassement(3, [50,50,50]), ['1ex','1ex','1ex'], 'all tied');
same(elabClassement(4, ['70',70,'50',50]), ['1ex','1ex','3ex','3ex'], 'database numeric strings');
same(elabClassement(2, [60,50,40]), ['1','2'], 'requested pair count');
same(elabClassement(0, []), [1], 'legacy empty ranking');
echo "$checks business calculation checks: OK\n";
