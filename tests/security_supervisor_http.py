"""Real supervisor HTTP/session flow in a disposable copy; database connector replaced by a recorder."""
from pathlib import Path
import http.cookiejar
import json
import re
import shutil
import socket
import subprocess
import sys
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request

root = Path(__file__).resolve().parents[1]
php = sys.argv[1]
with tempfile.TemporaryDirectory(prefix='bridgette-supervisor-') as directory:
    fixture = Path(directory)
    assert fixture.resolve().parent == Path(tempfile.gettempdir()).resolve()
    (fixture/'clubs').mkdir()
    shutil.copy2(root/'appli/supervisor.php', fixture/'supervisor.php')
    shutil.copy2(root/'appli/club_prefix.php', fixture/'club_prefix.php')
    shutil.copy2(root/'appli/security_passwords.php', fixture/'security_passwords.php')
    library = (root/'appli/supervisor_lib.php').read_text(encoding='utf-8')
    start = library.index('function supervisorConnect(')
    end = library.index('class SupervisorAdminExists', start)
    library = library[:start] + '''
class RecordedStatement {
    private $args = [];
    function __construct(private $sql) { }
    function execute($args) {
        $this->args = $args;
        if (preg_match('/^INSERT INTO `([^`]+)`/', $this->sql, $m)) {
            $file=__DIR__.'/accounts.json'; $rows=is_file($file) ? json_decode(file_get_contents($file),true) : [];
            $rows[$m[1]][strtolower($args[0])]=$args;
            file_put_contents($file,json_encode($rows));
            file_put_contents(__DIR__.'/writes.jsonl',json_encode($this->sql)."\\n",FILE_APPEND);
        }
        return true;
    }
    function fetchColumn() {
        if (preg_match('/^SELECT COUNT\\(\\*\\) FROM `([^`]+)` WHERE pseudo = \\?/', $this->sql, $m)) {
            $file=__DIR__.'/accounts.json'; $rows=is_file($file) ? json_decode(file_get_contents($file),true) : [];
            return isset($rows[$m[1]][strtolower($this->args[0])]) ? 1 : 0;
        }
        return 1;
    }
    function rowCount() { return 1; }
}
class RecordedDB {
    function prepare($sql) { return new RecordedStatement($sql); }
    function exec($sql) {
        $file=str_starts_with($sql,'LOCK TABLES') || $sql==='UNLOCK TABLES' ? 'locks.jsonl' : 'writes.jsonl';
        file_put_contents(__DIR__.'/'.$file,json_encode($sql)."\\n",FILE_APPEND);
        return 0;
    }
}
function supervisorConnect(array $c) {
    define('DB_NAME', 'isolated-fixture');
    return new RecordedDB();
}
''' + library[end:]
    (fixture/'supervisor_lib.php').write_text(library, encoding='utf-8')
    password = 'fixture-only-password-123'
    password_hash = subprocess.check_output([php, '-n', '-r', 'echo password_hash($argv[1], PASSWORD_DEFAULT);', password], text=True)
    expiry = int(time.time())+600
    config = "<?php if (!defined('BRIDGETTE_SUPERVISOR')) {http_response_code(404); exit;} return " + "['enabled'=>true,'expires_at'=>%d,'password_hash'=>'%s','host'=>'127.0.0.1','clubs'=>['tst_'=>'Test','sol_'=>'Solo']];" % (expiry, password_hash)
    activation = fixture/'clubs/supervisor_access_127.0.0.1.php'
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0))
        port = sock.getsockname()[1]
    process = subprocess.Popen([php, '-n', '-d', f'session.save_path={fixture}', '-d', f'sys_temp_dir={fixture}', '-S', f'127.0.0.1:{port}', '-t', str(fixture)], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, creationflags=getattr(subprocess, 'CREATE_NO_WINDOW', 0))
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0))
        other_port = sock.getsockname()[1]
    other_process = subprocess.Popen([php, '-n', '-d', f'session.save_path={fixture}', '-d', f'sys_temp_dir={fixture}', '-S', f'localhost:{other_port}', '-t', str(fixture)], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, creationflags=getattr(subprocess, 'CREATE_NO_WINDOW', 0))
    jar = http.cookiejar.CookieJar()
    browser = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
    base = f'http://127.0.0.1:{port}/'
    def request(data=None, path='supervisor.php'):
        payload = None if data is None else urllib.parse.urlencode(data).encode()
        try:
            response = browser.open(base+path, payload, timeout=4)
        except urllib.error.HTTPError as e:
            response = e
        return response.code, response.read().decode(), response.headers
    def token(body):
        return re.search(r'name="csrf" value="([a-f0-9]{64})"', body)[1]
    try:
        for _ in range(50):
            try:
                with socket.create_connection(('127.0.0.1', port), timeout=.2): break
            except OSError: time.sleep(.1)
        status, body, _ = request()
        assert status == 503 and 'désactivé' in body
        assert 'supervisor_access_127.0.0.1.php' in body
        # No fallback to the old generic activation or a different domain.
        (fixture/'clubs/supervisor_access.php').write_text(config, encoding='utf-8')
        (fixture/'clubs/supervisor_access_other.example.org.php').write_text(config.replace("'127.0.0.1'", "'other.example.org'"), encoding='utf-8')
        assert request()[0] == 503
        activation.write_text(config.replace("'127.0.0.1'", "'other.example.org'"), encoding='utf-8')
        assert request()[0] == 503
        activation.write_text(config, encoding='utf-8')
        status, body, headers = request()
        assert status == 200 and 'Connexion opérateur' in body
        assert 'no-store' in headers['Cache-Control'] and headers['X-Frame-Options'] == 'DENY'
        assert 'HttpOnly' in headers['Set-Cookie'] and 'SameSite=Strict' in headers['Set-Cookie']
        status, _, _ = request({'action':'preview','csrf':token(body),'prefix':'tst_','command':'droptables'})
        assert status == 403
        _, body, _ = request()
        status, _, _ = request({'action':'login','csrf':'forged','password':password})
        assert status == 403
        _, body, _ = request({'action':'login','csrf':token(body),'password':password})
        assert 'Préparer une intervention' in body
        # Identical synchronized directory, two server names, different club allowlists.
        other_config = config.replace("'127.0.0.1'", "'localhost'").replace("'tst_'=>'Test','sol_'=>'Solo'", "'oth_'=>'Other server club'")
        (fixture/'clubs/supervisor_access_localhost.php').write_text(other_config, encoding='utf-8')
        other_browser = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        other_url = f'http://localhost:{other_port}/supervisor.php'
        other_body = other_browser.open(other_url, timeout=4).read().decode()
        assert 'Connexion opérateur' in other_body
        other_body = other_browser.open(other_url, urllib.parse.urlencode({'action':'login','csrf':token(other_body),'password':password}).encode(), timeout=4).read().decode()
        assert 'value="oth_"' in other_body and 'value="tst_"' not in other_body
        assert 'value="tst_"' in body and 'value="oth_"' not in body
        _, body, _ = request({'action':'preview','csrf':token(body),'prefix':'other_','command':'droptables'})
        assert 'non autorisé' in body
        _, body, _ = request({'action':'preview','csrf':token(body),'prefix':'tst_','command':'patch'})
        assert 'empreinte non autorisée' in body
        _, body, _ = request({'action':'preview','csrf':token(body),'prefix':'tst_','command':'droptables'})
        assert 'tst_joueurs' in body and not (fixture/'writes.jsonl').exists()
        _, body, _ = request({'action':'execute','csrf':token(body),'confirmation':'WRONG','password':password})
        assert 'confirmation incorrect' in body and not (fixture/'writes.jsonl').exists()
        _, body, _ = request({'action':'preview','csrf':token(body),'prefix':'tst_','command':'droptables'})
        _, body, _ = request({'action':'execute','csrf':token(body),'confirmation':'DROPTABLES tst_','password':'wrong'})
        assert 'Mot de passe opérateur incorrect' in body and not (fixture/'writes.jsonl').exists()
        _, body, _ = request({'action':'preview','csrf':token(body),'prefix':'tst_','command':'droptables'})
        old_token = token(body)
        payload = {'action':'execute','csrf':old_token,'confirmation':'DROPTABLES tst_','password':password, 'prefix':'other_', 'command':'install'}
        _, body, _ = request(payload)
        assert 'neuf tables du club ont été supprimées' in body, body
        writes = (fixture/'writes.jsonl').read_text().splitlines()
        assert len(writes) == 9 and all('`tst_' in json.loads(line) for line in writes)
        status, _, _ = request(payload)
        assert status == 403 and (fixture/'writes.jsonl').read_text().splitlines() == writes
        _, body, _ = request()
        _, body, _ = request({'action':'execute','csrf':token(body),'confirmation':'DROPTABLES tst_','password':password})
        assert 'Confirmation expirée' in body
        _, body, _ = request(path='supervisor.php?action=execute&command=droptables&prefix=tst_')
        assert (fixture/'writes.jsonl').read_text().splitlines() == writes
        # Adding administrators has its own authenticated, confirmed club scope.
        _, body, _ = request({'action':'preview','csrf':token(body),'prefix':'sol_','command':'addadmin'})
        assert 'Nouvel administrateur du club' in body and 'sol_directeurs' in body and 'sol_joueurs' not in body
        assert (fixture/'writes.jsonl').read_text().splitlines() == writes
        _, refused, _ = request({'action':'execute','csrf':'forged','confirmation':'AJOUTER ADMINISTRATEUR sol_','password':password,'admin_name':'Chosen','admin_password':'long safe fixture password'})
        assert 'invalide' in refused and (fixture/'writes.jsonl').read_text().splitlines() == writes
        _, body, _ = request()
        _, body, _ = request({'action':'execute','csrf':token(body),'confirmation':'AJOUTER ADMINISTRATEUR sol_','password':password,'admin_name':'Chosen','admin_password':'short'})
        assert '14 à 256' in body and (fixture/'writes.jsonl').read_text().splitlines() == writes
        _, body, _ = request({'action':'preview','csrf':token(body),'prefix':'sol_','command':'addadmin'})
        payload={'action':'execute','csrf':token(body),'confirmation':'AJOUTER ADMINISTRATEUR sol_','password':password,'admin_name':'Chosen','admin_password':'long safe fixture password','prefix':'tst_','command':'droptables'}
        _, body, _ = request(payload)
        assert 'Administrateur ajouté au club' in body, body
        accounts=json.loads((fixture/'accounts.json').read_text())
        assert set(accounts)=={'sol_directeurs'} and accounts['sol_directeurs']['chosen'][2]=='admin'
        assert accounts['sol_directeurs']['chosen'][1].startswith('b2:') and 'long safe fixture password' not in json.dumps(accounts)
        writes=(fixture/'writes.jsonl').read_text().splitlines()
        assert len(writes)==10 and 'INSERT INTO `sol_directeurs`' in json.loads(writes[-1])
        assert request(payload)[0]==403 and (fixture/'writes.jsonl').read_text().splitlines()==writes
        _, body, _ = request()
        _, body, _ = request({'action':'preview','csrf':token(body),'prefix':'sol_','command':'addadmin'})
        _, body, _ = request({'action':'execute','csrf':token(body),'confirmation':'AJOUTER ADMINISTRATEUR sol_','password':password,'admin_name':'chosen','admin_password':'another long fixture password'})
        assert 'Ce pseudo existe déjà' in body and (fixture/'writes.jsonl').read_text().splitlines()==writes
        assert json.loads((fixture/'accounts.json').read_text())==accounts
        # Empty and short prefixes are rejected before any database write.
        _, body, _ = request({'action':'preview','csrf':token(body),'prefix':'','command':'droptables'})
        assert 'non autorisé' in body
        assert (fixture/'writes.jsonl').read_text().splitlines() == writes
        _, body, _ = request({'action':'cancel','csrf':token(body)})
        # Expire a pending operation in the disposable native PHP session store.
        _, body, _ = request({'action':'preview','csrf':token(body),'prefix':'tst_','command':'droptables'})
        session_file = fixture/('sess_'+next(iter(jar)).value)
        serialized = session_file.read_text()
        serialized = re.sub(r's:4:"time";i:\d+;', 's:4:"time";i:1;', serialized)
        session_file.write_text(serialized)
        _, body, _ = request({'action':'execute','csrf':token(body),'confirmation':'DROPTABLES tst_','password':password})
        assert 'Confirmation expirée' in body
        assert (fixture/'writes.jsonl').read_text().splitlines() == writes
        # Changing the activation revokes already authenticated sessions.
        activation.write_text(config.replace("'Test'", "'Renamed'"), encoding='utf-8')
        _, body, _ = request()
        assert 'Connexion opérateur' in body
        # Failed attempts are shared across fresh browser sessions.
        for attempt in range(5):
            _, body, _ = request({'action':'login','csrf':token(body),'password':'wrong'})
            assert 'Connexion refusée' in body
        jar.clear()
        _, body, _ = request()
        _, body, _ = request({'action':'login','csrf':token(body),'password':password})
        assert 'Trop de tentatives' in body
        activation.write_text(config.replace(str(expiry), str(int(time.time())-1)), encoding='utf-8')
        assert request()[0] == 503
        assert request(path='clubs/supervisor_access_127.0.0.1.php')[0] == 404
        activation.unlink()
        assert request()[0] == 503
        # Exercise the local generator in its own isolated directory; never print the password.
        (fixture/'tools').mkdir()
        (fixture/'appli'/'clubs').mkdir(parents=True)
        generator = fixture/'tools'/'create_supervisor_config.php'
        shutil.copy2(root/'tools/create_supervisor_config.php', generator)
        shutil.copy2(root/'appli/club_prefix.php', fixture/'appli/club_prefix.php')
        shutil.copy2(root/'appli/supervisor_lib.php', fixture/'appli/supervisor_lib.php')
        for invalid in ['', 'ab', 'ab_', '___', 'abc', 'abcd_', 'abc__']:
            refused = subprocess.run([php, '-n', str(generator), '--host=example.org', '--prefix='+invalid], capture_output=True)
            assert refused.returncode == 1 and not (fixture/'appli/clubs/supervisor_access_example.org.php').exists()
        generated = subprocess.run([php, '-n', str(generator), '--host=example.org', '--prefix=sol_', '--label=Solo'], capture_output=True, text=True)
        assert generated.returncode == 0, generated.stderr
        secret = re.search(r': ([a-f0-9]{32})\n', generated.stdout)[1]
        generated_file = fixture/'appli/clubs/supervisor_access_example.org.php'
        assert secret not in generated_file.read_text() and "'sol_' => 'Solo'" in generated_file.read_text()
        again = subprocess.run([php, '-n', str(generator), '--host=example.org', '--prefix=sol_'], capture_output=True)
        assert again.returncode == 1
        another = subprocess.run([php, '-n', str(generator), '--host=OTHER.EXAMPLE.ORG', '--prefix=tst_'], capture_output=True)
        assert another.returncode == 0
        assert (fixture/'appli/clubs/supervisor_access_other.example.org.php').exists()
        assert not (fixture/'appli/clubs/supervisor_access.php').exists()
        assert generated_file.exists()
        print('Supervisor HTTP: activation, independent login, CSRF, allowlist, confirmation, replay, GET, revocation and shared rate limit OK (recorded DB).')
    finally:
        process.terminate()
        process.wait(timeout=5)
        other_process.terminate()
        other_process.wait(timeout=5)
