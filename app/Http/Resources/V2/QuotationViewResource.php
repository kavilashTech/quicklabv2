<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\ResourceCollection;

class QuotationViewResource extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
            $total = 0;
            $subTotal = 0;
        return [

            'data' => $this->collection->map(function($data) {

                $product = \App\Models\Product::find($data->product_id);
                $product_stock = $product->stocks->where('variant', $data->variation)->first();
                if(!empty($quotationOtherDetaila->taxAvailable)){
                    $total = $total + (cart_product_price($data, $product, false) + $data->tax) * $data->quantity;
                }else{
                    $total = $total + cart_product_price($data, $product, false) * $data->quantity;
                }

                $subTotal = $subTotal + ($data->price) * $data->quantity;
                $product_name_with_choice = $product->getTranslation('name');
                if ($data->variation != null) {
                    $product_name_with_choice = $product->getTranslation('name') . ' - ' .$data->variation;
                }

                return [
                    'id'                =>$data->id,
                    'product_id'        => $data->product_id,
                    //'total'             => format_price($data->grand_total),
                    //'order_date'        => date('d-m-Y', strtotime($data->created_at)),
                    //'payment_status'    => $data->payment_status,
                    'delivery_status'   => $data->quotation_id
                ];
            })
        ];
    }
}
