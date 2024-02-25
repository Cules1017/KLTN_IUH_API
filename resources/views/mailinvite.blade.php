<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8">
    <style>
        /* Thiết lập các quy tắc CSS */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #333333;
        }
        .message {
            margin-top: 20px;
            font-size: 16px;
            line-height: 1.6;
            color: #666666;
        }
        .button {
            display: inline-block;
            background-color: #007bff;
            color: #ffffff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 3px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Thư Mời Làm Việc Từ {{$company_name}} - UPWORK</h2>
        <div class="message">
            {!! $message_mail !!}
        </div>
    </div>
</body>
</html>
