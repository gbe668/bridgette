"""Real policy/identity/route code, disposable PHP sessions and simulated club data."""
from pathlib import Path
import http.cookiejar
import json
import socket
import subprocess
import sys
import tempfile
import time
import urllib.request
import urllib.error
import urllib.parse

repo = Path(__file__).resolve().parents[1]
php = sys.argv[1]
def quote(path):
    return str(path).replace('\\', '/').replace("'", "\\'")
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args): return None

with tempfile.TemporaryDirectory(prefix='bridgette-player-policy-') as temp:
    root = Path(temp)
    assert root.resolve().parent == Path(tempfile.gettempdir()).resolve()
    (root/'sessions').mkdir()
    config = f"""<?php
require '{quote(repo/'appli/security.php')}';
session_save_path('{quote(root/'sessions')}');
session_set_cookie_params(['httponly'=>true]);
session_start();
securitySelectClub(['prefix'=>'tst_', 'timex'=>time()]);
$settings = json_decode(file_get_contents(__DIR__.'/settings.json'), true);
$parametres = ['checkuser'=>$settings['mode'], 'checkin'=>$settings['code']];
$tab_joueurs = 'tst_joueurs'; $tab_pairesNS = 'tst_pairesNS'; $tab_pairesEO = 'tst_pairesEO';
$st_phase_jeu = 33; $st_phase_fini = 34; $t_howell = 2; $prefix = 'tst_';
$tab_connexions = 'tst_connexions'; $cnx_fin = 9;
$relpgm = ''; $relimg = ''; $agepagemax = 1440; $teston = 0;
"""
    (root/'configuration.php').write_text(config, encoding='utf-8')
    source = (repo/'appli/bridgette_bdd.php').read_text(encoding='utf-8')
    identity = source[source.index('$ID_NOIDENT'):source.index('function isCheckedJoueur')]
    checked = source[source.index('function isCheckedJoueur'):source.index('function isAdmin')]
    finish = source[source.index('function renderCnxFin('):source.index('function getMinTour(')]
    mock = """<?php
class FakeStatement {
    private $sql;
    function __construct($sql) {$this->sql=$sql;}
    function execute($args) {if (str_starts_with($this->sql, 'UPDATE')) file_put_contents(__DIR__.'/writes.log', $this->sql."\n", FILE_APPEND); return true;}
    function fetchColumn() {global $settings; return str_contains($this->sql, 'joueurs') ? ($settings['active'] ? 7 : false) : (int)$settings['member'];}
}
class FakeDatabase {function prepare($sql) {return new FakeStatement($sql);}}
function connectBDD() {return new FakeDatabase();}
function existeTournoiNonClos() {return 5;}
function existeTournoiVivant() {return 5;}
function readTournoi($id) {global $settings; return ['etat'=>$settings['state'], 'code'=>'1887', 'genre'=>1, 'pairesNS'=>6, 'pairesEO'=>6, 'npositions'=>6, 'idtype'=>1, 'njouees'=>18, 'paquet'=>3, 'relais'=>0];}
function getParmTable($pair) {return ['cpt'=>18, 'pos'=>7, 'numdonne'=>18];}
function getposhowell($type, $pair, $round, $packet) {return ['table'=>1, 'adversaire'=>2, 'NS'=>1];}
function getligneNS($id, $pair) {return ['N'=>['nomcomplet'=>'Joueur N'], 'S'=>['nomcomplet'=>'Joueur S']];}
function htmlResultatPaquet($id, $ns, $eo) {return '<p>Dernier paquet</p>';}
"""
    (root/'bridgette_bdd.php').write_text(mock+identity+checked+finish+f"\nrequire '{quote(repo/'appli/security_routes.php')}';", encoding='utf-8')
    entry = (repo/'appli/bridge60.php').read_text(encoding='utf-8').split('function htmlPositionsHowell')[0]
    (root/'bridge60.php').write_text(entry+"echo 'WELCOME';", encoding='utf-8')
    for page in ['mitch61.php', 'mitch62.php', 'howell61.php', 'apk_mitch62.php']:
        (root/page).write_text("<?php require 'configuration.php'; require 'bridgette_bdd.php'; echo 'JOINED';", encoding='utf-8')
    (root/'mitch62.php').write_text("<?php require 'configuration.php'; require 'bridgette_bdd.php'; echo 'JOINED';", encoding='utf-8')
    for page in ['howell64.php', 'apk_howell64.php', 'mitch64.php', 'apk_mitch64.php']:
        (root/page).write_text("<?php require 'configuration.php'; require 'bridgette_bdd.php'; echo 'FINISHED';", encoding='utf-8')
    (root/'setcnxfin.php').write_text((repo/'appli/setcnxfin.php').read_text(), encoding='utf-8')
    (root/'csrf.php').write_text("<?php require 'configuration.php'; echo securityCsrfToken();", encoding='utf-8')
    (root/'lib63.php').write_text('<?php', encoding='utf-8')
    for page in ['howell64.php', 'apk_howell64.php']:
        (root/page).write_text((repo/'appli'/page).read_text(encoding='utf-8'), encoding='utf-8')
    (root/'authenticate.php').write_text("<?php require 'configuration.php'; require 'bridgette_bdd.php'; setIdent(7); if (isset($_GET['native'])) $_SESSION['android_ticket_hash']=str_repeat('a',64); echo 'AUTH';", encoding='utf-8')
    (root/'code.php').write_text("<?php require 'configuration.php'; $_SESSION['codedujour']=$_GET['code']; echo 'CODE';", encoding='utf-8')
    (root/'expire.php').write_text("<?php require 'configuration.php'; $_SESSION['player_identity']['time']=time()-31*86400; echo 'EXPIRED';", encoding='utf-8')
    settings = {'mode':0, 'code':0, 'member':False, 'active':True, 'state':33}
    def configure(**changes):
        settings.update(changes)
        (root/'settings.json').write_text(json.dumps(settings))
    configure()
    with socket.socket() as sock:
        sock.bind(('127.0.0.1',0)); port = sock.getsockname()[1]
    process = subprocess.Popen([php,'-n','-S',f'127.0.0.1:{port}','-t',str(root)], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
    jar = http.cookiejar.CookieJar()
    browser = urllib.request.build_opener(NoRedirect(), urllib.request.HTTPCookieProcessor(jar))
    def get(path):
        try: response = browser.open(f'http://127.0.0.1:{port}/'+path,timeout=4)
        except urllib.error.HTTPError as error: response = error
        return response.code, response.read().decode(), response.headers
    def finish_pair():
        token = get('csrf.php')[1]
        data = urllib.parse.urlencode({'idtournoi':5, 'table':1, '_csrf':token}).encode()
        try: response = browser.open(f'http://127.0.0.1:{port}/setcnxfin.php', data=data, timeout=4)
        except urllib.error.HTTPError as error: response = error
        return response.code
    try:
        for _ in range(50):
            try:
                with socket.create_connection(('127.0.0.1',port),timeout=.2): break
            except OSError: time.sleep(.1)
        assert get('bridge60.php')[:2] == (200,'WELCOME')
        assert get('mitch61.php?idtournoi=5')[:2] == (200,'JOINED')
        configure(code=1)
        status, _, headers = get('bridge60.php')
        assert status == 302 and headers['Location'].endswith('checkjoueur.php')
        get('code.php?code=wrong')
        assert get('mitch62.php?idtournoi=5')[0] == 302
        get('code.php?code=1887')
        assert get('mitch62.php?idtournoi=5')[:2] == (200,'JOINED')
        assert not (root/'writes.log').exists(), 'GET must not mark a connection finished'
        assert get('setcnxfin.php?idtournoi=5&table=1')[0] == 405
        assert finish_pair() == 204
        assert 'AND rdy>?' in (root/'writes.log').read_text()
        configure(state=34)
        status, body, _ = get('howell64.php?idtournoi=5&paire=1')
        assert status == 200 and 'Affichage des résultats provisoires' in body and 'Tournoi inaccessible' not in body
        assert get('mitch64.php?idtournoi=5')[:2] == (200,'FINISHED')
        assert 'bridgetteFinish(5,1)' in body
        assert finish_pair() == 204
        assert get('mitch62.php?idtournoi=5')[0] == 403
        configure(state=33)
        configure(mode=1)
        status, _, headers = get('bridge60.php')
        assert status == 302 and headers['Location'].endswith('loguserin.php')
        get('authenticate.php')
        cookie = next(iter(jar))
        assert not cookie.discard and cookie.expires > time.time()+29*86400
        assert get('mitch61.php?idtournoi=5')[:2] == (200,'JOINED')
        _, _, headers = get('howell61.php?idtournoi=5')
        assert 'expires=' in headers['Set-Cookie'].lower()
        jar.clear_session_cookies()
        assert get('mitch62.php?idtournoi=5')[:2] == (200,'JOINED')
        configure(mode=2)
        assert get('mitch61.php?idtournoi=5')[0] == 403
        configure(member=True)
        assert get('howell61.php?idtournoi=5')[:2] == (200,'JOINED')
        configure(active=False)
        assert get('mitch62.php?idtournoi=5')[0] == 401
        assert finish_pair() == 401
        configure(active=True)
        get('expire.php')
        assert get('mitch62.php?idtournoi=5')[0] == 302
        get('authenticate.php?native=1')
        get('code.php?code=wrong')
        assert get('apk_mitch62.php?idtournoi=5')[:2] == (200,'JOINED')
        configure(state=34)
        status, body, _ = get('apk_howell64.php/?idtournoi=5&paire=1&ligne=NS')
        assert status == 200 and 'Affichage des résultats provisoires' in body and 'Tournoi inaccessible' not in body
        assert get('apk_mitch64.php/?idtournoi=5')[:2] == (200,'FINISHED')
        assert finish_pair() == 204
        configure(state=4)
        assert get('apk_howell64.php/?idtournoi=5')[0] == 403
        assert finish_pair() == 403
        configure(state=33)
        configure(member=False,mode=0)
        assert get('apk_mitch62.php?idtournoi=5')[0] == 403
        configure(member=True,state=4)
        assert get('apk_mitch62.php?idtournoi=5')[0] == 403
        print('Player policy HTTP: Non/M/P, optional web code, borrowed phone, persistent thirty-day cookie, renewal, expiry, Android and closed tournament OK.')
    finally:
        process.terminate(); process.wait(timeout=5)
