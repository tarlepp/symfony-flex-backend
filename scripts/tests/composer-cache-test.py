#!/usr/bin/env python3
"""Verify the CI Composer cache boundary without downloading dependencies."""

import os
from pathlib import Path
import subprocess
import tempfile
import unittest


ROOT = Path(__file__).resolve().parents[2]


class ComposerCacheTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory(prefix="composer-cache-")
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        self.cache = self.root / "cache"
        composer = self.root / "composer"
        composer.write_text(
            "#!/usr/bin/env bash\n"
            "set -eu\n"
            '[ "$*" = "config cache-dir" ] || exit 64\n'
            'printf "%s\\n" "$ACTUAL_CACHE"\n'
            'exit "${COMPOSER_EXIT:-0}"\n'
        )
        composer.chmod(0o755)

    def run_check(self, **overrides):
        environment = dict(
            os.environ,
            PATH=f"{self.root}:{os.environ['PATH']}",
            COMPOSER_CACHE_DIR=str(self.cache),
            ACTUAL_CACHE=str(self.cache),
            COMPOSER_EXIT="0",
        )
        environment.update(overrides)
        return subprocess.run(
            ["bash", str(ROOT / "scripts/check-composer-cache.sh")],
            env=environment, text=True, capture_output=True, check=False,
            timeout=10,
        )

    def test_creates_writable_cache(self):
        result = self.run_check()
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertTrue(self.cache.is_dir())
        self.assertIn("mounted and writable", result.stdout)

    def test_preserves_existing_downloads(self):
        self.cache.mkdir()
        download = self.cache / "download.zip"
        download.write_bytes(b"existing download")
        result = self.run_check()
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(download.read_bytes(), b"existing download")

    def test_requires_explicit_container_cache(self):
        result = self.run_check(COMPOSER_CACHE_DIR="")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("must identify", result.stderr)
        self.assertFalse(self.cache.exists())

    def test_rejects_cache_path_mismatch(self):
        result = self.run_check(ACTUAL_CACHE=str(self.root / "unmounted"))
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("does not match", result.stderr)
        self.assertFalse(self.cache.exists())

    def test_propagates_composer_failure(self):
        result = self.run_check(COMPOSER_EXIT="7")
        self.assertEqual(result.returncode, 7)
        self.assertFalse(self.cache.exists())
        self.assertNotIn("mounted and writable", result.stdout)

    def test_rejects_non_directory_cache(self):
        self.cache.write_text("not a directory")
        result = self.run_check()
        self.assertNotEqual(result.returncode, 0)
        self.assertNotIn("mounted and writable", result.stdout)

    @unittest.skipIf(os.getuid() == 0, "Root bypasses directory write permissions")
    def test_rejects_unwritable_cache(self):
        self.cache.mkdir()
        self.cache.chmod(0o555)
        self.addCleanup(self.cache.chmod, 0o755)
        result = self.run_check()
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("not writable", result.stderr)


if __name__ == "__main__":
    unittest.main()
