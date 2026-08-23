@extends('layouts.app')
@section('title', 'Multi Cabang')

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0;font-size:1.3rem"><i class="fas fa-store" style="color:var(--primary);margin-right:6px"></i> Multi Cabang</h2>
</div>

<!-- Info box -->
<div class="card mb-4" style="background:linear-gradient(135deg,{{ auth()->user()->isSuperAdmin() ? '#0d9488 0%,#065f46' : '#7c3aed 0%,#4f46e5' }} 100%);color:#fff;border:none">
    <div style="font-size:.88rem;font-weight:700;margin-bottom:12px">
        <i class="fas fa-{{ auth()->user()->isSuperAdmin() ? 'crown' : 'building' }}"></i>
        @if(auth()->user()->isSuperAdmin())
            Super Admin — Kelola Semua Cabang
        @else
            Enterprise — Kelola Toko Anda (1 Pusat + Maks {{ auth()->user()->maxChildCabang() }} Cabang Anak)
        @endif
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:16px">
        <div style="background:rgba(255,255,255,.12);border-radius:10px;padding:14px;text-align:center">
            <div style="font-size:.72rem;opacity:.8;margin-bottom:4px">Total Cabang</div>
            <div style="font-size:1.8rem;font-weight:800">{{ $totalCabang }}</div>
            @if($sisaKuota >= 0)
            <div style="font-size:.64rem;opacity:.7;margin-top:4px">Sisa kuota cabang anak: <strong>{{ $sisaKuota }}</strong></div>
            @endif
        </div>
        <div style="background:rgba(255,255,255,.12);border-radius:10px;padding:14px;text-align:center">
            <div style="font-size:.72rem;opacity:.8;margin-bottom:4px">Total Teknisi</div>
            <div style="font-size:1.8rem;font-weight:800">{{ $totalTeknisi }}</div>
        </div>
        <div style="background:rgba(255,255,255,.12);border-radius:10px;padding:14px;text-align:center">
            <div style="font-size:.72rem;opacity:.8;margin-bottom:4px">Omset Semua Cabang</div>
            <div style="font-size:1.4rem;font-weight:800">{{ formatRp($totalOmset) }}</div>
        </div>
        <div style="background:rgba(255,255,255,.12);border-radius:10px;padding:14px;text-align:center">
            <div style="font-size:.72rem;opacity:.8;margin-bottom:4px">Laba Bersih Total</div>
            <div style="font-size:1.4rem;font-weight:800">{{ formatRp($totalLaba) }}</div>
        </div>
    </div>
</div>

<!-- Form tambah cabang -->
<div class="card mb-4">
    <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-plus-circle" style="color:var(--primary);margin-right:6px"></i> Tambah Cabang Baru</h3>
    @if($sisaKuota >= 0 && $sisaKuota <= 0)
    <div style="padding:12px 16px;background:#fef2f2;border-radius:8px;font-size:.82rem;color:#991b1b;margin-bottom:12px">
        <i class="fas fa-exclamation-triangle"></i> Kuota cabang anak sudah penuh (Paket {{ ucfirst(auth()->user()->paket ?? 'standar') }}: maks {{ auth()->user()->maxChildCabang() }} cabang anak).
    </div>
    @endif
    <form method="POST" action="{{ route('cabang.store') }}">
        @csrf
        <div class="form-row">
            <div class="form-group">
                <label for="nama">Nama Cabang</label>
                <input type="text" name="nama" class="form-input" required placeholder="Contoh: Cabang Utama">
            </div>
            <div class="form-group">
                <label for="telp">No. Telepon</label>
                <input type="text" name="telp" class="form-input" placeholder="08xx-xxxx-xxxx">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="tipe">Tipe Lokasi</label>
                <select name="tipe" class="form-input">
                    <option value="toko">🏠 Toko (tempat penjualan)</option>
                    <option value="gudang">🏬 Gudang (sumber stok grosir)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="alamat">Alamat</label>
                <input type="text" name="alamat" class="form-input" placeholder="Jl. Contoh No. 123">
            </div>
        </div>
        <button type="submit" class="btn btn-primary" @if($sisaKuota >= 0 && $sisaKuota <= 0) disabled @endif><i class="fas fa-plus"></i> Tambah Cabang</button>
    @if(!auth()->user()->isSuperAdmin())
    <span style="margin-left:10px;font-size:.74rem;color:#64748b">Maksimal <strong>{{ auth()->user()->maxChildCabang() }}</strong> cabang anak (1 pusat + 3 anak).</span>
    @endif
    </form>
