<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__.'/security_android_support.php';
require __DIR__.'/../appli/security.php';
function checkTicket($value, $label) { if (!$value) throw new RuntimeException($label); }
$db = new AndroidTestDB();
$ticket = securityAndroidIssue($db, 7, 'one_');
$key = securityAndroidTicketKey($ticket);
checkTicket((bool)preg_match('/\Aapk1\.[a-f0-9]{64}\z/', $ticket), 'Random URL-safe token');
checkTicket(strlen($key) === 32 && str_contains($key, '_'), 'Fits schema, cannot be a PHP session ID');
checkTicket(!str_contains($db->rows[$key]['data'], $ticket), 'Only the fingerprint is stored');
checkTicket(securityAndroidConsume($db, '7', 'one_') === 0, 'Numeric identity refused');
checkTicket(securityAndroidConsume($db, ['7'], 'one_') === 0, 'Array refused');
checkTicket(securityAndroidConsume($db, $ticket, 'two_') === 0, 'Other club refused');
$tampered = substr($ticket, 0, -1).($ticket[-1] === 'a' ? 'b' : 'a');
checkTicket(securityAndroidConsume($db, $tampered, 'one_') === 0, 'Tampering refused');
checkTicket(securityAndroidConsume($db, $ticket, 'one_') === 7, 'Valid ticket accepted');
checkTicket(securityAndroidConsume($db, $ticket, 'one_') === 0, 'Replay refused');
$expired = securityAndroidIssue($db, 7, 'one_');
$key = securityAndroidTicketKey($expired);
$data = json_decode($db->rows[$key]['data'], true); $data['expires'] = time() - 1;
$db->rows[$key]['data'] = json_encode($data);
checkTicket(securityAndroidConsume($db, $expired, 'one_') === 0, 'Expired ticket refused');
checkTicket($ticket !== $expired, 'Fresh login has a fresh ticket');
$db->rows['ordinary-session'] = ['access' => 0, 'data' => 'existing'];
$db->rows[$key]['access'] = 0;
securityAndroidIssue($db, 7, 'one_');
checkTicket(!isset($db->rows[$key]) && isset($db->rows['ordinary-session']), 'Cleanup preserves ordinary sessions');
echo "12 Android ticket security cases passed\n";
