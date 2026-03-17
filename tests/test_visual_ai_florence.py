"""
Test: Visual AI module with the local-advanced (Florence-2) provider.

Verifies:
  1. Provider resolves to local-advanced when passed explicitly
  2. Upload + analyze with provider=local-advanced → OCR + caption + elements + embedding
  3. Full pipeline (/process) with provider=local-advanced
  4. Gap detection works with Florence analysis
  5. Guidance generation from Florence analysis

Usage:
    docker exec support_api python -u tests/test_visual_ai_florence.py
"""

import requests
import json
import sys
import io
import struct
import zlib
import time
import uuid

BASE = "http://localhost:8000/api/v1"
passed = 0
failed = 0
total_tests = 0


def check(label, condition, detail=""):
    global passed, failed, total_tests
    total_tests += 1
    if condition:
        passed += 1
        print(f"  [PASS] {label}")
    else:
        failed += 1
        extra = f" — {detail}" if detail else ""
        print(f"  [FAIL] {label}{extra}")


def make_png(w=120, h=100, r=100, g=150, b=200):
    raw = b""
    for y in range(h):
        raw += b"\x00"
        for x in range(w):
            raw += bytes([max(0, min(255, r + x % 30)), max(0, min(255, g + y % 20)), b])
    ihdr = struct.pack(">IIBBBBB", w, h, 8, 2, 0, 0, 0)
    def chunk(ct, d):
        c = ct + d
        crc = zlib.crc32(c) & 0xFFFFFFFF
        return len(d).to_bytes(4, "big") + c + crc.to_bytes(4, "big")
    return b"\x89PNG\r\n\x1a\n" + chunk(b"IHDR", ihdr) + chunk(b"IDAT", zlib.compress(raw)) + chunk(b"IEND", b"")


