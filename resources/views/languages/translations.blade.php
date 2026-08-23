@extends('layouts.app')
@section('title', 'Terjemahan — ' . $language->name)

@section('content')
<div class="flex-between mb-4" style="flex-wrap:wrap;gap:10px">
    <h2 style="margin:0">
        <i class="fas fa-language" style="color:var(--primary);margin-right:6px"></i>
        Terjemahan — {{ $language->flag }} {{ $language->name }}
        <a href="{{ route('admin.languages.index') }}" class="btn btn-secondary btn-sm" style="margin-left:10px"><i class="fas fa-arrow-left"></i> Kembali</a>
    </h2>
</div>

<div class="alert alert-success" style="background:#fffbeb;border-color:#fde68a;color:#92400e">
    <i class="fas fa-lightbulb"></i>
    <div>Edit nilai terjemahan bahasa <strong>{{ $language->native_name ?? $language->name }}</strong>. Kolom <strong>"Nilai ID"</strong> adalah sumber (master key) — kolom <strong>"Terjemahan"</strong> yang harus Anda isi. Kosongkan jika ingin fallback ke nilai ID.</div>
</div>

{{-- Filter group + search --}}
<div class="card mb-4">
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <input type="hidden" name="q" value="{{ $q }}">
        <label style="font-size:.8rem;font-weight:600;margin-right:4px">Group:</label>
        <select name="group" class="form-input" style="width:auto;padding:7px 10px" onchange="this.form.submit()">
            @foreach($groups as $g)
            <option value="{{ $g }}" {{ $activeGroup === $g ? 'selected' : '' }}>{{ ucfirst($g) }}</option>
            @endforeach
        </select>
        <input type="text" name="q" value="{{ request('q') }}" class="form-input" style="flex:1;min-width:180px;padding:7px 10px" placeholder="Cari key atau nilai...">
        <button class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Cari</button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('addKeyForm').style.display='block'"><i class="fas fa-plus"></i> Tambah Key</button>
    </form>
</div>

{{-- Form tambah key --}}
<div class="card mb-4" id="addKeyForm" style="display:none">
    <h3 style="font-size:.92rem;margin-bottom:12px"><i class="fas fa-plus-circle" style="color:var(--primary)"></i> Tambah Key Baru</h3>
    <form method="POST" action="{{ route('admin.languages.keys.store') }}?group={{ urlencode($activeGroup) }}">
        @csrf
        <input type="hidden" name="group" value="{{ $activeGroup }}">
        <div class="form-row">
            <div class="form-group">
                <label>Key *</label>
                <input type="text" name="key" class="form-input" required placeholder="contoh: welcome_title" style="font-family:monospace">
            </div>
            <div class="form-group">
                <label>Nilai ID (sumber) *</label>
                <input type="text" name="value_id" class="form-input" required placeholder="Teks bahasa Indonesia">
            </div>
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Tambah Key</button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('addKeyForm').style.display='none'">Batal</button>
    </form>
</div>

{{-- Tabel inline editor --}}
<form method="POST" action="{{ route('admin.languages.translations.update', $language) }}">
    @csrf
    <input type="hidden" name="group" value="{{ $activeGroup }}">
    <div class="card">
        <div class="flex-between mb-3" style="flex-wrap:wrap;gap:8px">
            <h3 style="margin:0;font-size:.92rem"><i class="fas fa-edit"></i> Group: <code>{{ $activeGroup }}</code> — {{ $baseRows->total() }} key</h3>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Simpan Semua Perubahan</button>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:240px">Key</th>
                        <th style="width:38%">Nilai ID (Sumber)</th>
                        <th>Terjemahan {{ strtoupper($language->code) }} <span style="color:var(--primary)">●</span></th>
                        <th style="width:60px"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($baseRows as $row)
                    @php
                        $target = $targetMap[$row->key] ?? null;
                        $isEmpty = empty(trim((string)$target));
                    @endphp
                    <tr style="{{ $isEmpty ? 'background:#fffbeb' : '' }}">
                        <td><code style="font-size:.74rem;color:#64748b">{{ $row->key }}</code></td>
                        <td style="font-size:.8rem;color:#475569">{{ $row->value ?? '<em>(kosong)</em>' }}</td>
                        <td>
                            <input type="text" name="values[{{ $row->key }}]" value="{{ old("values.{$row->key}", $target) }}" class="form-input" style="padding:7px 10px;font-size:.82rem" placeholder="{{ $row->value ?? '— terjemahkan di sini —' }}">
                        </td>
                        <td>
                            @if($isEmpty)
                            <span class="badge badge-pending" style="font-size:.6rem" title="Belum diterjemahkan">!</span>
                            @else
                            <span class="badge badge-selesai" style="font-size:.6rem">✓</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    @if($baseRows->isEmpty())
                    <tr><td colspan="4" style="text-align:center;padding:24px;color:#94a3b8">Belum ada key di group ini. Tambahkan key baru.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div style="margin-top:14px;text-align:right">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Semua Perubahan</button>
        </div>
        <div style="margin-top:10px">
            {{ $baseRows->links() }}
        </div>
    </div>
</form>
@endsection
