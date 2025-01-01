<?php

if(! function_exists("string_plural_select_fr")) {
function string_plural_select_fr($n){
	$n = intval($n);
	if (($n == 0 || $n == 1)) { return 0; } else if ($n != 0 && $n % 1000000 == 0) { return 1; } else  { return 2; }
}}
$a->strings['Unable to locate original post.'] = 'Impossible de localiser la publication originale.';
$a->strings['Post updated.'] = 'Publication mise à jour.';
$a->strings['Item wasn\'t stored.'] = 'La publication n\'a pas été enregistrée.';
$a->strings['Item couldn\'t be fetched.'] = 'La publication n\'a pas pu être récupérée.';
$a->strings['Empty post discarded.'] = 'Publication vide rejetée.';
$a->strings['Item not found.'] = 'Élément introuvable.';
$a->strings['Permission denied.'] = 'Permission refusée.';
$a->strings['No valid account found.'] = 'Impossible de trouver un compte valide.';
$a->strings['Password reset request issued. Check your email.'] = 'Réinitialisation du mot de passe en cours. Vérifiez vos courriels.';
$a->strings['
		Dear %1$s,
			A request was recently received at "%2$s" to reset your account
		password. In order to confirm this request, please select the verification link
		below or paste it into your web browser address bar.

		If you did NOT request this change, please DO NOT follow the link
		provided and ignore and/or delete this email, the request will expire shortly.

		Your password will not be changed unless we can verify that you
		issued this request.'] = '
		Cher/Chère %1$s,
			Une demande vient d\'être faite depuis "%2$s" pour réinitialiser votre mot de passe de compte. 
		Afin de confirmer cette demande, merci de sélectionner le lien ci-dessous 
		et de le coller dans la barre d\'adresse de votre navigateur.

		Si vous n\'avez PAS fait cette demande de changement, merci de NE PAS suivre le lien
		ci-dessous et d\'ignorer et/ou supprimer ce message. La demande expirera rapidement.

		Votre mot de passe ne changera pas tant que nous n\'avons pas vérifié que vous êtes à l\'origine de la demande.';
$a->strings['
		Follow this link soon to verify your identity:

		%1$s

		You will then receive a follow-up message containing the new password.
		You may change that password from your account settings page after logging in.

		The login details are as follows:

		Site Location:	%2$s
		Login Name:	%3$s'] = '
		Suivez ce lien pour confirmer votre identité :

		%1$s

		Vous recevrez alors un message contenant votre nouveau mot de passe.
		Vous pourrez changer ce mot de passe depuis les paramètres de votre compte une fois connecté.

		Informations de connexion :

		Adresse :	%2$s
		Identifiant :	%3$s';
$a->strings['Password reset requested at %s'] = 'Demande de réinitialisation de mot de passe depuis %s';
$a->strings['Request could not be verified. (You may have previously submitted it.) Password reset failed.'] = 'La requête n\'a pas pu être vérifiée. (Vous l\'avez peut-être déjà précédemment envoyée.) La réinitialisation du mot de passe a échoué.';
$a->strings['Request has expired, please make a new one.'] = 'La requête a expiré, veuillez la renouveler.';
$a->strings['Forgot your Password?'] = 'Mot de passe oublié ?';
$a->strings['Enter your email address and submit to have your password reset. Then check your email for further instructions.'] = 'Entrez votre adresse de courriel et validez pour réinitialiser votre mot de passe. Vous recevrez la suite des instructions par courriel.';
$a->strings['Nickname or Email: '] = 'Pseudo ou Courriel : ';
$a->strings['Reset'] = 'Réinitialiser';
$a->strings['Password Reset'] = 'Réinitialiser le mot de passe';
$a->strings['Your password has been reset as requested.'] = 'Votre mot de passe a bien été réinitialisé.';
$a->strings['Your new password is'] = 'Votre nouveau mot de passe est ';
$a->strings['Save or copy your new password - and then'] = 'Sauvez ou copiez ce nouveau mot de passe - puis';
$a->strings['click here to login'] = 'cliquez ici pour vous connecter';
$a->strings['Your password may be changed from the <em>Settings</em> page after successful login.'] = 'Votre mot de passe peut être changé depuis la page &lt;em&gt;Réglages&lt;/em&gt;, une fois que vous serez connecté.';
$a->strings['Your password has been reset.'] = 'Votre mot de passe a été réinitialisé.';
$a->strings['
			Dear %1$s,
				Your password has been changed as requested. Please retain this
			information for your records (or change your password immediately to
			something that you will remember).
		'] = '
				Chère/Cher %1$s,
					Votre mot de passe a été changé ainsi que vous l’avez demandé. Veuillez conserver cette informations dans vos archives (ou changer immédiatement votre mot de passe pour un autre dont vous vous souviendrez).
			';
$a->strings['
			Your login details are as follows:

			Site Location:	%1$s
			Login Name:	%2$s
			Password:	%3$s

			You may change that password from your account settings page after logging in.
		'] = '
				Voici vos informations de connexion :

				Adresse :	%1$s
				Identifiant :	%2$s
				Mot de passe :	%3$s

				Vous pourrez changer votre mot de passe dans les paramètres de votre compte une fois connecté.
			';
$a->strings['Your password has been changed at %s'] = 'Votre mot de passe a été modifié à %s';
$a->strings['New Message'] = 'Nouveau message';
$a->strings['No recipient selected.'] = 'Pas de destinataire sélectionné.';
$a->strings['Unable to locate contact information.'] = 'Impossible de localiser les informations du contact.';
$a->strings['Message could not be sent.'] = 'Impossible d\'envoyer le message.';
$a->strings['Message collection failure.'] = 'Récupération des messages infructueuse.';
$a->strings['Discard'] = 'Rejeter';
$a->strings['Messages'] = 'Messages';
$a->strings['Conversation not found.'] = 'Conversation inconnue.';
$a->strings['Message was not deleted.'] = 'Le message n\'a pas été supprimé.';
$a->strings['Conversation was not removed.'] = 'La conversation n\'a pas été supprimée.';
$a->strings['Please enter a link URL:'] = 'Entrez un lien web :';
$a->strings['Send Private Message'] = 'Envoyer un message privé';
$a->strings['To:'] = 'À:';
$a->strings['Subject:'] = 'Sujet:';
$a->strings['Your message:'] = 'Votre message :';
$a->strings['Upload photo'] = 'Joindre photo';
$a->strings['Insert web link'] = 'Insérer lien web';
$a->strings['Please wait'] = 'Patientez';
$a->strings['Submit'] = 'Envoyer';
$a->strings['No messages.'] = 'Aucun message.';
$a->strings['Message not available.'] = 'Message indisponible.';
$a->strings['Delete message'] = 'Effacer message';
$a->strings['D, d M Y - g:i A'] = 'D, d M Y - g:i A';
$a->strings['Delete conversation'] = 'Effacer conversation';
$a->strings['No secure communications available. You <strong>may</strong> be able to respond from the sender\'s profile page.'] = 'Pas de communications sécurisées possibles. Vous serez <strong>peut-être</strong> en mesure de répondre depuis la page de profil de l\'émetteur.';
$a->strings['Send Reply'] = 'Répondre';
$a->strings['Unknown sender - %s'] = 'Émetteur inconnu - %s';
$a->strings['You and %s'] = 'Vous et %s';
$a->strings['%s and You'] = '%s et vous';
$a->strings['%d message'] = [
	0 => '%d message',
	1 => '%d messages',
	2 => '%d messages',
];
$a->strings['Personal Notes'] = 'Notes personnelles';
$a->strings['Personal notes are visible only by yourself.'] = 'Les notes personnelles ne sont visibles que par vous.';
$a->strings['Save'] = 'Sauver';
$a->strings['User not found.'] = 'Utilisateur introuvable.';
$a->strings['Photo Albums'] = 'Albums photo';
$a->strings['Recent Photos'] = 'Photos récentes';
$a->strings['Upload New Photos'] = 'Téléverser de nouvelles photos';
$a->strings['everybody'] = 'tout le monde';
$a->strings['Contact information unavailable'] = 'Informations de contact indisponibles';
$a->strings['Album not found.'] = 'Album introuvable.';
$a->strings['Album successfully deleted'] = 'Album bien supprimé';
$a->strings['Album was empty.'] = 'L\'album était vide';
$a->strings['Failed to delete the photo.'] = 'La suppression de la photo a échoué.';
$a->strings['a photo'] = 'une photo';
$a->strings['%1$s was tagged in %2$s by %3$s'] = '%1$s a été mentionné(e) dans %2$s par %3$s';
$a->strings['Public access denied.'] = 'Accès public refusé.';
$a->strings['No photos selected'] = 'Aucune photo sélectionnée';
$a->strings['The maximum accepted image size is %s'] = 'La taille maximum d\'image autorisée est de %s';
$a->strings['Upload Photos'] = 'Téléverser des photos';
$a->strings['New album name: '] = 'Nom du nouvel album : ';
$a->strings['or select existing album:'] = 'ou sélectionner un album existant';
$a->strings['Do not show a status post for this upload'] = 'Ne pas publier de notice de statut pour cet envoi';
$a->strings['Permissions'] = 'Permissions';
$a->strings['Do you really want to delete this photo album and all its photos?'] = 'Voulez-vous vraiment supprimer cet album photo et toutes ses photos ?';
$a->strings['Delete Album'] = 'Effacer l\'album';
$a->strings['Cancel'] = 'Annuler';
$a->strings['Edit Album'] = 'Éditer l\'album';
$a->strings['Drop Album'] = 'Supprimer l\'album';
$a->strings['Show Newest First'] = 'Plus récent d\'abord';
$a->strings['Show Oldest First'] = 'Plus ancien d\'abord';
$a->strings['View Photo'] = 'Voir la photo';
$a->strings['Permission denied. Access to this item may be restricted.'] = 'Interdit. L\'accès à cet élément peut avoir été restreint.';
$a->strings['Photo not available'] = 'Photo indisponible';
$a->strings['Do you really want to delete this photo?'] = 'Voulez-vous vraiment supprimer cette photo ?';
$a->strings['Delete Photo'] = 'Effacer la photo';
$a->strings['View photo'] = 'Voir photo';
$a->strings['Edit photo'] = 'Éditer la photo';
$a->strings['Delete photo'] = 'Effacer la photo';
$a->strings['Use as profile photo'] = 'Utiliser comme photo de profil';
$a->strings['Private Photo'] = 'Photo privée';
$a->strings['View Full Size'] = 'Voir en taille réelle';
$a->strings['Tags: '] = 'Étiquettes :';
$a->strings['[Select tags to remove]'] = '[Sélectionner les étiquettes à supprimer]';
$a->strings['New album name'] = 'Nom du nouvel album';
$a->strings['Caption'] = 'Titre';
$a->strings['Add a Tag'] = 'Ajouter une étiquette';
$a->strings['Example: @bob, @Barbara_Jensen, @jim@example.com, #California, #camping'] = 'Exemples : @bob, @Barbara_Jensen, @jim@example.com, #Californie, #vacances';
$a->strings['Do not rotate'] = 'Pas de rotation';
$a->strings['Rotate CW (right)'] = 'Tourner dans le sens des aiguilles d\'une montre (vers la droite)';
$a->strings['Rotate CCW (left)'] = 'Tourner dans le sens contraire des aiguilles d\'une montre (vers la gauche)';
$a->strings['This is you'] = 'C\'est vous';
$a->strings['Comment'] = 'Commenter';
$a->strings['Preview'] = 'Aperçu';
$a->strings['Loading...'] = 'Chargement en cours...';
$a->strings['Select'] = 'Sélectionner';
$a->strings['Delete'] = 'Supprimer';
$a->strings['Like'] = 'Aime';
$a->strings['I like this (toggle)'] = 'J\'aime';
$a->strings['Dislike'] = 'N\'aime pas';
$a->strings['I don\'t like this (toggle)'] = 'Je n\'aime pas';
$a->strings['Map'] = 'Carte';
$a->strings['Apologies but the website is unavailable at the moment.'] = 'Désolé mais le site web n\'est pas disponible pour le moment.';
$a->strings['Delete this item?'] = 'Effacer cet élément?';
$a->strings['Block this author? They won\'t be able to follow you nor see your public posts, and you won\'t be able to see their posts and their notifications.'] = 'Bloquer cet auteur ? Il ne pourra pas s\'abonner à votre compte et vous ne pourrez pas voir ses publications, ni ses commentaires.';
$a->strings['Ignore this author? You won\'t be able to see their posts and their notifications.'] = 'Ignorer cet auteur ? Vous ne serez plus en mesure de voir ses publications et notifications.';
$a->strings['Collapse this author\'s posts?'] = 'Réduire les publications de cet auteur ?';
$a->strings['Ignore this author\'s server?'] = 'Ignorer le serveur de cet auteur ?';
$a->strings['You won\'t see any content from this server including reshares in your Network page, the community pages and individual conversations.'] = 'Vous ne verrez aucun contenu provenant de ce serveur, y compris les partages dans votre page Réseau, les pages de la communauté et les conversations individuelles.';
$a->strings['Like not successful'] = 'Erreur lors du "Aime"';
$a->strings['Dislike not successful'] = 'Erreur lors du "N\'aime pas"';
$a->strings['Sharing not successful'] = 'Erreur lors du "Partager"';
$a->strings['Attendance unsuccessful'] = 'Erreur lors du "Participer"';
$a->strings['Backend error'] = 'Erreur backend';
$a->strings['Network error'] = 'Erreur réseau';
$a->strings['Drop files here to upload'] = 'Déposer des fichiers ici pour les envoyer';
$a->strings['Your browser does not support drag and drop file uploads.'] = 'Votre navigateur ne supporte pas l\'envoi de fichier par glisser-déposer.';
$a->strings['Please use the fallback form below to upload your files like in the olden days.'] = 'Veuillez utiliser le formulaire ci-dessous pour envoyer vos fichiers comme au bon vieux temps.';
$a->strings['File is too big ({{filesize}}MiB). Max filesize: {{maxFilesize}}MiB.'] = 'Fichier trop volumineux ({{filesize}}Mio). Taille maximum : {{maxFilesize}}Mio.';
$a->strings['You can\'t upload files of this type.'] = 'Vous ne pouvez pas envoyer des fichiers de ce type.';
$a->strings['Server responded with {{statusCode}} code.'] = 'Le serveur a répondu avec un code {{statusCode}}.';
$a->strings['Cancel upload'] = 'Annuler l\'envoi';
$a->strings['Upload canceled.'] = 'Envoi annulé.';
$a->strings['Are you sure you want to cancel this upload?'] = 'Êtes-vous sûr de vouloir annuler cet envoi ?';
$a->strings['Remove file'] = 'Supprimer le fichier';
$a->strings['You can\'t upload any more files.'] = 'Vous ne pouvez plus envoyer de fichiers.';
$a->strings['toggle mobile'] = 'activ. mobile';
$a->strings['Method not allowed for this module. Allowed method(s): %s'] = 'Méthode non autorisée pour ce module. Méthode(s) autorisée(s): %s';
$a->strings['Page not found.'] = 'Page introuvable.';
$a->strings['You must be logged in to use addons. '] = 'Vous devez être connecté pour utiliser les extensions.';
$a->strings['No system theme config value set.'] = 'Le thème système n\'est pas configuré.';
$a->strings['The form security token was not correct. This probably happened because the form has been opened for too long (>3 hours) before submitting it.'] = 'Le jeton de sécurité du formulaire n\'est pas correct. Ceci veut probablement dire que le formulaire est resté ouvert trop longtemps (plus de 3 heures) avant d\'être validé.';
$a->strings['All contacts'] = 'Tous les contacts';
$a->strings['Followers'] = 'Abonnés';
$a->strings['Following'] = 'Abonnements';
$a->strings['Mutual friends'] = 'Amis communs';
$a->strings['Common'] = 'Commun';
$a->strings['Addon not found'] = 'Extension manquante';
$a->strings['Addon already enabled'] = 'Extension déjà activée';
$a->strings['Addon already disabled'] = 'Extension déjà désactivée';
$a->strings['Could not find any unarchived contact entry for this URL (%s)'] = 'Aucune entrée de contact non archivé n\'a été trouvé pour cette URL (%s)';
$a->strings['The contact entries have been archived'] = 'Les contacts ont été archivés';
$a->strings['The avatar cache needs to be disabled in local.config.php to use this command.'] = 'Le cache des avatars doit être désactivé via le fichier local.config.php pour pouvoir utiliser cette commande.';
$a->strings['Could not find any contact entry for this URL (%s)'] = 'Aucun profil distant n\'a été trouvé à cette URL (%s)';
$a->strings['The contact has been blocked from the node'] = 'Le profile distant a été bloqué';
$a->strings['%d %s, %d duplicates.'] = '%d%s, %d duplications.';
$a->strings['uri-id is empty for contact %s.'] = 'l\'uri-id est vide pour le contact %s.';
$a->strings['No valid first contact found for uri-id %d.'] = 'Aucun premier contact valide trouvé pour l\'uri-id %d.';
$a->strings['Wrong duplicate found for uri-id %d in %d (url: %s != %s).'] = 'Mauvaise duplication trouvée pour l\'uri-id %d dans %d (url : %s != %s).';
$a->strings['Wrong duplicate found for uri-id %d in %d (nurl: %s != %s).'] = 'Mauvaise duplication trouvée pour l\'uri-id %d dans %d (nurl : %s != %s).';
$a->strings['Deletion of id %d failed'] = 'La suppression de l\'id %d a échoué.';
$a->strings['Deletion of id %d was successful'] = 'id %d supprimé avec succès.';
$a->strings['Updating "%s" in "%s" from %d to %d'] = 'Mise à jour de "%s" dans "%s" depuis %d vers %d';
$a->strings[' - found'] = '- trouvé(s)';
$a->strings[' - failed'] = '- échoué(s)';
$a->strings[' - success'] = '- succès';
$a->strings[' - deleted'] = '- supprimé(s)';
$a->strings[' - done'] = '- fait(s)';
$a->strings['The avatar cache needs to be enabled to use this command.'] = 'Le cache des avatars doit être activé pour pouvoir utiliser cette commande.';
$a->strings['no resource in photo %s'] = 'Aucune ressource dans la photo %s';
$a->strings['no photo with id %s'] = 'aucune photo avec l\'id %s';
$a->strings['no image data for photo with id %s'] = 'aucune donnée d\'image pour la photo avec l\'id %s';
$a->strings['invalid image for id %s'] = 'image invalide pour l\'id %s';
$a->strings['Quit on invalid photo %s'] = 'Sortie sur photo %s invalide';
$a->strings['Post update version number has been set to %s.'] = 'Le numéro de version de "post update" a été fixé à %s.';
$a->strings['Check for pending update actions.'] = 'Vérification pour les actions de mise à jour en cours.';
$a->strings['Done.'] = 'Fait.';
$a->strings['Execute pending post updates.'] = 'Exécution de la mise à jour des publications en attente.';
$a->strings['All pending post updates are done.'] = 'Toutes les mises à jour de publications en attente sont terminées.';
$a->strings['Enter user nickname: '] = 'Entrer un pseudo :';
$a->strings['User not found'] = 'Utilisateur introuvable';
$a->strings['Enter new password: '] = 'Entrer le nouveau mot de passe :';
$a->strings['Password update failed. Please try again.'] = 'Le changement de mot de passe a échoué. Merci de recommencer.';
$a->strings['Password changed.'] = 'Mot de passe changé.';
$a->strings['Enter user name: '] = 'Entrer le nom d\'utilisateur :';
$a->strings['Enter user email address: '] = 'Entrer l\'adresse courriel de l\'utilisateur :';
$a->strings['Enter a language (optional): '] = 'Saisissez une langue (facultatif) :';
$a->strings['Enter URL of an image to use as avatar (optional): '] = 'Saisissez l\'URL de l\'image à utiliser comme avatar (facultatif) :';
$a->strings['User is not pending.'] = 'L\'utilisateur n\'est pas en attente.';
$a->strings['User has already been marked for deletion.'] = 'L\'utilisateur a déjà été marqué pour suppression.';
$a->strings['Type "yes" to delete %s'] = 'Saisir "yes" pour supprimer %s';
$a->strings['Deletion aborted.'] = 'Suppression annulée.';
$a->strings['Enter category: '] = 'Saisissez la catégorie :';
$a->strings['Enter key: '] = 'Saisissez la clé :';
$a->strings['Enter value: '] = 'Saisissez la valeur :';
$a->strings['newer'] = 'Plus récent';
$a->strings['older'] = 'Plus ancien';
$a->strings['Frequently'] = 'Fréquente';
$a->strings['Hourly'] = 'Horaire';
$a->strings['Twice daily'] = 'Deux fois par jour';
$a->strings['Daily'] = 'Quotidienne';
$a->strings['Weekly'] = 'Hebdomadaire';
$a->strings['Monthly'] = 'Mensuelle';
$a->strings['DFRN'] = 'DFRN';
$a->strings['OStatus'] = 'Ostatus';
$a->strings['RSS/Atom'] = 'RSS/Atom';
$a->strings['Email'] = 'Courriel';
$a->strings['Diaspora'] = 'Diaspora';
$a->strings['Zot!'] = 'Zot!';
$a->strings['LinkedIn'] = 'LinkedIn';
$a->strings['XMPP/IM'] = 'XMPP/Messagerie Instantanée';
$a->strings['MySpace'] = 'MySpace';
$a->strings['Google+'] = 'Google+';
$a->strings['pump.io'] = 'pump.io';
$a->strings['Twitter'] = 'Twitter';
$a->strings['Discourse'] = 'Discourse';
$a->strings['Diaspora Connector'] = 'Connecteur Disapora';
$a->strings['GNU Social Connector'] = 'Connecteur GNU Social';
$a->strings['ActivityPub'] = 'ActivityPub';
$a->strings['pnut'] = 'pnut';
$a->strings['Tumblr'] = 'Tumblr';
$a->strings['Bluesky'] = 'Bluesky';
$a->strings['%s (via %s)'] = '%s (via %s)';
$a->strings['and'] = 'et';
$a->strings['and %d other people'] = 'et %d autres personnes';
$a->strings['%2$s likes this.'] = [
	0 => '%2$s aime.',
	1 => '%2$s aiment.',
	2 => '%2$s aiment.',
];
$a->strings['%2$s doesn\'t like this.'] = [
	0 => '%2$s n\'aime pas.',
	1 => '%2$s n\'aiment pas.',
	2 => '%2$s n\'aiment pas.',
];
$a->strings['%2$s attends.'] = [
	0 => '%2$s participe.',
	1 => '%2$s participent.',
	2 => '%2$s participent.',
];
$a->strings['%2$s doesn\'t attend.'] = [
	0 => '%2$s ne participe pas.',
	1 => '%2$s ne participent pas.',
	2 => '%2$s ne participent pas.',
];
$a->strings['%2$s attends maybe.'] = [
	0 => '%2$s participe peut-être.',
	1 => '%2$s participent peut-être.',
	2 => '%2$s participent peut-être.',
];
$a->strings['%2$s reshared this.'] = [
	0 => '%2$s à partagé.',
	1 => '%2$s ont partagé.',
	2 => '%2$s ont partagé.',
];
$a->strings['<button type="button" %2$s>%1$d person</button> likes this'] = [
	0 => '<button type="button" %2$s>%1$d personne</button> aime',
	1 => '<button type="button" %2$s>%1$d personnes</button> aiment',
	2 => '<button type="button" %2$s>%1$d personnes</button> aiment',
];
$a->strings['<button type="button" %2$s>%1$d person</button> doesn\'t like this'] = [
	0 => '<button type="button" %2$s>%1$d personne</button> n\'aime pas',
	1 => '<button type="button" %2$s>%1$d personnes</button> n\'aiment pas',
	2 => '<button type="button" %2$s>%1$d personnes</button> n\'aiment pas',
];
$a->strings['<button type="button" %2$s>%1$d person</button> attends'] = [
	0 => '<button type="button" %2$s>%1$d personne</button> participe',
	1 => '<button type="button" %2$s>%1$d personnes</button> participent',
	2 => '<button type="button" %2$s>%1$d personnes</button> participent',
];
$a->strings['<button type="button" %2$s>%1$d person</button> doesn\'t attend'] = [
	0 => '<button type="button" %2$s>%1$d personne</button> ne participe pas',
	1 => '<button type="button" %2$s>%1$d personnes</button> ne participent pas',
	2 => '<button type="button" %2$s>%1$d personnes</button> ne participent pas',
];
$a->strings['<button type="button" %2$s>%1$d person</button> attends maybe'] = [
	0 => '<button type="button" %2$s>%1$d personne</button> participe peut-être',
	1 => '<button type="button" %2$s>%1$d personnes</button> participent peut-être',
	2 => '<button type="button" %2$s>%1$d personnes</button> participent peut-être',
];
$a->strings['<button type="button" %2$s>%1$d person</button> reshared this'] = [
	0 => '<button type="button" %2$s>%1$d personne</button> a partagé',
	1 => '<button type="button" %2$s>%1$d personnes</button> ont partagé',
	2 => '<button type="button" %2$s>%1$d personnes</button> ont partagé',
];
$a->strings['Visible to <strong>everybody</strong>'] = 'Visible par <strong>tout le monde</strong>';
$a->strings['Please enter a image/video/audio/webpage URL:'] = 'Veuillez entrer une URL d\'image/vidéo/page web.';
$a->strings['Tag term:'] = 'Tag :';
$a->strings['Save to Folder:'] = 'Sauver dans le Dossier :';
$a->strings['Where are you right now?'] = 'Où êtes-vous actuellement ?';
$a->strings['Delete item(s)?'] = 'Supprimer les élément(s) ?';
$a->strings['Created at'] = 'Créé à';
$a->strings['New Post'] = 'Nouvelle publication';
$a->strings['Share'] = 'Partager';
$a->strings['upload photo'] = 'envoi image';
$a->strings['Attach file'] = 'Joindre fichier';
$a->strings['attach file'] = 'ajout fichier';
$a->strings['Bold'] = 'Gras';
$a->strings['Italic'] = 'Italique';
$a->strings['Underline'] = 'Souligné';
$a->strings['Quote'] = 'Citation';
$a->strings['Add emojis'] = 'Ajouter des émojis';
$a->strings['Content Warning'] = 'Avertissement de contenu';
$a->strings['Code'] = 'Code';
$a->strings['Image'] = 'Image';
$a->strings['Link'] = 'Lien';
$a->strings['Link or Media'] = 'Lien ou média';
$a->strings['Video'] = 'Vidéo';
$a->strings['Set your location'] = 'Définir votre localisation';
$a->strings['set location'] = 'spéc. localisation';
$a->strings['Clear browser location'] = 'Effacer la localisation du navigateur';
$a->strings['clear location'] = 'supp. localisation';
$a->strings['Set title'] = 'Définir un titre';
$a->strings['Categories (comma-separated list)'] = 'Catégories (séparées par des virgules)';
$a->strings['Scheduled at'] = 'Prévu pour';
$a->strings['Permission settings'] = 'Réglages des permissions';
$a->strings['Public post'] = 'Publication publique';
$a->strings['Message'] = 'Message';
$a->strings['Browser'] = 'Navigateur';
$a->strings['Open Compose page'] = 'Ouvrir la page de saisie';
$a->strings['remove'] = 'enlever';
$a->strings['Delete Selected Items'] = 'Supprimer les éléments sélectionnés';
$a->strings['You had been addressed (%s).'] = 'Vous avez été mentionné (%s)';
$a->strings['You are following %s.'] = 'Vous suivez %s.';
$a->strings['You subscribed to %s.'] = 'Vous vous êtes abonné(e) à %s.';
$a->strings['You subscribed to one or more tags in this post.'] = 'Vous vous êtes abonné(e) à une étiquette ou plus de cette publication.';
$a->strings['%s reshared this.'] = '%s a partagé.';
$a->strings['Reshared'] = 'Partagé';
$a->strings['Reshared by %s <%s>'] = 'Partagé par %s <%s>';
$a->strings['%s is participating in this thread.'] = '%s participe à ce fil de discussion';
$a->strings['Stored for general reasons'] = 'Stocké pour des raisons générales.';
$a->strings['Global post'] = 'Publication globale';
$a->strings['Sent via an relay server'] = 'Envoyé via un serveur relais';
$a->strings['Sent via the relay server %s <%s>'] = 'Envoyé par le serveur relais %s <%s>';
$a->strings['Fetched'] = 'Récupéré';
$a->strings['Fetched because of %s <%s>'] = 'Récupéré grâce à %s <%s>';
$a->strings['Stored because of a child post to complete this thread.'] = 'Stocké parce qu\'une publication fille complète ce fil de discussion.';
$a->strings['Local delivery'] = 'Distribution locale';
$a->strings['Stored because of your activity (like, comment, star, ...)'] = 'Stocké en lien avec votre activité (j\'aime, commentaire, étoile...)';
$a->strings['Distributed'] = 'Distribué';
$a->strings['Pushed to us'] = 'Poussé vers nous';
$a->strings['Pinned item'] = 'Élément épinglé';
$a->strings['View %s\'s profile @ %s'] = 'Voir le profil de %s @ %s';
$a->strings['Categories:'] = 'Catégories :';
$a->strings['Filed under:'] = 'Rangé sous :';
$a->strings['%s from %s'] = '%s de %s';
$a->strings['View in context'] = 'Voir dans le contexte';
$a->strings['For you'] = 'Pour vous';
$a->strings['Posts from contacts you interact with and who interact with you'] = 'Publications de contacts qui interagissent avec vous';
$a->strings['Discover'] = 'Découvrir';
$a->strings['Posts from accounts that you don\'t follow, but that you might like.'] = 'Publications de comptes que vous ne suivez pas, mais qui pourrait vous intéresser.';
$a->strings['What\'s Hot'] = 'Quoi de neuf';
$a->strings['Posts with a lot of interactions'] = 'Publications avec beaucoup d\'interactions';
$a->strings['Posts in %s'] = 'Publications dans %s';
$a->strings['Posts from your followers that you don\'t follow'] = 'Publications de personnes abonnées qui vous ne suivez pas';
$a->strings['Sharers of sharers'] = 'Partageurs de partageurs';
$a->strings['Posts from accounts that are followed by accounts that you follow'] = 'Publications de comptes suivis par des comptes que vous suivez';
$a->strings['Quiet sharers'] = 'Partageurs silencieux';
$a->strings['Posts from accounts that you follow but who don\'t post very often'] = 'Publications de comptes que vous suivez mais qui ne publient pas souvent.';
$a->strings['Images'] = 'Images';
$a->strings['Posts with images'] = 'Publications avec images';
$a->strings['Audio'] = 'Audio';
$a->strings['Posts with audio'] = 'Publications avec audio';
$a->strings['Videos'] = 'Vidéos';
$a->strings['Posts with videos'] = 'Publications avec vidéos';
$a->strings['Local Community'] = 'Communauté locale';
$a->strings['Posts from local users on this server'] = 'Conversations publiques démarrées par des utilisateurs locaux';
$a->strings['Global Community'] = 'Communauté globale';
$a->strings['Posts from users of the whole federated network'] = 'Conversations publiques provenant du réseau fédéré global';
$a->strings['Latest Activity'] = 'Activité récente';
$a->strings['Sort by latest activity'] = 'Trier par activité récente';
$a->strings['Latest Posts'] = 'Dernières publications';
$a->strings['Sort by post received date'] = 'Trier par date de réception';
$a->strings['Latest Creation'] = 'Dernière création';
$a->strings['Sort by post creation date'] = 'Trier par date de création';
$a->strings['Personal'] = 'Personnel';
$a->strings['Posts that mention or involve you'] = 'Publications qui vous concernent';
$a->strings['Starred'] = 'Mis en avant';
$a->strings['Favourite Posts'] = 'Publications favorites';
$a->strings['General Features'] = 'Fonctions générales';
$a->strings['Photo Location'] = 'Lieu de prise de la photo';
$a->strings['Photo metadata is normally stripped. This extracts the location (if present) prior to stripping metadata and links it to a map.'] = 'Les métadonnées des photos sont normalement retirées. Ceci permet de sauver l\'emplacement (si présent) et de positionner la photo sur une carte.';
$a->strings['Display the community in the navigation'] = 'Affiche la communauté dans la navigation';
$a->strings['If enabled, the community can be accessed via the navigation menu. Independent from this setting, the community timelines can always be accessed via the channels.'] = 'Si activé, vous pouvez accéder à la communauté via le menu de navigation. Indépendamment de ce paramètre, les flux des communautés sont toujours accessibles via les chaînes.';
$a->strings['Post Composition Features'] = 'Caractéristiques de composition de publication';
$a->strings['Explicit Mentions'] = 'Mentions explicites';
$a->strings['Add explicit mentions to comment box for manual control over who gets mentioned in replies.'] = 'Ajoute des mentions explicites dans les publications permettant un contrôle manuel des mentions dans les fils de commentaires.';
$a->strings['Add an abstract from ActivityPub content warnings'] = 'Ajouter un résumé depuis les avertissements de contenu d\'ActivityPub';
$a->strings['Add an abstract when commenting on ActivityPub posts with a content warning. Abstracts are displayed as content warning on systems like Mastodon or Pleroma.'] = 'Ajoute un résumé lorsque vous commentez des publications ActivityPub avec un avertissement de contenu. Les résumés sont affichés en tant qu\'avertissement de contenu sur les systèmes de type Mastodon ou Pleroma.';
$a->strings['Post/Comment Tools'] = 'Outils de publication/commentaire';
$a->strings['Post Categories'] = 'Catégories des publications';
$a->strings['Add categories to your posts'] = 'Ajouter des catégories à vos publications';
$a->strings['Network Widgets'] = 'Widgets Réseau';
$a->strings['Circles'] = 'Cercles';
$a->strings['Display posts that have been created by accounts of the selected circle.'] = 'Affiche les publications créées par les comptes du cercle sélectionné.';
$a->strings['Groups'] = 'Groupes';
$a->strings['Display posts that have been distributed by the selected group.'] = 'Affiche les publications qui ont été distribuées par le groupe sélectionné.';
$a->strings['Archives'] = 'Archives';
$a->strings['Display an archive where posts can be selected by month and year.'] = 'Affiche une archive permettant de sélectionner les publications par mois et année.';
$a->strings['Protocols'] = 'Protocoles';
$a->strings['Display posts with the selected protocols.'] = 'Affiche les publications obtenues via les protocoles sélectionnés.';
$a->strings['Account Types'] = 'Type de compte';
$a->strings['Display posts done by accounts with the selected account type.'] = 'Affiche les publications des comptes du type sélectionné.';
$a->strings['Channels'] = 'Chaînes';
$a->strings['Display posts in the system channels and user defined channels.'] = 'Affiche les publications dans les chaînes système et les chaînes créées par les utilisateurs.';
$a->strings['Saved Searches'] = 'Recherches';
$a->strings['Display posts that contain subscribed hashtags.'] = 'Affiche les publications incluant les étiquettes souscrites.';
$a->strings['Saved Folders'] = 'Dossiers sauvegardés';
$a->strings['Display a list of folders in which posts are stored.'] = 'Affiche une liste de dossier dans lesquels les publications sont enregistrées.';
$a->strings['Own Contacts'] = 'Publications de vos propres contacts';
$a->strings['Include or exclude posts from subscribed accounts. This widget is not visible on all channels.'] = 'Inclus ou exclu les publications des comptes abonnés. Ce widget n\'est pas visible sur toutes les chaînes.';
$a->strings['Trending Tags'] = 'Tendances';
$a->strings['Display a list of the most popular tags in recent public posts.'] = 'Affiche une liste des étiquettes les plus populaires dans les publications récentes.';
$a->strings['Advanced Profile Settings'] = 'Paramètres Avancés du Profil';
$a->strings['Tag Cloud'] = 'Nuage de tag';
$a->strings['Provide a personal tag cloud on your profile page'] = 'Affiche un nuage de tag personnel sur votre profil.';
$a->strings['Display Membership Date'] = 'Afficher l\'ancienneté';
$a->strings['Display membership date in profile'] = 'Affiche la date de création du compte sur votre profile';
$a->strings['Advanced Calendar Settings'] = 'Paramètres avancés du calendrier';
$a->strings['Allow anonymous access to your calendar'] = 'Autoriser un accès anonyme à votre calendrier';
$a->strings['Allows anonymous visitors to consult your calendar and your public events. Contact birthday events are private to you.'] = 'Autorise les visiteurs anonymes à consulter votre calendrier et vos évènements publics. Les anniversaires de vos contacts demeurent privés.';
$a->strings['External link to group'] = 'Lien externe vers le groupe';
$a->strings['show less'] = 'voir moins';
$a->strings['show more'] = 'montrer plus';
$a->strings['Create new group'] = 'Créer un nouveau groupe';
$a->strings['event'] = 'évènement';
$a->strings['status'] = 'le statut';
$a->strings['photo'] = 'photo';
$a->strings['%1$s tagged %2$s\'s %3$s with %4$s'] = '%1$s a mentionné %3$s de %2$s avec %4$s';
$a->strings['Follow Thread'] = 'Suivre le fil';
$a->strings['View Status'] = 'Voir les statuts';
$a->strings['View Profile'] = 'Voir le profil';
$a->strings['View Photos'] = 'Voir les photos';
$a->strings['Network Posts'] = 'Publications du réseau';
$a->strings['View Contact'] = 'Voir Contact';
$a->strings['Send PM'] = 'Message privé';
$a->strings['Block'] = 'Bloquer';
$a->strings['Ignore'] = 'Ignorer';
$a->strings['Collapse'] = 'Réduire';
$a->strings['Ignore %s server'] = 'Ignorer le serveur %s';
$a->strings['Languages'] = 'Langues';
$a->strings['Search Text'] = 'Rechercher un texte';
$a->strings['Connect/Follow'] = 'Se connecter/Suivre';
$a->strings['Unable to fetch user.'] = 'Impossible de récupérer l\'utilisateur.';
$a->strings['Nothing new here'] = 'Rien de neuf ici';
$a->strings['Go back'] = 'Revenir';
$a->strings['Clear notifications'] = 'Effacer les notifications';
$a->strings['@name, !group, #tags, content'] = '@nom, !groupe, #etiquettes, contenu';
$a->strings['Logout'] = 'Se déconnecter';
$a->strings['End this session'] = 'Mettre fin à cette session';
$a->strings['Login'] = 'Connexion';
$a->strings['Sign in'] = 'Se connecter';
$a->strings['Conversations'] = 'Discussions';
$a->strings['Conversations you started'] = 'Discussions que vous avez commencées';
$a->strings['Profile'] = 'Profil';
$a->strings['Your profile page'] = 'Votre page de profil';
$a->strings['Photos'] = 'Photos';
$a->strings['Your photos'] = 'Vos photos';
$a->strings['Media'] = 'Média';
$a->strings['Your postings with media'] = 'Vos publications avec des médias';
$a->strings['Calendar'] = 'Calendrier';
$a->strings['Your calendar'] = 'Votre calendrier';
$a->strings['Personal notes'] = 'Notes personnelles';
$a->strings['Your personal notes'] = 'Vos notes personnelles';
$a->strings['Home'] = 'Profil';
$a->strings['Home Page'] = 'Page d\'accueil';
$a->strings['Register'] = 'S\'inscrire';
$a->strings['Create an account'] = 'Créer un compte';
$a->strings['Help'] = 'Aide';
$a->strings['Help and documentation'] = 'Aide et documentation';
$a->strings['Apps'] = 'Applications';
$a->strings['Addon applications, utilities, games'] = 'Applications supplémentaires, utilitaires, jeux';
$a->strings['Search'] = 'Recherche';
$a->strings['Search site content'] = 'Rechercher dans le contenu du site';
$a->strings['Full Text'] = 'Texte Entier';
$a->strings['Tags'] = 'Étiquettes';
$a->strings['Contacts'] = 'Contacts';
$a->strings['Community'] = 'Communauté';
$a->strings['Conversations on this and other servers'] = 'Flux public global';
$a->strings['Directory'] = 'Annuaire';
$a->strings['People directory'] = 'Annuaire des utilisateurs';
$a->strings['Information'] = 'Information';
$a->strings['Information about this friendica instance'] = 'Information au sujet de cette instance de friendica';
$a->strings['Terms of Service'] = 'Conditions de service';
$a->strings['Terms of Service of this Friendica instance'] = 'Conditions d\'Utilisation de ce serveur Friendica';
$a->strings['Network'] = 'Réseau';
$a->strings['Conversations from your friends'] = 'Flux de conversations';
$a->strings['Your posts and conversations'] = 'Vos publications et conversations';
$a->strings['Introductions'] = 'Introductions';
$a->strings['Friend Requests'] = 'Demande d\'abonnement';
$a->strings['Notifications'] = 'Notifications';
$a->strings['See all notifications'] = 'Voir toutes les notifications';
$a->strings['Mark as seen'] = 'Marquer comme vu';
$a->strings['Mark all system notifications as seen'] = 'Marquer toutes les notifications système comme vues';
$a->strings['Private mail'] = 'Messages privés';
$a->strings['Inbox'] = 'Messages entrants';
$a->strings['Outbox'] = 'Messages sortants';
$a->strings['Accounts'] = 'Comptes';
$a->strings['Manage other pages'] = 'Gérer les autres pages';
$a->strings['Settings'] = 'Réglages';
$a->strings['Account settings'] = 'Compte';
$a->strings['Manage/edit friends and contacts'] = 'Gestion des contacts';
$a->strings['Admin'] = 'Admin';
$a->strings['Site setup and configuration'] = 'Démarrage et configuration du site';
$a->strings['Moderation'] = 'Modération';
$a->strings['Content and user moderation'] = 'Modération du contenu et des utilisateurs';
$a->strings['Navigation'] = 'Navigation';
$a->strings['Site map'] = 'Carte du site';
$a->strings['first'] = 'premier';
$a->strings['prev'] = 'précédent';
$a->strings['next'] = 'suivant';
$a->strings['last'] = 'dernier';
$a->strings['<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a> %3$s'] = '<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a> %3$s';
$a->strings['Link to source'] = 'Lien vers la source';
$a->strings['Click to open/close'] = 'Cliquer pour ouvrir/fermer';
$a->strings['$1 wrote:'] = '$1 a écrit :';
$a->strings['Encrypted content'] = 'Contenu chiffré';
$a->strings['Invalid source protocol'] = 'Protocole d\'image invalide';
$a->strings['Invalid link protocol'] = 'Protocole de lien invalide';
$a->strings['Loading more entries...'] = 'Chargement de résultats supplémentaires...';
$a->strings['The end'] = 'Fin';
$a->strings['Follow'] = 'S\'abonner';
$a->strings['Add New Contact'] = 'Ajouter un nouveau contact';
$a->strings['Enter address or web location'] = 'Entrez son adresse ou sa localisation web';
$a->strings['Example: bob@example.com, http://example.com/barbara'] = 'Exemple : bob@example.com, http://example.com/barbara';
$a->strings['Connect'] = 'Se connecter';
$a->strings['%d invitation available'] = [
	0 => '%d invitation disponible',
	1 => '%d invitations disponibles',
	2 => '%d invitations disponibles',
];
$a->strings['Find People'] = 'Trouver des personnes';
$a->strings['Enter name or interest'] = 'Entrez un nom ou un centre d\'intérêt';
$a->strings['Examples: Robert Morgenstein, Fishing'] = 'Exemples : Robert Morgenstein, Pêche';
$a->strings['Find'] = 'Trouver';
$a->strings['Friend Suggestions'] = 'Suggestions d\'abonnement';
$a->strings['Similar Interests'] = 'Intérêts similaires';
$a->strings['Random Profile'] = 'Profil au hasard';
$a->strings['Invite Friends'] = 'Inviter des contacts';
$a->strings['Global Directory'] = 'Annuaire global';
$a->strings['Local Directory'] = 'Annuaire local';
$a->strings['Everyone'] = 'Tous les groupes';
$a->strings['No relationship'] = 'Aucune relation';
$a->strings['Relationships'] = 'Relations';
$a->strings['All Contacts'] = 'Tous les contacts';
$a->strings['All Protocols'] = 'Tous les protocoles';
$a->strings['Everything'] = 'Tout';
$a->strings['Categories'] = 'Catégories';
$a->strings['%d contact in common'] = [
	0 => '%d contact en commun',
	1 => '%d contacts en commun',
	2 => '%d contacts en commun',
];
$a->strings['On this date'] = 'A cette date';
$a->strings['Persons'] = 'Personnes';
$a->strings['Organisations'] = 'Organisations';
$a->strings['News'] = 'Nouvelles';
$a->strings['Relays'] = 'Relais';
$a->strings['All'] = 'Tout';
$a->strings['Export'] = 'Exporter';
$a->strings['Export calendar as ical'] = 'Exporter au format iCal';
$a->strings['Export calendar as csv'] = 'Exporter au format CSV';
$a->strings['No contacts'] = 'Aucun contact';
$a->strings['%d Contact'] = [
	0 => '%d contact',
	1 => '%d contacts',
	2 => '%d contacts',
];
$a->strings['View Contacts'] = 'Voir les contacts';
$a->strings['Remove term'] = 'Retirer le terme';
$a->strings['Trending Tags (last %d hour)'] = [
	0 => 'Tendances (dernière %d heure)',
	1 => 'Tendances (dernières %d heures)',
	2 => 'Tendances (dernières %d heures)',
];
$a->strings['More Trending Tags'] = 'Plus de tendances';
$a->strings['Post to group'] = 'Publier sur le groupe';
$a->strings['Mention'] = 'Mention';
$a->strings['XMPP:'] = 'XMPP';
$a->strings['Matrix:'] = 'Matrix :';
$a->strings['Location:'] = 'Localisation :';
$a->strings['Network:'] = 'Réseau';
$a->strings['Unfollow'] = 'Se désabonner';
$a->strings['View group'] = 'Voir le groupe';
$a->strings['Yourself'] = 'Vous-même';
$a->strings['Mutuals'] = 'Mutuels';
$a->strings['Post to Email'] = 'Publier aux courriels';
$a->strings['Public'] = 'Public';
$a->strings['This content will be shown to all your followers and can be seen in the community pages and by anyone with its link.'] = 'Ce contenu sera visible par vos abonnés, sur votre profile, dans les flux communautaires et par quiconque ayant son adresse Web.';
$a->strings['Limited/Private'] = 'Limité/Privé';
$a->strings['This content will be shown only to the people in the first box, to the exception of the people mentioned in the second box. It won\'t appear anywhere public.'] = 'Ce contenu sera visible uniquement par les groupes et contacts listés dans le premier champ, sauf par les groupes et contacts listés dans le second champ. Il ne sera pas visible publiquement.';
$a->strings['Start typing the name of a contact or a circle to show a filtered list. You can also mention the special circles "Followers" and "Mutuals".'] = 'Commencer à écrire le nom d\'un contact ou d\'un cercle pour afficher une liste filtrée. Vous pouvez aussi mentionner les groupes spéciaux "Followers" et "Mutuels".';
$a->strings['Show to:'] = 'Visible par :';
$a->strings['Except to:'] = 'Masquer à :';
$a->strings['CC: email addresses'] = 'CC: adresses de courriel';
$a->strings['Example: bob@example.com, mary@example.com'] = 'Exemple : bob@exemple.com, mary@exemple.com';
$a->strings['Connectors'] = 'Connecteurs';
$a->strings['The database configuration file "config/local.config.php" could not be written. Please use the enclosed text to create a configuration file in your web server root.'] = 'Le fichier de configuration "config/local.config.php" n\'a pas pu être créé.  Veuillez utiliser le texte fourni pour créer manuellement ce fichier sur votre serveur.';
$a->strings['You may need to import the file "database.sql" manually using phpmyadmin or mysql.'] = 'Vous pourriez avoir besoin d\'importer le fichier "database.sql" manuellement au moyen de phpmyadmin ou de la commande mysql.';
$a->strings['Please see the file "doc/INSTALL.md".'] = 'Référez-vous au fichier "doc/INSTALL.md".';
$a->strings['Could not find a command line version of PHP in the web server PATH.'] = 'Impossible de trouver la version "ligne de commande" de PHP dans le PATH du serveur web.';
$a->strings['If you don\'t have a command line version of PHP installed on your server, you will not be able to run the background processing. See <a href=\'https://github.com/friendica/friendica/blob/stable/doc/Install.md#set-up-the-worker\'>\'Setup the worker\'</a>'] = 'Si vous n\'avez pas l\'éxecutable PHP en ligne de commande sur votre serveur, vous ne pourrez pas activer les tâches de fond. Voir <a href=\'https://github.com/friendica/friendica/blob/stable/doc/Install.md#set-up-the-worker\'> "Setup the worker" (en anglais)</a>';
$a->strings['PHP executable path'] = 'Chemin vers l\'exécutable de PHP';
$a->strings['Enter full path to php executable. You can leave this blank to continue the installation.'] = 'Entrez le chemin (absolu) vers l\'exécutable \'php\'. Vous pouvez laisser cette ligne vide pour continuer l\'installation.';
$a->strings['Command line PHP'] = 'Version "ligne de commande" de PHP';
$a->strings['PHP executable is not the php cli binary (could be cgi-fgci version)'] = 'L\'executable PHP n\'est pas le binaire php client (c\'est peut être la version cgi-fcgi)';
$a->strings['Found PHP version: '] = 'Version de PHP :';
$a->strings['PHP cli binary'] = 'PHP cli binary';
$a->strings['The command line version of PHP on your system does not have "register_argc_argv" enabled.'] = 'La version "ligne de commande" de PHP de votre système n\'a pas "register_argc_argv" d\'activé.';
$a->strings['This is required for message delivery to work.'] = 'Ceci est requis pour que la livraison des messages fonctionne.';
$a->strings['PHP register_argc_argv'] = 'PHP register_argc_argv';
$a->strings['Error: the "openssl_pkey_new" function on this system is not able to generate encryption keys'] = 'Erreur: la fonction "openssl_pkey_new" de ce système ne permet pas de générer des clés de chiffrement';
$a->strings['If running under Windows, please see "http://www.php.net/manual/en/openssl.installation.php".'] = 'Si vous utilisez Windows, merci de vous réferer à "http://www.php.net/manual/en/openssl.installation.php".';
$a->strings['Generate encryption keys'] = 'Générer les clés de chiffrement';
$a->strings['Error: Apache webserver mod-rewrite module is required but not installed.'] = 'Erreur : Le module "rewrite" du serveur web Apache est requis mais pas installé.';
$a->strings['Apache mod_rewrite module'] = 'Module mod_rewrite Apache';
$a->strings['Error: PDO or MySQLi PHP module required but not installed.'] = 'Erreur : Les modules PHP PDO ou MySQLi sont requis mais absents de votre serveur.';
$a->strings['Error: The MySQL driver for PDO is not installed.'] = 'Erreur : Le pilote MySQL pour PDO n\'est pas installé sur votre serveur.';
$a->strings['PDO or MySQLi PHP module'] = 'Module PHP PDO ou MySQLi';
$a->strings['Error: The IntlChar module is not installed.'] = 'Erreur : Le module IntlChar n\'est pas installé.';
$a->strings['IntlChar PHP module'] = 'Module PHP IntlChar';
$a->strings['Error, XML PHP module required but not installed.'] = 'Erreur : le module PHP XML requis est absent.';
$a->strings['XML PHP module'] = 'Module PHP XML';
$a->strings['libCurl PHP module'] = 'Module libCurl de PHP';
$a->strings['Error: libCURL PHP module required but not installed.'] = 'Erreur : Le module PHP "libCURL" est requis mais pas installé.';
$a->strings['GD graphics PHP module'] = 'Module GD (graphiques) de PHP';
$a->strings['Error: GD graphics PHP module with JPEG support required but not installed.'] = 'Erreur : Le module PHP "GD" disposant du support JPEG est requis mais pas installé.';
$a->strings['OpenSSL PHP module'] = 'Module OpenSSL de PHP';
$a->strings['Error: openssl PHP module required but not installed.'] = 'Erreur : Le module PHP "openssl" est requis mais pas installé.';
$a->strings['mb_string PHP module'] = 'Module mb_string de PHP';
$a->strings['Error: mb_string PHP module required but not installed.'] = 'Erreur : le module PHP mb_string est requis mais pas installé.';
$a->strings['iconv PHP module'] = 'Module PHP iconv';
$a->strings['Error: iconv PHP module required but not installed.'] = 'Erreur : Le module PHP iconv requis est absent.';
$a->strings['POSIX PHP module'] = 'Module PHP POSIX';
$a->strings['Error: POSIX PHP module required but not installed.'] = 'Erreur : Le module PHP POSIX est requis mais absent sur votre serveur.';
$a->strings['Program execution functions'] = 'Fonctions d\'exécution de programmes';
$a->strings['Error: Program execution functions (proc_open) required but not enabled.'] = 'Erreur : Les functions d\'exécution de programmes (proc_open) sont nécessaires mais manquantes.';
$a->strings['JSON PHP module'] = 'Module PHP JSON';
$a->strings['Error: JSON PHP module required but not installed.'] = 'Erreur : Le module PHP JSON est requis mais absent sur votre serveur.';
$a->strings['File Information PHP module'] = 'Module PHP fileinfo';
$a->strings['Error: File Information PHP module required but not installed.'] = 'Erreur : Le module PHP fileinfo requis est absent.';
$a->strings['GNU Multiple Precision PHP module'] = 'Module PHP de Précision Multiple GNU';
$a->strings['Error: GNU Multiple Precision PHP module required but not installed.'] = 'Erreur : le module PHP de Précision Multiple GNU est requis mais il n\'est pas installé.';
$a->strings['IDN Functions PHP module'] = 'Module PHP Fonctions IDN';
$a->strings['Error: IDN Functions PHP module required but not installed.'] = 'Erreur : le module PHP Fonctions IDN est obligatoire mais n\'est pas installé.';
$a->strings['The web installer needs to be able to create a file called "local.config.php" in the "config" folder of your web server and it is unable to do so.'] = 'L\'installeur web n\'est pas en mesure de créer le fichier "local.config.php" dans le répertoire "config" de votre serveur.';
$a->strings['This is most often a permission setting, as the web server may not be able to write files in your folder - even if you can.'] = 'Le plus souvent, il s\'agit d\'un problème de permission. Le serveur web peut ne pas être capable d\'écrire dans votre répertoire - alors que vous-même le pouvez.';
$a->strings['At the end of this procedure, we will give you a text to save in a file named local.config.php in your Friendica "config" folder.'] = 'À la fin de la procédure d\'installation nous vous fournirons le contenu du fichier "local.config.php" à créer manuellement dans le sous-répertoire "config" de votre répertoire Friendica sur votre serveur.';
$a->strings['You can alternatively skip this procedure and perform a manual installation. Please see the file "doc/INSTALL.md" for instructions.'] = 'Vous pouvez également sauter cette étape et procéder à une installation manuelle. Pour cela, merci de consulter le fichier "doc/INSTALL.md".';
$a->strings['config/local.config.php is writable'] = 'Le fichier "config/local.config.php" peut être créé.';
$a->strings['Friendica uses the Smarty3 template engine to render its web views. Smarty3 compiles templates to PHP to speed up rendering.'] = 'Friendica utilise le moteur de modèles Smarty3 pour le rendu d\'affichage web. Smarty3 compile les modèles en PHP pour accélérer le rendu.';
$a->strings['In order to store these compiled templates, the web server needs to have write access to the directory view/smarty3/ under the Friendica top level folder.'] = 'Pour pouvoir stocker ces modèles compilés, le serveur internet doit avoir accès au droit d\'écriture pour le répertoire view/smarty3/ sous le dossier racine de Friendica.';
$a->strings['Please ensure that the user that your web server runs as (e.g. www-data) has write access to this folder.'] = 'Veuillez vous assurer que l\'utilisateur qui exécute votre serveur internet (p. ex. www-data) détient le droit d\'accès en écriture sur ce dossier.';
$a->strings['Note: as a security measure, you should give the web server write access to view/smarty3/ only--not the template files (.tpl) that it contains.'] = 'Note: pour plus de sécurité, vous devriez ne donner le droit d\'accès en écriture qu\'à view/smarty3/ et pas aux fichiers modèles (.tpl) qu\'il contient.';
$a->strings['view/smarty3 is writable'] = 'view/smarty3 est autorisé à l écriture';
$a->strings['Url rewrite in .htaccess seems not working. Make sure you copied .htaccess-dist to .htaccess.'] = 'La réécriture d\'URL ne semble pas fonctionner, veuillez vous assurer que vous avez créé un fichier ".htaccess" à partir du fichier ".htaccess-dist".';
$a->strings['In some circumstances (like running inside containers), you can skip this error.'] = 'Dans certaines situations (comme une installation dans un container), vous pouvez ignorer cette erreur.';
$a->strings['Error message from Curl when fetching'] = 'Message d\'erreur de Curl lors du test de réécriture d\'URL';
$a->strings['Url rewrite is working'] = 'La réécriture d\'URL fonctionne.';
$a->strings['The detection of TLS to secure the communication between the browser and the new Friendica server failed.'] = 'La détection de TLS pour sécuriser la communication entre le navigateur et votre nouveau serveur Friendica a échoué.';
$a->strings['It is highly encouraged to use Friendica only over a secure connection as sensitive information like passwords will be transmitted.'] = 'Nous vous recommandons fortement de n\'utiliser Friendica qu\'avec une connection sécurisée étant donné que des informations sensibles comme des mots de passe seront échangés.';
$a->strings['Please ensure that the connection to the server is secure.'] = 'Veuillez vous assurer que la connection au serveur est sécurisée.';
$a->strings['No TLS detected'] = 'Pas de TLS détecté';
$a->strings['TLS detected'] = 'TLS détecté';
$a->strings['ImageMagick PHP extension is not installed'] = 'L\'extension PHP ImageMagick n\'est pas installée';
$a->strings['ImageMagick PHP extension is installed'] = 'L’extension PHP ImageMagick est installée';
$a->strings['Database already in use.'] = 'Base de données déjà en cours d\'utilisation.';
$a->strings['Could not connect to database.'] = 'Impossible de se connecter à la base.';
$a->strings['Undetermined'] = 'Indéterminé';
$a->strings['%s (%s)'] = '%s (%s)';
$a->strings['Monday'] = 'Lundi';
$a->strings['Tuesday'] = 'Mardi';
$a->strings['Wednesday'] = 'Mercredi';
$a->strings['Thursday'] = 'Jeudi';
$a->strings['Friday'] = 'Vendredi';
$a->strings['Saturday'] = 'Samedi';
$a->strings['Sunday'] = 'Dimanche';
$a->strings['January'] = 'Janvier';
$a->strings['February'] = 'Février';
$a->strings['March'] = 'Mars';
$a->strings['April'] = 'Avril';
$a->strings['May'] = 'Mai';
$a->strings['June'] = 'Juin';
$a->strings['July'] = 'Juillet';
$a->strings['August'] = 'Août';
$a->strings['September'] = 'Septembre';
$a->strings['October'] = 'Octobre';
$a->strings['November'] = 'Novembre';
$a->strings['December'] = 'Décembre';
$a->strings['Mon'] = 'Lun';
$a->strings['Tue'] = 'Mar';
$a->strings['Wed'] = 'Mer';
$a->strings['Thu'] = 'Jeu';
$a->strings['Fri'] = 'Ven';
$a->strings['Sat'] = 'Sam';
$a->strings['Sun'] = 'Dim';
$a->strings['Jan'] = 'Jan';
$a->strings['Feb'] = 'Fév';
$a->strings['Mar'] = 'Mar';
$a->strings['Apr'] = 'Avr';
$a->strings['Jun'] = 'Jun';
$a->strings['Jul'] = 'Jul';
$a->strings['Aug'] = 'Aoû';
$a->strings['Sep'] = 'Sep';
$a->strings['Oct'] = 'Oct';
$a->strings['Nov'] = 'Nov';
$a->strings['Dec'] = 'Déc';
$a->strings['The logfile \'%s\' is not usable. No logging possible (error: \'%s\')'] = 'Le fichier journal \'%s\' n\'est pas utilisable. Pas de journalisation possible (erreur \'%s\')';
$a->strings['The debug logfile \'%s\' is not usable. No logging possible (error: \'%s\')'] = 'Le fichier journal de débogage "%s" n\'existe pas ou n\'est pas accessible en écriture. Journalisation désactivée (erreur : "%s")';
$a->strings['Friendica can\'t display this page at the moment, please contact the administrator.'] = 'Friendica ne peut pas afficher cette page pour le moment. Merci de contacter l\'administrateur.';
$a->strings['template engine cannot be registered without a name.'] = 'Le moteur de template ne peut pas être enregistré sans nom.';
$a->strings['template engine is not registered!'] = 'le moteur de template n\'est pas enregistré!';
$a->strings['Storage base path'] = 'Chemin de base du stockage';
$a->strings['Folder where uploaded files are saved. For maximum security, This should be a path outside web server folder tree'] = 'Répertoire dans lequel les fichiers sont stockés. Pour une sécurité maximale, il devrait être situé dans un chemin hors de votre serveur web.';
$a->strings['Enter a valid existing folder'] = 'Entrez le chemin d\'un dossier existant';
$a->strings['Updates from version %s are not supported. Please update at least to version 2021.01 and wait until the postupdate finished version 1383.'] = 'Les mises à jour automatiques ne sont pas disponibles depuis la version %s. Veuillez mettre à jour manuellement jusqu\'à la version 2021.01 et attendre que la mise à jour des données atteigne la version 1383.';
$a->strings['Updates from postupdate version %s are not supported. Please update at least to version 2021.01 and wait until the postupdate finished version 1383.'] = 'La mise à jour automatique des données n\'est pas disponible depuis la version %s. Veuillez mettre à jour manuellement jusqu\'à la version 2021.01 et attendre que la mise à jour des données atteigne la version 1383.';
$a->strings['%s: executing pre update %d'] = '%s : Exécution de la mise à jour préalable %d';
$a->strings['%s: executing post update %d'] = '%s : Exécution de la mise à jour des données %d';
$a->strings['Update %s failed. See error logs.'] = 'Mise à jour %s échouée. Voir les journaux d\'erreur.';
$a->strings['
				The friendica developers released update %s recently,
				but when I tried to install it, something went terribly wrong.
				This needs to be fixed soon and I can\'t do it alone. Please contact a
				friendica developer if you can not help me on your own. My database might be invalid.'] = '
Les développeurs de Friendica ont récemment publié la mise à jour %s, mais en tentant de l’installer, quelque chose s’est terriblement mal passé. Une réparation s’impose et je ne peux pas la faire tout seul. Contactez un développeur Friendica si vous ne pouvez pas corriger le problème vous-même. Il est possible que ma base de données soit corrompue.';
$a->strings['The error message is\n[pre]%s[/pre]'] = 'The message d\'erreur est\n[pre]%s[/pre]';
$a->strings['[Friendica Notify] Database update'] = '[Friendica:Notification] Mise à jour de la base de données';
$a->strings['
				The friendica database was successfully updated from %s to %s.'] = '
				La base de données de Friendica a bien été mise à jour de la version %s à %s.';
$a->strings['The database version had been set to %s.'] = 'La version de la base de données a été fixée a %s.';
$a->strings['The post update is at version %d, it has to be at %d to safely drop the tables.'] = 'La mise à jour des données est à la version %d, mais elle doit atteindre la version %d pour pouvoir supprimer les tables en toute sécurité.';
$a->strings['No unused tables found.'] = 'Aucune table non utilisée trouvée.';
$a->strings['These tables are not used for friendica and will be deleted when you execute "dbstructure drop -e":'] = 'Ces tables ne sont pas utilisées pour friendica et seront supprimées lorsque vous exécuterez "dbstructure drop -e" :';
$a->strings['There are no tables on MyISAM or InnoDB with the Antelope file format.'] = 'Il n\'y a pas de tables MyISAM ou InnoDB avec le format de fichier Antelope.';
$a->strings['
Error %d occurred during database update:
%s
'] = '
Erreur %d survenue durant la mise à jour de la base de données :
%s
';
$a->strings['Errors encountered performing database changes: '] = 'Erreurs survenues lors de la mise à jour de la base de données :';
$a->strings['Another database update is currently running.'] = 'Une autre mise à jour de la base de données est en cours.';
$a->strings['%s: Database update'] = '%s : Mise à jour de la base de données';
$a->strings['%s: updating %s table.'] = '%s : Table %s en cours de mise à jour.';
$a->strings['Record not found'] = 'Enregistrement non trouvé';
$a->strings['Unprocessable Entity'] = 'Entité impossible à traiter';
$a->strings['Unauthorized'] = 'Accès réservé';
$a->strings['Token is not authorized with a valid user or is missing a required scope'] = 'Le jeton ne comporte pas un utilisateur valide ou une portée (scope) nécessaire.';
$a->strings['Internal Server Error'] = 'Erreur du site';
$a->strings['Legacy module file not found: %s'] = 'Module original non trouvé: %s';
$a->strings['A deleted circle with this name was revived. Existing item permissions <strong>may</strong> apply to this circle and any future members. If this is not what you intended, please create another circle with a different name.'] = 'Un cercle supprimé a été recréé. Les permissions existantes <strong>pourraient</strong> s\'appliquer à ce cercle et aux futurs membres. Si ce n\'est pas le comportement attendu, merci de re-créer un autre cercle sous un autre nom.';
$a->strings['Everybody'] = 'Tout le monde';
$a->strings['edit'] = 'éditer';
$a->strings['add'] = 'ajouter';
$a->strings['Edit circle'] = 'Modifier le cercle';
$a->strings['Contacts not in any circle'] = 'Contacts n\'appartenant à aucun cercle';
$a->strings['Create a new circle'] = 'Créer un nouveau cercle';
$a->strings['Circle Name: '] = 'Nom du cercle :';
$a->strings['Edit circles'] = 'Modifier les cercles';
$a->strings['Approve'] = 'Approuver';
$a->strings['%s has blocked you'] = '%s vous a bloqué(e).';
$a->strings['Organisation'] = 'Organisation';
$a->strings['Group'] = 'Groupe';
$a->strings['Relay'] = 'Relai';
$a->strings['Disallowed profile URL.'] = 'URL de profil interdite.';
$a->strings['Blocked domain'] = 'Domaine bloqué';
$a->strings['Connect URL missing.'] = 'URL de connexion manquante.';
$a->strings['The contact could not be added. Please check the relevant network credentials in your Settings -> Social Networks page.'] = 'Le contact n\'a pu être ajouté. Veuillez vérifier les identifiants du réseau concerné dans la page Réglages -> Réseaux Sociaux si pertinent.';
$a->strings['Expected network %s does not match actual network %s'] = 'Le réseau %s espéré ne correspond pas au réseau %s actuel';
$a->strings['This seems to be a relay account. They can\'t be followed by users.'] = 'Cela semble être un compte relai. Ils ne peuvent pas être suivi par des utilisateurs.';
$a->strings['The profile address specified does not provide adequate information.'] = 'L\'adresse de profil indiquée ne fournit par les informations adéquates.';
$a->strings['No compatible communication protocols or feeds were discovered.'] = 'Aucun protocole de communication ni aucun flux n\'a pu être découvert.';
$a->strings['An author or name was not found.'] = 'Aucun auteur ou nom d\'auteur n\'a pu être trouvé.';
$a->strings['No browser URL could be matched to this address.'] = 'Aucune URL de navigation ne correspond à cette adresse.';
$a->strings['Unable to match @-style Identity Address with a known protocol or email contact.'] = 'Impossible de faire correspondre l\'adresse d\'identité en "@" avec un protocole connu ou un contact courriel.';
$a->strings['Use mailto: in front of address to force email check.'] = 'Utilisez mailto: en face d\'une adresse pour l\'obliger à être reconnue comme courriel.';
$a->strings['Limited profile. This person will be unable to receive direct/personal notifications from you.'] = 'Profil limité. Cette personne ne sera pas capable de recevoir des notifications directes/personnelles de votre part.';
$a->strings['Unable to retrieve contact information.'] = 'Impossible de récupérer les informations du contact.';
$a->strings['l F d, Y \@ g:i A \G\M\TP (e)'] = 'l d F Y \@ G:i \G\M\TP (e)';
$a->strings['Starts:'] = 'Débute :';
$a->strings['Finishes:'] = 'Finit :';
$a->strings['all-day'] = 'toute la journée';
$a->strings['Sept'] = 'Sep';
$a->strings['today'] = 'aujourd\'hui';
$a->strings['month'] = 'mois';
$a->strings['week'] = 'semaine';
$a->strings['day'] = 'jour';
$a->strings['No events to display'] = 'Pas d\'évènement à afficher';
$a->strings['Access to this profile has been restricted.'] = 'L\'accès au profil a été restreint.';
$a->strings['Event not found.'] = 'Évènement non trouvé.';
$a->strings['l, F j'] = 'l, F j';
$a->strings['Edit event'] = 'Editer l\'évènement';
$a->strings['Duplicate event'] = 'Dupliquer l\'évènement';
$a->strings['Delete event'] = 'Supprimer l\'évènement';
$a->strings['l F d, Y \@ g:i A'] = 'l F d, Y \@ g:i A';
$a->strings['D g:i A'] = 'D G:i';
$a->strings['g:i A'] = 'G:i';
$a->strings['Show map'] = 'Montrer la carte';
$a->strings['Hide map'] = 'Cacher la carte';
$a->strings['%s\'s birthday'] = 'Anniversaire de %s\'s';
$a->strings['Happy Birthday %s'] = 'Joyeux anniversaire, %s !';
$a->strings['%s (%s - %s): %s'] = '%s (%s - %s) : %s';
$a->strings['%s (%s): %s'] = '%s (%s) : %s';
$a->strings['Detected languages in this post:
%s'] = 'Langues détectées dans la publication :
%s';
$a->strings['activity'] = 'activité';
$a->strings['comment'] = 'commentaire';
$a->strings['post'] = 'publication';
$a->strings['%s is blocked'] = '%s est bloqué(e)';
$a->strings['%s is ignored'] = '%s est ignoré(e)';
$a->strings['Content from %s is collapsed'] = 'Le contenu de %s est réduit';
$a->strings['Sensitive content'] = 'Contenu sensible';
$a->strings['bytes'] = 'octets';
$a->strings['%2$s (%3$d%%, %1$d vote)'] = [
	0 => '%2$s (%3$d%%, %1$d vote)',
	1 => '%2$s (%3$d%%, %1$d votes)',
	2 => '%2$s (%3$d%%, %1$d votes)',
];
$a->strings['%2$s (%1$d vote)'] = [
	0 => '%2$s (%1$d vote)',
	1 => '%2$s (%1$d votes)',
	2 => '%2$s (%1$d votes)',
];
$a->strings['%d voter. Poll end: %s'] = [
	0 => '%d votant. Fin du sondage : %s',
	1 => '%d votants. Fin du sondage : %s',
	2 => '%d votants. Fin du sondage : %s',
];
$a->strings['%d voter.'] = [
	0 => '%d votant.',
	1 => '%d votants.',
	2 => '%d votants.',
];
$a->strings['Poll end: %s'] = 'Fin du sondage : %s';
$a->strings['View on separate page'] = 'Voir dans une nouvelle page';
$a->strings['[no subject]'] = '[pas de sujet]';
$a->strings['Wall Photos'] = 'Photos du mur';
$a->strings['Edit profile'] = 'Editer le profil';
$a->strings['Change profile photo'] = 'Changer de photo de profil';
$a->strings['Homepage:'] = 'Page personnelle :';
$a->strings['About:'] = 'À propos :';
$a->strings['Atom feed'] = 'Flux Atom';
$a->strings['This website has been verified to belong to the same person.'] = 'Ce site web a été vérifié comme appartenant à la même personne.';
$a->strings['F d'] = 'F d';
$a->strings['[today]'] = '[aujourd\'hui]';
$a->strings['Birthday Reminders'] = 'Rappels d\'anniversaires';
$a->strings['Birthdays this week:'] = 'Anniversaires cette semaine :';
$a->strings['g A l F d'] = 'g A | F d';
$a->strings['[No description]'] = '[Sans description]';
$a->strings['Event Reminders'] = 'Rappels d\'évènements';
$a->strings['Upcoming events the next 7 days:'] = 'Évènements à venir dans les 7 prochains jours :';
$a->strings['Hometown:'] = ' Ville d\'origine :';
$a->strings['Marital Status:'] = 'Statut marital :';
$a->strings['With:'] = 'Avec :';
$a->strings['Since:'] = 'Depuis :';
$a->strings['Sexual Preference:'] = 'Préférence sexuelle :';
$a->strings['Political Views:'] = 'Opinions politiques :';
$a->strings['Religious Views:'] = 'Opinions religieuses :';
$a->strings['Likes:'] = 'J\'aime :';
$a->strings['Dislikes:'] = 'Je n\'aime pas :';
$a->strings['Title/Description:'] = 'Titre / Description :';
$a->strings['Summary'] = 'Résumé';
$a->strings['Musical interests'] = 'Goûts musicaux';
$a->strings['Books, literature'] = 'Lectures';
$a->strings['Television'] = 'Télévision';
$a->strings['Film/dance/culture/entertainment'] = 'Cinéma / Danse / Culture / Divertissement';
$a->strings['Hobbies/Interests'] = 'Passe-temps / Centres d\'intérêt';
$a->strings['Love/romance'] = 'Amour / Romance';
$a->strings['Work/employment'] = 'Activité professionnelle / Occupation';
$a->strings['School/education'] = 'Études / Formation';
$a->strings['Contact information and Social Networks'] = 'Coordonnées / Réseaux sociaux';
$a->strings['Responsible account: %s'] = 'Compte responsable : %s';
$a->strings['SERIOUS ERROR: Generation of security keys failed.'] = 'ERREUR FATALE : La génération des clés de sécurité a échoué.';
$a->strings['Login failed'] = 'Échec de l\'identification';
$a->strings['Not enough information to authenticate'] = 'Pas assez d\'informations pour s\'identifier';
$a->strings['Password can\'t be empty'] = 'Le mot de passe ne peut pas être vide';
$a->strings['Empty passwords are not allowed.'] = 'Les mots de passe vides ne sont pas acceptés.';
$a->strings['The new password has been exposed in a public data dump, please choose another.'] = 'Le nouveau mot de passe fait partie d\'une fuite de mot de passe publique, veuillez en choisir un autre.';
$a->strings['The password length is limited to 72 characters.'] = 'La taille du mot de passe est limitée à 72 caractères.';
$a->strings['The password can\'t contain white spaces nor accentuated letters'] = 'Le mot de passe ne peut pas contenir d\'espaces ou de lettres accentuées';
$a->strings['Passwords do not match. Password unchanged.'] = 'Les mots de passe ne correspondent pas. Aucun changement appliqué.';
$a->strings['An invitation is required.'] = 'Une invitation est requise.';
$a->strings['Invitation could not be verified.'] = 'L\'invitation fournie n\'a pu être validée.';
$a->strings['Invalid OpenID url'] = 'Adresse OpenID invalide';
$a->strings['We encountered a problem while logging in with the OpenID you provided. Please check the correct spelling of the ID.'] = 'Nous avons eu un souci avec l\'OpenID que vous avez fourni. Merci de vérifier qu\'il est correctement écrit.';
$a->strings['The error message was:'] = 'Le message d\'erreur était :';
$a->strings['Please enter the required information.'] = 'Entrez les informations requises.';
$a->strings['system.username_min_length (%s) and system.username_max_length (%s) are excluding each other, swapping values.'] = 'system.username_min_length (%s) et system.username_max_length (%s) s\'excluent mutuellement, leur valeur sont échangées.';
$a->strings['Username should be at least %s character.'] = [
	0 => 'L\'identifiant utilisateur doit comporter au moins %s caractère.',
	1 => 'L\'identifiant utilisateur doit comporter au moins %s caractères.',
	2 => 'L\'identifiant utilisateur doit comporter au moins %s caractères.',
];
$a->strings['Username should be at most %s character.'] = [
	0 => 'L\'identifiant utilisateur doit comporter au plus %s caractère.',
	1 => 'L\'identifiant utilisateur doit comporter au plus %s caractères.',
	2 => 'L\'identifiant utilisateur doit comporter au plus %s caractères.',
];
$a->strings['That doesn\'t appear to be your full (First Last) name.'] = 'Ceci ne semble pas être votre nom complet (Prénom Nom).';
$a->strings['Your email domain is not among those allowed on this site.'] = 'Votre domaine de courriel n\'est pas autorisé sur ce site.';
$a->strings['Not a valid email address.'] = 'Ceci n\'est pas une adresse courriel valide.';
$a->strings['The nickname was blocked from registration by the nodes admin.'] = 'Cet identifiant utilisateur est réservé.';
$a->strings['Cannot use that email.'] = 'Impossible d\'utiliser ce courriel.';
$a->strings['Your nickname can only contain a-z, 0-9 and _.'] = 'Votre identifiant utilisateur ne peut comporter que a-z, 0-9 et _.';
$a->strings['Nickname is already registered. Please choose another.'] = 'Pseudo déjà utilisé. Merci d\'en choisir un autre.';
$a->strings['An error occurred during registration. Please try again.'] = 'Une erreur est survenue lors de l\'inscription. Merci de recommencer.';
$a->strings['An error occurred creating your default profile. Please try again.'] = 'Une erreur est survenue lors de la création de votre profil par défaut. Merci de recommencer.';
$a->strings['An error occurred creating your self contact. Please try again.'] = 'Une erreur est survenue lors de la création de votre propre contact. Veuillez réssayer.';
$a->strings['Friends'] = 'Contacts';
$a->strings['An error occurred creating your default contact circle. Please try again.'] = 'Une erreur est survenue lors de la création de votre cercle de contacts par défaut. Veuillez réessayer.';
$a->strings['Profile Photos'] = 'Photos du profil';
$a->strings['
		Dear %1$s,
			the administrator of %2$s has set up an account for you.'] = '
		Cher/Chère %1$s,
			l\'administrateur de %2$s a créé un compte pour vous.';
$a->strings['
		The login details are as follows:

		Site Location:	%1$s
		Login Name:		%2$s
		Password:		%3$s

		You may change your password from your account "Settings" page after logging
		in.

		Please take a few moments to review the other account settings on that page.

		You may also wish to add some basic information to your default profile
		(on the "Profiles" page) so that other people can easily find you.

		We recommend adding a profile photo, adding some profile "keywords"
		(very useful in making new friends) - and perhaps what country you live in;
		if you do not wish to be more specific than that.

		We fully respect your right to privacy, and none of these items are necessary.
		If you are new and do not know anybody here, they may help
		you to make some new and interesting friends.

		If you ever want to delete your account, you can do so at %1$s/settings/removeme

		Thank you and welcome to %4$s.'] = '
		Les détails de connexion sont les suivants :

		Adresse :	%1$s
		Identifiant :		%2$s
		Mot de passe:		%3$s

		Vous pouvez modifier votre mot de passe à partir de la page "Paramètres"
		de votre compte après vous être connecté.

		Veuillez prendre quelques instants pour passer en revue les autres paramètres
		de votre compte sur cette page.

		Vous pouvez également ajouter quelques informations de base à votre profil par
		défaut (sur la page "Profils") afin que d\'autres personnes puissent vous trouver facilement.

		Nous vous recommandons d\'ajouter une photo de profil, des "mots-clés"
		(très utiles pour se faire de nouveaux amis) et peut-être le pays dans lequel
		vous vivez, si vous ne souhaitez pas être plus précis.

		Nous respectons pleinement votre droit à la vie privée et aucun de ces éléments
		n\'est nécessaire. Si vous êtes nouveau et que vous ne connaissez personne ici,
		ils peuvent vous aider à vous faire de nouveaux amis intéressants.

		Si vous souhaitez supprimer votre compte, vous pouvez le faire à l\'adresse %1$s/settings/removeme

		Merci et bienvenue sur %4$s.';
$a->strings['Registration details for %s'] = 'Détails d\'inscription pour %s';
$a->strings['
			Dear %1$s,
				Thank you for registering at %2$s. Your account is pending for approval by the administrator.

			Your login details are as follows:

			Site Location:	%3$s
			Login Name:		%4$s
			Password:		%5$s
		'] = '
			Ch·er·ère %1$s,
				Merci de vous être inscrit-e sur%2$s. Votre compte est en attente de la validation d\'un administrateur.

			Vos identifiants sont les suivants:

			Localisation du site :	%3$s
			Nom d\'utilisateur :		%4$s
			Mot de passe :		%5$s
		';
$a->strings['Registration at %s'] = 'inscription à %s';
$a->strings['
				Dear %1$s,
				Thank you for registering at %2$s. Your account has been created.
			'] = '
				Cher %1$s,
				Merci pour votre inscription sur %2$s. Votre compte a été créé.
			';
$a->strings['
			The login details are as follows:

			Site Location:	%3$s
			Login Name:		%1$s
			Password:		%5$s

			You may change your password from your account "Settings" page after logging
			in.

			Please take a few moments to review the other account settings on that page.

			You may also wish to add some basic information to your default profile
			(on the "Profiles" page) so that other people can easily find you.

			We recommend adding a profile photo, adding some profile "keywords" (very useful
			in making new friends) - and perhaps what country you live in; if you do not wish
			to be more specific than that.

			We fully respect your right to privacy, and none of these items are necessary.
			If you are new and do not know anybody here, they may help
			you to make some new and interesting friends.

			If you ever want to delete your account, you can do so at %3$s/settings/removeme

			Thank you and welcome to %2$s.'] = '
			Les détails de connexion sont les suivants :

			Adresse :	%3$s
			Identifiant :		%1$s
			Mot de passe:		%5$s

			Vous pouvez modifier votre mot de passe à partir de la page "Paramètres"
			de votre compte après vous être connecté.

			Veuillez prendre quelques instants pour passer en revue les autres paramètres
			de votre compte sur cette page.

			Vous pouvez également ajouter quelques informations de base à votre profil par
			défaut (sur la page "Profils") afin que d\'autres personnes puissent vous trouver facilement.

			Nous vous recommandons d\'ajouter une photo de profil, des "mots-clés"
			(très utiles pour se faire de nouveaux amis) et peut-être le pays dans lequel
			vous vivez, si vous ne souhaitez pas être plus précis.

			Nous respectons pleinement votre droit à la vie privée et aucun de ces éléments
			n\'est nécessaire. Si vous êtes nouveau et que vous ne connaissez personne ici,
			ils peuvent vous aider à vous faire de nouveaux amis intéressants.

			Si vous souhaitez supprimer votre compte, vous pouvez le faire à l\'adresse %3$s/settings/removeme

			Merci et bienvenue sur %2$s.';
$a->strings['User with delegates can\'t be removed, please remove delegate users first'] = 'Un utilisateur avec des délégataires ne peut pas être supprimé, veuillez d\'abord retirer les délégataires.';
$a->strings['Addon not found.'] = 'Extension manquante.';
$a->strings['Addon %s disabled.'] = 'Extension %s désactivée.';
$a->strings['Addon %s enabled.'] = 'Extension %s activée.';
$a->strings['Disable'] = 'Désactiver';
$a->strings['Enable'] = 'Activer';
$a->strings['Administration'] = 'Administration';
$a->strings['Addons'] = 'Extensions';
$a->strings['Toggle'] = 'Activer/Désactiver';
$a->strings['Author: '] = 'Auteur : ';
$a->strings['Maintainer: '] = 'Mainteneur : ';
$a->strings['Addons reloaded'] = 'Extensions rechargées';
$a->strings['Addon %s failed to install.'] = 'L\'extension %s a échoué à s\'installer.';
$a->strings['Save Settings'] = 'Sauvegarder les paramètres';
$a->strings['Reload active addons'] = 'Recharger les extensions activées.';
$a->strings['There are currently no addons available on your node. You can find the official addon repository at %1$s.'] = 'Il n\'y a pas d\'extension disponible sur votre serveur. Vous pouvez trouver le dépôt officiel d\'extensions sur %1$s.';
$a->strings['Update has been marked successful'] = 'Mise à jour validée comme \'réussie\'';
$a->strings['Database structure update %s was successfully applied.'] = 'La structure de base de données pour la mise à jour %s a été appliquée avec succès.';
$a->strings['Executing of database structure update %s failed with error: %s'] = 'L\'exécution de la mise à jour %s pour la structure de base de données a échoué avec l\'erreur: %s';
$a->strings['Executing %s failed with error: %s'] = 'L\'exécution %s a échoué avec l\'erreur: %s';
$a->strings['Update %s was successfully applied.'] = 'Mise à jour %s appliquée avec succès.';
$a->strings['Update %s did not return a status. Unknown if it succeeded.'] = 'La mise à jour %s n\'a pas retourné de détails. Impossible de savoir si elle a réussi.';
$a->strings['There was no additional update function %s that needed to be called.'] = 'Il n\'y avait aucune fonction supplémentaire de mise à jour %s qui devait être appelé';
$a->strings['No failed updates.'] = 'Pas de mises-à-jour échouées.';
$a->strings['Check database structure'] = 'Vérifier la structure de la base de données';
$a->strings['Failed Updates'] = 'Mises à jour échouées';
$a->strings['This does not include updates prior to 1139, which did not return a status.'] = 'Ceci n\'inclut pas les versions antérieures à la 1139, qui ne retournaient jamais de détails.';
$a->strings['Mark success (if update was manually applied)'] = 'Marquer comme \'réussie\' (dans le cas d\'une mise-à-jour manuelle)';
$a->strings['Attempt to execute this update step automatically'] = 'Tenter d\'éxecuter cette étape automatiquement';
$a->strings['No'] = 'Non';
$a->strings['Yes'] = 'Oui';
$a->strings['Locked'] = 'Vérouillé';
$a->strings['Manage Additional Features'] = 'Gérer les fonctionnalités avancées';
$a->strings['Other'] = 'Autre';
$a->strings['unknown'] = 'inconnu';
$a->strings['%2$s total system'] = [
	0 => '%2$s système au total',
	1 => '%2$s systèmes au total',
	2 => '%2$s systèmes au total',
];
$a->strings['%2$s active user last month'] = [
	0 => '%2$s utilisateur actif le mois dernier',
	1 => '%2$s utilisateurs actifs le mois dernier',
	2 => '%2$s utilisateurs actifs le mois dernier',
];
$a->strings['%2$s active user last six months'] = [
	0 => '%2$s utilisateur actif ces six derniers mois',
	1 => '%2$s utilisateurs actifs ces six derniers mois',
	2 => '%2$s utilisateurs actifs ces six derniers mois',
];
$a->strings['%2$s registered user'] = [
	0 => '%2$s utilisateur enregistré',
	1 => '%2$s utilisateurs enregistrés',
	2 => '%2$s utilisateurs enregistrés',
];
$a->strings['%2$s locally created post or comment'] = [
	0 => '%2$s publication ou commentaire créé localement',
	1 => '%2$s publications et commentaires créés localement',
	2 => '%2$s publications et commentaires créés localement',
];
$a->strings['%2$s post per user'] = [
	0 => '%2$s publication par utilisateur',
	1 => '%2$s publications par utilisateur',
	2 => '%2$s publications par utilisateur',
];
$a->strings['%2$s user per system'] = [
	0 => '%2$s utilisateur par système',
	1 => '%2$s utilisateurs par système',
	2 => '%2$s utilisateurs par système',
];
$a->strings['This page offers you some numbers to the known part of the federated social network your Friendica node is part of. These numbers are not complete but only reflect the part of the network your node is aware of.'] = 'Cette page montre quelques statistiques de la partie connue du réseau social fédéré dont votre instance Friendica fait partie. Ces chiffres sont partiels et ne reflètent que la portion du réseau dont votre instance a connaissance.';
$a->strings['Federation Statistics'] = 'Statistiques Federation';
$a->strings['Currently this node is aware of %2$s node (%3$s active users last month, %4$s active users last six months, %5$s registered users in total) from the following platforms:'] = [
	0 => 'Actuellement, ce nœud est en relation avec %2$s autre nœud (%3$s utilisateurs actifs le mois dernier, %4$s ces six derniers mois, %5$s utilisateurs enregistrés au total) issus des plateformes suivantes :',
	1 => 'Actuellement, ce nœud est en relation avec %2$s autres nœuds (%3$s utilisateurs actifs le mois dernier, %4$s ces six derniers mois, %5$s utilisateurs enregistrés au total) issus des plateformes suivantes :',
	2 => 'Actuellement, ce nœud est en relation avec %2$s autre nœud (%3$s utilisateurs actifs  le mois dernier, %4$s ces six derniers mois, %5$s utilisateurs enregistrés au total) issus des plateformes suivantes :',
];
$a->strings['The logfile \'%s\' is not writable. No logging possible'] = 'Le fichier journal \'%s\' n\'est pas accessible en écriture. Pas de journalisation possible';
$a->strings['PHP log currently enabled.'] = 'Log PHP actuellement activé.';
$a->strings['PHP log currently disabled.'] = 'Log PHP actuellement desactivé.';
$a->strings['Logs'] = 'Journaux';
$a->strings['Clear'] = 'Effacer';
$a->strings['Enable Debugging'] = 'Activer le déboggage';
$a->strings['<strong>Read-only</strong> because it is set by an environment variable'] = '<strong>En lecture seule</strong> car configuré avec une variable d\'environnement';
$a->strings['Log file'] = 'Fichier journal';
$a->strings['Must be writable by web server. Relative to your Friendica top-level directory.'] = 'Accès en écriture par le serveur web requis. Relatif à la racine de votre installation de Friendica.';
$a->strings['Log level'] = 'Niveau de journalisaton';
$a->strings['PHP logging'] = 'Log PHP';
$a->strings['To temporarily enable logging of PHP errors and warnings you can prepend the following to the index.php file of your installation. The filename set in the \'error_log\' line is relative to the friendica top-level directory and must be writeable by the web server. The option \'1\' for \'log_errors\' and \'display_errors\' is to enable these options, set to \'0\' to disable them.'] = 'Pour activer temporairement la journalisation de PHP vous pouvez insérez les lignes suivantes au début du fichier <code>index.php</code> dans votre répertoire Friendica. The nom de fichier défini dans la ligne <code>\'error_log\'</code> est relatif au répertoire d\'installation de Friendica et le serveur web doit avoir le droit d\'écriture sur ce fichier. Les lignes <code>log_errors</code> et <code>display_errors</code> prennent les valeurs <code>0</code>  et <code>1</code> respectivement pour les activer ou désactiver.';
$a->strings['Error trying to open <strong>%1$s</strong> log file.<br/>Check to see if file %1$s exist and is readable.'] = 'Erreur lors de l\'ouverture du fichier journal <strong>%1$s</strong>.<br/>Vérifiez si le fichier %1$s existe et est lisible.';
$a->strings['Couldn\'t open <strong>%1$s</strong> log file.<br/>Check to see if file %1$s is readable.'] = 'Impossible d\'ouvrir le fichier journal <strong>%1$s</strong> .<br/>Vérifiez si le fichier %1$s est lisible.';
$a->strings['View Logs'] = 'Voir les logs';
$a->strings['Search in logs'] = 'Rechercher dans les fichiers journaux';
$a->strings['Show all'] = 'Tout afficher';
$a->strings['Date'] = 'Date';
$a->strings['Level'] = 'Niveau';
$a->strings['Context'] = 'Contexte';
$a->strings['ALL'] = 'TOUS';
$a->strings['View details'] = 'Voir les détails';
$a->strings['Click to view details'] = 'Cliquer pour voir les détails';
$a->strings['Event details'] = 'Détails de l\'évènement';
$a->strings['Data'] = 'Données';
$a->strings['Source'] = 'Source';
$a->strings['File'] = 'Fichier';
$a->strings['Line'] = 'Ligne';
$a->strings['Function'] = 'Fonction';
$a->strings['UID'] = 'UID';
$a->strings['Process ID'] = 'ID de processus';
$a->strings['Close'] = 'Fermer';
$a->strings['Inspect Deferred Worker Queue'] = 'Détail des tâches de fond reportées';
$a->strings['This page lists the deferred worker jobs. This are jobs that couldn\'t be executed at the first time.'] = 'Cette page détaille les tâches de fond reportées après avoir échoué une première fois.';
$a->strings['Inspect Worker Queue'] = 'Détail des tâches de fond en attente';
$a->strings['This page lists the currently queued worker jobs. These jobs are handled by the worker cronjob you\'ve set up during install.'] = 'Cette page détaille les tâches de fond en attente. Elles seront traitées lors de la prochaine exécution de la tâche planifiée que vous avez définie lors de l\'installation.';
$a->strings['ID'] = 'ID';
$a->strings['Command'] = 'Commande';
$a->strings['Job Parameters'] = 'Paramètres de la tâche';
$a->strings['Created'] = 'Créé';
$a->strings['Next Try'] = 'Prochain Essai';
$a->strings['Priority'] = 'Priorité';
$a->strings['%s is no valid input for maximum media size'] = '%s n\'est pas une saisie valide pour une taille de média';
$a->strings['%s is no valid input for maximum image size'] = '%s n\'est pas une valeur valide pour la taille maximum d\'image';
$a->strings['No special theme for mobile devices'] = 'Pas de thème particulier pour les terminaux mobiles';
$a->strings['%s - (Experimental)'] = '%s- (expérimental)';
$a->strings['No community page'] = 'Aucune page de communauté';
$a->strings['No community page for visitors'] = 'Aucune page communautaire pour les visiteurs';
$a->strings['Public postings from users of this site'] = 'Publications publiques des utilisateurs de ce site';
$a->strings['Public postings from the federated network'] = 'Publications publiques du réseau fédéré';
$a->strings['Public postings from local users and the federated network'] = 'Publications publiques des utilisateurs du site et du réseau fédéré';
$a->strings['Multi user instance'] = 'Instance multi-utilisateurs';
$a->strings['Closed'] = 'Fermé';
$a->strings['Requires approval'] = 'Demande une apptrobation';
$a->strings['Open'] = 'Ouvert';
$a->strings['Don\'t check'] = 'Ne pas rechercher';
$a->strings['check the stable version'] = 'Rechercher les versions stables';
$a->strings['check the development version'] = 'Rechercher les versions de développement';
$a->strings['none'] = 'aucun';
$a->strings['Local contacts'] = 'Contacts locaux';
$a->strings['Interactors'] = 'Interagisseurs';
$a->strings['Site'] = 'Site';
$a->strings['General Information'] = 'Information générale';
$a->strings['Republish users to directory'] = 'Republier les utilisateurs sur le répertoire';
$a->strings['Registration'] = 'Inscription';
$a->strings['File upload'] = 'Téléversement de fichier';
$a->strings['Policies'] = 'Politiques';
$a->strings['Advanced'] = 'Avancé';
$a->strings['Auto Discovered Contact Directory'] = 'Répertoire de Contacts Découverts Automatiquement';
$a->strings['Performance'] = 'Performance';
$a->strings['Worker'] = 'Tâche de fond';
$a->strings['Message Relay'] = 'Relai de publication';
$a->strings['Use the command "console relay" in the command line to add or remove relays.'] = 'Utilisez la commande "console relay" en ligne de commande pour ajouter ou retirer des relais.';
$a->strings['The system is not subscribed to any relays at the moment.'] = 'Ce serveur n\'est pas abonné à un relai pour le moment.';
$a->strings['The system is currently subscribed to the following relays:'] = 'Ce serveur est actuellement abonné aux relais suivants:';
$a->strings['Relocate Node'] = 'Déplacer le nœud';
$a->strings['Relocating your node enables you to change the DNS domain of this node and keep all the existing users and posts. This process takes a while and can only be started from the relocate console command like this:'] = 'Déplacer votre nœud vous permet de changer le domaine DNS de celui-ci et de conserver tous les utilisateurs existants ainsi que les publications. Ce processus prend un certain temps et ne peut être lancé que depuis la ligne de commande relocate de la façon suivante :';
$a->strings['(Friendica directory)# bin/console relocate https://newdomain.com'] = '(Friendica directory)# bin/console relocate https://nouveaudomaine.fr';
$a->strings['Site name'] = 'Nom du site';
$a->strings['Sender Email'] = 'Courriel de l\'émetteur';
$a->strings['The email address your server shall use to send notification emails from.'] = 'L\'adresse courriel à partir de laquelle votre serveur enverra des courriels.';
$a->strings['Name of the system actor'] = 'Nom du compte système';
$a->strings['Name of the internal system account that is used to perform ActivityPub requests. This must be an unused username. If set, this can\'t be changed again.'] = 'Nom du compte interne utilisé pour effectuer les requêtes ActivityPub. Ce nom doit être inutilisé actuellement. Une fois défini, ce nom ne peut pas être changé.';
$a->strings['Banner/Logo'] = 'Bannière/Logo';
$a->strings['Email Banner/Logo'] = 'Bannière/Logo de courriel';
$a->strings['Shortcut icon'] = 'Icône de raccourci';
$a->strings['Link to an icon that will be used for browsers.'] = 'Lien vers une icône qui sera utilisée pour les navigateurs.';
$a->strings['Touch icon'] = 'Icône pour systèmes tactiles';
$a->strings['Link to an icon that will be used for tablets and mobiles.'] = 'Lien vers une icône qui sera utilisée pour les tablettes et les mobiles.';
$a->strings['Additional Info'] = 'Informations supplémentaires';
$a->strings['For public servers: you can add additional information here that will be listed at %s/servers.'] = 'Description publique destinée au <a href="%s">répertoire global de sites Friendica</a>.';
$a->strings['System language'] = 'Langue du système';
$a->strings['System theme'] = 'Thème du système';
$a->strings['Default system theme - may be over-ridden by user profiles - <a href="%s" id="cnftheme">Change default theme settings</a>'] = 'Thème système par défaut - peut être modifié par profil utilisateur - <a href="%s" id="cnftheme"> Changer les paramètres de thème par défaut</a>';
$a->strings['Mobile system theme'] = 'Thème mobile';
$a->strings['Theme for mobile devices'] = 'Thème pour les terminaux mobiles';
$a->strings['Force SSL'] = 'SSL obligatoire';
$a->strings['Force all Non-SSL requests to SSL - Attention: on some systems it could lead to endless loops.'] = 'Redirige toutes les requêtes en clair vers des requêtes SSL. Attention : sur certains systèmes cela peut conduire à des boucles de redirection infinies.';
$a->strings['Show help entry from navigation menu'] = 'Montrer l\'aide dans le menu de navigation';
$a->strings['Displays the menu entry for the Help pages from the navigation menu. It is always accessible by calling /help directly.'] = 'Montre l\'Aide dans le menu de navigation. L\'aide reste accessible en naviguant vers /help directement.';
$a->strings['Single user instance'] = 'Instance mono-utilisateur';
$a->strings['Make this instance multi-user or single-user for the named user'] = 'Transformer cette en instance en multi-utilisateur ou mono-utilisateur pour cet l\'utilisateur.';
$a->strings['Maximum image size'] = 'Taille maximale des images';
$a->strings['Maximum size in bytes of uploaded images. Default is 0, which means no limits. You can put k, m, or g behind the desired value for KiB, MiB, GiB, respectively.
													The value of <code>upload_max_filesize</code> in your <code>PHP.ini</code> needs be set to at least the desired limit.
													Currently <code>upload_max_filesize</code> is set to %s (%s byte)'] = 'Taille maximale en octets des images téléversées. La valeur par défaut est 0, ce qui signifie aucune limite. Vous pouvez indiquer k, m, ou g après la valeur désirée pour Kio, Mio, Gio respectivement.
													La valeur de <code>upload_max_filesize</code> dans votre <code>PHP.ini</code> doit être définie au minimum à la valeur désirée.
													Actuellement <code>upload_max_filesize</code> est définie à %s (%s octet)';
$a->strings['Maximum image length'] = 'Longueur maximale des images';
$a->strings['Maximum length in pixels of the longest side of uploaded images. Default is -1, which means no limits.'] = 'Longueur maximale en pixels du plus long côté des images téléversées. La valeur par défaut est -1 : absence de limite.';
$a->strings['JPEG image quality'] = 'Qualité JPEG des images';
$a->strings['Uploaded JPEGS will be saved at this quality setting [0-100]. Default is 100, which is full quality.'] = 'Les JPEGs téléversés seront sauvegardés avec ce niveau de qualité [0-100]. La valeur par défaut est 100, soit la qualité maximale.';
$a->strings['Maximum media file size'] = 'Taille maximale d\'un média';
$a->strings['Maximum size in bytes of uploaded media files. Default is 0, which means no limits. You can put k, m, or g behind the desired value for KiB, MiB, GiB, respectively.
													The value of <code>upload_max_filesize</code> in your <code>PHP.ini</code> needs be set to at least the desired limit.
													Currently <code>upload_max_filesize</code> is set to %s (%s byte)'] = 'Taille maximale en octets des fichiers multimédias envoyés. La valeur par défaut est 0, ce qui signifie aucune limite. Vous pouvez mettre k, m, ou g derrière la valeur souhaitée pour KiB, MiB, GiB, respectivement.
													La valeur de <code>upload_max_filesize</code> dans votre <code>PHP.ini</code> doit être au moins égale à la limite souhaitée.
													Actuellement, <code>upload_max_filesize</code> est fixé à %s (%s byte)';
$a->strings['Register policy'] = 'Politique d\'inscription';
$a->strings['Maximum Users'] = 'Utilisateurs maximum';
$a->strings['If defined, the register policy is automatically closed when the given number of users is reached and reopens the registry when the number drops below the limit. It only works when the policy is set to open or close, but not when the policy is set to approval.'] = 'Si défini, la politique d\'inscription est automatiquement définie à "Fermé" quand le nombre d\'utilisateurs est atteint et mis à "Ouvert" quand le nombre descend en dessous de la limite. Cela fonctionne uniquement si la politique est défini à "Ouvert" ou "Fermé", mais pas quand celle-ci est définie à "Demande une approbation".';
$a->strings['Maximum Daily Registrations'] = 'Inscriptions maximum par jour';
$a->strings['If registration is permitted above, this sets the maximum number of new user registrations to accept per day.  If register is set to closed, this setting has no effect.'] = 'Si les inscriptions sont permises ci-dessus, ceci fixe le nombre maximum d\'inscriptions de nouveaux utilisateurs acceptées par jour. Si les inscriptions ne sont pas ouvertes, ce paramètre n\'a aucun effet.';
$a->strings['Register text'] = 'Texte d\'inscription';
$a->strings['Will be displayed prominently on the registration page. You can use BBCode here.'] = 'Ce texte est affiché sur la page d\'inscription. Les BBCodes sont autorisés.';
$a->strings['Forbidden Nicknames'] = 'Identifiants réservés';
$a->strings['Comma separated list of nicknames that are forbidden from registration. Preset is a list of role names according RFC 2142.'] = 'Liste d\'identifiants réservés séparés par des virgules. Ces identifiants ne peuvent pas être utilisés pour s\'enregistrer. La liste de base provient de la RFC 2142.';
$a->strings['Accounts abandoned after x days'] = 'Les comptes sont abandonnés après x jours';
$a->strings['Will not waste system resources polling external sites for abandonded accounts. Enter 0 for no time limit.'] = 'Pour ne pas gaspiller les ressources système, on cesse d\'interroger les sites distants pour les comptes abandonnés. Mettre 0 pour désactiver cette fonction.';
$a->strings['Allowed friend domains'] = 'Domaines autorisés';
$a->strings['Comma separated list of domains which are allowed to establish friendships with this site. Wildcards are accepted. Empty to allow any domains'] = 'Une liste de domaines, séparés par des virgules, autorisés à établir des relations avec les utilisateurs de ce site. Les \'*\' sont acceptés. Laissez vide pour autoriser tous les domaines';
$a->strings['Allowed email domains'] = 'Domaines courriel autorisés';
$a->strings['Comma separated list of domains which are allowed in email addresses for registrations to this site. Wildcards are accepted. Empty to allow any domains'] = 'Liste de domaines - séparés par des virgules - dont les adresses de courriel sont autorisées à s\'inscrire sur ce site. Les \'*\' sont acceptées. Laissez vide pour autoriser tous les domaines';
$a->strings['Disallowed email domains'] = 'Domaines courriel interdits';
$a->strings['Comma separated list of domains which are rejected as email addresses for registrations to this site. Wildcards are accepted.'] = 'Liste, séparé par des virgules, de domaines d\'adresse de courriel qui seront rejetés lors de l\'enregistrement. Caractères de remplacement autorisés.';
$a->strings['No OEmbed rich content'] = 'Désactiver le texte riche avec OEmbed';
$a->strings['Don\'t show the rich content (e.g. embedded PDF), except from the domains listed below.'] = 'Evite le contenu riche avec OEmbed (comme un document PDF incrusté), sauf provenant des domaines autorisés listés ci-après.';
$a->strings['Trusted third-party domains'] = 'Domaines tierce-partie de confiance';
$a->strings['Comma separated list of domains from which content is allowed to be embedded in posts like with OEmbed. All sub-domains of the listed domains are allowed as well.'] = 'Liste séparée par des virgules de domaines dont le contenu est autorisé à être intégré dans les publications comme avec OEmbed. Tous les sous-domaines des domains mentionnés sont autorisés également.';
$a->strings['Block public'] = 'Interdire la publication globale';
$a->strings['Check to block public access to all otherwise public personal pages on this site unless you are currently logged in.'] = 'Cocher pour bloquer les accès anonymes (non-connectés) à tout sauf aux pages personnelles publiques.';
$a->strings['Force publish'] = 'Forcer la publication globale';
$a->strings['Check to force all profiles on this site to be listed in the site directory.'] = 'Cocher pour publier obligatoirement tous les profils locaux dans l\'annuaire du site.';
$a->strings['Enabling this may violate privacy laws like the GDPR'] = 'Activer cette option peut potentiellement enfreindre les lois sur la protection de la vie privée comme le RGPD.';
$a->strings['Global directory URL'] = 'URL de l\'annuaire global';
$a->strings['URL to the global directory. If this is not set, the global directory is completely unavailable to the application.'] = 'URL de l\'annuaire global. Si ce champ n\'est pas défini, l\'annuaire global sera complètement indisponible pour l\'application.';
$a->strings['Private posts by default for new users'] = 'Publications privées par défaut pour les nouveaux utilisateurs';
$a->strings['Set default post permissions for all new members to the default privacy circle rather than public.'] = 'Rendre les publications de tous les nouveaux utilisateurs accessibles seulement par le cercle de contacts par défaut, et non par tout le monde.';
$a->strings['Don\'t include post content in email notifications'] = 'Ne pas inclure le contenu de la publication dans le courriel de notification';
$a->strings['Don\'t include the content of a post/comment/private message/etc. in the email notifications that are sent out from this site, as a privacy measure.'] = 'Ne pas inclure le contenu d\'un(e) publication/commentaire/message privé/etc dans le courriel de notification qui est envoyé à partir du site, par mesure de confidentialité.';
$a->strings['Disallow public access to addons listed in the apps menu.'] = 'Interdire l’accès public pour les extensions listées dans le menu apps.';
$a->strings['Checking this box will restrict addons listed in the apps menu to members only.'] = 'Cocher cette case restreint la liste des extensions dans le menu des applications seulement aux membres.';
$a->strings['Don\'t embed private images in posts'] = 'Ne pas miniaturiser les images privées dans les publications';
$a->strings['Don\'t replace locally-hosted private photos in posts with an embedded copy of the image. This means that contacts who receive posts containing private photos will have to authenticate and load each image, which may take a while.'] = 'Ne remplacez pas les images privées hébergées localement dans les publications avec une image attaché en copie, car cela signifie que le contact qui reçoit les publications contenant ces photos privées devra s’authentifier pour charger chaque image, ce qui peut prendre du temps.';
$a->strings['Explicit Content'] = 'Contenu adulte';
$a->strings['Set this to announce that your node is used mostly for explicit content that might not be suited for minors. This information will be published in the node information and might be used, e.g. by the global directory, to filter your node from listings of nodes to join. Additionally a note about this will be shown at the user registration page.'] = 'Activez cette option si votre site est principalement utilisé pour publier du contenu adulte. Cette information est publique et peut être utilisée pour filtrer votre site dans le répertoire de site global. Elle est également affichée sur la page d\'inscription.';
$a->strings['Only local search'] = 'Recherche locale uniquement';
$a->strings['Blocks search for users who are not logged in to prevent crawlers from blocking your system.'] = 'Bloque la recherche pour les utilisateurs non connectés afin d\'éviter aux robot d\'indexation de bloquer votre système.';
$a->strings['Blocked tags for trending tags'] = 'Étiquettes bloquées pour les tendances';
$a->strings['Comma separated list of hashtags that shouldn\'t be displayed in the trending tags.'] = 'Liste d\'étiquettes, séparées par des virgules, qui ne seront pas affichées dans les tendances.';
$a->strings['Cache contact avatars'] = 'Mettre en cache les avatars des contacts';
$a->strings['Locally store the avatar pictures of the contacts. This uses a lot of storage space but it increases the performance.'] = 'Stocker localement les images d\'avatar des contacts. Cela utilise beaucoup d\'espace disque mais améliore les performances.';
$a->strings['Allow Users to set remote_self'] = 'Autoriser les utilisateurs à définir remote_self';
$a->strings['With checking this, every user is allowed to mark every contact as a remote_self in the repair contact dialog. Setting this flag on a contact causes mirroring every posting of that contact in the users stream.'] = 'Cocher cette case, permet à chaque utilisateur de marquer chaque contact comme un remote_self dans la boîte de dialogue de réparation des contacts. Activer cette fonction à un contact engendre la réplique de toutes les publications d\'un contact dans le flux d\'activités des utilisateurs.';
$a->strings['Allow Users to set up relay channels'] = 'Autorise les utilisateurs à créer les chaînes relais';
$a->strings['If enabled, it is possible to create relay users that are used to reshare content based on user defined channels.'] = 'Si activé, il est possible de créer des utilisateurs relai qui seront utilisés pour repartager du contenu basé sur les chaînes de l\'utilisateur.';
$a->strings['Adjust the feed poll frequency'] = 'Régler la fréquence d\'interrogation';
$a->strings['Automatically detect and set the best feed poll frequency.'] = 'Détecte automatiquement et défini la meilleure fréquence d\'interrogation.';
$a->strings['Minimum poll interval'] = 'Intervalle minimum de requêtage';
$a->strings['Minimal distance in minutes between two polls for mail and feed contacts. Reasonable values are between 1 and 59.'] = 'Temps minimum entre deux requêtages en minutes pour les courriels et les contacts. Les valeurs raisonnables sont entre 1 et 59.';
$a->strings['Enable multiple registrations'] = 'Autoriser les comptes multiples';
$a->strings['Enable users to register additional accounts for use as pages.'] = 'Permet aux utilisateurs d\'enregistrer des comptes supplémentaires pour être utilisés comme pages.';
$a->strings['Enable OpenID'] = 'Activer OpenID';
$a->strings['Enable OpenID support for registration and logins.'] = 'Permet l\'utilisation d\'OpenID pour l\'enregistrement de compte et l\'identification.';
$a->strings['Enable full name check'] = 'Activer la vérification du nom complet';
$a->strings['Prevents users from registering with a display name with fewer than two parts separated by spaces.'] = 'Empêche les utilisateurs de s\'enregistrer avec un nom d\'affichage n\'ayant pas au minimum 2 parties séparées par des espaces.';
$a->strings['Email administrators on new registration'] = 'Envoyer un courriel aux administrateurs lors d\'une nouvelle inscription';
$a->strings['If enabled and the system is set to an open registration, an email for each new registration is sent to the administrators.'] = 'Si activé et que le système est défini à une inscription ouverte, un courriel sera envoyé pour chaque nouvelle inscription aux administrateurs.';
$a->strings['Community pages for visitors'] = 'Affichage de la page communauté pour les utilisateurs anonymes';
$a->strings['Which community pages should be available for visitors. Local users always see both pages.'] = 'Quelles pages communauté sont disponibles pour les utilisateurs anonymes.';
$a->strings['Posts per user on community page'] = 'Nombre de publications par utilisateur sur la page de la communauté';
$a->strings['The maximum number of posts per user on the local community page. This is useful, when a single user floods the local community page.'] = 'Nombre maximum de publications par utilisateur sur la page de communauté locale. Utile lorsqu\'un seul utilisateur inonde la page de communauté locale.';
$a->strings['Posts per server on community page'] = 'Publications par serveur sur la page Communauté';
$a->strings['The maximum number of posts per server on the global community page. This is useful, when posts from a single server flood the global community page.'] = 'Nombre maximum de publications par serveur sur la page de communauté globale. Utile lorsque les publications d\'un seul serveur inonde la page de communauté globale.';
$a->strings['Enable Mail support'] = 'Activer la prise en charge courriel';
$a->strings['Enable built-in mail support to poll IMAP folders and to reply via mail.'] = 'Permet de se connecter à un compte IMAP et de répondre directement aux courriels via Friendica.';
$a->strings['Mail support can\'t be enabled because the PHP IMAP module is not installed.'] = 'La prise en charge courriel requiert le module PHP IMAP pour être activée.';
$a->strings['Diaspora support can\'t be enabled because Friendica was installed into a sub directory.'] = 'Le support de Diaspora ne peut pas être activé parce que Friendica a été installé dans un sous-répertoire.';
$a->strings['Enable Diaspora support'] = 'Activer le support de Diaspora';
$a->strings['Enable built-in Diaspora network compatibility for communicating with diaspora servers.'] = 'Permet la communication avec des comptes distants via Diaspora. Ce protocole est principalement utilisé par la plate-forme Diaspora.';
$a->strings['Verify SSL'] = 'Vérifier SSL';
$a->strings['If you wish, you can turn on strict certificate checking. This will mean you cannot connect (at all) to self-signed SSL sites.'] = 'Si vous le souhaitez, vous pouvez activier la vérification stricte des certificats. Cela signifie que vous ne pourrez pas vous connecter (du tout) aux sites SSL munis d\'un certificat auto-signé.';
$a->strings['Proxy user'] = 'Utilisateur du proxy';
$a->strings['User name for the proxy server.'] = 'Nom d\'utilisateur pour le serveur proxy';
$a->strings['Proxy URL'] = 'URL du proxy';
$a->strings['If you want to use a proxy server that Friendica should use to connect to the network, put the URL of the proxy here.'] = 'Si vous souhaitez utiliser un serveur proxy que Friendica devra employer pour se connecter au réseau, indiquez l\'adresse du proxy ici.';
$a->strings['Network timeout'] = 'Dépassement du délai d\'attente du réseau';
$a->strings['Value is in seconds. Set to 0 for unlimited (not recommended).'] = 'Valeur en secondes. Mettre à 0 pour \'illimité\' (pas recommandé).';
$a->strings['Maximum Load Average'] = 'Plafond de la charge moyenne';
$a->strings['Maximum system load before delivery and poll processes are deferred - default %d.'] = 'La charge système maximal avant que les processus livraisons et de sondage de profils distants soient reportées. Défaut : %d.';
$a->strings['Minimal Memory'] = 'Mémoire minimum';
$a->strings['Minimal free memory in MB for the worker. Needs access to /proc/meminfo - default 0 (deactivated).'] = 'Mémoire libre minimale pour les tâches de fond (en Mo). Requiert l\'accès à <code>/proc/meminfo</code>. La valeur par défaut est 0 (désactivé).';
$a->strings['Periodically optimize tables'] = 'Optimizer les tables régulièrement';
$a->strings['Periodically optimize tables like the cache and the workerqueue'] = 'Optimise régulièrement certaines tables de base de données très utilisées comme cache, locks, session, ou workerqueue.';
$a->strings['Discover followers/followings from contacts'] = 'Découvrir la liste de contacts des contacts';
$a->strings['If enabled, contacts are checked for their followers and following contacts.'] = 'Si activé, ce serveur collecte la liste d\'abonnés et d\'abonnements des contacts suivants.';
$a->strings['None - deactivated'] = 'Aucun - désactivé';
$a->strings['Local contacts - contacts of our local contacts are discovered for their followers/followings.'] = 'Contacts locaux : Les contacts des utilisateurs de ce serveur';
$a->strings['Interactors - contacts of our local contacts and contacts who interacted on locally visible postings are discovered for their followers/followings.'] = 'Interagisseurs : Les contacts des utilisateurs de ce serveur et les contacts qui ont intéragit avec les conversations dont ce serveur a connaissance.';
$a->strings['Only update contacts/servers with local data'] = 'Mettre a jour que les contacts/serveurs ayant des données locales';
$a->strings['If enabled, the system will only look for changes in contacts and servers that engaged on this system by either being in a contact list of a user or when posts or comments exists from the contact on this system.'] = 'Si activé, le système ne recherchera que les modifications apportées aux contacts et aux serveurs qui se sont engagés dans ce système, soit parce qu\'ils figurent dans la liste de contacts d\'un utilisateur, soit parce que des messages ou des commentaires ont été publiés par le contact sur ce système.';
$a->strings['Synchronize the contacts with the directory server'] = 'Synchroniser les contacts avec l\'annuaire distant';
$a->strings['if enabled, the system will check periodically for new contacts on the defined directory server.'] = 'Active l\'ajout de nouveaux contacts depuis l\'annuaire distant choisi.';
$a->strings['Discover contacts from other servers'] = 'Découvrir des contacts des autres serveurs';
$a->strings['Periodically query other servers for contacts and servers that they know of. The system queries Friendica, Mastodon and Hubzilla servers. Keep it deactivated on small machines to decrease the database size and load.'] = 'Le système interroge périodiquement d\'autres serveurs (Friendica, Mastodon et Hubzilla) pour connaître les contacts et les serveurs qu\'ils connaissent. Désactivez-le sur les petites machines pour réduire la taille et la charge de la base de données.';
$a->strings['Days between requery'] = 'Nombre de jours entre les requêtes';
$a->strings['Number of days after which a server is requeried for their contacts and servers it knows of. This is only used when the discovery is activated.'] = 'Nombre de jours après lesquels un serveur est interrogé sur ses contacts et les serveurs qu\'il connaît. Ce paramètre n\'est utilisé que lorsque la découverte est activée.';
$a->strings['Search the local directory'] = 'Chercher dans le répertoire local';
$a->strings['Search the local directory instead of the global directory. When searching locally, every search will be executed on the global directory in the background. This improves the search results when the search is repeated.'] = 'Cherche dans le répertoire local au lieu du répertoire local. Quand une recherche locale est effectuée, la même recherche est effectuée dans le répertoire global en tâche de fond. Cela améliore les résultats de la recherche si elle est réitérée.';
$a->strings['Publish server information'] = 'Publier les informations du serveur';
$a->strings['If enabled, general server and usage data will be published. The data contains the name and version of the server, number of users with public profiles, number of posts and the activated protocols and connectors. See <a href="http://the-federation.info/">the-federation.info</a> for details.'] = 'Active la publication de données générales sur ce serveur et son utilisation. Contient entre autres le nom et la version du serveur, le nombre d\'utilisateurs avec un profil public, le nombre de publications et la liste des connecteurs activés. Voir <a href="http://the-federation.info/">the-federation.info</a> pour les détails.';
$a->strings['Check upstream version'] = 'Mises à jour';
$a->strings['Enables checking for new Friendica versions at github. If there is a new version, you will be informed in the admin panel overview.'] = 'Permet de vérifier la présence de nouvelles versions de Friendica sur github. Si une nouvelle version est disponible, vous recevrez une notification dans l\'interface d\'administration.';
$a->strings['Suppress Tags'] = 'Masquer les étiquettes';
$a->strings['Suppress showing a list of hashtags at the end of the posting.'] = 'Ne pas afficher la liste des étiquettes à la fin d’un message.';
$a->strings['Clean database'] = 'Nettoyer la base de données';
$a->strings['Remove old remote items, orphaned database records and old content from some other helper tables.'] = 'Supprime les conversations distantes anciennes, les enregistrements orphelins et le contenu obsolète de certaines tables de débogage.';
$a->strings['Lifespan of remote items'] = 'Durée de vie des conversations distantes';
$a->strings['When the database cleanup is enabled, this defines the days after which remote items will be deleted. Own items, and marked or filed items are always kept. 0 disables this behaviour.'] = 'Si le nettoyage de la base de donnée est actif, cette valeur représente le délai en jours après lequel les conversations distantes sont supprimées. Les conversations démarrées par un utilisateur local, étoilées ou archivées sont toujours conservées. 0 pour désactiver.';
$a->strings['Lifespan of unclaimed items'] = 'Durée de vie des conversations relayées';
$a->strings['When the database cleanup is enabled, this defines the days after which unclaimed remote items (mostly content from the relay) will be deleted. Default value is 90 days. Defaults to the general lifespan value of remote items if set to 0.'] = 'Si le nettoyage de la base de donnée est actif, cette valeur représente le délai en jours après lequel les conversations relayées qui n\'ont pas reçu d\'interactions locales sont supprimées. La valeur par défaut est 90 jours. 0 pour aligner cette valeur sur la durée de vie des conversations distantes.';
$a->strings['Lifespan of raw conversation data'] = 'Durée de vie des méta-données de conversation';
$a->strings['The conversation data is used for ActivityPub, as well as for debug purposes. It should be safe to remove it after 14 days, default is 90 days.'] = 'Les données de conversation sont utilisées pour ActivityPub, ainsi qu\'à des fins de débogage. Elles peuvent être supprimées en toute sécurité après 14 jours, la durée par défaut étant de 90 jours.';
$a->strings['Maximum numbers of comments per post'] = 'Nombre maximum de commentaires par publication';
$a->strings['How much comments should be shown for each post? Default value is 100.'] = 'Nombre de commentaire affiché pour chaque publication. Valeur par défaut : 100.';
$a->strings['Maximum numbers of comments per post on the display page'] = 'Nombre maximum de commentaires par publication dans leur page dédié (/display)';
$a->strings['How many comments should be shown on the single view for each post? Default value is 1000.'] = 'Valeur par défaut : 1 000.';
$a->strings['Items per page'] = 'Éléments par page';
$a->strings['Number of items per page in stream pages (network, community, profile/contact statuses, search).'] = 'Nombre d\'éléments par page (concerne les pages Réseau, Communauté, Statuts de profil/contact, Recherche)';
$a->strings['Items per page for mobile devices'] = 'Éléments par page pour les appareils mobiles';
$a->strings['Number of items per page in stream pages (network, community, profile/contact statuses, search) for mobile devices.'] = 'Nombre d\'éléments par page pour les appareils mobiles (concerne les pages Réseau, Communauté, Statuts de profil/contact, Recherche)';
$a->strings['Temp path'] = 'Chemin des fichiers temporaires';
$a->strings['If you have a restricted system where the webserver can\'t access the system temp path, enter another path here.'] = 'Si vous n\'avez pas la possibilité d\'avoir accès au répertoire temp, entrez un autre répertoire ici.';
$a->strings['Only search in tags'] = 'Rechercher seulement dans les étiquettes';
$a->strings['On large systems the text search can slow down the system extremely.'] = 'La recherche textuelle peut ralentir considérablement les systèmes de grande taille.';
$a->strings['Limited search scope'] = 'Champ de recherche limité';
$a->strings['If enabled, searches will only be performed in the data used for the channels and not in all posts.'] = 'Si activé, les recherches ne seront effectuées que dans les données utilisées pour les chaînes et non dans toutes les publications.';
$a->strings['Maximum age of items in the search table'] = 'Âge maximum des éléments dans la table de recherche';
$a->strings['Maximum age of items in the search table in days. Lower values will increase the performance and reduce disk usage. 0 means no age restriction.'] = 'Âge maximum, en jours, des éléments dans la table de recherche. Une valeur plus faible améliorera les performances et réduira l\'utilisation du disque. 0 signifie aucune restriction.';
$a->strings['Generate counts per contact circle when calculating network count'] = 'Générer les comptes par cercle de contacts lors du calcul du nombre de réseaux.';
$a->strings['On systems with users that heavily use contact circles the query can be very expensive.'] = 'Sur les systèmes avec des utilisateurs utilisant fortement les cercles de contact, cette requête peut être très coûteuse.';
$a->strings['Process "view" activities'] = 'Traiter les activités "view"';
$a->strings['"view" activities are mostly geberated by Peertube systems. Per default they are not processed for performance reasons. Only activate this option on performant system.'] = 'Les activités "view" sont principalement gérées par les systèmes Peertube. Par défaut, elles ne sont pas traitées pour des raisons de performance. N\'activez cette option que sur un système performant.';
$a->strings['Days, after which a contact is archived'] = 'Nombre de jours après lesquels un contact est archivé';
$a->strings['Number of days that we try to deliver content or to update the contact data before we archive a contact.'] = 'Nombre de jours pendant lesquels nous essayons d\'envoyer du contenu ou de mettre à jour les données d\'un contact avant d\'archiver celui-ci.';
$a->strings['Maximum number of parallel workers'] = 'Nombre maximum de tâche de fond simultanés';
$a->strings['On shared hosters set this to %d. On larger systems, values of %d are great. Default value is %d.'] = 'Sur un hébergement partagé, mettez %d. Sur des serveurs plus puissants, %d est optimal. La valeur par défaut est %d.';
$a->strings['Maximum load for workers'] = 'Charge maximum pour les tâches de fond';
$a->strings['Maximum load that causes a cooldown before each worker function call.'] = 'Génère un délai d\'attente si une tache de fond atteint la charge maximale. ';
$a->strings['Enable fastlane'] = 'Activer la file prioritaire';
$a->strings['When enabed, the fastlane mechanism starts an additional worker if processes with higher priority are blocked by processes of lower priority.'] = 'Lorsqu\'il est activé, le mécanisme de file prioritaire démarre une tâche de fond additionnelle quand des tâches de fond de haute priorité sont bloquées par des tâches de moindre priorité.';
$a->strings['Decoupled receiver'] = 'Récepteur découplé';
$a->strings['Decouple incoming ActivityPub posts by processing them in the background via a worker process. Only enable this on fast systems.'] = 'Découple les messages ActivityPub entrants en les traitant en arrière-plan par l\'intermédiaire d\'une tâche de fond. N\'activez cette option que sur les systèmes rapides.';
$a->strings['Cron interval'] = 'Intervalle du cron';
$a->strings['Minimal period in minutes between two calls of the "Cron" worker job.'] = 'Durée minimale entre deux appels de la tâche "Cron"';
$a->strings['Worker defer limit'] = 'Limite de report d\'une tâche de fond';
$a->strings['Per default the systems tries delivering for 15 times before dropping it.'] = 'Par défaut, le système tente d\'effectuer un livraison 15 fois avant d\'abandonner.';
$a->strings['Worker fetch limit'] = 'Limite de récupération des tâches';
$a->strings['Number of worker tasks that are fetched in a single query. Higher values should increase the performance, too high values will mostly likely decrease it. Only change it, when you know how to measure the performance of your system.'] = 'Nombre de tâches de fond récupérées en une seule requête. Des valeurs plus élevées devraient augmenter les performances, des valeurs trop élevées les diminueront très probablement. Ne modifiez ces valeurs que lorsque vous savez comment mesurer les performances de votre système.';
$a->strings['Direct relay transfer'] = 'Relai direct';
$a->strings['Enables the direct transfer to other servers without using the relay servers'] = 'Soumet les conversations publiques aux serveurs distants sans passer par le serveur relai.';
$a->strings['Relay scope'] = 'Filtre du relai';
$a->strings['Can be "all" or "tags". "all" means that every public post should be received. "tags" means that only posts with selected tags should be received.'] = '"Tous" signifie que toutes les conversations publiques en provenance du relai sont acceptées. "Étiquettes" signifie que seules les conversations comportant les étiquettes suivantes sont acceptées.';
$a->strings['Disabled'] = 'Désactivé';
$a->strings['all'] = 'Tous';
$a->strings['tags'] = 'étiquettes';
$a->strings['Server tags'] = 'Étiquettes de filtre du relai';
$a->strings['Comma separated list of tags for the "tags" subscription.'] = 'Liste d\'étiquettes autorisées, séparées par des virgules, en provenance des relais.';
$a->strings['Deny Server tags'] = 'Étiquettes refusés';
$a->strings['Comma separated list of tags that are rejected.'] = 'Liste d\'étiquettes refusées, séparées par des virgules, en provenance des relais.';
$a->strings['Maximum amount of tags'] = 'Quantité maximum d\'étiquettes';
$a->strings['Maximum amount of tags in a post before it is rejected as spam. The post has to contain at least one link. Posts from subscribed accounts will not be rejected.'] = 'Quantité maximum d\'étiquettes dans une publication avant que celle-ci soit rejetée comme spam. La publication doit contenir au moins un lien. Les publications des comptes abonnés ne sont pas rejetées.';
$a->strings['Allow user tags'] = 'Inclure les étiquettes des utilisateurs';
$a->strings['If enabled, the tags from the saved searches will used for the "tags" subscription in addition to the "relay_server_tags".'] = 'Ajoute les étiquettes des recherches enregistrées des utilisateurs aux étiquettes autorisés en provenance des relais.';
$a->strings['Deny undetected languages'] = 'Refuser les langues non détectées';
$a->strings['If enabled, posts with undetected languages will be rejected.'] = 'Si actif, les publications avec des langues non détectées seront rejetés.';
$a->strings['Language Quality'] = 'Qualité de la langue';
$a->strings['The minimum language quality that is required to accept the post.'] = 'La qualité de la langue minimale requise pour accepter la publication.';
$a->strings['Number of languages for the language detection'] = 'Nombre de langues pour la détection la de langue';
$a->strings['The system detects a list of languages per post. Only if the desired languages are in the list, the message will be accepted. The higher the number, the more posts will be falsely detected.'] = 'Le système détecte une liste de langues par publication. La publication n\'est acceptée que si les langues souhaitées figurent dans la liste. Plus le nombre est élevé, plus le nombre de publications détectées à tort est important.';
$a->strings['Maximum age of channel'] = 'Age maximal d\'une chaîne';
$a->strings['This defines the maximum age in hours of items that should be displayed in channels. This affects the channel performance.'] = 'Ceci définit l\'âge maximum en heures des éléments qui doivent être affichés dans les chaînes. Cela affecte les performances des chaînes.';
$a->strings['Maximum number of channel posts'] = 'Nombre maximum de publications sur une chaîne';
$a->strings['For performance reasons, the channels use a dedicated table to store content. The higher the value the slower the channels.'] = 'Pour des raisons de performance, les chaînes utilisent une table dédiée pour stocker le contenu. Plus la valeur est élevée, plus les chaînes sont lentes.';
$a->strings['Interaction score days'] = 'Intervalle pour le score d\'interaction';
$a->strings['Number of days that are used to calculate the interaction score.'] = 'Nombre de jours utilisés pour calculer le score d\'interaction.';
$a->strings['Maximum number of posts per author'] = 'Nombre maximum de publications par auteur';
$a->strings['Maximum number of posts per page by author if the contact frequency is set to "Display only few posts". If there are more posts, then the post with the most interactions will be displayed.'] = 'Nombre maximum de publication par page par auteur si la fréquence de contact est réglée sur "Afficher quelques publications". S\'il y a plus de publications, les publications ayant le plus d\'interactions seront affichées.';
$a->strings['Sharer interaction days'] = 'Intervalle d\'interaction de partageurs';
$a->strings['Number of days of the last interaction that are used to define which sharers are used for the "sharers of sharers" channel.'] = 'Nombre de jours depuis la dernière interaction, utilisé pour définir les partageurs utilisés pour la chaîne "Partageurs de partageurs".';
$a->strings['Start Relocation'] = 'Démarrer le déménagement';
$a->strings['Storage backend, %s is invalid.'] = 'Le moteur de stockage %s est invalide.';
$a->strings['Storage backend %s error: %s'] = 'Moteur de stockage %s erreur : %s';
$a->strings['Invalid storage backend setting value.'] = 'Valeur de paramètre de moteur de stockage invalide.';
$a->strings['Current Storage Backend'] = 'Moteur de stockage actuel';
$a->strings['Storage Configuration'] = 'Configuration du stockage';
$a->strings['Storage'] = 'Stockage';
$a->strings['Save & Use storage backend'] = 'Enregistrer et utiliser ce moteur de stockage';
$a->strings['Use storage backend'] = 'Utiliser ce moteur de stockage';
$a->strings['Save & Reload'] = 'Enregistrer et recharger';
$a->strings['This backend doesn\'t have custom settings'] = 'Ce moteur de stockage n\'offre pas de paramètres personnalisés.';
$a->strings['Changing the current backend is prohibited because it is set by an environment variable'] = 'Il n\'est pas possible de changer le moteur de stockage car il est configuré avec une variable d\'environnement.';
$a->strings['Database (legacy)'] = 'Base de donnée (historique)';
$a->strings['Template engine (%s) error: %s'] = 'Moteur de template (%s) erreur : %s';
$a->strings['Your DB still runs with MyISAM tables. You should change the engine type to InnoDB. As Friendica will use InnoDB only features in the future, you should change this! See <a href="%s">here</a> for a guide that may be helpful converting the table engines. You may also use the command <tt>php bin/console.php dbstructure toinnodb</tt> of your Friendica installation for an automatic conversion.<br />'] = '<br />Votre base de donnée comporte des tables MYISAM. Vous devriez changer pour InnoDB car il est prévu d\'utiliser des fonctionnalités spécifiques à InnoDB à l\'avenir. Veuillez consulter <a href="%s">ce guide de conversion</a> pour mettre à jour votre base de donnée. Vous pouvez également exécuter la commande <tt>php bin/console.php dbstructure toinnodb</tt> à la racine de votre répertoire Friendica pour une conversion automatique.';
$a->strings['Your DB still runs with InnoDB tables in the Antelope file format. You should change the file format to Barracuda. Friendica is using features that are not provided by the Antelope format. See <a href="%s">here</a> for a guide that may be helpful converting the table engines. You may also use the command <tt>php bin/console.php dbstructure toinnodb</tt> of your Friendica installation for an automatic conversion.<br />'] = 'Votre BDD utilise encore des tables InnoDB au format de fichiers Antelope. Vous devriez le remplacer par le format Barracuda. Friendica utilise des fonctionnalités qui ne sont pas disponibles dans le format Antelope. Voir <a href="%s">ici</a> pour un guide qui peut être utile pour la conversion du moteur des tables. Vous pouvez également utiliser la commande <tt>php bin/console.php dbstructure toinnodb</tt> de votre installation Friendica pour une conversion automatique.<br />';
$a->strings['Your table_definition_cache is too low (%d). This can lead to the database error "Prepared statement needs to be re-prepared". Please set it at least to %d. See <a href="%s">here</a> for more information.<br />'] = 'Votre table_definition_cache est trop faible (%d). Cela peut conduire à l\'erreur de base de données "Prepared statement needs to be re-prepared". Merci de le définir au minimum à %d. Voir <a href="%s">ici</a> pour plus d\'informations.<br />';
$a->strings['There is a new version of Friendica available for download. Your current version is %1$s, upstream version is %2$s'] = 'Une nouvelle version de Friendica est disponible. Votre version est %1$s, la nouvelle version est %2$s';
$a->strings['The database update failed. Please run "php bin/console.php dbstructure update" from the command line and have a look at the errors that might appear.'] = 'La mise à jour automatique de la base de donnée a échoué. Veuillez exécuter la commande <code>php bin/console.php dbstructure update</code> depuis votre répertoire Friendica et noter les erreurs potentielles.';
$a->strings['The last update failed. Please run "php bin/console.php dbstructure update" from the command line and have a look at the errors that might appear. (Some of the errors are possibly inside the logfile.)'] = 'La dernière mise à jour a échoué. Merci d\'exécuter "php bin/console.php dbstructure update" depuis la ligne de commandes et de surveiller les erreurs qui pourraient survenir (Certaines erreurs pourraient être dans le fichier journal).';
$a->strings['The system.url entry is missing. This is a low level setting and can lead to unexpected behavior. Please add a valid entry as soon as possible in the config file or per console command!'] = 'L\'entrée system.url est manquante. Il s\'agit d\'un paramètre bas niveau qui peut conduire à un comportement non attendu. Merci d\'ajouter une entrée valide dès que possible dans le fichier de configuration ou via la console de commandes !';
$a->strings['The worker was never executed. Please check your database structure!'] = 'La tâche de fond n\'a pas encore été exécutée. Vérifiez la structure de votre base de données.';
$a->strings['The last worker execution was on %s UTC. This is older than one hour. Please check your crontab settings.'] = 'La dernière exécution de la tâche de fond s\'est déroulée à %s, c\'est-à-dire il y a plus d\'une heure. Vérifiez les réglages de crontab.';
$a->strings['Friendica\'s configuration now is stored in config/local.config.php, please copy config/local-sample.config.php and move your config from <code>.htconfig.php</code>. See <a href="%s">the Config help page</a> for help with the transition.'] = 'La configuration de votre site Friendica est maintenant stockée dans le fichier <code>config/local.config.php</code>, veuillez copier le fichier <code>config/local-sample.config.php</code> et transférer votre configuration depuis le fichier <code>.htconfig.php</code>. Veuillez consulter <a href="%s">la page d\'aide de configuration (en anglais)</a> pour vous aider dans la transition.';
$a->strings['Friendica\'s configuration now is stored in config/local.config.php, please copy config/local-sample.config.php and move your config from <code>config/local.ini.php</code>. See <a href="%s">the Config help page</a> for help with the transition.'] = 'La configuration de votre site Friendica est maintenant stockée dans le fichier <code>config/local.config.php</code>, veuillez copier le fichier <code>config/local-sample.config.php</code> et transférer votre configuration depuis le fichier <code>config/local.ini.php</code>. Veuillez consulter <a href="%s">la page d\'aide de configuration (en anglais)</a> pour vous aider dans la transition.';
$a->strings['<a href="%s">%s</a> is not reachable on your system. This is a severe configuration issue that prevents server to server communication. See <a href="%s">the installation page</a> for help.'] = '<a href="%s">%s</a> n\'est pas accessible sur votre site. C\'est un problème de configuration sévère qui empêche toute communication avec les serveurs distants. Veuillez consulter <a href="%s">la page d\'aide à l\'installation</a> (en anglais) pour plus d\'information.';
$a->strings['Friendica\'s system.basepath was updated from \'%s\' to \'%s\'. Please remove the system.basepath from your db to avoid differences.'] = 'Le system.basepath de Friendica a été mis à jour de \'%s\' à \'%s\'. Merci de supprimer le system.basepath de votre base de données pour éviter des différences.';
$a->strings['Friendica\'s current system.basepath \'%s\' is wrong and the config file \'%s\' isn\'t used.'] = 'Le system.basepath actuel de Friendica, \'%s\' est erroné et le fichier de configuration \'%s\' n\'est pas utilisé.';
$a->strings['Friendica\'s current system.basepath \'%s\' is not equal to the config file \'%s\'. Please fix your configuration.'] = 'Le system.basepath \'%s\' actuel de Friendica n\'est pas le même que le fichier de configuration \'%s\'. Merci de corriger votre configuration.';
$a->strings['Message queues'] = 'Files d\'attente des messages';
$a->strings['Server Settings'] = 'Paramètres du site';
$a->strings['Version'] = 'Version';
$a->strings['Active addons'] = 'Extensions actives';
$a->strings['Theme %s disabled.'] = 'Thème %s désactivé.';
$a->strings['Theme %s successfully enabled.'] = 'Thème %s activé avec succès.';
$a->strings['Theme %s failed to install.'] = 'Le thème %s a échoué à s\'installer.';
$a->strings['Screenshot'] = 'Capture d\'écran';
$a->strings['Themes'] = 'Thèmes';
$a->strings['Unknown theme.'] = 'Thème inconnu.';
$a->strings['Themes reloaded'] = 'Thèmes rechargés';
$a->strings['Reload active themes'] = 'Recharger les thèmes actifs';
$a->strings['No themes found on the system. They should be placed in %1$s'] = 'Aucun thème trouvé. Leur emplacement d\'installation est%1$s.';
$a->strings['[Experimental]'] = '[Expérimental]';
$a->strings['[Unsupported]'] = '[Non supporté]';
$a->strings['Display Terms of Service'] = 'Afficher les Conditions d\'Utilisation';
$a->strings['Enable the Terms of Service page. If this is enabled a link to the terms will be added to the registration form and the general information page.'] = 'Active la page de Conditions d\'Utilisation. Un lien vers cette page est ajouté dans le formulaire d\'inscription et la page A Propos.';
$a->strings['Display Privacy Statement'] = 'Afficher la Politique de Confidentialité';
$a->strings['Show some informations regarding the needed information to operate the node according e.g. to <a href="%s" target="_blank" rel="noopener noreferrer">EU-GDPR</a>.'] = 'Afficher quelques informations à propos des données nécessaires pour opérer un nœud conforme par exemple au <a href="%s" target="_blank" rel="noopener noreferrer">RGPD Européen</a>.';
$a->strings['Privacy Statement Preview'] = 'Aperçu de la Politique de Confidentialité';
$a->strings['The Terms of Service'] = 'Conditions d\'Utilisation';
$a->strings['Enter the Terms of Service for your node here. You can use BBCode. Headers of sections should be [h2] and below.'] = 'Saisissez les Conditions d\'Utilisations de votre site. Les BBCodes sont disponibles, les titres commencent à [h2].';
$a->strings['The rules'] = 'Les règles';
$a->strings['Enter your system rules here. Each line represents one rule.'] = 'Saisissez les règles de votre système ici. Chaque ligne représente une règle.';
$a->strings['API endpoint %s %s is not implemented but might be in the future.'] = 'Le point de terminaison de l\'API %s%s n\'est pas implémenté mais pourrait l\'être à l\'avenir.';
$a->strings['Missing parameters'] = 'Paramètres manquants';
$a->strings['Only starting posts can be bookmarked'] = 'Seuls les publications initiales peuvent être ajoutées aux signets';
$a->strings['Only starting posts can be muted'] = 'Les notifications de conversation ne peuvent être ignorées qu\'à partir de la publication initiale';
$a->strings['Posts from %s can\'t be shared'] = 'Les publications de %s ne peuvent pas être partagées';
$a->strings['Only starting posts can be unbookmarked'] = 'Seules les publications initiales peuvent être retirées des signets';
$a->strings['Only starting posts can be unmuted'] = 'Les notifications de conversation ne peuvent être rétablies qu\'à partir de la publication initiale';
$a->strings['Posts from %s can\'t be unshared'] = 'Il n\'est pas possible d\'annuler le partage des publications de %s ';
$a->strings['Contact not found'] = 'Contact non trouvé';
$a->strings['No installed applications.'] = 'Pas d\'application installée.';
$a->strings['Applications'] = 'Applications';
$a->strings['Item was not found.'] = 'Element introuvable.';
$a->strings['Please login to continue.'] = 'Merci de vous connecter pour continuer.';
$a->strings['You don\'t have access to administration pages.'] = 'Vous n\'avez pas accès aux pages d\'administration';
$a->strings['Submanaged account can\'t access the administration pages. Please log back in as the main account.'] = 'Les comptes sous-gérés ne peuvent accéder aux pages d\'administration. Veuillez vous identifier avec votre compte principal à la place.';
$a->strings['Overview'] = 'Synthèse';
$a->strings['Configuration'] = 'Configuration';
$a->strings['Additional features'] = 'Fonctions supplémentaires';
$a->strings['Database'] = 'Base de données';
$a->strings['DB updates'] = 'Mise à jour de la base';
$a->strings['Inspect Deferred Workers'] = 'Tâches de fond reportées';
$a->strings['Inspect worker Queue'] = 'Tâches de fond en attente';
$a->strings['Diagnostics'] = 'Diagnostics';
$a->strings['PHP Info'] = 'PHP Info';
$a->strings['probe address'] = 'Tester une adresse';
$a->strings['check webfinger'] = 'Vérifier le webfinger';
$a->strings['Babel'] = 'Babel';
$a->strings['ActivityPub Conversion'] = 'Conversion ActivityPub';
$a->strings['Addon Features'] = 'Fonctionnalités des extensions';
$a->strings['User registrations waiting for confirmation'] = 'Inscriptions en attente de confirmation';
$a->strings['Too Many Requests'] = 'Trop de requêtes';
$a->strings['Daily posting limit of %d post reached. The post was rejected.'] = [
	0 => 'Limite quotidienne d\'%d publication atteinte. La publication a été rejetée.',
	1 => 'Limite quotidienne de %d publications atteinte.  La publication a été rejetée.',
	2 => 'Limite quotidienne de %d publications atteinte.  La publication a été rejetée.',
];
$a->strings['Weekly posting limit of %d post reached. The post was rejected.'] = [
	0 => 'Limite hebdomadaire d\'%d unique publication atteinte, votre soumission a été rejetée.',
	1 => 'Limite hebdomadaire de %d publications atteinte, votre soumission a été rejetée.',
	2 => 'Limite hebdomadaire de %d publications atteinte, votre soumission a été rejetée.',
];
$a->strings['Monthly posting limit of %d post reached. The post was rejected.'] = [
	0 => 'La limite mensuelle de %d publication a été atteinte. La publication a été refusée.',
	1 => 'La limite mensuelle de %d publications a été atteinte. La publication a été refusée.',
	2 => 'La limite mensuelle de %d publications a été atteinte. La publication a été refusée.',
];
$a->strings['You don\'t have access to moderation pages.'] = 'Vous n\'avez pas accès aux pages de modération.';
$a->strings['Submanaged account can\'t access the moderation pages. Please log back in as the main account.'] = 'Les comptes sous-gérés ne peuvent accéder aux pages de modération. Veuillez vous identifier avec votre compte principal à la place.';
$a->strings['Reports'] = 'Signalements';
$a->strings['Users'] = 'Utilisateurs';
$a->strings['Tools'] = 'Outils';
$a->strings['Contact Blocklist'] = 'Liste de contacts bloqués';
$a->strings['Server Blocklist'] = 'Serveurs bloqués';
$a->strings['Delete Item'] = 'Supprimer un élément';
$a->strings['Item Source'] = 'Source de la publication';
$a->strings['Profile Details'] = 'Détails du profil';
$a->strings['Conversations started'] = 'Discussions commencées';
$a->strings['Only You Can See This'] = 'Vous seul pouvez voir ça';
$a->strings['Scheduled Posts'] = 'Publications programmées';
$a->strings['Posts that are scheduled for publishing'] = 'Publications programmées à l\'avance';
$a->strings['Tips for New Members'] = 'Conseils aux nouveaux venus';
$a->strings['People Search - %s'] = 'Recherche de personne - %s';
$a->strings['Group Search - %s'] = 'Recherche de groupe - %s';
$a->strings['No matches'] = 'Aucune correspondance';
$a->strings['%d result was filtered out because your node blocks the domain it is registered on. You can review the list of domains your node is currently blocking in the <a href="/friendica">About page</a>.'] = [
	0 => '%d résultat a été filtré car votre nœud bloque le domaine sur lequel il est enregistré. Vous pouvez voir la liste des domaines que votre nœud bloque dans la page <a href="/friendica">A propos</a>.',
	1 => '%d résultats ont été filtrés car votre nœud bloque le domaine sur lequel ils sont enregistrés. Vous pouvez voir la liste des domaines que votre nœud bloque dans la page <a href="/friendica">A propos</a>.',
	2 => '%d résultats ont été filtrés car votre nœud bloque le domaine sur lequel ils sont enregistrés. Vous pouvez voir la liste des domaines que votre nœud bloque dans la page <a href="/friendica">A propos</a>.',
];
$a->strings['Account'] = 'Compte';
$a->strings['Two-factor authentication'] = 'Authentification à deux facteurs';
$a->strings['Display'] = 'Affichage';
$a->strings['Social Networks'] = 'Réseaux sociaux';
$a->strings['Manage Accounts'] = 'Gérer vos comptes';
$a->strings['Connected apps'] = 'Applications connectées';
$a->strings['Remote servers'] = 'Serveurs distants';
$a->strings['Import Contacts'] = 'Importer des contacts';
$a->strings['Export personal data'] = 'Exporter';
$a->strings['Remove account'] = 'Supprimer le compte';
$a->strings['This page is missing a url parameter.'] = 'Il manque un paramètre d\'URL à cette adresse.';
$a->strings['The post was created'] = 'La publication a été créée';
$a->strings['Invalid Request'] = 'Requête invalide';
$a->strings['Event id is missing.'] = 'L\'identifiant de l\'évènement est manquant.';
$a->strings['Failed to remove event'] = 'La suppression de l\'évènement a échoué.';
$a->strings['Event can not end before it has started.'] = 'L\'évènement ne peut pas se terminer avant d\'avoir commencé.';
$a->strings['Event title and start time are required.'] = 'Vous devez donner un nom et un horaire de début à l\'évènement.';
$a->strings['Starting date and Title are required.'] = 'La date de début et le titre sont requis.';
$a->strings['Event Starts:'] = 'Début de l\'évènement :';
$a->strings['Required'] = 'Requis';
$a->strings['Finish date/time is not known or not relevant'] = 'Date / heure de fin inconnue ou sans objet';
$a->strings['Event Finishes:'] = 'Fin de l\'évènement :';
$a->strings['Title (BBCode not allowed)'] = 'Titre (BBCode non autorisé)';
$a->strings['Description (BBCode allowed)'] = 'Description (BBCode autorisé)';
$a->strings['Location (BBCode not allowed)'] = 'Localisation (BBCode non autorisé)';
$a->strings['Share this event'] = 'Partager cet évènement';
$a->strings['Basic'] = 'Simple';
$a->strings['This calendar format is not supported'] = 'Format de calendrier inconnu';
$a->strings['No exportable data found'] = 'Rien à exporter';
$a->strings['calendar'] = 'calendrier';
$a->strings['Events'] = 'Évènements';
$a->strings['View'] = 'Vue';
$a->strings['Create New Event'] = 'Créer un nouvel évènement';
$a->strings['list'] = 'liste';
$a->strings['Could not create circle.'] = 'Impossible de créer le cercle.';
$a->strings['Circle not found.'] = 'Cercle introuvable.';
$a->strings['Circle name was not changed.'] = 'Le nom du cercle n\'a pas été modifié.';
$a->strings['Unknown circle.'] = 'Cercle inconnu.';
$a->strings['Contact not found.'] = 'Contact introuvable.';
$a->strings['Invalid contact.'] = 'Contact invalide.';
$a->strings['Contact is deleted.'] = 'Le contact a été supprimé.';
$a->strings['Unable to add the contact to the circle.'] = 'Impossible d\'ajouter le contact au cercle.';
$a->strings['Contact successfully added to circle.'] = 'Le contact a été ajouté au cercle.';
$a->strings['Unable to remove the contact from the circle.'] = 'Impossible de retirer le contact du cercle.';
$a->strings['Contact successfully removed from circle.'] = 'Le contact a été retiré du cercle.';
$a->strings['Bad request.'] = 'Requête invalide.';
$a->strings['Save Circle'] = 'Enregistrer le cercle';
$a->strings['Filter'] = 'Filtre';
$a->strings['Create a circle of contacts/friends.'] = 'Créer un cercle de contacts/amis.';
$a->strings['Unable to remove circle.'] = 'Impossible de supprimer le cercle.';
$a->strings['Delete Circle'] = 'Supprimer le Cercle';
$a->strings['Edit Circle Name'] = 'Modifier le Nom du Cercle';
$a->strings['Members'] = 'Membres';
$a->strings['Circle is empty'] = 'Le cercle est vide';
$a->strings['Remove contact from circle'] = 'Retirer ce contact du cercle';
$a->strings['Click on a contact to add or remove.'] = 'Cliquez sur un contact pour l\'ajouter ou le supprimer.';
$a->strings['Add contact to circle'] = 'Ajouter ce contact au cercle';
$a->strings['%d contact edited.'] = [
	0 => '%d contact mis à jour.',
	1 => '%d contacts mis à jour.',
	2 => '%d contacts mis à jour.',
];
$a->strings['Show all contacts'] = 'Montrer tous les contacts';
$a->strings['Pending'] = 'En attente';
$a->strings['Only show pending contacts'] = 'Ne montrer que les demandes d\'abonnement';
$a->strings['Blocked'] = 'Bloqués';
$a->strings['Only show blocked contacts'] = 'Ne montrer que les contacts bloqués';
$a->strings['Ignored'] = 'Ignorés';
$a->strings['Only show ignored contacts'] = 'Ne montrer que les contacts ignorés';
$a->strings['Collapsed'] = 'Réduits';
$a->strings['Only show collapsed contacts'] = 'N\'afficher que les contacts réduits';
$a->strings['Archived'] = 'Archivés';
$a->strings['Only show archived contacts'] = 'Ne montrer que les contacts archivés';
$a->strings['Hidden'] = 'Cachés';
$a->strings['Only show hidden contacts'] = 'Ne montrer que les contacts masqués';
$a->strings['Organize your contact circles'] = 'Organisez vos cercles de contact';
$a->strings['Search your contacts'] = 'Rechercher dans vos contacts';
$a->strings['Results for: %s'] = 'Résultats pour : %s';
$a->strings['Update'] = 'Mises à jour';
$a->strings['Unblock'] = 'Débloquer';
$a->strings['Unignore'] = 'Ne plus ignorer';
$a->strings['Uncollapse'] = 'Ne plus réduire';
$a->strings['Batch Actions'] = 'Actions multiples';
$a->strings['Conversations started by this contact'] = 'Conversations entamées par ce contact';
$a->strings['Posts and Comments'] = 'Publications et commentaires';
$a->strings['Individual Posts and Replies'] = 'Publications et réponses individuelles';
$a->strings['Posts containing media objects'] = 'Publications contenant des objets média';
$a->strings['View all known contacts'] = 'Voir tous les contacts connus';
$a->strings['Advanced Contact Settings'] = 'Réglages avancés du contact';
$a->strings['Mutual Friendship'] = 'Relation réciproque';
$a->strings['is a fan of yours'] = 'Vous suit';
$a->strings['you are a fan of'] = 'Vous le/la suivez';
$a->strings['Pending outgoing contact request'] = 'Demande d\'abonnement distant en attente';
$a->strings['Pending incoming contact request'] = 'Demande d\'abonnement à votre compte en attente';
$a->strings['Visit %s\'s profile [%s]'] = 'Visiter le profil de %s [%s]';
$a->strings['Contact update failed.'] = 'Impossible d\'appliquer les réglages.';
$a->strings['Return to contact editor'] = 'Retour à l\'éditeur de contact';
$a->strings['Name'] = 'Nom';
$a->strings['Account Nickname'] = 'Pseudo du compte';
$a->strings['Account URL'] = 'URL du compte';
$a->strings['Poll/Feed URL'] = 'Téléverser des photos';
$a->strings['New photo from this URL'] = 'Nouvelle photo depuis cette URL';
$a->strings['No known contacts.'] = 'Aucun contact connu.';
$a->strings['No common contacts.'] = 'Aucun contact en commun.';
$a->strings['Follower (%s)'] = [
	0 => 'Abonné (%s)',
	1 => 'Abonnés (%s)',
	2 => 'Abonnés (%s)',
];
$a->strings['Following (%s)'] = [
	0 => 'Abonnement (%s)',
	1 => 'Abonnements (%s)',
	2 => 'Abonnements (%s)',
];
$a->strings['Mutual friend (%s)'] = [
	0 => 'Contact mutuel (%s)',
	1 => 'Contacts mutuels (%s)',
	2 => 'Contacts mutuels (%s)',
];
$a->strings['These contacts both follow and are followed by <strong>%s</strong>.'] = 'Ces contacts sont mutuellement abonnés avec <strong>%s</strong>.';
$a->strings['Common contact (%s)'] = [
	0 => 'Contact commun (%s)',
	1 => 'Contacts communs (%s)',
	2 => 'Contacts communs (%s)',
];
$a->strings['Both <strong>%s</strong> and yourself have publicly interacted with these contacts (follow, comment or likes on public posts).'] = '<strong>%s</strong> et vous-mêmes avez interagi publiquement avec ces contacts (abonnement, commentaires ou "J\'aime" sur des publications publiques)';
$a->strings['Contact (%s)'] = [
	0 => 'Contact (%s)',
	1 => 'Contacts (%s)',
	2 => 'Contacts (%s)',
];
$a->strings['Access denied.'] = 'Accès refusé.';
$a->strings['Submit Request'] = 'Envoyer la requête';
$a->strings['You already added this contact.'] = 'Vous avez déjà ajouté ce contact.';
$a->strings['The network type couldn\'t be detected. Contact can\'t be added.'] = 'Impossible de détecter le type de réseau. Le contact ne peut pas être ajouté.';
$a->strings['Diaspora support isn\'t enabled. Contact can\'t be added.'] = 'Le support de Diaspora est désactivé. Le contact ne peut pas être ajouté.';
$a->strings['Please answer the following:'] = 'Merci de répondre à ce qui suit :';
$a->strings['Your Identity Address:'] = 'Votre adresse d\'identité :';
$a->strings['Profile URL'] = 'URL du Profil';
$a->strings['Tags:'] = 'Étiquettes :';
$a->strings['%s knows you'] = '%s vous connaît';
$a->strings['Add a personal note:'] = 'Ajouter une note personnelle :';
$a->strings['Posts and Replies'] = 'Publications et réponses';
$a->strings['The contact could not be added.'] = 'Le contact n\'a pas pu être ajouté.';
$a->strings['Invalid request.'] = 'Requête invalide.';
$a->strings['No keywords to match. Please add keywords to your profile.'] = 'Aucun mot-clé ne correspond. Merci d\'ajouter des mots-clés à votre profil.';
$a->strings['Profile Match'] = 'Correpondance de profils';
$a->strings['Failed to update contact record.'] = 'Échec de mise à jour du contact.';
$a->strings['Contact has been unblocked'] = 'Le contact n\'est plus bloqué';
$a->strings['Contact has been blocked'] = 'Le contact a été bloqué';
$a->strings['Contact has been unignored'] = 'Le contact n\'est plus ignoré';
$a->strings['Contact has been ignored'] = 'Le contact a été ignoré';
$a->strings['Contact has been uncollapsed'] = 'Le contact n\'est plus réduit';
$a->strings['Contact has been collapsed'] = 'Le contact a été réduit';
$a->strings['You are mutual friends with %s'] = 'Vous êtes ami (et réciproquement) avec %s';
$a->strings['You are sharing with %s'] = 'Vous partagez avec %s';
$a->strings['%s is sharing with you'] = '%s partage avec vous';
$a->strings['Private communications are not available for this contact.'] = 'Les communications privées ne sont pas disponibles pour ce contact.';
$a->strings['This contact is on a server you ignored.'] = 'Ce contact est sur un serveur que vous ignorez.';
$a->strings['Never'] = 'Jamais';
$a->strings['(Update was not successful)'] = '(Échec de la mise à jour)';
$a->strings['(Update was successful)'] = '(Mise à jour effectuée avec succès)';
$a->strings['Suggest friends'] = 'Suggérer des abonnements';
$a->strings['Network type: %s'] = 'Type de réseau %s';
$a->strings['Communications lost with this contact!'] = 'Communications perdues avec ce contact !';
$a->strings['Fetch further information for feeds'] = 'Chercher plus d\'informations pour les flux';
$a->strings['Fetch information like preview pictures, title and teaser from the feed item. You can activate this if the feed doesn\'t contain much text. Keywords are taken from the meta header in the feed item and are posted as hash tags.'] = 'Récupère des informations comme les prévisualisations d\'images, les titres et les accroches depuis l\'élément du flux de discussion. Vous pouvez activer ceci si le flux ne contient pas beaucoup de texte. Les mots clés sont récupérés de la balise meta de l\'élément du flux de discussion et sont publiés comme étiquettes.';
$a->strings['Fetch information'] = 'Récupérer informations';
$a->strings['Fetch keywords'] = 'Récupérer les mots-clés';
$a->strings['Fetch information and keywords'] = 'Récupérer informations';
$a->strings['No mirroring'] = 'Pas de miroir';
$a->strings['Mirror as my own posting'] = 'Refléter les publications de ce profil comme les vôtres';
$a->strings['Native reshare'] = 'Partage natif';
$a->strings['Contact Information / Notes'] = 'Informations de contact / Notes';
$a->strings['Contact Settings'] = 'Paramètres du Contact';
$a->strings['Contact'] = 'Contact';
$a->strings['Their personal note'] = 'Leur note personnelle';
$a->strings['Edit contact notes'] = 'Éditer les notes des contacts';
$a->strings['Block/Unblock contact'] = 'Bloquer/débloquer ce contact';
$a->strings['Ignore contact'] = 'Ignorer ce contact';
$a->strings['View conversations'] = 'Voir les conversations';
$a->strings['Last update:'] = 'Dernière mise-à-jour :';
$a->strings['Update public posts'] = 'Fréquence de mise à jour:';
$a->strings['Update now'] = 'Mettre à jour';
$a->strings['Awaiting connection acknowledge'] = 'En attente de confirmation d\'abonnement';
$a->strings['Currently blocked'] = 'Actuellement bloqué';
$a->strings['Currently ignored'] = 'Actuellement ignoré';
$a->strings['Currently collapsed'] = 'Actuellement réduit';
$a->strings['Currently archived'] = 'Actuellement archivé';
$a->strings['Manage remote servers'] = 'Gérer les serveurs distants';
$a->strings['Hide this contact from others'] = 'Cacher ce contact aux autres';
$a->strings['Replies/likes to your public posts <strong>may</strong> still be visible'] = 'Les réponses et "j\'aime" à vos publications publiques <strong>peuvent</strong> être toujours visibles';
$a->strings['Notification for new posts'] = 'Notification des nouvelles publications';
$a->strings['Send a notification of every new post of this contact'] = 'Envoyer une notification de chaque nouveau message en provenance de ce contact';
$a->strings['Keyword Deny List'] = 'Liste de mot-clefs interdits';
$a->strings['Comma separated list of keywords that should not be converted to hashtags, when "Fetch information and keywords" is selected'] = 'Liste de mots-clés, séparés par des virgules, qui ne doivent pas être converti en étiquettes lorsque « Récupérer informations et mots-clés » est sélectionné.';
$a->strings['Actions'] = 'Actions';
$a->strings['Status'] = 'Statut';
$a->strings['Mirror postings from this contact'] = 'Copier les publications de ce contact';
$a->strings['Mark this contact as remote_self, this will cause friendica to repost new entries from this contact.'] = 'Marquer ce contact comme étant remote_self, friendica republiera alors les nouvelles entrées de ce contact.';
$a->strings['Channel Settings'] = 'Paramètres de Chaîne';
$a->strings['Frequency of this contact in relevant channels'] = 'Fréquence de ce contact dans les chaînes pertinentes';
$a->strings['Depending on the type of the channel not all posts from this contact are displayed. By default, posts need to have a minimum amount of interactions (comments, likes) to show in your channels. On the other hand there can be contacts who flood the channel, so you might want to see only some of their posts. Or you don\'t want to see their content at all, but you don\'t want to block or hide the contact completely.'] = 'Selon le type de chaîne, les publications de ce contact ne seront pas toutes affichées. Par défaut, les publications ont besoins d\'avoir un minimum d\'interaction (commentaires, aimes) pour être visible dans vos chaînes. D\'un autre côté, il peut y avoir des contacts qui inondent la chaîne, vous souhaiteriez donc ne voir que certaines de ces publications. Ou vous souhaiteriez ne pas les voir du tout, sans pour autant bloquer ou masquer complètement le contact.';
$a->strings['Default frequency'] = 'Fréquence par défaut';
$a->strings['Posts by this contact are displayed in the "for you" channel if you interact often with this contact or if a post reached some level of interaction.'] = 'Les publications de ce contact sont affichées dans la chaîne "Pour vous" si vous interagissez souvent avec ce contact ou si une publication atteint un certain niveau d\'interaction.';
$a->strings['Display all posts of this contact'] = 'Afficher toutes les publications de ce contact';
$a->strings['All posts from this contact will appear on the "for you" channel'] = 'Toutes les publications de ce contact apparaîtront dans la chaîne "Pour vous".';
$a->strings['Display only few posts'] = 'Afficher quelques publications';
$a->strings['When a contact creates a lot of posts in a short period, this setting reduces the number of displayed posts in every channel.'] = 'Lorsqu\'un contact créé beaucoup de publications en peu de temps, ce paramètre réduit le nombre de publications affichées dans chaque chaîne.';
$a->strings['Never display posts'] = 'Afficher aucune publication';
$a->strings['Posts from this contact will never be displayed in any channel'] = 'Les publications de ce contact n\'apparaîtront jamais dans les chaînes';
$a->strings['Channel Only'] = 'Chaîne uniquement';
$a->strings['If enabled, posts from this contact will only appear in channels and network streams in circles, but not in the general network stream.'] = 'Si activé, les publications de ce contact n\'apparaîtront que dans les canaux et les flux réseau des cercles, mais pas dans le flux réseau général.';
$a->strings['Refetch contact data'] = 'Récupérer à nouveau les données de contact';
$a->strings['Toggle Blocked status'] = '(dés)activer l\'état "bloqué"';
$a->strings['Toggle Ignored status'] = '(dés)activer l\'état "ignoré"';
$a->strings['Toggle Collapsed status'] = 'Commuter le statut réduit';
$a->strings['Revoke Follow'] = 'Révoquer le suivi';
$a->strings['Revoke the follow from this contact'] = 'Empêcher ce contact de vous suivre ';
$a->strings['Bad Request.'] = 'Mauvaise requête.';
$a->strings['Contact is being deleted.'] = 'Le contact est en cours de suppression.';
$a->strings['Follow was successfully revoked.'] = 'Le suivi a été révoqué avec succès.';
$a->strings['Do you really want to revoke this contact\'s follow? This cannot be undone and they will have to manually follow you back again.'] = 'Voulez-vous vraiment révoquer l\'abonnement de ce contact ? Cela ne peut être annulé et il devra se réabonner à vous manuellement.';
$a->strings['No suggestions available. If this is a new site, please try again in 24 hours.'] = 'Aucune suggestion. Si ce site est récent, merci de recommencer dans 24h.';
$a->strings['You aren\'t following this contact.'] = 'Vous ne suivez pas ce contact.';
$a->strings['Unfollowing is currently not supported by your network.'] = 'Le désabonnement n\'est actuellement pas supporté par votre réseau.';
$a->strings['Disconnect/Unfollow'] = 'Se déconnecter/Ne plus suivre';
$a->strings['Contact was successfully unfollowed'] = 'Le contact n\'est maintenant plus suivi';
$a->strings['Unable to unfollow this contact, please contact your administrator'] = 'Impossible de ne plus suivre ce contact, merci de contacter votre administrateur';
$a->strings['No results.'] = 'Aucun résultat.';
$a->strings['Channel not available.'] = 'Chaîne non disponible.';
$a->strings['This community stream shows all public posts received by this node. They may not reflect the opinions of this node’s users.'] = 'Ce fil communautaire liste toutes les publications publiques reçues par ce serveur. Elles ne reflètent pas nécessairement les opinions personnelles des utilisateurs locaux.';
$a->strings['Community option not available.'] = 'L\'option communauté n\'est pas disponible';
$a->strings['Not available.'] = 'Indisponible.';
$a->strings['No such circle'] = 'Cercle inexistant';
$a->strings['Circle: %s'] = 'Cercle : %s';
$a->strings['Error %d (%s) while fetching the timeline.'] = 'Erreur %d (%s) lors de la récupération du flux.';
$a->strings['Network feed not available.'] = 'Flux du réseau non disponible.';
$a->strings['Include'] = 'Inclure';
$a->strings['Hide'] = 'Masquer';
$a->strings['Credits'] = 'Remerciements';
$a->strings['Friendica is a community project, that would not be possible without the help of many people. Here is a list of those who have contributed to the code or the translation of Friendica. Thank you all!'] = 'Friendica est un projet communautaire, qui ne serait pas possible sans l\'aide de beaucoup de gens. Voici une liste de ceux qui ont contribué au code ou à la traduction de Friendica. Merci à tous!';
$a->strings['Formatted'] = 'Mis en page';
$a->strings['Activity'] = 'Activité';
$a->strings['Object data'] = 'Données de l\'object';
$a->strings['Result Item'] = 'Résultat';
$a->strings['Error'] = [
	0 => 'Erreur',
	1 => 'Erreurs',
	2 => 'Erreurs',
];
$a->strings['Source activity'] = 'Activité source';
$a->strings['Source input'] = 'Saisie source';
$a->strings['BBCode::toPlaintext'] = 'BBCode::toPlaintext';
$a->strings['BBCode::convert (raw HTML)'] = 'BBCode::convert (code HTML)';
$a->strings['BBCode::convert (hex)'] = 'BBCode::convert (hex)';
$a->strings['BBCode::convert'] = 'BBCode::convert';
$a->strings['BBCode::convert => HTML::toBBCode'] = 'BBCode::convert => HTML::toBBCode';
$a->strings['BBCode::toMarkdown'] = 'BBCode::toMarkdown';
$a->strings['BBCode::toMarkdown => Markdown::convert (raw HTML)'] = 'BBCode::toMarkdown => Markdown::convert (HTML pur)';
$a->strings['BBCode::toMarkdown => Markdown::convert'] = 'BBCode::toMarkdown => Markdown::convert';
$a->strings['BBCode::toMarkdown => Markdown::toBBCode'] = 'BBCode::toMarkdown => Markdown::toBBCode';
$a->strings['BBCode::toMarkdown =>  Markdown::convert => HTML::toBBCode'] = 'BBCode::toMarkdown =>  Markdown::convert => HTML::toBBCode';
$a->strings['Item Body'] = 'Corps du message';
$a->strings['Item Tags'] = 'Étiquettes du messages';
$a->strings['PageInfo::appendToBody'] = 'PageInfo::appendToBody';
$a->strings['PageInfo::appendToBody => BBCode::convert (raw HTML)'] = 'PageInfo::appendToBody => BBCode::convert (code HTML)';
$a->strings['PageInfo::appendToBody => BBCode::convert'] = 'PageInfo::appendToBody => BBCode::convert';
$a->strings['Source input (Diaspora format)'] = 'Saisie source (format Diaspora)';
$a->strings['Source input (Markdown)'] = 'Source (Markdown)';
$a->strings['Markdown::convert (raw HTML)'] = 'Markdown::convert (code HTML)';
$a->strings['Markdown::convert'] = 'Markdown::convert';
$a->strings['Markdown::toBBCode'] = 'Markdown::toBBCode';
$a->strings['Raw HTML input'] = 'Saisie code HTML';
$a->strings['HTML Input'] = 'Code HTML';
$a->strings['HTML Purified (raw)'] = 'HTML purifié (code)';
$a->strings['HTML Purified (hex)'] = 'HTML purifié (hexadecimal)';
$a->strings['HTML Purified'] = 'HTML purifié';
$a->strings['HTML::toBBCode'] = 'HTML::toBBCode';
$a->strings['HTML::toBBCode => BBCode::convert'] = 'HTML::toBBCode => BBCode::convert';
$a->strings['HTML::toBBCode => BBCode::convert (raw HTML)'] = 'HTML::toBBCode => BBCode::convert (code HTML)';
$a->strings['HTML::toBBCode => BBCode::toPlaintext'] = 'HTML::toBBCode => BBCode::toPlaintext';
$a->strings['HTML::toMarkdown'] = 'HTML::toMarkdown';
$a->strings['HTML::toPlaintext'] = 'HTML::toPlaintext';
$a->strings['HTML::toPlaintext (compact)'] = 'HTML::toPlaintext (compact)';
$a->strings['Decoded post'] = 'Publication décodée';
$a->strings['Post array before expand entities'] = 'Tableau de la publication avant de résoudre les entités';
$a->strings['Babel Diagnostic'] = 'Disagnostic Babel';
$a->strings['Source text'] = 'Texte source';
$a->strings['BBCode'] = 'BBCode';
$a->strings['Markdown'] = 'Markdown';
$a->strings['HTML'] = 'HTML';
$a->strings['Twitter Source / Tweet URL (requires API key)'] = 'Source Twitter / URL du tweet (requiert une clé d\'API)';
$a->strings['You must be logged in to use this module'] = 'Vous devez être identifié pour accéder à cette fonctionnalité';
$a->strings['Source URL'] = 'URL Source';
$a->strings['Time Conversion'] = 'Conversion temporelle';
$a->strings['Friendica provides this service for sharing events with other networks and friends in unknown timezones.'] = 'Friendica fournit ce service pour partager des évènements avec vos contacts indépendament de leur fuseau horaire.';
$a->strings['UTC time: %s'] = 'Temps UTC : %s';
$a->strings['Current timezone: %s'] = 'Zone de temps courante : %s';
$a->strings['Converted localtime: %s'] = 'Temps local converti : %s';
$a->strings['Please select your timezone:'] = 'Sélectionner votre zone :';
$a->strings['Only logged in users are permitted to perform a probing.'] = 'Le sondage de profil est réservé aux utilisateurs identifiés.';
$a->strings['Probe Diagnostic'] = 'Diasgnostic Sonde';
$a->strings['Output'] = 'Sortie';
$a->strings['Lookup address'] = 'Addresse de sondage';
$a->strings['Webfinger Diagnostic'] = 'Diagnostic Webfinger';
$a->strings['Lookup address:'] = 'Tester l\'adresse:';
$a->strings['No entries (some entries may be hidden).'] = 'Aucune entrée (certaines peuvent être cachées).';
$a->strings['Find on this site'] = 'Trouver sur ce site';
$a->strings['Results for:'] = 'Résultats pour :';
$a->strings['Site Directory'] = 'Annuaire local';
$a->strings['Item was not deleted'] = 'L\'élément n\'a pas été supprimé';
$a->strings['Item was not removed'] = 'L\'élément n\'a pas été retiré';
$a->strings['- select -'] = '- choisir -';
$a->strings['Suggested contact not found.'] = 'Contact suggéré non trouvé';
$a->strings['Friend suggestion sent.'] = 'Suggestion d\'abonnement envoyée.';
$a->strings['Suggest Friends'] = 'Suggérer des amis/contacts';
$a->strings['Suggest a friend for %s'] = 'Suggérer un ami/contact pour %s';
$a->strings['Installed addons/apps:'] = 'Extensions/Applications installées :';
$a->strings['No installed addons/apps'] = 'Aucune extension/application n\'est installée';
$a->strings['Read about the <a href="%1$s/tos">Terms of Service</a> of this node.'] = 'Lire les <a href="%1$s/tos">Conditions d\'utilisation</a> de ce nœud.';
$a->strings['On this server the following remote servers are blocked.'] = 'Sur ce serveur, les serveurs suivants sont sur liste noire.';
$a->strings['Reason for the block'] = 'Raison du blocage';
$a->strings['Download this list in CSV format'] = 'Télécharger cette liste au format CSV';
$a->strings['This is Friendica, version %s that is running at the web location %s. The database version is %s, the post update version is %s.'] = 'Vous êtes sur Friendica, version %s qui fonctionne à l\'emplacement web %s. La version de la base de données est %s, la version de mise à jour des publications est %s.';
$a->strings['Please visit <a href="https://friendi.ca">Friendi.ca</a> to learn more about the Friendica project.'] = 'Rendez-vous sur <a href="https://friendi.ca">Friendi.ca</a> pour en savoir plus sur le projet Friendica.';
$a->strings['Bug reports and issues: please visit'] = 'Pour les rapports de bugs : rendez vous sur';
$a->strings['the bugtracker at github'] = 'le bugtracker sur GitHub';
$a->strings['Suggestions, praise, etc. - please email "info" at "friendi - dot - ca'] = 'Suggestions, souhaits, etc. - merci d\'écrire à "info" at "friendi - dot - ca';
$a->strings['No profile'] = 'Aucun profil';
$a->strings['Method Not Allowed.'] = 'Méthode non autorisée.';
$a->strings['Help:'] = 'Aide :';
$a->strings['Welcome to %s'] = 'Bienvenue sur %s';
$a->strings['Friendica Communications Server - Setup'] = 'Serveur de média social Friendica - Installation';
$a->strings['System check'] = 'Vérifications système';
$a->strings['Requirement not satisfied'] = 'Exigence non remplie';
$a->strings['Optional requirement not satisfied'] = 'Exigence facultative non remplie';
$a->strings['OK'] = 'OK';
$a->strings['Next'] = 'Suivant';
$a->strings['Check again'] = 'Vérifier à nouveau';
$a->strings['Base settings'] = 'Paramètres de base';
$a->strings['Base path to installation'] = 'Chemin de base de l\'installation';
$a->strings['If the system cannot detect the correct path to your installation, enter the correct path here. This setting should only be set if you are using a restricted system and symbolic links to your webroot.'] = 'Si le système ne peut pas détecter le chemin de l\'installation, entrez le bon chemin ici. Ce paramètre doit être utilisé uniquement si vous avez des accès restreints à votre système et que vous n\'avez qu\'un lien symbolique vers le répertoire web.';
$a->strings['The Friendica system URL'] = 'L\'URL du système Friendica';
$a->strings['Overwrite this field in case the system URL determination isn\'t right, otherwise leave it as is.'] = 'Modifiez ce champ au cas où l\'URL du système n\'est pas la bonne, sinon laissez le tel quel.';
$a->strings['Database connection'] = 'Connexion à la base de données';
$a->strings['In order to install Friendica we need to know how to connect to your database.'] = 'Pour installer Friendica, nous avons besoin de savoir comment contacter votre base de données.';
$a->strings['Please contact your hosting provider or site administrator if you have questions about these settings.'] = 'Merci de vous tourner vers votre hébergeur et/ou administrateur pour toute question concernant ces réglages.';
$a->strings['The database you specify below should already exist. If it does not, please create it before continuing.'] = 'La base de données que vous spécifierez doit exister. Si ce n\'est pas encore le cas, merci de la créer avant de continuer.';
$a->strings['Database Server Name'] = 'Serveur de base de données';
$a->strings['Database Login Name'] = 'Nom d\'utilisateur de la base';
$a->strings['Database Login Password'] = 'Mot de passe de la base';
$a->strings['For security reasons the password must not be empty'] = 'Pour des raisons de sécurité, le mot de passe ne peut pas être vide.';
$a->strings['Database Name'] = 'Nom de la base';
$a->strings['Please select a default timezone for your website'] = 'Sélectionner un fuseau horaire par défaut pour votre site';
$a->strings['Site settings'] = 'Réglages du site';
$a->strings['Site administrator email address'] = 'Adresse de courriel de l\'administrateur du site';
$a->strings['Your account email address must match this in order to use the web admin panel.'] = 'Votre adresse de courriel doit correspondre à celle-ci pour pouvoir utiliser l\'interface d\'administration.';
$a->strings['System Language:'] = 'Langue système :';
$a->strings['Set the default language for your Friendica installation interface and to send emails.'] = 'Définit la langue par défaut pour l\'interface de votre instance Friendica et les courriels envoyés.';
$a->strings['Your Friendica site database has been installed.'] = 'La base de données de votre site Friendica a bien été installée.';
$a->strings['Installation finished'] = 'Installation terminée';
$a->strings['<h1>What next</h1>'] = '<h1>Ensuite</h1>';
$a->strings['IMPORTANT: You will need to [manually] setup a scheduled task for the worker.'] = 'IMPORTANT: vous devrez ajouter [manuellement] une tâche planifiée pour la tâche de fond.';
$a->strings['Go to your new Friendica node <a href="%s/register">registration page</a> and register as new user. Remember to use the same email you have entered as administrator email. This will allow you to enter the site admin panel.'] = 'Rendez-vous sur la <a href="%s/register">page d\'inscription</a> de votre nouveau nœud Friendica et inscrivez vous en tant que nouvel utilisateur. Rappelez-vous de bien utiliser la même adresse de courriel que celle que vous avez utilisée en tant qu\'adresse d\'administrateur. Cela vous permettra d\'accéder au panel d\'administration du site.';
$a->strings['Total invitation limit exceeded.'] = 'La limite d\'invitation totale est éxédée.';
$a->strings['%s : Not a valid email address.'] = '%s : Adresse de courriel invalide.';
$a->strings['Please join us on Friendica'] = 'Rejoignez-nous sur Friendica';
$a->strings['Invitation limit exceeded. Please contact your site administrator.'] = 'Limite d\'invitation exédée. Veuillez contacter l\'administrateur de votre site.';
$a->strings['%s : Message delivery failed.'] = '%s : L\'envoi du message a échoué.';
$a->strings['%d message sent.'] = [
	0 => '%d message envoyé.',
	1 => '%d messages envoyés.',
	2 => '%d messages envoyés.',
];
$a->strings['You have no more invitations available'] = 'Vous n\'avez plus d\'invitations disponibles';
$a->strings['Visit %s for a list of public sites that you can join. Friendica members on other sites can all connect with each other, as well as with members of many other social networks.'] = 'Visitez %s pour une liste des sites publics que vous pouvez rejoindre. Les membres de Friendica appartenant à d\'autres sites peuvent s\'interconnecter, ainsi qu\'avec les membres de plusieurs autres réseaux sociaux.';
$a->strings['To accept this invitation, please visit and register at %s or any other public Friendica website.'] = 'Pour accepter cette invitation, merci d\'aller vous inscrire sur %s, ou n\'importe quel autre site Friendica public.';
$a->strings['Friendica sites all inter-connect to create a huge privacy-enhanced social web that is owned and controlled by its members. They can also connect with many traditional social networks. See %s for a list of alternate Friendica sites you can join.'] = 'Les sites Friendica sont tous interconnectés pour créer un immense réseau social respectueux de la vie privée, possédé et contrôllé par ses membres. Ils peuvent également interagir avec plusieurs réseaux sociaux traditionnels. Voir %s pour une liste d\'autres sites Friendica que vous pourriez rejoindre.';
$a->strings['Our apologies. This system is not currently configured to connect with other public sites or invite members.'] = 'Toutes nos excuses. Ce système n\'est pas configuré pour se connecter à d\'autres sites publics ou inviter de nouveaux membres.';
$a->strings['Friendica sites all inter-connect to create a huge privacy-enhanced social web that is owned and controlled by its members. They can also connect with many traditional social networks.'] = 'Les instances Friendica sont interconnectées pour créer un immense réseau social possédé et contrôlé par ses membres, et qui respecte leur vie privée. Ils peuvent aussi s\'interconnecter avec d\'autres réseaux sociaux traditionnels.';
$a->strings['To accept this invitation, please visit and register at %s.'] = 'Pour accepter cette invitation, rendez-vous sur %s et inscrivez-vous.';
$a->strings['Send invitations'] = 'Envoyer des invitations';
$a->strings['Enter email addresses, one per line:'] = 'Entrez les adresses de courriel, une par ligne :';
$a->strings['You are cordially invited to join me and other close friends on Friendica - and help us to create a better social web.'] = 'Vous êtes cordialement invité à me rejoindre sur Friendica, et nous aider ainsi à créer un meilleur web social.';
$a->strings['You will need to supply this invitation code: $invite_code'] = 'Vous devrez fournir ce code d\'invitation : $invite_code';
$a->strings['Once you have registered, please connect with me via my profile page at:'] = 'Une fois inscrit, connectez-vous à la page de mon profil sur :';
$a->strings['For more information about the Friendica project and why we feel it is important, please visit http://friendi.ca'] = 'Pour plus d\'information sur Friendica et les valeurs que nous défendons, veuillez consulter http://friendi.ca';
$a->strings['Please enter a post body.'] = 'Veuillez saisir un corps de texte.';
$a->strings['This feature is only available with the frio theme.'] = 'Cette page ne fonctionne qu\'avec le thème "frio" activé.';
$a->strings['Compose new personal note'] = 'Composer une nouvelle note personnelle';
$a->strings['Compose new post'] = 'Composer une nouvelle publication';
$a->strings['Visibility'] = 'Visibilité';
$a->strings['Clear the location'] = 'Effacer la localisation';
$a->strings['Location services are unavailable on your device'] = 'Les services de localisation ne sont pas disponibles sur votre appareil';
$a->strings['Location services are disabled. Please check the website\'s permissions on your device'] = 'Les services de localisation sont désactivés pour ce site. Veuillez vérifier les permissions de ce site sur votre appareil/navigateur.';
$a->strings['You can make this page always open when you use the New Post button in the <a href="/settings/display">Theme Customization settings</a>.'] = 'Vous pouvez faire en sorte que cette page s\'ouvre systématiquement quand vous utilisez le bouton "Nouvelle publication" dans les <a href="/settings/display">paramètres de personnalisation des thèmes</a>.';
$a->strings['The feed for this item is unavailable.'] = 'Le flux pour cet objet n\'est pas disponible.';
$a->strings['Unable to follow this item.'] = 'Erreur lors de l\'abonnement à la conversation.';
$a->strings['System down for maintenance'] = 'Système indisponible pour cause de maintenance';
$a->strings['This Friendica node is currently in maintenance mode, either automatically because it is self-updating or manually by the node administrator. This condition should be temporary, please come back in a few minutes.'] = 'Ce serveur Friendica est actuellement en maintenance, soit automatiquement pendant la mise à jour ou manuellement par un administrateur. Cet état devrait être temporaire, merci de réessayer dans quelques minutes.';
$a->strings['A Decentralized Social Network'] = 'Un Réseau Social Décentralisé ';
$a->strings['You need to be logged in to access this page.'] = 'Vous devez être connecté pour accéder à cette page.';
$a->strings['Files'] = 'Fichiers';
$a->strings['Upload'] = 'Téléverser';
$a->strings['Sorry, maybe your upload is bigger than the PHP configuration allows'] = 'Désolé, il semble que votre fichier est plus important que ce que la configuration de PHP autorise';
$a->strings['Or - did you try to upload an empty file?'] = 'Ou — auriez-vous essayé de télécharger un fichier vide ?';
$a->strings['File exceeds size limit of %s'] = 'La taille du fichier dépasse la limite de %s';
$a->strings['File upload failed.'] = 'Le téléversement a échoué.';
$a->strings['Unable to process image.'] = 'Impossible de traiter l\'image.';
$a->strings['Image upload failed.'] = 'Le téléversement de l\'image a échoué.';
$a->strings['List of all users'] = 'Liste de tous les utilisateurs';
$a->strings['Active'] = 'Actif';
$a->strings['List of active accounts'] = 'Liste des comptes actifs';
$a->strings['List of pending registrations'] = 'Liste des inscriptions en attente';
$a->strings['List of blocked users'] = 'Liste des utilisateurs bloqués';
$a->strings['Deleted'] = 'Supprimé';
$a->strings['List of pending user deletions'] = 'Liste des utilisateurs en attente de suppression';
$a->strings['Normal Account Page'] = 'Compte normal';
$a->strings['Soapbox Page'] = 'Compte "boîte à savon"';
$a->strings['Public Group'] = 'Groupe Public';
$a->strings['Public Group - Restricted'] = 'Groupe Public - Restreint';
$a->strings['Automatic Friend Page'] = 'Abonnement réciproque';
$a->strings['Private Group'] = 'Groupe Privé';
$a->strings['Personal Page'] = 'Page personnelle';
$a->strings['Organisation Page'] = 'Page Associative';
$a->strings['News Page'] = 'Page d\'informations';
$a->strings['Community Group'] = 'Groupe Communautaire';
$a->strings['You can\'t block a local contact, please block the user instead'] = 'Vous ne pouvez pas bloquer un contact local. Merci de bloquer l\'utilisateur à la place';
$a->strings['%s contact unblocked'] = [
	0 => '%s contact débloqué',
	1 => '%s profiles distants débloqués',
	2 => '%s profiles distants débloqués',
];
$a->strings['Remote Contact Blocklist'] = 'Liste des profiles distants bloqués';
$a->strings['This page allows you to prevent any message from a remote contact to reach your node.'] = 'Cette page vous permet de refuser toutes les publications d\'un profile distant sur votre site.';
$a->strings['Block Remote Contact'] = 'Bloquer le profile distant';
$a->strings['select all'] = 'tout sélectionner';
$a->strings['select none'] = 'Sélectionner tous';
$a->strings['No remote contact is blocked from this node.'] = 'Aucun profil distant n\'est bloqué';
$a->strings['Blocked Remote Contacts'] = 'Profils distants bloqués';
$a->strings['Block New Remote Contact'] = 'Bloquer un nouveau profil distant';
$a->strings['Photo'] = 'Photo';
$a->strings['Reason'] = 'Raison';
$a->strings['%s total blocked contact'] = [
	0 => '%s profil distant bloqué',
	1 => '%s profils distans bloqués',
	2 => '%s profils distans bloqués',
];
$a->strings['URL of the remote contact to block.'] = 'URL du profil distant à bloquer.';
$a->strings['Also purge contact'] = 'Purger également le contact';
$a->strings['Removes all content related to this contact from the node. Keeps the contact record. This action cannot be undone.'] = 'Supprime tout le contenu relatif à ce contact du nœud. Conserve une trace du contact. Cette action ne peut être annulée.';
$a->strings['Block Reason'] = 'Raison du blocage';
$a->strings['Server domain pattern added to the blocklist.'] = 'Modèle de domaine de serveur ajouté à la liste de blocage.';
$a->strings['%s server scheduled to be purged.'] = [
	0 => 'La purge d\'%s serveur est planifiée.',
	1 => 'La purge des %s serveurs est planifiée.',
	2 => 'La purge des %s serveurs est planifiée.',
];
$a->strings['← Return to the list'] = '← Retourner à la liste';
$a->strings['Block A New Server Domain Pattern'] = 'Bloquer un nouveau modèle de domaine de serveur';
$a->strings['<p>The server domain pattern syntax is case-insensitive shell wildcard, comprising the following special characters:</p>
<ul>
	<li><code>*</code>: Any number of characters</li>
	<li><code>?</code>: Any single character</li>
</ul>'] = '<p>La syntaxe du modèle de domaine du serveur est du shell insensible à la casse avec wildcards, comprenant les caractères spéciaux suivants :</p>
<ul>
	<li><code>*</code>  N\'importe quel nombre de caractères</li>
	<li><code>?</code> N\'importe quel caractère unique</li>
</ul>';
$a->strings['Check pattern'] = 'Vérifier le modèle';
$a->strings['Matching known servers'] = 'Serveurs connus correspondants';
$a->strings['Server Name'] = 'Nom du serveur';
$a->strings['Server Domain'] = 'Domaine du serveur';
$a->strings['Known Contacts'] = 'Contacts connus';
$a->strings['%d known server'] = [
	0 => '%d serveur connu',
	1 => '%d serveurs connus',
	2 => '%d serveurs connus',
];
$a->strings['Add pattern to the blocklist'] = 'Ajouter le modèle à la liste de blocage';
$a->strings['Server Domain Pattern'] = 'Filtre de domaine';
$a->strings['The domain pattern of the new server to add to the blocklist. Do not include the protocol.'] = 'le modèle de domaine du nouveau serveur à ajouter à la liste de blocage. Ne pas inclure le protocole.';
$a->strings['Purge server'] = 'Purger le serveur';
$a->strings['Also purges all the locally stored content authored by the known contacts registered on that server. Keeps the contacts and the server records. This action cannot be undone.'] = [
	0 => 'Purge également tout le contenu local stocké créé par les contacts connus inscrit sur ce serveur. Garde un enregistrement des contacts et du serveur. Cette action ne peut être annulée.',
	1 => 'Purge également tout le contenu local stocké créé par les contacts connus inscrits sur ces serveurs. Garde un enregistrement des contacts et des serveurs. Cette action ne peut être annulée.',
	2 => 'Purge également tout le contenu local stocké créé par les contacts connus inscrits sur ces serveurs. Garde un enregistrement des contacts et des serveurs. Cette action ne peut être annulée.',
];
$a->strings['Block reason'] = 'Raison du blocage';
$a->strings['The reason why you blocked this server domain pattern. This reason will be shown publicly in the server information page.'] = 'La raison pour laquelle vous avez bloqué ce modèle de domaine de serveur. La raison sera publiquement affichée dans la page d\'information du serveur.';
$a->strings['Error importing pattern file'] = 'Erreur lors de l\'import du fichier de motifs';
$a->strings['Local blocklist replaced with the provided file.'] = 'La liste de blocage locale a été remplacée par le fichier fourni.';
$a->strings['%d pattern was added to the local blocklist.'] = [
	0 => '%d motif a été ajouté à la liste de blocage locale.',
	1 => '%d motifs ont été ajoutés à la liste de blocage locale.',
	2 => '%d motifs ont été ajoutés à la liste de blocage locale.',
];
$a->strings['No pattern was added to the local blocklist.'] = 'Aucun motif n\'a été ajouté à la liste de blocage locale.';
$a->strings['Import a Server Domain Pattern Blocklist'] = 'Importer une liste de blocage de motif de domaine de serveur';
$a->strings['<p>This file can be downloaded from the <code>/friendica</code> path of any Friendica server.</p>'] = '<p>Ce fichier peut être téléchargé depuis le chemin <code>/friendica</code> de n\'importe quel serveur Friendica.</p>';
$a->strings['Upload file'] = 'Téléverser un fichier';
$a->strings['Patterns to import'] = 'Motifs à importer';
$a->strings['Domain Pattern'] = 'Motif de domaine';
$a->strings['Import Mode'] = 'Mode d\'import';
$a->strings['Import Patterns'] = 'Importer les motifs';
$a->strings['%d total pattern'] = [
	0 => '%d motif total',
	1 => '%d motifs totaux',
	2 => '%d motifs totaux',
];
$a->strings['Server domain pattern blocklist CSV file'] = 'Fichier CSV de liste de blocage de motif de domaine de serveur';
$a->strings['Append'] = 'Ajouter';
$a->strings['Imports patterns from the file that weren\'t already existing in the current blocklist.'] = 'Importe les motifs du fichier qui n\'étaient pas déjà présent dans la liste de blocage actuelle.';
$a->strings['Replace'] = 'Remplacer';
$a->strings['Replaces the current blocklist by the imported patterns.'] = 'Remplace la liste de blocage locale par les motifs importés.';
$a->strings['Blocked server domain pattern'] = 'Filtre de domaine bloqué';
$a->strings['Delete server domain pattern'] = 'Supprimer ce filtre de domaine bloqué';
$a->strings['Check to delete this entry from the blocklist'] = 'Cochez la case pour retirer cette entrée de la liste noire';
$a->strings['Server Domain Pattern Blocklist'] = 'Liste des filtres de domaines bloqués';
$a->strings['This page can be used to define a blocklist of server domain patterns from the federated network that are not allowed to interact with your node. For each domain pattern you should also provide the reason why you block it.'] = 'Cette page sert à définit une liste de blocage de schémas de domaine de serveurs distants qui ne sont pas autorisé à interagir avec ce serveur. Veuillez fournir la raison pour laquelle vous avez décidé de bloquer chaque schéma de domaine.';
$a->strings['The list of blocked server domain patterns will be made publically available on the <a href="/friendica">/friendica</a> page so that your users and people investigating communication problems can find the reason easily.'] = 'La liste de blocage est disponible publiquement à la page <a href="/friendica">/friendica</a> pour permettre de déterminer la cause de certains problèmes de communication avec des serveurs distants.';
$a->strings['Import server domain pattern blocklist'] = 'Importer la liste de blocage de motif de domaine de serveur';
$a->strings['Add new entry to the blocklist'] = 'Ajouter une nouvelle entrée à la liste de blocage';
$a->strings['Save changes to the blocklist'] = 'Sauvegarder la liste noire';
$a->strings['Current Entries in the Blocklist'] = 'Entrées de la liste noire';
$a->strings['Delete entry from the blocklist'] = 'Supprimer l\'entrée de la liste de blocage';
$a->strings['Delete entry from the blocklist?'] = 'Supprimer l\'entrée de la liste de blocage ?';
$a->strings['Item marked for deletion.'] = 'L\'élément va être supprimé.';
$a->strings['Delete this Item'] = 'Supprimer l\'élément';
$a->strings['On this page you can delete an item from your node. If the item is a top level posting, the entire thread will be deleted.'] = 'Sur cette page, vous pouvez supprimer un élément de votre noeud. Si cet élément est le premier post d\'un fil de discussion, le fil de discussion entier sera supprimé.';
$a->strings['You need to know the GUID of the item. You can find it e.g. by looking at the display URL. The last part of http://example.com/display/123456 is the GUID, here 123456.'] = 'Vous devez connaître le GUID de l\'élément. Vous pouvez le trouver en sélectionnant l\'élément puis en lisant l\'URL. La dernière partie de l\'URL est le GUID. Exemple: http://example.com/display/123456 a pour GUID: 123456.';
$a->strings['GUID'] = 'GUID';
$a->strings['The GUID of the item you want to delete.'] = 'GUID de l\'élément à supprimer.';
$a->strings['Item Id'] = 'Id de la publication';
$a->strings['Item URI'] = 'URI de la publication';
$a->strings['Terms'] = 'Termes';
$a->strings['Tag'] = 'Tag';
$a->strings['Type'] = 'Type';
$a->strings['Term'] = 'Terme';
$a->strings['URL'] = 'URL';
$a->strings['Implicit Mention'] = 'Mention implicite';
$a->strings['Item not found'] = 'Élément introuvable';
$a->strings['No source recorded'] = 'Aucune source enregistrée';
$a->strings['Please make sure the <code>debug.store_source</code> config key is set in <code>config/local.config.php</code> for future items to have sources.'] = 'Merci de vérifier que la clé de configuration <code>debug.store_source</code> est définie dans <code>config/local.config.php</code> pour que les items futurs puissent avoir des sources.';
$a->strings['Item Guid'] = 'GUID du contenu';
$a->strings['Contact not found or their server is already blocked on this node.'] = 'Contact non trouvé ou son serveur est déjà bloqué sur ce nœud.';
$a->strings['Please login to access this page.'] = 'Connectez-vous pour accéder à cette page.';
$a->strings['Create Moderation Report'] = 'Créer un rapport de modération';
$a->strings['Pick Contact'] = 'Sélectionner le contact';
$a->strings['Please enter below the contact address or profile URL you would like to create a moderation report about.'] = 'Veuillez saisir ci-dessous l\'adresse ou l\'URL de profil du contact dont vous souhaitez faire un signalement.';
$a->strings['Contact address/URL'] = 'Adresse/URL du contact';
$a->strings['Pick Category'] = 'Sélectionner la catégorie';
$a->strings['Please pick below the category of your report.'] = 'Veuillez sélectionner la catégorie de votre signalement.';
$a->strings['Spam'] = 'Spam';
$a->strings['This contact is publishing many repeated/overly long posts/replies or advertising their product/websites in otherwise irrelevant conversations.'] = 'Ce contact publie beaucoup de publications/réponses répétées/très longs ou fait la promotion de ses produits/sites web sur des conversations non pertinentes.';
$a->strings['Illegal Content'] = 'Contenu illégal';
$a->strings['This contact is publishing content that is considered illegal in this node\'s hosting juridiction.'] = 'Ce contact publie du contenu qui est considéré illégal dans la juridiction où est hébergé ce nœud.';
$a->strings['Community Safety'] = 'Sécurité de la communauté';
$a->strings['This contact aggravated you or other people, by being provocative or insensitive, intentionally or not. This includes disclosing people\'s private information (doxxing), posting threats or offensive pictures in posts or replies.'] = 'Ce contact vous a irrité ou a irrité d\'autres personnes en se montrant provocateur ou insensible, intentionnellement ou non. Cela inclut la divulgation d\'informations privées (doxxing), la publication de menaces ou d\'images offensantes dans des publications ou des réponses.';
$a->strings['Unwanted Content/Behavior'] = 'Contenu/Comportement indésirable';
$a->strings['This contact has repeatedly published content irrelevant to the node\'s theme or is openly criticizing the node\'s administration/moderation without directly engaging with the relevant people for example or repeatedly nitpicking on a sensitive topic.'] = 'Ce contact a publié de manière répétée un contenu sans rapport avec le thème du nœud ou critique ouvertement l\'administration/la modération du nœud sans discuter directement avec les personnes concernées, par exemple ou en pinaillant de manière répétée sur un sujet sensible.';
$a->strings['Rules Violation'] = 'Violation de règles';
$a->strings['This contact violated one or more rules of this node. You will be able to pick which one(s) in the next step.'] = 'Ce contact à  violé une ou plusieurs règles de ce nœud. Vous pourrez sélectionner la ou les règles dans l\'étape suivante.';
$a->strings['Please elaborate below why you submitted this report. The more details you provide, the better your report can be handled.'] = 'Veuillez indiquer si-dessous les raisons de ce signalement. Plus vous donnez de détails, mieux le signalement sera pris en compte.';
$a->strings['Additional Information'] = 'Information supplémentaire';
$a->strings['Please provide any additional information relevant to this particular report. You will be able to attach posts by this contact in the next step, but any context is welcome.'] = 'Veuillez fournir n\'importe quelle information supplémentaire utile pour ce signalement. Vous pourrez joindre des publications de ce contact dans la prochaine étape, mais n\'importe quel contenu est accepté.';
$a->strings['Pick Rules'] = 'Sélectionner les règles';
$a->strings['Please pick below the node rules you believe this contact violated.'] = 'Veuillez sélectionner les règles que vous estimez avoir été violées par ce contact.';
$a->strings['Pick Posts'] = 'Sélectionner les publications';
$a->strings['Please optionally pick posts to attach to your report.'] = 'Veuillez sélectionner, si vous le souhaitez, les publications à joindre à votre signalement.';
$a->strings['Submit Report'] = 'Envoyer le signalement';
$a->strings['Further Action'] = 'Action supplémentaire';
$a->strings['You can also perform one of the following action on the contact you reported:'] = 'Vous pouvez également effectuer une des actions suivantes sur le contact que vous signalez :';
$a->strings['Nothing'] = 'Ne rien faire';
$a->strings['Collapse contact'] = 'Réduire le contact';
$a->strings['Their posts and replies will keep appearing in your Network page but their content will be collapsed by default.'] = 'Leurs publications et réponses continueront d\'apparaître sur votre page Réseau mais le contenu sera réduit par défaut.';
$a->strings['Their posts won\'t appear in your Network page anymore, but their replies can appear in forum threads. They still can follow you.'] = 'Leurs publications n\'apparaîtront plus sur votre page Réseau, mais leurs réponses peuvent apparaître dans des fils de discussion. Ils peuvent toujours vous suivre.';
$a->strings['Block contact'] = 'Bloquer le contact';
$a->strings['Their posts won\'t appear in your Network page anymore, but their replies can appear in forum threads, with their content collapsed by default. They cannot follow you but still can have access to your public posts by other means.'] = 'Leurs publications n\'apparaîtront plus sur votre page Réseau mais leurs réponses peuvent apparaître dans des fils de discussion, avec le contenu réduit par défaut. Ils ne peuvent pas vous suivre mais peuvent accéder à vos publications publiques par d\'autres moyens.';
$a->strings['Forward report'] = 'Transmettre le signalement';
$a->strings['Would you ike to forward this report to the remote server?'] = 'Voulez-vous transmettre le signalement au serveur distant ?';
$a->strings['1. Pick a contact'] = '1. Sélectionner le contact';
$a->strings['2. Pick a category'] = '2. Sélectionner la catégorie';
$a->strings['2a. Pick rules'] = '2a. Sélectionner les règles';
$a->strings['2b. Add comment'] = '2b. Ajouter un commentaire';
$a->strings['3. Pick posts'] = '3. Sélectionner les publications';
$a->strings['List of reports'] = 'Liste des signalements';
$a->strings['This page display reports created by our or remote users.'] = 'Cette page affiche les signalements créés par les utilisateurs locaux ou distants.';
$a->strings['No report exists at this node.'] = 'Aucun signalement sur ce nœud.';
$a->strings['Category'] = 'Catégorie';
$a->strings['%s total report'] = [
	0 => '%s signalement au total',
	1 => '%s signalements au total',
	2 => '%s signalements au total',
];
$a->strings['URL of the reported contact.'] = 'URL du contact signalé.';
$a->strings['Channel Relay'] = 'Relai de chaîne';
$a->strings['Registered users'] = 'Utilisateurs inscrits';
$a->strings['Pending registrations'] = 'Inscriptions en attente';
$a->strings['%s user blocked'] = [
	0 => '%s utilisateur bloqué',
	1 => '%s utilisateurs bloqués',
	2 => '%s utilisateurs bloqués',
];
$a->strings['You can\'t remove yourself'] = 'Vous ne pouvez pas supprimer votre propre compte';
$a->strings['%s user deleted'] = [
	0 => '%s utilisateur supprimé',
	1 => '%s utilisateurs supprimés',
	2 => '%s utilisateurs supprimés',
];
$a->strings['User "%s" deleted'] = 'Utilisateur "%s" supprimé';
$a->strings['User "%s" blocked'] = 'Utilisateur "%s" bloqué';
$a->strings['Register date'] = 'Date d\'inscription';
$a->strings['Last login'] = 'Dernière connexion';
$a->strings['Last public item'] = 'Dernière publication publique';
$a->strings['Active Accounts'] = 'Comptes actifs';
$a->strings['User blocked'] = 'Utilisateur bloqué';
$a->strings['Site admin'] = 'Administration du Site';
$a->strings['Account expired'] = 'Compte expiré';
$a->strings['Create a new user'] = 'Créer un nouvel utilisateur';
$a->strings['Selected users will be deleted!\n\nEverything these users had posted on this site will be permanently deleted!\n\nAre you sure?'] = 'Les utilisateurs sélectionnés vont être supprimés!\n\nTout ce qu\'ils ont posté sur ce site sera définitivement effacé!\n\nÊtes-vous certain?';
$a->strings['The user {0} will be deleted!\n\nEverything this user has posted on this site will be permanently deleted!\n\nAre you sure?'] = 'L\'utilisateur {0} va être supprimé!\n\nTout ce qu\'il a posté sur ce site sera définitivement perdu!\n\nÊtes-vous certain?';
$a->strings['%s user unblocked'] = [
	0 => '%s utilisateur débloqué',
	1 => '%s utilisateurs débloqués',
	2 => '%s utilisateurs débloqués',
];
$a->strings['User "%s" unblocked'] = 'Utilisateur "%s" débloqué';
$a->strings['Blocked Users'] = 'Utilisateurs bloqués';
$a->strings['New User'] = 'Nouvel utilisateur';
$a->strings['Add User'] = 'Ajouter l\'utilisateur';
$a->strings['Name of the new user.'] = 'Nom du nouvel utilisateur.';
$a->strings['Nickname'] = 'Pseudo';
$a->strings['Nickname of the new user.'] = 'Pseudo du nouvel utilisateur.';
$a->strings['Email address of the new user.'] = 'Adresse de courriel du nouvel utilisateur.';
$a->strings['Users awaiting permanent deletion'] = 'Utilisateurs en attente de suppression définitive';
$a->strings['Permanent deletion'] = 'Suppression définitive';
$a->strings['User waiting for permanent deletion'] = 'Utilisateur en attente de suppression définitive';
$a->strings['%s user approved'] = [
	0 => '%s utilisateur approuvé',
	1 => '%s utilisateurs approuvés',
	2 => '%s utilisateurs approuvés',
];
$a->strings['%s registration revoked'] = [
	0 => '%s inscription refusée',
	1 => '%s inscriptions refusées',
	2 => '%s inscriptions refusées',
];
$a->strings['Account approved.'] = 'Inscription validée.';
$a->strings['Registration revoked'] = 'Inscription refusée';
$a->strings['User registrations awaiting review'] = 'Inscriptions en attente de confirmation';
$a->strings['Request date'] = 'Date de la demande';
$a->strings['No registrations.'] = 'Pas d\'inscriptions.';
$a->strings['Note from the user'] = 'Message personnel';
$a->strings['Deny'] = 'Refuser';
$a->strings['Show Ignored Requests'] = 'Voir les demandes ignorées';
$a->strings['Hide Ignored Requests'] = 'Cacher les demandes ignorées';
$a->strings['Notification type:'] = 'Type de notification :';
$a->strings['Suggested by:'] = 'Suggéré par :';
$a->strings['Claims to be known to you: '] = 'Prétend que vous le connaissez : ';
$a->strings['Shall your connection be bidirectional or not?'] = 'Souhaitez vous que votre connexion soit bi-directionnelle ?';
$a->strings['Accepting %s as a friend allows %s to subscribe to your posts, and you will also receive updates from them in your news feed.'] = 'Accepter %s comme ami autorise %s à s\'abonner à vos publications, et vous recevrez également des nouvelles d\'eux dans votre fil d\'actualités.';
$a->strings['Accepting %s as a subscriber allows them to subscribe to your posts, but you will not receive updates from them in your news feed.'] = 'Accepter %s comme ami les autorise à s\'abonner à vos publications, mais vous ne recevrez pas de nouvelles d\'eux dans votre fil d\'actualités.';
$a->strings['Friend'] = 'Ami';
$a->strings['Subscriber'] = 'Abonné∙e';
$a->strings['No introductions.'] = 'Aucune demande d\'introduction.';
$a->strings['No more %s notifications.'] = 'Aucune notification de %s';
$a->strings['You must be logged in to show this page.'] = 'Vous devez être identifié pour afficher cette page.';
$a->strings['Network Notifications'] = 'Notifications du réseau';
$a->strings['System Notifications'] = 'Notifications du système';
$a->strings['Personal Notifications'] = 'Notifications personnelles';
$a->strings['Home Notifications'] = 'Notifications de page d\'accueil';
$a->strings['Show unread'] = 'Afficher non-lus';
$a->strings['{0} requested registration'] = '{0} a demandé à s\'inscrire';
$a->strings['{0} and %d others requested registration'] = '{0} et %d autres attendent la confirmation de leur inscription.';
$a->strings['Authorize application connection'] = 'Autoriser l\'application à se connecter';
$a->strings['Do you want to authorize this application to access your posts and contacts, and/or create new posts for you?'] = 'Voulez-vous autoriser cette application à accéder à vos publications et contacts, et/ou à créer des billets à votre place?';
$a->strings['Unsupported or missing response type'] = 'Type de réponse manquant ou non pris en charge';
$a->strings['Incomplete request data'] = 'Requête incomplète';
$a->strings['Please copy the following authentication code into your application and close this window: %s'] = 'Veuillez copier le code d\'identification suivant dans votre application et ensuite fermer cette fenêtre: %s';
$a->strings['Invalid data or unknown client'] = 'Données invalides ou client inconnu';
$a->strings['Unsupported or missing grant type'] = 'Type de "grant" manquant ou non pris en charge';
$a->strings['Subscribing to contacts'] = 'Abonnement aux contacts';
$a->strings['No contact provided.'] = 'Pas de contact fourni.';
$a->strings['Couldn\'t fetch information for contact.'] = 'Impossible de récupérer les informations pour ce contact.';
$a->strings['Couldn\'t fetch friends for contact.'] = 'Impossible d\'obtenir les abonnements de ce contact.';
$a->strings['Couldn\'t fetch following contacts.'] = 'Impossible de récupérer les contacts suivants.';
$a->strings['Couldn\'t fetch remote profile.'] = 'Impossible de récupérer le profil distant.';
$a->strings['Unsupported network'] = 'Réseau incompatible';
$a->strings['Done'] = 'Terminé';
$a->strings['success'] = 'réussite';
$a->strings['failed'] = 'échec';
$a->strings['ignored'] = 'ignoré';
$a->strings['Keep this window open until done.'] = 'Veuillez garder cette fenêtre ouverte jusqu\'à la fin.';
$a->strings['Search in Friendica %s'] = 'Rechercher sur Friendica de %s';
$a->strings['The Photo is not available.'] = 'La photo n\'est pas disponible.';
$a->strings['The Photo with id %s is not available.'] = 'La photo avec l\'identifiant %s n\'est pas disponible.';
$a->strings['Invalid external resource with url %s.'] = 'La ressource externe avec l\'URL %s est invalide.';
$a->strings['Invalid photo with id %s.'] = 'La photo avec l\'identifiant %s est invalide.';
$a->strings['Post not found.'] = 'Publication non trouvée.';
$a->strings['Edit post'] = 'Éditer la publication';
$a->strings['web link'] = 'lien web';
$a->strings['Insert video link'] = 'Insérer un lien video';
$a->strings['video link'] = 'lien vidéo';
$a->strings['Insert audio link'] = 'Insérer un lien audio';
$a->strings['audio link'] = 'lien audio';
$a->strings['Remove Item Tag'] = 'Enlever le tag de l\'élément';
$a->strings['Select a tag to remove: '] = 'Sélectionner un tag à supprimer :';
$a->strings['Remove'] = 'Utiliser comme photo de profil';
$a->strings['Wrong type "%s", expected one of: %s'] = 'Type inattendu "%s", valeurs attendues : %s';
$a->strings['Model not found'] = 'Objet introuvable';
$a->strings['Unlisted'] = 'Non listé';
$a->strings['Remote privacy information not available.'] = 'Informations de confidentialité indisponibles.';
$a->strings['Visible to:'] = 'Visible par :';
$a->strings['CC:'] = 'CC:';
$a->strings['BCC:'] = 'CCI:';
$a->strings['Audience:'] = 'Audience :';
$a->strings['Attributed To:'] = 'Attribué à :';
$a->strings['Collection (%s)'] = 'Collection (%s)';
$a->strings['Followers (%s)'] = 'Abonnés (%s)';
$a->strings['%d more'] = '%d supplémentaire';
$a->strings['No contacts.'] = 'Aucun contact.';
$a->strings['%s\'s posts'] = 'Les publications originales de %s';
$a->strings['%s\'s comments'] = 'Les commentaires de %s';
$a->strings['%s\'s timeline'] = 'Le flux de %s';
$a->strings['Image exceeds size limit of %s'] = 'L\'image dépasse la taille limite de %s';
$a->strings['Image upload didn\'t complete, please try again'] = 'La mise en ligne de l\'image ne s\'est pas terminée, veuillez réessayer';
$a->strings['Image file is missing'] = 'Fichier image manquant';
$a->strings['Server can\'t accept new file upload at this time, please contact your administrator'] = 'Le serveur ne peut pas accepter la mise en ligne d\'un nouveau fichier en ce moment, veuillez contacter un administrateur';
$a->strings['Image file is empty.'] = 'Fichier image vide.';
$a->strings['View Album'] = 'Voir l\'album';
$a->strings['Profile not found.'] = 'Profil introuvable.';
$a->strings['You\'re currently viewing your profile as <b>%s</b> <a href="%s" class="btn btn-sm pull-right">Cancel</a>'] = 'Vous êtes en train de consulter votre profil en tant que <b>%s</b> <a href="%s" class="btn btn-sm pull-right">Annuler</a>';
$a->strings['Full Name:'] = 'Nom complet :';
$a->strings['Member since:'] = 'Membre depuis :';
$a->strings['j F, Y'] = 'j F, Y';
$a->strings['j F'] = 'j F';
$a->strings['Birthday:'] = 'Anniversaire :';
$a->strings['Age: '] = 'Age : ';
$a->strings['%d year old'] = [
	0 => '%d an',
	1 => '%d ans',
	2 => '%d ans',
];
$a->strings['Description:'] = 'Description :';
$a->strings['Groups:'] = 'Groupes :';
$a->strings['View profile as:'] = 'Consulter le profil en tant que :';
$a->strings['View as'] = 'Voir en tant que';
$a->strings['Profile unavailable.'] = 'Profil indisponible.';
$a->strings['Invalid locator'] = 'Localisateur invalide';
$a->strings['The provided profile link doesn\'t seem to be valid'] = 'Le lien de profil fourni ne semble pas valide.';
$a->strings['Remote subscription can\'t be done for your network. Please subscribe directly on your system.'] = 'L\'abonnement à distance ne peut pas être fait pour votre réseau. Merci de vous abonner directement sur votre système.';
$a->strings['Friend/Connection Request'] = 'Demande de mise en contact';
$a->strings['Enter your Webfinger address (user@domain.tld) or profile URL here. If this isn\'t supported by your system, you have to subscribe to <strong>%s</strong> or <strong>%s</strong> directly on your system.'] = 'Saisissez votre adresse WebFinger (utilisateur@domaine.tld) ou l\'adresse URL de votre profil ici. Si ce n\'est pas supporté par votre site, vous devrez vous abonner à <strong>%s</strong> ou <strong>%s</strong> directement depuis votre système.';
$a->strings['If you are not yet a member of the free social web, <a href="%s">follow this link to find a public Friendica node and join us today</a>.'] = 'Si vous n\'avez pas de compte sur un site compatible, <a href="%s">cliquez ici pour trouver un site Friendica public et vous inscrire dès aujourd\'hui</a>.';
$a->strings['Your Webfinger address or profile URL:'] = 'Votre adresse Webfinger ou URL de profil :';
$a->strings['Restricted profile'] = 'Profil restreint';
$a->strings['This profile has been restricted which prevents access to their public content from anonymous visitors.'] = 'Ce profil a été restreint ce qui empêche l\'accès des visiteurs anonymes à son contenu public.';
$a->strings['Scheduled'] = 'Programmé';
$a->strings['Content'] = 'Contenu';
$a->strings['Remove post'] = 'Supprimer la publication';
$a->strings['Only parent users can create additional accounts.'] = 'Seuls les comptes parent peuvent créer des comptes supplémentaires.';
$a->strings['This site has exceeded the number of allowed daily account registrations. Please try again tomorrow.'] = 'Le nombre d\'inscriptions quotidiennes pour ce site a été dépassé. Merci de réessayer demain.';
$a->strings['You may (optionally) fill in this form via OpenID by supplying your OpenID and clicking "Register".'] = 'Vous pouvez (si vous le souhaitez) remplir ce formulaire via OpenID en fournissant votre OpenID et en cliquant sur "S\'inscrire".';
$a->strings['If you are not familiar with OpenID, please leave that field blank and fill in the rest of the items.'] = 'Si vous n\'êtes pas familier avec OpenID, laissez ce champ vide et remplissez le reste.';
$a->strings['Your OpenID (optional): '] = 'Votre OpenID (facultatif): ';
$a->strings['Include your profile in member directory?'] = 'Inclure votre profil dans l\'annuaire des membres?';
$a->strings['Note for the admin'] = 'Commentaire pour l\'administrateur';
$a->strings['Leave a message for the admin, why you want to join this node'] = 'Indiquez à l\'administrateur les raisons de votre inscription à cette instance.';
$a->strings['Membership on this site is by invitation only.'] = 'L\'inscription à ce site se fait uniquement sur invitation.';
$a->strings['Your invitation code: '] = 'Votre code d\'invitation :';
$a->strings['Your Display Name (as you would like it to be displayed on this system'] = 'Votre Nom d\'Affichage (tel que vous souhaiteriez l\'afficher sur ce système';
$a->strings['Your Email Address: (Initial information will be send there, so this has to be an existing address.)'] = 'Votre courriel : (Des informations de connexion vont être envoyées à cette adresse; elle doit exister).';
$a->strings['Please repeat your e-mail address:'] = 'Veuillez répéter votre adresse courriel :';
$a->strings['New Password:'] = 'Nouveau mot de passe :';
$a->strings['Leave empty for an auto generated password.'] = 'Laisser ce champ libre pour obtenir un mot de passe généré automatiquement.';
$a->strings['Confirm:'] = 'Confirmer :';
$a->strings['Choose a profile nickname. This must begin with a text character. Your profile address on this site will then be "<strong>nickname@%s</strong>".'] = 'Choisissez un pseudo. Celui devra commencer par une lettre. L\'adresse de votre profil en découlera sous la forme "<strong>pseudo@%s</strong>".';
$a->strings['Choose a nickname: '] = 'Choisir un pseudo : ';
$a->strings['Import'] = 'Importer';
$a->strings['Import your profile to this friendica instance'] = 'Importer votre profile dans cette instance de friendica';
$a->strings['Note: This node explicitly contains adult content'] = 'Note: Ce nœud contient explicitement du contenu destiné aux adultes';
$a->strings['Parent Password:'] = 'Mot de passe du compte parent :';
$a->strings['Please enter the password of the parent account to legitimize your request.'] = 'Veuillez saisir le mot de passe du compte parent pour authentifier votre requête.';
$a->strings['Password doesn\'t match.'] = 'Le mot de passe ne correspond pas.';
$a->strings['Please enter your password.'] = 'Veuillez saisir votre mot de passe.';
$a->strings['You have entered too much information.'] = 'Vous avez entré trop d\'informations.';
$a->strings['Please enter the identical mail address in the second field.'] = 'Veuillez entrer une adresse courriel identique dans le deuxième champ.';
$a->strings['Nickname cannot start with a digit.'] = 'Le pseudo ne peut pas commencer par un chiffre.';
$a->strings['Nickname can only contain US-ASCII characters.'] = 'Le pseudo ne peut contenir que des caractères US-ASCII.';
$a->strings['The additional account was created.'] = 'Le compte additionnel a bien été créé.';
$a->strings['Registration successful. Please check your email for further instructions.'] = 'Inscription réussie. Vérifiez vos courriels pour la suite des instructions.';
$a->strings['Failed to send email message. Here your accout details:<br> login: %s<br> password: %s<br><br>You can change your password after login.'] = 'Impossible d’envoyer le courriel de confirmation. Voici vos informations de connexion:<br> identifiant : %s<br> mot de passe : %s<br><br>Vous pourrez changer votre mot de passe une fois connecté.';
$a->strings['Registration successful.'] = 'Inscription réussie.';
$a->strings['Your registration can not be processed.'] = 'Votre inscription ne peut être traitée.';
$a->strings['You have to leave a request note for the admin.'] = 'Vous devez rédiger une demande auprès de l\'administrateur.';
$a->strings['An internal error occured.'] = 'Une erreur interne est survenue.';
$a->strings['Your registration is pending approval by the site owner.'] = 'Votre inscription attend une validation du propriétaire du site.';
$a->strings['You must be logged in to use this module.'] = 'Ce module est réservé aux utilisateurs identifiés.';
$a->strings['Only logged in users are permitted to perform a search.'] = 'Seuls les utilisateurs inscrits sont autorisés à lancer une recherche.';
$a->strings['Only one search per minute is permitted for not logged in users.'] = 'Une seule recherche par minute pour les utilisateurs qui ne sont pas connectés.';
$a->strings['Items tagged with: %s'] = 'Éléments marqué %s';
$a->strings['Search term was not saved.'] = 'Le terme de recherche n\'a pas été sauvegardé.';
$a->strings['Search term already saved.'] = 'Le terme de recherche a déjà été sauvegardé.';
$a->strings['Search term was not removed.'] = 'Le terme de recherche n\'a pas été supprimé.';
$a->strings['Create a New Account'] = 'Créer un nouveau compte';
$a->strings['Your OpenID: '] = 'Votre OpenID :';
$a->strings['Please enter your username and password to add the OpenID to your existing account.'] = 'Veuillez saisir votre nom d\'utilisateur et votre mot de passer pour ajouter l\'OpenID à votre compte existant.';
$a->strings['Or login using OpenID: '] = 'Ou connectez-vous via OpenID : ';
$a->strings['Password: '] = 'Mot de passe : ';
$a->strings['Remember me'] = 'Se souvenir de moi';
$a->strings['Forgot your password?'] = 'Mot de passe oublié?';
$a->strings['Website Terms of Service'] = 'Conditions d\'utilisation du site internet';
$a->strings['terms of service'] = 'conditions d\'utilisation';
$a->strings['Website Privacy Policy'] = 'Politique de confidentialité du site internet';
$a->strings['privacy policy'] = 'politique de confidentialité';
$a->strings['Logged out.'] = 'Déconnecté.';
$a->strings['OpenID protocol error. No ID returned'] = 'Erreur de protocole OpenID. Pas d\'ID en retour.';
$a->strings['Account not found. Please login to your existing account to add the OpenID to it.'] = 'Compte non trouvé. Veuillez vous connecter à votre compte existant pour y ajouter l\'OpenID.';
$a->strings['Account not found. Please register a new account or login to your existing account to add the OpenID to it.'] = 'Compte non trouvé. Veuillez créer un nouveau compte ou vous connecter à votre compte existant pour y ajouter l\'OpenID.';
$a->strings['Passwords do not match.'] = 'Les mots de passe ne correspondent pas.';
$a->strings['Password does not need changing.'] = 'Le mot de passe n\'a pas besoin d\'être changé.';
$a->strings['Password unchanged.'] = 'Mot de passe non changé.';
$a->strings['Password Too Long'] = 'Mot de passe trop long';
$a->strings['Since version 2022.09, we\'ve realized that any password longer than 72 characters is truncated during hashing. To prevent any confusion about this behavior, please update your password to be fewer or equal to 72 characters.'] = 'Depuis la version 2022.09, nous nous sommes rendu compte que tout mot de passe plus long que 72 caractères est tronqué lors du hashage. Pour éviter toute confusion à propos de ce comportement, merci de mettre à jour votre mot de passe pour qu\'il soit d\'une taille inférieure ou égale à 72 caractères.';
$a->strings['Update Password'] = 'Mettre à jour le mot de passe';
$a->strings['Current Password:'] = 'Mot de passe actuel :';
$a->strings['Your current password to confirm the changes'] = 'Votre mot de passe actuel pour confirmer les modifications';
$a->strings['Allowed characters are a-z, A-Z, 0-9 and special characters except white spaces and accentuated letters.'] = 'Les caractères autorisés sont a-z, A-Z, 0-9 et les caractères spéciaux à l\'exception des espaces et des lettres accentuées.';
$a->strings['Password length is limited to 72 characters.'] = 'La taille du mot de passe est limitée à 72 caractères.';
$a->strings['Remaining recovery codes: %d'] = 'Codes de récupération restants : %d';
$a->strings['Invalid code, please retry.'] = 'Code invalide, veuillez réessayer.';
$a->strings['Two-factor recovery'] = 'Récupération d\'identification à deux facteurs';
$a->strings['<p>You can enter one of your one-time recovery codes in case you lost access to your mobile device.</p>'] = '<p>Vous pouvez saisir l\'un de vos codes de récupération à usage unique si vous avez perdu l\'accès à votre périphérique mobile.</p>';
$a->strings['Don’t have your phone? <a href="%s">Enter a two-factor recovery code</a>'] = 'Vous n\'avez pas votre téléphone ? <a href="%s">Saisissez un code de récupération à deux facteurs</a>';
$a->strings['Please enter a recovery code'] = 'Veuillez saisir un code de récupération';
$a->strings['Submit recovery code and complete login'] = 'Soumettre le code de récupération et compléter l\'identification';
$a->strings['Sign out of this browser?'] = 'Se déconnecter de ce navigateur ?';
$a->strings['<p>If you trust this browser, you will not be asked for verification code the next time you sign in.</p>'] = '<p>Si vous faites confiance à ce navigateur, votre code de vérification ne vous sera pas demandé la prochaine fois que vous vous connecterez.</p>';
$a->strings['Sign out'] = 'Se déconnecter';
$a->strings['Trust and sign out'] = 'Faire confiance et se déconnecter';
$a->strings['Couldn\'t save browser to Cookie.'] = 'Impossible d\'enregistrer ce navigateur dans le cookie.';
$a->strings['Trust this browser?'] = 'Faire confiance à ce navigateur ?';
$a->strings['<p>If you choose to trust this browser, you will not be asked for a verification code the next time you sign in.</p>'] = '<p>Si vous choisissez de faire confiance à ce navigateur, votre code de vérification ne vous sera pas demandé la prochaine fois que vous vous connecterez.</p>';
$a->strings['Not now'] = 'Pas maintenant';
$a->strings['Don\'t trust'] = 'Ne pas faire confiance';
$a->strings['Trust'] = 'Faire confiance';
$a->strings['<p>Open the two-factor authentication app on your device to get an authentication code and verify your identity.</p>'] = '<p>Ouvrez l\'application d\'identification à deux facteurs sur votre appareil afin d\'avoir un code d\'identification et vérifier votre identité.</p>';
$a->strings['If you do not have access to your authentication code you can use a <a href="%s">two-factor recovery code</a>.'] = 'Si vous n\'avez pas accès à votre code d\'identification vous pouvez utiliser un <a href="%s">code de récupération à deux facteurs</a>.';
$a->strings['Please enter a code from your authentication app'] = 'Veuillez saisir le code fourni par votre application mobile d\'authentification à deux facteurs';
$a->strings['Verify code and complete login'] = 'Vérifier le code de récupération et compléter l\'identification';
$a->strings['Please use a shorter name.'] = 'Veuillez saisir un nom plus court.';
$a->strings['Name too short.'] = 'Le nom est trop court.';
$a->strings['Wrong Password.'] = 'Mot de passe erroné.';
$a->strings['Invalid email.'] = 'Courriel invalide.';
$a->strings['Cannot change to that email.'] = 'Ne peut pas changer vers ce courriel.';
$a->strings['Settings were not updated.'] = 'Les paramètres n\'ont pas été mis à jour.';
$a->strings['Relocate message has been send to your contacts'] = 'Un message de relocalisation a été envoyé à vos contacts.';
$a->strings['Unable to find your profile. Please contact your admin.'] = 'Impossible de trouver votre profile. Merci de contacter votre administrateur.';
$a->strings['Account for a service that automatically shares content based on user defined channels.'] = 'Compte de service qui partage automatiquement du contenu basés les chaînes de l\'utilisateur';
$a->strings['Personal Page Subtypes'] = 'Sous-catégories de page personnelle';
$a->strings['Community Group Subtypes'] = 'Sous-catégories de groupe communautaire';
$a->strings['Account for a personal profile.'] = 'Compte pour profil personnel.';
$a->strings['Account for an organisation that automatically approves contact requests as "Followers".'] = 'Compte pour une organisation qui accepte les demandes comme "Abonnés".';
$a->strings['Account for a news reflector that automatically approves contact requests as "Followers".'] = 'Compte pour les miroirs de nouvelles qui accepte automatiquement les de contact comme "Abonnés".';
$a->strings['Account for community discussions.'] = 'Compte pour des discussions communautaires.';
$a->strings['Account for a regular personal profile that requires manual approval of "Friends" and "Followers".'] = 'Les demandes d\'abonnement doivent être acceptées manuellement.';
$a->strings['Account for a public profile that automatically approves contact requests as "Followers".'] = 'Compte pour un profil public qui accepte les demandes de contact comme "Abonnés".';
$a->strings['Automatically approves all contact requests.'] = 'Les demandes de participation au forum sont automatiquement acceptées.';
$a->strings['Contact requests have to be manually approved.'] = 'Les demandes de contact doivent être validées manuellement.';
$a->strings['Account for a popular profile that automatically approves contact requests as "Friends".'] = 'Les demandes d\'abonnement sont automatiquement acceptées.';
$a->strings['Private Group [Experimental]'] = 'Groupe Privé [Expérimental]';
$a->strings['Requires manual approval of contact requests.'] = 'Les demandes de participation au forum nécessitent une approbation.';
$a->strings['OpenID:'] = 'OpenID:';
$a->strings['(Optional) Allow this OpenID to login to this account.'] = '(Facultatif) Autoriser cet OpenID à se connecter à ce compte.';
$a->strings['Publish your profile in your local site directory?'] = 'Publier votre profil dans le répertoire local';
$a->strings['Your profile will be published in this node\'s <a href="%s">local directory</a>. Your profile details may be publicly visible depending on the system settings.'] = 'Votre profil sera public sur l\'<a href="%s">annuaire local</a> de cette instance. Les détails de votre profil pourront être visible publiquement selon les paramètres de votre système.';
$a->strings['Your profile will also be published in the global friendica directories (e.g. <a href="%s">%s</a>).'] = 'Votre profil sera aussi publié dans le répertoire Friendica global (<a href="%s">%s</a>).';
$a->strings['Account Settings'] = 'Compte';
$a->strings['Your Identity Address is <strong>\'%s\'</strong> or \'%s\'.'] = 'L’adresse de votre profil est <strong>\'%s\'</strong> ou \'%s\'.';
$a->strings['Password Settings'] = 'Réglages de mot de passe';
$a->strings['Leave password fields blank unless changing'] = 'Laissez les champs de mot de passe vierges, sauf si vous désirez les changer';
$a->strings['Password:'] = 'Mot de passe :';
$a->strings['Your current password to confirm the changes of the email address'] = 'Votre mot de passe actuel pour confirmer les modifications de votre adresse de courriel.';
$a->strings['Delete OpenID URL'] = 'Supprimer l\'URL OpenID';
$a->strings['Basic Settings'] = 'Réglages de base';
$a->strings['Display name:'] = 'Nom d\'affichage :';
$a->strings['Email Address:'] = 'Adresse courriel :';
$a->strings['Your Timezone:'] = 'Votre fuseau horaire :';
$a->strings['Your Language:'] = 'Votre langue :';
$a->strings['Set the language we use to show you friendica interface and to send you emails'] = 'Détermine la langue que nous utilisons pour afficher votre interface Friendica et pour vous envoyer des courriels';
$a->strings['Default Post Location:'] = 'Emplacement de publication par défaut:';
$a->strings['Use Browser Location:'] = 'Utiliser la localisation géographique du navigateur:';
$a->strings['Security and Privacy Settings'] = 'Réglages de sécurité et vie privée';
$a->strings['Maximum Friend Requests/Day:'] = 'Nombre maximal de demandes d\'abonnement par jour :';
$a->strings['(to prevent spam abuse)'] = '(pour limiter l\'impact du spam)';
$a->strings['Allow your profile to be searchable globally?'] = 'Publier votre profil publiquement';
$a->strings['Activate this setting if you want others to easily find and follow you. Your profile will be searchable on remote systems. This setting also determines whether Friendica will inform search engines that your profile should be indexed or not.'] = 'Permet à quiconque de trouver votre profil via une recherche sur n\'importe quel site compatible ou un moteur de recherche.';
$a->strings['Hide your contact/friend list from viewers of your profile?'] = 'Cacher votre liste de contacts/amis des visiteurs de votre profil?';
$a->strings['A list of your contacts is displayed on your profile page. Activate this option to disable the display of your contact list.'] = 'La liste de vos contacts est affichée sur votre profil. Activer cette option pour désactiver son affichage.';
$a->strings['Hide your public content from anonymous viewers'] = 'Masque votre contenu public aux visiteurs anonymes';
$a->strings['Anonymous visitors will only see your basic profile details. Your public posts and replies will still be freely accessible on the remote servers of your followers and through relays.'] = 'Les visiteurs anonymes ne verront que vos détails de base de profil. Vos publications publiques et vos réponses seront toujours librement accessibles sur les serveurs distants de vos contacts et à travers les relais.';
$a->strings['Make public posts unlisted'] = 'Délister vos publications publiques';
$a->strings['Your public posts will not appear on the community pages or in search results, nor be sent to relay servers. However they can still appear on public feeds on remote servers.'] = 'Vos publications publiques n\'apparaîtront pas dans les pages communautaires ni les résultats de recherche de ce site et ne seront pas diffusées via les serveurs de relai. Cependant, elles pourront quand même apparaître dans les fils publics de sites distants.';
$a->strings['Make all posted pictures accessible'] = 'Rendre toutes les images envoyées accessibles.';
$a->strings['This option makes every posted picture accessible via the direct link. This is a workaround for the problem that most other networks can\'t handle permissions on pictures. Non public pictures still won\'t be visible for the public on your photo albums though.'] = 'Cette option rend chaque image envoyée accessible par un lien direct. C\'est un contournement pour prendre en compte que la pluplart des autres réseaux ne gèrent pas les droits sur les images. Cependant les images non publiques ne seront pas visibles sur votre album photo.';
$a->strings['Allow friends to post to your profile page?'] = 'Autoriser vos contacts à publier sur votre profil ?';
$a->strings['Your contacts may write posts on your profile wall. These posts will be distributed to your contacts'] = 'Vos contacts peuvent partager des publications sur votre mur. Ces publications seront visibles par vos abonnés.';
$a->strings['Allow friends to tag your posts?'] = 'Autoriser vos contacts à ajouter des tags à vos publications ?';
$a->strings['Your contacts can add additional tags to your posts.'] = 'Vos contacts peuvent ajouter des étiquettes à vos publications.';
$a->strings['Default privacy circle for new contacts'] = 'Cercle de contacts par défaut pour les nouveaux contacts';
$a->strings['Default privacy circle for new group contacts'] = 'Cercle de contacts par défaut pour les nouveaux contacts du groupe';
$a->strings['Default Post Permissions'] = 'Permissions de publication par défaut';
$a->strings['Expiration settings'] = 'Réglages d\'expiration';
$a->strings['Automatically expire posts after this many days:'] = 'Les publications expirent automatiquement après (en jours) :';
$a->strings['If empty, posts will not expire. Expired posts will be deleted'] = 'Si ce champ est vide, les publications n\'expireront pas. Les publications expirées seront supprimées';
$a->strings['Expire posts'] = 'Faire expirer les publications';
$a->strings['When activated, posts and comments will be expired.'] = 'Les publications originales et commentaires expireront.';
$a->strings['Expire personal notes'] = 'Faire expirer les notes personnelles';
$a->strings['When activated, the personal notes on your profile page will be expired.'] = ' ';
$a->strings['Expire starred posts'] = 'Faire expirer les publications marquées';
$a->strings['Starring posts keeps them from being expired. That behaviour is overwritten by this setting.'] = 'Ajouter une étoile à une publication empêche son expiration, sauf si vous activez ce paramètre.';
$a->strings['Only expire posts by others'] = 'Faire expirer uniquement les contenu reçus';
$a->strings['When activated, your own posts never expire. Then the settings above are only valid for posts you received.'] = 'Empêche vos propres publications d\'expirer. S\'applique à tous les choix précédents.';
$a->strings['Notification Settings'] = 'Réglages de notification';
$a->strings['Send a notification email when:'] = 'Envoyer un courriel de notification quand :';
$a->strings['You receive an introduction'] = 'Vous recevez une introduction';
$a->strings['Your introductions are confirmed'] = 'Vos introductions sont confirmées';
$a->strings['Someone writes on your profile wall'] = 'Quelqu\'un écrit sur votre mur';
$a->strings['Someone writes a followup comment'] = 'Quelqu\'un vous commente';
$a->strings['You receive a private message'] = 'Vous recevez un message privé';
$a->strings['You receive a friend suggestion'] = 'Vous avez reçu une suggestion d\'abonnement';
$a->strings['You are tagged in a post'] = 'Vous avez été mentionné(e) dans une publication';
$a->strings['Create a desktop notification when:'] = 'Créer une notification de bureau quand :';
$a->strings['Someone tagged you'] = 'Quelqu\'un vous a mentionné';
$a->strings['Someone directly commented on your post'] = 'Quelqu\'un a commenté directement sur votre publication';
$a->strings['Someone liked your content'] = 'Quelqu\'un a aimé votre contenu';
$a->strings['Can only be enabled, when the direct comment notification is enabled.'] = 'Peut uniquement être activé quand la notification des commentaires directs est activée.';
$a->strings['Someone shared your content'] = 'Quelqu\'un a partagé votre contenu';
$a->strings['Someone commented in your thread'] = 'Quelqu\'un a commenté dans votre conversation';
$a->strings['Someone commented in a thread where you commented'] = 'Quelqu\'un a commenté dans une conversation où vous avez commenté';
$a->strings['Someone commented in a thread where you interacted'] = 'Quelqu\'un a commenté dans une conversation avec laquelle vous avez interagi';
$a->strings['Activate desktop notifications'] = 'Activer les notifications de bureau';
$a->strings['Show desktop popup on new notifications'] = 'Afficher dans des pop-ups les nouvelles notifications';
$a->strings['Text-only notification emails'] = 'Courriels de notification en format texte';
$a->strings['Send text only notification emails, without the html part'] = 'Envoyer le texte des courriels de notification, sans la composante html';
$a->strings['Show detailled notifications'] = 'Notifications détaillées';
$a->strings['Per default, notifications are condensed to a single notification per item. When enabled every notification is displayed.'] = 'Par défaut seule la notification la plus récente par conversation est affichée. Ce réglage affiche toutes les notifications.';
$a->strings['Show notifications of ignored contacts'] = 'Montrer les notifications des contacts ignorés';
$a->strings['You don\'t see posts from ignored contacts. But you still see their comments. This setting controls if you want to still receive regular notifications that are caused by ignored contacts or not.'] = 'Par défaut les notifications de vos contacts ignorés sont également ignorées.';
$a->strings['Advanced Account/Page Type Settings'] = 'Paramètres avancés de compte/page';
$a->strings['Change the behaviour of this account for special situations'] = 'Modifier le comportement de ce compte dans certaines situations';
$a->strings['Relocate'] = 'Relocaliser';
$a->strings['If you have moved this profile from another server, and some of your contacts don\'t receive your updates, try pushing this button.'] = 'Si vous avez migré ce profil depuis un autre serveur et que vos contacts ne reçoivent plus vos mises à jour, essayez ce bouton.';
$a->strings['Resend relocate message to contacts'] = 'Renvoyer un message de relocalisation aux contacts.';
$a->strings['Addon Settings'] = 'Paramètres d\'extension';
$a->strings['No Addon settings configured'] = 'Aucuns paramètres d\'Extension paramétré.';
$a->strings['This page can be used to define the channels that will automatically be reshared by your account.'] = 'Cette page peut être utilisée pour définir les chaînes qui seront automatiquement repartagées par votre compte.';
$a->strings['This page can be used to define your own channels.'] = 'Cette page permet de définir votre propres chaînes.';
$a->strings['Publish'] = 'Publier';
$a->strings['When selected, the channel results are reshared. This only works for public ActivityPub posts from the public timeline or the user defined circles.'] = 'Si sélectionné, les résultats de la chaîne sont repartagés. Ceci ne fonctionne que pour les publications ActivityPub publiques ou pour les cercles définis par l\'utilisateur.';
$a->strings['Label'] = 'Titre';
$a->strings['Description'] = 'Description';
$a->strings['Access Key'] = 'Clé d\'accès';
$a->strings['Circle/Channel'] = 'Cercle/Chaîne';
$a->strings['Include Tags'] = 'Inclure des étiquettes';
$a->strings['Exclude Tags'] = 'Exclure des étiquettes';
$a->strings['Minimum Size'] = 'Taille minimum';
$a->strings['Maximum Size'] = 'Taille maximum';
$a->strings['Full Text Search'] = 'Recherche de texte intégral';
$a->strings['Select all languages that you want to see in this channel.'] = 'Sélectionnez les langues que vous souhaitez voir sur cette chaîne.';
$a->strings['Delete channel'] = 'Supprimer la chaîne';
$a->strings['Check to delete this entry from the channel list'] = 'Cochez pour supprimer cette entrée de la liste de chaîne';
$a->strings['Short name for the channel. It is displayed on the channels widget.'] = 'Nom court de la chaîne. Il est affiché dans le widget des chaînes.';
$a->strings['This should describe the content of the channel in a few word.'] = 'Décrivez le contenu de votre chaîne en quelques mots.';
$a->strings['When you want to access this channel via an access key, you can define it here. Pay attention to not use an already used one.'] = 'Si vous accédez à cette chaîne via une clé d\'accès, saisissez là ici. Attention à ne pas saisir une clé déjà utilisée.';
$a->strings['Select a circle or channel, that your channel should be based on.'] = 'Choisissez un cercle ou une chaîne sur lequel se basera votre chaîne.';
$a->strings['Comma separated list of tags. A post will be used when it contains any of the listed tags.'] = 'Liste d\'étiquettes, séparées par des virgules. Une publication sera affichée si elle contient au moins une de ces étiquettes.';
$a->strings['Comma separated list of tags. If a post contain any of these tags, then it will not be part of nthis channel.'] = 'Liste d\'étiquettes, séparées par des virgules. Si une publication contient une de ces étiquettes, elle ne sera pas affichée sur cette chaîne.';
$a->strings['Minimum post size. Leave empty for no minimum size. The size is calculated without links, attached posts, mentions or hashtags.'] = 'Taille minimum d\'une publication. Laisser vide pour aucune limite. La taille est calculée sans prendre en compte les liens, les publications attachées, les mentions ou les étiquettes.';
$a->strings['Maximum post size. Leave empty for no maximum size. The size is calculated without links, attached posts, mentions or hashtags.'] = 'Taille maximum d\'une publication. Laisser vide pour aucune limite. La taille est calculée sans prendre en compte les liens, les publications attachées, les mentions ou les étiquettes.';
$a->strings['Search terms for the body, supports the "boolean mode" operators from MariaDB. See the help for a complete list of operators and additional keywords: %s'] = 'Recherche les termes dans le corps, supporte les opérateurs "boolean mode" de MariaDB. Consultez l\'aide pour une liste complète des opérateurs et des mots clés additionnels : %s';
$a->strings['Check to display images in the channel.'] = 'Cochez pour afficher les images dans la chaîne.';
$a->strings['Check to display videos in the channel.'] = 'Cochez pour afficher la vidéo dans la chaîne.';
$a->strings['Check to display audio in the channel.'] = 'Cochez pour afficher l\'audio dans la chaîne.';
$a->strings['Add new entry to the channel list'] = 'Ajoute une nouvelle entrée dans la liste des chaînes';
$a->strings['Add'] = 'Ajouter';
$a->strings['Current Entries in the channel list'] = 'Entrées actuelles dans la liste des chaînes';
$a->strings['Delete entry from the channel list'] = 'Supprimer l\'entrée de la liste des chaînes';
$a->strings['Delete entry from the channel list?'] = 'Supprimer l\'entrée de la liste des chaînes ?';
$a->strings['Failed to connect with email account using the settings provided.'] = 'Impossible de se connecter au compte courriel configuré.';
$a->strings['Diaspora (Socialhome, Hubzilla)'] = 'Diaspora (Socialhome, Hubzilla)';
$a->strings['Built-in support for %s connectivity is enabled'] = 'Le support intégré pour la connectivité %s est activé';
$a->strings['Built-in support for %s connectivity is disabled'] = 'Le support intégré pour la connectivité %s est désactivé';
$a->strings['Email access is disabled on this site.'] = 'L\'accès courriel est désactivé sur ce site.';
$a->strings['None'] = 'Aucun(e)';
$a->strings['Default (Mastodon will display the title and a link to the post)'] = 'Par défaut (Mastodon affichera le titre et un lien vers la publication)';
$a->strings['Use the summary (Mastodon and some others will treat it as content warning)'] = 'Utiliser le résumé (Mastodon et quelques autres le considèreront comme un avertissement de contenu)';
$a->strings['Embed the title in the body'] = 'Intégrer le titre dans le corps du texte';
$a->strings['General Social Media Settings'] = 'Paramètres généraux des réseaux sociaux';
$a->strings['Followed content scope'] = 'Étendue des contenus suivis';
$a->strings['By default, conversations in which your follows participated but didn\'t start will be shown in your timeline. You can turn this behavior off, or expand it to the conversations in which your follows liked a post.'] = 'Par défaut, les conversations dans lesquelles vos comptes suivis ont participé mais qu\'ils n\'ont pas commencées seront affichées dans votre flux. Vous pouvez désactiver ce comportement, ou l\'étendre aux conversations dans lesquelles vos comptes suivis ont aimé une publication.';
$a->strings['Only conversations my follows started'] = 'Seulement les conversations démarrées par mes comptes suivis';
$a->strings['Conversations my follows started or commented on (default)'] = 'Les conversations que mes comptes suivis ont commencé ou commentées (par défaut)';
$a->strings['Any conversation my follows interacted with, including likes'] = 'Toute conversation avec laquelle mes comptes suivis ont interagi, y compris les "J\'aime"';
$a->strings['Collapse sensitive posts'] = 'Réduire les publications sensibles';
$a->strings['If a post is marked as "sensitive", it will be displayed in a collapsed state, if this option is enabled.'] = 'Si actif, les publications marquées comme "sensible" seront affichées réduites.';
$a->strings['Enable intelligent shortening'] = 'Activer l\'abbréviation intelligente';
$a->strings['Normally the system tries to find the best link to add to shortened posts. If disabled, every shortened post will always point to the original friendica post.'] = 'L\'abbréviation intelligente cherche le lien le plus adapté dans les publications abbréviées. Quand elle est désactivée, le lien est toujours celui de la publication Friendica initiale.';
$a->strings['Enable simple text shortening'] = 'Activer l\'abbréviation de texte simple';
$a->strings['Normally the system shortens posts at the next line feed. If this option is enabled then the system will shorten the text at the maximum character limit.'] = 'Cette option raccourcit le texte des publications au nombre de caractères exact au lieu d\'attendre la fin du paragraphe.';
$a->strings['Attach the link title'] = 'Attacher le titre du lien (Diaspora)';
$a->strings['When activated, the title of the attached link will be added as a title on posts to Diaspora. This is mostly helpful with "remote-self" contacts that share feed content.'] = 'Si vos publications contiennent un lien, le titre de la page associée sera attaché à la publication à destination de vos contacts Diaspora. C\'est principalement utile avec les contacts "remote-self" qui partagent du contenu de flux RSS/Atom.';
$a->strings['API: Use spoiler field as title'] = 'API : Utiliser le champ spoiler (divulgachis) en tant que titre';
$a->strings['When activated, the "spoiler_text" field in the API will be used for the title on standalone posts. When deactivated it will be used for spoiler text. For comments it will always be used for spoiler text.'] = 'Quand activé, le champ "spoiler_text" dans l\'API sera utilisé pour le titre des publications individuelles. Quand désactivé, il sera utilisé pour du texte spoiler (divulgachis). Pour les commentaires, il sera toujours utilisé pour du texte spoiler.';
$a->strings['API: Automatically links at the end of the post as attached posts'] = 'API : Afficher comme publications attachés les liens ajoutés en fin de publication';
$a->strings['When activated, added links at the end of the post react the same way as added links in the web interface.'] = 'Quand activé, les liens ajoutés à la fin d\'une publication fonctionnent de la même manière que les liens ajoutés dans l\'interface web.';
$a->strings['Article Mode'] = 'Mode Article';
$a->strings['Controls how posts with titles are transmitted. Mastodon and its forks don\'t display the content of these posts if the post is created in the correct (default) way.'] = 'Contrôle la façon dont les publications avec des titres sont transmises. Mastodon et ses forks n\'affichent pas le contenu de ces publications si la publication est créée de la bonne manière (par défaut).';
$a->strings['Email/Mailbox Setup'] = 'Réglages de courriel/boîte à lettre';
$a->strings['If you wish to communicate with email contacts using this service (optional), please specify how to connect to your mailbox.'] = 'Si vous souhaitez communiquer avec vos contacts "courriel" (facultatif), merci de nous indiquer comment vous connecter à votre boîte.';
$a->strings['Last successful email check:'] = 'Dernière vérification réussie des courriels :';
$a->strings['IMAP server name:'] = 'Nom du serveur IMAP :';
$a->strings['IMAP port:'] = 'Port IMAP :';
$a->strings['Security:'] = 'Sécurité :';
$a->strings['Email login name:'] = 'Nom de connexion :';
$a->strings['Email password:'] = 'Mot de passe :';
$a->strings['Reply-to address:'] = 'Adresse de réponse :';
$a->strings['Send public posts to all email contacts:'] = 'Envoyer les publications publiques à tous les contacts courriels :';
$a->strings['Action after import:'] = 'Action après import :';
$a->strings['Move to folder'] = 'Déplacer vers';
$a->strings['Move to folder:'] = 'Déplacer vers :';
$a->strings['Contact CSV file upload error'] = 'Erreur de téléversement du fichier de contact CSV';
$a->strings['Importing Contacts done'] = 'Import des contacts effectué';
$a->strings['Upload a CSV file that contains the handle of your followed accounts in the first column you exported from the old account.'] = 'Téléversez un fichier CSV contenant des identifiants de contacts dans la première colonne.';
$a->strings['Upload File'] = 'Téléverser le fichier';
$a->strings['Your legacy ActivityPub/GNU Social account'] = 'Votre ancient compte ActivityPub/GNU Social';
$a->strings['If you enter your old account name from an ActivityPub based system or your GNU Social/Statusnet account name here (in the format user@domain.tld), your contacts will be added automatically. The field will be emptied when done.'] = 'Si vous saisissez votre adresse de compte précédente d\'un réseau basé sur ActivityPub ou GNU Social/Statusnet (au format utilisateur@domaine.tld), vos contacts seront ajoutés autoamtiquement. Le champ sera vidé quand l\'opération sera terminé.';
$a->strings['Delegation successfully granted.'] = 'Délégation accordée avec succès.';
$a->strings['Parent user not found, unavailable or password doesn\'t match.'] = 'Utilisateur parent introuvable, indisponible ou mot de passe incorrect.';
$a->strings['Delegation successfully revoked.'] = 'Délégation retirée avec succès.';
$a->strings['Delegated administrators can view but not change delegation permissions.'] = 'Les administrateurs délégués peuvent uniquement consulter les permissions de délégation.';
$a->strings['Delegate user not found.'] = 'Délégué introuvable.';
$a->strings['No parent user'] = 'Pas d\'utilisateur parent';
$a->strings['Parent User'] = 'Compte parent';
$a->strings['Additional Accounts'] = 'Comptes supplémentaires';
$a->strings['Register additional accounts that are automatically connected to your existing account so you can manage them from this account.'] = 'Enregistrez des comptes supplémentaires qui seront automatiquement rattachés à votre compte actuel pour vous permettre de les gérer facilement.';
$a->strings['Register an additional account'] = 'Enregistrer un compte supplémentaire';
$a->strings['Parent users have total control about this account, including the account settings. Please double check whom you give this access.'] = 'Le compte parent a un contrôle total sur ce compte, incluant les paramètres de compte. Veuillez vérifier à qui vous donnez cet accès.';
$a->strings['Delegates'] = 'Délégataires';
$a->strings['Delegates are able to manage all aspects of this account/page except for basic account settings. Please do not delegate your personal account to anybody that you do not trust completely.'] = 'Les délégataires seront capables de gérer tous les aspects de ce compte ou de cette page, à l\'exception des réglages de compte. Merci de ne pas déléguer votre compte principal à quelqu\'un en qui vous n\'avez pas une confiance absolue.';
$a->strings['Existing Page Delegates'] = 'Délégataires existants';
$a->strings['Potential Delegates'] = 'Délégataires potentiels';
$a->strings['No entries.'] = 'Aucune entrée.';
$a->strings['The theme you chose isn\'t available.'] = 'Le thème que vous avez choisi n\'est pas disponible.';
$a->strings['%s - (Unsupported)'] = '%s- (non supporté)';
$a->strings['Color/Black'] = 'Couleur/Noir';
$a->strings['Black'] = 'Noir';
$a->strings['Color/White'] = 'Couleur/Blanc';
$a->strings['White'] = 'Blanc';
$a->strings['No preview'] = 'Pas d\'aperçu';
$a->strings['No image'] = 'Pas d\'image';
$a->strings['Small Image'] = 'Petite image';
$a->strings['Large Image'] = 'Grande image';
$a->strings['Display Settings'] = 'Affichage';
$a->strings['General Theme Settings'] = 'Paramètres généraux de thème';
$a->strings['Custom Theme Settings'] = 'Paramètres personnalisés de thème';
$a->strings['Content Settings'] = 'Paramètres de contenu';
$a->strings['Theme settings'] = 'Réglages du thème graphique';
$a->strings['Timelines'] = 'Flux';
$a->strings['Display Theme:'] = 'Thème d\'affichage:';
$a->strings['Mobile Theme:'] = 'Thème mobile:';
$a->strings['Number of items to display per page:'] = 'Nombre d’éléments par page :';
$a->strings['Maximum of 100 items'] = 'Maximum de 100 éléments';
$a->strings['Number of items to display per page when viewed from mobile device:'] = 'Nombre d\'éléments à afficher par page pour un appareil mobile';
$a->strings['Update browser every xx seconds'] = 'Mettre à jour l\'affichage toutes les xx secondes';
$a->strings['Minimum of 10 seconds. Enter -1 to disable it.'] = 'Minimum de 10 secondes. Saisir -1 pour désactiver.';
$a->strings['Display emoticons'] = 'Afficher les émoticônes';
$a->strings['When enabled, emoticons are replaced with matching symbols.'] = 'Quand activé, les émoticônes sont remplacées par les symboles correspondants.';
$a->strings['Infinite scroll'] = 'Défilement infini';
$a->strings['Automatic fetch new items when reaching the page end.'] = 'Charge automatiquement de nouveaux contenus en bas de la page.';
$a->strings['Enable Smart Threading'] = 'Activer le fil de discussion intelligent';
$a->strings['Enable the automatic suppression of extraneous thread indentation.'] = 'Activer la suppression automatique de l\'indentation excédentaire des fils de discussion.';
$a->strings['Display the Dislike feature'] = 'Afficher la fonctionnalité "Je n\'aime pas"';
$a->strings['Display the Dislike button and dislike reactions on posts and comments.'] = 'Afficher le bouton "Je n\'aime pas" et les réactions "Je n\'aime pas" sur les publications et les commentaires.';
$a->strings['Display the resharer'] = 'Afficher le partageur';
$a->strings['Display the first resharer as icon and text on a reshared item.'] = 'Afficher le premier partageur en tant qu\'icône et texte sur un élément partagé.';
$a->strings['Stay local'] = 'Rester local';
$a->strings['Don\'t go to a remote system when following a contact link.'] = 'Ne pas aller sur un système distant lors du suivi du lien d\'un contact.';
$a->strings['Show the post deletion checkbox'] = 'Afficher la case à cocher de suppression de publication.';
$a->strings['Display the checkbox for the post deletion on the network page.'] = 'Affiche la case à cocher de suppression de publication sur la page Réseau.';
$a->strings['DIsplay the event list'] = 'Afficher la liste des évènements';
$a->strings['Display the birthday reminder and event list on the network page.'] = 'Affiche le rappel d’anniversaire et la liste des évènements sur la page Réseau.';
$a->strings['Link preview mode'] = 'Mode de prévisualisation des liens';
$a->strings['Appearance of the link preview that is added to each post with a link.'] = 'Apparence de la prévisualisation du lien qui est ajoutée à chaque publication comprenant un lien.';
$a->strings['Hide pictures with empty alternative text'] = 'Masquer les images sans texte alternatif';
$a->strings['Don\'t display pictures that are missing the alternative text.'] = 'N\'affiche pas les images qui n\'ont pas de texte alternatif.';
$a->strings['Hide custom emojis'] = 'Masquer les emojis personnalisés';
$a->strings['Don\'t display custom emojis.'] = 'N\'affiche pas les émojis personnalisés.';
$a->strings['Platform icons style'] = 'Style des icônes de plateforme';
$a->strings['Style of the platform icons'] = 'Style des icônes de plateforme.';
$a->strings['Bookmark'] = 'Favoris';
$a->strings['Enable timelines that you want to see in the channels widget. Bookmark timelines that you want to see in the top menu.'] = 'Activez les flux que vous souhaitez voir dans le widget Chaînes. Mettez en favoris les flux que vous souhaitez voir dans le menu supérieur.';
$a->strings['Channel languages:'] = 'Langues de la chaîne :';
$a->strings['Select all languages that you want to see in your channels.'] = 'Sélectionnez les langues que vous souhaitez voir dans vos chaînes.';
$a->strings['Beginning of week:'] = 'Début de la semaine :';
$a->strings['Default calendar view:'] = 'Vue par défaut du calendrier :';
$a->strings['Additional Features'] = 'Fonctions supplémentaires';
$a->strings['Connected Apps'] = 'Applications connectées';
$a->strings['Remove authorization'] = 'Révoquer l\'autorisation';
$a->strings['Display Name is required.'] = 'Le nom d\'affichage est requis.';
$a->strings['Profile couldn\'t be updated.'] = 'Le profil n\'a pas pu être mis à jour.';
$a->strings['Label:'] = 'Description :';
$a->strings['Value:'] = 'Contenu :';
$a->strings['Field Permissions'] = 'Permissions du champ';
$a->strings['(click to open/close)'] = '(cliquer pour ouvrir/fermer)';
$a->strings['Add a new profile field'] = 'Ajouter un nouveau champ de profil';
$a->strings['The homepage is verified. A rel="me" link back to your Friendica profile page was found on the homepage.'] = 'La page d\'accueil est vérifiée. Un lien rel="me" vers votre page de profil Friendica a été trouvé sur la page d\'accueil.';
$a->strings['To verify your homepage, add a rel="me" link to it, pointing to your profile URL (%s).'] = 'Pour vérifier votre page d\'accueil, ajouter un lien rel="me" à celle-ci, pointant vers l\'URL de votre profil (%s).';
$a->strings['Profile Actions'] = 'Actions de Profil';
$a->strings['Edit Profile Details'] = 'Éditer les détails du profil';
$a->strings['Change Profile Photo'] = 'Changer la photo du profil';
$a->strings['Profile picture'] = 'Image de profil';
$a->strings['Location'] = 'Localisation';
$a->strings['Miscellaneous'] = 'Divers';
$a->strings['Custom Profile Fields'] = 'Champs de profil personalisés';
$a->strings['Upload Profile Photo'] = 'Téléverser une photo de profil';
$a->strings['<p>Custom fields appear on <a href="%s">your profile page</a>.</p>
				<p>You can use BBCodes in the field values.</p>
				<p>Reorder by dragging the field title.</p>
				<p>Empty the label field to remove a custom field.</p>
				<p>Non-public fields can only be seen by the selected Friendica contacts or the Friendica contacts in the selected circles.</p>'] = '<p>Les champs de profil personnalisés apparaissent sur <a href="%s">votre page de profil</a>.</p>
				<p>Vous pouvez utilisez les BBCodes dans le contenu des champs.</p>
				<p>Triez les champs en glissant-déplaçant leur titre.</p>
				<p>Laissez le titre d\'un champ vide pour le supprimer lors de la soumission du formulaire .</p>
				<p>Les champs non-publics peuvent être consultés uniquement par les contacts Friendica autorisés ou par les contacts Friendica de cercles autorisés.</p>';
$a->strings['Street Address:'] = 'Adresse postale :';
$a->strings['Locality/City:'] = 'Ville :';
$a->strings['Region/State:'] = 'Région / État :';
$a->strings['Postal/Zip Code:'] = 'Code postal :';
$a->strings['Country:'] = 'Pays :';
$a->strings['XMPP (Jabber) address:'] = 'Adresse XMPP (Jabber) :';
$a->strings['The XMPP address will be published so that people can follow you there.'] = 'L\'adresse XMPP sera publiée de façon à ce que les autres personnes puissent vous y suivre.';
$a->strings['Matrix (Element) address:'] = 'Adresse Matrix (Element) :';
$a->strings['The Matrix address will be published so that people can follow you there.'] = 'L\'adresse Matrix sera publiée de façon à ce que les autres personnes puissent vous y suivre.';
$a->strings['Homepage URL:'] = 'Page personnelle :';
$a->strings['Public Keywords:'] = 'Mots-clés publics :';
$a->strings['(Used for suggesting potential friends, can be seen by others)'] = '(Utilisés pour vous suggérer des abonnements. Ils peuvent être vus par autrui)';
$a->strings['Private Keywords:'] = 'Mots-clés privés :';
$a->strings['(Used for searching profiles, never shown to others)'] = '(Utilisés pour rechercher des profils. Ils ne seront jamais montrés à autrui)';
$a->strings['Image size reduction [%s] failed.'] = 'Réduction de la taille de l\'image [%s] échouée.';
$a->strings['Shift-reload the page or clear browser cache if the new photo does not display immediately.'] = 'Rechargez la page avec la touche Maj pressée, ou bien effacez le cache du navigateur, si d\'aventure la nouvelle photo n\'apparaissait pas immédiatement.';
$a->strings['Unable to process image'] = 'Impossible de traiter l\'image';
$a->strings['Photo not found.'] = 'Photo introuvable.';
$a->strings['Profile picture successfully updated.'] = 'Photo de profil mise à jour avec succès.';
$a->strings['Crop Image'] = '(Re)cadrer l\'image';
$a->strings['Please adjust the image cropping for optimum viewing.'] = 'Ajustez le cadre de l\'image pour une visualisation optimale.';
$a->strings['Use Image As Is'] = 'Utiliser l\'image telle quelle';
$a->strings['Missing uploaded image.'] = 'Image téléversée manquante';
$a->strings['Profile Picture Settings'] = 'Réglages de la photo de profil';
$a->strings['Current Profile Picture'] = 'Photo de profil actuelle';
$a->strings['Upload Profile Picture'] = 'Téléverser une photo de profil';
$a->strings['Upload Picture:'] = 'Téléverser une photo :';
$a->strings['or'] = 'ou';
$a->strings['skip this step'] = 'ignorer cette étape';
$a->strings['select a photo from your photo albums'] = 'choisissez une photo depuis vos albums';
$a->strings['There was a validation error, please make sure you\'re logged in with the account you want to remove and try again.'] = 'Il y a eu une erreur de validation, vérifiez que vous êtes connecté avec le compte que vous souhaitez supprimer et réessayez.';
$a->strings['If this error persists, please contact your administrator.'] = 'Si cette erreur persiste, veuillez contacter votre administrateur.';
$a->strings['[Friendica System Notify]'] = '[Notification Système de Friendica]';
$a->strings['User deleted their account'] = 'L\'utilisateur a supprimé son compte';
$a->strings['On your Friendica node an user deleted their account. Please ensure that their data is removed from the backups.'] = 'Sur votre nœud Friendica, un utilisateur a supprimé son compte. Veuillez vous assurer que ses données sont supprimées des sauvegardes.';
$a->strings['The user id is %d'] = 'L\'identifiant d\'utilisateur est %d';
$a->strings['Your account has been successfully removed. Bye bye!'] = 'Votre compte a été supprimé avec succès. Au revoir !';
$a->strings['Remove My Account'] = 'Supprimer mon compte';
$a->strings['This will completely remove your account. Once this has been done it is not recoverable.'] = 'Ceci supprimera totalement votre compte. Cette opération est irréversible.';
$a->strings['Please enter your password for verification:'] = 'Veuillez saisir votre mot de passe pour vérification :';
$a->strings['Do you want to ignore this server?'] = 'Voulez-vous ignorer ce serveur ?';
$a->strings['Do you want to unignore this server?'] = 'Voulez-vous ne plus ignorer ce serveur ?';
$a->strings['Remote server settings'] = 'Paramètres du serveur distant';
$a->strings['Server URL'] = 'URL du serveur';
$a->strings['Settings saved'] = 'Paramètres sauvegardés';
$a->strings['Here you can find all the remote servers you have taken individual moderation actions against. For a list of servers your node has blocked, please check out the <a href="friendica">Information</a> page.'] = 'Vous trouverez ici tous les serveurs distants pour lesquels vous avez pris des mesures de modération individuelles. Pour obtenir une liste des serveurs que votre nœud a bloqués, veuillez consulter la page <a href="friendica">Information</a>.';
$a->strings['Delete all your settings for the remote server'] = 'Supprime tous vos paramètres du serveur distant';
$a->strings['Save changes'] = 'Sauvegarder les changements';
$a->strings['Please enter your password to access this page.'] = 'Veuillez saisir votre mot de passe pour accéder à cette page.';
$a->strings['App-specific password generation failed: The description is empty.'] = 'La génération du mot de passe spécifique à l\'application a échoué : la description est vide.';
$a->strings['App-specific password generation failed: This description already exists.'] = 'La génération du mot de passe spécifique à l\'application a échoué : cette description existe déjà.';
$a->strings['New app-specific password generated: %s'] = 'Nouveau mot de passe applicatif généré : %s';
$a->strings['App-specific passwords successfully revoked.'] = 'Mots de passe spécifiques à des applications révoqués avec succès.';
$a->strings['App-specific password successfully revoked.'] = 'Mot de passe spécifique à l\'application révoqué avec succès.';
$a->strings['Two-factor app-specific passwords'] = 'Authentification à deux facteurs : Mots de passe spécifiques aux applications';
$a->strings['<p>App-specific passwords are randomly generated passwords used instead your regular password to authenticate your account on third-party applications that don\'t support two-factor authentication.</p>'] = '<p>Les mots de passe spécifiques aux application sont des mots de passe générés aléatoirement pour vous identifier avec votre compte Friendica sur des applications tierce-partie qui n\'offrent pas d\'authentification à deux facteurs.</p>';
$a->strings['Make sure to copy your new app-specific password now. You won’t be able to see it again!'] = 'Veillez à copier votre nouveau mot de passe spécifique à l\'application maintenant. Il ne sera plus jamais affiché!';
$a->strings['Last Used'] = 'Dernière utilisation';
$a->strings['Revoke'] = 'Révoquer';
$a->strings['Revoke All'] = 'Révoquer tous';
$a->strings['When you generate a new app-specific password, you must use it right away, it will be shown to you once after you generate it.'] = 'Une fois que votre nouveau mot de passe spécifique à l\'application est généré, vous devez l\'utiliser immédiatement car il ne vous sera pas remontré plus tard.';
$a->strings['Generate new app-specific password'] = 'Générer un nouveau mot de passe spécifique à une application';
$a->strings['Friendiqa on my Fairphone 2...'] = 'Friendiqa sur mon Fairphone 2...';
$a->strings['Generate'] = 'Générer';
$a->strings['Two-factor authentication successfully disabled.'] = 'Authentification à deux facteurs désactivée avec succès.';
$a->strings['<p>Use an application on a mobile device to get two-factor authentication codes when prompted on login.</p>'] = '<p>Utilisez une application mobile pour obtenir des codes d\'authentification à deux facteurs que vous devrez fournir lors de la saisie de vos identifiants.</p>';
$a->strings['Authenticator app'] = 'Application mobile';
$a->strings['Configured'] = 'Configurée';
$a->strings['Not Configured'] = 'Pas encore configurée';
$a->strings['<p>You haven\'t finished configuring your authenticator app.</p>'] = '<p>Vous n\'avez pas complété la configuration de votre application mobile d\'authentification.</p>';
$a->strings['<p>Your authenticator app is correctly configured.</p>'] = '<p>Votre application mobile d\'authentification est correctement configurée.</p>';
$a->strings['Recovery codes'] = 'Codes de secours';
$a->strings['Remaining valid codes'] = 'Codes valides restant';
$a->strings['<p>These one-use codes can replace an authenticator app code in case you have lost access to it.</p>'] = '<p>Ces codes à usage unique peuvent remplacer un code de votre application mobile d\'authentification si vous n\'y avez pas ou plus accès.</p>';
$a->strings['App-specific passwords'] = 'Mots de passe spécifiques aux applications';
$a->strings['Generated app-specific passwords'] = 'Générer des mots de passe d\'application';
$a->strings['<p>These randomly generated passwords allow you to authenticate on apps not supporting two-factor authentication.</p>'] = '<p>Ces mots de passe générés aléatoirement vous permettent de vous identifier sur des applications tierce-partie qui ne supportent pas l\'authentification à deux facteurs.</p>';
$a->strings['Current password:'] = 'Mot de passe actuel :';
$a->strings['You need to provide your current password to change two-factor authentication settings.'] = 'Vous devez saisir votre mot de passe actuel pour changer les réglages de l\'authentification à deux facteurs.';
$a->strings['Enable two-factor authentication'] = 'Activer l\'authentification à deux facteurs';
$a->strings['Disable two-factor authentication'] = 'Désactiver l\'authentification à deux facteurs';
$a->strings['Show recovery codes'] = 'Montrer les codes de secours';
$a->strings['Manage app-specific passwords'] = 'Gérer les mots de passe spécifiques aux applications';
$a->strings['Manage trusted browsers'] = 'Gérer les navigateurs de confiance';
$a->strings['Finish app configuration'] = 'Compléter la configuration de l\'application mobile';
$a->strings['New recovery codes successfully generated.'] = 'Nouveaux codes de secours générés avec succès.';
$a->strings['Two-factor recovery codes'] = 'Codes d\'identification de secours';
$a->strings['<p>Recovery codes can be used to access your account in the event you lose access to your device and cannot receive two-factor authentication codes.</p><p><strong>Put these in a safe spot!</strong> If you lose your device and don’t have the recovery codes you will lose access to your account.</p>'] = '<p>Les codes de secours peuvent être utilisés pour accéder à votre compte dans l\'eventualité où vous auriez perdu l\'accès à votre application mobile d\'authentification à deux facteurs.</p><p><strong>Prenez soin de ces codes !</strong> Si vous perdez votre appareil mobile et n\'avez pas de codes de secours vous n\'aurez plus accès à votre compte.</p>';
$a->strings['When you generate new recovery codes, you must copy the new codes. Your old codes won’t work anymore.'] = 'Après avoir généré de nouveaux codes de secours, veillez à remplacer les anciens qui ne seront plus valides.';
$a->strings['Generate new recovery codes'] = 'Générer de nouveaux codes de secours';
$a->strings['Next: Verification'] = 'Prochaine étape : Vérification';
$a->strings['Trusted browsers successfully removed.'] = 'Les navigateurs de confiance ont bien été supprimés.';
$a->strings['Trusted browser successfully removed.'] = 'Le navigateur de confiance a bien été supprimé.';
$a->strings['Two-factor Trusted Browsers'] = 'Navigateurs de confiance pour la 2FA';
$a->strings['Trusted browsers are individual browsers you chose to skip two-factor authentication to access Friendica. Please use this feature sparingly, as it can negate the benefit of two-factor authentication.'] = 'Les navigateurs de confiance sont des navigateurs individuels pour lesquels vous avez choisi de ne pas utiliser l\'identification à deux facteurs pour accéder à Friendica. Merci d\'utiliser cette fonctionnalité avec discernement, au vu du fait qu\'elle peut annuler les bénéfices de l\'identification à deux facteurs.';
$a->strings['Device'] = 'Périphérique';
$a->strings['OS'] = 'Système d\'exploitation';
$a->strings['Trusted'] = 'De confiance';
$a->strings['Created At'] = 'Créé à';
$a->strings['Last Use'] = 'Dernière utilisation';
$a->strings['Remove All'] = 'Tout supprimer';
$a->strings['Two-factor authentication successfully activated.'] = 'Authentification à deux facteurs activée avec succès.';
$a->strings['<p>Or you can submit the authentication settings manually:</p>
<dl>
	<dt>Issuer</dt>
	<dd>%s</dd>
	<dt>Account Name</dt>
	<dd>%s</dd>
	<dt>Secret Key</dt>
	<dd>%s</dd>
	<dt>Type</dt>
	<dd>Time-based</dd>
	<dt>Number of digits</dt>
	<dd>6</dd>
	<dt>Hashing algorithm</dt>
	<dd>SHA-1</dd>
</dl>'] = '<p>Ou bien vous pouvez saisir les paramètres de l\'authentification manuellement:</p>
<dl>
	<dt>Émetteur</dt>
	<dd>%s</dd>
	<dt>Nom du compte</dt>
	<dd>%s</dd>
	<dt>Clé secrète</dt>
	<dd>%s</dd>
	<dt>Type</dt>
	<dd>Temporel</dd>
	<dt>Nombre de chiffres</dt>
	<dd>6</dd>
	<dt>Algorithme de hachage</dt>
	<dd>SHA-1</dd>
</dl>';
$a->strings['Two-factor code verification'] = 'Vérification du code d\'identification';
$a->strings['<p>Please scan this QR Code with your authenticator app and submit the provided code.</p>'] = '<p>Veuillez scanner ce QR Code avec votre application mobile d\'authenficiation à deux facteurs et saisissez le code qui s\'affichera.</p>';
$a->strings['<p>Or you can open the following URL in your mobile device:</p><p><a href="%s">%s</a></p>'] = '<p>Ou vous pouvez ouvrir l\'adresse suivante sur votre périphérique mobile: </p><p><a href="%s">%s</a></p>';
$a->strings['Verify code and enable two-factor authentication'] = 'Vérifier le code d\'identification et activer l\'authentification à deux facteurs';
$a->strings['Export account'] = 'Exporter le compte';
$a->strings['Export your account info and contacts. Use this to make a backup of your account and/or to move it to another server.'] = 'Exportez votre compte, vos infos et vos contacts. Vous pourrez utiliser le résultat comme sauvegarde et/ou pour le ré-importer sur un autre serveur.';
$a->strings['Export all'] = 'Tout exporter';
$a->strings['Export your account info, contacts and all your items as json. Could be a very big file, and could take a lot of time. Use this to make a full backup of your account (photos are not exported)'] = 'Exporte vos informations de compte, vos contacts et toutes vos publications au format JSON. Ce processus peut prendre beaucoup de temps et générer un fichier de taille importante. Utilisez cette fonctionnalité pour faire une sauvegarde complète de votre compte (vos photos ne sont pas exportées).';
$a->strings['Export Contacts to CSV'] = 'Exporter vos contacts au format CSV';
$a->strings['Export the list of the accounts you are following as CSV file. Compatible to e.g. Mastodon.'] = 'Exporter vos abonnements au format CSV. Compatible avec Mastodon.';
$a->strings['The top-level post isn\'t visible.'] = 'La publication de premier niveau n\'est pas visible.';
$a->strings['The top-level post was deleted.'] = 'La publication de premier niveau a été supprimée.';
$a->strings['This node has blocked the top-level author or the author of the shared post.'] = 'Ce nœud a bloqué l\'auteur de premier niveau ou l\'auteur de la publication partagée.';
$a->strings['You have ignored or blocked the top-level author or the author of the shared post.'] = 'Vous avez ignoré ou bloqué l\'auteur de premier niveau ou l\'auteur de la publication partagée.';
$a->strings['You have ignored the top-level author\'s server or the shared post author\'s server.'] = 'Vous avez ignoré le serveur de l\'auteur de premier niveau ou le serveur de l\'auteur de la publication partagée.';
$a->strings['Conversation Not Found'] = 'Conversation Non Trouvée';
$a->strings['Unfortunately, the requested conversation isn\'t available to you.'] = 'Malheureusement, la conversation demandée n\'est pas disponible pour vous.';
$a->strings['Possible reasons include:'] = 'Les raisons possibles sont :';
$a->strings['Stack trace:'] = 'Stack trace:';
$a->strings['Exception thrown in %s:%d'] = 'Exception produite dans %s:%d';
$a->strings['At the time of registration, and for providing communications between the user account and their contacts, the user has to provide a display name (pen name), an username (nickname) and a working email address. The names will be accessible on the profile page of the account by any visitor of the page, even if other profile details are not displayed. The email address will only be used to send the user notifications about interactions, but wont be visibly displayed. The listing of an account in the node\'s user directory or the global user directory is optional and can be controlled in the user settings, it is not necessary for communication.'] = 'Au moment de l\'inscription, et afin de fournir des communications entre le compte de l\'utilisateur et ses contacts, l\'utilisateur doit fournir un nom d\'affichage (nom de plume), un nom d\'utilisateur (pseudo) et une adresse de courriel fonctionnelle. Les noms seront accessibles sur la page de profil du compte par tout visiteur de la page, même si les autres informations de profil ne sont pas affichées. L\'adresse de courriel ne sera utilisée que pour envoyer des notifications à l\'utilisateur à propos de ses interactions, mais ne sera pas affichée de manière visible. Le référencement du compte dans le répertoire des comptes du nœud ou le répertoire global des utilisateurs est facultatif et peut être contrôlé dans les paramètres utilisateur, il n\'est pas nécessaire pour la communication.  ';
$a->strings['This data is required for communication and is passed on to the nodes of the communication partners and is stored there. Users can enter additional private data that may be transmitted to the communication partners accounts.'] = 'Ces données sont requises pour la communication et transférées aux nœuds des partenaires de communication, et sont stockées ici. Les utilisateurs peuvent ajouter des données privées additionnelles qui peuvent être transmises aux comptes de leurs partenaires de communication.';
$a->strings['At any point in time a logged in user can export their account data from the <a href="%1$s/settings/userexport">account settings</a>. If the user wants to delete their account they can do so at <a href="%1$s/settings/removeme">%1$s/settings/removeme</a>. The deletion of the account will be permanent. Deletion of the data will also be requested from the nodes of the communication partners.'] = 'A n\'importe quel moment, un utilisateur connecté peut exporter les données de son compte à partir des <a href="%1$s/settings/userexport">Paramètres du compte</a>. Si l\'utilisateur souhaite supprimer son compte, il peut le faire à partir de la page <a href="%1$s/settings/removeme">%1$s/settings/removeme</a>. La suppression du compte sera permanente. La suppression des données sera également demandée aux noeuds des partenaires de communication.';
$a->strings['Privacy Statement'] = 'Politique de Confidentialité';
$a->strings['Rules'] = 'Règles';
$a->strings['Parameter uri_id is missing.'] = 'Le paramètre uri_id est manquant.';
$a->strings['The requested item doesn\'t exist or has been deleted.'] = 'L\'objet recherché n\'existe pas ou a été supprimé.';
$a->strings['You are now logged in as %s'] = 'Vous êtes maintenant connecté en tant que %s';
$a->strings['Switch between your accounts'] = 'Changer de compte';
$a->strings['Manage your accounts'] = 'Gérér vos comptes';
$a->strings['Toggle between different identities or community/group pages which share your account details or which you have been granted "manage" permissions'] = 'Basculez entre les différentes identités ou pages (groupes/communautés) qui se partagent votre compte ou que vous avez été autorisé à gérer.';
$a->strings['Select an identity to manage: '] = 'Choisir une identité à gérer: ';
$a->strings['User imports on closed servers can only be done by an administrator.'] = 'L\'import d\'utilisateur sur un serveur fermé ne peut être effectué que par un administrateur.';
$a->strings['Move account'] = 'Migrer le compte';
$a->strings['You can import an account from another Friendica server.'] = 'Vous pouvez importer un compte d\'un autre serveur Friendica.';
$a->strings['You need to export your account from the old server and upload it here. We will recreate your old account here with all your contacts. We will try also to inform your friends that you moved here.'] = 'Vous devez exporter votre compte à partir de l\'ancien serveur et le téléverser ici. Nous recréerons votre ancien compte ici avec tous vos contacts. Nous tenterons également d\'informer vos contacts que vous avez déménagé ici.';
$a->strings['This feature is experimental. We can\'t import contacts from the OStatus network (GNU Social/Statusnet) or from Diaspora'] = 'Cette fonctionalité est expérimentale. Il n\'est pas possible d\'importer des contacts depuis le réseau OStatus (GNU Social/Statusnet) ou depuis Diaspora.';
$a->strings['Account file'] = 'Fichier du compte';
$a->strings['To export your account, go to "Settings->Export your personal data" and select "Export account"'] = 'Pour exporter votre compte, allez dans "Paramètres> Exporter vos données personnelles" et sélectionnez "exportation de compte"';
$a->strings['Error decoding account file'] = 'Une erreur a été détecté en décodant un fichier utilisateur';
$a->strings['Error! No version data in file! This is not a Friendica account file?'] = 'Erreur ! Pas de ficher de version existant ! Êtes vous sur un compte Friendica ?';
$a->strings['User \'%s\' already exists on this server!'] = 'L\'utilisateur \'%s\' existe déjà sur ce serveur!';
$a->strings['User creation error'] = 'Erreur de création d\'utilisateur';
$a->strings['%d contact not imported'] = [
	0 => '%d contacts non importés',
	1 => '%d contacts non importés',
	2 => '%d contacts non importés',
];
$a->strings['User profile creation error'] = 'Erreur de création du profil utilisateur';
$a->strings['Done. You can now login with your username and password'] = 'Action réalisée. Vous pouvez désormais vous connecter avec votre nom d\'utilisateur et votre mot de passe';
$a->strings['Welcome to Friendica'] = 'Bienvenue sur Friendica';
$a->strings['New Member Checklist'] = 'Checklist du nouvel utilisateur';
$a->strings['We would like to offer some tips and links to help make your experience enjoyable. Click any item to visit the relevant page. A link to this page will be visible from your home page for two weeks after your initial registration and then will quietly disappear.'] = 'Nous souhaiterions vous donner quelques astuces et ressources pour rendre votre expérience la plus agréable possible. Cliquez sur n\'importe lequel de ces éléments pour visiter la page correspondante. Un lien vers cette page restera visible sur votre page d\'accueil pendant les deux semaines qui suivent votre inscription initiale, puis disparaîtra silencieusement.';
$a->strings['Getting Started'] = 'Bien démarrer';
$a->strings['Friendica Walk-Through'] = 'Friendica pas-à-pas';
$a->strings['On your <em>Quick Start</em> page - find a brief introduction to your profile and network tabs, make some new connections, and find some groups to join.'] = 'Sur votre page d\'accueil, dans <em>Conseils aux nouveaux venus</em> - vous trouverez une rapide introduction aux onglets Profil et Réseau, pourrez vous connecter à Facebook, établir de nouvelles relations, et choisir des groupes à rejoindre.';
$a->strings['Go to Your Settings'] = 'Éditer vos Réglages';
$a->strings['On your <em>Settings</em> page -  change your initial password. Also make a note of your Identity Address. This looks just like an email address - and will be useful in making friends on the free social web.'] = 'Sur la page des <em>Réglages</em> -     changez votre mot de passe initial. Notez bien votre Identité. Elle ressemble à une adresse de courriel - et vous sera utile pour vous faire des amis dans le web social libre.';
$a->strings['Review the other settings, particularly the privacy settings. An unpublished directory listing is like having an unlisted phone number. In general, you should probably publish your listing - unless all of your friends and potential friends know exactly how to find you.'] = 'Vérifiez les autres réglages, tout particulièrement ceux liés à la vie privée. Un profil non listé, c\'est un peu comme un numéro sur liste rouge. En général, vous devriez probablement publier votre profil - à moins que tous vos amis (potentiels) sachent déjà comment vous trouver.';
$a->strings['Upload a profile photo if you have not done so already. Studies have shown that people with real photos of themselves are ten times more likely to make friends than people who do not.'] = 'Téléversez (envoyez) une photo de profil si vous n\'en avez pas déjà une. Les études montrent que les gens qui affichent de vraies photos d\'eux sont dix fois plus susceptibles de se faire des amis.';
$a->strings['Edit Your Profile'] = 'Éditer votre Profil';
$a->strings['Edit your <strong>default</strong> profile to your liking. Review the settings for hiding your list of friends and hiding the profile from unknown visitors.'] = 'Éditez votre profil <strong>par défaut</strong> à votre convenance. Vérifiez les réglages concernant la visibilité de votre liste d\'amis par les visiteurs inconnus.';
$a->strings['Profile Keywords'] = 'Mots-clés du profil';
$a->strings['Set some public keywords for your profile which describe your interests. We may be able to find other people with similar interests and suggest friendships.'] = 'Choisissez quelques mots-clé publics pour votre profil qui décrivent vos intérêts. Nous pourrons peut-être trouver d\'autres personnes aux intérêts similaires et suggérer des abonnements.';
$a->strings['Connecting'] = 'Connexions';
$a->strings['Importing Emails'] = 'Importer courriels';
$a->strings['Enter your email access information on your Connector Settings page if you wish to import and interact with friends or mailing lists from your email INBOX'] = 'Entrez vos paramètres de courriel dans les Réglages des connecteurs si vous souhaitez importer et interagir avec des amis ou des listes venant de votre Boîte de Réception.';
$a->strings['Go to Your Contacts Page'] = 'Consulter vos Contacts';
$a->strings['Your Contacts page is your gateway to managing friendships and connecting with friends on other networks. Typically you enter their address or site URL in the <em>Add New Contact</em> dialog.'] = 'Votre page Contacts est le point d\'entrée vers la gestion de vos contacts et l\'abonnement à des contacts sur d\'autres serveurs. Vous pourrez y saisir leur adresse d\'Identité ou l\'URL de leur site dans le formulaire <em>Ajouter un nouveau contact</em>.';
$a->strings['Go to Your Site\'s Directory'] = 'Consulter l\'Annuaire de votre Site';
$a->strings['The Directory page lets you find other people in this network or other federated sites. Look for a <em>Connect</em> or <em>Follow</em> link on their profile page. Provide your own Identity Address if requested.'] = 'La page Annuaire vous permet de trouver d\'autres personnes au sein de ce réseaux ou parmi d\'autres sites fédérés. Cherchez un lien <em>Relier</em> ou <em>Suivre</em> sur leur profil. Vous pourrez avoir besoin d\'indiquer votre adresse d\'identité.';
$a->strings['Finding New People'] = 'Trouver de nouvelles personnes';
$a->strings['On the side panel of the Contacts page are several tools to find new friends. We can match people by interest, look up people by name or interest, and provide suggestions based on network relationships. On a brand new site, friend suggestions will usually begin to be populated within 24 hours.'] = 'Sur le panneau latéral de la page Contacts, il y a plusieurs moyens de trouver de nouveaux contacts. Nous pouvons mettre les gens en relation selon leurs intérêts, rechercher des amis par nom ou intérêt, et fournir des suggestions en fonction de la topologie du réseau. Sur un site tout neuf, les suggestions d\'abonnement devraient commencer à apparaître au bout de 24 heures.';
$a->strings['Add Your Contacts To Circle'] = 'Ajouter vos contacts à des cercles';
$a->strings['Once you have made some friends, organize them into private conversation circles from the sidebar of your Contacts page and then you can interact with each circle privately on your Network page.'] = 'Une fois que vous vous êtes fait des amis, organisez-les en cercles de conversation privés dans la barre latérale de votre page Contacts. Vous pouvez ensuite interagir avec chaque cercle en privé sur votre page Réseau.';
$a->strings['Why Aren\'t My Posts Public?'] = 'Pourquoi mes éléments ne sont pas publics ?';
$a->strings['Friendica respects your privacy. By default, your posts will only show up to people you\'ve added as friends. For more information, see the help section from the link above.'] = 'Friendica respecte votre vie privée. Par défaut, toutes vos publications seront seulement montrés à vos amis. Pour plus d\'information, consultez la section "aide" du lien ci-dessus.';
$a->strings['Getting Help'] = 'Obtenir de l\'aide';
$a->strings['Go to the Help Section'] = 'Aller à la section Aide';
$a->strings['Our <strong>help</strong> pages may be consulted for detail on other program features and resources.'] = 'Nos pages d\'<strong>aide</strong> peuvent être consultées pour davantage de détails sur les fonctionnalités ou les ressources.';
$a->strings['{0} wants to follow you'] = '{0} souhaite vous suivre';
$a->strings['{0} has started following you'] = '{0} a commencé à vous suivre';
$a->strings['%s liked %s\'s post'] = '%s a aimé la publication de %s';
$a->strings['%s disliked %s\'s post'] = '%s n\'a pas aimé la publication de %s';
$a->strings['%s is attending %s\'s event'] = '%s participe à l\'évènement de %s';
$a->strings['%s is not attending %s\'s event'] = '%s ne participe pas à l\'évènement de %s';
$a->strings['%s may attending %s\'s event'] = '%s participe peut-être à l\'évènement de %s';
$a->strings['%s is now friends with %s'] = '%s est désormais ami(e) avec %s';
$a->strings['%s commented on %s\'s post'] = '%s a commenté la publication de %s';
$a->strings['%s created a new post'] = '%s a créé une nouvelle publication';
$a->strings['Friend Suggestion'] = 'Suggestion d\'abonnement';
$a->strings['Friend/Connect Request'] = 'Demande de connexion/relation';
$a->strings['%1$s wants to follow you'] = '%1$s veut s\'abonner à votre contenu';
$a->strings['%1$s has started following you'] = '%1$s a commencé à vous suivre';
$a->strings['%1$s liked your comment on %2$s'] = '%1$s a aimé votre commentaire sur %2$s';
$a->strings['%1$s liked your post %2$s'] = '%1$s a aimé votre publication %2$s';
$a->strings['%1$s disliked your comment on %2$s'] = '%1$s n\'a pas aimé votre commentaire sur %2$s';
$a->strings['%1$s disliked your post %2$s'] = '%1$s n\'a pas aimé votre publication %2$s';
$a->strings['%1$s shared your comment %2$s'] = '%1$s a partagé votre commentaire %2$s';
$a->strings['%1$s shared your post %2$s'] = '%1$s a partagé votre publication %2$s';
$a->strings['%1$s shared the post %2$s from %3$s'] = '%1$s a partagé la publication %2$s de %3$s';
$a->strings['%1$s shared a post from %3$s'] = '%1$s a partagé une publication de %3$s';
$a->strings['%1$s shared the post %2$s'] = '%1$s a partagé la publication %2$s';
$a->strings['%1$s shared a post'] = '%1$s a partagé une publication';
$a->strings['%1$s wants to attend your event %2$s'] = '%1$s souhaite participer à votre évènement %2$s';
$a->strings['%1$s does not want to attend your event %2$s'] = '%1$s ne souhaite pas participer à votre évènement %2$s';
$a->strings['%1$s maybe wants to attend your event %2$s'] = '%1$s souhaite peut-être participer à votre évènement %2$s';
$a->strings['%1$s tagged you on %2$s'] = '%1$s vous a mentionné(e) dans %2$s';
$a->strings['%1$s replied to you on %2$s'] = '%1$s vous a répondu dans %2$s';
$a->strings['%1$s commented in your thread %2$s'] = '%1$s a commenté dans votre conversation %2$s';
$a->strings['%1$s commented on your comment %2$s'] = '%1$s a répondu à votre commentaire %2$s';
$a->strings['%1$s commented in their thread %2$s'] = '%1$s a commenté dans sa conversation %2$s';
$a->strings['%1$s commented in their thread'] = '%1$s a commenté dans sa conversation';
$a->strings['%1$s commented in the thread %2$s from %3$s'] = '%1$s a commenté dans la conversation %2$s de %3$s';
$a->strings['%1$s commented in the thread from %3$s'] = '%1$s a commenté dans la conversation de %3$s';
$a->strings['%1$s commented on your thread %2$s'] = '%1$s a commenté dans votre conversation %2$s';
$a->strings['[Friendica:Notify]'] = '[Friendica:Notification]';
$a->strings['%s New mail received at %s'] = '%s Nouveau message privé reçu sur %s';
$a->strings['%1$s sent you a new private message at %2$s.'] = '%1$s vous a envoyé un nouveau message privé sur %2$s.';
$a->strings['a private message'] = 'un message privé';
$a->strings['%1$s sent you %2$s.'] = '%1$s vous a envoyé %2$s.';
$a->strings['Please visit %s to view and/or reply to your private messages.'] = 'Merci de visiter %s pour voir vos messages privés et/ou y répondre.';
$a->strings['%1$s commented on %2$s\'s %3$s %4$s'] = '%1$s a commenté sur %3$s de %2$s %4$s';
$a->strings['%1$s commented on your %2$s %3$s'] = '%1$s a commenté sur votre %2$s %3$s';
$a->strings['%1$s commented on their %2$s %3$s'] = '%1$s a commenté sur son %2$s %3$s';
$a->strings['%1$s Comment to conversation #%2$d by %3$s'] = '%1$s Nouveau commentaire dans la conversation #%2$d par %3$s';
$a->strings['%s commented on an item/conversation you have been following.'] = '%s a commenté un élément que vous suivez.';
$a->strings['Please visit %s to view and/or reply to the conversation.'] = 'Merci de visiter %s pour voir la conversation et/ou y répondre.';
$a->strings['%s %s posted to your profile wall'] = '%s %s a posté sur votre mur';
$a->strings['%1$s posted to your profile wall at %2$s'] = '%1$s a publié sur votre mur à %2$s';
$a->strings['%1$s posted to [url=%2$s]your wall[/url]'] = '%1$s a posté sur [url=%2$s]votre mur[/url]';
$a->strings['%s Introduction received'] = '%s Demande de mise en contact reçue';
$a->strings['You\'ve received an introduction from \'%1$s\' at %2$s'] = 'Vous avez reçu une introduction de \'%1$s\' sur %2$s';
$a->strings['You\'ve received [url=%1$s]an introduction[/url] from %2$s.'] = 'Vous avez reçu [url=%1$s]une introduction[/url] de %2$s.';
$a->strings['You may visit their profile at %s'] = 'Vous pouvez visiter son profil sur %s';
$a->strings['Please visit %s to approve or reject the introduction.'] = 'Merci de visiter %s pour approuver ou rejeter l\'introduction.';
$a->strings['%s A new person is sharing with you'] = '%s Quelqu\'un a commencé à partager avec vous';
$a->strings['%1$s is sharing with you at %2$s'] = '%1$s partage avec vous sur %2$s';
$a->strings['%s You have a new follower'] = '%s Vous avez un nouvel abonné';
$a->strings['You have a new follower at %2$s : %1$s'] = 'Vous avez un nouvel abonné à %2$s : %1$s';
$a->strings['%s Friend suggestion received'] = '%s Suggestion de mise en contact reçue';
$a->strings['You\'ve received a friend suggestion from \'%1$s\' at %2$s'] = 'Vous avez reçu une suggestion de \'%1$s\' sur %2$s';
$a->strings['You\'ve received [url=%1$s]a friend suggestion[/url] for %2$s from %3$s.'] = 'Vous avez reçu [url=%1$s]une suggestion[/url] de %3$s pour %2$s.';
$a->strings['Name:'] = 'Nom :';
$a->strings['Photo:'] = 'Photo :';
$a->strings['Please visit %s to approve or reject the suggestion.'] = 'Merci de visiter %s pour approuver ou rejeter la suggestion.';
$a->strings['%s Connection accepted'] = '%s Demande d\'abonnement acceptée';
$a->strings['\'%1$s\' has accepted your connection request at %2$s'] = '\'%1$s\' a accepté votre demande de connexion à %2$s';
$a->strings['%2$s has accepted your [url=%1$s]connection request[/url].'] = '%2$s a accepté votre [url=%1$s]demande de connexion[/url].';
$a->strings['You are now mutual friends and may exchange status updates, photos, and email without restriction.'] = 'Vous êtes désormais mutuellement amis, et pouvez échanger des mises à jour d\'état, des photos, et des courriels sans restriction.';
$a->strings['Please visit %s if you wish to make any changes to this relationship.'] = 'Veuillez visiter %s si vous souhaitez modifier cette relation.';
$a->strings['\'%1$s\' has chosen to accept you a fan, which restricts some forms of communication - such as private messaging and some profile interactions. If this is a celebrity or community page, these settings were applied automatically.'] = '\'%1$s\' a choisi de vous accepter comme fan ce qui empêche certains canaux de communication tel les messages privés et certaines interactions de profil. Ceci est une page de célébrité ou de communauté, ces paramètres ont été appliqués automatiquement.';
$a->strings['\'%1$s\' may choose to extend this into a two-way or more permissive relationship in the future.'] = '%1$s peut choisir à l\'avenir de rendre cette relation réciproque ou au moins plus permissive.';
$a->strings['Please visit %s  if you wish to make any changes to this relationship.'] = 'Veuillez visiter %s si vous souhaitez modifier cette relation.';
$a->strings['registration request'] = 'demande d\'inscription';
$a->strings['You\'ve received a registration request from \'%1$s\' at %2$s'] = 'Vous avez reçu une demande d\'inscription de %1$s sur %2$s';
$a->strings['You\'ve received a [url=%1$s]registration request[/url] from %2$s.'] = '%2$s vous a envoyé une [url=%1$s]demande de création de compte[/url].';
$a->strings['Display Name:	%s
Site Location:	%s
Login Name:	%s (%s)'] = 'Nom d\'Affichage :	%s
Emplacement :	%s
Nom de connexion :	%s (%s)';
$a->strings['Please visit %s to approve or reject the request.'] = 'Veuillez visiter %s pour approuver ou rejeter la demande.';
$a->strings['new registration'] = 'Nouvelle inscription';
$a->strings['You\'ve received a new registration from \'%1$s\' at %2$s'] = 'Vous avez reçu une nouvelle inscription de \'%1$s\' à %2$s';
$a->strings['You\'ve received a [url=%1$s]new registration[/url] from %2$s.'] = 'Vous avez reçu une [url=%1$s]nouvelle inscription[/url] de %2$s.';
$a->strings['Please visit %s to have a look at the new registration.'] = 'Merci de visiter %s pour consulter la nouvelle inscription.';
$a->strings['%s %s tagged you'] = '%s%s vous a mentionné(e)';
$a->strings['%s %s shared a new post'] = '%s %s a partagé une nouvelle publication';
$a->strings['%1$s %2$s liked your post #%3$d'] = '%1$s %2$s a aimé votre publication #%3$d';
$a->strings['%1$s %2$s liked your comment on #%3$d'] = '%1$s %2$s a aimé votre commentaire sur #%3$d';
$a->strings['This message was sent to you by %s, a member of the Friendica social network.'] = 'Ce message vous a été envoyé par %s, membre du réseau social Friendica.';
$a->strings['You may visit them online at %s'] = 'Vous pouvez leur rendre visite sur %s';
$a->strings['Please contact the sender by replying to this post if you do not wish to receive these messages.'] = 'Merci de contacter l’émetteur en répondant à cette publication si vous ne souhaitez pas recevoir ces messages.';
$a->strings['%s posted an update.'] = '%s a publié une mise à jour.';
$a->strings['Private Message'] = 'Message privé';
$a->strings['Public Message'] = 'Message Public';
$a->strings['Unlisted Message'] = 'Message non référencé';
$a->strings['This entry was edited'] = 'Cette entrée a été éditée';
$a->strings['Connector Message'] = 'Message du connecteur';
$a->strings['Edit'] = 'Éditer';
$a->strings['Delete globally'] = 'Effacer globalement';
$a->strings['Remove locally'] = 'Effacer localement';
$a->strings['Block %s'] = 'Bloquer %s';
$a->strings['Ignore %s'] = 'Ignorer %s';
$a->strings['Collapse %s'] = 'Réduire %s';
$a->strings['Report post'] = 'Signaler la publication';
$a->strings['Save to folder'] = 'Sauvegarder dans le dossier';
$a->strings['I will attend'] = 'Je vais participer';
$a->strings['I will not attend'] = 'Je ne vais pas participer';
$a->strings['I might attend'] = 'Je vais peut-être participer';
$a->strings['Ignore thread'] = 'Ignorer cette conversation';
$a->strings['Unignore thread'] = 'Ne pas ignorer cette conversation';
$a->strings['Toggle ignore status'] = 'Commuter le statut de suivi';
$a->strings['Add star'] = 'Ajouter une étoile';
$a->strings['Remove star'] = 'Retirer l\'étoile';
$a->strings['Toggle star status'] = 'Commuter l\'état de l\'étoile';
$a->strings['Pin'] = 'Épingler';
$a->strings['Unpin'] = 'Désépingler';
$a->strings['Toggle pin status'] = 'Commuter le statut de l\'épingle';
$a->strings['Pinned'] = 'Épinglé';
$a->strings['Add tag'] = 'Ajouter un tag';
$a->strings['Quote share this'] = 'Citer et repartager ceci';
$a->strings['Quote Share'] = 'Citer et repartager';
$a->strings['Reshare this'] = 'Partager ceci';
$a->strings['Reshare'] = 'Partager';
$a->strings['Cancel your Reshare'] = 'Annuler votre repartage';
$a->strings['Unshare'] = 'Ne plus partager';
$a->strings['%s (Received %s)'] = '%s ( Reçu %s)';
$a->strings['Comment this item on your system'] = 'Commenter ce sujet sur votre instance';
$a->strings['Remote comment'] = 'Commentaire distant';
$a->strings['Share via ...'] = 'Partager par...';
$a->strings['Share via external services'] = 'Partager par des services externes';
$a->strings['Unknown parent'] = 'Parent inconnu';
$a->strings['in reply to %s'] = 'en réponse à %s';
$a->strings['Parent is probably private or not federated.'] = 'Le parent est probablement privé ou non fédéré.';
$a->strings['to'] = 'à';
$a->strings['via'] = 'via';
$a->strings['Wall-to-Wall'] = 'Inter-mur';
$a->strings['via Wall-To-Wall:'] = 'en Inter-mur :';
$a->strings['Reply to %s'] = 'Répondre à %s';
$a->strings['More'] = 'Plus';
$a->strings['Notifier task is pending'] = 'La notification de la tâche est en cours';
$a->strings['Delivery to remote servers is pending'] = 'La distribution aux serveurs distants est en attente';
$a->strings['Delivery to remote servers is underway'] = 'La distribution aux serveurs distants est en cours';
$a->strings['Delivery to remote servers is mostly done'] = 'La distribution aux serveurs distants est presque terminée';
$a->strings['Delivery to remote servers is done'] = 'La distribution aux serveurs distants est terminée';
$a->strings['%d comment'] = [
	0 => '%d commentaire',
	1 => '%d commentaires',
	2 => '%d commentaires',
];
$a->strings['Show more'] = 'Montrer plus';
$a->strings['Show fewer'] = 'Montrer moins';
$a->strings['Reshared by: %s'] = 'Partagé par : %s';
$a->strings['Viewed by: %s'] = 'Vu par : %s';
$a->strings['Read by: %s'] = 'Lu par : %s';
$a->strings['Liked by: %s'] = 'Aimé par : %s';
$a->strings['Disliked by: %s'] = 'Pas aimé par : %s';
$a->strings['Attended by: %s'] = 'Y assisteront : %s';
$a->strings['Maybe attended by: %s'] = 'Y assisteront peut-être : %s';
$a->strings['Not attended by: %s'] = 'N\'y assisteront pas : %s';
$a->strings['Commented by: %s'] = 'Commenté par : %s';
$a->strings['Reacted with %s by: %s'] = 'La réaction %s a été faite par : %s';
$a->strings['Quote shared by: %s'] = 'Cité et repartagé par : %s';
$a->strings['Chat'] = 'Chat';
$a->strings['(no subject)'] = '(aucun sujet)';
$a->strings['The folder %s must be writable by webserver.'] = 'Le répertoire %s doit être accessible en écriture par le serveur web.';
$a->strings['Login failed.'] = 'Échec de connexion.';
$a->strings['Login failed. Please check your credentials.'] = 'Échec d\'authentification. Merci de vérifier vos identifiants.';
$a->strings['Welcome %s'] = 'Bienvenue %s';
$a->strings['Please upload a profile photo.'] = 'Merci d\'illustrer votre profil d\'une image.';
$a->strings['OpenWebAuth: %1$s welcomes %2$s'] = '%1$s souhaite la bienvenue à %2$s grâce à OpenWebAuth';
$a->strings['Friendica Notification'] = 'Notification Friendica';
$a->strings['%1$s, %2$s Administrator'] = 'L\'administrateur de %1$s, %2$s.';
$a->strings['%s Administrator'] = 'L\'administrateur de %s';
$a->strings['thanks'] = 'merci';
$a->strings['YYYY-MM-DD or MM-DD'] = 'AAAA-MM-JJ ou MM-JJ';
$a->strings['Time zone: <strong>%s</strong> <a href="%s">Change in Settings</a>'] = 'Fuseau horaire : <strong>%s</strong> <a href="%s">Le changer dans les paramètres</a>';
$a->strings['never'] = 'jamais';
$a->strings['less than a second ago'] = 'il y a moins d\'une seconde';
$a->strings['year'] = 'année';
$a->strings['years'] = 'années';
$a->strings['months'] = 'mois';
$a->strings['weeks'] = 'semaines';
$a->strings['days'] = 'jours';
$a->strings['hour'] = 'heure';
$a->strings['hours'] = 'heures';
$a->strings['minute'] = 'minute';
$a->strings['minutes'] = 'minutes';
$a->strings['second'] = 'seconde';
$a->strings['seconds'] = 'secondes';
$a->strings['in %1$d %2$s'] = 'dans %1$d %2$s';
$a->strings['%1$d %2$s ago'] = 'Il y a %1$d %2$s';
$a->strings['Notification from Friendica'] = 'Notification de Friendica';
$a->strings['Empty Post'] = 'Publication vide';
$a->strings['default'] = 'Par défaut';
$a->strings['greenzero'] = 'greenzero';
$a->strings['purplezero'] = 'purplezero';
$a->strings['easterbunny'] = 'easterbunny';
$a->strings['darkzero'] = 'darkzero';
$a->strings['comix'] = 'comix';
$a->strings['slackr'] = 'slackr';
$a->strings['Variations'] = 'Variations';
$a->strings['Note'] = 'Note';
$a->strings['Check image permissions if all users are allowed to see the image'] = 'Vérifier les permissions des images si tous les utilisateurs sont autorisés à voir l\'image';
$a->strings['Appearance'] = 'Apparence';
$a->strings['Accent color'] = 'Couleur d\'accentuation';
$a->strings['Blue'] = 'Bleu';
$a->strings['Red'] = 'Rouge';
$a->strings['Purple'] = 'Violet';
$a->strings['Green'] = 'Vert';
$a->strings['Pink'] = 'Rose';
$a->strings['Copy or paste schemestring'] = 'Copier ou coller le fil conducteur';
$a->strings['You can copy this string to share your theme with others. Pasting here applies the schemestring'] = 'Vous pouvez copier le contenu de ce champ pour partager votre thème. Vous pouvez également y coller une définition de palette différente pour l\'appliquer à votre thème.';
$a->strings['Navigation bar background color'] = 'Couleur d\'arrière-plan de la barre de navigation';
$a->strings['Navigation bar icon color '] = 'Couleur des icônes de la barre de navigation';
$a->strings['Link color'] = 'Couleur des liens';
$a->strings['Set the background color'] = 'Paramétrer la couleur d\'arrière-plan';
$a->strings['Content background opacity'] = 'Opacité du contenu d\'arrière-plan';
$a->strings['Set the background image'] = 'Paramétrer l\'image d\'arrière-plan';
$a->strings['Background image style'] = 'Style de l\'image de fond';
$a->strings['Always open Compose page'] = 'Toujours ouvrir la page Compose';
$a->strings['The New Post button always open the <a href="/compose">Compose page</a> instead of the modal form. When this is disabled, the Compose page can be accessed with a middle click on the link or from the modal.'] = 'Le bouton Nouvelle publication ouvre systématiquement la <a href="/compose">page Compose</a> à la place du formulaire modal. Quand désactivé, la page Compose peut être ouverte via un clic milieu sur le lien ou à partir du modal.';
$a->strings['Login page background image'] = 'Image de fond de la page de login';
$a->strings['Login page background color'] = 'Couleur d\'arrière-plan de la page de login';
$a->strings['Leave background image and color empty for theme defaults'] = 'Laisser l\'image et la couleur de fond vides pour les paramètres par défaut du thème';
$a->strings['Top Banner'] = 'Bannière du haut';
$a->strings['Resize image to the width of the screen and show background color below on long pages.'] = 'Redimensionner l\'image à la largeur de l\'écran et combler en dessous avec la couleur d\'arrière plan sur les pages longues.';
$a->strings['Full screen'] = 'Plein écran';
$a->strings['Resize image to fill entire screen, clipping either the right or the bottom.'] = 'Agrandir l\'image pour remplir l\'écran, jusqu\'à toucher le bord droit ou le bas de l\'écran.';
$a->strings['Single row mosaic'] = 'Mosaïque sur une seule colonne';
$a->strings['Resize image to repeat it on a single row, either vertical or horizontal.'] = 'Redimensionner l\'image pour la répéter sur une seule colonne, verticale ou horizontale.';
$a->strings['Mosaic'] = 'Mosaïque';
$a->strings['Repeat image to fill the screen.'] = 'Répète l\'image pour couvrir l\'écran.';
$a->strings['Skip to main content'] = 'Aller au contenu principal';
$a->strings['Back to top'] = 'Retour en haut';
$a->strings['Light'] = 'Clair';
$a->strings['Dark'] = 'Sombre';
$a->strings['Custom'] = 'Personnalisé';
$a->strings['Guest'] = 'Invité';
$a->strings['Visitor'] = 'Visiteur';
$a->strings['Alignment'] = 'Alignement';
$a->strings['Left'] = 'Gauche';
$a->strings['Center'] = 'Centré';
$a->strings['Color scheme'] = 'Schéma de couleurs';
$a->strings['Posts font size'] = 'Taille de texte des publications';
$a->strings['Textareas font size'] = 'Taille de police des zones de texte';
$a->strings['Comma separated list of helper groups'] = 'Liste de groupe d\'entraide, séparés par des virgules';
$a->strings['don\'t show'] = 'cacher';
$a->strings['show'] = 'montrer';
$a->strings['Set style'] = 'Définir le style';
$a->strings['Community Pages'] = 'Pages Communautaires';
$a->strings['Community Profiles'] = 'Profils communautaires';
$a->strings['Help or @NewHere ?'] = 'Besoin d\'aide ou @NouveauIci ?';
$a->strings['Connect Services'] = 'Connecter des services';
$a->strings['Find Friends'] = 'Trouver des contacts';
$a->strings['Last users'] = 'Derniers utilisateurs';
$a->strings['Quick Start'] = 'Démarrage rapide';
