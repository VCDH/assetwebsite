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
function initMiniMap() {
    var minimapposition = [$('#latitude').val(), $('#longitude').val()];

	var minimap = L.map('minimap').setView(minimapposition, 13);

	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
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

$(document).ready( function() {
    //get json content
    $.getJSON($(this).attr('href'), { data: 'json' })
    .done (function(tabledata) {
        //create Tabulator on DOM element with id "example-table"
        var table = new Tabulator('#assettable', {
            data: tabledata, //assign data to table
            layout: 'fitDataFill', //fit columns to width of table (optional)
            columns: [ //Define Table Columns
                { title: 'type', field: 'assettypename' },
                { title: 'code', field: 'code' },
                { title: 'naam', field: 'naam' },
                { title: 'status', field: 'status' },
                { title: 'aansturing', field: 'aansturing' },
                { title: 'wegbeheerder', field: 'wegbeheerder' },
                { title: 'onderhoud', field: 'onderhoud' },
                { title: 'voeding', field: 'voeding' },
                { title: 'verbinding', field: 'verbinding' },
                { title: 'leverancier', field: 'leverancier' },
                { title: 'bouwjaar', field: 'bouwjaar' },
                { title: 'oorspr. bj', field: 'oorspronkelijk_bouwjaar' },
            ],
            /*persistence:{
                sort:true,
                //filter:true,
                //columns:true,
            },
            persistenceID:"assettable",*/
            rowClick: function(e, row) {
                openDetailsWindow(row._row.data.assetid);
            }
        });
        //filter functions
        var filterField = document.getElementById("table-filter");
        var assettypeField = document.getElementById("table-asset-filter");
        function updateFilter(){
            var filterArray = [
                {field: 'code', type:'like', value: filterField.value},
                {field: 'naam', type:'like', value: filterField.value},
                {field: 'status', type:'like', value: filterField.value},
                {field: 'aansturing', type:'like', value: filterField.value},
                {field: 'wegbeheerder', type:'like', value: filterField.value},
                {field: 'onderhoud', type:'like', value: filterField.value},
                {field: 'voeding', type:'like', value: filterField.value},
                {field: 'verbinding', type:'like', value: filterField.value},
                {field: 'leverancier', type:'like', value: filterField.value},
                {field: 'bouwjaar', type:'like', value: filterField.value},
                {field: 'oorspronkelijk_bouwjaar', type:'like', value: filterField.value}
            ];
            var assetFilter = {field: 'assettypename', type:'like', value: assettypeField.value};

            if ((filterField.value) && (assettypeField.value)) {
                table.setFilter([assetFilter, filterArray]);
            }
            else if (filterField.value) {
                table.setFilter([filterArray]);
            }
            else if (assettypeField.value) {
                table.setFilter([assetFilter]);
            }
            else {
                table.clearFilter();
            }
        }
        filterField.addEventListener("keyup", updateFilter);
        assettypeField.addEventListener("change", updateFilter);
    })
    .fail (function() {
        $('#assettable').html('Kan tabelgegevens niet laden.');
    });
    //open success id link
    $('#updatesuccessassetid').click( function() {
        openDetailsWindow($(this).html());
    });
});

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
    $.getJSON($(this).attr('href'), { data: 'details', id: id } )
    .done (function(json) {
        $('#detailsdialog').html(json.html);
        $('#detailsdialog').dialog('option', 'title', json.title);
        //open map
        initMiniMap();
    })
    .fail( function() {
        $('#detailsdialog').html('Kan gegevens niet laden');
        $('#detailsdialog').dialog('option', 'title', 'Fout');
    });
}