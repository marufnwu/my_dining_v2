<?php

namespace App\Utils;

class ContainerData
{
    /**
     * Get the mess_id from the container.
     *
     * @return mixed
     */
    public static function getMessId()
    {
        return app('mess_id') ?? null;
    }

    /**
     * Get the month_id from the container.
     *
     * @return mixed
     */
    public static function getMonthId()
    {
        return app('month_id');
    }
}
