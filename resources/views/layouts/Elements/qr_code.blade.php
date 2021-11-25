<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.css"/>
<script src="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.js"></script>

<div class="container-fluid">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
  <div class="text-center">
    <a data-fancybox="gallery"
       href="https://api.qrserver.com/v1/create-qr-code/?data=http%3A%2F%2Fwww.google.com&size=240x240">
      <img src="https://api.qrserver.com/v1/create-qr-code/?data=http%3A%2F%2Fwww.google.com&size=120x120"
           class="qr-code img-thumbnail img-responsive"></a>
  </div>
</div>
<script>
  let baseURL = 'https://api.qrserver.com/v1/create-qr-code/?data=';
  let config = '&size=120x120';

  let qrCode, content;

  function htmlEncode(value) {
    return $('<div/>').text(value).html();
  }

  $(function () {
    btn = $('#generate');
    qrCode = $('.qr-code');
    content = '{{ $text }}';
    qrCode.attr('src', baseURL + encodeURIComponent(htmlEncode(content)) + config);
  });
</script>