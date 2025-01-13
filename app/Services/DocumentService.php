<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentService
{
    public function generateInvoice(Order $order)
    {
        $pdf = Pdf::loadView('documents.invoice', compact('order'))
            ->setPaper('A4', 'portrait')
            ->output();

        return response()->streamDownload(
            fn () => print ($pdf),
            'Invoice-'.$order->order_number.'.pdf'
        );
    }
}
