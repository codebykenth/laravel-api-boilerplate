<?php

namespace App\Enums;

enum JobStatus: string
{
    case Wishlist = 'wishlist';
    case Applied = 'applied';
    case Interview = 'interview';
    case Interviewed = 'interviewed';
    case Offered = 'offered';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';
    case OfferDeclined = 'offer_declined';
    case OfferAccepted = 'offer_accepted';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
