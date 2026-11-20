"""Real settings page/save endpoint and guards; disposable files, no MySQL."""
from pathlib import Path
import http.cookiejar, json, re, socket, subprocess, sys, tempfile, time
import urllib.request, urllib.parse, urllib.error
repo = Path(__file__).resolve().parents[1]
php = sys.argv[1]
def q(path): return str(path).replace('\\', '/').replace("'", "\\'")
with tempfile.TemporaryDirectory(prefix='bridgette-parameters-') as temp:
    root = Path(temp)
    for folder in ['', 'nested/appli']:
        app = root/folder
        app.mkdir(parents=True, exist_ok=True)
        (app/'clubs').mkdir()
        for name in ['saveparams.php','bridge36.php','club_parameters.php','libcalculs.php']:
            (app/name).write_bytes((repo/'appli'/name).read_bytes())
        # Execute the actual production loading block, without business DB functions.
        source = (repo/'appli/bridgette_bdd.php').read_text(encoding='utf-8')
        (app/'bridgette_bdd.php').write_text(source.split('//tests de qualification')[0] +
            f"\nrequire '{q(repo/'appli/security_routes.php')}';", encoding='utf-8')
        (app/'configuration.php').write_text(f"""<?php
require '{q(repo/'appli/security.php')}';
session_start();
securitySelectClub(['prefix'=>$_GET['club'] ?? ($_SESSION['prefix'] ?? 'abc_'),'timex'=>time()]);
if (isset($_GET['role'])) {{
    $_SESSION['pseudo']='Test'; $_SESSION['fonction']=$_GET['role'];
    $_SESSION['auth_club']=$_SESSION['prefix']; $_SESSION['ttlast']=time();
}}
$file_params=__DIR__.'/clubs/'.$_SESSION['prefix'].'parametres.json';
function isDirecteur() {{ return securityRole('directeur'); }}
function logevent($event) {{ file_put_contents(__DIR__.'/events.txt',$event."\\n",FILE_APPEND); }}
""", encoding='utf-8')
    with socket.socket() as sock:
        sock.bind(('127.0.0.1',0)); port=sock.getsockname()[1]
    proc=subprocess.Popen([php,'-n','-S',f'127.0.0.1:{port}','-t',str(root)],
        stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL,
        creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
    checks=0
    try:
        for _ in range(50):
            try:
                with socket.create_connection(('127.0.0.1',port),timeout=.2): break
            except OSError: time.sleep(.1)
        for folder in ['', 'nested/appli']:
            app=root/folder
            base=f'http://127.0.0.1:{port}/'+(folder+'/' if folder else '')
            client=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
            def request(path,data=None):
                body=None if data is None else urllib.parse.urlencode(data).encode()
                req=urllib.request.Request(base+path,data=body,headers={'Origin':f'http://127.0.0.1:{port}'})
                try: response=client.open(req,timeout=3)
                except urllib.error.HTTPError as e: response=e
                return response.code,response.read().decode()
            status,page=request('bridge36.php?role=directeur')
            assert status==200,(status,page)
            csrf=re.search(r'"token":"([a-f0-9]{64})"',page)
            assert csrf is not None, 'Settings page must include the real CSRF token'
            token=csrf[1]
            values=json.loads(re.search(r'var parametres = (.*);',page)[1])
            path=app/'clubs/abc_parametres.json'
            assert request('saveparams.php')[0]==405
            assert request('saveparams.php',{'jsonparms':json.dumps(values)})[0]==403
            assert not path.exists()
            payload={'_csrf':token,'jsonparms':json.dumps(values)}
            assert request('saveparams.php',payload)[0]==200
            before=path.read_bytes()
            for invalid in ['null','{',json.dumps(dict(values,checkuser=3)),json.dumps(dict(values,extra=1))]:
                assert request('saveparams.php',{'_csrf':token,'jsonparms':invalid})[0]==400
                assert path.read_bytes()==before
                checks+=1
            values['checkuser']=2
            assert request('saveparams.php',{'_csrf':token,'jsonparms':json.dumps(values)})[0]==200
            assert json.loads(path.read_text())['checkuser']==2
            assert request('saveparams.php?role=formateur',payload)[0]==403
            assert json.loads(path.read_text())['checkuser']==2
            # Selection of another club revokes roles; only its own path can be saved.
            assert request('saveparams.php?club=tst_',payload)[0]==403
            status,page=request('bridge36.php?role=directeur')
            token=re.search(r'"token":"([a-f0-9]{64})"',page)[1]
            assert request('saveparams.php',{'_csrf':token,'jsonparms':json.dumps(values)})[0]==200
            assert (app/'clubs/tst_parametres.json').exists()
            assert path.read_bytes()!=before
            path.write_text('null')
            assert request('bridge36.php?club=abc_&role=directeur')[0]==503
            assert request('saveparams.php',{'_csrf':token,'jsonparms':json.dumps(values)})[0]==503
            assert path.read_text()=='null'
            checks+=12
        print(f'{checks} settings HTTP checks: OK (root/subfolder, files/DB isolated)')
    finally:
        proc.terminate(); proc.wait(timeout=5)
