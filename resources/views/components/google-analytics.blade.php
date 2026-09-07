@php
    $gaId = config('services.google.analytics_id', 'G-52RCD5KV68');
@endphp
@if(!empty($gaId))
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
<script>
  try {
    const consent = JSON.parse(localStorage.getItem('vops_cookie_consent') || '{}');
    if (consent.analytics === false) {
      window['ga-disable-{{ $gaId }}'] = true;
    }
  } catch(e) {}

  window.addEventListener('cookie-consent-updated', function(e) {
    if (e.detail) {
      window['ga-disable-{{ $gaId }}'] = (e.detail.analytics === false);
    }
  });

  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', '{{ $gaId }}');
</script>
@endif
