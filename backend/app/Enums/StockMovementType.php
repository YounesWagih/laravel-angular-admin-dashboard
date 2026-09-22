<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Adjustment = 'adjustment';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case Reservation = 'reservation';
    case ReservationRelease = 'reservation_release';
    case Fulfillment = 'fulfillment';
}
