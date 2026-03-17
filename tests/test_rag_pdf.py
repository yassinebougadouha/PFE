"""
End-to-end test script for the RAG PDF Documents API.

Tests all PDF-related endpoints:
 1. List documents (empty / populated)
 2. Upload a PDF
 3. Ingest a single PDF
 4. Ingest all PDFs (bulk)
 5. Search ingested PDF content
 6. Stats after PDF ingestion

Prerequisites:
  - generate_test_pdfs.py must have been run first
  - Admin user admin@test.com / Admin123! must exist

Usage:
    python test_rag_pdf.py
"""

import requests
import json
import sys
import os

BASE = "http://localhost:8000/api/v1"

passed = 0
failed = 0


def pp(data):
    print(json.dumps(data, indent=2, default=str))


def check(label: str, ok: bool, detail: str = ""):
    global passed, failed
    if ok:
        passed += 1
        print(f"  [PASS] {label}")
    else:
        failed += 1
        print(f"  [FAIL] {label} — {detail}")


def main():
    global passed, failed

    # ════════════════════════════════════════════════════════
    #  1. LOGIN
    # ════════════════════════════════════════════════════════
    print("=" * 65)
    print("1. LOGIN (admin)")
    print("=" * 65)
    resp = requests.post(f"{BASE}/auth/login", json={
        "email": "admin@test.com",
        "password": "Admin123!",
    })
    if resp.status_code != 200:
        print(f"  [FATAL] Login failed ({resp.status_code}): {resp.text}")
        sys.exit(1)
    token = resp.json()["access_token"]
    headers = {"Authorization": f"Bearer {token}"}
    print(f"  [OK] Logged in, token={token[:30]}...")

    # ════════════════════════════════════════════════════════
    #  2. LIST DOCUMENTS (should have 3 test PDFs)
    # ════════════════════════════════════════════════════════
    print("\n" + "=" * 65)
    print("2. LIST PDF DOCUMENTS")
    print("=" * 65)
    resp = requests.get(f"{BASE}/rag/documents", headers=headers)
    check("List documents (200)", resp.status_code == 200, f"Got {resp.status_code}: {resp.text[:200]}")
    docs = resp.json()
    check("total_files >= 3", docs["total_files"] >= 3, f"total={docs['total_files']}")
    print(f"  Directory: {docs['directory']}")
    for f in docs["files"]:
        print(f"    - {f['filename']} ({f['size_human']})")

    # ════════════════════════════════════════════════════════
    #  3. UPLOAD A PDF
    # ════════════════════════════════════════════════════════
    print("\n" + "=" * 65)
    print("3. UPLOAD PDF DOCUMENT")
    print("=" * 65)

    # Create a small PDF in memory for upload testing
    try:
        import fitz
        doc = fitz.open()
        doc.set_metadata({"title": "Upload Test Document", "author": "Test Script"})
        page = doc.new_page()
        rect = fitz.Rect(50, 50, 562, 742)
        page.insert_textbox(rect, (
            "Upload Test Document\n\n"
            "This document was uploaded via the API to test the upload endpoint. "
            "It contains information about troubleshooting email delivery issues.\n\n"
            "Common Email Issues:\n\n"
            "1. Emails going to spam folder: Check your SPF, DKIM, and DMARC records.\n"
            "2. Emails not being received: Verify the recipient address and check bounce logs.\n"
            "3. Delayed email delivery: Check your email queue and server load.\n"
            "4. Attachment issues: Ensure attachments are under 25 MB and not blocked file types."
        ), fontsize=11, fontname="helv")
        upload_pdf_path = "test_upload_email_troubleshooting.pdf"
        doc.save(upload_pdf_path)
        doc.close()
        has_upload_pdf = True
    except Exception as e:
        print(f"  [SKIP] Cannot create upload PDF locally: {e}")
        has_upload_pdf = False

    if has_upload_pdf:
        with open(upload_pdf_path, "rb") as f:
            resp = requests.post(
                f"{BASE}/rag/documents/upload",
                headers=headers,
                files={"file": ("email-troubleshooting.pdf", f, "application/pdf")},
            )
        check("Upload PDF (201)", resp.status_code == 201, f"Got {resp.status_code}: {resp.text[:200]}")
        if resp.status_code == 201:
            upload_data = resp.json()
            check("Filename correct", upload_data["filename"] == "email-troubleshooting.pdf")
            check("Size > 0", upload_data["size_bytes"] > 0)
            print(f"  Uploaded: {upload_data['filename']} ({upload_data['size_bytes']:,} bytes)")

        # Clean up local file
        os.remove(upload_pdf_path)

        # Verify it shows in the list now
        resp = requests.get(f"{BASE}/rag/documents", headers=headers)
        new_docs = resp.json()
        uploaded_names = [f["filename"] for f in new_docs["files"]]
        check("Uploaded file in list", "email-troubleshooting.pdf" in uploaded_names)

        # Test rejecting non-PDF upload
        resp = requests.post(
            f"{BASE}/rag/documents/upload",
            headers=headers,
            files={"file": ("readme.txt", b"not a pdf", "text/plain")},
        )
        check("Non-PDF rejected (400)", resp.status_code == 400)

    # ════════════════════════════════════════════════════════
    #  4. INGEST A SINGLE PDF
    # ════════════════════════════════════════════════════════
    print("\n" + "=" * 65)
    print("4. INGEST SINGLE PDF — return-policy.pdf")
    print("=" * 65)
    resp = requests.post(f"{BASE}/rag/documents/ingest", headers=headers, json={
        "filename": "return-policy.pdf",
        "category": "POLICY",
        "language": "en",
        "tags": ["return", "refund", "policy"],
        "auto_publish": True,
        "auto_index": True,
    })
    check("Ingest PDF (200)", resp.status_code == 200, f"Got {resp.status_code}: {resp.text[:300]}")
    if resp.status_code == 200:
        ingest = resp.json()
        check("Article created", ingest["article_id"] is not None)
        check("Has title", len(ingest["title"]) > 0)
        check("Page count > 0", ingest["page_count"] > 0)
        check("Words extracted", ingest["total_words"] > 0)
        check("Chunks created", ingest["chunks_created"] > 0)
        check("Is published", ingest["is_published"] is True)
        policy_article_id = ingest["article_id"]
        print(f"  Title: {ingest['title']}")
        print(f"  Pages: {ingest['page_count']}, Words: {ingest['total_words']}")
        print(f"  Chunks: {ingest['chunks_created']}, Tokens: {ingest['total_tokens']}")
        print(f"  Status: {ingest['status']}")
    else:
        policy_article_id = None

    # Test ingesting non-existent PDF
    resp = requests.post(f"{BASE}/rag/documents/ingest", headers=headers, json={
        "filename": "nonexistent.pdf",
        "category": "GENERAL",
    })
    check("Non-existent PDF returns 404", resp.status_code == 404)

    # ════════════════════════════════════════════════════════
    #  5. BULK INGEST ALL PDFs
    # ════════════════════════════════════════════════════════
    print("\n" + "=" * 65)
    print("5. BULK INGEST ALL PDFs")
    print("=" * 65)
    resp = requests.post(f"{BASE}/rag/documents/ingest-all", headers=headers, json={
        "category": "GENERAL",
        "language": "en",
        "auto_publish": True,
        "auto_index": True,
        "skip_existing": True,
    })
    check("Bulk ingest (200)", resp.status_code == 200, f"Got {resp.status_code}: {resp.text[:300]}")
    if resp.status_code == 200:
        bulk = resp.json()
        check("total_files > 0", bulk["total_files"] > 0)
        check("ingested > 0", bulk["ingested"] > 0)
        check("skipped (return-policy)", bulk["skipped"] >= 1,
              f"skipped={bulk['skipped']}")
        check("No failures", bulk["failed"] == 0, f"failed={bulk['failed']}")
        print(f"  Total files: {bulk['total_files']}")
        print(f"  Ingested: {bulk['ingested']}, Skipped: {bulk['skipped']}, Failed: {bulk['failed']}")
        for r in bulk["results"]:
            print(f"    - {r['filename']}: {r['title']} ({r['chunks_created']} chunks)")

    # ════════════════════════════════════════════════════════
    #  6. SEARCH INGESTED PDF CONTENT
    # ════════════════════════════════════════════════════════
    print("\n" + "=" * 65)
    print("6. SEARCH INGESTED PDF CONTENT")
    print("=" * 65)

    # Search for return policy content
    print("  >> Query: 'how to return a product and get refund'")
    resp = requests.post(f"{BASE}/rag/search", headers=headers, json={
        "query": "how to return a product and get refund",
        "top_k": 5,
        "min_similarity": 0.2,
        "include_content": True,
    })
    check("Search (200)", resp.status_code == 200, f"Got {resp.status_code}")
    if resp.status_code == 200:
        search = resp.json()
        check("Got hits", search["total_hits"] > 0, f"total_hits={search['total_hits']}")
        if search["total_hits"] > 0:
            top = search["hits"][0]
            print(f"  Top hit: '{top['article_title']}' (sim={top['similarity']})")
            print(f"  Content preview: {top['chunk_content'][:120]}...")
            check("Top hit about returns/refund/policy",
                  any(kw in top["article_title"].lower() for kw in ["return", "refund", "policy"]),
                  f"Got: {top['article_title']}")

    # Search for security content from PDF
    print("\n  >> Query: 'two factor authentication setup'")
    resp = requests.post(f"{BASE}/rag/search", headers=headers, json={
        "query": "two factor authentication setup",
        "top_k": 5,
        "min_similarity": 0.2,
    })
    check("Security search (200)", resp.status_code == 200)
    if resp.status_code == 200:
        search = resp.json()
        check("Got security hits", search["total_hits"] > 0)
        if search["total_hits"] > 0:
            top = search["hits"][0]
            print(f"  Top hit: '{top['article_title']}' (sim={top['similarity']})")

    # Search for onboarding content
    print("\n  >> Query: 'how to invite team members to workspace'")
    resp = requests.post(f"{BASE}/rag/search", headers=headers, json={
        "query": "how to invite team members to workspace",
        "top_k": 5,
        "min_similarity": 0.2,
    })
    check("Onboarding search (200)", resp.status_code == 200)
    if resp.status_code == 200:
        search = resp.json()
        check("Got onboarding hits", search["total_hits"] > 0)
        if search["total_hits"] > 0:
            top = search["hits"][0]
            print(f"  Top hit: '{top['article_title']}' (sim={top['similarity']})")

    # ════════════════════════════════════════════════════════
    #  7. VERIFY ARTICLE DETAIL (from PDF)
    # ════════════════════════════════════════════════════════
    print("\n" + "=" * 65)
    print("7. VERIFY PDF ARTICLE DETAIL")
    print("=" * 65)
    if policy_article_id:
        resp = requests.get(f"{BASE}/rag/articles/{policy_article_id}", headers=headers)
        check("Get PDF article (200)", resp.status_code == 200)
        if resp.status_code == 200:
            art = resp.json()
            check("Source is pdf:return-policy.pdf", art["source"] == "pdf:return-policy.pdf")
            check("Category is POLICY", art["category"] == "POLICY")
            check("Has PDF tag", "pdf" in art["tags"])
            check("Has metadata_extra with pdf_pages",
                  art.get("metadata_extra", {}).get("pdf_pages", 0) > 0)
            check("Is indexed", art["is_indexed"] is True)
            check("Status is PUBLISHED", art["status"] == "PUBLISHED")
            print(f"  Title: {art['title']}")
            print(f"  Source: {art['source']}")
            print(f"  PDF metadata: pages={art['metadata_extra'].get('pdf_pages')}, "
                  f"words={art['metadata_extra'].get('pdf_words')}")

    # ════════════════════════════════════════════════════════
    #  8. KNOWLEDGE BASE STATS
    # ════════════════════════════════════════════════════════
    print("\n" + "=" * 65)
    print("8. KNOWLEDGE BASE STATS (after PDF ingestion)")
    print("=" * 65)
    resp = requests.get(f"{BASE}/rag/stats", headers=headers)
    check("Stats (200)", resp.status_code == 200)
    if resp.status_code == 200:
        stats = resp.json()
        print(f"  Articles: {stats['total_articles']} total, {stats['published_articles']} published")
        print(f"  Chunks: {stats['total_chunks']} ({stats['indexed_chunks']} indexed)")
        print(f"  Tokens: {stats['total_tokens']}")
        print(f"  Categories: {stats['categories']}")

    # ════════════════════════════════════════════════════════
    #  SUMMARY
    # ════════════════════════════════════════════════════════
    print("\n" + "=" * 65)
    total = passed + failed
    print(f"RESULTS: {passed}/{total} passed, {failed} failed")
    print("=" * 65)

    if failed:
        print("\n  ❌ Some tests failed!")
        sys.exit(1)
    else:
        print("\n  ✅ All tests passed!")
        sys.exit(0)


if __name__ == "__main__":
    main()
