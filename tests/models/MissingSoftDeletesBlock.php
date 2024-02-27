<?php

namespace RaziAlsayyed\LaravelCascadedSoftDeletes\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Traits\CascadedSoftDeletes;

class MissingSoftDeletesBlock extends Model
{
    use CascadedSoftDeletes;

    protected $table = 'blocks';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    protected $fillable = ['page_id', 'name'];

    public $timestamps = false;

    public function page()
    {
        return $this->belongsTo(MissingSoftDeletesPage::class, 'page_id', 'id');
    }

    public function plugins()
    {
        return $this->hasMany(Plugin::class, 'block_id', 'id');
    }

    protected function getCascadedSoftDeletes()
    {
        return ['plugins'];
    }
}
