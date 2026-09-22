function confirmDelete(id) {
  document.getElementById('del-id').value = id;
  document.getElementById('del-modal-bg').classList.add('show');
}

// Show a popup (with OK button) after Add / Update / Delete / Column changes,
// instead of the old green text banner that was easy to miss.
document.addEventListener('DOMContentLoaded', function () {
  var msg = document.body.getAttribute('data-flash-msg');
  var ok = document.body.getAttribute('data-flash-ok');
  if (msg) {
    var titleEl = document.getElementById('flash-modal-title');
    var textEl = document.getElementById('flash-modal-text');
    titleEl.textContent = (ok === '1') ? '✅ Done' : '⚠️ Problem';
    textEl.textContent = msg;
    document.getElementById('flash-modal-bg').classList.add('show');

    // Remove msg/ok from the address bar so refreshing the page doesn't
    // re-show the same popup.
    var url = new URL(window.location.href);
    url.searchParams.delete('msg');
    url.searchParams.delete('ok');
    window.history.replaceState({}, '', url.toString());
  }
});
