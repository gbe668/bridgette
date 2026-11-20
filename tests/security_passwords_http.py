"""Real authentication/account pages with disposable DB and native ticket storage."""
from pathlib import Path
import http.cookiejar, json, re, socket, subprocess, sys, tempfile, time
import urllib.request, urllib.parse, urllib.error
repo=Path(__file__).resolve().parents[1]; php=sys.argv[1]
def q(path): return str(path).replace('\\','/').replace("'","\\'")
with tempfile.TemporaryDirectory(prefix='bridgette-passwords-') as temp:
    root=Path(temp)
    for folder in ['', 'nested/appli']:
        app=root/folder; app.mkdir(parents=True,exist_ok=True)
        for name in ['loguserin.php','apk_loguserin.php','apk_resume.php','logadmin.php','logdirecteur.php','logformateur.php','setpassword.php','setloginadmin.php','setlogindirecteur.php','setloginformateur.php']:
            (app/name).write_bytes((repo/'appli'/name).read_bytes())
        (app/'bridgette_bdd.php').write_text('<?php',encoding='utf-8')
        bootstrap=f"""<?php
define('PASSWORD_TEST_FIXTURE',true);
require '{q(repo/'appli/security.php')}';
require '{q(repo/'tests/security_passwords_support.php')}';
session_start(); securitySelectClub(['prefix'=>$_GET['club'] ?? ($_SESSION['prefix'] ?? 'one_'),'timex'=>time()]);
$_SESSION['ttlast']=time();
$prefix=$_SESSION['prefix']; $tab_joueurs=$prefix.'joueurs'; $tab_directeurs=$prefix.'directeurs'; $titre='Test';
$db=file_exists(__DIR__.'/db.bin') ? unserialize(file_get_contents(__DIR__.'/db.bin')) : new PasswordTestDB();
$db->deviceRace=isset($_GET['fail_device_sql']) ? 'fail' : null;
if (isset($_GET['remote_http'])) $_SERVER['REMOTE_ADDR']='198.51.100.1';
if (!$db->tables) {{
    $old=hash('sha256',htmlspecialchars(stripslashes('old & special')));
    $db->tables['one_joueurs']=[['id'=>7,'email'=>'player@example.org','prenom'=>'Test','nom'=>'Player','datesupp'=>0,'password'=>$old]];
    $db->tables['one_joueurs'][]=['id'=>8,'email'=>'native@example.org','prenom'=>'Native','nom'=>'Player','datesupp'=>0,'password'=>$old];
    foreach (['admin','directeur','formateur'] as $i=>$role) $db->tables['one_directeurs'][]=['id'=>$i+1,'pseudo'=>$role,'droits'=>$role,'password'=>$old];
}}
function connectBDD() {{return $GLOBALS['db'];}}
function logevent($event) {{}}
function setIdent($id) {{session_regenerate_id(true); $_SESSION['player_identity']=['club'=>$GLOBALS['prefix'],'id'=>$id,'time'=>time()];}}
register_shutdown_function(function() {{file_put_contents(__DIR__.'/db.bin',serialize($GLOBALS['db']));}});
require '{q(repo/'appli/security_routes.php')}';
"""
        (app/'configuration.php').write_text(bootstrap,encoding='utf-8')
        (app/'inspect.php').write_text("<?php require 'configuration.php'; echo json_encode(['tables'=>$db->tables,'session'=>$_SESSION,'rows'=>$db->rows,'new_ok'=>bridgettePasswordVerify(\"new & < \\\\ plus+\",$db->tables['one_directeurs'][0]['password'])]);",encoding='utf-8')
        (app/'expire_device.php').write_text("<?php require 'configuration.php'; $key=securityAndroidDeviceKey($_GET['device']); $data=json_decode($db->rows[$key]['data'],true); $data['expires']=time()-1; $db->rows[$key]['data']=json_encode($data);",encoding='utf-8')
        (app/'webview.php').write_text("<?php require 'configuration.php'; echo json_encode(['player'=>securityAndroidRequire()]);",encoding='utf-8')
    (root/'router.php').write_text("<?php $path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH); $file=__DIR__.rtrim($path,'/'); if (is_file($file)) { $_SERVER['SCRIPT_FILENAME']=$file; $_SERVER['SCRIPT_NAME']=$path; chdir(dirname($file)); require $file; } else http_response_code(404);",encoding='utf-8')
    class NoRedirect(urllib.request.HTTPRedirectHandler):
        def redirect_request(self,*args,**kwargs): return None
    with socket.socket() as sock: sock.bind(('127.0.0.1',0)); port=sock.getsockname()[1]
    proc=subprocess.Popen([php,'-n','-S',f'127.0.0.1:{port}',str(root/'router.php')],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL,creationflags=getattr(subprocess,'CREATE_NO_WINDOW',0))
    try:
        for _ in range(50):
            try:
                with socket.create_connection(('127.0.0.1',port),timeout=.2): break
            except OSError: time.sleep(.1)
        for folder in ['', '/nested/appli']:
            browser=urllib.request.build_opener(NoRedirect(),urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
            native=urllib.request.build_opener(NoRedirect(),urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
            def req(path,data=None,client=browser):
                body=None if data is None else urllib.parse.urlencode(data).encode()
                try: response=client.open(f'http://127.0.0.1:{port}'+folder+path,body,timeout=8)
                except urllib.error.HTTPError as error: response=error
                payload=response.read().decode()
                assert 'Fatal error' not in payload and 'Warning:' not in payload,payload
                return response.code,payload,response.headers
            def token(path):
                status,body,_=req(path); assert status==200,(status,body)
                return re.search(r'name="_csrf" value="([a-f0-9]{64})"',body)[1]
            def state(): return json.loads(req('/inspect.php')[1])
            csrf=token('/loguserin.php')
            assert req('/loguserin.php',{'usermail':'player@example.org','password':'old & special'})[0]==403
            assert req('/loguserin.php',{'usermail':'player@example.org','password':'bad','_csrf':csrf})[0]==200
            assert not state()['tables']['one_joueurs'][0]['password'].startswith('b2:')
            status,_,headers=req('/loguserin.php',{'usermail':'player@example.org','password':'old & special','_csrf':csrf})
            assert status==302 and headers['Location']=='bridge60.php'
            assert state()['tables']['one_joueurs'][0]['password'].startswith('b2:') and state()['session']['player_identity']['id']==7
            native_url='/apk_loguserin.php/?'+urllib.parse.urlencode({'mailuser':'player@example.org','password':'old & special'})
            answer=json.loads(req(native_url,client=native)[1])
            assert answer['login']==0 and answer['userid'].startswith('apk1.') and set(answer)=={'login','message','userid','username','club'}
            assert 'player_identity' not in json.loads(req('/inspect.php',client=native)[1])['session']
            credentials={'mailuser':'player@example.org','password':'old & special'}
            answer=json.loads(req('/apk_loguserin.php/',credentials,client=native)[1])
            assert answer['login']==0 and answer['userid'].startswith('apk1.')
            assert set(answer)=={'login','message','userid','username','club'}
            assert 'player_identity' not in json.loads(req('/inspect.php',client=native)[1])['session']
            assert json.loads(req(native_url,{},client=native)[1])['login']==1
            assert json.loads(req(native_url,{'mailuser':'player@example.org'},client=native)[1])['login']==2
            assert json.loads(req(native_url,dict(credentials,password='incorrect'),client=native)[1])['login']==4
            assert json.loads(req('/apk_loguserin.php/?club=two_',credentials,client=native)[1])['login']!=0
            assert json.loads(req('/apk_loguserin.php/?club=one_',credentials,client=native)[1])['login']==0
            assert json.loads(req('/apk_loguserin.php/',{'mailuser[]':'x','password[]':'x'},client=native)[1])['login']!=0
            legacy_native='/apk_loguserin.php/?'+urllib.parse.urlencode({'mailuser':'native@example.org','password':'old & special'})
            assert json.loads(req(legacy_native,client=native)[1])['login']==0
            assert state()['tables']['one_joueurs'][1]['password'].startswith('b2:')
            # V2 shape unchanged even when the optional flag is supplied in GET.
            assert set(json.loads(req(native_url+'&remember_device=1',client=native)[1]))=={'login','message','userid','username','club'}
            assert req('/apk_loguserin.php/?remote_http=1',dict(credentials,remember_device='1'),client=native)[0]==426
            v3=json.loads(req('/apk_loguserin.php/',dict(credentials,remember_device='1'),client=native)[1])
            assert v3['login']==0 and v3['device_token'].startswith('adr1.')
            device=v3['device_token']
            assert device not in json.dumps(state()['rows'])
            fresh=urllib.request.build_opener(NoRedirect(),urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
            resumed=json.loads(req('/apk_resume.php/',{'device_token':device},client=fresh)[1])
            assert resumed['login']==0 and resumed['userid'].startswith('apk1.') and resumed['userid']!=v3['userid']
            assert 'player_identity' not in json.loads(req('/inspect.php',client=fresh)[1])['session']
            webview=urllib.request.build_opener(NoRedirect(),urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
            assert json.loads(req('/webview.php/?userid='+resumed['userid'],client=webview)[1])['player']==7
            assert req('/apk_resume.php/?fail_device_sql=1',{'device_token':device},client=fresh)[0]==503
            assert req('/apk_resume.php/',{'device_token':device},client=fresh)[0]==200
            touch=json.loads(req('/apk_resume.php/',{'device_token':device,'action':'touch'},client=fresh)[1])
            assert touch['login']==0 and 'userid' not in touch
            assert req('/apk_resume.php/',client=fresh)[0]==405
            assert req('/apk_resume.php/?remote_http=1',{'device_token':device},client=fresh)[0]==426
            assert req('/apk_resume.php/?device_token='+device,{},client=fresh)[0]==401
            assert req('/apk_resume.php/',{'device_token[]':device},client=fresh)[0]==401
            assert req('/apk_resume.php/?club=two_',{'device_token':device},client=fresh)[0]==401
            assert req('/apk_resume.php/?club=one_',{'device_token':device},client=fresh)[0]==200
            assert req('/apk_resume.php/',{'device_token':device,'action':'invalid'},client=fresh)[0]==400
            assert req('/apk_resume.php/',{'device_token':device,'action':'revoke'},client=webview)[0]==200
            assert 'player_identity' not in json.loads(req('/inspect.php',client=webview)[1])['session']
            assert req('/apk_resume.php/',{'device_token':device},client=fresh)[0]==401
            expiring=json.loads(req('/apk_loguserin.php/',dict(credentials,remember_device='1'),client=native)[1])['device_token']
            req('/expire_device.php?device='+expiring)
            assert req('/apk_resume.php/',{'device_token':expiring},client=fresh)[0]==401
            assert json.loads(req(native_url+'&club=two_',client=native)[1])['login']!=0
            for role,dest in [('directeur','bridge40.php'),('formateur','bridgform10.php'),('admin','admin.php')]:
                csrf=token('/log'+role+'.php')
                status,_,headers=req('/log'+role+'.php',{'pseudo':role,'password':'old & special','_csrf':csrf})
                assert status==302 and headers['Location']==dest
                assert state()['session']['fonction']==role
            csrf=token('/setlogindirecteur.php')
            assert req('/setlogindirecteur.php',{'pseudo':'newdirector','password':'raw & < password','_csrf':csrf})[0]==302
            assert state()['tables']['one_directeurs'][-1]['password'].startswith('b2:')
            csrf=token('/setpassword.php')
            assert req('/setpassword.php',{'pseudo':'admin','oldpassword':'old & special','newpassword1':'new & < \\ plus+','newpassword2':'new & < \\ plus+','_csrf':csrf})[0]==200
            assert state()['new_ok'] and 'fonction' not in state()['session']
            csrf=token('/logadmin.php')
            assert req('/logadmin.php',{'pseudo':'admin','password':'old & special','_csrf':csrf})[0]==200
            assert req('/logadmin.php',{'pseudo':'admin','password':'new & < \\ plus+','_csrf':csrf})[0]==302
        print('Actual password HTTP flows passed: web/native migration, roles, creation/change, raw characters, CSRF and club isolation (root/subdirectory, simulated DB)')
    finally: proc.terminate(); proc.wait(timeout=5)
