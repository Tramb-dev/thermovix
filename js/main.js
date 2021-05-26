// JavaScript Document
jQuery(function(){
	$('.message').delay(20000).animate({color:'#000'}, 2000).fadeOut(1500);
	
	/* COMPTABILIATION DES CONTACTS AFFICHES */
	if($('#caption').text() == 'Contacts'){
		var cpt = $('.contact').length;
		$('#caption').text('Contacts (' + cpt + ')');
	}
	/* FIN COMPTABILIATION DES CONTACTS AFFICHES */
	
	/* FONCTION AUTOCOMPLETE */
	$('#search').autocomplete({
		source: "js/autocomplete.php",
		minLength: 2,
		select: function(event, ui){
			if ($('#' + ui.item.desc).length > 0) {
				$('.contact.selected').removeClass('selected');
				$('#' + ui.item.desc).addClass('selected');
				$('.contenu').scrollTo('#' + ui.item.desc, {
					axis: 'y',
					duration: 500,
					easing: 'easeOutCubic'
				});
			}
 			else {
				modifContact(ui.item.desc);
			}
		}
	});
	/* FIN FONCTION AUTOCOMPLETE */
	
	/* RAFRAICHISSEMENT CHAMP NOTE PAGE CONTACT */
	$('.contact').on('click', function(){
		$('.contact.selected').removeClass('selected');
		$(this).addClass('selected');
		$.post(
			'js/ajax.php',
			{
				note : $(this).attr("id"),
			},
			function(data){
				$('.right-panel').html(data);
				$('.right-panel div span').on('click', function(){
					modifInter($(this).attr('id'));
				});
			},
			'text'
		);
	});
	/* FIN RAFRAICHISSEMENT CHAMP NOTE PAGE CONTACT */
	
	/* RAFRAICHISSEMENT CHAMP COMMENTAIRE PAGE INTERVENTIONS */
	$('.intervention').on('click', function(){
		$('.intervention.selected').removeClass('selected');
		$(this).addClass('selected');
		$.post(
			'js/ajax.php',
			{
				noteInter : $(this).attr("id"),
			},
			function(data){
				$('.right-panel').html(data);
				$('.right-panel div span').on('click', function(){
					modifInter($(this).attr('id'));
				});
			},
			'text'
		);
	});
	/* FIN RAFRAICHISSEMENT CHAMP COMMENTAIRE PAGE INTERVENTIONS */

	
	/* CHOIX COMMENTAIRE, LIVRE OU INTERVENTION DANS PANNEAU NAV CONTACTS*/
	$('#choixNotes').on('click', function(){
		var selected = $('#tableContacts .selected').attr('id');
		$.post(
			'js/ajax.php',
			{
				commentaires : true,
				id : selected
			},
			function(data){
				$('.right-panel').html(data);
			},
			'text'
		);
	});
		
	$('#choixInterventions').on('click', function(){
		var selected = $('#tableContacts .selected').attr('id');
		$.post(
			'js/ajax.php',
			{
				interventions : true,
				id : selected
			},
			function(data){
				$('.right-panel').html(data);
				$('.right-panel div span').on('click', function(){
					modifInter($(this).attr('id'));
				});
			},
			'text'
		);
	});

	$('#choixLivres').on('click', function(){
		var selected = $('#tableContacts .selected').attr('id');
		$.post(
			'js/ajax.php',
			{
				livres : true,
				id : selected
			},
			function(data){
				$('.right-panel').html(data);
			},
			'text'
		);
	});
	/* FIN CHOIX COMMENTAIRE, LIVRE OU INTERVENTION DANS PANNEAU NAV CONTACTS*/

	/* CHOIX COMMENTAIRE, LIVRE OU INTERVENTION DANS PANNEAU NAV INTERVENTIONS*/
	$('#choixCommentaires').on('click', function(){
		var selected = $('#tableInterventions .selected').attr('id');
		$.post(
			'js/ajax.php',
			{
				commentairesInter : true,
				id : selected
			},
			function(data){
				$('.right-panel').html(data);
			},
			'text'
		);
	});
		
	$('#choixResume').on('click', function(){
		var selected = $('#tableInterventions .selected').attr('id');
		$.post(
			'js/ajax.php',
			{
				resume : true,
				id : selected
			},
			function(data){
				$('.right-panel').html(data);
				$('.right-panel div span').on('click', function(){
					modifInter($(this).attr('id'));
				});
			},
			'text'
		);
	});
	/* FIN CHOIX COMMENTAIRE, LIVRE OU INTERVENTION DANS PANNEAU NAV INTERVENTIONS*/
	
	/* GESTION POPUP */
	/* MODIF CONTACT */
	$('#modifContact').on('click', function(){
		modifContact($('#tableContacts .selected').attr('id'));
	});
	$('.contact').on('dblclick', function(){
		modifContact($(this).attr('id'));
	});
	$('#ficheContact').on('click', function(){
		var selected = $('#tableInterventions .selected').attr('data-id');
		$.colorbox({
			href:'src/contact.php?c_id=' + selected,
			width:'90%',
			onComplete:function(){
				ajoutChamp();
				supprimerChamp();
				modifierGroupes();
				$('.datepicker').datepicker();
				// Sécurise le bouton supprimer
				$('input[name="delete"]').on('click', function(){
					$('#formContact').submit(function(event){
						if(!confirm("Etes-vous sûr de vouloir supprimer ce contact ?"))
							event.preventDefault();
					});
				});
				reload();
				ajoutInter(selected);
			}
		});
	});	
	/* FIN MODIF CONTACT */

	// Fermeture de la fenêtre
	var originalClose = $.colorbox.close;
	$.colorbox.close = function(){
		var response;
		if($('#cboxLoadedContent').find('form').length > 0){
			response = confirm('Fermer la fenêtre ?');
			if(!response){
				return;
			}
		}
		originalClose();
	};
	
	
	/* NOUVEAU CONTACT */
	$('#addContact').on('click', function(){
		$(this).colorbox({
			width:'90%',
			height:'90%',
			onComplete:function(){
				ajoutChamp();
				supprimerChamp();
				$('.datepicker').datepicker();
			}
		});
	});	
	/* FIN NOUVEAU CONTACT */

	/* NOUVELLE INTERVENTION */
	$('#newIntervention').on('click', function(){
		var selected = $('#tableContacts .selected').attr('id');
		$.colorbox({
			href:'src/newIntervention.php?id=' + selected,
			onComplete:function(){
				$('.datepicker').datepicker({
					hourMin: 6,
					hourMax: 23
				});
				$('.timepicker').timepicker({
					hourMin: 6,
					hourMax: 23
				});
			}
		});
		$('#formIntervention').formValidation({
            alias       : "name",
            required    : "accept",
            err_list    : true
		});
	});
	$('.contact').mousedown(function(event){
		if(event.which == 3){
			var selected = $(this).attr('id');
			$.colorbox({
				href:'src/newIntervention.php?id=' + selected,
				onComplete:function(){
					$('.datepicker').datepicker({
						hourMin: 6,
						hourMax: 23
					});
					$('.timepicker').timepicker({
						hourMin: 6,
						hourMax: 23
					});
				}
			});
			$('#formIntervention').formValidation({
				alias       : "name",
				required    : "accept",
				err_list    : true
			});
		}
	});	
	/* FIN NOUVELLE INTERVENTION */
	
	/* MODIF INTERVENTION */
	$('#modifInter').on('click', function(){
		modifInter($('#tableInterventions .selected').attr('id'));
	});
	$('.intervention').on('dblclick', function(){
		modifInter($(this).attr('id'));
	});
	/* FIN MODIF INTERVENTION */
	
	/* FIN GESTION POPUP */
		
	
	/* TRI CONTACTS PAR GROUPES */
	$('#triAll').on('click', function(){
		$(':checkbox').prop('checked', true);
	});
	
	$('#triNone').on('click', function(){
		$(':checkbox').prop('checked', false);
	});
	/* FIN TRI CONTACTS PAR GROUPES */
	
	/* MODIFICATION GROUPE */
	$('form#modif_groupes span').on('click', function(){
		var gr_id = $(this).children('input').attr('value');
		var name = $(this).children('input').data('name');
		$(this).replaceWith('<span><input type="text" value="' + name + '" size="18" name="' + gr_id + '" /><input type="submit" name="modif" value="Ok" /></span>')
	});
	
	// Sécurisation de la suppression du groupe
	$('#delete_group input[name="delete"]').on('click', function(){
		$('#delete_group').submit(function(event){
			if(!confirm("Etes-vous sûr de vouloir supprimer ce groupe ?"))
				event.preventDefault();
		});
	});
	/* FIN MODIFICATION GROUPE */
	
});

