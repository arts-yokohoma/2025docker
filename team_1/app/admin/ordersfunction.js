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
    // Use server-provided date (Asia/Tokyo) so filter matches PHP counts
    const targetStr = btn.getAttribute('data-target-date') || '';
    if (!targetStr) {
      rows.forEach(row => { row.style.display = 'table-row'; });
      return;
    }

    let visibleCount = 0;
    rows.forEach((row) => {
      const rowDateStr = (row.getAttribute('data-date') || '').trim();
      const match = rowDateStr.match(/(\d{4}-\d{2}-\d{2})/);
      const rowDatePart = match ? match[1] : '';
      if (rowDatePart === targetStr) {
        row.style.display = 'table-row';
        visibleCount++;
      } else {
        row.style.display = 'none';
      }
    });
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
