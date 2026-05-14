"""
Integration test for GLPI ticket synchronization.
Demonstrates full flow: create ticket locally -> sync to GLPI -> update status -> sync back.
"""

import asyncio
import logging
from datetime import datetime

import httpx

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)


class GlpiIntegrationTester:
    """End-to-end GLPI integration test."""

    def __init__(self, backend_url: str = "http://127.0.0.1:8000", glpi_url: str = "http://platform-glpi-main-laravel.test-1/api/v1"):
        self.backend_url = backend_url
        self.glpi_url = glpi_url
        self.client = httpx.AsyncClient(timeout=30.0)
        self.auth_token = None
        self.ticket_id = None
        self.glpi_ticket_id = None

    async def setup(self):
        """Setup: Authenticate with backend."""
        logger.info("=" * 80)
        logger.info("GLPI TICKET INTEGRATION TEST")
        logger.info("=" * 80)
        
        # For testing, we'll assume auth token exists or mock it
        self.auth_token = "test_token_for_testing"
        logger.info(f"✓ Using auth token")

    async def test_create_ticket(self):
        """Test 1: Create a ticket locally."""
        logger.info("\n[TEST 1] Creating ticket locally...")
        
        payload = {
            "subject": "Integration Test - GLPI Sync",
            "description": "This is a test ticket to verify GLPI synchronization",
            "priority": "HIGH",
            "channel_source": "TICKET",
        }
        
        try:
            response = await self.client.post(
                f"{self.backend_url}/api/v1/tickets",
                json=payload,
                headers={"Authorization": f"Bearer {self.auth_token}"}
            )
            
            if response.status_code in (200, 201):
                data = response.json()
                self.ticket_id = data.get("id")
                logger.info(f"✓ Ticket created with ID: {self.ticket_id}")
                logger.info(f"  - Subject: {data.get('subject')}")
                logger.info(f"  - Status: {data.get('status')}")
                logger.info(f"  - GLPI Sync Status: {data.get('glpi_sync_status')}")
                return True
            else:
                logger.error(f"✗ Failed to create ticket: {response.status_code}")
                logger.error(response.text)
                return False
        except Exception as e:
            logger.error(f"✗ Error creating ticket: {e}")
            return False

    async def test_verify_glpi_sync(self):
        """Test 2: Verify ticket was synced to GLPI."""
        logger.info("\n[TEST 2] Verifying GLPI synchronization...")
        
        if not self.ticket_id:
            logger.warning("⊘ Skipping: No ticket ID from previous test")
            return False
        
        try:
            response = await self.client.get(
                f"{self.backend_url}/api/v1/tickets/{self.ticket_id}/glpi/status",
                headers={"Authorization": f"Bearer {self.auth_token}"}
            )
            
            if response.status_code == 200:
                data = response.json()
                self.glpi_ticket_id = data.get("glpi_ticket_id")
                sync_status = data.get("sync_status")
                
                logger.info(f"✓ GLPI sync status retrieved")
                logger.info(f"  - GLPI Ticket ID: {self.glpi_ticket_id}")
                logger.info(f"  - Sync Status: {sync_status}")
                logger.info(f"  - Synced At: {data.get('synced_at')}")
                
                if sync_status == "synced" and self.glpi_ticket_id:
                    logger.info("✓ Ticket successfully synced to GLPI!")
                    return True
                else:
                    logger.warning(f"⊘ Ticket not yet synced (status: {sync_status})")
                    return False
            else:
                logger.error(f"✗ Failed to get sync status: {response.status_code}")
                return False
        except Exception as e:
            logger.error(f"✗ Error checking sync status: {e}")
            return False

    async def test_update_ticket_status(self):
        """Test 3: Update ticket status and verify GLPI sync."""
        logger.info("\n[TEST 3] Updating ticket status and syncing to GLPI...")
        
        if not self.ticket_id:
            logger.warning("⊘ Skipping: No ticket ID from previous test")
            return False
        
        payload = {
            "status": "IN_PROGRESS",
            "resolution_note": "Started working on this issue"
        }
        
        try:
            response = await self.client.post(
                f"{self.backend_url}/api/v1/tickets/{self.ticket_id}/status",
                json=payload,
                headers={"Authorization": f"Bearer {self.auth_token}"}
            )
            
            if response.status_code == 200:
                data = response.json()
                logger.info(f"✓ Ticket status updated")
                logger.info(f"  - New Status: {data.get('status')}")
                logger.info(f"  - GLPI Sync Status: {data.get('glpi_sync_status')}")
                logger.info(f"  - Resolution Note: {data.get('resolution_note')}")
                return True
            else:
                logger.error(f"✗ Failed to update status: {response.status_code}")
                logger.error(response.text)
                return False
        except Exception as e:
            logger.error(f"✗ Error updating status: {e}")
            return False

    async def test_manual_sync_from_glpi(self):
        """Test 4: Manual sync from GLPI back to local database."""
        logger.info("\n[TEST 4] Manual sync from GLPI...")
        
        if not self.glpi_ticket_id:
            logger.warning("⊘ Skipping: No GLPI ticket ID from previous tests")
            return False
        
        try:
            response = await self.client.post(
                f"{self.backend_url}/api/v1/tickets/glpi/{self.glpi_ticket_id}/sync",
                headers={"Authorization": f"Bearer {self.auth_token}"}
            )
            
            if response.status_code == 200:
                data = response.json()
                logger.info(f"✓ Manual sync from GLPI successful")
                logger.info(f"  - Ticket ID: {data.get('id')}")
                logger.info(f"  - Subject: {data.get('subject')}")
                logger.info(f"  - Status: {data.get('status')}")
                logger.info(f"  - GLPI Sync Status: {data.get('glpi_sync_status')}")
                return True
            else:
                logger.error(f"✗ Failed to sync from GLPI: {response.status_code}")
                logger.error(response.text)
                return False
        except Exception as e:
            logger.error(f"✗ Error syncing from GLPI: {e}")
            return False

    async def test_get_ticket(self):
        """Test 5: Retrieve full ticket details."""
        logger.info("\n[TEST 5] Retrieving full ticket details...")
        
        if not self.ticket_id:
            logger.warning("⊘ Skipping: No ticket ID from previous test")
            return False
        
        try:
            response = await self.client.get(
                f"{self.backend_url}/api/v1/tickets/{self.ticket_id}",
                headers={"Authorization": f"Bearer {self.auth_token}"}
            )
            
            if response.status_code == 200:
                data = response.json()
                logger.info(f"✓ Ticket details retrieved")
                logger.info(f"  - ID: {data.get('id')}")
                logger.info(f"  - Subject: {data.get('subject')}")
                logger.info(f"  - Status: {data.get('status')}")
                logger.info(f"  - Priority: {data.get('priority')}")
                logger.info(f"  - GLPI ID: {data.get('glpi_ticket_id')}")
                logger.info(f"  - GLPI Sync Status: {data.get('glpi_sync_status')}")
                logger.info(f"  - Created At: {data.get('created_at')}")
                logger.info(f"  - Updated At: {data.get('updated_at')}")
                return True
            else:
                logger.error(f"✗ Failed to retrieve ticket: {response.status_code}")
                return False
        except Exception as e:
            logger.error(f"✗ Error retrieving ticket: {e}")
            return False

    async def cleanup(self):
        """Cleanup: Close HTTP client."""
        await self.client.aclose()
        logger.info("\n" + "=" * 80)
        logger.info("TEST COMPLETED")
        logger.info("=" * 80)

    async def run_all_tests(self):
        """Run all integration tests."""
        await self.setup()
        
        results = {
            "Create Ticket": await self.test_create_ticket(),
            "Verify GLPI Sync": await self.test_verify_glpi_sync(),
            "Update Ticket Status": await self.test_update_ticket_status(),
            "Manual Sync from GLPI": await self.test_manual_sync_from_glpi(),
            "Get Ticket Details": await self.test_get_ticket(),
        }
        
        await self.cleanup()
        
        # Print summary
        logger.info("\nTEST SUMMARY")
        logger.info("-" * 80)
        passed = 0
        for test_name, result in results.items():
            status = "✓ PASS" if result else "✗ FAIL"
            logger.info(f"{status:7} | {test_name}")
            if result:
                passed += 1
        
        logger.info("-" * 80)
        logger.info(f"Total: {passed}/{len(results)} tests passed")
        logger.info("=" * 80)
        
        return passed == len(results)


async def main():
    """Run GLPI integration tests."""
    # Adjust URLs if needed for your environment
    tester = GlpiIntegrationTester(
        backend_url="http://localhost:8000",
        glpi_url="http://localhost:8001"
    )
    
    success = await tester.run_all_tests()
    exit(0 if success else 1)


if __name__ == "__main__":
    asyncio.run(main())