// Lance la modification d'un contact
function modifContact(selected){
	$.colorbox({
		href:'src/contact.php?c_id=' + selected,
		width:'90%',
		height:'90%',
		onComplete:function(){
			ajoutChamp();
			supprimerChamp();
			modifierGroupes();
			ajoutInter(selected);
			$('.datepicker').datepicker();
			// Sécurise le bouton supprimer
			$('input[name="delete"]').on('click', function(){
				$('#formContact').submit(function(event){
					if(!confirm("Etes-vous sûr de vouloir supprimer ce contact ?"))
						event.preventDefault();
				});
			});
			reload();
		}
	});
}

// Lance la modification d'une intervention
function modifInter(selected){
	$.colorbox({
		href:'src/modif_intervention.php?id=' + selected,
		onComplete:function(){
			ajoutChamp();
			$('.datepicker').datepicker();
		}
	});
}

// Permet d'ajouter les champs email, téléphone et adresse pour les popups contact
function ajoutChamp(){
	$('#ajoutTel').on('click', function(){
		var valeur = $(this).attr('data-valeur');
		$(this).attr('data-valeur', parseInt(valeur,10)+1);
		$(this).before('<div class="ligneTel"><input type="text" size="8" name="lTel-'+valeur+'" value=""><input type="tel" size="12" name="tel-'+valeur+'" value=""><br></div>');
	});
	$('#ajoutEmail').on('click', function(){
		var valeur = $(this).attr('data-valeur');
		$(this).attr('data-valeur', parseInt(valeur,10)+1);
		$(this).before('<div class="ligneEmail"><input type="text" size="8" name="lEmail-'+valeur+'" value=""><input type="email" name="email-'+valeur+'" value="" size="25"><br></div>');
	});
	$('#ajoutAdresse').on('click', function(){
		var valeur = $(this).attr('data-valeur');
		$(this).attr('data-valeur', parseInt(valeur,10)+1);
		$(this).before('<div class="ligneAdresse"><input type="text" class="inputarea" size="8" name="lAdresse-'+valeur+'" value=""><div><textarea name="rue-'+valeur+'"></textarea><br><span class="postcode">CP : </span><input class="inputarea" type="text" size="5" name="postcode-'+valeur+'"><input class="inputarea" type="text" size="12" name="ville-'+valeur+'"></div><br></div>');
	});
}

