<?php

namespace App\Models\Conf;

use Illuminate\Database\Eloquent\Model;

class ImageCategory extends Model
{
    
    protected $table = 'inf_image_category';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'category_name',
    ];

    protected $casts = [
    ];
}
