"""
End-to-end test script for the Visual AI module.

Tests:
  Phase A — Unit tests (no network, pure logic):
    1. Gap detector: identical screens → NO_GAP
    2. Gap detector: different text → MINOR+
    3. Gap detector: error elements → penalty applied
    4. Gap detector: totally different → CRITICAL
    5. Gap detector: severity thresholds correct
    6. Guidance engine: rule-based for each condition
    7. Guidance engine: suggested actions generated
    8. Schemas: round-trip serialization
    9. Screenshot store: save / read / delete
   10. Cosine similarity: edge cases

  Phase B — API integration tests (via HTTP):
   11. Upload screenshot (with consent)
   12. Upload screenshot (without consent → 400)
   13. Upload non-image → 400
   14. Get screenshot by ID
   15. List screenshots
   16. Analyze stored screenshot
   17. Analyze raw image
   18. Get analysis by ID
   19. Create reference screen
   20. List references
   21. Get reference by ID
   22. Detect gap (analysis vs reference)
   23. Generate guidance
   24. Full pipeline: process endpoint
   25. Get timeline
   26. Delete reference
   27. Duplicate reference key → 409

Usage:
    python tests/test_visual_ai.py
"""

import requests
import json
import sys
import os
import io
import struct
import zlib
import time
import uuid

BASE = "http://localhost:8000/api/v1"

passed = 0
failed = 0
total_tests = 0


def pp(data):
    print(json.dumps(data, indent=2, default=str))


def check(label: str, ok: bool, detail: str = ""):
    global passed, failed, total_tests
    total_tests += 1
    if ok:
        passed += 1
        print(f"  [\033[92mPASS\033[0m] {label}")
    else:
        failed += 1
        print(f"  [\033[91mFAIL\033[0m] {label} — {detail}")


def make_png(width=100, height=80, r=200, g=100, b=50):
    """Generate a minimal valid PNG image in memory."""
    def create_chunk(chunk_type, data):
        chunk = chunk_type + data
        return struct.pack('>I', len(data)) + chunk + struct.pack('>I', zlib.crc32(chunk) & 0xFFFFFFFF)

    signature = b'\x89PNG\r\n\x1a\n'
    ihdr_data = struct.pack('>IIBBBBB', width, height, 8, 2, 0, 0, 0)
    ihdr = create_chunk(b'IHDR', ihdr_data)

    raw_data = b''
    for _ in range(height):
        raw_data += b'\x00'  # filter byte
        raw_data += bytes([r, g, b]) * width

    compressed = zlib.compress(raw_data)
    idat = create_chunk(b'IDAT', compressed)
    iend = create_chunk(b'IEND', b'')

    return signature + ihdr + idat + iend


def make_png_with_text(text="Hello World", width=200, height=100):
    """Generate a simple PNG. Text won't be OCR-readable but image is valid."""
    return make_png(width, height, r=240, g=240, b=240)


# ═══════════════════════════════════════════════════════════
#  PHASE A: Unit Tests (pure logic, no network)
# ═══════════════════════════════════════════════════════════

