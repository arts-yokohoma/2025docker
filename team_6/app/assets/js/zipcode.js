
// 郵便番号入力イベント
document.getElementById('zipcode').addEventListener('input', async (e) => {
  const zip = e.target.value.replace(/\D/g, '');
  if (zip.length !== 7) return; // 日本の郵便番号は7桁

  const status = document.getElementById('zip-status');
  status.textContent = '住所検索中...';

  try {
    const res = await fetch(`https://zipcloud.ibsnet.co.jp/api/search?zipcode=${zip}`);
    const data = await res.json();

    if (data && data.results && data.results.length) {
      const r = data.results[0];

      // 住所フィールドに自動入力
      document.getElementById('pref').value = r.address1 || '';
      document.getElementById('city').value = r.address2 || '';
      document.getElementById('addr').value = r.address3 || '';

      // PHPに渡す結合住所
      document.getElementById('address').value =
        `${r.address1}${r.address2}${r.address3}`;

      status.textContent = '✅ 自動入力しました';
    } else {
      status.textContent = '⚠️ 該当する住所が見つかりません';
    }
  } catch (err) {
    console.error('郵便番号検索エラー:', err);
    status.textContent = '❌ 検索エラーが発生しました';
  }

  // 数秒後にメッセージを消す
  setTimeout(() => (status.textContent = ''), 4000);
});

// 手動入力変更時も hidden address 更新
['pref', 'city', 'addr'].forEach(id => {
  document.getElementById(id).addEventListener('input', () => {
    document.getElementById('address').value =
      `${document.getElementById('pref').value}${document.getElementById('city').value}${document.getElementById('addr').value}`;
  });
});