<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapController extends Controller
{
    /**
     * Receive a basket of {product_id, quantity} pairs,
     * return the total average basket price per city.
     */
    public function compute(Request $request): JsonResponse
    {
        $request->validate([
            'basket'              => ['required', 'array', 'min:1'],
            'basket.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'basket.*.quantity'   => ['required', 'numeric', 'min:0.01'],
            'days'                => ['sometimes', 'integer', 'min:0'],
        ]);

        $currency = auth()->user()->effectiveCurrency();
        $days     = $request->input('days', 30);
        $basket   = $request->input('basket');
        $products = Product::findMany(array_column($basket, 'product_id'));
        $cities   = City::with('country')->get();

        $results = $cities->map(function (City $city) use ($basket, $products, $currency, $days) {
            $total     = 0.0;
            $complete  = true;

            foreach ($basket as $item) {
                $product  = $products->find($item['product_id']);
                $avg      = $product->averagePriceInCity($city, $currency, $days);

                if ($avg === null) {
                    $complete = false;
                    continue;
                }

                $total += $avg * (float) $item['quantity'];
            }

            return [
                'city_id'   => $city->id,
                'city_name' => $city->name,
                'country'   => $city->country->name,
                'lat'       => $city->lat,
                'lng'       => $city->lng,
                'total'     => round($total, 2),
                'complete'  => $complete, // false = some products had no data
            ];
        })->filter(fn($r) => $r['total'] > 0)->values();

        return response()->json([
            'results'  => $results,
            'currency' => [
                'symbol' => $currency->symbol,
                'code'   => $currency->code,
            ],
        ]);
    }
}