<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use PDF;
class InvoiceEmailManager extends Mailable
{
    use Queueable, SerializesModels;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $array;

    public function __construct($array)
    {
        $this->array = $array;
    }
    /**
     * Build the message.
     *
     * @return $this
     */
     public function build()
     {
        $data['order'] = $this->array['order'];
        $invoice_no_array = explode('/',$data['order']->invoice_number);
        $invoice_no = "invoice-".end($invoice_no_array).".pdf";
        $data['orderOtherDetails'] = $this->array['orderOtherDetails'];
        $pdf  = PDF::loadView('backend.invoices.invoice_pdf_mail',$data);
         return $this->view($this->array['view'])
                     ->from($this->array['from'], env('MAIL_FROM_NAME'))
                     ->subject($this->array['subject'])
                     ->attachData($pdf->output(), $invoice_no)
                     // ->html($this->array['body'])
                     ->with([
                         'order' => $this->array['order'],
                         'orderOtherDetails' => $this->array['orderOtherDetails'] ?? [],
                     ]);
     }
}
