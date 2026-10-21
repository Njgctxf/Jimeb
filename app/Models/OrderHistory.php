<?php

namespace App\Models;

use App\Models\BaseModel;

/** Jimbe : historique horaire d'une commande (changements d'état, paiement, addition envoyée). */
class OrderHistory extends BaseModel
{
    protected $fillable = ['order_id', 'status'];

    public static function record(int $orderId, ?string $status): void
    {
        if ($status) {
            static::create(['order_id' => $orderId, 'status' => $status]);
        }
    }
}
