"""Native login and WebView use distinct cookie jars. No app DB or APK required."""
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

php = sys.argv[1]
repo = Path(__file__).resolve().parents[1]
def quote(path): return str(path).replace('\\', '/').replace("'", "\\'")
with tempfile.TemporaryDirectory(prefix='bridgette-native-') as temp:
    root = Path(temp)
    assert root.resolve().parent == Path(tempfile.gettempdir()).resolve()
    sessions = root / 'sessions'
    sessions.mkdir()
    bootstrap = f"""<?php
define('ANDROID_TEST_FIXTURE', true);
session_name('TESTANDROID'); session_save_path('{quote(sessions)}'); session_start();
require '{quote(repo/'tests/security_android_support.php')}';
require '{quote(repo/'appli/security.php')}';
securitySelectClub(['prefix' => $_GET['club'] ?? 'one_', 'timex' => time()]);
$tab_joueurs = 'test_joueurs';
function connectBDD() {{ return new AndroidTestDB('{quote(root/'tickets.json')}'); }}
function setIdent($id) {{
    session_regenerate_id(true);
    $_SESSION['player_identity'] = ['club' => $_SESSION['prefix'], 'id' => $id, 'time' => time()];
}}
"""
    login = bootstrap + """
header('Cache-Control: no-store'); header('Content-Type: application/json');
// Authentication itself is simulated; ticket issue and validation are real.
echo json_encode(['login'=>0, 'userid'=>securityAndroidIssue(connectBDD(), 7, $_SESSION['prefix'])]);
"""
    page = bootstrap + "echo 'ANDROID:'.securityAndroidRequire();"
    for directory in [root, root / 'nested/appli']:
        directory.mkdir(parents=True, exist_ok=True)
        (directory / 'apk_loguserin.php').write_text(login, encoding='utf-8')
        for name in ['apk_bridgette.php', 'apk_bridge25.php', 'apk_howell62.php']:
            (directory / name).write_text(page, encoding='utf-8')
    class NoRedirect(urllib.request.HTTPRedirectHandler):
        def redirect_request(self, *args, **kwargs):
            raise AssertionError('Native flow must never redirect')
    def client():
        return urllib.request.build_opener(NoRedirect(), urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    def fetch(opener, url, status=200):
        try: response = opener.open(url, timeout=3)
        except urllib.error.HTTPError as error: response = error
        assert response.code == status, (response.code, response.read())
        assert response.headers.get('Location') is None
        return response.read()
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0)); port = sock.getsockname()[1]
    process = subprocess.Popen([php, '-n', '-S', f'127.0.0.1:{port}', '-t', str(root)],
        stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL,
        creationflags=getattr(subprocess, 'CREATE_NO_WINDOW', 0))
    try:
        for _ in range(50):
            try:
                with socket.create_connection(('127.0.0.1', port), timeout=.2): break
            except OSError: time.sleep(.1)
        for directory in ['', '/nested/appli']:
            base = f'http://127.0.0.1:{port}{directory}'
            volley, webview = client(), client()
            ticket = json.loads(fetch(volley, base+'/apk_loguserin.php/'))['userid']
            url = base+'/apk_bridgette.php/?userid='+ticket
            assert fetch(webview, url) == b'ANDROID:7'
            assert fetch(webview, url) == b'ANDROID:7'  # MainPlayer.onStart reuses userid.
            assert fetch(webview, base+'/apk_bridge25.php/?userid='+ticket) == b'ANDROID:7'
            assert fetch(webview, base+'/apk_howell62.php/') == b'ANDROID:7'
            fetch(client(), url, 401)  # A consumed ticket cannot establish another session.
            fetch(client(), base+'/apk_bridgette.php/?userid=7', 401)
            fresh = json.loads(fetch(volley, base+'/apk_loguserin.php/'))['userid']
            fetch(client(), base+'/apk_bridgette.php/?club=two_&userid='+fresh, 401)
            assert fetch(client(), base+'/apk_bridgette.php/?userid='+fresh) == b'ANDROID:7'
            fetch(webview, base+'/apk_howell62.php/?club=two_', 401)
        print('18 native/WebView HTTP cases passed; no redirects, separate cookies')
    finally:
        process.terminate(); process.wait(timeout=5)
