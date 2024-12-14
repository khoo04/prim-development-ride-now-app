<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Redirecting to Payment Gateway...</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f4f4f4;
            font-family: Arial, sans-serif;
        }
        .container {
            text-align: center;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-radius: 50%;
            border-top: 4px solid #3498db;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .message {
            margin-top: 20px;
            font-size: 18px;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner"></div>
        <div class="message">Redirecting to Payment Gateway...</div>
        <form name="form1" id="form1" method="post" action="https://directpay.my/fpx/pay">
            @csrf
            <input type="hidden" value="{{ $fpx_buyerName }}" name="BuyerName">
            <input type="hidden" value="{{ $fpx_buyerEmail }}" name="BuyerEmail">
            <input type="hidden" value="{{ $private_key }}" name="PrivateKey">
            <input type="hidden" value="{{ $fpx_txnAmount }}" name="Amount">
            <input type="hidden" value="{{ $fpx_sellerExOrderNo }}" name="SellerOrderNo">
        </form>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.getElementById("form1").submit();
        });
    </script>
</body>
</html>
