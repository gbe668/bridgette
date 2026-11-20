"""Local, non-destructive checks; never connects to a configured application DB."""
from pathlib import Path
import subprocess
import sys
import socket
import time
import urllib.request
import urllib.error
import urllib.parse

root = Path(__file__).resolve().parents[1]
php = sys.argv[1] if len(sys.argv) > 1 else 'php'
node = sys.argv[2] if len(sys.argv) > 2 else 'node'
files = list((root/'appli').rglob('*.php')) + list((root/'tests').glob('*.php')) + list((root/'tools').glob('*.php'))
for path in files:
    result = subprocess.run([php, '-n', '-l', str(path)], capture_output=True, text=True)
    if result.returncode:
        raise RuntimeError(result.stdout + result.stderr)
print(f'{len(files)} PHP files: syntax OK', flush=True)
subprocess.run([node, str(root/'tests/business_median.cjs')], check=True)
subprocess.run([node, str(root/'tests/business_web_android.cjs')], check=True)
for name in ['android_phone.cjs', 'android_dds_export.cjs', 'howell_packets.cjs', 'security_csrf_js.cjs']:
    subprocess.run([node, str(root/'tests'/name)], cwd=root, check=True)
subprocess.run([php, '-n', str(root/'tests/howell_packets.php')], check=True)
subprocess.run([php, '-n', str(root/'tests/business_calculations.php')], check=True)
subprocess.run([php, '-n', str(root/'tests/business_movements.php')], check=True)
subprocess.run([php, '-n', str(root/'tests/business_results.php')], check=True)
subprocess.run([php, '-n', str(root/'tests/business_rankings.php')], check=True)
subprocess.run([php, '-n', str(root/'tests/business_insert.php')], check=True)
subprocess.run([php, '-n', str(root/'tests/business_update.php')], check=True)
subprocess.run([php, '-n', str(root/'tests/security_parameters.php')], check=True)
subprocess.run([sys.executable, str(root/'tests/security_parameters_http.py'), php], check=True)
for name in ['security_access.php', 'security_identity.php', 'security_session_lifecycle.php', 'security_redirects.php', 'security_android.php', 'security_android_version.php', 'security_supervisor.php', 'security_monthly_gc.php', 'security_sql.php', 'security_sql_static.php', 'security_password_reset.php']:
    subprocess.run([php, '-n', str(root/'tests'/name)], check=True)
subprocess.run([sys.executable, str(root/'tests/security_redirect_http.py'), php], check=True)
subprocess.run([sys.executable, str(root/'tests/security_android_http.py'), php], check=True)
subprocess.run([php, '-n', str(root/'tests/security_android_devices.php')], check=True)
subprocess.run([sys.executable, str(root/'tests/security_supervisor_http.py'), php], check=True)
subprocess.run([sys.executable, str(root/'tests/security_player_policy_http.py'), php], check=True)
subprocess.run([sys.executable, str(root/'tests/security_csrf_http.py'), php], check=True)
subprocess.run([sys.executable, str(root/'tests/security_password_reset_http.py'), php], check=True)
subprocess.run([php, '-n', str(root/'tests/security_passwords.php')], check=True)
subprocess.run([sys.executable, str(root/'tests/security_passwords_http.py'), php], check=True)

with socket.socket() as sock:
    sock.bind(('127.0.0.1', 0))
    port = sock.getsockname()[1]
process = subprocess.Popen([php, '-n', '-S', f'127.0.0.1:{port}', '-t', str(root/'appli')],
    stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL,
    creationflags=getattr(subprocess, 'CREATE_NO_WINDOW', 0))
try:
    for attempt in range(50):
        try:
            with socket.create_connection(('127.0.0.1', port), timeout=.2): break
        except OSError: time.sleep(.1)
    # Every URL is rejected before configuration/DB initialization.
    urls = ['maintenance.php', 'importbddpart.php', 'configuration%20-%20save.php',
        'f25setpaire_old.php', 'oldpatches/xxpurge.php', 'apk_bridge25old.php',
        'bridgette.php?token=e30=&cmd=install', 'bridgette.php?token=e30=&cmd=patch',
        'bridgette.php?token=e30=&cmd=droptables', 'bridgette.php?token=e30=&cmd=gc']
    for url in urls:
        try:
            urllib.request.urlopen(f'http://127.0.0.1:{port}/{url}', timeout=3)
            raise AssertionError('HTTP request unexpectedly allowed: '+url)
        except urllib.error.HTTPError as error:
            path = root/'appli'/urllib.parse.unquote(url.split('?', 1)[0])
            expected = 403 if path.is_file() else 404
            assert error.code == expected, (url, error.code, error.read())
    print(f'{len(urls)} HTTP maintenance/import/legacy denials: OK')
finally:
    process.terminate()
    process.wait(timeout=5)
