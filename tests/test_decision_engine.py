"""
End-to-end test script for the Decision Engine API.
"""

import requests
import json
import sys

BASE = "http://localhost:8000/api/v1"

def pp(data):
    """Pretty print JSON."""
    print(json.dumps(data, indent=2, default=str))

def main():
    # ── 1. Login ──
    print("=" * 60)
    print("1. LOGIN")
    print("=" * 60)
    resp = requests.post(f"{BASE}/auth/login", json={
        "email": "admin@test.com",
        "password": "Admin123!",
    })
    assert resp.status_code == 200, f"Login failed: {resp.text}"
    token = resp.json()["access_token"]
    headers = {"Authorization": f"Bearer {token}"}
    print(f"  [OK] Logged in, token={token[:30]}...")

    # ── 2. Analyze text - TECHNICAL ──
    print("\n" + "=" * 60)
    print("2. ANALYZE TEXT — Technical Issue")
    print("=" * 60)
    resp = requests.post(f"{BASE}/decision-engine/analyze-text", headers=headers, json={
        "text": "My internet connection keeps dropping every few minutes. I've tried restarting the router.",
        "subject": "Internet issues",
    })
    assert resp.status_code == 200, f"Analyze text failed: {resp.text}"
    data = resp.json()
    print(f"  Intent: {data['intent_category']}")
    print(f"  Confidence: {data['confidence_score']} ({data['confidence_level']})")
    print(f"  Risk: {data['risk_score']} ({data['risk_level']})")
    print(f"  Decision: {data['decision_outcome']}")
    print(f"  Rules: {data['matched_rules']}")
    print(f"  Suggestions: {len(data['response_suggestions'])} provided")
    assert data["intent_category"] == "TECHNICAL", "Expected TECHNICAL intent"
    print("  [OK] PASSED")

    # ── 3. Analyze text - SECURITY (should escalate) ──
    print("\n" + "=" * 60)
    print("3. ANALYZE TEXT — Security Breach (should escalate)")
    print("=" * 60)
    resp = requests.post(f"{BASE}/decision-engine/analyze-text", headers=headers, json={
        "text": "Someone hacked my account, changed my password. Unauthorized transactions detected. Data breach!",
        "subject": "URGENT: Account hacked",
    })
    assert resp.status_code == 200, f"Failed: {resp.text}"
    data = resp.json()
    print(f"  Intent: {data['intent_category']}")
    print(f"  Confidence: {data['confidence_score']} ({data['confidence_level']})")
    print(f"  Risk: {data['risk_score']} ({data['risk_level']})")
    print(f"  Decision: {data['decision_outcome']}")
    assert data["intent_category"] == "SECURITY", "Expected SECURITY intent"
    assert data["decision_outcome"] == "ESCALATE_HUMAN", "Expected ESCALATE_HUMAN"
    print("  [OK] PASSED — Security correctly escalated!")

    # ── 4. Analyze text - BILLING ──
    print("\n" + "=" * 60)
    print("4. ANALYZE TEXT — Billing Issue")
    print("=" * 60)
    resp = requests.post(f"{BASE}/decision-engine/analyze-text", headers=headers, json={
        "text": "I was charged twice for my subscription. I need a refund for the duplicate payment.",
        "subject": "Double billing",
    })
    assert resp.status_code == 200, f"Failed: {resp.text}"
    data = resp.json()
    print(f"  Intent: {data['intent_category']}")
    print(f"  Decision: {data['decision_outcome']}")
    assert data["intent_category"] == "BILLING", "Expected BILLING intent"
    print("  [OK] PASSED")

    # ── 5. Analyze text - COMPLAINT ──
    print("\n" + "=" * 60)
    print("5. ANALYZE TEXT — Complaint")
    print("=" * 60)
    resp = requests.post(f"{BASE}/decision-engine/analyze-text", headers=headers, json={
        "text": "This is the worst service I have ever experienced. I am extremely disappointed and frustrated. I want to cancel everything.",
        "subject": "Terrible experience",
    })
    assert resp.status_code == 200, f"Failed: {resp.text}"
    data = resp.json()
    print(f"  Intent: {data['intent_category']}")
    print(f"  Risk: {data['risk_score']} ({data['risk_level']})")
    print(f"  Decision: {data['decision_outcome']}")
    assert data["intent_category"] == "COMPLAINT", "Expected COMPLAINT"
    print("  [OK] PASSED")

    # ── 6. Create a ticket for full analysis ──
    print("\n" + "=" * 60)
    print("6. CREATE TICKET for full analysis")
    print("=" * 60)
    resp = requests.post(f"{BASE}/tickets", headers=headers, json={
        "subject": "Cannot login to dashboard",
        "description": "I keep getting an error when trying to login. Reset password twice but still locked out. My entire team is blocked!",
        "channel": "EMAIL",
        "priority": "HIGH",
    })
    assert resp.status_code == 201, f"Create ticket failed: {resp.text}"
    ticket = resp.json()
    ticket_id = ticket["id"]
    print(f"  Ticket ID: {ticket_id}")
    print(f"  Status: {ticket['status']}, Priority: {ticket['priority']}")
    print("  [OK] Ticket created")

    # ── 7. Full ticket analysis ──
    print("\n" + "=" * 60)
    print("7. FULL TICKET ANALYSIS (analyze endpoint)")
    print("=" * 60)
    resp = requests.post(f"{BASE}/decision-engine/analyze", headers=headers, json={
        "ticket_id": ticket_id,
        "auto_assign": False,
        "auto_update_priority": False,
    })
    assert resp.status_code == 200, f"Analyze ticket failed: {resp.text}"
    data = resp.json()
    print(f"  Intent: {data['intent_category']}")
    print(f"  Confidence: {data['confidence_score']} ({data['confidence_level']})")
    print(f"  Risk: {data['risk_score']} ({data['risk_level']})")
    print(f"  Decision: {data['decision_outcome']}")
    print(f"  Reasoning: {data['reasoning'][:120]}...")
    print(f"  Suggestions: {len(data['response_suggestions'])} provided")
    print(f"  Priority suggestion: {data['suggested_priority']}")
    print("  [OK] PASSED — Full pipeline works!")

    # ── 8. Decision history ──
    print("\n" + "=" * 60)
    print("8. DECISION HISTORY")
    print("=" * 60)
    resp = requests.get(f"{BASE}/decision-engine/decisions/{ticket_id}", headers=headers)
    assert resp.status_code == 200, f"Decision history failed: {resp.text}"
    data = resp.json()
    print(f"  Total decisions: {data['total']}")
    print(f"  Latest decision: {data['decisions'][0]['decision_outcome']}")
    assert data["total"] >= 1, "Expected at least 1 decision"
    print("  [OK] PASSED")

    # ── 9. Response suggestions ──
    print("\n" + "=" * 60)
    print("9. RESPONSE SUGGESTIONS")
    print("=" * 60)
    resp = requests.get(f"{BASE}/decision-engine/suggestions/{ticket_id}", headers=headers)
    assert resp.status_code == 200, f"Suggestions failed: {resp.text}"
    data = resp.json()
    print(f"  Category: {data['intent_category']}")
    print(f"  Confidence: {data['confidence']}")
    print(f"  Suggestions ({len(data['suggestions'])}):")
    for i, s in enumerate(data["suggestions"], 1):
        print(f"    {i}. {s[:80]}...")
    print("  [OK] PASSED")

    # ── 10. Agent Skills CRUD ──
    print("\n" + "=" * 60)
    print("10. AGENT SKILLS — Create")
    print("=" * 60)
    admin_id = "593f04c5-eaf7-41ab-83e3-c5446e5c914d"  # our admin user
    resp = requests.post(f"{BASE}/decision-engine/agent-skills", headers=headers, json={
        "agent_id": admin_id,
        "skill_category": "TECHNICAL",
        "proficiency": 0.85,
        "max_concurrent_tickets": 15,
    })
    assert resp.status_code == 201, f"Create skill failed: {resp.text}"
    skill = resp.json()
    skill_id = skill["id"]
    print(f"  Skill ID: {skill_id}")
    print(f"  Agent: {skill['agent_id']}, Category: {skill['skill_category']}")
    print(f"  Proficiency: {skill['proficiency']}, Max tickets: {skill['max_concurrent_tickets']}")
    print("  [OK] Skill created")

    # Create a second skill
    resp = requests.post(f"{BASE}/decision-engine/agent-skills", headers=headers, json={
        "agent_id": admin_id,
        "skill_category": "BILLING",
        "proficiency": 0.7,
        "max_concurrent_tickets": 10,
    })
    assert resp.status_code == 201, f"Create skill 2 failed: {resp.text}"
    print("  [OK] Second skill (BILLING) created")

    # ── 11. List agent skills ──
    print("\n" + "=" * 60)
    print("11. AGENT SKILLS — List")
    print("=" * 60)
    resp = requests.get(f"{BASE}/decision-engine/agent-skills", headers=headers)
    assert resp.status_code == 200, f"List skills failed: {resp.text}"
    data = resp.json()
    print(f"  Total skills: {data['total']}")
    for s in data["skills"]:
        print(f"    - {s['skill_category']}: proficiency={s['proficiency']}")
    print("  [OK] PASSED")

    # Filter by category
    resp = requests.get(f"{BASE}/decision-engine/agent-skills?category=TECHNICAL", headers=headers)
    assert resp.status_code == 200
    data = resp.json()
    print(f"  Filtered (TECHNICAL): {data['total']} skills")
    print("  [OK] Filter works")

    # ── 12. Route ticket ──
    print("\n" + "=" * 60)
    print("12. ROUTE TICKET (Smart Routing)")
    print("=" * 60)
    resp = requests.post(f"{BASE}/decision-engine/route/{ticket_id}", headers=headers)
    assert resp.status_code == 200, f"Route failed: {resp.text}"
    data = resp.json()
    print(f"  Selected agent: {data.get('selected_agent')}")
    print(f"  Candidates: {len(data.get('candidates', []))}")
    print(f"  Auto-assigned: {data['auto_assigned']}")
    print("  [OK] PASSED")

    # ── 13. Escalation ──
    print("\n" + "=" * 60)
    print("13. ESCALATION PACKAGE")
    print("=" * 60)
    resp = requests.post(f"{BASE}/decision-engine/escalate/{ticket_id}", headers=headers)
    assert resp.status_code == 200, f"Escalate failed: {resp.text}"
    data = resp.json()
    print(f"  Ticket: {data['ticket_subject']}")
    print(f"  Category: {data['intent_category']}")
    print(f"  Risk: {data['risk_score']} ({data['risk_level']})")
    print(f"  Summary: {data['summary'][:120]}...")
    print(f"  Recommended actions: {len(data['recommended_actions'])}")
    for a in data["recommended_actions"]:
        print(f"    - {a}")
    print("  [OK] PASSED")

    # ── 14. Delete agent skill ──
    print("\n" + "=" * 60)
    print("14. DELETE AGENT SKILL")
    print("=" * 60)
    resp = requests.delete(f"{BASE}/decision-engine/agent-skills/{skill_id}", headers=headers)
    assert resp.status_code == 200, f"Delete skill failed: {resp.text}"
    print(f"  Deleted skill: {skill_id}")
    print("  [OK] PASSED")

    # ── 15. Dashboard stats ──
    print("\n" + "=" * 60)
    print("15. DASHBOARD STATS")
    print("=" * 60)
    resp = requests.get(f"{BASE}/decision-engine/stats", headers=headers)
    assert resp.status_code == 200, f"Stats failed: {resp.text}"
    data = resp.json()
    print(f"  Total decisions: {data['total_decisions']}")
    print(f"  Auto-resolved: {data['auto_resolved']}")
    print(f"  Escalated: {data['escalated']}")
    print(f"  Routed: {data['routed']}")
    print(f"  Clarification needed: {data['clarification_needed']}")
    print(f"  Avg confidence: {data['avg_confidence']:.2f}")
    print(f"  Avg risk: {data['avg_risk']:.2f}")
    print(f"  Escalation rate: {data['escalation_rate']:.2%}")
    print(f"  By category: {data['decisions_by_category']}")
    print(f"  By outcome: {data['decisions_by_outcome']}")
    print("  [OK] PASSED")

    # ── Summary ──
    print("\n" + "=" * 60)
    print("ALL TESTS PASSED!")
    print("=" * 60)

if __name__ == "__main__":
    try:
        main()
    except AssertionError as e:
        print(f"\n  [FAIL] {e}")
        sys.exit(1)
    except Exception as e:
        print(f"\n  [ERROR] {type(e).__name__}: {e}")
        sys.exit(1)
