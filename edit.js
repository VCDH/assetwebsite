/*
 	assetwebsite - viewer en aanvraagformulier voor verkeersmanagementassets
    Copyright (C) 2020 Gemeente Den Haag, Netherlands
    Developed by Jasper Vries
 
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

//init map
var map;
var marker;
var position = [52,5];
function initMiniMap() {
	map = L.map('minimap').setView(position, 13);

	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		maxZoom: 19,
		attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
	}).addTo(map);

	marker = L.marker(position, {
		draggable: true, 
		rotationAngle: 0,
		rotationOrigin: 'center',
		icon: L.icon({	
			iconUrl: 'image.php?t=' + $('#assettype').val() + '&w=0', 
			iconSize: [16,16], 
			className: 'L-icon-def' }),
	}).addTo(map);
}

//set marker position from field values
function setNewPosition() {
	position = [$('input[name=latitude]').val(), $('input[name=longitude]').val()];
	marker.setLatLng(position);
	map.setView(position);
}

$( document ).ready(function() {
	initMiniMap();
	//set marker position
	setNewPosition();
	marker.setRotationAngle($('input[name=heading]').val());

	//change latitude
	$('input[name=latitude]').change( function() {
		setNewPosition();
		//set_map_cookie();
	});
	//change longitude
	$('input[name=longitude]').change( function() {
		setNewPosition();
		//set_map_cookie();
	});
	
	//map click sets new marker position
	map.on('click', function(e) {
		$('input[name=latitude]').val(e.latlng.lat);
		$('input[name=longitude]').val(e.latlng.lng);
		setNewPosition();
	});
	//marker drag also sets new position
	marker.on('dragend', function() {
		$('input[name=latitude]').val(marker.getLatLng().lat);
		$('input[name=longitude]').val(marker.getLatLng().lng);
	});

	//change rotation
	$('input[name=heading]').change( function() {
		marker.setRotationAngle($('input[name=heading]').val());
	});



	/*
	//map position from cookie, if any and no other value set
	if (($('input#latitude').val() == '') && (typeof(Cookies.get('asset_map')) !== 'undefined')) {
		var cookievalues = Cookies.getJSON('asset_map');
		position = cookievalues[0];
		$('input#latitude').val(position.lat);
		$('input#longitude').val(position.lng);
	}
	//no cookie
	else {
		position = new google.maps.LatLng(parseFloat($('input#latitude').val()), parseFloat($('input#longitude').val()));
	}
	*/
});
