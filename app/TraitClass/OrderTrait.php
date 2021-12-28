<?php

namespace App\TraitClass;

trait OrderTrait
{
    public function generateOrderNumber()
    {
        return 'S'.date('Ymd').random_int(100000, 999999).substr(microtime(true),-4);
    }
}