/*
 	scenariobrowser - viewer en editor voor verkeersmanagementscenario's
    Copyright (C) 2016-2019 Gemeente Den Haag, Netherlands
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
var named_categories = {
	scn: 'Scenario\'s',
	wvk: 'Wegvakken',
	hmp: 'Hectometerposities'
};

$.widget( "custom.catcomplete", $.ui.autocomplete, {
	_create: function() {
		this._super();
		this.widget().menu( "option", "items", "> :not(.ui-autocomplete-category)" );
	},
	_renderMenu: function( ul, items ) {
		var that = this,
		currentCategory = "";
		$.each( items, function( index, item ) {
			var li;
			if ( item.category != currentCategory ) {
				ul.append( "<li class='ui-autocomplete-category'>" + named_categories[item.category] + "</li>" );
				currentCategory = item.category;
			}
			li = that._renderItemData( ul, item );
			if ( item.category ) {
				li.attr( "aria-label", item.category + " : " + item.label );
			}
			if ((item.category == 'hmp') && (typeof item.id == 'undefined')) {
				li.addClass('notfound');
			}
		});
	}
});

$(document).ready( function() {
	$( "#searchbox" ).catcomplete({
		source: 'searchresults.php',
		minLength: 2,
		select: function( event, ui ) {
			if (ui.item.category == 'scn') {
				var href = 'scenario.php?id=' + ui.item.id;
			}
			else if (ui.item.category == 'wvk') {
				var href = 'index.php?lookat=' + ui.item.id;
			}
			else if (ui.item.category == 'hmp') {
				if (typeof ui.item.id !== 'undefined') {
					var href = 'index.php?lookat=' + ui.item.id + '&latlng=' + ui.item.latlng;
				}
				else {
					var href = 'index.php?latlng=' + ui.item.latlng;
				}
			}
			else {
				$('#searchbox').catcomplete('close');
				return false;
			}
			history.pushState({}, document.title, window.location.href);
			window.location.replace(href);
		}
	})
	.catcomplete( 'instance' )._renderItem = function( ul, item ) {
		if (item.category == 'scn') {
			var scenario_types = {w : 'Werkzaamheden', e : 'Evenement', i : 'Ongeval', f : 'File', t : 'Tunnel', b : 'Brug', a : 'Algemeen'};
			var scenario_ernsten = {d : 'Dicht (incl. omleiden)', h : 'Hinder (incl. omleiden)', i : 'Informeren'};
			var	scenario_types_afbeeldingen = {w : 'werk.png', e : 'evenement.png', i : 'ongeval.png', f : 'file.png', t : 'tunnel.png', b : 'brug.png', a : 'algemeen.png'};
			var scenario_ernsten_afbeeldingen = {d : 'dicht.png', h : 'omleiden.png', i : 'informeren.png'};
			return $( '<li>' )
				.append( '<img src="images/' + scenario_types_afbeeldingen[item.type] + '" class="te" width="16" height="16" alt="' + scenario_types[item.type] + '" title="' + scenario_types[item.type] + '"><img src="images/' + scenario_ernsten_afbeeldingen[item.ernst] + '" class="te" width="16" height="16" alt="' + scenario_ernsten[item.type] + '" title="' + scenario_ernsten[item.type] + '"> ' + item.label )
				.appendTo( ul );
		}
		else {
			return $( '<li>' )
				.append( item.label )
				.appendTo( ul );
		}
    };
});