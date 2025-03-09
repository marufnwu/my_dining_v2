<?php

namespace App\Traits;

trait HasModelName
{
    public function getModelNameAttribute()
    {
        return class_basename($this);
    }

    protected function initializeHasModelName()
    {
        $this->appends[] = 'model_name';
    }
}
