<div id="map" style="width: 100%; height: 400px;"></div>
<script>
  var mr_lat = {{ $lat }};
  var mr_lon = {{ $lon }};

  function initMap() {
    var uluru = {lat: mr_lat, lng: mr_lon};
    var map = new google.maps.Map(
        document.getElementById('map'), {zoom: 10, center: uluru});
    var marker = new google.maps.Marker({position: uluru, map: map});
  }
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCR-J-RVvzRnGhEjtMPS9UhkTYkPa3YtJU&callback=initMap"></script>