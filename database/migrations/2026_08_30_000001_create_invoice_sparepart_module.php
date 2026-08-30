<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * REVISI FITUR INVOICE SPAREPART & CONTROL AKTIVASI — FIXPRO
 *
 * 1. Invoice Sparepart = pusat transaksi penjualan (Retail + Grosir 1/2/3 + Reseller + Member + Harga Khusus)
 * 2. Piutang, pembayaran bertahap, retur, void, approval diskon, log perubahan
 * 3. Aktivasi: request diperluas (status Diproses/Aktif/Nonaktif/Expired) & kode aktivasi terikat cabang
 */
return new class extends Migration
{
    public function up(): void
    {
        // ===== 1. HEADER INVOICE =====
        if (!Schema::hasTable('invoice_spareparts')) {
            Schema::create('invoice_spareparts', function (Blueprint $table) {
                $table->id();
                $table->string('no_invoice', 40)->unique();
                $table->foreignId('cabang_id')->nullable()->constrained('cabang')->nullOnDelete(); // cabang pencatat invoice
                $table->foreignId('sumber_cabang_id')->nullable()->constrained('cabang')->nullOnDelete(); // gudang/cabang sumber stok
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // kasir pembuat invoice

                // Data pelanggan (snapshot)
                $table->foreignId('pelanggan_grosir_id')->nullable()->constrained('pelanggan_grosirs')->nullOnDelete();
                $table->string('nama_pelanggan')->nullable();
                $table->string('no_wa', 30)->nullable();
                $table->text('alamat')->nullable();
                $table->string('tipe_pelanggan', 30)->default('Umum'); // Umum/Member/Grosir/Reseller/Distributor

                $table->dateTime('tanggal');
                $table->decimal('subtotal', 15, 2)->default(0);      // sebelum diskon (sudah dikurangi diskon per item)
                $table->decimal('diskon_item', 15, 2)->default(0);   // total diskon per item
                $table->decimal('diskon_total', 15, 2)->default(0);  // diskon transaksi
                $table->decimal('total', 15, 2)->default(0);         // subtotal - diskon_total
                $table->decimal('total_retur', 15, 2)->default(0);
                $table->decimal('dibayar', 15, 2)->default(0);
                $table->decimal('sisa', 15, 2)->default(0);          // piutang

                $table->enum('metode_bayar', ['Tunai', 'Transfer', 'QRIS', 'DP', 'Tempo'])->default('Tunai');
                $table->enum('status', ['Lunas', 'Sebagian', 'Piutang', 'Dibatalkan'])->default('Lunas');
                $table->date('jatuh_tempo')->nullable();

                // Approval diskon
                $table->foreignId('approval_diskon_oleh')->nullable()->constrained('users')->nullOnDelete();

                // Void
                $table->string('alasan_void', 500)->nullable();
                $table->foreignId('void_oleh')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('void_pada')->nullable();

                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('catatan')->nullable();
                $table->timestamps();

                $table->index(['cabang_id', 'tanggal']);
                $table->index(['status', 'jatuh_tempo']);
            });
        }

        // ===== 2. ITEM INVOICE =====
        if (!Schema::hasTable('invoice_sparepart_items')) {
            Schema::create('invoice_sparepart_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_sparepart_id')->constrained('invoice_spareparts')->cascadeOnDelete();
                $table->foreignId('stok_id')->nullable()->constrained('stoks')->nullOnDelete();
                $table->string('kode', 60)->nullable();
                $table->string('nama');
                $table->string('merk_hp', 60)->nullable(); // tipe HP
                $table->string('tipe_lcd', 60)->nullable(); // tipe LCD (origami/OCA/soft pack dll)
                $table->integer('qty')->default(1);
                $table->decimal('harga_satuan', 15, 2)->default(0); // harga sebelum diskon item
                $table->string('jenis_harga', 20)->default('retail'); // retail/grosir1/grosir2/grosir3/reseller/member/khusus/manual
                $table->decimal('diskon', 15, 2)->default(0);        // diskon per item (Rp)
                $table->decimal('harga_modal', 15, 2)->default(0);
                $table->decimal('subtotal', 15, 2)->default(0);      // qty*harga - diskon
                $table->timestamps();
            });
        }

        // ===== 3. PEMBAYARAN (riwayat pembayaran / pelunasan piutang) =====
        if (!Schema::hasTable('invoice_sparepart_payments')) {
            Schema::create('invoice_sparepart_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_sparepart_id')->constrained('invoice_spareparts')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->decimal('jumlah', 15, 2);
                $table->enum('metode', ['Tunai', 'Transfer', 'QRIS'])->default('Tunai');
                $table->dateTime('tanggal');
                $table->string('catatan', 255)->nullable();
                $table->timestamps();
            });
        }

        // ===== 4. LOG PERUBAHAN INVOICE (diskon, harga, void, bayar, retur) =====
        if (!Schema::hasTable('invoice_sparepart_logs')) {
            Schema::create('invoice_sparepart_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_sparepart_id')->constrained('invoice_spareparts')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('aksi', 30); // create/diskon/harga/void/bayar/retur
                $table->string('deskripsi', 500);
                $table->json('data_lama')->nullable();
                $table->json('data_baru')->nullable();
                $table->timestamps();
            });
        }

        // ===== 5. RETUR INVOICE =====
        if (!Schema::hasTable('invoice_returs')) {
            Schema::create('invoice_returs', function (Blueprint $table) {
                $table->id();
                $table->string('no_retur', 40)->unique();
                $table->foreignId('invoice_sparepart_id')->constrained('invoice_spareparts')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('cabang_id')->nullable()->constrained('cabang')->nullOnDelete();
                $table->dateTime('tanggal');
                $table->decimal('total', 15, 2)->default(0);
                $table->string('alasan', 500)->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('invoice_retur_items')) {
            Schema::create('invoice_retur_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_retur_id')->constrained('invoice_returs')->cascadeOnDelete();
                $table->foreignId('invoice_sparepart_item_id')->nullable()->constrained('invoice_sparepart_items')->nullOnDelete();
                $table->foreignId('stok_id')->nullable()->constrained('stoks')->nullOnDelete();
                $table->string('nama')->nullable();
                $table->integer('qty')->default(1);
                $table->decimal('harga_satuan', 15, 2)->default(0);
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->timestamps();
            });
        }

        // ===== 6. HARGA MEMBER di master harga grosir =====
        if (Schema::hasTable('harga_grosirs') && !Schema::hasColumn('harga_grosirs', 'harga_member')) {
            Schema::table('harga_grosirs', function (Blueprint $table) {
                $table->decimal('harga_member', 15, 2)->nullable()->after('harga_reseller');
            });
        }

        // ===== 7. AKTIVASI REQUEST DIPERLUAS =====
        if (Schema::hasTable('activation_requests')) {
            Schema::table('activation_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('activation_requests', 'nama_cabang')) {
                    $table->string('nama_cabang')->nullable()->after('nama_toko');
                }
                if (!Schema::hasColumn('activation_requests', 'alamat')) {
                    $table->text('alamat')->nullable()->after('nama_cabang');
                }
                if (!Schema::hasColumn('activation_requests', 'nama_pemilik')) {
                    $table->string('nama_pemilik')->nullable()->after('alamat');
                }
                if (!Schema::hasColumn('activation_requests', 'no_wa')) {
                    $table->string('no_wa', 30)->nullable()->after('nama_pemilik');
                }
                if (!Schema::hasColumn('activation_requests', 'email')) {
                    $table->string('email')->nullable()->after('no_wa');
                }
                if (!Schema::hasColumn('activation_requests', 'paket')) {
                    $table->string('paket', 30)->nullable()->after('email'); // standar/enterprise
                }
                if (!Schema::hasColumn('activation_requests', 'jumlah_user')) {
                    $table->integer('jumlah_user')->default(1)->after('paket');
                }
                if (!Schema::hasColumn('activation_requests', 'jumlah_perangkat')) {
                    $table->integer('jumlah_perangkat')->nullable()->after('jumlah_user');
                }
            });
            // Status diperluas: pending | processing | approved | rejected | aktif | nonaktif | expired
            if (Schema::getColumnType('activation_requests', 'status') === 'enum') {
                DB::statement("ALTER TABLE activation_requests MODIFY status VARCHAR(20) NOT NULL DEFAULT 'pending'");
            }
        }

        // ===== 8. KODE AKTIVASI TERIKAT CABANG + MASA BERLAKU =====
        if (Schema::hasTable('activation_codes')) {
            Schema::table('activation_codes', function (Blueprint $table) {
                if (!Schema::hasColumn('activation_codes', 'cabang_id')) {
                    $table->foreignId('cabang_id')->nullable()->constrained('cabang')->nullOnDelete()->after('code');
                }
                if (!Schema::hasColumn('activation_codes', 'status')) {
                    $table->string('status', 20)->default('aktif')->after('cabang_id'); // aktif/nonaktif
                }
                if (!Schema::hasColumn('activation_codes', 'paket')) {
                    $table->string('paket', 30)->nullable()->after('durasi');
                }
                if (!Schema::hasColumn('activation_codes', 'jumlah_user')) {
                    $table->integer('jumlah_user')->default(1)->after('paket');
                }
                if (!Schema::hasColumn('activation_codes', 'activated_at')) {
                    $table->timestamp('activated_at')->nullable()->after('is_used');
                }
                if (!Schema::hasColumn('activation_codes', 'activated_by')) {
                    $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete()->after('activated_at');
                }
                if (!Schema::hasColumn('activation_codes', 'mulai_berlaku')) {
                    $table->timestamp('mulai_berlaku')->nullable()->after('activated_by');
                }
                if (!Schema::hasColumn('activation_codes', 'berakhir_berlaku')) {
                    $table->timestamp('berakhir_berlaku')->nullable()->after('mulai_berlaku');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_retur_items');
        Schema::dropIfExists('invoice_returs');
        Schema::dropIfExists('invoice_sparepart_logs');
        Schema::dropIfExists('invoice_sparepart_payments');
        Schema::dropIfExists('invoice_sparepart_items');
        Schema::dropIfExists('invoice_spareparts');
    }
};
