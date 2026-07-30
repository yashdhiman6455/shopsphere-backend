<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f7f7f7; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; }
        .header { background: #4f46e5; padding: 30px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; }
        .body { padding: 30px; color: #374151; line-height: 1.6; }
        .body h2 { color: #1a1a2e; margin-top: 0; }
        .btn { display: inline-block; background: #4f46e5; color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 6px; font-weight: 600; margin: 20px 0; }
        .footer { background: #f3f4f6; padding: 20px; text-align: center; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to ShopSphere!</h1>
        </div>
        <div class="body">
            <h2>Hi {{ $user->name }},</h2>
            <p>We're thrilled to have you join ShopSphere! Your account has been created successfully.</p>
            <p>Start exploring our wide range of products and enjoy a seamless shopping experience.</p>
            <p style="text-align: center;">
                <a href="{{ config('app.url', 'http://localhost:5173') }}/products" class="btn">Start Shopping</a>
            </p>
            <p>If you have any questions, feel free to reach out to our support team.</p>
            <p>Happy Shopping!<br><strong>The ShopSphere Team</strong></p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} ShopSphere. All rights reserved.
        </div>
    </div>
</body>
</html>
