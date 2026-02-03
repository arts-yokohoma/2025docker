<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>ユーザー作成</title>
<link rel="stylesheet" href="css/add_user.css">
</head>
<body>

<div class="wrap">

  <!-- ボックス内・右上の戻る -->
  <a href="user.php" class="back-link">← 戻る</a>

  <h1>ユーザー作成</h1>
  <div class="sub">新しいシステムユーザーを登録し、適切な権限を割り当てます。</div>

  <div class="row">
  <div class="col">
    <label>ログイン</label>
    <input placeholder="例:tanaka01">
  </div>

  <div class="col col-pw">
    <label>password</label>
    <div class="pw-wrap">
      <input type="password" id="pw" placeholder="英数字8文字以上">
      <span class="pw-eye"
        onclick="pw.type=pw.type=='password'?'text':'password'">👁</span>
    </div>
  </div>
</div>

<div class="row">
  <div class="col">
    <label>名前</label>
    <input placeholder="例:Yamada Taro">
  </div>

  <div class="col">
    <label>電話番号</label>
    <input placeholder="例:070-1234-5678">
  </div>
</div>


  <div style="margin-top:20px">
    <label>役割 (Role)</label>
      <div class="roles">
      <div class="role">👤 マネージャー<input type="radio" name="r"></div> <br>
      <div class="role">🚚 ドライバー<input type="radio" name="r"></div><br>
      <div class="role">🍴 キッチンスタッフ<input type="radio" name="r"></div><br>
    </div>
  </div>
<div class="btns">
  <button>キャンセル</button>
  <button class="primary">追加</button>
    </div>
  </div>
</body>
</html>