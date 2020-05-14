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

/*
* Initialize global variables
*/
var map;
var selectedMapStyle = 'map-style-lighter';
var selectedTileLayer = 5;
var tileLayers = [
	{
		name: 'BRT Achtergrondkaart',
		layer: L.tileLayer('https://geodata.nationaalgeoregister.nl/tiles/service/wmts/brtachtergrondkaart/EPSG:3857/{z}/{x}/{y}.png', {
			minZoom: 6,
			maxZoom: 19,
			bounds: [[50.5, 3.25], [54, 7.6]],
			attribution: 'Kaartgegevens &copy; <a href="https://www.kadaster.nl">Kadaster</a> | <a href="https://www.verbeterdekaart.nl">Verbeter de kaart</a>'
		})
	},
	{
		name: 'Luchtfoto',
		layer: L.tileLayer('https://geodata.nationaalgeoregister.nl/luchtfoto/rgb/wmts/2018_ortho25/EPSG:3857/{z}/{x}/{y}.png', {
			minZoom: 6,
			maxZoom: 19,
			bounds: [[50.5, 3.25], [54, 7.6]],
			attribution: 'Kaartgegevens &copy; <a href="https://www.kadaster.nl">Kadaster</a>'
		})
	},
	{
		name: 'OpenStreetMap',
		layer: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
		})
	},
	{
		name: 'Thunderforest Transport',
		layer: L.tileLayer('https://tile.thunderforest.com/transport/{z}/{x}/{y}.png?apikey=423cd178822a4d178e961233ebb95dcf', {
			attribution: 'Maps &copy; <a href="http://www.thunderforest.com">Thunderforest</a>, Data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>'
		})
	},
	{
		name: 'Thunderforest Buurten',
		layer: L.tileLayer('https://tile.thunderforest.com/neighbourhood/{z}/{x}/{y}.png?apikey=423cd178822a4d178e961233ebb95dcf', {
			attribution: 'Maps &copy; <a href="http://www.thunderforest.com">Thunderforest</a>, Data &copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>'
		})
	}
];
var onloadCookie;
var markers = {};
var maplayers = {};
var table;
var selectedMarkerIds = [];
var layer = 1; //TODO dit veronderstelt dat DRIP layer laag 1 is, wat zo is in default install

/*
* Initialize the map on page load
*/
function initMap() {
	map = L.map('map');
	//set map position from cookie, if any
	if ((typeof onloadCookie !== 'undefined') && ($.isNumeric(onloadCookie[1]))) {
		//get and use center and zoom from cookie
		map.setView(onloadCookie[0], onloadCookie[1]);
		//get map style from cookie
        setMapStyle(onloadCookie[2]);
        //set tile layer from cookie
        selectedTileLayer = onloadCookie[4];
	}
	else {
		//set initial map view
		map.setView([51.9918383,4.2139163],10);
    }
	//add tile layer
	setMapTileLayer(selectedTileLayer);
	//modify some map controls
	map.zoomControl.setPosition('topleft');
    L.control.scale().addTo(map);
    //store map position and zoom in cookie
	map.on('load moveend', function() {
		loadMarkers(layer);
    });
}

/*
* Set the map style and store it in the cookie
*/
function setMapStyle(style_id) {
	if ((style_id == 'map-style-grayscale') || (style_id == 'map-style-lighter') || (style_id == 'map-style-dark') || (style_id == 'map-style-oldskool') || (style_id == 'map-style-cycle')) {
		selectedMapStyle = style_id;
	}
	else {
		selectedMapStyle = 'map-style-default';
	}
}

/*
* Set the map tileset
*/
function setMapTileLayer(tile_id) {
	for (var i = 0; i < tileLayers.length; i++) {
		if (i == tile_id) {
			map.addLayer(tileLayers[i].layer);
		}
		else {
			map.removeLayer(tileLayers[i].layer);
		}
	}
	selectedTileLayer = tile_id;
	updateMapStyle();
}

/*
* Apply or remove a CSS style when the user changes the map style or the map
*/
function updateMapStyle() {
	$('img.leaflet-tile').removeClass('map-style-grayscale');
	$('img.leaflet-tile').removeClass('map-style-lighter');
	$('img.leaflet-tile').removeClass('map-style-dark');
	$('img.leaflet-tile').removeClass('map-style-oldskool');
	//map recolor
	if ((selectedMapStyle == 'map-style-grayscale') || (selectedMapStyle == 'map-style-lighter') ||  (selectedMapStyle == 'map-style-dark') || (selectedMapStyle == 'map-style-oldskool')) {
		$('img.leaflet-tile').addClass(selectedMapStyle);
	}
}