def run_unit_tests():
    print("\n" + "=" * 70)
    print("PHASE A: UNIT TESTS (gap detector, guidance, schemas, store)")
    print("=" * 70)

    # ── Import modules ────────────────────────────────────
    sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))
    from app.visual_ai.gap_detector import (
        detect_gap, _cosine_similarity, _text_diff_ratio,
        _element_diff, _error_penalty, _severity_from_score,
    )
    from app.visual_ai.schemas import (
        FullAnalysisResult, OCRResult, UIAnalysisResult, UIElement,
        GapDiff, GapResult, GuidanceResponse,
    )
    from app.visual_ai.enums import GapSeverity, UIElementType
    from app.visual_ai.guidance import generate_rule_guidance, _determine_condition

    # ── Test 1: Identical screens → NO_GAP ────────────────
    print("\n--- Test 1: Identical screens → NO_GAP ---")
    embedding = [0.1] * 512
    analysis = FullAnalysisResult(
        ocr=OCRResult(text="Login page welcome", confidence=0.9, word_count=3),
        ui_analysis=UIAnalysisResult(
            caption="Login page",
            elements=[
                UIElement(element_type=UIElementType.INPUT_FIELD, label="email", confidence=0.9),
                UIElement(element_type=UIElementType.BUTTON, label="submit", confidence=0.9),
            ],
        ),
        embedding=embedding,
    )
    result = detect_gap(
        analysis,
        reference_embedding=embedding,
        reference_ocr_text="Login page welcome",
        reference_elements=[
            {"element_type": "INPUT_FIELD", "label": "email"},
            {"element_type": "BUTTON", "label": "submit"},
        ],
    )
    check("Gap score ≈ 0 for identical", result.gap_score < 0.05, f"got {result.gap_score}")
    check("Severity is NO_GAP", result.severity == GapSeverity.NO_GAP, f"got {result.severity}")
    check("Has guidance hints", len(result.guidance_hints) > 0)

    # ── Test 2: Different OCR text → MINOR gap ────────────
    print("\n--- Test 2: Different OCR text → gap detected ---")
    analysis2 = FullAnalysisResult(
        ocr=OCRResult(text="Dashboard overview statistics", confidence=0.9, word_count=3),
        ui_analysis=UIAnalysisResult(
            caption="Dashboard",
            elements=[
                UIElement(element_type=UIElementType.BUTTON, label="submit", confidence=0.9),
            ],
        ),
        embedding=embedding,
    )
    result2 = detect_gap(
        analysis2,
        reference_embedding=embedding,
        reference_ocr_text="Login page welcome form",
        reference_elements=[
            {"element_type": "INPUT_FIELD", "label": "email"},
            {"element_type": "BUTTON", "label": "submit"},
        ],
    )
    check("Gap score > 0 for different text", result2.gap_score > 0.05, f"got {result2.gap_score}")
    check("Missing keywords present", len(result2.diffs.missing_keywords) > 0, f"got {result2.diffs.missing_keywords}")
    check("Text diff ratio > 0", result2.diffs.text_diff_ratio > 0, f"got {result2.diffs.text_diff_ratio}")

    # ── Test 3: Error elements → penalty ──────────────────
    print("\n--- Test 3: Error elements → error penalty ---")
    analysis3 = FullAnalysisResult(
        ocr=OCRResult(text="Error: Invalid credentials", confidence=0.9),
        ui_analysis=UIAnalysisResult(
            caption="Error page",
            elements=[
                UIElement(element_type=UIElementType.ERROR_MESSAGE, label="Invalid credentials", confidence=0.95),
            ],
        ),
        embedding=embedding,
    )
    result3 = detect_gap(
        analysis3,
        reference_embedding=embedding,
        reference_ocr_text="Login page welcome",
        reference_elements=[{"element_type": "INPUT_FIELD"}],
    )
    check("Error penalty = 1.0", result3.diffs.error_penalty == 1.0, f"got {result3.diffs.error_penalty}")
    check("Gap score includes penalty", result3.gap_score > 0.1, f"got {result3.gap_score}")

    # ── Test 4: Totally different → SIGNIFICANT/CRITICAL ──
    print("\n--- Test 4: Totally different → high gap ---")
    diff_embedding = [-x for x in embedding]  # opposite direction
    analysis4 = FullAnalysisResult(
        ocr=OCRResult(text="completely unrelated content xyz", confidence=0.9),
        ui_analysis=UIAnalysisResult(
            caption="Unknown page",
            elements=[
                UIElement(element_type=UIElementType.ERROR_MESSAGE, label="crash", confidence=0.9),
            ],
        ),
        embedding=diff_embedding,
    )
    result4 = detect_gap(
        analysis4,
        reference_embedding=embedding,
        reference_ocr_text="Login page welcome email password submit",
        reference_elements=[
            {"element_type": "INPUT_FIELD"},
            {"element_type": "BUTTON"},
            {"element_type": "FORM"},
        ],
    )
    check("Gap score >= 0.70 (CRITICAL)", result4.gap_score >= 0.70, f"got {result4.gap_score}")
    check("Severity CRITICAL", result4.severity == GapSeverity.CRITICAL, f"got {result4.severity}")

    # ── Test 5: Severity thresholds ───────────────────────
    print("\n--- Test 5: Severity thresholds ---")
    check("Score 0.0 → NO_GAP", _severity_from_score(0.0) == GapSeverity.NO_GAP)
    check("Score 0.10 → NO_GAP", _severity_from_score(0.10) == GapSeverity.NO_GAP)
    check("Score 0.15 → MINOR", _severity_from_score(0.15) == GapSeverity.MINOR)
    check("Score 0.30 → MINOR", _severity_from_score(0.30) == GapSeverity.MINOR)
    check("Score 0.40 → SIGNIFICANT", _severity_from_score(0.40) == GapSeverity.SIGNIFICANT)
    check("Score 0.60 → SIGNIFICANT", _severity_from_score(0.60) == GapSeverity.SIGNIFICANT)
    check("Score 0.70 → CRITICAL", _severity_from_score(0.70) == GapSeverity.CRITICAL)
    check("Score 1.00 → CRITICAL", _severity_from_score(1.00) == GapSeverity.CRITICAL)

    # ── Test 6: Guidance rule templates ───────────────────
    print("\n--- Test 6: Guidance engine — rule-based ---")

    # SUCCESS condition
    gap_success = GapResult(gap_score=0.0, severity=GapSeverity.NO_GAP, diffs=GapDiff())
    guidance_ok = generate_rule_guidance(gap_success)
    check("SUCCESS guidance has text", len(guidance_ok.rule_based_guidance) > 0)
    check("SUCCESS confidence high", guidance_ok.confidence >= 0.9, f"got {guidance_ok.confidence}")
    condition = _determine_condition(gap_success)
    check("Condition is SUCCESS_STATE", condition == "SUCCESS_STATE", f"got {condition}")

    # ERROR condition
    gap_error = GapResult(
        gap_score=0.6, severity=GapSeverity.SIGNIFICANT,
        diffs=GapDiff(error_penalty=1.0),
    )
    guidance_err = generate_rule_guidance(gap_error)
    check("ERROR guidance has text", "error" in guidance_err.rule_based_guidance.lower())
    condition_err = _determine_condition(gap_error)
    check("Condition is ERROR_DETECTED", condition_err == "ERROR_DETECTED", f"got {condition_err}")

    # WRONG_PAGE condition
    gap_wrong = GapResult(
        gap_score=0.5, severity=GapSeverity.SIGNIFICANT,
        diffs=GapDiff(visual_similarity=0.3),
    )
    condition_wp = _determine_condition(gap_wrong)
    check("Condition is WRONG_PAGE", condition_wp == "WRONG_PAGE", f"got {condition_wp}")

    # LOADING condition
    gap_loading = GapResult(
        gap_score=0.3, severity=GapSeverity.MINOR,
        diffs=GapDiff(error_penalty=0.5),
    )
    condition_load = _determine_condition(gap_loading)
    check("Condition is LOADING_STATE", condition_load == "LOADING_STATE", f"got {condition_load}")

    # ── Test 7: Suggested actions ─────────────────────────
    print("\n--- Test 7: Suggested actions ---")
    gap_missing = GapResult(
        gap_score=0.35, severity=GapSeverity.MINOR,
        diffs=GapDiff(missing_elements=["BUTTON", "INPUT_FIELD"], visual_similarity=0.8),
    )
    guidance_miss = generate_rule_guidance(gap_missing)
    check("Has suggested actions", len(guidance_miss.suggested_actions) > 0, f"got {len(guidance_miss.suggested_actions)}")
    check("Actions mention missing elements", any("BUTTON" in a or "INPUT" in a for a in guidance_miss.suggested_actions),
          f"actions={guidance_miss.suggested_actions}")

    # ── Test 8: Schema round-trip ─────────────────────────
    print("\n--- Test 8: Schema serialization ---")
    full = FullAnalysisResult(
        ocr=OCRResult(text="test", confidence=0.9, word_count=1),
        ui_analysis=UIAnalysisResult(caption="cap", elements=[], labels=["foo"]),
        embedding=[0.5] * 10,
        provider="test",
        processing_ms=100,
        confidence=0.8,
    )
    d = full.model_dump()
    restored = FullAnalysisResult(**d)
    check("OCR text round-trips", restored.ocr.text == "test")
    check("Embedding round-trips", len(restored.embedding) == 10)
    check("Provider round-trips", restored.provider == "test")

    gap_d = GapResult(
        gap_score=0.45,
        severity=GapSeverity.SIGNIFICANT,
        diffs=GapDiff(visual_similarity=0.7, missing_elements=["BUTTON"]),
        guidance_hints=["hint1"],
    ).model_dump()
    gap_r = GapResult(**gap_d)
    check("GapResult round-trips", gap_r.gap_score == 0.45)
    check("GapDiff round-trips", gap_r.diffs.missing_elements == ["BUTTON"])

    # ── Test 9: Screenshot store ──────────────────────────
    print("\n--- Test 9: Screenshot store (save/read/delete) ---")
    from app.visual_ai.screenshot_store import save_screenshot, read_screenshot, delete_screenshot

    test_png = make_png(50, 50)
    fpath, fsize = save_screenshot(test_png, filename="test_unit.png", conversation_id="unit_test")
    check("File saved", os.path.exists(fpath), f"path={fpath}")
    check("Size matches", fsize == len(test_png), f"expected {len(test_png)}, got {fsize}")

    read_back = read_screenshot(fpath)
    check("Read back matches", read_back == test_png, f"len read={len(read_back)}, expected={len(test_png)}")

    delete_screenshot(fpath)
    check("File deleted", not os.path.exists(fpath))

    # ── Test 10: Cosine similarity edge cases ─────────────
    print("\n--- Test 10: Cosine similarity edge cases ---")
    check("Empty vectors → 0", _cosine_similarity([], []) == 0.0)
    check("Mismatched length → 0", _cosine_similarity([1.0], [1.0, 2.0]) == 0.0)
    check("Zero vector → 0", _cosine_similarity([0.0, 0.0], [1.0, 2.0]) == 0.0)
    check("Identical → 1.0", abs(_cosine_similarity([1.0, 0.0], [1.0, 0.0]) - 1.0) < 0.001)
    check("Opposite → -1.0", abs(_cosine_similarity([1.0, 0.0], [-1.0, 0.0]) - (-1.0)) < 0.001)
    check("Orthogonal → 0", abs(_cosine_similarity([1.0, 0.0], [0.0, 1.0])) < 0.001)


