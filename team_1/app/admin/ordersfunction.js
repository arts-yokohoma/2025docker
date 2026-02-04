function filterByStatus(btn) {
  const status = btn.getAttribute('data-status');
  const dateFilter = btn.getAttribute('data-date');
  // Get all rows and buttons
  const rows = document.querySelectorAll('tbody tr');
  const buttons = document.querySelectorAll('.tab-btn');

  // Remove active class from all buttons
  buttons.forEach(b => b.classList.remove('active'));
  // Add active class to clicked button
  btn.classList.add('active');

  if (dateFilter) {
    const now = new Date();
    let target = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    if (dateFilter === 'tomorrow') target.setDate(target.getDate() + 1);
    else if (dateFilter === 'dayafter') target.setDate(target.getDate() + 2);

    const y = target.getFullYear();
    const m = String(target.getMonth() + 1).padStart(2, '0');
    const d = String(target.getDate()).padStart(2, '0');
    const targetStr = `${y}-${m}-${d}`;
    console.log('Filtering by date:', dateFilter, '=>', targetStr);

    let visibleCount = 0;
    rows.forEach((row, idx) => {
      const rowDateStr = (row.getAttribute('data-date') || '').trim();
      // Extract YYYY-MM-DD robustly (handles 'YYYY-MM-DD', 'YYYY-MM-DD HH:MM', 'YYYY-MM-DDTHH:MM:SS')
      const m = rowDateStr.match(/(\d{4}-\d{2}-\d{2})/);
      const rowDatePart = m ? m[1] : '';
      console.debug(`Row ${idx}: data-date="${rowDateStr}" -> datePart="${rowDatePart}" (target=${targetStr})`);
      if (rowDatePart === targetStr) {
        row.style.display = 'table-row';
        visibleCount++;
      } else {
        row.style.display = 'none';
      }
    });

    console.log('Date filter visible rows:', visibleCount);
    if (visibleCount === 0) console.warn('Date filter matched 0 rows - check `data-date` attributes and delivery_time values.');
    return;
  }

  // Status filter (including 'all')
  rows.forEach(row => {
    const rowStatus = row.getAttribute('data-status');
    if (status === 'all' || rowStatus === status) {
      row.style.display = 'table-row';
    } else {
      row.style.display = 'none';
    }
  });
}

document.addEventListener('DOMContentLoaded', function() {
  // Cancel button handler
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('cancel-btn')) {
      e.preventDefault();
      const orderId = e.target.getAttribute('data-id');
      const orderStatus = e.target.getAttribute('data-status');
      
      let confirmMessage = '';
      
      // Different messages based on status
      if (orderStatus === 'New') {
        confirmMessage = 'この新規注文をキャンセルしますか？';
      } else if (orderStatus === 'In Progress') {
        confirmMessage = '⚠️ 注意：調理中の注文です。キャンセルしてもよろしいですか？キャンセルすると調理が中止されます。';
      } else {
        confirmMessage = 'この注文をキャンセルしてもよろしいですか？';
      }
      
      if (confirm(confirmMessage)) {
        fetch('delete_order.php?id=' + orderId, {
          method: 'GET'
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('注文がキャンセルされました');
            location.reload();
          } else {
            alert('キャンセルに失敗しました: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('エラー: ' + error.message);
        });
      }
    }
  });

  // Status button handler
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('status-btn')) {
      e.preventDefault();
      const orderId = e.target.getAttribute('data-id');
      const nextStatus = e.target.getAttribute('data-next');
      
      fetch('update_order_status.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'id=' + orderId + '&status=' + encodeURIComponent(nextStatus)
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          location.reload();
        } else {
          alert('更新に失敗しました: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('エラー: ' + error.message);
      });
    }
  });

  // Edit button handler
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('edit-btn')) {
      e.preventDefault();
      const orderId = e.target.getAttribute('data-id');
      window.location.href = 'edit_order.php?id=' + orderId;
    }
  });
});
