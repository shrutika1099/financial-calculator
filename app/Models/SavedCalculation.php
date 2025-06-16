<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedCalculation extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'type', 'name', 'input_data', 'result_data'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $casts = [
        'input_data' => 'array',
        'result_data' => 'array',
    ];


}
