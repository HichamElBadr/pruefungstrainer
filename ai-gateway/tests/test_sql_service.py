import unittest

from pydantic import ValidationError

from app.schemas.sql import GenerateSqlRequest
from app.services.sql_service import build_sql_prompt


class SqlRequestDifficultyTest(unittest.TestCase):
    def test_sql_prompt_contains_selected_difficulty_rules(self):
        prompt = build_sql_prompt(
            GenerateSqlRequest(
                request_id="sql-hard",
                difficulty="hard",
                language="de",
            )
        )

        self.assertIn("Difficulty: hard", prompt)
        self.assertIn("multiple JOINs", prompt)
        self.assertIn("HAVING", prompt)
        self.assertIn("subqueries", prompt)
        self.assertIn("must match the selected difficulty exactly", prompt)

    def test_invalid_sql_difficulty_is_rejected(self):
        with self.assertRaises(ValidationError):
            GenerateSqlRequest(
                request_id="sql-invalid",
                difficulty="expert",
                language="de",
            )


if __name__ == "__main__":
    unittest.main()
