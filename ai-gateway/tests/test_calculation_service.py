import json
import unittest

from fastapi import HTTPException

from app.services.calculation_service import (
    generate_calculation_exercise,
    validate_calculation_response,
)
from app.schemas.calculation import GenerateCalculationRequest


VALID_RESPONSE = {
    "title": "Dreisatz: Netzwerkkabel kalkulieren",
    "task": "Für 8 Netzwerkkabel bezahlt ein Ausbildungsbetrieb 24 Euro. Wie viel Euro kosten 14 gleich teure Netzwerkkabel?",
    "expected_result": "42",
    "expected_unit": "Euro",
    "sample_solution": "1. 24 Euro / 8 = 3 Euro.\n2. 14 * 3 Euro = 42 Euro.\n3. Das erwartete Ergebnis ist 42 Euro.",
}


class FakeOllamaClient:
    model = "test-model"
    timeout_sec = 120

    def __init__(self, responses):
        self.responses = list(responses)
        self.calls = 0

    async def generate(self, prompt, response_format=None):
        self.calls += 1
        return self.responses.pop(0)


def calculation_request():
    return GenerateCalculationRequest(
        request_id="test",
        difficulty="medium",
        language="de",
        topic="Dreisatz",
    )


class CalculationValidationTest(unittest.TestCase):
    def test_valid_calculation_json_is_accepted(self):
        result = validate_calculation_response(VALID_RESPONSE)

        self.assertEqual("Dreisatz: Netzwerkkabel kalkulieren", result["title"])
        self.assertEqual("42", result["expected_result"])
        self.assertEqual("Euro", result["expected_unit"])

    def test_vague_tasks_are_rejected(self):
        data = {
            **VALID_RESPONSE,
            "task": "Berechnen Sie die Preise für 8 Netzwerkkabel und 14 Adapter im Warenkorb.",
        }

        with self.assertRaises(HTTPException) as raised:
            validate_calculation_response(data)

        self.assertEqual(422, raised.exception.status_code)
        self.assertIn("too vague", raised.exception.detail)

    def test_tasks_with_fewer_than_two_numbers_are_rejected(self):
        data = {
            **VALID_RESPONSE,
            "task": "Ein Server kostet 900 Euro. Berechne den Endpreis für den Einkauf.",
        }

        with self.assertRaises(HTTPException) as raised:
            validate_calculation_response(data)

        self.assertEqual(422, raised.exception.status_code)
        self.assertIn("at least two numeric values", raised.exception.detail)

    def test_non_numeric_expected_result_is_rejected(self):
        data = {
            **VALID_RESPONSE,
            "expected_result": "42 Euro",
        }

        with self.assertRaises(HTTPException) as raised:
            validate_calculation_response(data)

        self.assertEqual(422, raised.exception.status_code)
        self.assertIn("expected_result", raised.exception.detail)


class CalculationRetryTest(unittest.IsolatedAsyncioTestCase):
    async def test_retry_logic_returns_valid_result_if_later_attempt_passes(self):
        client = FakeOllamaClient([
            json.dumps({**VALID_RESPONSE, "task": "Berechnen Sie die Preise für 8 Netzwerkkabel und 14 Adapter."}),
            json.dumps(VALID_RESPONSE),
        ])

        result = await generate_calculation_exercise(calculation_request(), client)

        self.assertEqual(2, client.calls)
        self.assertEqual("42", result["expected_result"])
        self.assertEqual(2, result["meta"]["generation_attempt"])

    async def test_retry_logic_returns_422_if_all_attempts_fail(self):
        client = FakeOllamaClient([
            json.dumps({**VALID_RESPONSE, "task": "Berechnen Sie die Preise für 8 Netzwerkkabel und 14 Adapter."}),
            json.dumps({**VALID_RESPONSE, "expected_result": "42 Euro"}),
            json.dumps({**VALID_RESPONSE, "task": "Nur 8 Kabel sollen berechnet werden."}),
        ])

        with self.assertRaises(HTTPException) as raised:
            await generate_calculation_exercise(calculation_request(), client)

        self.assertEqual(3, client.calls)
        self.assertEqual(422, raised.exception.status_code)
        self.assertIn("after 3 attempts", raised.exception.detail)


if __name__ == "__main__":
    unittest.main()