def main():
    start = time.time()

    # ── Login ─────────────────────────────────────────────
    print("\n--- Login (admin) ---")
    resp = requests.post(f"{BASE}/auth/login", json={
        "email": "admin@test.com",
        "password": "Admin123!",
    }, timeout=30)
    if resp.status_code != 200:
        print(f"  [FATAL] Login failed ({resp.status_code}): {resp.text[:200]}")
        sys.exit(1)
    token = resp.json()["access_token"]
    headers = {"Authorization": f"Bearer {token}"}
    print(f"  [OK] Logged in")

    # ── Test 1: Upload screenshot ─────────────────────────
    print("\n--- Test 1: Upload screenshot ---")
    png = make_png(150, 120, 80, 120, 200)
    resp = requests.post(
        f"{BASE}/visual-ai/upload",
        headers=headers,
        files={"file": ("florence_test.png", io.BytesIO(png), "image/png")},
        data={"consent": "true"},
        timeout=30,
    )
    check("Upload → 201", resp.status_code == 201, f"got {resp.status_code}")
    screenshot_id = None
    if resp.status_code == 201:
        screenshot_id = resp.json()["id"]
        check("Has screenshot ID", screenshot_id is not None)
        print(f"  Screenshot ID: {screenshot_id}")

    # ── Test 2: Analyze with provider=local-advanced ──────
    print("\n--- Test 2: Analyze with local-advanced provider ---")
    analysis_id = None
    if screenshot_id:
        t1 = time.time()
        resp = requests.post(
            f"{BASE}/visual-ai/screenshots/{screenshot_id}/analyze",
            headers=headers,
            json={"provider": "local-advanced"},
            timeout=300,
        )
        elapsed = time.time() - t1
        check("Analyze → 201", resp.status_code == 201, f"got {resp.status_code}: {resp.text[:300]}")
        if resp.status_code == 201:
            data = resp.json()
            analysis_id = data["id"]
            check("Has analysis ID", analysis_id is not None)
            check("Provider is local-advanced", data.get("provider") == "local-advanced", f"got {data.get('provider')}")
            check("Has ocr_text field", "ocr_text" in data)
            check("Has caption field", "caption" in data)
            check("Has elements field", "elements" in data)
            check("Has processing_ms", "processing_ms" in data)
            print(f"  Analysis ID: {analysis_id}")
            print(f"  Processing: {data.get('processing_ms')}ms (wall: {elapsed:.1f}s)")
            print(f"  OCR: {(data.get('ocr_text') or '')[:100]!r}")
            print(f"  Caption: {(data.get('caption') or '')[:100]!r}")
            print(f"  Elements: {len(data.get('elements') or [])}")
    else:
        check("Analyze (skipped)", False, "no screenshot_id")

    # ── Test 3: Analyze raw with provider=local-advanced ──
    print("\n--- Test 3: Analyze raw image (local-advanced) ---")
    raw_png = make_png(100, 80, 200, 100, 50)
    t2 = time.time()
    resp = requests.post(
        f"{BASE}/visual-ai/analyze-raw",
        headers=headers,
        files={"file": ("raw_florence.png", io.BytesIO(raw_png), "image/png")},
        data={"provider": "local-advanced"},
        timeout=300,
    )
    elapsed2 = time.time() - t2
    check("Analyze raw → 200", resp.status_code == 200, f"got {resp.status_code}: {resp.text[:300]}")
    if resp.status_code == 200:
        data = resp.json()
        check("Has ocr", "ocr" in data)
        check("Has ui_analysis", "ui_analysis" in data)
        check("Has embedding", "embedding" in data)
        check("Has provider", "provider" in data)
        check("Provider is local-advanced", data.get("provider") == "local-advanced")
        check("Embedding dim = 512", len(data.get("embedding", [])) == 512)
        if data.get("ui_analysis"):
            check("Has caption in ui_analysis", "caption" in data["ui_analysis"])
        print(f"  Wall time: {elapsed2:.1f}s")
        print(f"  OCR: {data.get('ocr', {}).get('text', '')[:80]!r}")
        print(f"  Caption: {data.get('ui_analysis', {}).get('caption', '')[:80]!r}")

    # ── Test 4: Create reference for gap test ─────────────
    print("\n--- Test 4: Create reference screen ---")
    # Clean up old test refs
    refs = requests.get(f"{BASE}/visual-ai/references?limit=200", headers=headers, timeout=30)
    if refs.status_code == 200:
        for r in refs.json().get("items", []):
            if r.get("screen_key") == "florence_gap_test":
                requests.delete(f"{BASE}/visual-ai/references/{r['id']}", headers=headers, timeout=30)

    ref_png = make_png(150, 120, 50, 200, 50)
    resp = requests.post(
        f"{BASE}/visual-ai/references",
        headers=headers,
        files={"file": ("ref_florence.png", io.BytesIO(ref_png), "image/png")},
        timeout=60,
        data={
            "name": "Florence Gap Test",
            "screen_key": "florence_gap_test",
            "description": "Reference for Florence gap testing",
            "expected_ocr_text": "Login Email Password Submit",
            "expected_elements": json.dumps([
                {"element_type": "INPUT_FIELD", "label": "email"},
                {"element_type": "BUTTON", "label": "submit"},
            ]),
        },
    )
    reference_id = None
    check("Create reference → 201", resp.status_code == 201, f"got {resp.status_code}: {resp.text[:200]}")
    if resp.status_code == 201:
        reference_id = resp.json()["id"]
        print(f"  Reference ID: {reference_id}")

    # ── Test 5: Gap detection with Florence analysis ──────
    print("\n--- Test 5: Gap detection (Florence analysis vs reference) ---")
    if analysis_id and reference_id:
        resp = requests.post(
            f"{BASE}/visual-ai/analysis/{analysis_id}/detect-gap",
            headers=headers,
            json={"reference_key": "florence_gap_test"},
            timeout=60,
        )
        check("Gap detection → 200", resp.status_code == 200, f"got {resp.status_code}: {resp.text[:200]}")
        if resp.status_code == 200:
            data = resp.json()
            check("Has gap_score", "gap_score" in data)
            check("gap_score in [0,1]", 0 <= data.get("gap_score", -1) <= 1)
            check("Has severity", "severity" in data)
            check("Has diffs", "diffs" in data)
            print(f"  Gap score: {data.get('gap_score')}")
            print(f"  Severity: {data.get('severity')}")
    else:
        check("Gap detection (skipped)", False, "missing analysis_id or reference_id")

    # ── Test 6: Guidance from Florence analysis ───────────
    print("\n--- Test 6: Guidance generation ---")
    if analysis_id:
        resp = requests.post(
            f"{BASE}/visual-ai/analysis/{analysis_id}/guidance",
            headers=headers,
            json={},
            timeout=60,
        )
        check("Guidance → 200", resp.status_code == 200, f"got {resp.status_code}")
        if resp.status_code == 200:
            data = resp.json()
            check("Has rule_based_guidance", "rule_based_guidance" in data)
            check("Has suggested_actions", "suggested_actions" in data)
            check("Has confidence", "confidence" in data)
    else:
        check("Guidance (skipped)", False, "no analysis_id")

    # ── Test 7: Full pipeline with local-advanced ─────────
    print("\n--- Test 7: Full pipeline (process) with local-advanced ---")
    pipeline_png = make_png(130, 100, 180, 60, 60)
    t3 = time.time()
    resp = requests.post(
        f"{BASE}/visual-ai/process",
        headers=headers,
        files={"file": ("florence_pipeline.png", io.BytesIO(pipeline_png), "image/png")},
        data={
            "consent": "true",
            "provider": "local-advanced",
            "reference_key": "florence_gap_test" if reference_id else "",
        },
        timeout=300,
    )
    elapsed3 = time.time() - t3
    check("Process → 201", resp.status_code == 201, f"got {resp.status_code}: {resp.text[:400]}")
    if resp.status_code == 201:
        data = resp.json()
        check("Has screenshot", "screenshot" in data)
        check("Has analysis", "analysis" in data)
        check("Screenshot has ID", data.get("screenshot", {}).get("id") is not None)
        check("Analysis has ID", data.get("analysis", {}).get("id") is not None)
        check("Analysis has provider", data.get("analysis", {}).get("provider") is not None)
        if reference_id:
            check("Has gap_result", "gap_result" in data)
        print(f"  Pipeline keys: {list(data.keys())}")
        print(f"  Wall time: {elapsed3:.1f}s")

    # ── Cleanup ───────────────────────────────────────────
    if reference_id:
        requests.delete(f"{BASE}/visual-ai/references/{reference_id}", headers=headers, timeout=30)

    # ── Summary ───────────────────────────────────────────
    elapsed_total = time.time() - start
    print("\n" + "=" * 70)
    print(f"FLORENCE TEST RESULTS: {passed}/{total_tests} passed, {failed} failed  ({elapsed_total:.1f}s)")
    print("=" * 70)

    if failed:
        print(f"\n{failed} test(s) FAILED")
        sys.exit(1)
    else:
        print(f"\nAll {total_tests} tests PASSED!")


if __name__ == "__main__":
    main()
