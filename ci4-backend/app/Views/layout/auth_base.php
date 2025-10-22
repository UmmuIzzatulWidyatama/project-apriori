<script>
  if (typeof window.apiFetch !== 'function') {
    async function apiFetch(url, options = {}) {
      options.headers = Object.assign(
        { 'X-Requested-With': 'XMLHttpRequest' },
        options.headers || {}
      );
      return fetch(url, options);
    }
  }
</script>
