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
    protected function deleteCascadedSoftDeletes(): void
    {
        if ($this->isHardDeleting()) {
            return;
        }

        if (config('cascaded-soft-deletes.queue_cascades_by_default') === true) {
            CascadeSoftDeletes::dispatch($this, 'delete', null)->onQueue(config('cascaded-soft-deletes.queue_name'));
        } else {
            CascadeSoftDeletes::dispatchSync($this, 'delete', null);
        }
    }

    /**
     * Restore the cascaded relations.
     */
    protected function restoreCascadedSoftDeleted(): void
    {
        if (config('cascaded-soft-deletes.queue_cascades_by_default') === true) {
            CascadeSoftDeletes::dispatch($this, 'restore', static::$instanceDeletedAt)->onQueue(config('cascaded-soft-deletes.queue_name'));
        } else {
            CascadeSoftDeletes::dispatchSync($this, 'restore', static::$instanceDeletedAt);
        }
    }

    /**
     * Delete/Restore Cascaded Relationships.
     */
    public function cascadeSoftDeletes(string $action, ?\Carbon\Carbon $instanceDeletedAt = null): void
    {
        if (method_exists($this, 'getCascadedSoftDeletes')) {
            $relations = collect($this->getCascadedSoftDeletes());
        } elseif (property_exists($this, 'cascadedSoftDeletes')) {
            $relations = collect($this->cascadedSoftDeletes);
        } else {
            throw new RuntimeException('neither getCascadedSoftDeletes function or cascaded_soft_deletes property exists!');
        }

        $relations->each(function ($item, $key) use ($action, $instanceDeletedAt) {
            $relation = $key;
            if (is_numeric($key)) {
                $relation = $item;
            }

            if (! is_callable($item) && ! $this->relationUsesSoftDelete($relation)) {
                throw new LogicException('relationship '.$relation.' does not use SoftDeletes trait.');
            }

            if (is_callable($item)) {
                $query = $item();
            } else {
                $query = $this->{$relation}();
            }

            if ($action == 'delete') {
                $query->get()->each->delete();
            } else {
                $query
                    ->onlyTrashed()
                    ->where($this->getDeletedAtColumn(), '>=', $instanceDeletedAt->format('Y-m-d H:i:s.u'))
                    ->get()
                    ->each
                    ->restore();
            }

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
     */
    protected function isHardDeleting(): bool
    {
        return ! $this->instanceUsesSoftDelete() || $this->forceDeleting;
    }

    /**
     * Check if the instance uses SoftDeletes trait.
     */
    protected static function instanceUsesSoftDelete(): bool
    {
        static $softDelete;

        if (is_null($softDelete)) {
            $instance = new static();

            return $softDelete = method_exists($instance, 'bootSoftDeletes');
        }

        return $softDelete;
    }

    /**
     * Check if the relation uses SoftDeletes trait.
     */
    public static function relationUsesSoftDelete($relation): bool
    {
        static $softDeletes;

        if (is_null($softDeletes)) {
            $softDeletes = collect([]);
        }

        return $softDeletes->get($relation, function () use ($relation, $softDeletes) {
            $instance = new static();
            $cls = $instance->{$relation}()->getRelated();
            $relationInstance = new $cls();

            return $softDeletes->put($relation, method_exists($relationInstance, 'bootSoftDeletes'))->get($relation);
        });
    }
}
