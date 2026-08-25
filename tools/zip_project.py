import os
import zipfile

root_dir = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
zip_names = ['web-shoe-ready-to-host.zip', 'web-shoe-deploy.zip']

exclude_dirs = {'.git', '.gemini', 'scratch', 'tools', '.agents', '.vscode', '__pycache__'}
exclude_files = {'web-shoe-ready-to-host.zip', 'web-shoe-deploy.zip', 'AUDIT-REPORT.txt', 'Thang_diem_TMDT.xlsx'}

for zip_name in zip_names:
    zip_path = os.path.join(root_dir, zip_name)
    if os.path.exists(zip_path):
        os.remove(zip_path)

    count = 0
    with zipfile.ZipFile(zip_path, 'w', zipfile.ZIP_DEFLATED) as zipf:
        for root, dirs, files in os.walk(root_dir):
            dirs[:] = [d for d in dirs if d not in exclude_dirs]
            for file in files:
                if file in exclude_files or file.endswith('.zip') or file.endswith('.log') or file.endswith('.tmp'):
                    continue
                full_path = os.path.join(root, file)
                rel_path = os.path.relpath(full_path, root_dir).replace('\\', '/')
                zipf.write(full_path, rel_path)
                count += 1

    size_mb = os.path.getsize(zip_path) / (1024 * 1024)
    print(f"SUCCESS: Zipped {count} files into {zip_name} ({size_mb:.2f} MB)")
