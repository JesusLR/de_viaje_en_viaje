"""Create a source release without credentials, uploads, dependencies or caches."""
from pathlib import Path
import tarfile

root = Path(__file__).resolve().parent.parent
destination = root / 'output' / 'docker' / 'de-viaje-docker.tar.gz'
destination.parent.mkdir(parents=True, exist_ok=True)
directories = ('app', 'bootstrap', 'config', 'database', 'docker', 'docs', 'public', 'resources', 'routes')
files = ('artisan', 'composer.json', 'composer.lock', 'package.json', 'package-lock.json',
         'vite.config.js', 'Dockerfile', 'compose.yaml', '.dockerignore', '.env.docker.example',
         '.env.example', 'README.md', '.gitattributes', '.gitignore')
included = []
for directory in directories:
    for path in (root / directory).rglob('*'):
        relative = path.relative_to(root).as_posix()
        if path.is_symlink() or not path.is_file():
            continue
        if relative.startswith(('public/storage/', 'public/build/', 'bootstrap/cache/')):
            continue
        if relative == 'public/hot' or path.suffix.lower() in ('.sqlite', '.sqlite3', '.sql', '.log', '.pyc'):
            continue
        if '__pycache__' in path.parts:
            continue
        included.append(path)
included += [root / file for file in files]
with tarfile.open(destination, 'w:gz') as archive:
    for path in sorted(included):
        archive.add(path, arcname=path.relative_to(root).as_posix(), recursive=False)
    for directory in ('bootstrap/cache', 'storage/app/public', 'storage/app/private',
                      'storage/framework/cache/data', 'storage/framework/sessions',
                      'storage/framework/views', 'storage/logs', 'storage/fonts'):
        entry = tarfile.TarInfo(directory)
        entry.type = tarfile.DIRTYPE
        entry.mode = 0o755
        archive.addfile(entry)
with tarfile.open(destination) as archive:
    names = archive.getnames()
    assert '.env' not in names and '.env.docker' not in names
    assert not any(name.startswith(('vendor/', 'node_modules/')) for name in names)
    assert 'Dockerfile' in names and 'package-lock.json' in names
print(f'{destination} ({destination.stat().st_size:,} bytes; {len(names)} entries)')
