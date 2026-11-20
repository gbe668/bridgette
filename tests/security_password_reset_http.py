"""Actual recovery pages, real CSRF guards, disposable DB double and mail recorder."""
from pathlib import Path
import base64, http.cookiejar, json, re, socket, subprocess, sys, tempfile, time
import urllib.request, urllib.parse, urllib.error

repo = Path(__file__).resolve().parents[1]
php = sys.argv[1]
def q(path): return str(path).replace('\\', '/').replace("'", "\\'")
with tempfile.TemporaryDirectory(prefix='bridgette-reset-') as temp:
    root = Path(temp)
    for folder in ['', 'nested/appli']:
        app = root/folder
        app.mkdir(parents=True, exist_ok=True)
        for name in ['sendmailpwd.php', 'apk_sendmailpwd.php', 'changeforgottenpwd.php', 'apk_changeforgottenpwd.php', 'security_password_reset.php']:
            (app/name).write_bytes((repo/'appli'/name).read_bytes())
        (app/'bridgette_bdd.php').write_text('<?php', encoding='utf-8')
        (app/'libmailpwd.php').write_text("<?php require_once __DIR__.'/security_password_reset.php'; function send_mailpwd($email,$ticket) { file_put_contents(__DIR__.'/sent.json',json_encode([$email,$ticket,passwordResetUrl($ticket,$GLOBALS['token'])])); return true; }", encoding='utf-8')
        fixture = f"""<?php
define('RESET_TEST_FIXTURE',true);
require '{q(repo/'appli/security.php')}';
require '{q(repo/'tests/security_password_reset_support.php')}';
session_start();
$club = isset($_GET['token']) ? json_decode(base64_decode($_GET['token']),true) : ['prefix'=>$_SESSION['prefix'] ?? 'one_', 'timex'=>time()];
securitySelectClub(securityClub($club));
$prefix = $_SESSION['prefix']; $tab_joueurs = $prefix.'joueurs'; $titre = 'Test';
$token = base64_encode(json_encode(['prefix'=>$prefix,'timex'=>time()]));
$db = file_exists(__DIR__.'/db.bin') ? unserialize(file_get_contents(__DIR__.'/db.bin')) : new ResetDB();
if (!$db->players) $db->players['one_'] = [['id'=>7,'email'=>'player@example.org','password'=>hash('sha256','old'),'datesupp'=>0]];
function connectBDD() {{ return $GLOBALS['db']; }}
register_shutdown_function(function() {{ file_put_contents(__DIR__.'/db.bin',serialize($GLOBALS['db'])); }});
require '{q(repo/'appli/security_routes.php')}';
"""
        (app/'configuration.php').write_text(fixture, encoding='utf-8')
        (app/'inspect.php').write_text("<?php require 'configuration.php'; echo json_encode(['players'=>$db->players,'password_ok'=>bridgettePasswordVerify('new',$db->players['one_'][0]['password'])]);", encoding='utf-8')
    (root/'router.php').write_text("<?php $path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH); $file=__DIR__.rtrim($path,'/'); if (is_file($file)) { $_SERVER['SCRIPT_FILENAME']=$file; $_SERVER['SCRIPT_NAME']=$path; chdir(dirname($file)); require $file; } else { http_response_code(404); }", encoding='utf-8')
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0)); port = sock.getsockname()[1]
    proc = subprocess.Popen([php, '-n', '-S', f'127.0.0.1:{port}', str(root/'router.php')], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
    try:
        for _ in range(50):
            try:
                with socket.create_connection(('127.0.0.1',port),timeout=.2): break
            except OSError: time.sleep(.1)
        count = 0
        for folder in ['', '/nested/appli']:
            browser = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
            def request(path, data=None, client=browser, headers=None):
                body = None if data is None else urllib.parse.urlencode(data).encode()
                req = urllib.request.Request(f'http://127.0.0.1:{port}'+folder+path, data=body, headers=headers or {})
                try: response = client.open(req,timeout=5)
                except urllib.error.HTTPError as error: response=error
                return response.code,response.read().decode(),response.headers
            def csrf(body): return re.search(r'name="_csrf" value="([a-f0-9]{64})"',body)[1]
            status, body, _ = request('/sendmailpwd.php')
            assert status == 200 and 'Fatal error' not in body,body
            token = csrf(body)
            assert request('/sendmailpwd.php',{'usermail':'player@example.org'})[0] == 403
            status, known, _ = request('/sendmailpwd.php',{'usermail':'player@example.org','_csrf':token})
            assert status == 200 and 'Si cette adresse correspond' in known,known
            app = root/folder.lstrip('/')
            _,ticket,url = json.loads((app/'sent.json').read_text())
            assert folder+'/changeforgottenpwd.php?' in url,url
            status, unknown, _ = request('/sendmailpwd.php',{'usermail':'unknown@example.org','_csrf':token})
            assert known == unknown, 'Known/unknown HTML response differs'
            query = '?'+urllib.parse.urlencode({'phrase':ticket})
            status,form,headers = request('/changeforgottenpwd.php'+query)
            assert status == 200 and 'name="newpassword1"' in form,form
            assert headers['Referrer-Policy'] == 'origin' and headers['Cache-Control'] == 'no-store'
            csrf_token = csrf(form)
            args = {'phrase3':ticket,'newpassword1':'new','newpassword2':'new','_csrf':csrf_token}
            status, rejected, _ = request('/changeforgottenpwd.php'+query,args,headers={'Origin':'null'})
            assert status == 403 and 'Origine de la requête absente' in rejected
            assert request('/changeforgottenpwd.php'+query,args,headers={'Origin':'https://evil.example'})[0] == 403
            assert request('/changeforgottenpwd.php'+query,{'phrase3':ticket,'newpassword1':'new','newpassword2':'new'})[0] == 403
            status, body, _ = request('/changeforgottenpwd.php'+query,{'phrase3':ticket,'newpassword1':'new','newpassword2':'different','_csrf':csrf_token})
            assert 'deux mots de passe identiques' in body
            other = base64.b64encode(json.dumps({'prefix':'two_','timex':int(time.time())}).encode()).decode()
            outsider = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
            status,body,_=request('/changeforgottenpwd.php'+query+'&'+urllib.parse.urlencode({'token':other}),client=outsider)
            assert 'name="newpassword1"' not in body and 'Lien invalide' in body,body
            status, body, _ = request('/apk_changeforgottenpwd.php'+query,{'phrase3':ticket,'newpassword1':'new','newpassword2':'new','_csrf':csrf_token},headers={'Origin':f'http://127.0.0.1:{port}'})
            assert 'changé avec succès' in body and 'name="newpassword1"' not in body,body
            status, body, _ = request('/changeforgottenpwd.php'+query,{'phrase3':ticket,'newpassword1':'replay','newpassword2':'replay','_csrf':csrf_token})
            assert 'Lien invalide' in body,body
            inspected = json.loads(request('/inspect.php')[1])
            assert inspected['password_ok'] and inspected['players']['one_'][0]['password'].startswith('b2:')
            for mail in ['player@example.org','absent@example.org']:
                status,body,_ = request('/apk_sendmailpwd.php/?'+urllib.parse.urlencode({'mailuser':mail}))
                answer=json.loads(body)
                assert status == 200 and answer['login']==0 and 'Si cette adresse correspond' in answer['message'],body
            for bad in ['oldencryptedlink', '"><script>bad</script>']:
                status,body,_=request('/changeforgottenpwd.php?'+urllib.parse.urlencode({'phrase':bad}))
                assert 'name="newpassword1"' not in body and '<script>bad</script>' not in body,body
            count += 18
        print(f'{count} recovery HTTP checks passed (actual pages, root/subdirectory, Android GET/JSON, simulated DB/mail)')
    finally:
        proc.terminate(); proc.wait(timeout=5)