# ═══════════════════════════════════════════════════════════
#  PHASE B: API Integration Tests
# ═══════════════════════════════════════════════════════════

def run_api_tests():
    print("\n" + "=" * 70)
    print("PHASE B: API INTEGRATION TESTS")
    print("=" * 70)

    # ── Login ─────────────────────────────────────────────
    print("\n--- Login (admin) ---")
    resp = requests.post(f"{BASE}/auth/login", json={
        "email": "admin@test.com",
        "password": "Admin123!",
    })
    if resp.status_code != 200:
        print(f"  [FATAL] Login failed ({resp.status_code}): {resp.text[:200]}")
        return
    token = resp.json()["access_token"]
    headers = {"Authorization": f"Bearer {token}"}
    print(f"  [OK] Logged in, token={token[:20]}...")

    test_png = make_png(200, 150, r=100, g=150, b=200)
    screenshot_id = None
    analysis_id = None
    reference_id = None

    # ── Test 11: Upload screenshot (with consent) ─────────
    print("\n--- Test 11: Upload screenshot (with consent) ---")
    resp = requests.post(
        f"{BASE}/visual-ai/upload",
        headers=headers,
        files={"file": ("test_screenshot.png", io.BytesIO(test_png), "image/png")},
        data={"consent": "true"},
    )
    check("Upload returns 201", resp.status_code == 201, f"got {resp.status_code}: {resp.text[:200]}")
    if resp.status_code == 201:
        data = resp.json()
        screenshot_id = data["id"]
        check("Has screenshot ID", screenshot_id is not None)
        check("Filename preserved", data.get("filename") == "test_screenshot.png", f"got {data.get('filename')}")
        check("File size > 0", data.get("file_size", 0) > 0, f"got {data.get('file_size')}")
        check("Consent is true", data.get("consent") is True)
        check("MIME type correct", data.get("mime_type") == "image/png", f"got {data.get('mime_type')}")
        print(f"  Screenshot ID: {screenshot_id}")

    # ── Test 12: Upload without consent → 400 ─────────────
    print("\n--- Test 12: Upload without consent → 400 ---")
    resp = requests.post(
        f"{BASE}/visual-ai/upload",
        headers=headers,
        files={"file": ("nocon.png", io.BytesIO(test_png), "image/png")},
        data={"consent": "false"},
    )
    check("No consent → 400", resp.status_code == 400, f"got {resp.status_code}")
    if resp.status_code == 400:
        check("Error mentions consent", "consent" in resp.json().get("detail", "").lower(), f"detail={resp.json().get('detail')}")

    # ── Test 13: Upload non-image → 400 ──────────────────
    print("\n--- Test 13: Upload non-image → 400 ---")
    resp = requests.post(
        f"{BASE}/visual-ai/upload",
        headers=headers,
        files={"file": ("test.txt", io.BytesIO(b"not an image"), "text/plain")},
        data={"consent": "true"},
    )
    check("Non-image → 400", resp.status_code == 400, f"got {resp.status_code}")

    # ── Test 14: Get screenshot by ID ─────────────────────
    print("\n--- Test 14: Get screenshot by ID ---")
    if screenshot_id:
        resp = requests.get(f"{BASE}/visual-ai/screenshots/{screenshot_id}", headers=headers)
        check("Get screenshot → 200", resp.status_code == 200, f"got {resp.status_code}")
        if resp.status_code == 200:
            data = resp.json()
            check("ID matches", data["id"] == screenshot_id)
    else:
        check("Get screenshot (skipped — no ID)", False, "no screenshot_id")

    # ── Test 15: List screenshots ─────────────────────────
    print("\n--- Test 15: List screenshots ---")
    resp = requests.get(f"{BASE}/visual-ai/screenshots", headers=headers)
    check("List screenshots → 200", resp.status_code == 200, f"got {resp.status_code}")
    if resp.status_code == 200:
        data = resp.json()
        check("Has items array", "items" in data, f"keys={list(data.keys())}")
        check("Has total count", "total" in data)
        check("Total >= 1", data.get("total", 0) >= 1, f"total={data.get('total')}")

    # ── Test 16: Analyze stored screenshot ────────────────
    print("\n--- Test 16: Analyze stored screenshot ---")
    if screenshot_id:
        resp = requests.post(
            f"{BASE}/visual-ai/screenshots/{screenshot_id}/analyze",
            headers=headers,
            json={},
        )
        check("Analyze → 201", resp.status_code == 201, f"got {resp.status_code}: {resp.text[:300]}")
        if resp.status_code == 201:
            data = resp.json()
            analysis_id = data["id"]
            check("Has analysis ID", analysis_id is not None)
            check("Has provider field", "provider" in data, f"keys={list(data.keys())}")
            check("Provider is local-basic", data.get("provider") == "local-basic", f"got {data.get('provider')}")
            check("Has ocr_text field", "ocr_text" in data)
            check("Has caption field", "caption" in data)
            check("Has elements field", "elements" in data)
            check("Has processing_ms", data.get("processing_ms") is not None)
            print(f"  Analysis ID: {analysis_id}")
            print(f"  Processing: {data.get('processing_ms')}ms")
            print(f"  OCR text: {(data.get('ocr_text') or '')[:100]}")
            print(f"  Elements: {len(data.get('elements') or [])}")
    else:
        check("Analyze (skipped)", False, "no screenshot_id")

    # ── Test 17: Analyze raw image ────────────────────────
    print("\n--- Test 17: Analyze raw image (no persist) ---")
    resp = requests.post(
        f"{BASE}/visual-ai/analyze-raw",
        headers=headers,
        files={"file": ("raw_test.png", io.BytesIO(test_png), "image/png")},
    )
    check("Analyze raw → 200", resp.status_code == 200, f"got {resp.status_code}: {resp.text[:300]}")
    if resp.status_code == 200:
        data = resp.json()
        check("Has ocr field", "ocr" in data)
        check("Has ui_analysis field", "ui_analysis" in data)
        check("Has embedding field", "embedding" in data)
        check("Embedding is list", isinstance(data.get("embedding"), list))
        emb_len = len(data.get("embedding", []))
        check(f"Embedding dim = 512", emb_len == 512, f"got {emb_len}")
        check("Has provider field", "provider" in data)

    # ── Test 18: Get analysis by ID ───────────────────────
    print("\n--- Test 18: Get analysis by ID ---")
    if analysis_id:
        resp = requests.get(f"{BASE}/visual-ai/analysis/{analysis_id}", headers=headers)
        check("Get analysis → 200", resp.status_code == 200, f"got {resp.status_code}")
        if resp.status_code == 200:
            data = resp.json()
            check("ID matches", data["id"] == analysis_id)
    else:
        check("Get analysis (skipped)", False, "no analysis_id")

    # ── Test 19: Create reference screen ──────────────────
    print("\n--- Test 19: Create reference screen ---")
    # Cleanup: delete any existing reference with this key from prior runs
    _refs_resp = requests.get(f"{BASE}/visual-ai/references?limit=200", headers=headers)
    if _refs_resp.status_code == 200:
        for _ref in _refs_resp.json().get("items", []):
            if _ref.get("screen_key") == "login_page_test":
                requests.delete(f"{BASE}/visual-ai/references/{_ref['id']}", headers=headers)
                print(f"  [cleanup] Deleted old reference {_ref['id']}")

    ref_png = make_png(200, 150, r=50, g=200, b=50)
    resp = requests.post(
        f"{BASE}/visual-ai/references",
        headers=headers,
        files={"file": ("ref_login.png", io.BytesIO(ref_png), "image/png")},
        data={
            "name": "Login Page Reference",
            "screen_key": "login_page_test",
            "description": "Expected login page layout",
            "expected_ocr_text": "Login Email Password Submit",
            "expected_elements": json.dumps([
                {"element_type": "INPUT_FIELD", "label": "email"},
                {"element_type": "INPUT_FIELD", "label": "password"},
                {"element_type": "BUTTON", "label": "submit"},
            ]),
        },
    )
    check("Create reference → 201", resp.status_code == 201, f"got {resp.status_code}: {resp.text[:300]}")
    if resp.status_code == 201:
        data = resp.json()
        reference_id = data["id"]
        check("Has reference ID", reference_id is not None)
        check("Screen key correct", data.get("screen_key") == "login_page_test")
        check("Name correct", data.get("name") == "Login Page Reference")
        check("Has file_path", data.get("file_path") is not None)
        check("Has expected_elements", data.get("expected_elements") is not None)
        print(f"  Reference ID: {reference_id}")

    # ── Test 20: List references ──────────────────────────
    print("\n--- Test 20: List references ---")
    resp = requests.get(f"{BASE}/visual-ai/references", headers=headers)
    check("List references → 200", resp.status_code == 200, f"got {resp.status_code}")
    if resp.status_code == 200:
        data = resp.json()
        check("Has items", "items" in data)
        check("Has total", "total" in data)
        check("Total >= 1", data.get("total", 0) >= 1, f"got {data.get('total')}")

    # ── Test 21: Get reference by ID ──────────────────────
    print("\n--- Test 21: Get reference by ID ---")
    if reference_id:
        resp = requests.get(f"{BASE}/visual-ai/references/{reference_id}", headers=headers)
        check("Get reference → 200", resp.status_code == 200, f"got {resp.status_code}")
        if resp.status_code == 200:
            check("ID matches", resp.json()["id"] == reference_id)
    else:
        check("Get reference (skipped)", False, "no reference_id")

    # ── Test 22: Detect gap ───────────────────────────────
    print("\n--- Test 22: Detect gap (analysis vs reference) ---")
    if analysis_id and reference_id:
        resp = requests.post(
            f"{BASE}/visual-ai/analysis/{analysis_id}/detect-gap",
            headers=headers,
            json={"reference_key": "login_page_test"},
        )
        check("Detect gap → 200", resp.status_code == 200, f"got {resp.status_code}: {resp.text[:300]}")
        if resp.status_code == 200:
            data = resp.json()
            check("Has gap_score", "gap_score" in data)
            check("gap_score in [0,1]", 0 <= data.get("gap_score", -1) <= 1, f"got {data.get('gap_score')}")
            check("Has severity", "severity" in data)
            check("Has diffs", "diffs" in data)
            check("Has guidance_hints", "guidance_hints" in data)
            check("Diffs has visual_similarity", "visual_similarity" in data.get("diffs", {}))
            check("Diffs has text_diff_ratio", "text_diff_ratio" in data.get("diffs", {}))
            print(f"  Gap score: {data.get('gap_score')}")
            print(f"  Severity: {data.get('severity')}")
    else:
        check("Gap detection (skipped)", False, "missing IDs")

    # ── Test 23: Generate guidance ────────────────────────
    print("\n--- Test 23: Generate guidance ---")
    if analysis_id:
        resp = requests.post(
            f"{BASE}/visual-ai/analysis/{analysis_id}/guidance",
            headers=headers,
            json={"reference_key": "login_page_test"} if reference_id else {},
        )
        check("Guidance → 200", resp.status_code == 200, f"got {resp.status_code}: {resp.text[:300]}")
        if resp.status_code == 200:
            data = resp.json()
            check("Has rule_based_guidance", len(data.get("rule_based_guidance", "")) > 0)
            check("Has suggested_actions", "suggested_actions" in data)
            check("Has confidence", "confidence" in data)
            check("Confidence in [0,1]", 0 <= data.get("confidence", -1) <= 1)
            print(f"  Guidance: {data.get('rule_based_guidance', '')[:120]}...")
            print(f"  Actions: {data.get('suggested_actions', [])[:3]}")
    else:
        check("Guidance (skipped)", False, "no analysis_id")

    # ── Test 24: Full pipeline ────────────────────────────
    print("\n--- Test 24: Full pipeline (process endpoint) ---")
    pipeline_png = make_png(180, 120, r=80, g=80, b=200)
    resp = requests.post(
        f"{BASE}/visual-ai/process",
        headers=headers,
        files={"file": ("pipeline.png", io.BytesIO(pipeline_png), "image/png")},
        data={
            "consent": "true",
            "reference_key": "login_page_test" if reference_id else "",
        },
    )
    check("Process → 201", resp.status_code == 201, f"got {resp.status_code}: {resp.text[:400]}")
    if resp.status_code == 201:
        data = resp.json()
        check("Has screenshot in result", "screenshot" in data)
        check("Has analysis in result", "analysis" in data)
        check("Screenshot has ID", data.get("screenshot", {}).get("id") is not None)
        check("Analysis has ID", data.get("analysis", {}).get("id") is not None)
        check("Analysis has provider", data.get("analysis", {}).get("provider") is not None)
        if reference_id:
            check("Has gap_result", "gap_result" in data)
            if data.get("gap_result"):
                check("Gap has score", "gap_score" in data["gap_result"])
        print(f"  Pipeline result keys: {list(data.keys())}")

    # ── Test 25: Timeline (needs conversation_id) ─────────
    print("\n--- Test 25: Timeline ---")
    # Create a real conversation so the FK constraint is satisfied
    conv_resp = requests.post(
        f"{BASE}/conversations",
        headers=headers,
        json={"channel": "CHAT", "subject": "Visual AI Timeline Test"},
    )
    if conv_resp.status_code == 201:
        conv_id = conv_resp.json()["id"]
        print(f"  Created conversation: {conv_id}")
    else:
        print(f"  Conv create failed: {conv_resp.status_code} {conv_resp.text[:200]}")
        # Fallback: list existing and pick first
        cl = requests.get(f"{BASE}/conversations", headers=headers)
        items = cl.json().get("items", []) if cl.status_code == 200 else []
        conv_id = items[0]["id"] if items else str(uuid.uuid4())
        print(f"  Using existing conversation: {conv_id}")

    # First upload with conversation
    resp = requests.post(
        f"{BASE}/visual-ai/process",
        headers=headers,
        files={"file": ("timeline1.png", io.BytesIO(make_png(100, 80, 200, 50, 50)), "image/png")},
        data={"consent": "true", "conversation_id": conv_id},
    )
    if resp.status_code == 201:
        # Second upload to same conversation
        resp = requests.post(
            f"{BASE}/visual-ai/process",
            headers=headers,
            files={"file": ("timeline2.png", io.BytesIO(make_png(100, 80, 50, 50, 200)), "image/png")},
            data={"consent": "true", "conversation_id": conv_id},
        )

    resp = requests.get(f"{BASE}/visual-ai/timeline/{conv_id}", headers=headers)
    check("Timeline → 200", resp.status_code == 200, f"got {resp.status_code}: {resp.text[:200]}")
    if resp.status_code == 200:
        data = resp.json()
        check("Has states array", "states" in data)
        check("Has total_states", "total_states" in data)
        check("Total states >= 1", data.get("total_states", 0) >= 1, f"got {data.get('total_states')}")
        check("Has gaps_detected count", "gaps_detected" in data)
        if data.get("states"):
            st = data["states"][0]
            check("State has sequence_num", "sequence_num" in st)
            check("State has gap_detected", "gap_detected" in st)
        print(f"  Timeline: {data.get('total_states')} states, {data.get('gaps_detected')} gaps")

    # ── Test 26: Delete reference ─────────────────────────
    print("\n--- Test 26: Delete reference ---")
    if reference_id:
        resp = requests.delete(f"{BASE}/visual-ai/references/{reference_id}", headers=headers)
        check("Delete reference → 204", resp.status_code == 204, f"got {resp.status_code}")

        # Verify deleted
        resp = requests.get(f"{BASE}/visual-ai/references/{reference_id}", headers=headers)
        check("Deleted ref → 404", resp.status_code == 404, f"got {resp.status_code}")
    else:
        check("Delete (skipped)", False, "no reference_id")

    # ── Test 27: Duplicate ref key → 409 ──────────────────
    print("\n--- Test 27: Duplicate reference key → 409 ---")
    # Cleanup: remove any leftover dup_key_test from prior runs
    _refs_resp2 = requests.get(f"{BASE}/visual-ai/references?limit=200", headers=headers)
    if _refs_resp2.status_code == 200:
        for _ref in _refs_resp2.json().get("items", []):
            if _ref.get("screen_key") == "dup_key_test":
                requests.delete(f"{BASE}/visual-ai/references/{_ref['id']}", headers=headers)

    dup_png = make_png(100, 100)
    # Create first
    resp1 = requests.post(
        f"{BASE}/visual-ai/references",
        headers=headers,
        files={"file": ("dup1.png", io.BytesIO(dup_png), "image/png")},
        data={"name": "Dup Test", "screen_key": "dup_key_test"},
    )
    if resp1.status_code == 201:
        dup_ref_id = resp1.json()["id"]
        # Try duplicate
        resp2 = requests.post(
            f"{BASE}/visual-ai/references",
            headers=headers,
            files={"file": ("dup2.png", io.BytesIO(dup_png), "image/png")},
            data={"name": "Dup Test 2", "screen_key": "dup_key_test"},
        )
        check("Duplicate key → 409", resp2.status_code == 409, f"got {resp2.status_code}: {resp2.text[:200]}")

        # Cleanup
        requests.delete(f"{BASE}/visual-ai/references/{dup_ref_id}", headers=headers)
    else:
        check("Duplicate test (skipped)", False, f"first create failed: {resp1.status_code}")


# ═══════════════════════════════════════════════════════════
#  Main
# ═══════════════════════════════════════════════════════════

def main():
    global passed, failed, total_tests
    start = time.time()

    run_unit_tests()
    run_api_tests()

    elapsed = time.time() - start
    print("\n" + "=" * 70)
    print(f"RESULTS: {passed}/{total_tests} passed, {failed} failed  ({elapsed:.1f}s)")
    print("=" * 70)

    if failed:
        print(f"\n\033[91m{failed} test(s) FAILED\033[0m")
        sys.exit(1)
    else:
        print(f"\n\033[92mAll {total_tests} tests PASSED!\033[0m")


if __name__ == "__main__":
    main()
