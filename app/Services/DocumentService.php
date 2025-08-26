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
        $products = [];

        foreach ($sales as $item) {
            // dd($item);

            $variantId = $item->product_variant_id;
            $price = $item->order_detail_price;
            $quantity = $item->order_detail_quantity;

            if (isset($products[$variantId])) {
                $products[$variantId]['total_sold'] += $quantity;
                $products[$variantId]['total_sales'] += $price * $quantity;
            } else {
                $products[$variantId] = [
                    'variant_id' => $variantId,
                    'name' => $item->product_name,
                    'variation_name' => $item->variation_name ? $item->variation_name : null,
                    'variant_name' => $item->variant_name ? $item->variant_name : null,
                    'category_name' => $item->category_name ? $item->category_name : null,
                    'subcategory_name' => $item->subcategory_name ? $item->subcategory_name : null,
                    'total_sold' => $quantity,
                    'price' => $price,
                    'cost_price' => $item->product_cost_price,
                    'margin_profit' => $price - $item->product_cost_price,
                    'total_sales' => $price * $quantity,
                    'total_profit' => ($price - $item->product_cost_price) * $quantity,
                ];
            }
        }

        $products = array_values($products);

        $grandTotalSales = array_sum(array_column($products, 'total_sales'));

        $grandTotalProfit = array_sum(array_column($products, 'total_profit'));

        $pdf = Pdf::loadView('documents.sales-report', compact('grandTotalSales', 'grandTotalProfit', 'products', 'month', 'year'))
            ->setPaper('A4', 'landscape')
            ->output();

        $year = $year !== '' ? $year : date('Y');

        $fileName = 'Laporan Penjualan Website Toko Jogja Electrik';

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
