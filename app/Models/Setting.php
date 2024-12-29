<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type'];

    public function getValueAttribute($value)
    {
        switch ($this->attributes['type']) {
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'boolean':
                return (bool) $value;
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    public function setValueAttribute($value)
    {
        if (is_array($value) || is_object($value)) {
            $this->attributes['type'] = 'json';
            $this->attributes['value'] = json_encode($value);
        } elseif (is_int($value)) {
            $this->attributes['type'] = 'integer';
            $this->attributes['value'] = $value;
        } elseif (is_float($value)) {
            $this->attributes['type'] = 'float';
            $this->attributes['value'] = $value;
        } elseif (is_bool($value)) {
            $this->attributes['type'] = 'boolean';
            $this->attributes['value'] = $value;
        } else {
            $this->attributes['type'] = 'string';
            $this->attributes['value'] = $value;
        }
    }
}
