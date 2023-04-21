/*
    assetwebsite - viewer en aanvraagformulier voor verkeersmanagementassets
    Copyright (C) 2020-2022 Gemeente Den Haag, Netherlands
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
* details window of asset
*/
function openDetailsWindow(id) {
    if ($('#detailsdialog').length == 0) {
        $('html').append('<div id="detailsdialog"></div>');
    }
    $('#detailsdialog').html('');
    $('#detailsdialog').dialog({
        autoOpen: false,
        title: 'laden...',
        height: 'auto',
        width: $(window).width() - 60,
        height: $(window).height() - 60,
        position: { my: 'center', at: 'center', of: window }
    });
    $("#detailsdialog").parent().css({position : 'fixed'}).end().dialog('open');
    $.getJSON('tabel.php', { data: 'details', id: id } )
    .done (function(json) {
        $('#detailsdialog').html(json.html);
		//init tabs
		$('#details-tabs').tabs();
        $('#detailsdialog').dialog('option', 'title', json.title);
        //open map
        initMiniMap();
    })
    .fail( function() {
        $('#detailsdialog').html('Kan gegevens niet laden');
        $('#detailsdialog').dialog('option', 'title', 'Fout');
    });
}

/* minimap for details window*/
function initMiniMap() {
    var minimapposition = [$('#latitude').val(), $('#longitude').val()];

	var minimap = L.map('minimap').setView(minimapposition, 13);

	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(minimap);
    
    L.marker(minimapposition, {
		draggable: true, 
		rotationAngle: $('#heading').val(),
		rotationOrigin: 'center',
		icon: L.icon({	
			iconUrl: 'image.php?t=' + $('#assettype').val() + '&w=' + $('#aansturing').val(), 
			iconSize: [16,16], 
			className: 'L-icon-def' }),
	}).addTo(minimap);
}