</div>

<!-- Transfer Stok Antar Cabang -->
<div class="card mb-4">
    <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-exchange-alt" style="color:var(--primary);margin-right:6px"></i> Transfer Stok Antar Cabang</h3>
    <div style="padding:10px 14px;background:#f0fdf4;border-radius:8px;font-size:.78rem;color:#166534;margin-bottom:14px;border:1px solid #bbf7d0">
        <i class="fas fa-info-circle"></i> Transfer stok antar cabang dalam group Anda. Stok di cabang asal akan berkurang otomatis, stok di cabang tujuan akan bertambah.
        @if(!$parentCabang && !auth()->user()->isSuperAdmin())
        <br><strong style="color:#92400e"><i class="fas fa-exclamation-triangle"></i> Anda belum punya cabang pusat (parent). Semua cabang akan muncul di dropdown.</strong>
        @endif
    </div>

    {{-- TPI: Transfer dari Pusat (Enterprise) ke Cabang Anak — BATCH (maks 25 item) --}}
    @if($parentCabang && $childCabangs->count() > 0 && !auth()->user()->isSuperAdmin())
    <div style="padding:16px 20px;background:linear-gradient(135deg,#7c3aed,#4f46e5);border-radius:12px;color:#fff;margin-bottom:16px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
            <div>
                <div style="font-size:.92rem;font-weight:700"><i class="fas fa-building"></i> Transfer Stok dari Pusat ke Cabang Anak</div>
                <div style="font-size:.72rem;opacity:.85;margin-top:4px">Cabang Pusat: <strong>{{ $parentCabang->nama }}</strong> → Kirim ke cabang anak (maks 25 produk sekaligus)</div>
            </div>
        </div>

        <form method="POST" action="{{ route('cabang.transfer-stok-batch') }}" id="formTransferBatch" onsubmit="return validateBatchTransfer()">
            @csrf
            <input type="hidden" name="from_cabang_id" value="{{ $parentCabang->id }}">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
                <div class="form-group">
                    <label style="color:rgba(255,255,255,.8);font-size:.78rem">Cabang Pusat (Asal)</label>
                    <select class="form-input" disabled style="background:#f8fafc;font-size:.82rem">
                        <option selected>{{ $parentCabang->nama }} (Pusat)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label style="color:rgba(255,255,255,.8);font-size:.78rem">Cabang Tujuan (Anak)</label>
                    <select name="to_cabang_id" id="tpiToCabang" class="form-input" required style="font-size:.82rem">
                        <option value="">-- Pilih Cabang Anak --</option>
                        @foreach($childCabangs as $cc)
                        <option value="{{ $cc->id }}">{{ $cc->nama }}{{ !$cc->aktif ? ' (Nonaktif)' : '' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Daftar Produk --}}
            <div style="margin-bottom:10px">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
                    <label style="color:rgba(255,255,255,.9);font-size:.82rem;font-weight:600"><i class="fas fa-boxes-stacked"></i> Produk (dari stok cabang pusat)</label>
                    <button type="button" onclick="addBatchRow()" class="btn" id="btnAddRow" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.3);font-size:.75rem;padding:4px 12px;border-radius:6px;cursor:pointer">
                        <i class="fas fa-plus"></i> Tambah Produk
                    </button>
                </div>
                <div id="batchCounter" style="font-size:.68rem;opacity:.7;margin-bottom:8px">0 / 25 produk ditambahkan</div>

                {{-- Header Tabel --}}
                <div id="batchHeader" style="display:none;grid-template-columns:40px 1fr 100px 80px 40px;gap:8px;padding:8px 10px;background:rgba(255,255,255,.12);border-radius:8px 8px 0 0;font-size:.7rem;font-weight:600;opacity:.8">
                    <div>#</div>
                    <div>Produk</div>
                    <div>Qty</div>
                    <div>Stok</div>
                    <div></div>
                </div>

                {{-- Container Rows --}}
                <div id="batchRows" style="max-height:420px;overflow-y:auto;background:rgba(255,255,255,.06);border-radius:0 0 8px 8px;padding:6px 10px">
                    {{-- Row kosong placeholder --}}
                    <div id="batchPlaceholder" style="text-align:center;padding:28px 10px;font-size:.78rem;opacity:.6">
                        <i class="fas fa-inbox" style="font-size:1.6rem;display:block;margin-bottom:8px"></i>
                        Klik "Tambah Produk" untuk menambahkan sparepart yang akan ditransfer
                    </div>
                </div>
            </div>

            {{-- Catatan --}}
            <div class="form-group" style="margin-bottom:14px">
                <label style="color:rgba(255,255,255,.8);font-size:.78rem">Catatan (opsional)</label>
                <input type="text" name="catatan" class="form-input" placeholder="Catatan transfer..." style="font-size:.82rem">
            </div>

            <button type="submit" class="btn" style="background:rgba(255,255,255,.25);color:#fff;border:1px solid rgba(255,255,255,.4);font-size:.85rem;padding:10px 24px;border-radius:8px;cursor:pointer;font-weight:600;width:100%">
                <i class="fas fa-paper-plane"></i> Kirim ke Cabang Anak
            </button>
        </form>
    </div>
    @endif

    {{-- Transfer Umum (Semua cabang ke semua cabang) --}}
    <h4 style="font-size:.85rem;margin-bottom:12px;color:#475569"><i class="fas fa-arrows-alt-h" style="margin-right:4px"></i> Transfer Umum (Semua Cabang)</h4>
    <form method="POST" action="{{ route('cabang.transfer-stok') }}" onsubmit="return validateTransfer()">
        @csrf
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="form-group">
                <label>Cabang Asal</label>
                <select name="from_cabang_id" id="fromCabang" class="form-input" required onchange="loadStokAsal()">
                    <option value="">-- Pilih Cabang --</option>
                    @foreach($cabangs as $c)
                    <option value="{{ $c->id }}">{{ $c->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>Cabang Tujuan</label>
                <select name="to_cabang_id" id="toCabang" class="form-input" required>
                    <option value="">-- Pilih Cabang --</option>
                    @foreach($cabangs as $c)
                    <option value="{{ $c->id }}">{{ $c->nama }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Produk (dari stok cabang asal)</label>
            <input type="text" id="stokSearch" class="form-input" placeholder="🔍 Ketik untuk mencari produk..." oninput="filterStokSelect()" style="margin-bottom:6px;font-size:.82rem">
            <select name="stok_id" id="stokSelect" class="form-input" required>
                <option value="">-- Pilih cabang asal terlebih dahulu --</option>
            </select>
        </div>
        <div id="stokInfo" style="display:none;padding:8px 14px;background:#eff6ff;border-radius:8px;font-size:.78rem;color:#1e40af;margin-bottom:8px;border:1px solid #bfdbfe">
            <i class="fas fa-box"></i> Stok tersedia: <strong id="stokAvailable">0</strong>
        </div>
        <div class="form-group">
            <label>Jumlah Transfer</label>
            <input type="number" name="qty" id="transferQty" class="form-input" min="1" required placeholder="Jumlah yang akan ditransfer">
        </div>
        <div class="form-group">
            <label>Catatan (opsional)</label>
            <input type="text" name="catatan" class="form-input" placeholder="Catatan transfer...">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-exchange-alt"></i> Transfer Stok</button>
    </form>
</div>

<!-- Daftar cabang & omset -->
<div class="card mb-4">
    <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-list" style="color:var(--primary);margin-right:6px"></i> Data Cabang & Omset Toko</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Cabang</th>
                    <th>Akun Login</th>
                    <th>Email</th>
                    <th>Total Servis</th>
                    <th>Omset Toko</th>
                    <th>Pengeluaran</th>
                    <th>Laba Bersih</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cabangStats as $c)
                <tr>
                    <td>
                        <strong>{{ $c->nama }}</strong>
                        @if($c->parent_cabang_id === null && !$c->cabang?->parent_cabang_id)
                        <span class="badge badge-selesai" style="margin-left:4px;font-size:.6rem">Pusat</span>
                        @elseif($c->parent_cabang_id)
                        <span class="badge badge-proses" style="margin-left:4px;font-size:.6rem">Anak</span>
                        @endif
                        @if(($c->tipe ?? 'toko') === 'gudang')
                        <span class="badge badge-normal" style="margin-left:4px;font-size:.6rem">🏬 Gudang</span>
                        @endif
                        @if(!$c->aktif)
                        <span class="badge badge-pending" style="margin-left:4px">Nonaktif</span>
                        @endif
                    </td>
                    <td>
                        @php $admins = $branchAdmins[$c->id] ?? []; @endphp
                        @if(count($admins) > 0)
                            @foreach($admins as $admin)
                            <div style="display:flex;align-items:center;gap:6px;margin-bottom:2px">
                                <div style="width:24px;height:24px;border-radius:50%;background:var(--primary-bg);display:flex;align-items:center;justify-content:center;color:var(--primary);font-weight:700;font-size:.55rem">{{ strtoupper(substr($admin->name, 0, 2)) }}</div>
                                <div>
                                    <div style="font-size:.76rem;font-weight:600">{{ $admin->name }}</div>
                                    <div style="font-size:.62rem;color:#94a3b8">{{ $admin->email }}</div>
                                </div>
                                @if(!$admin->is_active)
                                <span class="badge badge-pending" style="font-size:.55rem">Off</span>
                                @endif
                            </div>
                            @endforeach
                        @else
                            <span style="font-size:.75rem;color:#94a3b8"><i class="fas fa-user-slash" style="margin-right:3px"></i> Belum ada akun</span>
                        @endif
                    </td>
                    <td style="font-size:.78rem;color:#64748b">{{ $c->stat_email }}</td>
                    <td>{{ $c->stat_servis }}</td>
                    <td style="font-weight:600;color:var(--primary)">{{ formatRp($c->stat_omset) }}</td>
                    <td style="color:var(--danger)">{{ formatRp($c->stat_pengeluaran) }}</td>
                    <td style="font-weight:700;color:var(--success)">{{ formatRp($c->stat_laba) }}</td>
                    <td>
                        <button type="button" class="btn btn-primary btn-xs" onclick="editCabang({{ $c->id }}, '{{ addslashes($c->nama) }}', '{{ addslashes($c->alamat ?? '') }}', '{{ $c->telp ?? '' }}', {{ $c->aktif ? 1 : 0 }})"><i class="fas fa-edit"></i></button>
                        @if(auth()->user()->isSuperAdmin() || (auth()->user()->isEnterprise() && $c->parent_cabang_id !== null && (int)$c->id !== (int)auth()->user()->cabang_id))
                        <button type="button" class="btn btn-xs" style="background:#7c3aed;color:#fff" onclick="openCreateAccountModal({{ $c->id }}, '{{ addslashes($c->nama) }}')" title="Buat Akun Login"><i class="fas fa-user-plus"></i></button>
                        @endif
                        <form method="POST" action="{{ route('cabang.destroy', $c) }}" style="display:inline" onsubmit="return confirm('Hapus cabang {{ $c->nama }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Riwayat Transfer Stok -->
@if($transfers && $transfers->count() > 0)
<div class="card mb-4">
    <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-exchange-alt" style="color:var(--primary);margin-right:6px"></i> Riwayat Transfer Stok (20 Terakhir)</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Tanggal</th>
                    <th>Produk</th>
                    <th>Dari</th>
                    <th>Ke</th>
                    <th>Qty</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transfers as $t)
                <tr>
                    <td><strong style="color:var(--primary)">{{ $t->kode }}</strong></td>
                    <td>{{ $t->created_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $t->stok?->nama ?? '-' }}</td>
                    <td><span style="color:#dc2626">{{ $t->fromCabang?->nama ?? '-' }}</span></td>
                    <td><span style="color:#16a34a">{{ $t->toCabang?->nama ?? '-' }}</span></td>
                    <td><strong>{{ $t->qty }}</strong></td>
                    <td style="font-size:.76rem;color:#64748b">{{ $t->catatan ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Teknisi performance -->
<div class="card mb-4">
    <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-wrench" style="color:var(--primary);margin-right:6px"></i> Pendapatan Teknisi Seluruh Cabang</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Teknisi</th>
                    <th>Cabang</th>
                    <th>Servis Selesai</th>
                    <th>Omset</th>
                    <th>Laba Bersih (50%)</th>
                    <th>Bagi %</th>
                    <th>Bagi Hasil Rp</th>
                </tr>
            </thead>
            <tbody>
                @foreach($teknisiAll as $t)
                <tr>
                    <td><strong>{{ $t->nama }}</strong></td>
                    <td>{{ $t->cabang?->nama ?? '-' }}</td>
                    <td>{{ $t->selesai_count }}</td>
                    <td style="font-weight:600">{{ formatRp($t->omset) }}</td>
                    <td>{{ formatRp($t->laba_bersih) }}</td>
                    <td><span class="badge badge-selesai">{{ $t->bagi_persen }}%</span></td>
                    <td style="font-weight:700;color:var(--success)">{{ formatRp($t->bagi_hasil) }}</td>
                </tr>
                @endforeach
                @if($teknisiAll->count() === 0)
                <tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:20px">Belum ada data teknisi.</td></tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Buat Akun Login Cabang -->
<div id="accountModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:16px;padding:28px;max-width:500px;width:90%">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
            <h3 style="font-size:1rem;margin:0"><i class="fas fa-user-plus" style="color:#7c3aed;margin-right:6px"></i> Buat Akun Login Cabang</h3>
            <button type="button" onclick="closeAccountModal()" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:#94a3b8"><i class="fas fa-times"></i></button>
        </div>
        <div style="padding:12px 16px;background:#f5f3ff;border-radius:10px;border:1px solid #e9d5ff;margin-bottom:16px">
            <div style="font-size:.72rem;color:#7c3aed;font-weight:600;margin-bottom:4px">📍 CABANG TUJUAN</div>
            <div style="font-size:.92rem;font-weight:700;color:#4c1d95" id="accountModalCabangName">-</div>
            <div style="font-size:.68rem;color:#94a3b8;margin-top:2px">Akun ini akan terhubung ke cabang di atas. Login dengan akun ini akan langsung masuk ke cabang tersebut.</div>
        </div>
        <form id="accountForm" method="POST" action="{{ route('cabang.create-account') }}">
            @csrf
            <input type="hidden" name="cabang_id" id="accountCabangId">
            <div class="form-group">
                <label>Nama Admin *</label>
                <input type="text" name="name" id="accountName" class="form-input" required placeholder="Nama admin cabang">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" id="accountEmail" class="form-input" required placeholder="admin@cabang.com">
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" id="accountPassword" class="form-input" required minlength="6" placeholder="Min. 6 karakter">
                </div>
            </div>
            <div class="form-group">
                <label>No. HP</label>
                <input type="tel" name="phone" id="accountPhone" class="form-input" placeholder="08xxxxxxxxxx">
            </div>
            <div style="padding:10px 14px;background:#fffbeb;border-radius:8px;border:1px solid #fde68a;font-size:.76rem;color:#92400e;margin-bottom:16px">
                <i class="fas fa-info-circle" style="margin-right:4px"></i>
                <strong>Catatan:</strong> Akun yang dibuat akan mendapat role <strong>Admin</strong> dengan status <strong>Permanen</strong>.
                Akun ini hanya bisa mengelola data cabang yang dituju (transaksi, stok, laporan cabang sendiri).
            </div>
            <div style="display:flex;gap:8px;margin-top:16px">
                <button type="submit" class="btn" style="background:#7c3aed;color:#fff"><i class="fas fa-user-plus"></i> Buat Akun</button>
                <button type="button" class="btn btn-secondary" onclick="closeAccountModal()"><i class="fas fa-times"></i> Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit -->
<div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:16px;padding:28px;max-width:480px;width:90%">
        <h3 style="font-size:1rem;margin-bottom:16px"><i class="fas fa-edit" style="color:var(--primary);margin-right:6px"></i> Edit Cabang</h3>
        <form id="editForm" method="POST" action="">
            @csrf @method('PUT')
            <div class="form-group">
                <label>Nama Cabang</label>
                <input type="text" name="nama" id="editNama" class="form-input" required>
            </div>
            <div class="form-group">
                <label>Alamat</label>
                <input type="text" name="alamat" id="editAlamat" class="form-input">
            </div>
            <div class="form-group">
                <label>Telepon</label>
                <input type="text" name="telp" id="editTelp" class="form-input">
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" name="aktif" id="editAktif" value="1" style="width:18px;height:18px;accent-color:var(--primary)">
                    <span>Cabang Aktif</span>
                </label>
            </div>
            <div style="display:flex;gap:8px;margin-top:16px">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()"><i class="fas fa-times"></i> Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Pencarian Produk (Transfer Stok) -->
<div id="productSearchModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:999;align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:16px;padding:24px;max-width:520px;width:92%;max-height:80vh;display:flex;flex-direction:column">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
            <h3 style="font-size:1rem;margin:0"><i class="fas fa-search" style="color:var(--primary);margin-right:6px"></i> Cari Produk</h3>
            <button type="button" onclick="closeProductSearch()" style="background:none;border:none;font-size:1.1rem;cursor:pointer;color:#94a3b8"><i class="fas fa-times"></i></button>
        </div>
        <input type="text" id="productSearchInput" class="form-input" placeholder="Ketik nama atau kode produk..." oninput="filterProductSearch()" style="margin-bottom:12px">
        <div id="productSearchResults" style="overflow-y:auto;flex:1;border:1px solid #e2e8f0;border-radius:10px;min-height:120px"></div>
        <div style="font-size:.72rem;color:#94a3b8;margin-top:8px;text-align:center">Klik produk untuk menambahkannya ke daftar transfer</div>
    </div>
</div>

<script>
// Inject animation
(function(){const s=document.createElement('style');s.textContent='@keyframes fadeIn{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}';document.head.appendChild(s)})();

let stokList = [];
let tpiStokList = [];
let batchRowCount = 0;
const MAX_BATCH_ROWS = 25;

// Auto-load stok for TPI (parent cabang) on page load
(function autoLoadTpiStok() {
    const parentCabangId = '{{ $parentCabang?->id ?? "" }}';
    if (parentCabangId) {
        fetch('/api/cabang-stok?cabang_id=' + parentCabangId)
            .then(r => r.json())
            .then(data => {
                tpiStokList = data;
            })
            .catch(() => {});
    }
})();

function addBatchRow() {
    if (batchRowCount >= MAX_BATCH_ROWS) {
        alert('Maksimal ' + MAX_BATCH_ROWS + ' produk yang bisa ditambahkan!');
        return;
    }
    if (tpiStokList.length === 0) {
        alert('Tidak ada stok tersedia di cabang pusat.');
        return;
    }
    // Buka modal pencarian produk
    openProductSearch();
}

// ====== PENCARIAN PRODUK (Transfer Stok) ======
function openProductSearch() {
    const modal = document.getElementById('productSearchModal');
    const input = document.getElementById('productSearchInput');
    input.value = '';
    modal.style.display = 'flex';
    renderProductSearch('');
    setTimeout(() => input.focus(), 50);
}

function closeProductSearch() {
    document.getElementById('productSearchModal').style.display = 'none';
}

function filterProductSearch() {
    const q = document.getElementById('productSearchInput').value.trim().toLowerCase();
    renderProductSearch(q);
}

function renderProductSearch(q) {
    const box = document.getElementById('productSearchResults');
    // Ambil id produk yang sudah dipilih (cegah duplikat)
    const already = Array.from(document.querySelectorAll('#batchRows .batch-stok-select'))
        .map(s => s.value).filter(Boolean);

    const filtered = tpiStokList.filter(s => {
        if (already.includes(String(s.id))) return false;
        if (!q) return true;
        return (s.nama || '').toLowerCase().includes(q) || (s.kode || '').toLowerCase().includes(q);
    });

    if (filtered.length === 0) {
        box.innerHTML = '<div style="text-align:center;color:#94a3b8;padding:30px;font-size:.82rem"><i class=\'fas fa-search\' style=\'font-size:1.4rem;display:block;margin-bottom:6px;opacity:.4\'></i>Tidak ada produk ditemukan' + (q ? ' untuk "' + q + '"' : '') + '</div>';
        return;
    }

    box.innerHTML = filtered.map(s =>
        '<div onclick="pickProductFromSearch(' + s.id + ')" style="padding:10px 14px;border-bottom:1px solid #f1f5f9;cursor:pointer;display:flex;justify-content:space-between;align-items:center" onmouseover="this.style.background=\'#f0fdfa\'" onmouseout="this.style.background=\'\'">' +
            '<div><div style="font-weight:600;font-size:.84rem">' + (s.nama||'-') + '</div><div style="font-size:.7rem;color:#94a3b8">' + (s.kode||'-') + '</div></div>' +
            '<div style="text-align:right"><span style="font-size:.7rem;color:#16a34a;font-weight:600">Stok: ' + s.stok + '</span></div>' +
        '</div>'
    ).join('');
}

function pickProductFromSearch(stokId) {
    closeProductSearch();
    // tambahkan row dengan produk tsb terpilih
    const placeholder = document.getElementById('batchPlaceholder');
    if (placeholder) placeholder.style.display = 'none';
    document.getElementById('batchHeader').style.display = 'grid';
    batchRowCount++;
    const rowNum = batchRowCount;
    const container = document.getElementById('batchRows');
    const row = document.createElement('div');
    row.id = 'batchRow_' + rowNum;
    row.style.cssText = 'display:grid;grid-template-columns:40px 1fr 100px 80px 40px;gap:8px;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.08);align-items:center;animation:fadeIn .2s ease';

    let optionsHtml = '<option value="">-- Pilih --</option>';
    tpiStokList.forEach(s => {
        optionsHtml += '<option value="' + s.id + '" data-stok="' + s.stok + '" data-satuan="' + (s.satuan || 'pcs') + '"' + (s.id == stokId ? ' selected' : '') + '>' + s.nama + ' (' + s.kode + ')</option>';
    });

    row.innerHTML =
        '<div style="font-size:.72rem;opacity:.7;text-align:center">' + rowNum + '</div>' +
        '<select name="stok_ids[]" class="form-input batch-stok-select" required onchange="onBatchStokChange(this)" style="font-size:.78rem;padding:6px 8px">' + optionsHtml + '</select>' +
        '<input type="number" name="qtys[]" class="form-input batch-qty-input" min="1" required placeholder="0" style="font-size:.78rem;padding:6px 8px">' +
        '<div class="batch-stok-avail" style="font-size:.7rem;text-align:center;opacity:.7">-</div>' +
        '<button type="button" onclick="removeBatchRow(\'' + row.id + '\')" style="background:rgba(255,255,255,.15);border:none;color:#fca5a5;cursor:pointer;border-radius:6px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;font-size:.8rem" title="Hapus"><i class="fas fa-times"></i></button>';
    container.appendChild(row);
    // trigger isi qty & avail otomatis
    const sel = row.querySelector('.batch-stok-select');
    if (sel) onBatchStokChange(sel);
    updateBatchCounter();
}

function removeBatchRow(rowId) {
    const row = document.getElementById(rowId);
    if (row) {
        row.remove();
        batchRowCount--;
        renumberBatchRows();
        updateBatchCounter();
        if (batchRowCount === 0) {
            const placeholder = document.getElementById('batchPlaceholder');
            if (placeholder) placeholder.style.display = 'block';
            document.getElementById('batchHeader').style.display = 'none';
        }
    }
}

function renumberBatchRows() {
    const rows = document.getElementById('batchRows').querySelectorAll('[id^="batchRow_"]');
    rows.forEach((row, i) => {
        const numEl = row.querySelector('div:first-child');
        if (numEl) numEl.textContent = (i + 1);
    });
}

function onBatchStokChange(selectEl) {
    const row = selectEl.closest('[id^="batchRow_"]');
    const availEl = row.querySelector('.batch-stok-avail');
    const qtyEl = row.querySelector('.batch-qty-input');
    const opt = selectEl.selectedOptions[0];

    if (selectEl.value && opt) {
        const avail = parseInt(opt.dataset.stok) || 0;
        const satuan = opt.dataset.satuan || 'pcs';
        availEl.textContent = avail + ' ' + satuan;
        qtyEl.max = avail;
        qtyEl.placeholder = avail;
        qtyEl.value = 1;
    } else {
        availEl.textContent = '-';
        qtyEl.max = '';
        qtyEl.placeholder = '0';
        qtyEl.value = '';
    }
}

function updateBatchCounter() {
    const counter = document.getElementById('batchCounter');
    const rows = document.getElementById('batchRows').querySelectorAll('[id^="batchRow_"]');
    const count = rows.length;
    counter.textContent = count + ' / ' + MAX_BATCH_ROWS + ' produk ditambahkan';
    counter.style.color = count >= MAX_BATCH_ROWS ? '#fca5a5' : 'rgba(255,255,255,.7)';

    const btn = document.getElementById('btnAddRow');
    if (count >= MAX_BATCH_ROWS) {
        btn.disabled = true;
        btn.style.opacity = '.5';
        btn.style.cursor = 'not-allowed';
    } else {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
    }
}

function validateBatchTransfer() {
    const to = document.getElementById('tpiToCabang')?.value;
    if (!to) {
        alert('Pilih cabang tujuan!');
        return false;
    }

    const rows = document.getElementById('batchRows').querySelectorAll('[id^="batchRow_"]');
    if (rows.length === 0) {
        alert('Tambahkan minimal 1 produk yang akan ditransfer!');
        return false;
    }

    // Validasi setiap row
    for (const row of rows) {
        const select = row.querySelector('.batch-stok-select');
        const qty = row.querySelector('.batch-qty-input');
        if (!select.value) {
            alert('Pilih produk pada semua baris!');
            select.focus();
            return false;
        }
        if (!qty.value || parseInt(qty.value) < 1) {
            alert('Isi jumlah transfer minimal 1!');
            qty.focus();
            return false;
        }
        const maxStok = parseInt(qty.max);
        if (maxStok && parseInt(qty.value) > maxStok) {
            alert('Jumlah transfer melebihi stok tersedia (' + maxStok + ')!');
            qty.focus();
            return false;
        }
    }

    // Cek duplikasi produk
    const selectedIds = [];
    for (const row of rows) {
        const val = row.querySelector('.batch-stok-select').value;
        if (val) selectedIds.push(val);
    }
    if (new Set(selectedIds).size !== selectedIds.length) {
        alert('Tidak boleh ada produk yang sama ditambahkan lebih dari 1 kali.\nGabungkan qty-nya menjadi satu baris.');
        return false;
    }

    return confirm('Kirim ' + rows.length + ' produk ke cabang tujuan?');
}

function editCabang(id, nama, alamat, telp, aktif) {
    document.getElementById('editForm').action = '/cabang/' + id;
    document.getElementById('editNama').value = nama;
    document.getElementById('editAlamat').value = alamat;
    document.getElementById('editTelp').value = telp;
    document.getElementById('editAktif').checked = aktif == 1;
    document.getElementById('editModal').style.display = 'flex';
}
function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}

function openCreateAccountModal(cabangId, cabangName) {
    document.getElementById('accountCabangId').value = cabangId;
    document.getElementById('accountModalCabangName').textContent = cabangName;
    document.getElementById('accountName').value = '';
    document.getElementById('accountEmail').value = '';
    document.getElementById('accountPassword').value = '';
    document.getElementById('accountPhone').value = '';
    document.getElementById('accountModal').style.display = 'flex';
}
function closeAccountModal() {
    document.getElementById('accountModal').style.display = 'none';
}

function validateTransfer() {
    // Try to detect which form is being submitted
    const tpiForm = document.querySelector('input[name="from_cabang_id"][value="{{ $parentCabang?->id ?? '' }}"]');
    if (tpiForm) {
        const to = document.getElementById('tpiToCabang')?.value;
        if (!to) {
            alert('Pilih cabang tujuan!');
            return false;
        }
        return true;
    }
    // General transfer form
    const from = document.getElementById('fromCabang').value;
    const to = document.getElementById('toCabang').value;
    if (from === to) {
        alert('Cabang asal dan tujuan tidak boleh sama!');
        return false;
    }
    return true;
}

function loadStokAsal() {
    const cabangId = document.getElementById('fromCabang').value;
    const select = document.getElementById('stokSelect');
    const info = document.getElementById('stokInfo');

    if (!cabangId) {
        select.innerHTML = '<option value="">-- Pilih cabang asal terlebih dahulu --</option>';
        info.style.display = 'none';
        return;
    }

    select.innerHTML = '<option value="">Memuat stok...</option>';
    info.style.display = 'none';

    fetch('/api/cabang-stok?cabang_id=' + cabangId)
        .then(r => r.json())
        .then(data => {
            stokList = data;
            if (data.length === 0) {
                select.innerHTML = '<option value="">Tidak ada stok di cabang ini</option>';
            } else {
                select.innerHTML = '<option value="">-- Pilih Produk --</option>';
                data.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id;
                    opt.textContent = s.nama + ' (' + s.kode + ') — Stok: ' + s.stok + ' ' + (s.satuan || 'pcs');
                    select.appendChild(opt);
                });
            }
        })
        .catch(() => {
            select.innerHTML = '<option value="">Gagal memuat stok</option>';
        });
}

