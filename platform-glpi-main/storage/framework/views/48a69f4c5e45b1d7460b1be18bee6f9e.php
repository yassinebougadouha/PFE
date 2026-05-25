<script>
  window.SUPPORT_API_BASE_URL = <?php echo json_encode('/api/v1', 15, 512) ?>;
  window.SUPPORT_API_PUBLIC_BASE_URL = <?php echo json_encode(rtrim((string) config('services.support_api.public_url'), '/'), 512) ?>;

  window.supportApiToken = function () {
    return localStorage.getItem('auth_token')
      || localStorage.getItem('access_token')
      || sessionStorage.getItem('auth_token')
      || sessionStorage.getItem('access_token')
      || '';
  };

  window.supportApiUrl = function (path) {
    if (/^https?:\/\//i.test(path)) return path;
    var base = String(window.SUPPORT_API_BASE_URL || '/api/v1').replace(/\/$/, '');
    return base + '/' + String(path || '').replace(/^\//, '');
  };

  window.supportBackendUrl = function (path) {
    if (/^https?:\/\//i.test(path)) return path;
    var base = String(window.SUPPORT_API_PUBLIC_BASE_URL || 'http://localhost:8600/api/v1').replace(/\/$/, '');
    return base + '/' + String(path || '').replace(/^\//, '');
  };

  window.supportBackendFetch = async function (path, opts) {
    opts = opts || {};
    var headers = Object.assign({ 'Accept': 'application/json' }, opts.headers || {});
    var body = opts.body;

    if (body && !(body instanceof FormData) && !headers['Content-Type']) {
      headers['Content-Type'] = 'application/json';
    }

    var res = await fetch(window.supportBackendUrl(path), Object.assign({}, opts, { headers: headers }));
    if (!res.ok) {
      var data = await res.json().catch(function () { return {}; });
      var detail = data.detail || data.message || data.error || res.statusText || ('HTTP ' + res.status);
      if (Array.isArray(detail)) detail = detail.join(', ');
      throw new Error(typeof detail === 'string' ? detail : JSON.stringify(detail));
    }
    if (res.status === 204) return null;
    return res.json();
  };

  window.supportApiFetch = async function (path, opts) {
    opts = opts || {};
    var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var headers = Object.assign({ 'Accept': 'application/json' }, opts.headers || {});
    var token = window.supportApiToken();

    if (token && !headers.Authorization) headers.Authorization = 'Bearer ' + token;
    if (csrf && !headers['X-CSRF-TOKEN']) headers['X-CSRF-TOKEN'] = csrf;
    if (!(opts.body instanceof FormData) && !headers['Content-Type']) {
      headers['Content-Type'] = 'application/json';
    }

    var res = await fetch(window.supportApiUrl(path), Object.assign({}, opts, { headers: headers }));
    if (!res.ok) {
      var data = await res.json().catch(function () { return {}; });
      var detail = data.detail || data.message || data.error || res.statusText || ('HTTP ' + res.status);
      if (Array.isArray(detail)) detail = detail.join(', ');
      throw new Error(typeof detail === 'string' ? detail : JSON.stringify(detail));
    }
    if (res.status === 204) return null;
    return res.json();
  };

  (function () {
    var nativeFetch = window.fetch.bind(window);
    window.fetch = function (input, init) {
      var url = typeof input === 'string' ? input : (input && input.url) || '';
      if (!String(url).startsWith('/api/v1')) {
        return nativeFetch(input, init);
      }

      var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
      var token = window.supportApiToken();
      var nextInit = Object.assign({}, init || {});
      var headers = Object.assign({}, nextInit.headers || {});

      if (csrf && !headers['X-CSRF-TOKEN']) headers['X-CSRF-TOKEN'] = csrf;
      if (token && !headers.Authorization) headers.Authorization = 'Bearer ' + token;
      if (!headers.Accept) headers.Accept = 'application/json';
      nextInit.headers = headers;

      return nativeFetch(input, nextInit);
    };
  })();
</script>
<?php /**PATH /var/www/html/resources/views/partials/support-api.blade.php ENDPATH**/ ?>