"""
End-to-end test script for the RAG Knowledge Base API.

Tests all 13 endpoints:
 1. Create article (with auto_index)
 2. Create article (without auto_index)
 3. List articles
 4. Get article detail
 5. Update article
 6. Publish article
 7. Index article (chunk + embed)
 8. Semantic search
 9. Hybrid search
10. Archive article
11. Delete article
12. Reindex all (sync)
13. Stats

Usage:
    python test_rag.py
"""

import requests
import json
import sys
import time

BASE = "http://localhost:8000/api/v1"

passed = 0
failed = 0


def pp(data):
    """Pretty print JSON."""
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
    #  2. CREATE ARTICLE (auto_index=false)
    # ════════════════════════════════════════════════════════
    print("\n" + "=" * 65)
    print("2. CREATE ARTICLE — Password Reset Guide (no auto-index)")
    print("=" * 65)
    resp = requests.post(f"{BASE}/rag/articles", headers=headers, json={
        "title": "How to Reset Your Password",
        "content": (
            "If you forgot your password, follow these steps to reset it:\n\n"
            "Step 1: Go to the login page and click 'Forgot Password'.\n"
            "Step 2: Enter your registered email address.\n"
            "Step 3: Check your inbox for the reset link. It may take a few minutes.\n"
            "Step 4: Click the link and create a new password. "
            "Make sure it is at least 8 characters with uppercase, lowercase, "
            "and a number.\n"
            "Step 5: Log in with your new password.\n\n"
            "If you still cannot log in, contact support at support@example.com. "
            "A support agent will verify your identity and manually reset your account."
        ),
        "summary": "Step-by-step guide for resetting forgotten passwords.",
        "category": "ACCOUNT",
        "tags": ["password", "reset", "login", "account"],
        "language": "en",
        "auto_index": False,
    })
    check("Create article (201)", resp.status_code == 201, f"Got {resp.status_code}: {resp.text[:200]}")
    art1 = resp.json()
    art1_id = art1["id"]
    check("Status is DRAFT", art1["status"] == "DRAFT")
    check("Not indexed yet", art1["is_indexed"] is False)
    check("Category is ACCOUNT", art1["category"] == "ACCOUNT")
    print(f"  Article ID: {art1_id}")

    # ════════════════════════════════════════════════════════
    #  3. CREATE ARTICLE — auto-indexed
    # ════════════════════════════════════════════════════════
    print("\n" + "=" * 65)
    print("3. CREATE ARTICLE — Billing FAQ (auto_index=true)")
    print("=" * 65)
    resp = requests.post(f"{BASE}/rag/articles", headers=headers, json={
        "title": "Billing and Payment FAQ",
        "content": (
            "Frequently asked questions about billing and payments:\n\n"
            "Q: How do I update my payment method?\n"
            "A: Go to Settings > Billing > Payment Methods and add a new card.\n\n"
            "Q: When will I be charged?\n"
            "A: Subscriptions are billed on the first of each month. "
            "You will receive an invoice via email 3 days before the charge.\n\n"
            "Q: How do I get a refund?\n"
            "A: Refund requests must be submitted within 30 days of the charge. "
            "Go to Support > Billing Issues and select 'Request Refund'. "
            "Refunds are processed within 5-10 business days.\n\n"
            "Q: Can I cancel my subscription?\n"
            "A: Yes, go to Settings > Billing > Cancel Subscription. "
            "Your access will continue until the end of the current billing period.\n\n"
            "Q: Why was I charged twice?\n"
            "A: Duplicate charges can occur due to payment processing delays. "
            "If you see a duplicate charge, please contact billing support. "
            "We will investigate and issue a refund within 48 hours."
        ),
        "summary": "Common billing and payment questions and answers.",
        "category": "BILLING",
        "tags": ["billing", "payment", "refund", "subscription"],
        "language": "en",
        "auto_index": True,
    })
    check("Create article (201)", resp.status_code == 201, f"Got {resp.status_code}: {resp.text[:200]}")
    art2 = resp.json()
    art2_id = art2["id"]
    check("Status is DRAFT", art2["status"] == "DRAFT")
    # auto_index should have indexed it
    check("Auto-indexed (is_indexed=true)", art2["is_indexed"] is True)
    check("Has chunks", art2["chunk_count"] > 0)
    print(f"  Article ID: {art2_id}")
    print(f"  Chunks: {art2['chunk_count']}, Tokens: {art2['total_tokens']}")

    # ════════════════════════════════════════════════════════
    #  4. CREATE ARTICLE — Technical troubleshooting
    # ════════════════════════════════════════════════════════
    print("\n" + "=" * 65)
    print("4. CREATE ARTICLE — Network Troubleshooting (auto_index=true)")
    print("=" * 65)
    resp = requests.post(f"{BASE}/rag/articles", headers=headers, json={
        "title": "Network Connection Troubleshooting Guide",
        "content": (
            "If you are experiencing network issues, try these troubleshooting steps:\n\n"
            "1. Restart your router and modem. Unplug the power, wait 30 seconds, "
            "and plug it back in. Wait 2 minutes for the connection to stabilize.\n\n"
            "2. Check your WiFi signal strength. Move closer to the router or remove "
            "physical obstructions between your device and the router.\n\n"
            "3. Run a speed test at speedtest.net. If speeds are significantly below "
            "your plan, contact your ISP.\n\n"
            "4. Clear your DNS cache:\n"
            "   - Windows: Open Command Prompt and run 'ipconfig /flushdns'\n"
            "   - macOS: Open Terminal and run 'sudo dscacheutil -flushcache'\n\n"
            "5. Try using a wired ethernet connection instead of WiFi to rule out "
            "wireless interference.\n\n"
            "6. Check if the issue affects all devices. If only one device is affected, "
            "the problem is likely with that device's network adapter.\n\n"
            "7. Update your network adapter drivers to the latest version.\n\n"
            "If none of these steps resolve the issue, please contact technical support "
            "with your router model, ISP name, and a screenshot of your speed test results."
        ),
        "summary": "Step-by-step guide for diagnosing and fixing network connectivity problems.",
        "category": "TROUBLESHOOTING",
        "tags": ["network", "wifi", "internet", "troubleshooting", "connectivity"],
        "language": "en",
        "auto_index": True,
    })
    check("Create article (201)", resp.status_code == 201, f"Got {resp.status_code}: {resp.text[:200]}")
    art3 = resp.json()
    art3_id = art3["id"]
    check("Auto-indexed", art3["is_indexed"] is True)
    print(f"  Article ID: {art3_id}")
    print(f"  Chunks: {art3['chunk_count']}, Tokens: {art3['total_tokens']}")

    # ════════════════════════════════════════════════════════
    #  5. LIST ARTICLES
    # ════════════════════════════════════════════════════════
    print("\n" + "=" * 65)
    print("5. LIST ARTICLES")
    print("=" * 65)
    resp = requests.get(f"{BASE}/rag/articles", headers=headers)
    check("List articles (200)", resp.status_code == 200, f"Got {resp.status_code}")
    data = resp.json()
    check("Total >= 3", data["total"] >= 3, f"total={data['total']}")
    print(f"  Total: {data['total']}, Returned: {len(data['items'])}")

    # Filter by category
    resp = requests.get(f"{BASE}/rag/articles?category=BILLING", headers=headers)
    check("Filter by BILLING (200)", resp.status_code == 200)
    billing_data = resp.json()
    check("Filtered results have BILLING category",
          all(i["category"] == "BILLING" for i in billing_data["items"]),
          f"Categories: {[i['category'] for i in billing_data['items']]}")

    # Search by title
    resp = requests.get(f"{BASE}/rag/articles?search=Network", headers=headers)
    check("Search by title 'Network' (200)", resp.status_code == 200)
    search_data = resp.json()
    check("Found network article",
          any("Network" in i["title"] for i in search_data["items"]))

    # ════════════════════════════════════════════════════════
    #  6. GET ARTICLE DETAIL
    # ════════════════════════════════════════════════════════
    print("\n" + "=" * 65)
    print("6. GET ARTICLE DETAIL")
    print("=" * 65)
    resp = requests.get(f"{BASE}/rag/articles/{art2_id}", headers=headers)
    check("Get article (200)", resp.status_code == 200, f"Got {resp.status_code}")
    detail = resp.json()
    check("Title matches", detail["title"] == "Billing and Payment FAQ")
    check("Has content", len(detail["content"]) > 100)
    print(f"  Title: {detail['title']}")
    print(f"  Indexed: {detail['is_indexed']}, Chunks: {detail['chunk_count']}")

    # 404 for non-existent article
    resp = requests.get(f"{BASE}/rag/articles/00000000-0000-0000-0000-000000000000", headers=headers)
    check("Non-existent article returns 404", resp.status_code == 404)

    # ════════════════════════════════════════════════════════
    #  7. INDEX ARTICLE (manual)
    # ════════════════════════════════════════════════════════
    print("\n" + "=" * 65)
    print("7. INDEX ARTICLE — Manual index for article 1")
    print("=" * 65)
    resp = requests.post(
        f"{BASE}/rag/articles/{art1_id}/index?chunk_size=256&chunk_overlap=32",
        headers=headers,
    )
    check("Index article (200)", resp.status_code == 200, f"Got {resp.status_code}: {resp.text[:200]}")
    idx_data = resp.json()
    check("Chunks created > 0", idx_data["chunks_created"] > 0)
    check("Status is 'indexed'", idx_data["status"] == "indexed")
    print(f"  Chunks: {idx_data['chunks_created']}, Tokens: {idx_data['total_tokens']}")

    # Verify article is now indexed
    resp = requests.get(f"{BASE}/rag/articles/{art1_id}", headers=headers)
    check("Article now shows is_indexed=true", resp.json()["is_indexed"] is True)

    # ════════════════════════════════════════════════════════
    #  8. PUBLISH ARTICLES (required for search)
    # ════════════════════════════════════════════════════════
    print("\n" + "=" * 65)
    print("8. PUBLISH ARTICLES")
    print("=" * 65)
    for aid, title in [(art1_id, "Password Reset"), (art2_id, "Billing FAQ"), (art3_id, "Network Guide")]:
        resp = requests.post(f"{BASE}/rag/articles/{aid}/publish", headers=headers)
        check(f"Publish '{title}' (200)", resp.status_code == 200, f"Got {resp.status_code}: {resp.text[:100]}")
        check(f"  Status is PUBLISHED", resp.json()["status"] == "PUBLISHED")

    # ════════════════════════════════════════════════════════
    #  9. SEMANTIC SEARCH
    # ════════════════════════════════════════════════════════
    print("\n" + "=" * 65)
    print("9. SEMANTIC SEARCH")
    print("=" * 65)

    # Search for password reset
    print("  >> Query: 'I forgot my password and cannot log in'")
    resp = requests.post(f"{BASE}/rag/search", headers=headers, json={
        "query": "I forgot my password and cannot log in",
        "top_k": 5,
        "min_similarity": 0.2,
        "include_content": True,
    })
    check("Semantic search (200)", resp.status_code == 200, f"Got {resp.status_code}: {resp.text[:200]}")
    search = resp.json()
    check("Got hits", search["total_hits"] > 0, f"total_hits={search['total_hits']}")
    check("Model used is populated", len(search.get("model_used", "")) > 0)
    if search["total_hits"] > 0:
        top_hit = search["hits"][0]
        print(f"  Top hit: '{top_hit['article_title']}' (sim={top_hit['similarity']})")
        print(f"  Chunk content: {top_hit['chunk_content'][:120]}...")
        check("Top hit relates to password/account",
              "password" in top_hit["article_title"].lower() or "account" in top_hit["article_category"].lower(),
              f"Got: {top_hit['article_title']}")

    # Search for billing / refund
    print("\n  >> Query: 'how to get a refund for double charge'")
    resp = requests.post(f"{BASE}/rag/search", headers=headers, json={
        "query": "how to get a refund for double charge",
        "top_k": 5,
        "min_similarity": 0.2,
    })
    check("Billing search (200)", resp.status_code == 200)
    billing_search = resp.json()
    check("Got hits", billing_search["total_hits"] > 0)
    if billing_search["total_hits"] > 0:
        top = billing_search["hits"][0]
        print(f"  Top hit: '{top['article_title']}' (sim={top['similarity']})")

    # Search with category filter
    print("\n  >> Query: 'internet issues' (filter: TROUBLESHOOTING)")
    resp = requests.post(f"{BASE}/rag/search", headers=headers, json={
        "query": "internet connection problems slow speed",
        "top_k": 3,
        "category": "TROUBLESHOOTING",
        "min_similarity": 0.2,
    })
    check("Category-filtered search (200)", resp.status_code == 200)
    cat_search = resp.json()
    if cat_search["total_hits"] > 0:
        check("All hits are TROUBLESHOOTING",
              all(h["article_category"] == "TROUBLESHOOTING" for h in cat_search["hits"]))
        print(f"  Hits: {cat_search['total_hits']}")

    # ════════════════════════════════════════════════════════
    # 10. HYBRID SEARCH
    # ════════════════════════════════════════════════════════
    print("\n" + "=" * 65)
    print("10. HYBRID SEARCH (keyword + semantic)")
    print("=" * 65)
    resp = requests.post(f"{BASE}/rag/search/hybrid", headers=headers, json={
        "query": "reset password email link",
        "top_k": 5,
        "keyword_weight": 0.4,
        "min_similarity": 0.2,
    })
    check("Hybrid search (200)", resp.status_code == 200, f"Got {resp.status_code}: {resp.text[:200]}")
    hybrid = resp.json()
    check("Got hybrid hits", hybrid["total_hits"] > 0)
    if hybrid["total_hits"] > 0:
        print(f"  Top hit: '{hybrid['hits'][0]['article_title']}' (score={hybrid['hits'][0]['similarity']})")

    # ════════════════════════════════════════════════════════
    # 11. UPDATE ARTICLE
    # ════════════════════════════════════════════════════════
    print("\n" + "=" * 65)
    print("11. UPDATE ARTICLE")
    print("=" * 65)
    resp = requests.patch(f"{BASE}/rag/articles/{art1_id}", headers=headers, json={
        "title": "How to Reset Your Password (Updated)",
        "tags": ["password", "reset", "login", "account", "security"],
        "re_index": False,
    })
    check("Update article (200)", resp.status_code == 200, f"Got {resp.status_code}: {resp.text[:200]}")
    updated = resp.json()
    check("Title updated", updated["title"] == "How to Reset Your Password (Updated)")
    check("Tags updated", "security" in updated["tags"])

    # ════════════════════════════════════════════════════════
    # 12. ARCHIVE ARTICLE
    # ════════════════════════════════════════════════════════
    print("\n" + "=" * 65)
    print("12. ARCHIVE ARTICLE")
    print("=" * 65)
    resp = requests.post(f"{BASE}/rag/articles/{art1_id}/archive", headers=headers)
    check("Archive article (200)", resp.status_code == 200, f"Got {resp.status_code}")
    archived = resp.json()
    check("Status is ARCHIVED", archived["status"] == "ARCHIVED")

    # Verify archived article doesn't appear in search
    resp = requests.post(f"{BASE}/rag/search", headers=headers, json={
        "query": "how to reset password",
        "top_k": 10,
        "min_similarity": 0.1,
    })
    search_after_archive = resp.json()
    archived_in_results = any(
        h["article_id"] == art1_id for h in search_after_archive["hits"]
    )
    check("Archived article excluded from search", not archived_in_results)

    # ════════════════════════════════════════════════════════
    # 13. REINDEX ALL (sync)
    # ════════════════════════════════════════════════════════
    print("\n" + "=" * 65)
    print("13. REINDEX ALL (sync mode)")
    print("=" * 65)
    resp = requests.post(
        f"{BASE}/rag/reindex-all?use_celery=false",
        headers=headers,
    )
    check("Reindex all (200)", resp.status_code == 200, f"Got {resp.status_code}: {resp.text[:200]}")
    reindex = resp.json()
    check("Status is 'completed'", reindex["status"] == "completed")
    check("Total articles > 0", reindex["total_articles"] > 0)
    print(f"  Reindexed {reindex['total_articles']} article(s)")

    # ════════════════════════════════════════════════════════
    # 14. DELETE ARTICLE (soft-delete)
    # ════════════════════════════════════════════════════════
    print("\n" + "=" * 65)
    print("14. DELETE ARTICLE (soft-delete)")
    print("=" * 65)
    resp = requests.delete(f"{BASE}/rag/articles/{art1_id}", headers=headers)
    check("Delete article (204)", resp.status_code == 204, f"Got {resp.status_code}")

    # Verify it's gone from GET
    resp = requests.get(f"{BASE}/rag/articles/{art1_id}", headers=headers)
    check("Deleted article returns 404", resp.status_code == 404)

    # ════════════════════════════════════════════════════════
    # 15. KNOWLEDGE BASE STATS
    # ════════════════════════════════════════════════════════
    print("\n" + "=" * 65)
    print("15. KNOWLEDGE BASE STATS")
    print("=" * 65)
    resp = requests.get(f"{BASE}/rag/stats", headers=headers)
    check("Stats (200)", resp.status_code == 200, f"Got {resp.status_code}")
    stats = resp.json()
    check("total_articles >= 2", stats["total_articles"] >= 2)
    check("published_articles >= 1", stats["published_articles"] >= 1)
    check("total_chunks > 0", stats["total_chunks"] > 0)
    check("indexed_chunks > 0", stats["indexed_chunks"] > 0)
    check("total_tokens > 0", stats["total_tokens"] > 0)
    check("categories dict populated", len(stats["categories"]) > 0)
    check("languages dict populated", len(stats["languages"]) > 0)
    print(f"  Articles: {stats['total_articles']} total, {stats['published_articles']} published")
    print(f"  Chunks: {stats['total_chunks']} ({stats['indexed_chunks']} indexed)")
    print(f"  Tokens: {stats['total_tokens']}")
    print(f"  Categories: {stats['categories']}")
    print(f"  Languages: {stats['languages']}")
    print(f"  Avg chunks/article: {stats['avg_chunks_per_article']}")

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
