@extends('layouts.app')
@section('title', 'Daftar Servis HP Baru')

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0">Daftar Servis HP</h2>
</div>

<div class="grid-2">
    <div class="card">
        <h3 style="font-size:.95rem;margin-bottom:16px"><i class="fas fa-mobile-alt" style="color:var(--primary);margin-right:6px"></i>Form Pendaftaran</h3>
        <form method="POST" action="{{ route('my-service.store') }}">
            @csrf
            <div class="form-group">
                <label>Nama Pemilik *</label>
                <input type="text" name="nama" class="form-input" value="{{ auth()->user()->name }}" required>
            </div>
            <div class="form-group">
                <label>No. HP *</label>
                <input type="tel" name="no_hp" class="form-input" value="{{ auth()->user()->phone ?? '' }}" placeholder="08xxx" required>
            </div>

            {{-- Pilih Cabang --}}
            <div class="form-group">
                <label>Pilih Cabang / Toko Tujuan *</label>
                <select name="cabang_id" class="form-input" required>
                    <option value="">-- Pilih Cabang --</option>
                    @foreach($cabangs as $c)
                    <option value="{{ $c->id }}">{{ $c->nama }}{{ $c->alamat ? ' — ' . $c->alamat : '' }}</option>
                    @endforeach
                </select>
                <div style="font-size:.72rem;color:#94a3b8;margin-top:4px">Pilih toko/cabang tempat Anda ingin servis HP</div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Perangkat *</label>
                    <input type="text" name="perangkat" class="form-input" placeholder="iPhone 11, Samsung A52..." required>
                </div>
                <div class="form-group">
                    <label>Tipe *</label>
                    <select name="tipe" class="form-input" required>
                        <option value="">-- Pilih --</option>
                        <option value="Apple">Apple</option>
                        <option value="Android">Android</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>IMEI (opsional)</label>
                <input type="text" name="imei" class="form-input" placeholder="15 digit" maxlength="15">
            </div>
            <div class="form-group">
                <label>Keluhan / Kerusakan *</label>
                <input type="text" name="keluhan" class="form-input" placeholder="LCD pecah, baterai drop, mati total..." required>
            </div>
            <div class="form-group">
                <label>Catatan Tambahan</label>
                <input type="text" name="catatan" class="form-input" placeholder="Opsional">
            </div>
            <div style="padding:12px;background:#f0fdf4;border-radius:8px;font-size:.78rem;color:#166534;margin-bottom:16px">
                <i class="fas fa-info-circle"></i> Setelah mendaftar, silakan bawa HP ke <strong>toko/cabang yang Anda pilih</strong>. Teknisi kami akan segera memproses servis Anda.
            </div>
            <div style="display:flex;gap:8px">
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Daftar Servis</button>
                <a href="{{ route('my-service.index') }}" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
    <div>
        <div class="card" style="margin-bottom:16px">
            <h3 style="font-size:.95rem;margin-bottom:10px"><i class="fas fa-store" style="color:var(--primary);margin-right:6px"></i>Cabang FIXPRO</h3>
            @foreach($cabangs as $c)
            <div style="padding:10px 0;border-bottom:1px solid #f1f5f9;font-size:.84rem">
                <strong style="color:var(--primary)">{{ $c->nama }}</strong>
                @if($c->alamat)
                <div style="font-size:.76rem;color:#64748b">{{ $c->alamat }}</div>
                @endif
                @if($c->telp)
                <div style="font-size:.76rem;color:#64748b"><i class="fas fa-phone" style="margin-right:4px"></i>{{ $c->telp }}</div>
                @endif
            </div>
            @endforeach
        </div>
        <div class="card" style="margin-bottom:16px">
            <h3 style="font-size:.95rem;margin-bottom:10px"><i class="fas fa-question-circle" style="color:var(--info);margin-right:6px"></i>Cara Daftar</h3>
            <ol style="font-size:.84rem;color:#64748b;line-height:2.2;padding-left:16px">
                <li>Isi data HP Anda</li>
                <li>Pilih toko/cabang tujuan</li>
                <li>Jelaskan keluhan / kerusakan</li>
                <li>Submit formulir</li>
                <li>Bawa HP ke toko yang dipilih</li>
                <li>Pantau status di menu Riwayat</li>
            </ol>
        </div>
        <div class="card">
            <h3 style="font-size:.95rem;margin-bottom:10px"><i class="fas fa-shield-alt" style="color:var(--success);margin-right:6px"></i>Kenapa FIXPRO?</h3>
            <ul style="font-size:.84rem;color:#64748b;line-height:2;padding-left:16px">
                <li>Tracking real-time servis Anda</li>
                <li>Garansi servis resmi</li>
                <li>Update status otomatis</li>
                <li>Teknisi profesional</li>
                <li>Sparepart berkualitas</li>
            </ul>
        </div>
    </div>
</div>
@endsection
