<script>
function showNotification(type, message) {
    const toastEl = document.getElementById(type + 'Toast');
    const messageEl = document.getElementById(type + 'Message');
    if (!toastEl || !messageEl) return;
    messageEl.textContent = message;
    const toast = new bootstrap.Toast(toastEl, { autohide: true, delay: 5000 });
    toast.show();
    toastEl.style.transform = 'translateX(100%)';
    setTimeout(() => {
        toastEl.style.transition = 'transform 0.3s ease-in-out';
        toastEl.style.transform = 'translateX(0)';
    }, 100);
}

@if(session('success'))
  document.addEventListener('DOMContentLoaded', function() {
    showNotification('success', @json(session('success')));
  });
@endif
@if(session('error'))
  document.addEventListener('DOMContentLoaded', function() {
    showNotification('error', @json(session('error')));
  });
@endif
@if(session('info'))
  document.addEventListener('DOMContentLoaded', function() {
    showNotification('info', @json(session('info')));
  });
@endif
</script>