// Supprime la ligne d'infos correspondante. Différence entre un champ qui vient d'être créé (pas encore en base) et un champ issu de la base
function supprimerChamp(){
	$('.delete').on('click', function(){
			$(this).parent('div').remove();
	});
}

// Ajoute une intervention pour le contact en cours de modification
function ajoutInter(selected){
	$('#addInter').on('click', function(){
		$.colorbox({
			href:'src/newIntervention.php?id=' + selected,
			onComplete:function(){
				$('.datepicker').datepicker({
					hourMin: 6,
					hourMax: 23
				});
				$('.timepicker').timepicker({
					hourMin: 6,
					hourMax: 23
				});
			}
		});
		$('#formIntervention').formValidation({
            alias       : "name",
            required    : "accept",
            err_list    : true
		});
	});
}

// Permet de réessayer de chercher l'adresse du contact
function reload(){
	$('.reload').on('click', function(){
		var selected = $(this).data('id');
		$.post(
			'js/ajax.php',
			{
				reload : selected
			},
			function(data){
				if(data == true)
					$('.reload[data-id="' + selected + '"]').replaceWith('<img class="locate" src="./img/home.png" width="17px" height="17px" />');
				else
					alert('Adresse non trouvée. Erreur : ' + data);
			},
			'text'
		);
	});
}

