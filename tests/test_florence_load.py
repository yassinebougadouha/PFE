"""Quick test: load Florence-2 via our provider and run inference."""
import sys, time, asyncio

print("Step 1: importing provider ...", flush=True)
t0 = time.time()

from app.visual_ai.providers.local_advanced import LocalAdvancedProvider
import io, struct, zlib

def make_png(w=100, h=80, r=200, g=50, b=50):
    raw = b""
    for _ in range(h):
        raw += b"\x00" + bytes([r, g, b]) * w
    ihdr = struct.pack(">IIBBBBB", w, h, 8, 2, 0, 0, 0)
    def chunk(ct, d):
        c = ct + d
        crc = zlib.crc32(c) & 0xFFFFFFFF
        return len(d).to_bytes(4, "big") + c + crc.to_bytes(4, "big")
    return b"\x89PNG\r\n\x1a\n" + chunk(b"IHDR", ihdr) + chunk(b"IDAT", zlib.compress(raw)) + chunk(b"IEND", b"")

provider = LocalAdvancedProvider()
png = make_png()

async def run():
    print(f"\nStep 2: extract_ocr ...", flush=True)
    t1 = time.time()
    ocr = await provider.extract_ocr(png)
    print(f"  OCR: text={ocr.text!r}, confidence={ocr.confidence}, words={ocr.word_count} ({time.time()-t1:.1f}s)", flush=True)

    print(f"\nStep 3: analyze_ui ...", flush=True)
    t2 = time.time()
    ui = await provider.analyze_ui(png)
    print(f"  Caption: {ui.caption!r}", flush=True)
    print(f"  Elements: {len(ui.elements)}", flush=True)
    print(f"  Regions: {len(ui.regions)}", flush=True)
    print(f"  ({time.time()-t2:.1f}s)", flush=True)

    print(f"\nStep 4: encode_embedding ...", flush=True)
    t3 = time.time()
    emb = await provider.encode_embedding(png)
    print(f"  Embedding dim: {len(emb)}", flush=True)
    print(f"  ({time.time()-t3:.1f}s)", flush=True)

    print(f"\nStep 5: full_analysis ...", flush=True)
    t4 = time.time()
    result = await provider.full_analysis(png)
    print(f"  Provider: {result.provider}", flush=True)
    print(f"  OCR: {result.ocr.text!r}", flush=True)
    print(f"  Caption: {result.ui_analysis.caption!r}", flush=True)
    print(f"  Elements: {len(result.ui_analysis.elements)}", flush=True)
    print(f"  Embedding dim: {len(result.embedding)}", flush=True)
    print(f"  Processing: {result.processing_ms}ms", flush=True)
    print(f"  ({time.time()-t4:.1f}s)", flush=True)

asyncio.run(run())

total = time.time() - t0
print(f"\nDONE — Total: {total:.1f}s", flush=True)
