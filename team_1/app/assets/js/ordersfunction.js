// Filter functionality
document.addEventListener('DOMContentLoaded', function() {
  console.log('Orders function loaded');
  
  // Filter tabs handler
  const filterTabs = document.querySelectorAll('.filter-tab');
  filterTabs.forEach(tab => {
    tab.addEventListener('click', function() {
      const filterValue = this.getAttribute('data-filter');
      console.log('Filter clicked:', filterValue);
      
      // Update active tab
      filterTabs.forEach(t => t.classList.remove('active'));
      this.classList.add('active');
      
      // Filter table rows
      const rows = document.querySelectorAll('tbody tr');
      rows.forEach(row => {
        if (filterValue === 'all') {
          row.classList.add('visible');
        } else {
          const status = row.getAttribute('data-status');
          if (status === filterValue) {
            row.classList.add('visible');
          } else {
            row.classList.remove('visible');
          }
        }
      });
    });
  });
  
  // Delete button functionality
  // Delete button handler
  document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const orderId = this.getAttribute('data-id');
      console.log('Delete clicked for order:', orderId);
      if (confirm('この注文を削除してもよろしいですか？')) {
        // Send delete request to server
        fetch('delete_order.php?id=' + orderId, {
          method: 'GET'
        })
        .then(response => {
          console.log('Delete response status:', response.status);
          return response.json();
        })
        .then(data => {
          console.log('Delete response data:', data);
          if (data.success) {
            // Remove the row from the table
            this.closest('tr').remove();
            alert('注文が削除されました');
          } else {
            alert('削除に失敗しました: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Delete error:', error);
          alert('削除エラー: ' + error.message);
        });
      }
    });
  });

  // Status button handler (調理開始, 完了にする)
  document.querySelectorAll('.status-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const orderId = this.getAttribute('data-id');
      const nextStatus = this.getAttribute('data-next');
      console.log('Status clicked for order:', orderId, 'new status:', nextStatus);
      
      fetch('update_order_status.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'id=' + orderId + '&status=' + encodeURIComponent(nextStatus)
      })
      .then(response => {
        console.log('Status response status:', response.status);
        return response.json();
      })
      .then(data => {
        console.log('Status response data:', data);
        if (data.success) {
          // Reload the page to show updated status
          location.reload();
        } else {
          alert('ステータス更新に失敗しました: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Status update error:', error);
        alert('ステータス更新エラー: ' + error.message);
      });
    });
  });

  // Edit button handler
  document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const orderId = this.getAttribute('data-id');
      console.log('Edit clicked for order:', orderId);
      // Redirect to edit page or open modal
      window.location.href = 'edit_order.php?id=' + orderId;
    });
  });
});
