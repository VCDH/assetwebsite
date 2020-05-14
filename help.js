/*
	scenariobrowser - viewer en editor voor verkeersmanagementscenario's
	assetwebsite - viewer en aanvraagformulier voor verkeersmanagementassets
    Copyright (C) 2016-2020 Gemeente Den Haag, Netherlands
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

$(document).ready( function() {
	//help function
	$('#help').click( function(event) {
		event.preventDefault();
		if ($('#dialog').length == 0) {
			$('html').append('<div id="helpdialog"></div>');
		}
		$('#helpdialog').html('');
		$('#helpdialog').dialog({
			autoOpen: false,
			title: 'laden...',
			height: 'auto',
			width: Math.max(($(window).width() - 980), 400),
			height: $(window).height(),
			position: { my: "left top", at: "left top", of: window }
		});
		$("#helpdialog").parent().css({position : "fixed"}).end().dialog('open');
		$.get($(this).attr('href'), {h: $(this).attr('rel')})
		.done (function(data) {
			$('#helpdialog').html(data);
			$('div#helpdialogcontent').attr('style', 'margin-right: 16px');
		})
		.fail( function() {
			$('#helpdialog').html('Kan helptekst niet laden');
		})
		.always( function() {
			$('#helpdialog').dialog('option', 'title', 'Help');
		});
		$('#helpdialog').dialog({
			close: function( event, ui ) {
				$('div#helpdialogcontent').removeAttr('style');
			}
		});
	});
	//close message
    $('.closeparent').click( function() {
        $(this).parent().remove();
    });
});