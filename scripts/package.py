"""Build deterministic WordPress upload packages from source, with readable permissions."""
from pathlib import Path
from zipfile import ZIP_DEFLATED, ZipFile, ZipInfo

ROOT = Path(__file__).resolve().parent.parent
OUTPUT = ROOT / 'releases'
OUTPUT.mkdir(exist_ok=True)
for source, name in [
    (ROOT / 'wp-content/themes/adi-personal', 'adi-personal-2.2.0.zip'),
    (ROOT / 'wp-content/plugins/adi-site-content', 'adi-site-content-1.2.0.zip'),
]:
    target = OUTPUT / name
    with ZipFile(target, 'w', compression=ZIP_DEFLATED, compresslevel=9) as archive:
        for file in sorted(source.rglob('*')):
            if not file.is_file():
                continue
            info = ZipInfo(str(Path(source.name) / file.relative_to(source)), date_time=(2026, 10, 13, 0, 0, 0))
            info.external_attr = 0o100644 << 16
            info.compress_type = ZIP_DEFLATED
            archive.writestr(info, file.read_bytes())
    with ZipFile(target) as archive:
        assert archive.testzip() is None
    print(f'{target.relative_to(ROOT)} ({target.stat().st_size:,} bytes)')
