var gmapsDraggable = function (id) {
	var e_map = document.getElementById(id + '_gmap');
	var e_lat = document.getElementById(id + '_x');
	var e_lng = document.getElementById(id + '_y');
	var e_zoom = document.getElementById(id + '_z');
	var e_search = document.getElementById(id + '_s');

	var myLatlng = new google.maps.LatLng(e_lat.value, e_lng.value);
	var zoom = parseInt(e_zoom.value, 10);

	var myOptions = {
		zoom: isNaN(zoom) ? 1 : zoom,
		center: myLatlng,
		mapTypeId: google.maps.MapTypeId.ROADMAP
	};
	var map = new google.maps.Map(e_map, myOptions);
	var marker = new google.maps.Marker({
		position: myLatlng,
		map: map,
		draggable:true
    });
    var geocoder = new google.maps.Geocoder();

	//Events
	google.maps.event.addListener(marker, 'drag', function() {
		e_lat.value = marker.position.lat();
		e_lng.value = marker.position.lng();
	});
	google.maps.event.addListener(map, 'zoom_changed', function() {
		e_zoom.value = map.getZoom();
	});
	e_lat.onkeyup = function () {
		var currLatLng = new google.maps.LatLng(this.value, marker.position.lng());
		marker.setPosition(currLatLng);
		map.setCenter(currLatLng);
	}
	e_lng.onkeyup = function () {
		var currLatLng = new google.maps.LatLng(marker.position.lat(), this.value);
		marker.setPosition(currLatLng);
		map.setCenter(currLatLng);
	}
	e_search.onclick = function () {
		var address = prompt('Address to search');

		if (!address) {
			return false;
		}

		geocoder.geocode({'address': address}, function(results, status) {
			if (status == google.maps.GeocoderStatus.OK) {
				marker.setPosition(results[0].geometry.location);
				map.setCenter(results[0].geometry.location);
				e_lat.value = marker.position.lat();
				e_lng.value = marker.position.lng();
			} else if (status == google.maps.GeocoderStatus.ZERO_RESULTS) {
				alert("No results have been found");
			} else {
				alert("There was the following error searching the address: " + status);
			}
		});
	}
}