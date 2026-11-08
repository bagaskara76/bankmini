<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    public const MINIMUM_BALANCE = 10000;

    public const MAX_CLASS_SIZE = 36;

    protected $fillable = [
        'user_id',
        'account_number',
        'pin',
        'qr_code_string',
        'balance',
        'grade',
        'kelas',
        'is_graduated',
    ];

    protected $casts = [
        'is_graduated' => 'boolean',
    ];

    /**
     * Daftar kelas yang tersedia di sekolah.
     */
    public static function availableKelas(?string $grade = null): array
    {
        $kelas = [
            'RPL 1', 'RPL 2',
            'TKJ 1', 'TKJ 2',
            'DKV 1', 'DKV 2',
            'AK 1', 'AK 2', 'AK 3', 'AK 4',
            'MP 1',  'MP 2',
            'LPS 1', 'LPS 2',
            'BR 1',  'BR 2',
            'BD',
        ];

        if ($grade === '12') {
            return array_values(array_diff($kelas, ['LPS 2']));
        }

        return $kelas;
    }

    /**
     * Kembalikan label kelas lengkap, contoh: "12 RPL 2"
     */
    public function getFullKelasAttribute(): string
    {
        if ($this->grade && $this->kelas) {
            return $this->grade.' '.$this->kelas;
        }

        return '-';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