// Permet l'apparition des groupes pour la modification dans la popup contact
function modifierGroupes(){
 	$('#modifGroupes').on('click', function(){
		$('#modifGroupes').remove();
		$('#selectGroupes').css('display', 'initial');
		$('#resize').resizable({
			minHeight: 119,
			minWidth: 212
		});
		$('#resize').change(function(){
			var str = '';
			$('#selectGroupes option:selected').each(function(){
				str += '<span class="contactGroupe">' + $(this).text() + '</span>'
			});
			$('#groupes').html('Groupes : ' + str);
		}).change();
	});
 }

/* French initialisation for the jQuery UI date picker plugin. */
/* Written by Stéphane Nahmani (sholby@sholby.net). */
jQuery(function($) {
	$.datepicker.regional['fr'] = {
			renderer: $.ui.datepicker.defaultRenderer,
			monthNames: ['Janvier','Février','Mars','Avril','Mai','Juin',
			'Juillet','Août','Septembre','Octobre','Novembre','Décembre'],
			monthNamesShort: ['Jan','Fév','Mar','Avr','Mai','Jun',
			'Jul','Aoû','Sep','Oct','Nov','Déc'],
			dayNames: ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'],
			dayNamesShort: ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'],
			dayNamesMin: ['Di','Lu','Ma','Me','Je','Ve','Sa'],
			dateFormat: 'dd/mm/yy',
			firstDay: 1,
			prevText: '&#x3c;Préc', prevStatus: 'Voir le mois précédent',
			prevJumpText: '&#x3c;&#x3c;', prevJumpStatus: 'Voir l\'année précédent',
			nextText: 'Suiv&#x3e;', nextStatus: 'Voir le mois suivant',
			nextJumpText: '&#x3e;&#x3e;', nextJumpStatus: 'Voir l\'année suivant',
			currentText: 'Courant', currentStatus: 'Voir le mois courant',
			todayText: 'Aujourd\'hui', todayStatus: 'Voir aujourd\'hui',
			clearText: 'Effacer', clearStatus: 'Effacer la date sélectionnée',
			closeText: 'Fermer', closeStatus: 'Fermer sans modifier',
			yearStatus: 'Voir une autre année', monthStatus: 'Voir un autre mois',
			weekText: 'Sm', weekStatus: 'Semaine de l\'année',
			dayStatus: '\'Choisir\' le DD d MM',
			defaultStatus: 'Choisir la date',
			isRTL: false,
			numberOfMonths: 3,
			showButtonPanel: true
	};
	$.datepicker.setDefaults($.datepicker.regional['fr']);
});

jQuery(function($) {
	$.timepicker.regional['fr'] = {
	timeOnlyTitle: 'Choisir une heure',
	timeText: 'Heure',
	hourText: 'Heures',
	minuteText: 'Minutes',
	secondText: 'Secondes',
	millisecText: 'Millisecondes',
	timezoneText: 'Fuseau horaire',
	currentText: 'Maintenant',
	closeText: 'Terminé',
	amNames: ['AM', 'A'],
	pmNames: ['PM', 'P'],
	ampm: false
	};
	$.timepicker.setDefaults($.timepicker.regional['fr']);
});

