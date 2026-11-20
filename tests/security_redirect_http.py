"""Exercise real login redirects/cookies against isolated PHP fixtures, without MySQL."""
from pathlib import Path
import http.cookiejar
import socket
import subprocess
import sys
import tempfile
import time
import urllib.request

php = sys.argv[1]
security = str(Path(__file__).resolve().parents[1] / 'appli/security.php').replace('\\', '/').replace("'", "\\'")
with tempfile.TemporaryDirectory(prefix='bridgette-redirect-') as temp:
    root = Path(temp)
    assert root.resolve().parent == Path(tempfile.gettempdir()).resolve()
    sessions = root / 'sessions'
    sessions.mkdir()
    session_path = str(sessions).replace('\\', '/').replace("'", "\\'")
    bootstrap = f"<?php session_save_path('{session_path}'); session_start(); require '{security}'; "
    page = bootstrap + "$_SESSION['prefix'] = 'test_'; securityWebLogin(); echo 'AUTHENTICATED';"
    login = bootstrap + """
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['player_identity'] = ['club' => 'test_', 'id' => 7, 'time' => time()];
    header('Location: '.$_SESSION['login_return']); exit;
}
echo 'LOGIN';
"""
    for directory in [root, root / 'nested/appli']:
        directory.mkdir(parents=True, exist_ok=True)
        for name in ['apk_bridgette.php', 'apk_bridge25.php']:
            (directory / name).write_text(page, encoding='utf-8')
        (directory / 'loguserin.php').write_text(login, encoding='utf-8')
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0))
        port = sock.getsockname()[1]
    process = subprocess.Popen([php, '-n', '-S', f'127.0.0.1:{port}', '-t', str(root)],
        stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL,
        creationflags=getattr(subprocess, 'CREATE_NO_WINDOW', 0))
    try:
        for _ in range(50):
            try:
                with socket.create_connection(('127.0.0.1', port), timeout=.2): break
            except OSError: time.sleep(.1)
        for directory in ['', '/nested/appli']:
            for name in ['apk_bridgette.php', 'apk_bridge25.php']:
                opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
                base = f'http://127.0.0.1:{port}{directory}'
                response = opener.open(f'{base}/{name}/?userid=7&token=selection', timeout=3)
                assert response.url == base + '/loguserin.php', response.url
                assert response.read() == b'LOGIN'
                response = opener.open(base + '/loguserin.php', data=b'test=1', timeout=3)
                assert response.url == f'{base}/{name}?userid=7', response.url
                assert response.read() == b'AUTHENTICATED'
        print('4 Android HTTP login/return flows: OK (root and subdirectory)')
    finally:
        process.terminate()
        process.wait(timeout=5)
