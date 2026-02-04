FROM_MAIL = "Acme <onboarding@resend.dev>"
# FORGOT_PASS_HTML = "<strong>あなたのパスワードリセットコードは: {reset_code}</strong>"
FORGOT_PASS_HTML = """
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<style>
  body {{
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    background-color: #f9f9f9;
    margin: 0;
    padding: 0;
  }}
  .email-container {{
    max-width: 500px;
    background-color: #ffffff;
    margin: 30px auto;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
  }}
  h2 {{
    color: #333;
  }}
  p {{
    font-size: 16px;
    color: #555;
  }}
  .reset-code {{
    display: inline-block;
    margin: 20px 0;
    padding: 10px 20px;
    font-size: 20px;
    font-weight: bold;
    color: #ffffff;
    background-color: #ff6600;
    border-radius: 6px;
    letter-spacing: 2px;
  }}
</style>
</head>
<body>
  <div class="email-container">
    <h2>パスワードリセット</h2>
    <p>あなたのパスワードリセットコードは以下です：</p>
    <div class="reset-code">{reset_code}</div>
    <p>このコードは5分間有効です。</p>
  </div>
</body>
</html>
"""

FORGOT_PASS_TEMPLATE = {
    "from": FROM_MAIL,
    "subject": "パスワードリセットコード",
    "html": FORGOT_PASS_HTML,
}

ORDER_HTML = """
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<style>
  body {{
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    background-color: #f9f9f9;
    margin: 0;
    padding: 0;
  }}
  .email-container {{
    max-width: 600px;
    background-color: #ffffff;
    margin: 20px auto;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 20px 30px;
  }}
  h2 {{
    color: #333;
    border-bottom: 2px solid #ff6600;
    padding-bottom: 8px;
  }}
  p {{
    color: #444;
    line-height: 1.6;
    font-size: 15px;
  }}
  ul {{
    list-style: none;
    padding: 0;
  }}
  ul li {{
    padding: 8px 0;
    border-bottom: 1px solid #eee;
  }}
  .total {{
    font-weight: bold;
    color: #ff6600;
    font-size: 16px;
  }}
  hr {{
    margin: 25px 0;
    border: none;
    border-top: 1px solid #ddd;
  }}
  a {{
    color: #ff6600;
    text-decoration: none;
  }}
  .footer {{
    font-size: 13px;
    color: #777;
    margin-top: 20px;
  }}
</style>
</head>

<body>
  <div class="email-container">
    <h2>{customer_name} 様</h2>

    <p>この度は <strong>Pizza Shop</strong> をご利用いただき、誠にありがとうございます。</p>

    <p>ご注文番号：<strong>{order_code}</strong></p>
    <p>配達：<strong>{address}</strong></p>

    <p>ご注文内容：</p>
    <ul>
      {order_items}
    </ul>

    <p class="total">合計金額：{total_price:,} 円</p>
    <p>現在の注文ステータス：<b>{order_status}</b></p>

    <hr>

    <p>ご不明な点がございましたら、以下までお問い合わせください。</p>
    <p>電話番号：<a href="tel:0909xxxxxx">0909xxxxxx</a></p>
    <p>店の住所：アツカレッジヨコハマ</p>

    <div class="footer">
      <p>今後とも <strong>Pizza Shop</strong> をよろしくお願いいたします。<br>
      Pizza Shop チームより</p>
    </div>
  </div>
</body>
</html>
"""


ORDER_MAIL_TEMPLATE = {
    "from": FROM_MAIL,
    "subject": "【Pizza Shop】ご注文確認",
    "html": ORDER_HTML,
}


ORDER_UPDATE_STATUS_HTML = """
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<style>
  body {{
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    background-color: #f9f9f9;
    margin: 0;
    padding: 0;
  }}
  .email-container {{
    max-width: 600px;
    background-color: #ffffff;
    margin: 20px auto;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 20px 30px;
  }}
  h2 {{
    color: #333;
    border-bottom: 2px solid #ff6600;
    padding-bottom: 8px;
  }}
  p {{
    color: #444;
    line-height: 1.6;
    font-size: 15px;
  }}
  hr {{
    margin: 25px 0;
    border: none;
    border-top: 1px solid #ddd;
  }}
  a {{
    color: #ff6600;
    text-decoration: none;
  }}
  .footer {{
    font-size: 13px;
    color: #777;
    margin-top: 20px;
  }}
</style>
</head>

<body>
  <div class="email-container">
    <h2>{customer_name} 様</h2>

    <p>この度は <strong>Pizza Shop</strong> をご利用いただき、誠にありがとうございます。</p>

    <p>ご注文番号：<strong>{order_code}</strong></p>

    <p>現在の注文ステータス：<b>{order_status}</b></p>

    <hr>

    <p>ご不明な点がございましたら、以下までお問い合わせください。</p>
    <p>電話番号：<a href="tel:0909xxxxxx">0909xxxxxx</a></p>

    <div class="footer">
      <p>今後とも <strong>Pizza Shop</strong> をよろしくお願いいたします。<br>
      Pizza Shop チームより</p>
    </div>
  </div>
</body>
</html>
"""

UPDATE_STATUS_TEMPLATE = {
    "from": FROM_MAIL,
    "subject": "注文状態変更",
    "html": ORDER_UPDATE_STATUS_HTML,
}