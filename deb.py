"""Depends: https://github.com/goreleaser/nfpm"""

import fnmatch
import os
import pathlib
import re
import shutil
import subprocess
import tempfile

package_name = 'archivarius_web'


def file_replace(filename: str, search: list[str], replace: list[str]):
	with open(filename) as f:
		data = f.read()

	for i, v in enumerate(search):
		data = data.replace(str(v), str(replace[i]))

	f, newfilename = tempfile.mkstemp()
	os.write(f, str.encode(data))
	os.close(f)
	os.chmod(newfilename, os.stat(filename).st_mode)

	os.replace(newfilename, filename)


def ignore_patterns(*patterns):
	def _ignore_patterns(path, names):
		ignored_names = []
		for pattern in patterns:
			if '/' in str(pattern):
				# Работает только с абсолютным путем
				patternfn_d = os.path.realpath(os.path.dirname(pattern))
				patternfn_n = os.path.basename(pattern)

				if not os.path.relpath(path, start=patternfn_d).startswith(os.pardir):
					ignored_names.extend(fnmatch.filter(names, patternfn_n))
			else:
				ignored_names.extend(fnmatch.filter(names, pattern))

		return set(ignored_names)
	return _ignore_patterns


if __name__ == '__main__':
	base_path = pathlib.Path(os.path.dirname(os.path.realpath(__file__)))
	commit_id = subprocess.check_output(["git", "describe", "--abbrev=7", "--always", "--long"], cwd=base_path).strip().decode()

	dpkg_package_name = re.sub(r'[^-+.a-z]', '-', package_name.lower())
	dpkg_package_version = re.sub(r"^v?(.*)-g\w{7}$", r"\1", commit_id).replace("-", ".")

	with tempfile.TemporaryDirectory() as package_path:
		shutil.copytree(
			base_path,
			os.path.join(package_path, 'usr/share', package_name),
			ignore=ignore_patterns(
				'*.deb',
				'.*',
				'node_modules',
				'public',
				base_path / 'config',
				base_path / 'debian',
				base_path / 'doc',
			)
		)

		shutil.copytree(
			base_path / 'doc',
			os.path.join(package_path, 'usr/share/doc', package_name),
			ignore=ignore_patterns('.*')
		)

		shutil.copytree(
			base_path / 'public',
			os.path.join(package_path, 'var/www', package_name),
			ignore=ignore_patterns(
				'.*',
				'node_modules',
				base_path / 'public/static/storage',
			)
		)

		file_replace(
			os.path.join(package_path, 'var/www', package_name, 'index.php'),
			[
				"""__DIR__ . '/../config/""",
				"""__DIR__ . '/../""",
			],
			[
				"""'/etc/{}/""".format(package_name),
				"""'/usr/share/{}/""".format(package_name),
			],
		)

		file_replace(
			os.path.join(package_path, 'var/www', package_name, 'download_auth.php'),
			[
				"""__DIR__ . '/../config/""",
				"""__DIR__ . '/../""",
			],
			[
				"""'/etc/{}/""".format(package_name),
				"""'/usr/share/{}/""".format(package_name),
			],
		)

		with open(os.path.join(package_path, 'nfpm.yaml'), 'w') as f:
			f.write("""name: "{dpkg_package_name}"
arch: "amd64"
contents:
- src: {package_path}/usr
  dst: /usr
  file_info:
    owner: root
    group: root
- src: {package_path}/var
  dst: /var
  file_info:
    owner: root
    group: root
- src: {base_path}/config/{package_name}_config.php.sample
  dst: /etc/{package_name}/{package_name}_config.php.sample
  type: config
  file_info:
    owner: root
    group: root
description: "Digital Archive web applitation."
maintainer: "Dmitry Petrov <32b100@gmail.com>"
platform: "linux"
section: "default"
version: "{package_version}"
""".format(
	base_path=str(base_path),
	dpkg_package_name=dpkg_package_name,
	package_name=package_name,
	package_path=package_path,
	package_version=dpkg_package_version,
))

		if not os.path.exists(os.path.join(base_path, 'debian')):
			os.makedirs(os.path.join(base_path, 'debian'))

		subprocess.check_call(
			[
				'nfpm',
				'pkg',
				'--config', os.path.join(package_path, 'nfpm.yaml'),
				'--packager', 'deb',
				'--target', os.path.join(base_path, 'debian', '{}_{}.deb'.format(dpkg_package_name, dpkg_package_version))
			],
		)
