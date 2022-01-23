<?php

namespace RaziAlsayyed\LaravelCascadedSoftDeletes\Traits;

use RaziAlsayyed\LaravelCascadedSoftDeletes\Exceptions\LogicException;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Exceptions\RuntimeException;
use RaziAlsayyed\LaravelCascadedSoftDeletes\Jobs\CascadeSoftDeletes;

trait CascadedSoftDeletes
{

    /**
     * Keeps track of Instance deleted_at time.
     *
     * @var \Carbon\Carbon
     */
    public static $instanceDeletedAt;

    /**
     * Sign on model events.
     */
    protected static function bootCascadedSoftDeletes()
    {

        static::deleted(function ($model) {
            $model->deleteCascadedSoftDeletes();
        });

        if (static::instanceUsesSoftDelete()) {
            static::restoring(function ($model) {
                static::$instanceDeletedAt = $model->{$model->getDeletedAtColumn()};
            });

            static::restored(function ($model) {
                $model->restoreCascadedSoftDeleted();
            });
        }
    }

    /**
     * Delete the cascaded relations.
     */
    protected function deleteCascadedSoftDeletes() : void
    {
        if($this->isHardDeleting())
            return;

        CascadeSoftDeletes::dispatch($this, 'delete', null);
    }

    /**
     * Restore the cascaded relations.
     */
    protected function restoreCascadedSoftDeleted() : void
    {
        CascadeSoftDeletes::dispatch($this, 'restore', static::$instanceDeletedAt);
    }

    /**
     * Delete/Restore Cascaded Relationships
     *
     * @param $action
     */
    public function cascadeSoftDeletes(string $action, ?\Carbon\Carbon $instanceDeletedAt = null) : void
    {
        if(!method_exists($this, 'getCascadedSoftDeletes')) {
            throw new RuntimeException('getCascadedSoftDeletes function not found!');
        }

        collect($this->getCascadedSoftDeletes())->each(function($item, $key) use ($action, $instanceDeletedAt) {

            $relation = $key;
            if(is_numeric($key))
                $relation = $item;

            if(!is_callable($item) && !$this->relationUsesSoftDelete($relation))
                throw new LogicException('relationship '.$relation.' does not use SoftDeletes trait.');

            if(is_callable($item))
                $query = $item();
            else
                $query = $this->{$relation}();

            if($action == 'delete')
                $query->get()->each->delete();
            else
                $query
                    ->onlyTrashed()
                    ->where($this->getDeletedAtColumn(), '>=', $instanceDeletedAt->format('Y-m-d H:i:s.u'))
                    ->get()
                    ->each
                    ->restore();

            // else {
            //     $query = $query
            //         ->withTrashed()
            //         ->where($query->qualifyColumn($this->getDeletedAtColumn()), '>=', $instanceDeletedAt);

            //     dd($query->toSql(), $query->getBindings(), $query->pluck('deleted_at'));

            // }

        });

    }

    /**
     * Get whether user is intended to delete the model from database entirely.
     *
     * @return bool
     */
    protected function isHardDeleting() : bool
    {
        return ! $this->instanceUsesSoftDelete() || $this->forceDeleting;
    }

    /**
     * Check if the instance uses SoftDeletes trait.
     *
     * @return bool
     */
    protected static function instanceUsesSoftDelete() : bool
    {
        static $softDelete;

        if (is_null($softDelete)) {
            $instance = new static;

            return $softDelete = method_exists($instance, 'bootSoftDeletes');
        }

        return $softDelete;
    }

    /**
     * Check if the relation uses SoftDeletes trait.
     *
     * @return bool
     */
    public static function relationUsesSoftDelete($relation) : bool
    {
        static $softDeletes;

        if(is_null($softDeletes))
            $softDeletes = collect([]);

        return $softDeletes->get($relation, function() use($relation, $softDeletes) {
            $instance = new static;
            $cls = $instance->{$relation}()->getRelated();
            $relationInstance = new $cls;
            return $softDeletes->put($relation, method_exists($relationInstance, 'bootSoftDeletes'))->get($relation);
        });
    }
}
