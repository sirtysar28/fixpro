<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Barcode - FIXPRO</title>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; padding: 20px; }
        .print-header { text-align: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #333; }
        .print-header h1 { font-size: 1.2rem; }
        .print-header p { font-size: .8rem; color: #666; }
        .bc-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .bc-card {
            border: 1px solid #333; border-radius: 8px; padding: 14px; text-align: center;
            page-break-inside: avoid; background: #fff;
        }
        .bc-card .bc-name { font-size: .78rem; font-weight: 700; margin-bottom: 2px; }
        .bc-card .bc-code { font-size: .6rem; color: #666; }
        .bc-card .bc-barcode { margin: 8px 0; }
        .bc-card .bc-barcode svg { max-width: 100%; }

        @media print {
            body { padding: 10px; }
            .no-print { display: none !important; }
            .bc-grid { grid-template-columns: repeat(3, 1fr); gap: 8px; }
        }
    </style>
</head>
<body>
    <div class="print-header">
        <h1>🏷️ BARCODE PRODUK - FIXPRO</h1>
        <p>Dicetak pada {{ now()->format('d/m/Y H:i') }} • Total: {{ $stoks->count() }} produk</p>
    </div>

    <div class="bc-grid">
        @foreach($stoks as $s)
        <div class="bc-card">
            <div class="bc-name">{{ $s->nama }}</div>
            <div class="bc-code">{{ $s->kode }}</div>
            <div class="bc-barcode">
                <svg id="print-bc-{{ $s->id }}"></svg>
            </div>
        </div>
        @endforeach
    </div>

    <div class="no-print" style="text-align:center;margin-top:20px">
        <button onclick="window.print()" style="padding:12px 32px;background:#0d9488;color:#fff;border:none;border-radius:8px;font-size:1rem;font-weight:700;cursor:pointer">🖨️ Cetak Sekarang</button>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const stoks = @json($stoks->map(fn($s) => ['id' => $s->id, 'barcode' => $s->barcode]));
        stoks.forEach(s => {
            try {
                JsBarcode("#print-bc-" + s.id, s.barcode, {
                    format: "CODE128", width: 1.5, height: 40, displayValue: true, fontSize: 10, margin: 4
                });
            } catch(e) { console.warn('Error', s.id, e); }
        });
    });
    </script>
</body>
</html>
