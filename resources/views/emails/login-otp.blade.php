<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0;">
    <meta name="format-detection" content="telephone=no" />

    <style>
        /* Reset styles */
        body {
            margin: 0;
            padding: 0;
            min-width: 100%;
            width: 100% !important;
            height: 100% !important;
        }

        body,
        table,
        td,
        div,
        p,
        a {
            -webkit-font-smoothing: antialiased;
            text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
            line-height: 100%;
        }

        table,
        td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
            border-collapse: collapse !important;
            border-spacing: 0;
        }

        img {
            border: 0;
            line-height: 100%;
            outline: none;
            text-decoration: none;
            -ms-interpolation-mode: bicubic;
            display: block;
            max-width: 100%;
            height: auto !important;
        }

        #outlook a {
            padding: 0;
        }

        .ReadMsgBody {
            width: 100%;
        }

        .ExternalClass {
            width: 100%;
        }

        .ExternalClass,
        .ExternalClass p,
        .ExternalClass span,
        .ExternalClass font,
        .ExternalClass td,
        .ExternalClass div {
            line-height: 100%;
        }

        @media all and (min-width: 560px) {
            body {
                margin-top: 30px;
            }
        }

        /* Rounded corners */
        @media all and (min-width: 560px) {
            .container {
                border-radius: 8px;
                -webkit-border-radius: 8px;
                -moz-border-radius: 8px;
                -khtml-border-radius: 8px;
            }
        }

        /* Links */
        a,
        a:hover {
            color: #127DB3;
        }

        .footer a,
        .footer a:hover {
            color: #999999;
        }

        .otp-code {
            font-size: 32px;
            font-weight: bold;
            font-family: sans-serif;
            letter-spacing: 8px;
            color: #14377d;
            padding: 20px;
            background-color: #f5f5f5;
            border-radius: 8px;
            display: inline-block;
            margin: 20px 0;
        }
    </style>

    <title>{{ __('email.verification_code') }}</title>

</head>

<!-- BODY -->

<body topmargin="0" rightmargin="0" bottommargin="0" leftmargin="0" marginwidth="0" marginheight="0" width="100%"
    style="border-collapse: collapse; border-spacing: 0;  padding: 0; width: 100%; height: 100%; -webkit-font-smoothing: antialiased; text-size-adjust: 100%; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; line-height: 100%;
	background-color: #F0F0F0;
	color: #000000;"
    bgcolor="#F0F0F0" text="#000000">
    
    @php($emailCompany = email_company_from_data($data ?? []))
<table width="100%" align="center" border="0" cellpadding="0" cellspacing="0"
        style="border-collapse: collapse; border-spacing: 0; margin: 0; padding: 0; width: 100%;" class="background">
        <tr>
            <td align="center" valign="top"
                style="border-collapse: collapse; border-spacing: 0; margin: 0; padding: 32px 16px 32px 16px;" bgcolor="#F0F0F0">
                <table border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#FFFFFF" width="560"
                    style="border-collapse: collapse; border-spacing: 0; padding: 0; width: inherit;
	max-width: 560px;"
                    class="container">

                    <!-- GREETING & BODY -->
                    <tr>
                        <td align="center" valign="top"
                            style="border-collapse: collapse; border-spacing: 0; margin: 0; padding: 0; padding-left: 6.25%; padding-right: 6.25%; width: 87.5%; font-size: 17px; font-weight: 400; line-height: 160%;
			padding-top: 25px; 
			color: #000000;
			font-family: sans-serif;"
                            class="paragraph">
                            {!! render_template($data['template_data']['greeting'], ['name' => $data['name']], $emailCompany) !!}<br>
                            {!! render_template($data['template_data']['body'], ['expires_in_minutes' => $data['expires_in_minutes']], $emailCompany) !!}
                        </td>
                    </tr>

                    <!-- OTP CODE -->
                    <tr>
                        <td align="center" valign="top"
                            style="border-collapse: collapse; border-spacing: 0; margin: 0; padding: 0; padding-left: 6.25%; padding-right: 6.25%; width: 87.5%;
			padding-top: 25px;
			padding-bottom: 25px;">
                            <div class="otp-code">{{ $data['otp_code'] }}</div>
                        </td>
                    </tr>

                    <!-- DIVIDER -->
                    <tr>
                        <td align="center" valign="top"
                            style="border-collapse: collapse; border-spacing: 0; margin: 0; padding: 0; padding-left: 6.25%; padding-right: 6.25%; width: 87.5%;
			padding-top: 25px;"
                            class="line">
                            <hr color="#E0E0E0" align="center" width="100%" size="1" noshade
                                style="margin: 0; padding: 0;" />
                        </td>
                    </tr>

                    <tr>                    <!-- FOOTER -->
                        <td align="center" valign="top"
                            style="border-collapse: collapse; border-spacing: 0; margin: 0; padding: 0; padding-left: 6.25%; padding-right: 6.25%; width: 87.5%; font-size: 17px; font-weight: 400; line-height: 160%;
			padding-top: 4px;
			padding-bottom: 25px;
			color: #000000;
			font-family: sans-serif;"
                            class="paragraph">
                            {!! render_template($data['template_data']['footer'], [
                                'year' => now()->year,
                            ], $emailCompany) !!}
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>

</html>
