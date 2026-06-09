import tempfile
import unittest
from pathlib import Path

from app.core.settings import GATEWAY_ROOT, PROJECT_ROOT, Settings


class SettingsEnvFileTest(unittest.TestCase):
    def test_default_env_files_include_project_and_gateway_envs(self):
        self.assertEqual(
            (PROJECT_ROOT / ".env", GATEWAY_ROOT / ".env"),
            Settings.model_config["env_file"],
        )

    def test_gateway_env_overrides_project_env_and_ignores_laravel_keys(self):
        with tempfile.TemporaryDirectory() as tmp:
            tmp_path = Path(tmp)
            project_env = tmp_path / "project.env"
            gateway_env = tmp_path / "gateway.env"

            project_env.write_text(
                "APP_NAME=Pruefungstrainer\n"
                "OLLAMA_MODEL=project-model\n"
                "OLLAMA_TIMEOUT_SEC=120\n",
                encoding="utf-8",
            )
            gateway_env.write_text(
                "OLLAMA_MODEL=gateway-model\n",
                encoding="utf-8",
            )

            settings = Settings(_env_file=(project_env, gateway_env))

        self.assertEqual("gateway-model", settings.OLLAMA_MODEL)
        self.assertEqual(120, settings.OLLAMA_TIMEOUT_SEC)


if __name__ == "__main__":
    unittest.main()
