<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

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

    public function generateShippingLabel(Order $order)
    {
        $pdf = Pdf::loadView('documents.shipping-label', compact('order'))
            ->setPaper('A6', 'portrait')
            ->output();

        return response()->streamDownload(
            fn () => print ($pdf),
            'Label Pengiriman-'.$order->order_number.'.pdf'
        );
    }

    public function generateSalesReport(Collection $sales, string $month = '', string $year = '')
    {
        $pdf = Pdf::loadView('documents.sales-report', compact('sales', 'month', 'year'))
            ->setPaper('A4', 'landscape')
            ->output();

        $year = $year !== '' ? $year : date('Y');

        $fileName = 'Laporan Penjualan';

        if ($month !== '') {
            $fileName .= '-'.$month;
        }

        $fileName .= '-'.$year.'.pdf';

        return response()->streamDownload(
            fn () => print ($pdf),
            $fileName
        );
    }
}
