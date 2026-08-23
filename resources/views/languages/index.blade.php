@extends('layouts.app')
@section('title', 'Multi Bahasa')

@section('content')
<div class="flex-between mb-4">
    <h2 style="margin:0"><i class="fas fa-language" style="color:var(--primary);margin-right:6px"></i> Multi Bahasa</h2>
    <button class="btn btn-primary btn-sm" onclick="document.getElementById('addLangForm').style.display='block'"><i class="fas fa-plus"></i> Tambah Bahasa</button>
</div>

<div class="alert alert-success" style="background:#eff6ff;border-color:#bfdbfe;color:#1e40af">
    <i class="fas fa-info-circle"></i>
    <div>Kelola bahasa yang tersedia di aplikasi. <strong>Indonesia (id)</strong> adalah default & tidak bisa dihapus. Untuk mengubah terjemahan konten EN atau bahasa lain, klik <strong>"Terjemahan"</strong>.</div>
</div>

{{-- Form tambah bahasa --}}
<div class="card mb-4" id="addLangForm" style="display:none">
    <h3 style="font-size:.95rem;margin-bottom:12px"><i class="fas fa-plus-circle" style="color:var(--primary)"></i> Tambah Bahasa Baru</h3>
    <form method="POST" action="{{ route('admin.languages.store') }}">
        @csrf
        <div class="form-row">
            <div class="form-group">
                <label>Kode Bahasa *</label>
                <input type="text" name="code" class="form-input" required placeholder="en, jv, su, ar, zh ..." maxlength="10" style="text-transform:lowercase">
                <div class="text-xs text-muted" style="margin-top:4px">Kode standar (ISO 639-1)</div>
            </div>
            <div class="form-group">
                <label>Nama (English) *</label>
                <input type="text" name="name" class="form-input" required placeholder="English / Javanese / Arabic" maxlength="60">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Nama Native</label>
                <input type="text" name="native_name" class="form-input" placeholder="Bahasa Inggris / Basa Jawa" maxlength="60">
            </div>
            <div class="form-group">
                <label>Emoji Flag</label>
                <input type="text" name="flag" class="form-input" placeholder="🇬🇧" maxlength="12" value="🌐">
            </div>
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="is_active" checked> Aktifkan bahasa ini (tampil di switcher)</label>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addLangForm').style.display='none'">Batal</button>
        <div class="text-xs text-muted" style="margin-top:8px"><i class="fas fa-lightbulb"></i> Bahasa baru otomatis menyalin semua key dari bahasa default (ID) sebagai starter — Anda tinggal menerjemahkannya.</div>
    </form>
</div>

{{-- Daftar bahasa --}}
<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Flag</th>
                    <th>Bahasa</th>
                    <th>Kode</th>
                    <th>Status</th>
                    <th>Total Key</th>
                    <th>Terjemahan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($languages as $lang)
                <tr>
                    <td style="font-size:1.4rem">{{ $lang->flag }}</td>
                    <td>
                        <strong>{{ $lang->name }}</strong>
                        @if($lang->native_name && $lang->native_name !== $lang->name)
                        <br><span style="font-size:.72rem;color:#64748b">{{ $lang->native_name }}</span>
                        @endif
                    </td>
                    <td><code>{{ $lang->code }}</code></td>
                    <td>
                        @if($lang->is_default)
                            <span class="badge" style="background:#dcfce7;color:#166534">Default</span>
                        @endif
                        @if($lang->is_active)
                            <span class="badge badge-selesai">Aktif</span>
                        @else
                            <span class="badge badge-pending">Nonaktif</span>
                        @endif
                    </td>
                    <td>{{ $lang->translations()->count() }}</td>
                    <td>
                        @php
                            $filled = $lang->translations()->whereNotNull('value')->where('value', '!=', '')->count();
                            $total = max(1, $lang->translations()->count());
                            $pct = round($filled / $total * 100);
                        @endphp
                        <div style="font-size:.72rem;color:#64748b">{{ $filled }}/{{ $total }} ({{ $pct }}%)</div>
                        <div style="width:80px;height:6px;background:#e2e8f0;border-radius:4px;margin-top:3px;overflow:hidden">
                            <div style="width:{{ $pct }}%;height:100%;background:var(--primary)"></div>
                        </div>
                    </td>
                    <td>
                        <a href="{{ route('admin.languages.translations', $lang) }}" class="btn btn-secondary btn-xs"><i class="fas fa-edit"></i> Terjemahan</a>
                        @if(!$lang->is_default)
                        <button class="btn btn-secondary btn-xs" onclick='editLang({{ json_encode(["id"=>$lang->id,"name"=>$lang->name,"native_name"=>$lang->native_name,"flag"=>$lang->flag,"is_active"=>$lang->is_active]) }})'><i class="fas fa-cog"></i></button>
                        <form method="POST" action="{{ route('admin.languages.destroy', $lang) }}" style="display:inline" onsubmit="return confirm('Hapus bahasa {{ $lang->name }}? Semua terjemahan akan ikut terhapus.')">
                            @csrf @method('DELETE')
                            <button class="btn btn-danger btn-xs"><i class="fas fa-trash"></i></button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Modal edit --}}
<div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
    <div class="card" style="width:420px;max-width:90vw;margin:0">
        <h3 style="margin-bottom:14px"><i class="fas fa-cog" style="color:var(--primary)"></i> Edit Bahasa</h3>
        <form method="POST" id="editForm" action="">
            @csrf @method('PUT')
            <div class="form-group"><label>Nama (English)</label><input type="text" name="name" id="ef_name" class="form-input" required></div>
            <div class="form-group"><label>Nama Native</label><input type="text" name="native_name" id="ef_native" class="form-input"></div>
            <div class="form-group"><label>Emoji Flag</label><input type="text" name="flag" id="ef_flag" class="form-input"></div>
            <div class="form-group"><label><input type="checkbox" name="is_active" id="ef_active"> Aktif</label></div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('editModal').style.display='none'">Batal</button>
        </form>
    </div>
</div>

<script>
function editLang(data){
    document.getElementById('editForm').action = '{{ route("admin.languages.update", "__ID__") }}'.replace('__ID__', data.id);
    document.getElementById('ef_name').value = data.name;
    document.getElementById('ef_native').value = data.native_name || '';
    document.getElementById('ef_flag').value = data.flag || '';
    document.getElementById('ef_active').checked = !!data.is_active;
    document.getElementById('editModal').style.display = 'flex';
}
</script>
@endsection
