<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use PDF;

class QuotationMail extends Mailable
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
        $quotationOtherDetails = $this->array['quotationOtherDetails'] ?? [];
        $quotation_no = "quotation.pdf";
        if(!empty($quotationOtherDetails)){
            $no = $quotationOtherDetails['quotation_estimate_number'];
            $quotation_no = "quotation-$no.pdf";
        }
        $data['quotation'] = $this->array['content'];
        $data['sender']    = $this->array['sender'];
        $data['details']   = $this->array['details'];
        $data['quotationOtherDetails'] = $this->array['quotationOtherDetails'] ?? [];
        $pdf  = PDF::loadView('emails.quotation',$data);
        return $this->view('emails.quotation_new')
                    ->from($this->array['from'], env('MAIL_FROM_NAME'))
                    ->subject($this->array['subject'])
                    ->attachData($pdf->output(), $quotation_no)
                    ->with([
                        'quotation' => $this->array['content'],
                        'sender' => $this->array['sender'],
                        'details' => $this->array['details'],
                        'quotationOtherDetails' => $this->array['quotationOtherDetails'] ?? [],
                    ]);
    }
}
