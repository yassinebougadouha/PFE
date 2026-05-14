"""Verify fastapi_ticket_id mapping in GLPI list endpoint."""
import asyncio, httpx

async def main():
    async with httpx.AsyncClient(timeout=30) as c:
        r = await c.get(
            "http://localhost:8000/api/v1/tickets/glpi-list?range=0-5",
            headers={"Accept": "application/json"},
        )
        j = r.json()
        print(f"Total: {j.get('total')}")
        for t in j.get("tickets", [])[:5]:
            print(
                f"  glpi={t.get('glpi_ticket_id')} "
                f"fastapi_id={'yes' if t.get('fastapi_ticket_id') else 'no'} "
                f"status={t.get('status')} "
                f"subject={t.get('subject', '?')[:40]}"
            )

asyncio.run(main())
