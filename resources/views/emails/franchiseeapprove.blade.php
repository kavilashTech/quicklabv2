<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Email Approved</title>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
        <style>
            body {
                /* background-color: #243824; */
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
            @media only screen and (max-width: 600px) {
                .container {
                    width: 95%;
                }
            }
            .button:hover {
                background-color: #243824;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div style="padding: 20px 15px 20px; border-bottom: 0.5px solid #dadce0;">
                <img src="https://quicklabkart.com/public/assets/img/logo.png" alt=" logo" style="width: 200px;" />
            </div>

            <div style="padding: 0px 30px 15px; position: relative; z-index: 99;">
                <div style="width: 130px; margin: 0px; position: absolute; right: 30px; top: 10px; z-index: -1; opacity: 0.4;">
                    <img src="https://store.suitecrm.com/assets/img/addons/bv-email-verify/logo.png?1644951288" alt="email" style="width: 100%;" />
                </div>
                <h1 style="font-size: 24px; margin-top: 0px; padding: 30px 0px; margin-bottom: 0px;">
                    Your Franchisee Profile Approved.
                </h1>

                <p>Dear <span style="color: #243824; font-weight: bold;"> {{ $array['name'] }}</span>,</p>
                <p style="margin-top: 0px;">
                   Congratulations! You are now the franchisee of Quicklab.<BR>We welcome you for a wonderful future ahead.
                </p>
                <p>Quicklab Team</p>


            </div>

        </div>
    </body>
</html>
