"""
Test script to verify RAG PDF upload and indexing pipeline.

This tests the complete flow:
1. Upload PDF via Laravel controller
2. Verify file is saved to documents directory
3. Ingest PDF (create article)
4. Index article (chunk + embed)
5. Verify chunks are stored in database
"""

import requests
import json
import time
from pathlib import Path


BASE_API = "http://localhost:8000/api/v1"
ADMIN_TOKEN = "your-admin-token-here"  # Set this to a valid token

# Test PDF content
TEST_PDF_CONTENT = b"""%PDF-1.4
1 0 obj
<< /Type /Catalog /Pages 2 0 R >>
endobj
2 0 obj
<< /Type /Pages /Kids [3 0 R] /Count 1 >>
endobj
3 0 obj
<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>
endobj
4 0 obj
<< >>
stream
BT
/F1 12 Tf
100 700 Td
(Test RAG Article) Tj
0 -20 Td
(This is a test PDF for RAG ingestion.) Tj
ET
endstream
endobj
5 0 obj
<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>
endobj
xref
0 6
0000000000 65535 f 
0000000009 00000 n 
0000000058 00000 n 
0000000115 00000 n 
0000000214 00000 n 
0000000386 00000 n 
trailer
<< /Size 6 /Root 1 0 R >>
startxref
462
%%EOF"""


def test_rag_upload_pipeline():
    """Test the complete RAG upload pipeline."""
    
    print("=" * 60)
    print("RAG PDF UPLOAD & INDEXING TEST")
    print("=" * 60)
    
    # Create test PDF
    test_pdf_path = Path("test_rag_document.pdf")
    test_pdf_path.write_bytes(TEST_PDF_CONTENT)
    print(f"✓ Created test PDF: {test_pdf_path}")
    
    try:
        # 1. Test upload endpoint
        print("\n[1/4] Testing PDF upload endpoint...")
        with open(test_pdf_path, "rb") as f:
            files = {"file": ("test_rag_document.pdf", f, "application/pdf")}
            headers = {"Authorization": f"Bearer {ADMIN_TOKEN}"}
            
            response = requests.post(
                f"{BASE_API}/rag/documents/upload",
                files=files,
                headers=headers,
                timeout=10
            )
        
        if response.status_code in [201, 200]:
            print(f"✓ Upload successful: {response.status_code}")
            upload_data = response.json()
            print(f"  → Filename: {upload_data.get('filename')}")
            print(f"  → Size: {upload_data.get('size_bytes')} bytes")
        else:
            print(f"✗ Upload failed: {response.status_code}")
            print(f"  → Response: {response.text}")
            return False
        
        # 2. Test document listing
        print("\n[2/4] Listing available documents...")
        headers = {"Authorization": f"Bearer {ADMIN_TOKEN}"}
        response = requests.get(
            f"{BASE_API}/rag/documents",
            headers=headers,
            timeout=10
        )
        
        if response.status_code == 200:
            data = response.json()
            print(f"✓ Found {data.get('total_files', 0)} documents")
            if data.get("files"):
                for pdf in data["files"]:
                    print(f"  → {pdf['filename']} ({pdf['size_human']})")
        else:
            print(f"✗ Listing failed: {response.status_code}")
            return False
        
        # 3. Test ingestion
        print("\n[3/4] Ingesting PDF into knowledge base...")
        ingest_payload = {
            "filename": "test_rag_document.pdf",
            "auto_index": True,
            "auto_publish": True,
            "category": "GENERAL",
            "language": "en"
        }
        headers = {"Authorization": f"Bearer {ADMIN_TOKEN}"}
        response = requests.post(
            f"{BASE_API}/rag/documents/ingest",
            json=ingest_payload,
            headers=headers,
            timeout=10
        )
        
        if response.status_code in [201, 200]:
            print(f"✓ Ingestion successful: {response.status_code}")
            ingest_data = response.json()
            article_id = ingest_data.get("article_id")
            chunks = ingest_data.get("chunks_created", 0)
            print(f"  → Article ID: {article_id}")
            print(f"  → Chunks created: {chunks}")
            print(f"  → Status: {ingest_data.get('status')}")
            
            if chunks == 0:
                print("  ⚠ WARNING: No chunks were created!")
                return False
        else:
            print(f"✗ Ingestion failed: {response.status_code}")
            print(f"  → Response: {response.text}")
            return False
        
        # 4. Test statistics
        print("\n[4/4] Checking RAG statistics...")
        response = requests.get(
            f"{BASE_API}/rag/stats",
            headers=headers,
            timeout=10
        )
        
        if response.status_code == 200:
            stats = response.json()
            print(f"✓ Statistics retrieved:")
            print(f"  → Total articles: {stats.get('total_articles', 0)}")
            print(f"  → Total chunks: {stats.get('total_chunks', 0)}")
            print(f"  → Total tokens: {stats.get('total_tokens', 0)}")
        else:
            print(f"✗ Statistics retrieval failed: {response.status_code}")
            return False
        
        print("\n" + "=" * 60)
        print("✓ ALL TESTS PASSED")
        print("=" * 60)
        return True
        
    except Exception as e:
        print(f"\n✗ Test error: {e}")
        import traceback
        traceback.print_exc()
        return False
        
    finally:
        # Cleanup
        if test_pdf_path.exists():
            test_pdf_path.unlink()


if __name__ == "__main__":
    import sys
    success = test_rag_upload_pipeline()
    sys.exit(0 if success else 1)
