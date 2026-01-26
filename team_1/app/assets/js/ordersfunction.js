function filterByStatus(btn) {
  const status = btn.getAttribute('data-status');
  console.log('Button clicked, filtering by:', status);
  
  // Get all rows and buttons
  const rows = document.querySelectorAll('tbody tr');
  const buttons = document.querySelectorAll('.tab-btn');
  
  console.log('Total rows:', rows.length);
  
  // Remove active class from all buttons
  buttons.forEach(b => {
    b.classList.remove('active');
  });
  
  // Add active class to clicked button
  btn.classList.add('active');
  console.log('Active button set');
  
  // Filter and show/hide rows
  rows.forEach(row => {
    const rowStatus = row.getAttribute('data-status');
    console.log('Row status:', rowStatus, 'Filter:', status);
    
    if (status === 'all') {
      // Show all rows
      row.style.display = 'table-row';
      console.log('Showing row (all)');
    } else if (rowStatus === status) {
      // Show matching rows
      row.style.display = 'table-row';
      console.log('Showing row (match)');
    } else {
      // Hide non-matching rows
      row.style.display = 'none';
      console.log('Hiding row');
    }
  });
  
  console.log('Filter complete');
}

document.addEventListener('DOMContentLoaded', function() {
  // Delete button handler
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('delete-btn')) {
      e.preventDefault();
      const orderId = e.target.getAttribute('data-id');
      
      if (confirm('この注文を削除してもよろしいですか？')) {
        fetch('delete_order.php?id=' + orderId, {
          method: 'GET'
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            e.target.closest('tr').remove();
            alert('注文が削除されました');
          } else {
            alert('削除に失敗しました: ' + data.message);
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