/*
* Load/update markers for map layer
*/
function loadMarkers(layer) {
	//check if layer has entry in makers object and add it if not
	if (!markers.hasOwnProperty(layer)) {
		markers[layer] = [];
	}
	//draw new markers if they are not already drawn
	var visibleMarkerIds = [];
	//$.getJSON('maplayer.php', { layer: layer, bounds: map.getBounds().toBBoxString(), filter: getCurrentlySelectedFilters() })
	$.getJSON('maplayer.php', { layer: layer, bounds: map.getBounds().toBBoxString() })
	.done( function(json) {
		$.each(json, function(index, v) {
			visibleMarkerIds.push(v.id);
			//find if marker is already drawn
			var markerfound = false;
			for (var i = 0; i < markers[layer].length; i++) {
				if (markers[layer][i].options.x_id == v.id) {
					markerfound = true;
					break;
				}
			}
			//add new marker
			if (markerfound == false) {
                //decide icon class
                if (selectedMarkerIds.indexOf(parseInt(v.id)) === -1) {
                    var iconClassName = 'L-icon-trans';
                }
                else {
                    var iconClassName = 'L-icon-def';
                }
                var marker = L.marker([v.lat, v.lon], {
					x_id: v.id,
					icon: L.icon({	iconUrl: 'image.php?t=' + layer + '&w=' + v.icon + '&i=' + v.itype, iconSize: [16,16], className: iconClassName }),
					//zIndexOffset: ((layer == 2) ? 1000: 0), //TODO manage this from database, this assumes layer 2 is CAM layer, which is the case in the default install
					rotationAngle: v.heading,
					rotationOrigin: 'center',
					title: v.code
				}).addTo(map);
				marker.on('click', function(e) {
					changeSelection(v.id);
				});
				markers[layer].push(marker);
			}
		});

		//remove markers that should not be drawn (both out of bound and as a result of filtering)
		for (var i = markers[layer].length - 1; i >= 0; i--) {
			if (visibleMarkerIds.indexOf(markers[layer][i].options.x_id) === -1) {
				markers[layer][i].remove();
				markers[layer].splice(i, 1);
				
			}
		}
	});
}

function changeSelection(id) {
    id = parseInt(id);
    var marker;
    //find marker
    for (var i = 0; i < markers[layer].length; i++) {
        if (markers[layer][i].options.x_id == id) {
            marker = markers[layer][i];
            var icon = marker.getIcon();
            break;
        }
    }
    //find row id
    var rowId = table.searchRows("assetid", "=", id);
    //check if selected
    if (selectedMarkerIds.indexOf(id) === -1) {
        //not selected
        //add to selection
        selectedMarkerIds.push(id);
        //change marker icon class to visible
        //only if icon is in view
        if (marker !== undefined) {
            icon.options.className = 'L-icon-def';
            marker.setIcon(icon);
        }
        //add to table selection
        table.selectRow(rowId);
    }
    else {
        //selected
        //remove from selection
        index = selectedMarkerIds.findIndex(Mid => Mid === id);
        selectedMarkerIds.splice(index, 1);
        //change marker icon class to transparent
        if (marker !== undefined) {
            icon.options.className = 'L-icon-trans';
            marker.setIcon(icon);
        }
        //remove from table selection
        table.deselectRow(rowId);
    }
    console.log(selectedMarkerIds);
}

/*
* document.ready
*/
$(document).ready( function() {
    //get json content
    $.getJSON($(this).attr('href'), { data: 'json' })
    .done (function(tabledata) {
        //create Tabulator on DOM element with id "example-table"
        table = new Tabulator('#assettable', {
            selectable: true,
            data: tabledata, //assign data to table
            layout: 'fitColumns', //fit columns to width of table (optional)
            columns: [ //Define Table Columns
                { title: 'code', field: 'code', widthGrow: 1 },
                { title: 'status', field: 'status', width: 100 },
                { title: 'aansturing', field: 'aansturing', width: 150 },
            ],
            /*persistence:{
                sort:true,
                //filter:true,
                //columns:true,
            },
            persistenceID:"assettable",*/
            rowClick: function(e, row) {
                changeSelection(row._row.data.assetid);
            }
        });
        //filter functions
        var filterField = document.getElementById("table-filter");
        function updateFilter(){
            var filterArray = [
                {field: 'code', type:'like', value: filterField.value},
                {field: 'status', type:'like', value: filterField.value},
                {field: 'aansturing', type:'like', value: filterField.value}
            ];
            if (filterField.value) {
                table.setFilter([filterArray]);
            }
            else {
                table.clearFilter();
            }
        }
        filterField.addEventListener("keyup", updateFilter);
        /*
        * init map
        */
       onloadCookie = Cookies.getJSON('assetwebsite_map');
       initMap();
       loadMarkers(layer);
    })
    .fail (function() {
        $('#assettable').html('Kan tabelgegevens niet laden.');
    });
    //download link
    $('#aanvraagformulier-download').click( function() {
        //check if any selection
        if (selectedMarkerIds.length <= 0) {
			alert('Selecteer de DRIPs die moeten worden opgenomen in het aanvraagformulier.');
		}
        else {
            window.open('docx.php?s=' + JSON.stringify(selectedMarkerIds));
        }
    });
});