"""Real CSRF/authorization guards with disposable sessions; no application database."""
from pathlib import Path
import http.cookiejar
import json
import re
import socket
import subprocess
import sys
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request

repo = Path(__file__).resolve().parents[1]
php = sys.argv[1]
def quote(path): return str(path).replace('\\', '/').replace("'", "\\'")
routes = re.findall(r"'([^']+\.php)'", (repo/'appli/security_mutations.php').read_text())
with tempfile.TemporaryDirectory(prefix='bridgette-csrf-') as temp:
    root = Path(temp)
    assert root.resolve().parent == Path(tempfile.gettempdir()).resolve()
    (root/'sessions').mkdir()
    router = f"""<?php
require '{quote(repo/'appli/security.php')}';
session_save_path('{quote(root/'sessions')}'); session_start();
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$route = basename(rtrim($path, '/'));
$_SERVER['SCRIPT_FILENAME'] = __DIR__.'/'.$route;
$_SERVER['SCRIPT_NAME'] = $path;
if ($route === 'page.php') {{
    securitySelectClub(['prefix'=>$_GET['club'] ?? 'tst_', 'timex'=>time()]);
    $_SESSION += ['pseudo'=>'Director', 'fonction'=>'directeur', 'auth_club'=>$_SESSION['prefix'], 'ttlast'=>time()];
    $_SESSION['player_identity']=['club'=>$_SESSION['prefix'],'id'=>7,'time'=>time()];
    securityCsrfHead(); securityCsrfField(); exit;
}}
$tab_joueurs='tst_joueurs'; $tab_pairesNS='tst_pairesNS'; $tab_pairesEO='tst_pairesEO';
$st_phase_jeu=33; $st_phase_fini=34; $t_howell=2; $parametres=['checkuser'=>0];
class Query {{ function execute($args) {{}} function fetchColumn() {{return 1;}} }}
class DB {{ function prepare($sql) {{return new Query();}} }}
function connectBDD() {{return new DB();}}
function readTournoi($id) {{return ['etat'=>33,'genre'=>1,'pairesNS'=>6,'pairesEO'=>6];}}
function isCheckedJoueur($id) {{return true;}}
require '{quote(repo/'appli/security_routes.php')}';
file_put_contents(__DIR__.'/accepted.log', $route."\\n", FILE_APPEND);
echo json_encode($_GET);
"""
    (root/'router.php').write_text(router, encoding='utf-8')
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0)); port = sock.getsockname()[1]
    process = subprocess.Popen([php, '-n', '-S', f'127.0.0.1:{port}', str(root/'router.php')],
        stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL,
        creationflags=getattr(subprocess, 'CREATE_NO_WINDOW', 0))
    try:
        for _ in range(50):
            try:
                with socket.create_connection(('127.0.0.1', port), timeout=.2): break
            except OSError: time.sleep(.1)
        browser = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        base = f'http://127.0.0.1:{port}'
        def request(path, data=None, headers=None, opener=browser):
            body = None if data is None else urllib.parse.urlencode(data, doseq=True).encode()
            req = urllib.request.Request(base+path, data=body, headers=headers or {})
            try: response = opener.open(req, timeout=3)
            except urllib.error.HTTPError as error: response = error
            return response.code, response.read().decode()
        count = 0
        for directory in ['', '/nested/appli']:
            status, body = request(directory+'/page.php')
            assert status == 200, body
            token = re.search(r'name="_csrf" value="([a-f0-9]{64})"', body)[1]
            assert directory+'/js/security-csrf.js' in body
            for route in routes:
                for data, expected in [(None, 405), ({}, 403), ({'_csrf':'wrong'}, 403), ({'_csrf[]':token}, 403)]:
                    result = request(directory+'/'+route, data)
                    assert result[0] == expected, (route, expected, result)
                    count += 1
            assert not (root/'accepted.log').exists()
            for route, args in [
                ('erasetournoi.php', {'idtournoi':5}),
                ('f30updatejoueur.php', {'idjoueur':7}),
                ('f25setpaire.php', {'ida':7, 'idb':8}),
                ('insertdonne.php', {'idtournoi':5,'ns':1,'eo':2,'donne':1,'points':420,'contrat':'4 P','jouepar':'N','entame':'AC','resultat':'0'}),
                ('saveparams.php', {'jsonparms':'{}'}),
                ('loguserin.php', {'usermail':'test@example.test','password':'test'}),
            ]:
                status, payload = request(directory+'/'+route+'?idtournoi=999', dict(args, _csrf=token))
                assert status == 200, (route, status, payload)
                if route in routes:
                    assert json.loads(payload).get('idtournoi') != '999', payload
                count += 1
            assert request(directory+'/erasetournoi.php', {'idtournoi':5}, {'X-CSRF-Token': token})[0] == 200
            assert request(directory+'/erasetournoi.php', {'idtournoi':5,'_csrf':token}, {'Origin':'https://evil.test'})[0] == 403
            outsider = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
            assert request(directory+'/erasetournoi.php', {'_csrf':token}, opener=outsider)[0] == 403
            request(directory+'/page.php?club=oth_')
            assert request(directory+'/erasetournoi.php', {'_csrf':token})[0] == 403
            request(directory+'/page.php?club=tst_')
            assert request(directory+'/erasetournoi.php', {'_csrf':token})[0] == 403
            # Reset the test log between the two installations.
            (root/'accepted.log').unlink()
        print(f'{count} CSRF HTTP cases plus session/club/origin isolation: OK (root and nested install).')
    finally:
        process.terminate(); process.wait(timeout=5)
