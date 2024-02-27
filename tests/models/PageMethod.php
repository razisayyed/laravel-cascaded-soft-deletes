<?php

namespace RaziAlsayyed\LaravelCascadedSoftDeletes\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Traits\CascadedSoftDeletes;

class PageMethod extends Model
{
    use CascadedSoftDeletes;
    use SoftDeletes;

    protected $table = 'pages';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = ['name'];

    public $timestamps = false;

    public function blocks()
    {
        return $this->hasMany(Block::class, 'page_id', 'id');
    }

    protected function getCascadedSoftDeletes()
    {
        return ['blocks'];
    }
}
