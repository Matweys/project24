import os
import pathlib
import re
import subprocess

if __name__ == "__main__":
    base_path = pathlib.Path(os.path.dirname(os.path.realpath(__file__)))
    commit_id = subprocess.check_output(["git", "describe", "--abbrev=7", "--always", "--long"], cwd=base_path).strip().decode()
    version = re.sub(r"^v?(.*)-g\w{7}$", r"\1", commit_id).replace("-", ".")

    if version:
        with open(base_path / "version.php", "w") as f:
            f.write(f"""<?php return "{version}";""")
        subprocess.check_output(["git", "add", "version.php"], cwd=base_path)
