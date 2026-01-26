document.addEventListener('DOMContentLoaded', function () {
  function statusClass(name) {
    return name.toLowerCase().replace(/\s+/g, '-');
  }

  function updateRow(button, nextStatus) {
    const row = button.closest('tr');
    const statusSpan = row.querySelector('.status');
    // update text
    statusSpan.textContent = nextStatus;
    // update class
    // remove any existing status-* classes except 'status'
    statusSpan.className = 'status ' + statusClass(nextStatus);

    // update date cell to current datetime (real-time)
    const dateCell = row.querySelectorAll('td')[1];
    if (dateCell) {
      const now = new Date();
      const pad = (n) => String(n).padStart(2, '0');
      const formatted = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())} ${pad(now.getHours())}:${pad(now.getMinutes())}`;
      dateCell.textContent = formatted;
    }

    // update button appearance and behavior
    if (nextStatus === 'In Progress') {
      button.textContent = '完了にする';
      button.dataset.next = 'Completed';
      button.classList.remove('blue');
      button.classList.add('yellow');
    } else if (nextStatus === 'Completed') {
      const newBtn = document.createElement('button');
      newBtn.className = 'btn gray';
      newBtn.textContent = '顧客リクエスト';
      newBtn.disabled = true;
      button.replaceWith(newBtn);
    }
  }

  // Status button click handler
  document.querySelectorAll('.status-btn').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      const button = e.currentTarget;
      const next = button.dataset.next;
      if (!next) return;

      const orderId = button.dataset.id;
      
      // Save to server
      fetch('api.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'update_status', id: orderId, status: next})
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Update UI after successful save
          updateRow(button, next);
        } else {
          alert('Error updating order: ' + (data.error || 'Unknown error'));
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Error updating order');
      });
    });
  });

  // Delete button click handler
  document.querySelectorAll('.delete-btn').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      const button = e.currentTarget;
      const orderId = button.dataset.id;
      if (confirm(`注文 #${orderId} を削除しますか？`)) {
        const row = button.closest('tr');
        row.style.opacity = '0.5';
        button.disabled = true;
        button.textContent = '削除中...';
        
        fetch('api.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({action: 'delete', id: orderId})
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            row.remove();
          } else {
            alert('Error deleting order: ' + (data.error || 'Unknown error'));
            row.style.opacity = '1';
            button.disabled = false;
            button.textContent = '−';
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error deleting order');
          row.style.opacity = '1';
          button.disabled = false;
          button.textContent = '−';
        });
      }
    });
  });

  // Edit button click handler
  document.querySelectorAll('.edit-btn').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      const button = e.currentTarget;
      const orderId = button.dataset.id;
      alert(`注文 #${orderId} を編集します`);
      // TODO: Redirect to edit page or open modal
      // window.location.href = `edit_order.php?id=${orderId}`;
    });
  });
});