document.getElementById('stokSelect')?.addEventListener('change', function() {
    const info = document.getElementById('stokInfo');
    const stokId = parseInt(this.value);
    const stok = stokList.find(s => s.id === stokId);
    if (stok) {
        document.getElementById('stokAvailable').textContent = stok.stok + ' ' + (stok.satuan || 'pcs');
        info.style.display = 'block';
        document.getElementById('transferQty').max = stok.stok;
    } else {
        info.style.display = 'none';
    }
});

// ====== FILTER PENCARIAN PRODUK (Transfer Umum) ======
function filterStokSelect() {
    const q = (document.getElementById('stokSearch')?.value || '').trim().toLowerCase();
    const select = document.getElementById('stokSelect');
    if (!select || stokList.length === 0) return;

    const currentVal = select.value;
    select.innerHTML = '<option value="">-- Pilih Produk --</option>';
    stokList.forEach(s => {
        const text = (s.nama + ' ' + s.kode).toLowerCase();
        if (!q || text.includes(q)) {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.nama + ' (' + s.kode + ') \u2014 Stok: ' + s.stok + ' ' + (s.satuan || 'pcs');
            select.appendChild(opt);
        }
    });
    if (currentVal && Array.from(select.options).some(o => o.value === currentVal)) {
        select.value = currentVal;
    }
}
</script>
@endsection
