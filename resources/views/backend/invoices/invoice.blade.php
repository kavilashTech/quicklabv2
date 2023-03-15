<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Order Email Verification</title>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
        <style>
            body {
                padding: 0px;
                margin: 0px;
                box-sizing: border-box;
                background-color: #f8fff8;
                font-family: "Open Sans", sans-serif;
                color: #000000;
            }
            .container {
                max-width: 600px;
                margin: 50px auto;
                background-color: #f8fff8;
                border-radius: 10px;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
                border-style: solid;
                border-width: thin;
                border-color: #dadce0;
            }
            .order-card {
                padding: 20px 20px 20px 30px;
                background-color: #243824;
                border-radius: 8px;
                width: 80%;
                margin: 20px auto 0px;
            }
            .social-list {
                width: 50%;
            }
            .social-list {
                text-align: center;
            }
            @media only screen and (max-width: 600px) {
                .container {
                    width: 95%;
                }
                .order-card {
                    width: 100%;
                    padding: 15px;
                }
                .social-list {
                    width: 50%;
                    float: left;
                    margin-bottom: 0px;
                    text-align: start !important;
                }
            }
        </style>
    </head>

    <body>
        @php
            $shipping_address = json_decode($order->shipping_address);
        @endphp
        <div class="container">
            <div style="padding: 15px; text-align: center; border-bottom: 0.5px solid #dadce0;"><img src="https://quicklabkart.com/public/assets/img/logo.png" alt=" logo" style="width: 200px;" /></div>
            <div style="padding: 20px;">
                <p style="margin: 0px 0px 20px;">Hello , <span style="color: #243824; font-weight: bold;"> {{ $shipping_address->name }}</span>,</p>
                <div style="padding: 20px 20px 30px; background-color: #fff;">
                    <h2 style="margin-top: 0px; margin-bottom: 5px; font-size: 20px; text-align: center;">Your Order Is Confirmed</h2>
                    <p style="margin-top: 0px; margin-bottom: 10px; font-size: 15px; text-align: center;">Order number <span style="color: #243824; font-weight: bold;">: {{ $order->code }}</span></p>
                </div>
                <div>
                    <p style="font-size: 14px;">
                        Thank you so much for your business! We will get started on your order right away. When we ship your order, you will receive an auto-generated notification email. The transit time will vary based on the method of
                        shipping you chose.
                    </p>
                    <p style="font-size: 14px;">Please find the Invoice attached with this email.</p>
                    <p style="font-size: 14px;">In the meantime, if any questions, please do not hesitate to connect with us.</p>
                    <p style="font-size: 14px; margin-bottom: 0px;">Cheers!</p>
                </div>
            </div>
            <div style="border-top: 0.5px solid #dadce0; padding: 15px 20px;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tbody>
                        <tr>
                            <td class="social-list">
                                <h3 style="color: #000; margin-top: 0px; margin-bottom: 5px; font-size: 15px;">Email</h3>
                                <a href="mailto:info@quicklab.in" style="font-size: 13px; color: #243824; font-weight: bold;">info@quicklab.in</a>
                            </td>
                            <td class="social-list">
                                <h3 style="color: #000; margin-top: 0px; margin-bottom: 5px; font-size: 15px;">Phone number</h3>
                                <a href="tel:+91 74490 93777" style="font-size: 13px; color: #243824; font-weight: bold;">+91 74490 93777</a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </body>
</html>
