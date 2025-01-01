<?php

if(! function_exists("string_plural_select_gd")) {
function string_plural_select_gd($n){
	$n = intval($n);
	if (($n==1 || $n==11)) { return 0; } else if (($n==2 || $n==12)) { return 1; } else if (($n > 2 && $n < 20)) { return 2; } else  { return 3; }
}}
$a->strings['Unable to locate original post.'] = 'Cha do lorg sinn am post tùsail.';
$a->strings['Post updated.'] = 'Chaidh am post ùrachadh.';
$a->strings['Item wasn\'t stored.'] = 'Cha deach an nì a stòradh.';
$a->strings['Item couldn\'t be fetched.'] = 'Cha d’ fhuair sinn grèim air a nì.';
$a->strings['Empty post discarded.'] = 'Chaidh post falamh a thilgeil air falbh.';
$a->strings['Item not found.'] = 'Cha deach an nì a lorg.';
$a->strings['Permission denied.'] = 'Chaidh cead a dhiùltadh.';
$a->strings['No valid account found.'] = 'Cha deach cunntas dligheach a lorg.';
$a->strings['Password reset request issued. Check your email.'] = 'Chaidh ath-shuidheachadh an fhacail-fhaire iarraidh. Thoir sùil air a’ phost-d agad.';
$a->strings['
		Dear %1$s,
			A request was recently received at "%2$s" to reset your account
		password. In order to confirm this request, please select the verification link
		below or paste it into your web browser address bar.

		If you did NOT request this change, please DO NOT follow the link
		provided and ignore and/or delete this email, the request will expire shortly.

		Your password will not be changed unless we can verify that you
		issued this request.'] = '
		%1$s, a charaid
			Fhuair sinn iarrtas aig “%2$s” o chionn goirid airson facal-faire a’ chunntais agad
		ath-shuidheachadh. Airson an t-iarrtas seo a dhearbhadh, tagh an ceangal dearbhaidh
		gu h-ìosal no cuir a-steach ann am bàr nan seòladh sa bhrabhsair-lìn agad e.

		MUR an do dh’iarr thu fhèin an t-atharrachadh seo, NA LEAN air a’ cheangal
		a tha ’ga sholar agus leig seachad agus/no sguab às am post-d seo ’s falbhaidh an ùine air an iarrtas a dh’aithghearr.

		Cha dèid am facal-faire agad atharrachadh ach mas urrainn dhuinn dearbhadh gun do
		dh’iarr thu fhèin seo.';
$a->strings['
		Follow this link soon to verify your identity:

		%1$s

		You will then receive a follow-up message containing the new password.
		You may change that password from your account settings page after logging in.

		The login details are as follows:

		Site Location:	%2$s
		Login Name:	%3$s'] = '
		Lean ris a’ cheangal seo a dh’aithghearr a dhearbhadh gur e tusa a bh’ ann:

		%1$s

		Ghaibh thu teachdaireachd eile an uairsin sa bheil am facal-faire ùr.
		’S urrainn dhut am facal-faire sin atharrachadh o dhuilleag roghainnean a’ chunntais agad às dèidh clàradh a-steach.

		Seo am fiosrachadh clàraidh a-steach:

		Ionad na làraich:	%2$s
		Ainm a’ chlàraidh a-steach:	%3$s';
$a->strings['Password reset requested at %s'] = 'Chaidh ath-shuidheachadh an fhacail-fhaire iarraidh aig %s';
$a->strings['Request could not be verified. (You may have previously submitted it.) Password reset failed.'] = 'Cha b’ urrainn dhuinn an t-iarrtas a dhearbhadh. (Dh’fhaoidte gun do chuir thu a-null e cheana.) Dh’fhàillig le ath-shuidheachadh an fhacail-fhaire.';
$a->strings['Request has expired, please make a new one.'] = 'Dh’fhalbh an ùine air an iarrtas, cruthaich fear ùr.';
$a->strings['Forgot your Password?'] = 'Na dhìochuimhnich thu am facal-faire agad?';
$a->strings['Enter your email address and submit to have your password reset. Then check your email for further instructions.'] = 'Cuir a-steach ’s a-null an seòladh puist-d agad airson ath-shuidheachadh an fhacail-fhaire agad. Thoir sùil air na puist-d agad an uairsin airson stiùireadh.';
$a->strings['Nickname or Email: '] = 'Far-ainm no post-d: ';
$a->strings['Reset'] = 'Ath-shuidhich';
$a->strings['Password Reset'] = 'Ath-shuidheachadh facail-fhaire';
$a->strings['Your password has been reset as requested.'] = 'Chaidh am facal-faire agad ath-shuidheachadh.';
$a->strings['Your new password is'] = 'Seo am facal-faire ùr agad:';
$a->strings['Save or copy your new password - and then'] = 'Sàbhail no dèan lethbhreac dhen fhacal-fhaire ùr agad. An uairsin,';
$a->strings['click here to login'] = 'briog an-seo gus clàradh a-steach';
$a->strings['Your password may be changed from the <em>Settings</em> page after successful login.'] = '’S urrainn dhut am facal-faire agad atharrachadh o dhuilleag nan <em>Roghainnean</em> às dèidh a’ chlàraidh a-steach.';
$a->strings['Your password has been reset.'] = 'Chaidh am facal-faire agad ath-shuidheachadh.';
$a->strings['
			Dear %1$s,
				Your password has been changed as requested. Please retain this
			information for your records (or change your password immediately to
			something that you will remember).
		'] = '
			%1$s, a charaid,
				Chaidh am facal-faire agad atharrachadh mar a dh’iarr thu. Cùm
			am fiosrachadh seo sna clàran agad (no atharraich am facal-faire agad sa bhad
			gu rudeigin as urrainn dhut cur ’nad chuimhne).
		';
$a->strings['
			Your login details are as follows:

			Site Location:	%1$s
			Login Name:	%2$s
			Password:	%3$s

			You may change that password from your account settings page after logging in.
		'] = '
			Seo am fiosrachadh clàraidh a-steach agad:

			Ionad na làraich:	%1$s
			Ainm clàraidh a-steach:	%2$s
			Facal-faire:	%3$s

			’S urrainn dhut am facal-faire agad atharrachadh o dhuilleag roghainnean a’ chunntais agad às dèidh a’ chlàraidh a-steach.
		';
$a->strings['Your password has been changed at %s'] = 'Chaidh am facal-faire agad atharrachadh air %s';
$a->strings['New Message'] = 'Teachdaireachd ùr';
$a->strings['No recipient selected.'] = 'Cha deach faightear a thaghadh.';
$a->strings['Unable to locate contact information.'] = 'Cha do lorg sinn am fiosrachadh conaltraidh.';
$a->strings['Message could not be sent.'] = 'Cha b’ urrainn dhuinn an teachdaireachd a chur.';
$a->strings['Message collection failure.'] = 'Dh’fhàillig cruinneachadh nan teachdaireachdan.';
$a->strings['Discard'] = 'Tilg air falbh';
$a->strings['Messages'] = 'Teachdaireachdan';
$a->strings['Conversation not found.'] = 'Cha deach an còmhradh a lorg.';
$a->strings['Message was not deleted.'] = 'Cha deach an teachdaireachd a sguabadh às.';
$a->strings['Conversation was not removed.'] = 'Cha deach an còmhradh a thoirt air falbh.';
$a->strings['Please enter a link URL:'] = 'Cuir URL ceangail:';
$a->strings['Send Private Message'] = 'Cuir teachdaireachd phearsanta';
$a->strings['To:'] = 'Gu:';
$a->strings['Subject:'] = 'Cuspair:';
$a->strings['Your message:'] = 'An teachdaireachd agad:';
$a->strings['Upload photo'] = 'Luchdaich suas dealbh';
$a->strings['Insert web link'] = 'Cuir a-steach ceangal-lìn';
$a->strings['Please wait'] = 'Fuirich ort';
$a->strings['Submit'] = 'Cuir a-null';
$a->strings['No messages.'] = 'Chan eil teachdaireachd sam bith ann.';
$a->strings['Message not available.'] = 'Chan eil an teachdaireachd ri fhaighinn.';
$a->strings['Delete message'] = 'Sguab às an teachdaireachd';
$a->strings['D, d M Y - g:i A'] = 'D d M Y – g:ia';
$a->strings['Delete conversation'] = 'Sguab às an còmhradh';
$a->strings['No secure communications available. You <strong>may</strong> be able to respond from the sender\'s profile page.'] = 'Chan eil conaltradh tèarainte ri fhaighinn. <strong>Dh’fhaoidte</strong> gur urrainn dhut freagairt o dhuilleag pròifil an t-seòladair.';
$a->strings['Send Reply'] = 'Cuir an fhreagairt';
$a->strings['Unknown sender - %s'] = 'Seòladair nach aithne dhuinn – %s';
$a->strings['You and %s'] = 'Thusa is %s';
$a->strings['%s and You'] = '%s is thusa';
$a->strings['%d message'] = [
	0 => '%d teachdaireachd',
	1 => '%d theachdaireachd',
	2 => '%d teachdaireachdan',
	3 => '%d teachdaireachd',
];
$a->strings['Personal Notes'] = 'Nòtaichean pearsanta';
$a->strings['Personal notes are visible only by yourself.'] = 'Chan fhaic ach thu fhèin na nòtaichean pearsanta.';
$a->strings['Save'] = 'Sàbhail';
$a->strings['User not found.'] = 'Cha deach an cleachdaiche a lorg.';
$a->strings['Photo Albums'] = 'Pasgain dhealbhan';
$a->strings['Recent Photos'] = 'Dealbhan o chionn goirid';
$a->strings['Upload New Photos'] = 'Luchdaich suas dealbhan ùra';
$a->strings['everybody'] = 'a h-uile duine';
$a->strings['Contact information unavailable'] = 'Chan eil fiosrachadh conaltraidh ri fhaighinn';
$a->strings['Album not found.'] = 'Cha deach an t-albam a lorg.';
$a->strings['Album successfully deleted'] = 'Chaidh an t-albam a sguabadh às';
$a->strings['Album was empty.'] = 'Bha an t-albam falamh.';
$a->strings['Failed to delete the photo.'] = 'Cha b’ urrainn dhuinn an dealbh a sguabadh às.';
$a->strings['a photo'] = 'dealbh';
$a->strings['%1$s was tagged in %2$s by %3$s'] = 'Chuir %3$s %1$s ’na thaga ri %2$s';
$a->strings['Public access denied.'] = 'Chaidh an t-inntrigeadh poblach a dhiùltadh.';
$a->strings['No photos selected'] = 'Cha deach dealbh a thaghadh';
$a->strings['The maximum accepted image size is %s'] = 'Cha ghabh sinn ri dealbhan nas motha na %s';
$a->strings['Upload Photos'] = 'Luchdaich suas dealbhan';
$a->strings['New album name: '] = 'Ainm an albaim ùir: ';
$a->strings['or select existing album:'] = 'no tagh albam a tha ann:';
$a->strings['Do not show a status post for this upload'] = 'Na seall post staide dhan luchdadh suas seo';
$a->strings['Permissions'] = 'Ceadan';
$a->strings['Do you really want to delete this photo album and all its photos?'] = 'A bheil thu cinnteach gu bheil thu airson an t-albam seo ’s a h-uile dealbh aige a sguabadh às?';
$a->strings['Delete Album'] = 'Sguab às an t-albam';
$a->strings['Cancel'] = 'Sguir dheth';
$a->strings['Edit Album'] = 'Deasaich an t-albam';
$a->strings['Drop Album'] = 'Thoir air falbh an t-albam';
$a->strings['Show Newest First'] = 'Seall an fheadhainn as ùire an toiseach';
$a->strings['Show Oldest First'] = 'Seall an fheadhainn as sine an toiseach';
$a->strings['View Photo'] = 'Seall an dealbh';
$a->strings['Permission denied. Access to this item may be restricted.'] = 'Chaidh cead a dhiùltadh. Dh’fhaoidte gu bheil an t-inntrigeadh dhan nì seo cuingichte.';
$a->strings['Photo not available'] = 'Chan eil an dealbhan ri fhaighinn';
$a->strings['Do you really want to delete this photo?'] = 'A bheil thu cinnteach gu bheil thu airson an dealbh seo a sguabadh às?';
$a->strings['Delete Photo'] = 'Sguab às an dealbh';
$a->strings['View photo'] = 'Seall an dealbh';
$a->strings['Edit photo'] = 'Deasaich an dealbh';
$a->strings['Delete photo'] = 'Sguab às an dealbh';
$a->strings['Use as profile photo'] = 'Cleachd ’na dhealbh pròifile';
$a->strings['Private Photo'] = 'Dealbh prìobhaideach';
$a->strings['View Full Size'] = 'Seall air a làn-mheud';
$a->strings['Tags: '] = 'Tagaichean: ';
$a->strings['[Select tags to remove]'] = '[Tagh tagaichean gus an toirt air falbh]';
$a->strings['New album name'] = 'Ainm albaim ùir';
$a->strings['Caption'] = 'Fo-thiotal';
$a->strings['Add a Tag'] = 'Cuir taga ris';
$a->strings['Example: @bob, @Barbara_Jensen, @jim@example.com, #California, #camping'] = 'Ball-eisimpleir: @aonghas, @Oighrig_Chaimbeul, @seaonaidh@ball-eisimpleir.com, #Leòdhas, #gàirnealaireachd';
$a->strings['Do not rotate'] = 'Na cuairtich';
$a->strings['Rotate CW (right)'] = 'Cuairtich a’ dol deiseil';
$a->strings['Rotate CCW (left)'] = 'Cuairtich a’ dol tuathail';
$a->strings['This is you'] = 'Seo thusa';
$a->strings['Comment'] = 'Beachd';
$a->strings['Preview'] = 'Ro-sheall';
$a->strings['Loading...'] = '’Ga luchdadh…';
$a->strings['Select'] = 'Tagh';
$a->strings['Delete'] = 'Sguab às';
$a->strings['Like'] = '’S toigh leam seo';
$a->strings['I like this (toggle)'] = '’S toigh leam seo (toglaich)';
$a->strings['Dislike'] = 'Cha toigh leam seo';
$a->strings['I don\'t like this (toggle)'] = 'Cha toigh leam seo (toglaich)';
$a->strings['Map'] = 'Mapa';
$a->strings['Apologies but the website is unavailable at the moment.'] = 'Tha sinn duilich ach chan eil an làrach-lìn ri fhaighinn an-dràsta.';
$a->strings['Delete this item?'] = 'A bheil thu airson an nì seo a sguabadh às?';
$a->strings['Block this author? They won\'t be able to follow you nor see your public posts, and you won\'t be able to see their posts and their notifications.'] = 'A bheil thu airson an t-ùghdar seo a bhacadh? Chan urrainn dhaibh leantainn ort no na postaichean poblach agad fhaicinn tuilleadh agus chan fhaic thu fhèin na postaichean no na brathan uapa.';
$a->strings['Ignore this author? You won\'t be able to see their posts and their notifications.'] = 'A bheil thu airson an t-ùghdar seo a leigeil seachad? Chan fhaic thu na postaichean no na brathan uapa.';
$a->strings['Collapse this author\'s posts?'] = 'A bheil thu airson postaichean an ùghdair seo a cho-theannachadh?';
$a->strings['Like not successful'] = 'Cha deach leinn a chur ris na h-annsachdan';
$a->strings['Dislike not successful'] = 'Cha deach leinn a thoirt air falbh o na h-annsachdan';
$a->strings['Sharing not successful'] = 'Cha deach leinn a cho-roinneadh';
$a->strings['Attendance unsuccessful'] = 'Cha deach leis an làthaireachd';
$a->strings['Backend error'] = 'Mearachd a’ backend';
$a->strings['Network error'] = 'Mearachd lìonraidh';
$a->strings['Drop files here to upload'] = 'Leig às faidhlichean an-seo gus an luchdadh suas';
$a->strings['Your browser does not support drag and drop file uploads.'] = 'Cha chuir am brabhsair agad taic ri luchdadh suas fhaidhlichean le slaodadh is leigeil às.';
$a->strings['Please use the fallback form below to upload your files like in the olden days.'] = 'Cleachd an t-seann-dòigh airson faidhlichean a luchdadh suas leis an fhoirm gu h-ìosal.';
$a->strings['File is too big ({{filesize}}MiB). Max filesize: {{maxFilesize}}MiB.'] = 'Tha am faidhle ro mhòr ({{filesize}}MiB). Meud as motha nam faidhlichean: {{maxFilesize}}MiB.';
$a->strings['You can\'t upload files of this type.'] = 'Chan urrainn dhut faidhlichean dhen t-seòrsa seo a luchdadh suas.';
$a->strings['Server responded with {{statusCode}} code.'] = 'Dh’fhreagair am frithealaiche le còd {{statusCode}}.';
$a->strings['Cancel upload'] = 'Sguir dhen luchdadh suas';
$a->strings['Upload canceled.'] = 'Chaidh sgur dhen luchdadh suas.';
$a->strings['Are you sure you want to cancel this upload?'] = 'A bheil thu cinnteach gu bheil thu airson sgur dhen luchdadh suas seo?';
$a->strings['Remove file'] = 'Thoir am faidhle air falbh';
$a->strings['You can\'t upload any more files.'] = 'Chan urrainn dhut barrachd fhaidhlichean a luchdadh suas.';
$a->strings['toggle mobile'] = 'toglaich mobile';
$a->strings['Method not allowed for this module. Allowed method(s): %s'] = 'Chan eil am modh ceadaichte dhan mhòideal seo. Modh(an) ceadaichte: %s';
$a->strings['Page not found.'] = 'Cha deach an duilleag a lorg.';
$a->strings['You must be logged in to use addons. '] = 'Feumaidh tu clàradh a-steach mus urrainn dhut tuilleadain a chleachdadh. ';
$a->strings['No system theme config value set.'] = 'Cha deach luach a shuidheachadh do rèiteachadh ùrlar an t-siostaim.';
$a->strings['The form security token was not correct. This probably happened because the form has been opened for too long (>3 hours) before submitting it.'] = 'Cha robh tòcan tèarainteachd an fhoirm mar bu chòir. Tha sinn an dùil gun do thachair sin air sgàth ’s gun robh am foirm fosgailte do fhada (>3 uairean a thìde) mus deach a chur a-null.';
$a->strings['All contacts'] = 'A h-uile neach-aithne';
$a->strings['Followers'] = 'Luchd-leantainn';
$a->strings['Following'] = 'A’ leantainn';
$a->strings['Common'] = 'Cumanta';
$a->strings['Addon not found'] = 'Cha deach an tuilleadan a lorg';
$a->strings['Addon already enabled'] = 'Tha an tuilleadan an comas mu thràth';
$a->strings['Addon already disabled'] = 'Tha an tuilleadan à comas mu thràth';
$a->strings['Could not find any unarchived contact entry for this URL (%s)'] = 'Cha do lorg sinn neach-aithne nach eil san tasg-lann dhan URL seo (%s)';
$a->strings['The contact entries have been archived'] = 'Chaidh an luchd-aithne a chur san tasg-lann';
$a->strings['Could not find any contact entry for this URL (%s)'] = 'Cha do lorg sinn neach-aithne dhan URL seo (%s)';
$a->strings['The contact has been blocked from the node'] = 'Chaidh an neach-aithne a bhacadh on nòd';
$a->strings['%d %s, %d duplicates.'] = '%d %s, dùblachaidhean: %d.';
$a->strings['uri-id is empty for contact %s.'] = 'Tha uri-id falamh dhan neach-aithne %s.';
$a->strings['No valid first contact found for uri-id %d.'] = 'Cha deach ciad neach-aithne dligheach a lorg dha uri-id %d.';
$a->strings['Wrong duplicate found for uri-id %d in %d (url: %s != %s).'] = 'Lorg sinn an dùblachadh ceàrr dha uri-id %d am broinn %d (url: %s != %s).';
$a->strings['Wrong duplicate found for uri-id %d in %d (nurl: %s != %s).'] = 'Lorg sinn an dùblachadh ceàrr dha uri-id %d am broinn %d (nurl: %s != %s).';
$a->strings['Deletion of id %d failed'] = 'Cha deach leinn id %d a sguabadh às';
$a->strings['Deletion of id %d was successful'] = 'Chaidh id %d a sguabadh às';
$a->strings['Updating "%s" in "%s" from %d to %d'] = 'Ag ùrachadh “%s” am broinn “%s” o %d gu %d';
$a->strings[' - found'] = ' – air a lorg';
$a->strings[' - failed'] = ' – air fàilligeadh';
$a->strings[' - success'] = ' – chaidh leis';
$a->strings[' - deleted'] = ' – air a sguabadh às';
$a->strings[' - done'] = ' – deiseil';
$a->strings['The avatar cache needs to be enabled to use this command.'] = 'Feumaidh tasgadan nan avatar a bhith an comas mus urrainn dhut an àithne seo a chleachdadh.';
$a->strings['no resource in photo %s'] = 'chan eil goireas san dealbh %s';
$a->strings['no photo with id %s'] = 'chan eil dealbh leis an id %s ann';
$a->strings['no image data for photo with id %s'] = 'chan eil dàta deilbh dhan dealbh leis an id %s ann';
$a->strings['invalid image for id %s'] = 'dealbh mì-dhligheach dhan id %s';
$a->strings['Quit on invalid photo %s'] = 'Fàg an-seo ma tha dealbh %s mì-dhligheach ann';
$a->strings['Post update version number has been set to %s.'] = 'Chaidh àireamh tionndadh an ùrachaidh aig a’ phost a shuidheachadh air %s.';
$a->strings['Check for pending update actions.'] = 'A’ sgrùdadh air gnìomhan ùrachaidh rin dèiligeadh.';
$a->strings['Done.'] = 'Deiseil.';
$a->strings['Execute pending post updates.'] = 'A’ gnìomhachadh nan ùrachaidhean air postaichean rin dèiligeadh.';
$a->strings['All pending post updates are done.'] = 'Tha na h-ùrachaidhean air postaichean rin dèiligeadh deiseil.';
$a->strings['Enter user nickname: '] = 'Cuir a-steach far-ainm a’ chleachdaiche: ';
$a->strings['User not found'] = 'Cha deach an cleachdaiche a lorg';
$a->strings['Enter new password: '] = 'Cuir a-steach am facal-faire ùr: ';
$a->strings['Password update failed. Please try again.'] = 'Dh’fhàillig ùrachadh an fhacail-fhaire. Feuch ris a-rithist.';
$a->strings['Password changed.'] = 'Chaidh am facal-faire atharrachadh.';
$a->strings['Enter user name: '] = 'Cuir a-steach ainm-cleachdaiche: ';
$a->strings['Enter user email address: '] = 'Cuir a-steach seòladh puist-d a’ chleachdaiche: ';
$a->strings['Enter a language (optional): '] = 'Cuir a-steach cànan (roghainneil): ';
$a->strings['User is not pending.'] = 'Chan eil an cleachdaiche ri dhèiligeadh.';
$a->strings['User has already been marked for deletion.'] = 'Chaidh comharra a chur mu thràth gun tèid an cleachdaiche a sguabadh às.';
$a->strings['Deletion aborted.'] = 'Chaidh sgur dhen sguabadh às.';
$a->strings['Enter category: '] = 'Cuir a-steach roinn-seòrsa: ';
$a->strings['Enter key: '] = 'Cuir a-steach iuchair: ';
$a->strings['Enter value: '] = 'Cuir a-steach luach: ';
$a->strings['newer'] = 'nas ùire';
$a->strings['older'] = 'nas sine';
$a->strings['Frequently'] = 'Gu tric';
$a->strings['Hourly'] = 'Gach uair a thìde';
$a->strings['Twice daily'] = 'Dà thuras gach latha';
$a->strings['Daily'] = 'Gach latha';
$a->strings['Weekly'] = 'Gach seachdain';
$a->strings['Monthly'] = 'Gach mìos';
$a->strings['DFRN'] = 'DFRN';
$a->strings['OStatus'] = 'OStatus';
$a->strings['RSS/Atom'] = 'RSS/Atom';
$a->strings['Email'] = 'Post-d';
$a->strings['Diaspora'] = 'Diaspora*';
$a->strings['Zot!'] = 'Zot!';
$a->strings['LinkedIn'] = 'LinkedIn';
$a->strings['XMPP/IM'] = 'XMPP/IM';
$a->strings['MySpace'] = 'MySpace';
$a->strings['Google+'] = 'Google+';
$a->strings['pump.io'] = 'pump.io';
$a->strings['Twitter'] = 'Twitter';
$a->strings['Discourse'] = 'Discourse';
$a->strings['Diaspora Connector'] = 'Ceangladair diaspora*';
$a->strings['GNU Social Connector'] = 'Ceangladair GNU Social';
$a->strings['ActivityPub'] = 'ActivityPub';
$a->strings['pnut'] = 'pnut';
$a->strings['Tumblr'] = 'Tumblr';
$a->strings['Bluesky'] = 'Bluesky';
$a->strings['%s (via %s)'] = '%s (slighe %s)';
$a->strings['and'] = 'agus';
$a->strings['and %d other people'] = 'agus %d eile';
$a->strings['%2$s likes this.'] = [
	0 => '’S toigh le %2$s seo.',
	1 => '’S toigh le %2$s seo.',
	2 => '’S toigh le %2$s seo.',
	3 => '’S toigh le %2$s seo.',
];
$a->strings['%2$s doesn\'t like this.'] = [
	0 => 'Cha toigh le %2$s seo.',
	1 => 'Cha toigh le %2$s seo.',
	2 => 'Cha toigh le %2$s seo.',
	3 => 'Cha toigh le %2$s seo.',
];
$a->strings['%2$s attends.'] = [
	0 => 'Bidh %2$s an làthair.',
	1 => 'Bidh %2$s an làthair.',
	2 => 'Bidh %2$s an làthair.',
	3 => 'Bidh %2$s an làthair.',
];
$a->strings['%2$s doesn\'t attend.'] = [
	0 => 'Cha bhi %2$s an làthair.',
	1 => 'Cha bhi %2$s an làthair.',
	2 => 'Cha bhi %2$s an làthair.',
	3 => 'Cha bhi %2$s an làthair.',
];
$a->strings['%2$s attends maybe.'] = [
	0 => '’S dòcha gum bi %2$s an làthair.',
	1 => '’S dòcha gum bi %2$s an làthair.',
	2 => '’S dòcha gum bi %2$s an làthair.',
	3 => '’S dòcha gum bi %2$s an làthair.',
];
$a->strings['%2$s reshared this.'] = [
	0 => 'Cho-roinn %2$s seo.',
	1 => 'Cho-roinn %2$s seo.',
	2 => 'Cho-roinn %2$s seo.',
	3 => 'Cho-roinn %2$s seo.',
];
$a->strings['<button type="button" %2$s>%1$d person</button> likes this'] = [
	0 => '’S toil le <button type="button" %2$s>%1$d</button> seo',
	1 => '’S toigh le <button type="button" %2$s>%1$d</button> seo',
	2 => '’S toigh le <button type="button" %2$s>%1$d</button> seo',
	3 => '’S toigh le <button type="button" %2$s>%1$d</button> seo',
];
$a->strings['<button type="button" %2$s>%1$d person</button> doesn\'t like this'] = [
	0 => 'Cha toigh le <button type="button" %2$s>%1$d</button> seo',
	1 => 'Cha toigh le <button type="button" %2$s>%1$d</button> seo',
	2 => 'Cha toigh le <button type="button" %2$s>%1$d</button> seo',
	3 => 'Cha toigh le <button type="button" %2$s>%1$d</button> seo',
];
$a->strings['<button type="button" %2$s>%1$d person</button> attends'] = [
	0 => 'Bidh <button type="button" %2$s>%1$d</button> an làthair',
	1 => 'Bidh <button type="button" %2$s>%1$d</button> an làthair',
	2 => 'Bidh <button type="button" %2$s>%1$d</button> an làthair',
	3 => 'Bidh <button type="button" %2$s>%1$d</button> an làthair',
];
$a->strings['<button type="button" %2$s>%1$d person</button> doesn\'t attend'] = [
	0 => 'Cha bhi <button type="button" %2$s>%1$d</button> an làthair.',
	1 => 'Cha bhi <button type="button" %2$s>%1$d</button> an làthair',
	2 => 'Cha bhi <button type="button" %2$s>%1$d</button> an làthair',
	3 => 'Cha bhi <button type="button" %2$s>%1$d</button> an làthair',
];
$a->strings['<button type="button" %2$s>%1$d person</button> attends maybe'] = [
	0 => '’S dòcha gum bi <button type="button" %2$s>%1$d</button> an làthair',
	1 => '’S dòcha gum bi <button type="button" %2$s>%1$d</button> an làthair',
	2 => '’S dòcha gum bi <button type="button" %2$s>%1$d</button> an làthair',
	3 => '’S dòcha gum bi <button type="button" %2$s>%1$d</button> an làthair',
];
$a->strings['<button type="button" %2$s>%1$d person</button> reshared this'] = [
	0 => 'Cho-roinn <button type="button" %2$s>%1$d</button> seo',
	1 => 'Cho-roinn <button type="button" %2$s>%1$d</button> seo',
	2 => 'Cho-roinn <button type="button" %2$s>%1$d</button> seo',
	3 => 'Cho-roinn <button type="button" %2$s>%1$d</button> seo',
];
$a->strings['Visible to <strong>everybody</strong>'] = 'Chì <strong>a h-uile duine</strong> e';
$a->strings['Please enter a image/video/audio/webpage URL:'] = 'Cuir a-steach URL deilbh/video/fuaime/làraich-lìn:';
$a->strings['Tag term:'] = 'Teirm tagaidh:';
$a->strings['Save to Folder:'] = 'Sàbhail gu pasgan:';
$a->strings['Where are you right now?'] = 'Càit a bheil thu an-dràsta?';
$a->strings['Delete item(s)?'] = 'An sguab thu seo às?';
$a->strings['Created at'] = 'Air a chruthachadh';
$a->strings['New Post'] = 'Post ùr';
$a->strings['Share'] = 'Co-roinn';
$a->strings['upload photo'] = 'luchdaich suas dealbh';
$a->strings['Attach file'] = 'Ceangail faidhle ris';
$a->strings['attach file'] = 'ceangail faidhle ris';
$a->strings['Bold'] = 'Trom';
$a->strings['Italic'] = 'Eadailteach';
$a->strings['Underline'] = 'Loidhne fodha';
$a->strings['Quote'] = 'Iomradh';
$a->strings['Add emojis'] = 'Cuir Emojis ris';
$a->strings['Content Warning'] = 'Rabhadh susbainte';
$a->strings['Code'] = 'Còd';
$a->strings['Image'] = 'Dealbh';
$a->strings['Link'] = 'Ceangal';
$a->strings['Link or Media'] = 'Ceangal no meadhan';
$a->strings['Video'] = 'Video';
$a->strings['Set your location'] = 'Suidhich d’ ionad';
$a->strings['set location'] = 'suidhich d’ ionad';
$a->strings['Clear browser location'] = 'Falamhaich ionad a’ bhrabhsair';
$a->strings['clear location'] = 'falamhaich an ionad';
$a->strings['Set title'] = 'Suidhich an tiotal';
$a->strings['Categories (comma-separated list)'] = 'Roinnean-seòrsa (liosta sgaraichte le cromagan).';
$a->strings['Scheduled at'] = 'Air an sgeideal';
$a->strings['Permission settings'] = 'Roghainnean cead';
$a->strings['Public post'] = 'Post poblach';
$a->strings['Message'] = 'Teachdaireachd';
$a->strings['Browser'] = 'Brabhsair';
$a->strings['Open Compose page'] = 'Fosgail duilleag an sgrìobhaidh';
$a->strings['remove'] = 'thoir air falbh';
$a->strings['Delete Selected Items'] = 'Sguab às na nithean a thagh thu';
$a->strings['You had been addressed (%s).'] = 'Chaidh d’ ainmeachadh (%s).';
$a->strings['You are following %s.'] = 'Tha thu a’ leantainn air %s.';
$a->strings['You subscribed to one or more tags in this post.'] = 'Dh’fho-sgrìobh thu air taga no dhà sa phost seo.';
$a->strings['%s reshared this.'] = 'Cho-roinn %s seo.';
$a->strings['Reshared'] = '’Ga cho-roinneadh';
$a->strings['Reshared by %s <%s>'] = '’Ga cho-roinneadh le  %s <%s>';
$a->strings['%s is participating in this thread.'] = 'Tha %s a’ gabhail pàirt san t-snàithlean seo.';
$a->strings['Stored for general reasons'] = 'Chaidh a stòradh air adhbharan coitcheann';
$a->strings['Global post'] = 'Post co-naisgte';
$a->strings['Sent via an relay server'] = 'Chaidh a chur slighe frithealaiche ath-sheachadain';
$a->strings['Sent via the relay server %s <%s>'] = 'Chaidh a chur slighe frithealaiche ath-sheachadain %s <%s>';
$a->strings['Fetched'] = 'Air fhaighinn';
$a->strings['Fetched because of %s <%s>'] = 'Air fhaighinn ri linn %s <%s>';
$a->strings['Stored because of a child post to complete this thread.'] = 'Chaidh a stòradh air sàilleibh post-cloinne airson an snàithlean iomlain fhaighinn.';
$a->strings['Local delivery'] = 'Lìbhrigeadh ionadail';
$a->strings['Stored because of your activity (like, comment, star, ...)'] = 'Chaidh a stòradh air sàilleibh do ghnìomhachd (’s toigh, beachd, rionnag, …)';
$a->strings['Distributed'] = 'Sgaoilte';
$a->strings['Pushed to us'] = 'Air a phutadh thugainne';
$a->strings['Pinned item'] = 'Nì prìnichte';
$a->strings['View %s\'s profile @ %s'] = 'Seall a’ phròifil aig %s @ %s';
$a->strings['Categories:'] = 'Roinnean-seòrsa:';
$a->strings['Filed under:'] = 'Air a chlàradh fo:';
$a->strings['%s from %s'] = '%s o %s';
$a->strings['View in context'] = 'Seall le co-theacsa';
$a->strings['Local Community'] = 'Coimhearsnachd ionadail';
$a->strings['Posts from local users on this server'] = 'Postaichean o luchd-cleachdaidh ionadail an fhrithealaiche seo';
$a->strings['Global Community'] = 'Coimhearsnachd cho-naisgte';
$a->strings['Posts from users of the whole federated network'] = 'Postaichean on luchd-cleachdaidh air an lìonra cho-naisgte gu lèir';
$a->strings['Latest Activity'] = 'A’ ghnìomhachd as ùire';
$a->strings['Sort by latest activity'] = 'Seòrsaich a-rèir na gnìomhachd as ùire';
$a->strings['Latest Posts'] = 'Na postaichean as ùire';
$a->strings['Sort by post received date'] = 'Seòrsaich a-rèir ceann-là faighinn nam post';
$a->strings['Latest Creation'] = 'An cruthachadh as ùire';
$a->strings['Sort by post creation date'] = 'Seòrsaich a-rèir ceann-là cruthachadh nam post';
$a->strings['Personal'] = 'Pearsanta';
$a->strings['Posts that mention or involve you'] = 'Postaichean le iomradh ort no sa bheil thu an sàs';
$a->strings['Starred'] = 'Rionnag';
$a->strings['Favourite Posts'] = 'Na postaichean as annsa';
$a->strings['General Features'] = 'Gleusan coitcheann';
$a->strings['Photo Location'] = 'Ionad an deilbh';
$a->strings['Photo metadata is normally stripped. This extracts the location (if present) prior to stripping metadata and links it to a map.'] = 'Thèid meata-dàta nan dealbhan a rùsgadh air falbh. Togaidh seo an t-ionad (ma tha gin ann) mus dèid am meata-dàta a rùsgadh is thèid a cheangal ri mapa.';
$a->strings['Post Composition Features'] = 'Gleusan sgrìobhadh puist';
$a->strings['Explicit Mentions'] = 'Iomraidhean soilleir';
$a->strings['Add explicit mentions to comment box for manual control over who gets mentioned in replies.'] = 'Cuir iomraidhean soilleir ri bogsa a’ bheachd airson smachd a làimh air cò air a thèid iomradh a dhèanamh ann am freagairtean.';
$a->strings['Add an abstract from ActivityPub content warnings'] = 'Cuir geàrr-chunntas ris o rabhaidhean susbainte ActivityPub';
$a->strings['Add an abstract when commenting on ActivityPub posts with a content warning. Abstracts are displayed as content warning on systems like Mastodon or Pleroma.'] = 'Cuir geàrr-chunntas ris nuair a bhios tu a’ beachdachadh air postaichean ActivityPub le rabhadh susbainte riutha. Thèid geàrr-chunntasan a shealltainn ’nan rabhaidhean susbainte air siostaman mar Mastodon no Pleroma.';
$a->strings['Post/Comment Tools'] = 'Innealan postaidh/beachdachaidh';
$a->strings['Post Categories'] = 'Roinnean-seòrsa nam post';
$a->strings['Add categories to your posts'] = 'Cuir roinnean-seòrsa ris na postaichean agad';
$a->strings['Circles'] = 'Cearcallan';
$a->strings['Groups'] = 'Buidhnean';
$a->strings['Archives'] = 'Tasg-lannan';
$a->strings['Protocols'] = 'Pròtacalan';
$a->strings['Account Types'] = 'Seòrsaichean chunntasan';
$a->strings['Saved Searches'] = 'Lorgan a shàbhail thu';
$a->strings['Saved Folders'] = 'Pasganan air an sàbhaladh';
$a->strings['Own Contacts'] = 'An luchd-aithne agadsa';
$a->strings['Trending Tags'] = 'Tagaichean a’ treandadh';
$a->strings['Advanced Profile Settings'] = 'Roghainnean adhartach na pròifile';
$a->strings['Tag Cloud'] = 'Neul nan tagaichean';
$a->strings['Provide a personal tag cloud on your profile page'] = 'Solair neul thagaichean pearsanta air duilleag do phròifile';
$a->strings['Display Membership Date'] = 'Seall ceann-là na ballrachd';
$a->strings['Display membership date in profile'] = 'Seall ceann-là na ballrachd sa phròifil';
$a->strings['Advanced Calendar Settings'] = 'Roghainnean adhartach a’ mhìosachain';
$a->strings['Allow anonymous access to your calendar'] = 'Ceadaich inntrigeadh gun ainm dhan mhìosachan agad';
$a->strings['Allows anonymous visitors to consult your calendar and your public events. Contact birthday events are private to you.'] = 'Leigidh seo le aoighean sùil a thoirt air a’ mhìosachan ’s air na tachartasan poblach agad. Bidh tachartasan cinn-là breith an luchd-aithne agad prìobhaideach dhut-sa.';
$a->strings['External link to group'] = 'Ceangal cèin dhan bhuidheann';
$a->strings['show less'] = 'seall nas lugha dheth';
$a->strings['show more'] = 'seall barrachd dheth';
$a->strings['Create new group'] = 'Cruthaich buidheann ùr';
$a->strings['event'] = 'tachartas';
$a->strings['status'] = 'staid';
$a->strings['photo'] = 'dealbh';
$a->strings['%1$s tagged %2$s\'s %3$s with %4$s'] = 'Chuir %1$s taga %4$s ri %3$s aig %2$s';
$a->strings['Follow Thread'] = 'Lean air an t-snàithlean';
$a->strings['View Status'] = 'Seall an staid';
$a->strings['View Profile'] = 'Seall a’ phròifil';
$a->strings['View Photos'] = 'Seall na dealbhan';
$a->strings['Network Posts'] = 'Postaichean lìonraidh';
$a->strings['View Contact'] = 'Seall an neach-aithne';
$a->strings['Send PM'] = 'Cuir TPh';
$a->strings['Block'] = 'Bac';
$a->strings['Ignore'] = 'Leig seachad';
$a->strings['Collapse'] = 'Co-theannaich';
$a->strings['Languages'] = 'Cànanan';
$a->strings['Connect/Follow'] = 'Ceangail ris/Lean air';
$a->strings['Unable to fetch user.'] = 'Chan urrainn dhuinn an cleachdaiche fhaighinn dhut.';
$a->strings['Nothing new here'] = 'Chan eil dad ùr an-seo';
$a->strings['Go back'] = 'Air ais';
$a->strings['Clear notifications'] = 'Falamhaich na brathan';
$a->strings['@name, !group, #tags, content'] = '@ainm, !buidheann, #tagaichean, susbaint';
$a->strings['Logout'] = 'Clàraich a-mach';
$a->strings['End this session'] = 'Cuir crìoch air an t-seisean seo';
$a->strings['Login'] = 'Clàraich a-steach';
$a->strings['Sign in'] = 'Clàraich a-steach';
$a->strings['Conversations'] = 'Còmhraidhean';
$a->strings['Conversations you started'] = 'Na còmhraidhean a thòisich thusa';
$a->strings['Profile'] = 'Pròifil';
$a->strings['Your profile page'] = 'Duilleag na pròifil agad';
$a->strings['Photos'] = 'Dealbhan';
$a->strings['Your photos'] = 'Na dealbhan agad';
$a->strings['Media'] = 'Meadhanan';
$a->strings['Your postings with media'] = 'Na postaichean agad sa bheil meadhanan';
$a->strings['Calendar'] = 'Mìosachan';
$a->strings['Your calendar'] = 'Am mìosachan agad';
$a->strings['Personal notes'] = 'Nòtaichean pearsanta';
$a->strings['Your personal notes'] = 'Na nòtaichean pearsanta agad';
$a->strings['Home'] = 'Dachaigh';
$a->strings['Home Page'] = 'Duilleag-dhachaigh';
$a->strings['Register'] = 'Clàraich leinn';
$a->strings['Create an account'] = 'Cruthaich cunntas';
$a->strings['Help'] = 'Cobhair';
$a->strings['Help and documentation'] = 'Cobhair is docamaideadh';
$a->strings['Apps'] = 'Aplacaidean';
$a->strings['Addon applications, utilities, games'] = 'Tuilleadain aplacaide, goireis is geama';
$a->strings['Search'] = 'Lorg';
$a->strings['Search site content'] = 'Lorg susbaint san làrach';
$a->strings['Full Text'] = 'Teacsa slàn';
$a->strings['Tags'] = 'Tagaichean';
$a->strings['Contacts'] = 'Luchd-aithne';
$a->strings['Community'] = 'Coimhearsnachd';
$a->strings['Conversations on this and other servers'] = 'Còmhraidhean air an fhrithealaiche seo is frithealaichean eile';
$a->strings['Directory'] = 'Eòlaire';
$a->strings['People directory'] = 'Eòlaire nan daoine';
$a->strings['Information'] = 'Fiosrachadh';
$a->strings['Information about this friendica instance'] = 'Fiosrachadh mun ionstans Friendica seo';
$a->strings['Terms of Service'] = 'Teirmichean na seirbheise';
$a->strings['Terms of Service of this Friendica instance'] = 'Teirmichean seirbheise an ionstans Friendica seo';
$a->strings['Network'] = 'Lìonra';
$a->strings['Conversations from your friends'] = 'Còmhraidhean nan caraidean agad';
$a->strings['Your posts and conversations'] = 'Na postaichean ’s còmhraidhean agad';
$a->strings['Introductions'] = 'Cuir an aithne';
$a->strings['Friend Requests'] = 'Iarrtasan càirdeis';
$a->strings['Notifications'] = 'Brathan';
$a->strings['See all notifications'] = 'Seall gach brath';
$a->strings['Mark as seen'] = 'Cuir comharra gun deach fhaicinn';
$a->strings['Mark all system notifications as seen'] = 'Cuir comharra gun deach gach brath an t-siostaim a leughadh';
$a->strings['Private mail'] = 'Post prìobhaideach';
$a->strings['Inbox'] = 'Am bogsa a-steach';
$a->strings['Outbox'] = 'Am bogsa a-mach';
$a->strings['Accounts'] = 'Cunntasan';
$a->strings['Manage other pages'] = 'Stiùir duilleagan eile';
$a->strings['Settings'] = 'Roghainnean';
$a->strings['Account settings'] = 'Roghainnean a’ chunntais';
$a->strings['Manage/edit friends and contacts'] = 'Stiùir/deasaich caraidean is luchd-aithne';
$a->strings['Admin'] = 'Rianachd';
$a->strings['Site setup and configuration'] = 'Suidheachadh ’s rèiteachadh na làraich';
$a->strings['Moderation'] = 'Maorsainneachd';
$a->strings['Content and user moderation'] = 'Susbaint is maorsainneachd chleachdaichean';
$a->strings['Navigation'] = 'Seòladaireachd';
$a->strings['Site map'] = 'Mapa na làraich';
$a->strings['first'] = 'dhan toiseach';
$a->strings['prev'] = 'air ais';
$a->strings['next'] = 'air adhart';
$a->strings['last'] = 'dhan deireadh';
$a->strings['<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a> %3$s'] = '<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a> %3$s';
$a->strings['Link to source'] = 'Ceangal dhan tùs';
$a->strings['Click to open/close'] = 'Briog ’ga fhosgladh/dhùnadh';
$a->strings['$1 wrote:'] = 'Sgrìobh $1:';
$a->strings['Encrypted content'] = 'Susbaint chrioptaichte';
$a->strings['Invalid source protocol'] = 'Pròtacal tùis mì-dhligheach';
$a->strings['Invalid link protocol'] = 'Pròtacal ceangail mì-dhligheach';
$a->strings['Loading more entries...'] = 'A’ luchdadh barrachd nithean…';
$a->strings['The end'] = 'A’ chrìoch';
$a->strings['Follow'] = 'Lean air';
$a->strings['Add New Contact'] = 'Cuir neach-aithne ùr ris';
$a->strings['Enter address or web location'] = 'Cuir a-steach seòladh no ionad-lìn';
$a->strings['Example: bob@example.com, http://example.com/barbara'] = 'Mar eisimpleir: calum@ball-eisimpleir.com, http://ball-eisimpleir.com/oighrig';
$a->strings['Connect'] = 'Ceangail';
$a->strings['%d invitation available'] = [
	0 => 'Tha %d chuireadh ri fhaighinn',
	1 => 'Tha %d chuireadh ri fhaighinn',
	2 => 'Tha %d cuiridhean ri fhaighinn',
	3 => 'Tha %d cuireadh ri fhaighinn',
];
$a->strings['Find People'] = 'Lorg daoine';
$a->strings['Enter name or interest'] = 'Cuir a-steach ainm no ùidh';
$a->strings['Examples: Robert Morgenstein, Fishing'] = 'Mar eisimpleir: Aonghas MacLeòid, iasgach';
$a->strings['Find'] = 'Lorg';
$a->strings['Friend Suggestions'] = 'Molaidhean charaidean';
$a->strings['Similar Interests'] = 'Ùidhean an cumantas';
$a->strings['Random Profile'] = 'Pròifil air thuaiream';
$a->strings['Invite Friends'] = 'Thoir cuireadh do charaidean';
$a->strings['Global Directory'] = 'Eòlaire co-naisgte';
$a->strings['Local Directory'] = 'Eòlaire ionadail';
$a->strings['Everyone'] = 'A h-uile duine';
$a->strings['No relationship'] = 'Gu dàimh';
$a->strings['Relationships'] = 'Dàimhean';
$a->strings['All Contacts'] = 'A h-uile neach-aithne';
$a->strings['All Protocols'] = 'Gach pròtacal';
$a->strings['Everything'] = 'A h-uile càil';
$a->strings['Categories'] = 'Roinnean-seòrsa';
$a->strings['%d contact in common'] = [
	0 => '%d neach-aithne an cumantas',
	1 => '%d luchd-aithne an cumantas',
	2 => '%d luchd-aithne an cumantas',
	3 => '%d luchd-aithne an cumantas',
];
$a->strings['On this date'] = 'Air an latha seo';
$a->strings['Persons'] = 'Daoine';
$a->strings['Organisations'] = 'Buidhnean';
$a->strings['News'] = 'Naidheachdan';
$a->strings['All'] = 'Na h-uile';
$a->strings['Export'] = 'Às-phortaich';
$a->strings['Export calendar as ical'] = 'Às-phortaich am mìosachan ’na ical';
$a->strings['Export calendar as csv'] = 'Às-phortaich am mìosachan ’na csv';
$a->strings['No contacts'] = 'Chan eil neach-aithne ann';
$a->strings['%d Contact'] = [
	0 => '%d neach-aithne',
	1 => '%d luchd-aithne',
	2 => '%d luchd-aithne',
	3 => '%d luchd-aithne',
];
$a->strings['View Contacts'] = 'Seall an luchd-aithne';
$a->strings['Remove term'] = 'Thoir am briathar air falbh';
$a->strings['Trending Tags (last %d hour)'] = [
	0 => 'Tagaichean a’ treandadh (san %d uair a thìde seo chaidh)',
	1 => 'Tagaichean a’ treandadh (san %d uair a thìde seo chaidh)',
	2 => 'Tagaichean a’ treandadh (sna %d uairean a thìde seo chaidh)',
	3 => 'Tagaichean a’ treandadh (san %d uair a thìde seo chaidh)',
];
$a->strings['More Trending Tags'] = 'Barrachd thagaichean a’ treandadh';
$a->strings['Mention'] = 'Iomradh';
$a->strings['XMPP:'] = 'XMPP:';
$a->strings['Matrix:'] = 'Matrix:';
$a->strings['Location:'] = 'Ionad:';
$a->strings['Network:'] = 'Lìonra:';
$a->strings['Unfollow'] = 'Na lean tuilleadh';
$a->strings['Yourself'] = 'Thu fhèin';
$a->strings['Mutuals'] = 'Co-dhàimhean';
$a->strings['Post to Email'] = 'Postaich dhan phost-d';
$a->strings['Public'] = 'Poblach';
$a->strings['This content will be shown to all your followers and can be seen in the community pages and by anyone with its link.'] = 'Thèid an t-susbaint seo a shealltainn dhan luchd-leantainn gu lèir agad agus chithear air duilleagan na coimhearsnachd i agus chì a h-uile duine aig a bheil an ceangal seo i.';
$a->strings['Limited/Private'] = 'Cuingichte/Prìobhaideach';
$a->strings['This content will be shown only to the people in the first box, to the exception of the people mentioned in the second box. It won\'t appear anywhere public.'] = 'Thèid an t-susbaint seo a shealltainn dhan fheadhainn san dàrna bhogsa a-mhàin is chan fhaic an fheadhainn sa bhogsa eile. Cha nochd i gu poblach àite sam bith.';
$a->strings['Start typing the name of a contact or a circle to show a filtered list. You can also mention the special circles "Followers" and "Mutuals".'] = 'Tòisich air ainm neach-aithne no cearcall a sgrìobhadh a shealltainn liosta chriathraichte. ’S urrainn dhut iomradh a thoirt air cearcallan sònraichte mar “Luchd-leantainn” no “Co-dhàimhean” cuideachd.';
$a->strings['Show to:'] = 'Seall gu:';
$a->strings['Except to:'] = 'Ach gu:';
$a->strings['CC: email addresses'] = 'CC: seòlaidhean puist-d';
$a->strings['Example: bob@example.com, mary@example.com'] = 'Mar eisimpleir: aonghas@ball-eisimpleir.com, oighrig@ball-eisimpleir.com';
$a->strings['Connectors'] = 'Ceangladairean';
$a->strings['The database configuration file "config/local.config.php" could not be written. Please use the enclosed text to create a configuration file in your web server root.'] = 'Cha b’ urrainn dhuinn sgrìobadh gu faidhle rèiteachadh an stòir-dhàta “config/local.config.php”. Cleachd an teacsa am broinn seo airson faidhle rèiteachaidh a chruthachadh ann am freumh an fhrithealaiche-lìn agad.';
$a->strings['You may need to import the file "database.sql" manually using phpmyadmin or mysql.'] = 'Dh’fhaoidte gum bi agad ris an fhaidhle “database.sql” ion-phortadh a làimh le phpmyadmin no mysql.';
$a->strings['Please see the file "doc/INSTALL.md".'] = 'Faic am faidhle “doc/INSTALL.md”.';
$a->strings['Could not find a command line version of PHP in the web server PATH.'] = 'Cha do lorg sinn tionndadh loidhne-àithne dhe PHP ann am PATH an fhrithealaiche-lìn.';
$a->strings['If you don\'t have a command line version of PHP installed on your server, you will not be able to run the background processing. See <a href=\'https://github.com/friendica/friendica/blob/stable/doc/Install.md#set-up-the-worker\'>\'Setup the worker\'</a>'] = 'Mur eil tionndadh loidhne-àithne dhe PHP stàlaichte air an fhrithealaiche agad, chan urrainn dhut am pròiseasadh sa chùlaibh a ruith. Faic <a href=\'https://github.com/friendica/friendica/blob/stable/doc/Install.md#set-up-the-worker\'>“Setup the worker”</a>';
$a->strings['PHP executable path'] = 'Slighe sho-ghnìomhaichte PHP';
$a->strings['Enter full path to php executable. You can leave this blank to continue the installation.'] = 'Cuir a-steach an t-slighe shlàn dhan fhaidhle sho-ghnìomhaichte php. ’S urrainn dhut seo fhàgail bàn airson leantainn air adhart leis an stàladh.';
$a->strings['Command line PHP'] = 'Loidhne-àithne PHP';
$a->strings['PHP executable is not the php cli binary (could be cgi-fgci version)'] = 'Chan e am faidhle bìnearaidh cli a tha san fhaidhle sho-ghnìomhaichte PHP (dh’fhaoidte gu bheil an tionndadh cgi-fgci agad)';
$a->strings['Found PHP version: '] = 'Chaidh tionndadh dhe PHP a lorg: ';
$a->strings['PHP cli binary'] = 'Faidhle bìnearaidh cli PHP';
$a->strings['The command line version of PHP on your system does not have "register_argc_argv" enabled.'] = 'Chan eil “register_argc_argv” an comas aig an tionndadh loidhne-àithne dhe PHP air an t-siostam agad.';
$a->strings['This is required for message delivery to work.'] = 'Tha seo riatanach ach an obraich lìbhrigeadh nan teachdaireachdan.';
$a->strings['PHP register_argc_argv'] = 'PHP register_argc_argv';
$a->strings['Error: the "openssl_pkey_new" function on this system is not able to generate encryption keys'] = 'Mearachd: chan urrainn dhan ghleus “openssl_pkey_new” air an t-siostam seo iuchraichean crioptachaidh a gintinn';
$a->strings['If running under Windows, please see "http://www.php.net/manual/en/openssl.installation.php".'] = 'Ma tha thu ’ga ruith fo Windows, faic “http://www.php.net/manual/en/openssl.installation.php”.';
$a->strings['Generate encryption keys'] = 'A’ gintinn nan iuchraichean crioptachaidh';
$a->strings['Error: Apache webserver mod-rewrite module is required but not installed.'] = 'Mearachd: Tha mòideal mod-rewrite aig an fhrithealaiche-lìn Apache riatanach ach cha deach a stàladh.';
$a->strings['Apache mod_rewrite module'] = 'Mòideal Apache mod_rewrite';
$a->strings['Error: PDO or MySQLi PHP module required but not installed.'] = 'Mearachd: Tha mòideal PDO no MySQLi aig PHP riatanach ach cha deach gin diubh a stàladh.';
$a->strings['Error: The MySQL driver for PDO is not installed.'] = 'Mearachd: Cha deach an draibhear MySQL airson PDO a stàladh.';
$a->strings['PDO or MySQLi PHP module'] = 'Mòideal PDO no MySQLi aig PHP';
$a->strings['Error, XML PHP module required but not installed.'] = 'Mearachd: Tha am mòideal XML aig PHP riatanach ach cha deach a stàladh.';
$a->strings['XML PHP module'] = 'Mòideal XML aig PHP';
$a->strings['libCurl PHP module'] = 'Mòideal libCurl aig PHP';
$a->strings['Error: libCURL PHP module required but not installed.'] = 'Mearachd: Tha am mòideal libCurl aig PHP riatanach ach cha deach a stàladh.';
$a->strings['GD graphics PHP module'] = 'Mòideal grafaigeachd GD aig PHP';
$a->strings['Error: GD graphics PHP module with JPEG support required but not installed.'] = 'Mearachd: Tha am mòideal grafaigeachd GD aig PHP le taic dha JPEG riatanach ach cha deach a stàladh.';
$a->strings['OpenSSL PHP module'] = 'Mòideal OpenSSL aig PHP';
$a->strings['Error: openssl PHP module required but not installed.'] = 'Mearachd: Tha am mòideal openssl aig PHP riatanach ach cha deach a stàladh.';
$a->strings['mb_string PHP module'] = 'mòideal mb_string aig PHP';
$a->strings['Error: mb_string PHP module required but not installed.'] = 'Mearachd: Tha am mòideal mb_string aig PHP riatanach ach cha deach a stàladh.';
$a->strings['iconv PHP module'] = 'Mòideal iconv aig PHP';
$a->strings['Error: iconv PHP module required but not installed.'] = 'Mearachd: Tha am mòideal iconv aig PHP riatanach ach cha deach a stàladh.';
$a->strings['POSIX PHP module'] = 'Mòideal POSIX aig PHP';
$a->strings['Error: POSIX PHP module required but not installed.'] = 'Mearachd: Tha am mòideal POSIX aig PHP riatanach ach cha deach a stàladh.';
$a->strings['Program execution functions'] = 'Foincseanan gnìomhachadh phrògraman';
$a->strings['Error: Program execution functions (proc_open) required but not enabled.'] = 'Mearachd: Tha foincseanan gnìomhachadh phrògraman (proc_open) riatanach ach cha deach an cur an comas.';
$a->strings['JSON PHP module'] = 'Mòideal JSON aig PHP';
$a->strings['Error: JSON PHP module required but not installed.'] = 'Mearachd: Tha am mòideal JSON aig PHP riatanach ach cha deach a stàladh.';
$a->strings['File Information PHP module'] = 'Mòideal File Information aig PHP';
$a->strings['Error: File Information PHP module required but not installed.'] = 'Mearachd: Tha am mòideal File Information aig PHP aig PHP riatanach ach cha deach a stàladh.';
$a->strings['GNU Multiple Precision PHP module'] = 'Mòideal GNU Multiple Precision aig PHP';
$a->strings['Error: GNU Multiple Precision PHP module required but not installed.'] = 'Mearachd: Tha am mòideal GNU Multiple Precision aig PHP riatanach ach cha deach a stàladh.';
$a->strings['The web installer needs to be able to create a file called "local.config.php" in the "config" folder of your web server and it is unable to do so.'] = 'Feumaidh an stàlaichear-lìn faidhle a chruthachadh air a bheil “local.config.php” ann am pasgan “config” an fhrithealaiche-lìn agad is chan urrainn dha sin a dhèanamh.';
$a->strings['This is most often a permission setting, as the web server may not be able to write files in your folder - even if you can.'] = 'Mar as trice, is roghainn cead as coireach agus ’s dòcha nach fhaod am frithealaiche-lìn faidhlichean a sgrìobhadh sa phasgan agad –  fiù mas urrainn dhut fhèin.';
$a->strings['At the end of this procedure, we will give you a text to save in a file named local.config.php in your Friendica "config" folder.'] = 'Air deireadh na sgeòil, bheir sinn teacsa thugad ach an sàbhail thu e ann am faidhle air a bheil local.config.php sa phasgan “config” aig Friendica.';
$a->strings['You can alternatively skip this procedure and perform a manual installation. Please see the file "doc/INSTALL.md" for instructions.'] = 'Mar roghainn eile, leig seo dhan dàrna taobh agus dèan stàladh a làimh. Faic am faidhle “doc/INSTALL.md” airson stiùireadh.';
$a->strings['config/local.config.php is writable'] = 'Gabhaidh sgrìobhadh san fhaidhle config/local.config.php';
$a->strings['Friendica uses the Smarty3 template engine to render its web views. Smarty3 compiles templates to PHP to speed up rendering.'] = 'Cleachdaidh Friendica einnsean theamplaidean Smarty3 airson na seallaidhean-lìn aige a reandaradh. Trusaidh Smarty3 na teamplaidean gu PHP airson an reandaradh a dhèanamh nas luaithe.';
$a->strings['In order to store these compiled templates, the web server needs to have write access to the directory view/smarty3/ under the Friendica top level folder.'] = 'Airson na teamplaidean sin a stòradh às dèidh an trusaidh, feumaidh inntrigeadh a chùm sgrìobhaidh sa phasgan view/smarty3/ fo phrìomh-phasgan Friendica a bhith aig an fhrithealaiche-lìn.';
$a->strings['Please ensure that the user that your web server runs as (e.g. www-data) has write access to this folder.'] = 'Dèan cinnteach gu bheil inntrigeadh a chùm sgrìobhaidh dhan phasgan seo aig a’ chleachdaiche leis a bheil am frithealaiche-lìn ’ga ruith (m.e. www-data).';
$a->strings['Note: as a security measure, you should give the web server write access to view/smarty3/ only--not the template files (.tpl) that it contains.'] = 'An aire: a chùm tèarainteachd, bu chòir dhut inntrigeadh a chùm sgrìobhaidh a thoirt dhan fhrithealaiche-lìn airson a’ phasgain view/smarty3/ a-mhàin is chan ann dha na faidhlichean teamplaide (.tpl) a tha ’na bhroinn.';
$a->strings['view/smarty3 is writable'] = 'Gabhaidh sgrìobhadh sa phasgan view/smarty3';
$a->strings['Url rewrite in .htaccess seems not working. Make sure you copied .htaccess-dist to .htaccess.'] = 'Tha coltas nach eil an Url rewrite ag obair san fhaidhle .htaccess. Dèan cinnteach gun do chruthaich thu lethbhreac dhe .htaccess-dist aig .htaccess.';
$a->strings['In some circumstances (like running inside containers), you can skip this error.'] = 'Ann an cuid a shuidheachaidhean (can ruith am broinn suithich), ’s urrainn dhut a’ mhearachd seo a leigeil seachad.';
$a->strings['Error message from Curl when fetching'] = 'Teachdaireachd mearachd o Curl rè na faighinn';
$a->strings['Url rewrite is working'] = 'Tha Url rewrite ag obair';
$a->strings['The detection of TLS to secure the communication between the browser and the new Friendica server failed.'] = 'Cha deach leinn mothachadh dha TLS a chùm conaltraidh thèarainte eadar am brabhsair ’s am frithealaiche Friendica ùr.';
$a->strings['It is highly encouraged to use Friendica only over a secure connection as sensitive information like passwords will be transmitted.'] = 'Mholamaid nach cleachd thu Friendica ach thar ceangal tèarainte idir on a thèid fiosrachadh dìomhair mar fhaclan-faire a thar-chur.';
$a->strings['Please ensure that the connection to the server is secure.'] = 'Dèan cinnteach gum bi an ceangal dhan fhrithealaiche tèarainte.';
$a->strings['No TLS detected'] = 'Cha do mhothaich sinn dha TLS';
$a->strings['TLS detected'] = 'Mhothaich sinn dha TLS';
$a->strings['ImageMagick PHP extension is not installed'] = 'Cha deach an tuilleadan ImageMagick aig PHP a stàladh';
$a->strings['ImageMagick PHP extension is installed'] = 'Chaidh an tuilleadan ImageMagick aig PHP a stàladh';
$a->strings['Database already in use.'] = 'Tha an stòr-dàta ’ga chleachdadh mu thràth.';
$a->strings['Could not connect to database.'] = 'Cha b’ urrainn dhuinn ceangal ris an stòr-dàta.';
$a->strings['Monday'] = 'DiLuain';
$a->strings['Tuesday'] = 'DiMàirt';
$a->strings['Wednesday'] = 'DiCiadain';
$a->strings['Thursday'] = 'DiarDaoin';
$a->strings['Friday'] = 'DihAoine';
$a->strings['Saturday'] = 'DiSathairne';
$a->strings['Sunday'] = 'DiDòmhnaich';
$a->strings['January'] = 'Am Faoilleach';
$a->strings['February'] = 'An Gearran';
$a->strings['March'] = 'Am Màrt';
$a->strings['April'] = 'An Giblean';
$a->strings['May'] = 'An Cèitean';
$a->strings['June'] = 'An t-Ògmhios';
$a->strings['July'] = 'An t-Iuchar';
$a->strings['August'] = 'An Lùnastal';
$a->strings['September'] = 'An t-Sultain';
$a->strings['October'] = 'An Dàmhair';
$a->strings['November'] = 'An t-Samhain';
$a->strings['December'] = 'An Dùbhlachd';
$a->strings['Mon'] = 'DiL';
$a->strings['Tue'] = 'DiM';
$a->strings['Wed'] = 'DiC';
$a->strings['Thu'] = 'Dia';
$a->strings['Fri'] = 'Dih';
$a->strings['Sat'] = 'DiS';
$a->strings['Sun'] = 'DiD';
$a->strings['Jan'] = 'Faoi';
$a->strings['Feb'] = 'Gearr';
$a->strings['Mar'] = 'Màrt';
$a->strings['Apr'] = 'Gibl';
$a->strings['Jun'] = 'Ògmh';
$a->strings['Jul'] = 'Iuch';
$a->strings['Aug'] = 'Lùna';
$a->strings['Sep'] = 'Sult';
$a->strings['Oct'] = 'Dàmh';
$a->strings['Nov'] = 'Samh';
$a->strings['Dec'] = 'Dùbh';
$a->strings['The logfile \'%s\' is not usable. No logging possible (error: \'%s\')'] = 'Cha ghabh faidhle “%s” an loga a chleachdadh. Cha ghabh logadh a dhèanamh (mearachd: “%s”)';
$a->strings['The debug logfile \'%s\' is not usable. No logging possible (error: \'%s\')'] = 'Cha ghabh faidhle “%s” an loga dì-bhugachaidh a chleachdadh. Cha ghabh logadh a dhèanamh (mearachd: “%s”)';
$a->strings['Friendica can\'t display this page at the moment, please contact the administrator.'] = 'Chan urrainn dha Friendica an duilleag seo a shealltainn an-dràsta, cuir fios gun rianaire.';
$a->strings['template engine cannot be registered without a name.'] = 'cha ghabh einnsean theamplaidean a chlàradh gun ainm.';
$a->strings['template engine is not registered!'] = 'cha deach an t-einnsean theamplaidean a chlàradh!';
$a->strings['Storage base path'] = 'Bun-slighe an stòrais';
$a->strings['Folder where uploaded files are saved. For maximum security, This should be a path outside web server folder tree'] = 'Am pasgan far an dèid faidhlichean air an luchdadh suas a shàbhaladh. A chùm na tèarainteachd as fheàrr, mholamaid slighe taobh a-muigh craobh phasganan an fhrithealaiche-lìn';
$a->strings['Enter a valid existing folder'] = 'Cuir a-steach pasgan dligheach a tha ann';
$a->strings['Updates from version %s are not supported. Please update at least to version 2021.01 and wait until the postupdate finished version 1383.'] = 'Chan eil taic ri ùrachadh o thionndadh %s. Ùraich gun tionndadh 2021.01 air a char as sine agus fuirich gus am bith an t-iar-ùrachadh deiseil le tionndadh 1383.';
$a->strings['Updates from postupdate version %s are not supported. Please update at least to version 2021.01 and wait until the postupdate finished version 1383.'] = 'Chan eil taic ri iar-ùrachadh o thionndadh %s. Ùraich gun tionndadh 2021.01 air a char as sine agus fuirich gus am bith an t-iar-ùrachadh deiseil le tionndadh 1383.';
$a->strings['%s: executing pre update %d'] = '%s: a’ dèanamh ro-ùrachadh %d';
$a->strings['%s: executing post update %d'] = '%s: a’ dèanamh iar-ùrachadh %d';
$a->strings['Update %s failed. See error logs.'] = 'Dh’fhàillig le ùrachadh %s. Thoir sùil air logaichean nam mearachdan.';
$a->strings['
				The friendica developers released update %s recently,
				but when I tried to install it, something went terribly wrong.
				This needs to be fixed soon and I can\'t do it alone. Please contact a
				friendica developer if you can not help me on your own. My database might be invalid.'] = '
				Dh’fhoillsich luchd-leasachaidh Friendica ùrachadh %s o chionn goirid
				ach nuair a dh’fheuch mi ris a stàladh, chaidh rudeigin ceàrr gu dona.
				Feumaidh sinn seo a chàradh a dh’aithghearr ach chan urrainn dhomh sin a dhèanamh ’nam aonar. Cuir fios gu
				neach-leasachaidh Friendica mur urrainn dhut fhèin mo chuideachadh. Dh’fhaoidte nach eil an stòr-dàta agam dligheach.';
$a->strings['The error message is\n[pre]%s[/pre]'] = 'Seo teachdaireachd na mearachd:\n[pre]%s[/pre]';
$a->strings['[Friendica Notify] Database update'] = '[Brath Friendica] Ùrachadh an stòir-dhàta';
$a->strings['
				The friendica database was successfully updated from %s to %s.'] = '
				Chaidh stòr-dàta Friendica ùrachadh o %s gu %s.';
$a->strings['The database version had been set to %s.'] = 'Chaidh tionndadh an stòir-dhàta a shuidheachadh air %s.';
$a->strings['The post update is at version %d, it has to be at %d to safely drop the tables.'] = 'Tha an t-iar-ùrachadh air tionndadh %d ach feumaidh e bhith air %d mus gabh na clàran a leigeil às gu sàbhailte.';
$a->strings['No unused tables found.'] = 'Cha deach clàr gun cleachdadh a lorg.';
$a->strings['These tables are not used for friendica and will be deleted when you execute "dbstructure drop -e":'] = 'Chan eil na clàran seo ’gan cleachdadh airson Friendica is thèid an sguabadh às ma ghnìomhaicheas tu “dbstructure drop -e”:';
$a->strings['There are no tables on MyISAM or InnoDB with the Antelope file format.'] = 'Chan eil clàr air MyISAM no InnoDB le fòrmat faidhle Antelope.';
$a->strings['
Error %d occurred during database update:
%s
'] = '
Thachair mearachd %d rè ùrachadh an stòir-dhàta:
%s
';
$a->strings['Errors encountered performing database changes: '] = 'Thachair sinn ri mearachdan nuair a bha sinn ag atharrachadh an stòir-dhàta: ';
$a->strings['Another database update is currently running.'] = 'Tha ùrachadh eile ’ga ruith air an stòr-dàta an-dràsta fhèin.';
$a->strings['%s: Database update'] = '%s: Ùrachadh an stòir-dhàta';
$a->strings['%s: updating %s table.'] = '%s: Ag ùrachadh clàr %s.';
$a->strings['Record not found'] = 'Cha deach an clàr a lorg';
$a->strings['Unprocessable Entity'] = 'Eintiteas nach gabh a phròiseasadh';
$a->strings['Unauthorized'] = 'Gun chead';
$a->strings['Token is not authorized with a valid user or is missing a required scope'] = 'Cha deach an tòcan ùghdarrachadh le cleachdaiche dligheach no tha sgòp riatanach a dhìth';
$a->strings['Internal Server Error'] = 'Mearachd frithealaiche inntearnail';
$a->strings['Legacy module file not found: %s'] = 'Cha deach am faidhle mòideil dìleabach seo a lorg: %s';
$a->strings['A deleted circle with this name was revived. Existing item permissions <strong>may</strong> apply to this circle and any future members. If this is not what you intended, please create another circle with a different name.'] = 'Chaidh cearcall a bh’ air a sguabadh às ath-bheòthachadh. <strong>Faodaidh</strong> ceadan a tha ann air nithean a bhith an sàs air a’ chearcall seo is air ball ri teachd sam bith. Mur e sin a bha fa-near dhut, cruthaich cearcall eile air a bheil ainm eile.';
$a->strings['Everybody'] = 'A h-uile duine';
$a->strings['edit'] = 'deasaich';
$a->strings['add'] = 'cuir ris';
$a->strings['Edit circle'] = 'Deasaich an cearcall';
$a->strings['Contacts not in any circle'] = 'Luchd-aithne gun chearcall';
$a->strings['Create a new circle'] = 'Cruthaich cearcall ùr';
$a->strings['Circle Name: '] = 'Ainm a’ chearcaill: ';
$a->strings['Edit circles'] = 'Deasaich na cearcallan';
$a->strings['Approve'] = 'Aontaich ris';
$a->strings['Organisation'] = 'Buidheann';
$a->strings['Group'] = 'Buidheann';
$a->strings['Relay'] = 'Ath-sheachadan';
$a->strings['Disallowed profile URL.'] = 'URL pròifile mì-dhligheach.';
$a->strings['Blocked domain'] = 'Àrainn bhacte';
$a->strings['Connect URL missing.'] = 'Tha URL a’ cheangail a dhìth.';
$a->strings['The contact could not be added. Please check the relevant network credentials in your Settings -> Social Networks page.'] = 'Cha b’ urrainn dhuinn an neach-aithne a chur ris. Thoir sùil air teisteas an lìonraidh iomchaidh air duilleag nan “Roghainnean” > “Lìonraidhean sòisealta” agad.';
$a->strings['Expected network %s does not match actual network %s'] = 'Chan eil an lìonra %s air a bheil dùil co-ionnann ris a lìonra %s a tha ann';
$a->strings['The profile address specified does not provide adequate information.'] = 'Chan eil an seòladh pròifile a shònraich thu a’ solar am fiosrachadh iomchaidh.';
$a->strings['No compatible communication protocols or feeds were discovered.'] = 'Cha do lorg sinn pròtacal conaltraidh no inbhir iomchaidh.';
$a->strings['An author or name was not found.'] = 'Cha deach an t-ùghdar no ainm a lorg.';
$a->strings['No browser URL could be matched to this address.'] = 'Cha b’ urrainn dhuinn URL a’ bhrabhsair a mhaidseadh ris an t-seòladh seo.';
$a->strings['Unable to match @-style Identity Address with a known protocol or email contact.'] = 'Tha b’ urrainn dhuinn an seòladh-aithne san stoidhle @ a mhaidseadh le pròtacal as aithne dhuinn no neach-aithne puist-d.';
$a->strings['Use mailto: in front of address to force email check.'] = 'Cleachd mailto: ron t-seòladh airson sgrùdadh nam post-d a sparradh.';
$a->strings['Limited profile. This person will be unable to receive direct/personal notifications from you.'] = 'Pròifil chuingichte. Chan fhaigh an neach seo brathan dìreach/pearsanta uat.';
$a->strings['Unable to retrieve contact information.'] = 'Cha d’ fhuair sinn grèim air fiosrachadh an neach-aithne.';
$a->strings['l F d, Y \@ g:i A \G\M\TP (e)'] = 'l, d F Y \@ g:i a \G\M\TP (e)';
$a->strings['Starts:'] = 'A’ tòiseachadh:';
$a->strings['Finishes:'] = 'Thig e gu crìoch:';
$a->strings['all-day'] = 'fad an latha';
$a->strings['Sept'] = 'Sult';
$a->strings['today'] = 'an-diugh';
$a->strings['month'] = 'mìos';
$a->strings['week'] = 'seachdain';
$a->strings['day'] = 'latha';
$a->strings['No events to display'] = 'Chan eil tachartas ri shealltainn ann';
$a->strings['Access to this profile has been restricted.'] = 'Chaidh an t-inntrigeadh dhan phròifil seo a chuingeachadh.';
$a->strings['Event not found.'] = 'Cha deach an tachartas a lorg.';
$a->strings['l, F j'] = 'l, j F';
$a->strings['Edit event'] = 'Deasaich an tachartas';
$a->strings['Duplicate event'] = 'Dùblaich an tachartas';
$a->strings['Delete event'] = 'Sguab às an tachartas';
$a->strings['l F d, Y \@ g:i A'] = 'l, d F Y \@ g:i a';
$a->strings['D g:i A'] = 'D g:i a';
$a->strings['g:i A'] = 'g:i a';
$a->strings['Show map'] = 'Seall am mapa';
$a->strings['Hide map'] = 'Falaich am mapa';
$a->strings['%s\'s birthday'] = 'Cò-là breith aig %s';
$a->strings['Happy Birthday %s'] = 'Co-là breith sona dhut, %s';
$a->strings['activity'] = 'gnìomhachd';
$a->strings['comment'] = 'beachd';
$a->strings['post'] = 'post';
$a->strings['%s is blocked'] = 'Tha %s ’ga bhacadh';
$a->strings['%s is ignored'] = 'Tha %s ’ga leigeil seachad';
$a->strings['Content from %s is collapsed'] = 'Tha susbaint o %s ’ga cho-theannachadh';
$a->strings['bytes'] = 'baidht';
$a->strings['%2$s (%3$d%%, %1$d vote)'] = [
	0 => '%2$s (%3$d%%, %1$d bhòt)',
	1 => '%2$s (%3$d%%, %1$d bhòt)',
	2 => '%2$s (%3$d%%, %1$d bhòtaichean)',
	3 => '%2$s (%3$d%%, %1$d bhòt)',
];
$a->strings['%2$s (%1$d vote)'] = [
	0 => '%2$s (%1$d bhòt)',
	1 => '%2$s (%1$d bhòt)',
	2 => '%2$s (%1$d bhòtaichean)',
	3 => '%2$s (%1$d bhòt)',
];
$a->strings['%d voter. Poll end: %s'] = [
	0 => 'Rinn %d bhòtadh. Crìoch a’ chunntais-bheachd: %s',
	1 => 'Rinn %d bhòtadh. Crìoch a’ chunntais-bheachd: %s',
	2 => 'Rinn %d bhòtadh. Crìoch a’ chunntais-bheachd: %s',
	3 => 'Rinn %d bhòtadh. Crìoch a’ chunntais-bheachd: %s',
];
$a->strings['%d voter.'] = [
	0 => 'Rinn %d bhòtadh.',
	1 => 'Rinn %d bhòtadh.',
	2 => 'Rinn %d bhòtadh.',
	3 => 'Rinn %d bhòtadh.',
];
$a->strings['Poll end: %s'] = 'Crìoch a’ bhunntais-bheachd:%s';
$a->strings['View on separate page'] = 'Seall air duilleag fa leth';
$a->strings['[no subject]'] = '[gun chuspair]';
$a->strings['Wall Photos'] = 'Dealbhan balla';
$a->strings['Edit profile'] = 'Deasaich a’ phròifil';
$a->strings['Change profile photo'] = 'Atharraich dealbh na pròifil';
$a->strings['Homepage:'] = 'Duilleag-dhachaigh:';
$a->strings['About:'] = 'Mu dhèidhinn:';
$a->strings['Atom feed'] = 'Inbhir Atom';
$a->strings['This website has been verified to belong to the same person.'] = 'Chaidh dearbhadh gu bheil an làrach-lìn seo aig an aon neach.';
$a->strings['F d'] = 'd F';
$a->strings['[today]'] = '[an-diugh]';
$a->strings['Birthday Reminders'] = 'Cuimhneachain co-là breith';
$a->strings['Birthdays this week:'] = 'Co-làithean breith an t-seachdain seo:';
$a->strings['g A l F d'] = 'g a l d F';
$a->strings['[No description]'] = '[Gun tuairisgeul]';
$a->strings['Event Reminders'] = 'Cuimhneachain air tachartasan';
$a->strings['Upcoming events the next 7 days:'] = 'Tachartasan anns na 7 làithean seo tighinn:';
$a->strings['Hometown:'] = 'Baile d’ àraich:';
$a->strings['Marital Status:'] = 'Inbhe pòsaidh:';
$a->strings['With:'] = 'Le:';
$a->strings['Since:'] = 'O chionn:';
$a->strings['Sexual Preference:'] = 'Aidmheil cleamhnais:';
$a->strings['Political Views:'] = 'Beachdan poilitigeach:';
$a->strings['Religious Views:'] = 'Beachdan creideamhach:';
$a->strings['Likes:'] = '’S toigh seo le:';
$a->strings['Dislikes:'] = 'Cha toigh seo le:';
$a->strings['Title/Description:'] = 'Tiotal/Tuairisgeul:';
$a->strings['Summary'] = 'Geàrr-chunntas';
$a->strings['Musical interests'] = 'Ùidhean ciùil';
$a->strings['Books, literature'] = 'Leabhraichean ⁊ litreachas';
$a->strings['Television'] = 'Telebhisean';
$a->strings['Film/dance/culture/entertainment'] = 'Film/dannsa/cultar/dibheirsean';
$a->strings['Hobbies/Interests'] = 'Cur-seachadan/ùidhean';
$a->strings['Love/romance'] = 'Gaol/suirghe';
$a->strings['Work/employment'] = 'Obair/fastadh';
$a->strings['School/education'] = 'Sgoil/foghlam';
$a->strings['Contact information and Social Networks'] = 'Fiosrachadh conaltraidh is meadhanan sòisealta';
$a->strings['SERIOUS ERROR: Generation of security keys failed.'] = 'MEARACHD MHÒR: Dh’fhàillig le gintinn nan iuchraichean tèarainteachd.';
$a->strings['Login failed'] = 'Dh’fhàillig leis a’ chlàradh a-steach';
$a->strings['Not enough information to authenticate'] = 'Tha fiosrachadh a dhìth dhan dearbhadh';
$a->strings['Password can\'t be empty'] = 'Chan fhaod am facal-faire a bhith bàn';
$a->strings['Empty passwords are not allowed.'] = 'Chan eil faclan-faire bàna ceadaichte.';
$a->strings['The new password has been exposed in a public data dump, please choose another.'] = 'Chaidh am facal-faire ùr fhoillseachadh ann an dumpadh dàta poblach, tagh fear eile.';
$a->strings['The password length is limited to 72 characters.'] = 'Chan fhaod am facal-faire a bhith nas fhaide na 72 caractar.';
$a->strings['The password can\'t contain white spaces nor accentuated letters'] = 'Chan fhaod àite bàn no litir le stràc a bhith am broinn an fhacail-fhaire';
$a->strings['Passwords do not match. Password unchanged.'] = 'Chan eil an dà fhacal-faire co-ionnann. Cha deach am facal-faire atharrachadh.';
$a->strings['An invitation is required.'] = 'Tha feum air cuireadh.';
$a->strings['Invitation could not be verified.'] = 'Cha b’ urrainn dhuinn an cuireadh a dhearbhadh.';
$a->strings['Invalid OpenID url'] = 'URL OpenID mì-dhligheach';
$a->strings['We encountered a problem while logging in with the OpenID you provided. Please check the correct spelling of the ID.'] = 'Thachair sinn ri duilgheadas fhad ’s a bha sinn ’gad clàradh a-steach leis an OpenID a thug thu seachad. Thoir sùil air litreachadh an ID.';
$a->strings['The error message was:'] = 'Seo teachdaireachd na mearachd:';
$a->strings['Please enter the required information.'] = 'Cuir a-steach am fiosrachadh riatanach.';
$a->strings['system.username_min_length (%s) and system.username_max_length (%s) are excluding each other, swapping values.'] = 'Tha system.username_min_length (%s) agus system.username_max_length (%s) ag às-dùnadh càch a chèile, a’ cur nan luachan an àite càch a chèile.';
$a->strings['Username should be at least %s character.'] = [
	0 => 'Feumaidh co-dhiù %s charactar a bhith am broinn an ainm-chleachdaiche.',
	1 => 'Feumaidh co-dhiù %s charactar a bhith am broinn an ainm-chleachdaiche.',
	2 => 'Feumaidh co-dhiù %s caractaran a bhith am broinn an ainm-chleachdaiche.',
	3 => 'Feumaidh co-dhiù %s caractar a bhith am broinn an ainm-chleachdaiche.',
];
$a->strings['Username should be at most %s character.'] = [
	0 => 'Chan fhaod còrr is %s charactar a bhith am broinn an ainm-chleachdaiche.',
	1 => 'Chan fhaod còrr is %s charactar a bhith am broinn an ainm-chleachdaiche.',
	2 => 'Chan fhaod còrr is %s caractaran a bhith am broinn an ainm-chleachdaiche.',
	3 => 'Chan fhaod còrr is %s caractar a bhith am broinn an ainm-chleachdaiche.',
];
$a->strings['That doesn\'t appear to be your full (First Last) name.'] = 'Chan eil coltas ainm shlàin (ainm ⁊ sloinneadh) air.';
$a->strings['Your email domain is not among those allowed on this site.'] = 'Chan eil àrainn a’ phuist-d agad am measg na feadhna a tha ceadaichte air an làrach seo.';
$a->strings['Not a valid email address.'] = 'Chan e seòladh puist-d dligheach a tha seo.';
$a->strings['The nickname was blocked from registration by the nodes admin.'] = 'Chaidh am far-ainm seo a bhacadh on chlàradh le rianaire an nòid.';
$a->strings['Cannot use that email.'] = 'Chan urrainn dhut am post-d seo a chleachdadh.';
$a->strings['Your nickname can only contain a-z, 0-9 and _.'] = 'Chan fhaod ach a-z, 0-9 ’s _ a bhith am broinn d’ fhar-ainm.';
$a->strings['Nickname is already registered. Please choose another.'] = 'Chaidh am far-ainm seo a chlàradh mu thràth. Nach tagh thu fear eile?';
$a->strings['An error occurred during registration. Please try again.'] = 'Thachair mearachd rè a’ chlàraidh. Feuch ris a-rithist.';
$a->strings['An error occurred creating your default profile. Please try again.'] = 'Thachair mearachd le cruthachadh na pròifile bunaitiche agad. Feuch ris a-rithist.';
$a->strings['An error occurred creating your self contact. Please try again.'] = 'Thachair mearachd le cruthachadh neach-aithne dhiot fhèin. Feuch ris a-rithist.';
$a->strings['Friends'] = 'Caraidean';
$a->strings['An error occurred creating your default contact circle. Please try again.'] = 'Thachair mearachd le cruthachadh a’ chearcaill luchd-aithne bhunaitich agad. Feuch ris a-rithist.';
$a->strings['Profile Photos'] = 'Dealbhan na pròifil';
$a->strings['
		Dear %1$s,
			the administrator of %2$s has set up an account for you.'] = '
		%1$s, a charaid,
			shuidhich rianaire %2$s cunntas dhut.';
$a->strings['Registration details for %s'] = 'Am fiosrachadh clàraidh airson %s';
$a->strings['
			Dear %1$s,
				Thank you for registering at %2$s. Your account is pending for approval by the administrator.

			Your login details are as follows:

			Site Location:	%3$s
			Login Name:		%4$s
			Password:		%5$s
		'] = '
			%1$s, a charaid,
				Mòran taing airson clàradh air %2$s. Tha an cunntas agad a’ feitheamh air aonta on rianaire.

			Seo am fiosrachadh clàraidh a-steach agad:

			Seòladh na làraich:	%3$s
			Ainm clàraidh a-steach:		%4$s
			Facal-faire:		%5$s
		';
$a->strings['Registration at %s'] = 'An clàradh air %s';
$a->strings['
				Dear %1$s,
				Thank you for registering at %2$s. Your account has been created.
			'] = '
				%1$s, a charaid,
				Mòran taing airson clàradh air %2$s. Chaidh an cunntas agad a chruthachadh.
			';
$a->strings['Addon not found.'] = 'Cha deach an tuilleadan a lorg.';
$a->strings['Addon %s disabled.'] = 'Tha an tuilleadan %s à comas.';
$a->strings['Addon %s enabled.'] = 'Tha an tuilleadan %s an comas.';
$a->strings['Disable'] = 'Cuir à comas';
$a->strings['Enable'] = 'Cuir an comas';
$a->strings['Administration'] = 'Rianachd';
$a->strings['Addons'] = 'Tuilleadain';
$a->strings['Toggle'] = 'Toglaich';
$a->strings['Author: '] = 'Ùghdar: ';
$a->strings['Maintainer: '] = 'Neach-glèidhidh: ';
$a->strings['Addons reloaded'] = 'Chaidh na tuilleadain ath-luchdadh';
$a->strings['Addon %s failed to install.'] = 'Dh’fhàillig le stàladh an tuilleadain %s.';
$a->strings['Save Settings'] = 'Sàbhail na roghainnean';
$a->strings['Reload active addons'] = 'Ath-luchdaich na tuilleadain ghnìomhach';
$a->strings['Update has been marked successful'] = 'Chaidh comharrachadh gun do soirbhich leis an ùrachadh';
$a->strings['Database structure update %s was successfully applied.'] = 'Chaidh ùrachadh %s air structar an stòir-dhàta a chur an sàs.';
$a->strings['Executing of database structure update %s failed with error: %s'] = 'Dh’fhàillig an t-ùrachadh %s air structar an stòir-dhàta leis a’ mhearachd seo: %s';
$a->strings['Executing %s failed with error: %s'] = 'Dh’fhàillig gnìomhachadh %s leis a’ chòd mhearachd seo: %s';
$a->strings['Update %s was successfully applied.'] = 'Chaidh ùrachadh %s a chur an sàs.';
$a->strings['Update %s did not return a status. Unknown if it succeeded.'] = 'Cha do thill an t-ùrachadh %s staid. Chan eil fhios an do shoirbhich leis gus nach do shoirbhich.';
$a->strings['There was no additional update function %s that needed to be called.'] = 'Cha robh foincsean ùrachaidh %s eile feumach air gairm ann.';
$a->strings['No failed updates.'] = 'Cha do dh’fhàillig le ùrachadh sam bith.';
$a->strings['Check database structure'] = 'Thoir sùil air structar an stòir-dhàta';
$a->strings['Failed Updates'] = 'Na dh’ùrachaidhean a dh’fhàillig leotha';
$a->strings['This does not include updates prior to 1139, which did not return a status.'] = 'Cha ghabh seo a-staigh na h-ùrachaidhean ro 1139 nach do thilleadh staid.';
$a->strings['Mark success (if update was manually applied)'] = 'Cuir comharra gun do shoirbhich leis (ma rinn thu an t-ùrachadh a làimh)';
$a->strings['Attempt to execute this update step automatically'] = 'Feuch gnìomhachadh a’ cheuma seo dhen ùrachadh gu fèin-obrachail';
$a->strings['No'] = 'Chan eil';
$a->strings['Yes'] = 'Tha';
$a->strings['Manage Additional Features'] = 'Stiùir na gleusan a bharrachd';
$a->strings['Other'] = 'Eile';
$a->strings['unknown'] = 'chan eil fhios';
$a->strings['%2$s total system'] = [
	0 => '%2$s siostam gu h-iomlan',
	1 => '%2$s shiostam gu h-iomlan',
	2 => '%2$s siostaman gu h-iomlan',
	3 => '%2$s siostam gu h-iomlan',
];
$a->strings['%2$s active user last month'] = [
	0 => '%2$s chleachdaiche gnìomhach sa mhìos seo chaidh',
	1 => '%2$s chleachdaiche gnìomhach sa mhìos seo chaidh',
	2 => '%2$s cleachdaichean gnìomhach sa mhìos seo chaidh',
	3 => '%2$s cleachdaiche gnìomhach sa mhìos seo chaidh',
];
$a->strings['%2$s active user last six months'] = [
	0 => '%2$s chleachdaiche gnìomhach san leth-bhliadhna seo chaidh',
	1 => '%2$s chleachdaiche gnìomhach san leth-bhliadhna seo chaidh',
	2 => '%2$s cleachdaichean gnìomhach san leth-bhliadhna seo chaidh',
	3 => '%2$s cleachdaiche gnìomhach san leth-bhliadhna seo chaidh',
];
$a->strings['%2$s registered user'] = [
	0 => '%2$s chleachdaiche clàraichte',
	1 => '%2$s chleachdaiche clàraichte',
	2 => '%2$s cleachdaichean clàraichte',
	3 => '%2$s cleachdaiche clàraichte',
];
$a->strings['%2$s locally created post or comment'] = [
	0 => 'Chaidh %2$s phost no beachd a chruthachadh gu h-ionadail',
	1 => 'Chaidh %2$s phost ’s bheachd a chruthachadh gu h-ionadail',
	2 => 'Chaidh %2$s postaichean ’s beachdan a chruthachadh gu h-ionadail',
	3 => 'Chaidh %2$s post ’s beachd a chruthachadh gu h-ionadail',
];
$a->strings['%2$s post per user'] = [
	0 => '%2$s phost gach cleachdaiche',
	1 => '%2$s phost gach cleachdaiche',
	2 => '%2$s postaichean gach cleachdaiche',
	3 => '%2$s post gach cleachdaiche',
];
$a->strings['%2$s user per system'] = [
	0 => '%2$s chleachdaiche gach siostaim',
	1 => '%2$s chleachdaiche gach siostaim',
	2 => '%2$s cleachdaichean gach siostaim',
	3 => '%2$s cleachdaiche gach siostaim',
];
$a->strings['This page offers you some numbers to the known part of the federated social network your Friendica node is part of. These numbers are not complete but only reflect the part of the network your node is aware of.'] = 'Bheir an duilleag seo àireamhan dhut mun chuid dhen lìonra shòisealta cho-naisgte sa bheil an nòd seo dhe Friendica. Chan eil na h-àireamhan seo coileanta is cha sheall iad ach a’ phàirt dhen lìonra air a bheil an nòd agad eòlach.';
$a->strings['Federation Statistics'] = 'Stadastaireachd a’ cho-nasgaidh';
$a->strings['Currently this node is aware of %2$s node (%3$s active users last month, %4$s active users last six months, %5$s registered users in total) from the following platforms:'] = [
	0 => 'Tha an nòd seo eòlach air %2$s nòd aig an àm seo (cleachdaichean gnìomhach sa mhìos seo chaidh: %3$s, cleachdaichean gnìomhach san leth-bhliadhna seo chaidh: %4$s, cleachdaichean clàraichte: %5$s gu h-iomlan) o na h-ùrlaran a leanas:',
	1 => 'Tha an nòd seo eòlach air %2$s nòd aig an àm seo (cleachdaichean gnìomhach sa mhìos seo chaidh: %3$s, cleachdaichean gnìomhach san leth-bhliadhna seo chaidh: %4$s, cleachdaichean clàraichte: %5$s gu h-iomlan) o na h-ùrlaran a leanas:',
	2 => 'Tha an nòd seo eòlach air %2$s nòdan aig an àm seo (cleachdaichean gnìomhach sa mhìos seo chaidh: %3$s, cleachdaichean gnìomhach san leth-bhliadhna seo chaidh: %4$s, cleachdaichean clàraichte: %5$s gu h-iomlan) o na h-ùrlaran a leanas:',
	3 => 'Tha an nòd seo eòlach air %2$s nòd aig an àm seo (cleachdaichean gnìomhach sa mhìos seo chaidh: %3$s, cleachdaichean gnìomhach san leth-bhliadhna seo chaidh: %4$s, cleachdaichean clàraichte: %5$s gu h-iomlan) o na h-ùrlaran a leanas:',
];
$a->strings['The logfile \'%s\' is not writable. No logging possible'] = 'Cha ghabh sgrìobhadh ann am faidhle “%s” an loga. Cha ghabh logadh a dhèanamh';
$a->strings['PHP log currently enabled.'] = 'Tha logadh PHP an comas an-dràsta.';
$a->strings['PHP log currently disabled.'] = 'Tha logadh PHP à comas an-dràsta.';
$a->strings['Logs'] = 'Logaichean';
$a->strings['Clear'] = 'Falamhaich';
$a->strings['Enable Debugging'] = 'Cuir dì-bhugachadh an comas';
$a->strings['<strong>Read-only</strong> because it is set by an environment variable'] = '<strong>Cead-leughaidh a-mhàin</strong> on a chaidh a shuidheachadh le caochladair àrainne';
$a->strings['Log file'] = 'Faidhle an loga';
$a->strings['Must be writable by web server. Relative to your Friendica top-level directory.'] = 'Feumaidh cead sgrìobhaidh a bhith aig an fhrithealaiche-lìn. Dàimheach ri prìomh-pasgan Friendica.';
$a->strings['Log level'] = 'Leibheil an loga';
$a->strings['PHP logging'] = 'Logadh PHP';
$a->strings['To temporarily enable logging of PHP errors and warnings you can prepend the following to the index.php file of your installation. The filename set in the \'error_log\' line is relative to the friendica top-level directory and must be writeable by the web server. The option \'1\' for \'log_errors\' and \'display_errors\' is to enable these options, set to \'0\' to disable them.'] = 'Airson logadh nam mearachdan is rabhaidhean PHP a chur an comas gu sealach, ’s urrainn dhut na leanas a chur air thoiseach faidhle index.php an stàlaidh agad. Tha ainm an fhaidhle a tha ’ga shuidheachadh air an loidhne \'error_log\' dàimheach ri prìomh-phasgan Friendica agus feumaidh cead sgrìobhaidh a bhith aig an fhrithealaiche-lìn ann. Cuiridh tu \'log_errors\' (logadh nam mearachdan) agus \'display_errors\' (sealltainn nam mearachdan) an comas leis an roghainn \'1\' agus à comas leis an roghainn \'0\'.';
$a->strings['Error trying to open <strong>%1$s</strong> log file.<br/>Check to see if file %1$s exist and is readable.'] = 'Mearachd a’ feuchainn ri faidhle <strong>%1$s</strong> an loga fhosgladh.<br/>Dearbh gu bheil am faidhle %1$s ann is gun gabh a leughadh.';
$a->strings['Couldn\'t open <strong>%1$s</strong> log file.<br/>Check to see if file %1$s is readable.'] = 'Cha b’ urrainn dhinn faidhle <strong>%1$s</strong> an loga fhosgladh.<br/>Dearbh gun gabh am faidhle %1$s a leughadh.';
$a->strings['View Logs'] = 'Seall na logaichean';
$a->strings['Search in logs'] = 'Lorg sna logaichean';
$a->strings['Show all'] = 'Seall na h-uile';
$a->strings['Date'] = 'Ceann-là';
$a->strings['Level'] = 'Leibheil';
$a->strings['Context'] = 'Co-theacsa';
$a->strings['ALL'] = 'NA h-UILE';
$a->strings['View details'] = 'Seall am mion-fhiosrachadh';
$a->strings['Click to view details'] = 'Briog air a shealltainn a’ mhion-fhiosrachaidh';
$a->strings['Event details'] = 'Fiosrachadh an tachartais';
$a->strings['Data'] = 'Dàta';
$a->strings['Source'] = 'Tùs';
$a->strings['File'] = 'Faidhle';
$a->strings['Line'] = 'Loidhne';
$a->strings['Function'] = 'Foincsean';
$a->strings['UID'] = 'UID';
$a->strings['Process ID'] = 'ID a’ phròiseis';
$a->strings['Close'] = 'Dùin';
$a->strings['Inspect Deferred Worker Queue'] = 'Sgrùd ciutha nan obraichean dàilichte';
$a->strings['This page lists the deferred worker jobs. This are jobs that couldn\'t be executed at the first time.'] = 'Gheibh thu liosta nan obraichean dàilichte air an duilleag seo. Cha b’ urrainn dhuinn na h-obraichean seo a dhèanamh sa chiad uair.';
$a->strings['Inspect Worker Queue'] = 'Sgrùd ciutha nan obraichean';
$a->strings['This page lists the currently queued worker jobs. These jobs are handled by the worker cronjob you\'ve set up during install.'] = 'Gheibh thu liosta nan obraichean a tha sa chiutha an-dràsta air an duilleag seo. Thèid na h-obraichean seo a làimhseachadh leis a’ cronjob a shuidhich thu rè an stàlaidh.';
$a->strings['ID'] = 'ID';
$a->strings['Command'] = 'Àithne';
$a->strings['Job Parameters'] = 'Paramadairean na h-obrach';
$a->strings['Created'] = 'Air a chruthachadh';
$a->strings['Priority'] = 'Prìomhachas';
$a->strings['%s is no valid input for maximum image size'] = 'Chan eil %s ’na ion-chur dligheach do mheud as motha nan dealbhan';
$a->strings['No special theme for mobile devices'] = 'Chan eil ùrlar sònraichte do dh’uidheaman mobile ann';
$a->strings['%s - (Experimental)'] = '%s – (deuchainneil)';
$a->strings['No community page'] = 'Gun duilleag coimhearsnachd';
$a->strings['No community page for visitors'] = 'Gun duilleag coimhearsnachd do dh’aoighean';
$a->strings['Public postings from users of this site'] = 'Postaichean poblach nan cleachdaichean air an làrach seo';
$a->strings['Public postings from the federated network'] = 'Postaichean poblach on lìonra cho-naisgte';
$a->strings['Public postings from local users and the federated network'] = 'Postaichean poblach nan cleachdaichean ionadail ’s on lìonra cho-naisgte';
$a->strings['Multi user instance'] = 'Ionstans ioma-chleachdaiche';
$a->strings['Closed'] = 'Dùinte';
$a->strings['Requires approval'] = 'Tha feum air aontachadh';
$a->strings['Open'] = 'Fosgailte';
$a->strings['Don\'t check'] = 'Na cuir sùil';
$a->strings['check the stable version'] = 'cuir sùil air na tionndadh seasmhach';
$a->strings['check the development version'] = 'cuir sùil air na tionndadh leasachaidh';
$a->strings['none'] = 'chan eil gin';
$a->strings['Local contacts'] = 'Luchd-aithne an ionadail';
$a->strings['Interactors'] = 'Luchd co-luadair';
$a->strings['Site'] = 'Làrach';
$a->strings['General Information'] = 'Fiosrachadh coitcheann';
$a->strings['Republish users to directory'] = 'Ath-fhoillsich na cleachdaichean dhan eòlaire';
$a->strings['Registration'] = 'Clàradh';
$a->strings['File upload'] = 'Luchdadh suas fhaidhlichean';
$a->strings['Policies'] = 'Poileasaidhean';
$a->strings['Advanced'] = 'Adhartach';
$a->strings['Auto Discovered Contact Directory'] = 'Eòlaire an luchd-aithne a chaidh a lorg gu fèin-obrachail';
$a->strings['Performance'] = 'Dèanadas';
$a->strings['Worker'] = 'Obraiche';
$a->strings['Message Relay'] = 'Ath-sheachadan theachdaireachdan';
$a->strings['Use the command "console relay" in the command line to add or remove relays.'] = 'Cleachd an àithne “console relay” air an loidhne-àithne a chur ris no a thoirt air falbh ath-sheachadain.';
$a->strings['The system is not subscribed to any relays at the moment.'] = 'Cha do rinn an siostam fo-sgrìobhadh air ath-sheachadan sam bith aig an àm seo.';
$a->strings['The system is currently subscribed to the following relays:'] = 'Tha an siostam a’ fo-sgrìobhadh dha na h-ath-sheachadain seo:';
$a->strings['Relocate Node'] = 'Imrich an nòd';
$a->strings['Relocating your node enables you to change the DNS domain of this node and keep all the existing users and posts. This process takes a while and can only be started from the relocate console command like this:'] = 'Le imrich an nòd, ’s urrainn dhut àrainn DNS an nòid seo atharrachadh agus na cleachdaichean is postaichean uile a chumail. Bheir am pròiseas seo greis mhath is cha ghabh a thòiseachadh ach le àithne imrich na consoil mar seo:';
$a->strings['(Friendica directory)# bin/console relocate https://newdomain.com'] = '(Pasgan Friendica)# bin/console relocate https://àrainn-ùr.com';
$a->strings['Site name'] = 'Ainm na làraich';
$a->strings['Sender Email'] = 'Post-d an t-seòladair';
$a->strings['The email address your server shall use to send notification emails from.'] = 'An seòladh puist-d a chleachdas am frithealaiche airson brathan puist-d a chur uaithe.';
$a->strings['Name of the system actor'] = 'Ainm actar an t-siostaim';
$a->strings['Name of the internal system account that is used to perform ActivityPub requests. This must be an unused username. If set, this can\'t be changed again.'] = 'Ainm cunntas inntearnail an fhrithealaiche a thèid a chleachdadh airson iarrtasan ActivityPub. Feumaidh seo a bhith ’na ainm-chleachdaiche gun chleachdadh. Nuair a bhios seo air a shuidheachadh, gha ghabh atharrachadh a-rithist.';
$a->strings['Banner/Logo'] = 'Bratach/Suaicheantas';
$a->strings['Email Banner/Logo'] = 'Bratach/Suaicheantas puist-d';
$a->strings['Shortcut icon'] = 'Ìomhaigheag na h-ath-ghoirid';
$a->strings['Link to an icon that will be used for browsers.'] = 'Ceangal gu ìomhaigheag a thèid a chleachdadh le brabhsairean.';
$a->strings['Touch icon'] = 'Ìomhaigheag suathaidh';
$a->strings['Link to an icon that will be used for tablets and mobiles.'] = 'Ceangal gu ìomhaigheag a thèid a chleachdadh airson tablaidean is fònaichean-làimhe.';
$a->strings['Additional Info'] = 'Barrachd fiosrachaidh';
$a->strings['For public servers: you can add additional information here that will be listed at %s/servers.'] = 'Airson frithealaichean poblach: ’s urrainn dhut barrachd fiosrachaidh a chur ris an-seo a nochdas air %s/servers.';
$a->strings['System language'] = 'Cànan an t-siostaim';
$a->strings['System theme'] = 'Ùrlar an t-siostaim';
$a->strings['Default system theme - may be over-ridden by user profiles - <a href="%s" id="cnftheme">Change default theme settings</a>'] = 'Ùrlar bunaiteach an t-siostaim – gabhaidh a thar-àithneadh le pròifilean cleachdaiche – <a href="%s" id="cnftheme">Atharraich roghainnean an ùrlair bhunaitich</a>';
$a->strings['Mobile system theme'] = 'Ùrlar mobile an t-siostaim';
$a->strings['Theme for mobile devices'] = 'An t-ùrlar do dh’uidheaman mobile';
$a->strings['Force SSL'] = 'Spàrr SSL';
$a->strings['Force all Non-SSL requests to SSL - Attention: on some systems it could lead to endless loops.'] = 'Spàrr SSL air a h-uile iarrtas gun SSL – An aire: dh’fhaoidte gun adhbharaich seo lùban gun chrìoch air cuid a shiostaman.';
$a->strings['Show help entry from navigation menu'] = 'Seall nì na cobharach ann an clàr-taice na seòladaireachd';
$a->strings['Displays the menu entry for the Help pages from the navigation menu. It is always accessible by calling /help directly.'] = 'Seallaidh an nì airson duilleagan na cobharach ann an clàr-taice na seòladaireachd. Gabhaidh inntrigeadh le gairm /help gu dìreach an-còmhnaidh.';
$a->strings['Single user instance'] = 'Ionstans aon-chleachdaiche';
$a->strings['Make this instance multi-user or single-user for the named user'] = 'Dèan ionstans ioma-chleachdaiche no aon-chleachdaiche dhan chleachdaiche ainmichte dhen ionstans seo';
$a->strings['Maximum image size'] = 'Meud as motha nan dealbhan';
$a->strings['Maximum size in bytes of uploaded images. Default is 0, which means no limits. You can put k, m, or g behind the desired value for KiB, MiB, GiB, respectively.
													The value of <code>upload_max_filesize</code> in your <code>PHP.ini</code> needs be set to at least the desired limit.
													Currently <code>upload_max_filesize</code> is set to %s (%s byte)'] = 'Am meud as motha ann am baidht do dhealbhan a thèid a luchdadh suas. Is 0 a’ bhun-roghainn, ’s e sin gun chrìoch. ’S urrainn dhut k, m no g a chur às dèidh an luacha a thogras tu airson KiB, MiB no GiB.
													Feumaidh tu an luach air <code>upload_max_filesize</code> sa <code>PHP.ini</code> a shuidheachadh air a’ chrìoch a thogras tu air a char as lugha.
													Chaidh <code>upload_max_filesize</code> a shuidheachadh air %s (%s baidht) aig an àm seo';
$a->strings['Maximum image length'] = 'Faide as motha nan dealbhan';
$a->strings['Maximum length in pixels of the longest side of uploaded images. Default is -1, which means no limits.'] = 'An fhaide as motha ann am piogsail aig an taobh as fhaide do dhealbhan a thèid a luchdadh suas. Is -1 a’ bhun-roghainn, ’s e sin gun chrìoch.';
$a->strings['JPEG image quality'] = 'Càileachd deilbh JPEG';
$a->strings['Uploaded JPEGS will be saved at this quality setting [0-100]. Default is 100, which is full quality.'] = 'Thèid dealbhan a’ sàbhaladh leis a’ chàileachd JPEG seo às dèidh an luchdadh suas [0-100]. Is 100 a’ bhun-roghainn, ’s e sin a’ chàileachd shlàn.';
$a->strings['Register policy'] = 'Poileasaidh clàraidh';
$a->strings['Maximum Users'] = 'Àireamh as motha de chleachdaichean';
$a->strings['If defined, the register policy is automatically closed when the given number of users is reached and reopens the registry when the number drops below the limit. It only works when the policy is set to open or close, but not when the policy is set to approval.'] = 'Ma bhios seo air a mhìneachadh, thèid poileasaidh nan clàraidhean a dhùnadh gu fèin-obrachail nuair a bhios an àireamh shònraichte de chleachdaichean air a ruigsinn agus fhosgladh a-rithist nuair a thèid an àireamh nas ìsle na a’ chrìoch. Chan obraich seo ach ma chaidh am poileasaidh a shuidheachadh air “Fosgailte” no “Dùinte” agus chan obraich e ma chaidh am poileasaidh a shuidheachadh air “Aontachadh”.';
$a->strings['Maximum Daily Registrations'] = 'An àireamh as motha de chlàraidhean gach latha';
$a->strings['If registration is permitted above, this sets the maximum number of new user registrations to accept per day.  If register is set to closed, this setting has no effect.'] = 'Ma tha an clàradh ceadaichte gu h-àrd, suidhichidh seo an àireamh as motha de chlàraidhean chleachdaichean ùra gach latha. Ma tha an clàradh dùinte, cha doir an roghainn seo buaidh.';
$a->strings['Register text'] = 'Teacsa a’ chlàraidh';
$a->strings['Will be displayed prominently on the registration page. You can use BBCode here.'] = 'Thèid a shealltainn gu follaiseach air duilleag a’ chlàraidh. ’S urrainn dhut BBCode a chleachdadh an-seo.';
$a->strings['Forbidden Nicknames'] = 'Far-ainmean toirmisgte';
$a->strings['Comma separated list of nicknames that are forbidden from registration. Preset is a list of role names according RFC 2142.'] = 'Liosta sgaraichte le cromagan de dh’fhar-ainmean nach fhaod clàradh. Tha liosta de dh’ainmean dreuchdan a-rèir RFC 2142 ro-shocraichte.';
$a->strings['Accounts abandoned after x days'] = 'Cunntasan air an trèigsinn às dèidh x là';
$a->strings['Will not waste system resources polling external sites for abandonded accounts. Enter 0 for no time limit.'] = 'Cha chaith seo goireasan an t-siostam le ceasnachadh làraichean air an taobh a-muigh airson cunntasan air an trèigsinn. Cuir a-steach 0 airson cunntasan gun chrìoch ùine.';
$a->strings['Allowed friend domains'] = 'Àrainnean ceadaichte do chàirdeasan';
$a->strings['Comma separated list of domains which are allowed to establish friendships with this site. Wildcards are accepted. Empty to allow any domains'] = 'Liosta sgaraichte le cromagan de dh’àrainnean a dh’fhaodas càirdeasan a stèidheachadh leis an làrach seo. Gabhaidh seo ri saoragan. Fàg bàn airson a h-uile àrainn a cheadachadh';
$a->strings['Allowed email domains'] = 'Àrainnean puist-d ceadaichte';
$a->strings['Comma separated list of domains which are allowed in email addresses for registrations to this site. Wildcards are accepted. Empty to allow any domains'] = 'Liosta sgaraichte le cromagan de dh’àrainnean a tha ceadaichte ann am puist-d airson clàradh leis an làrach seo. Gabhaidh seo ri saoragan. Fàg bàn airson a h-uile àrainn a cheadachadh';
$a->strings['No OEmbed rich content'] = 'Gun susbaint OEmbed bheartach';
$a->strings['Don\'t show the rich content (e.g. embedded PDF), except from the domains listed below.'] = 'Na seall susbaint bheartach (m. e. PDF leabaichte) ach o na h-àrainnean air an liosta gu h-ìosal.';
$a->strings['Trusted third-party domains'] = 'Àrainnean threas-phàrtaidhea earbsach';
$a->strings['Comma separated list of domains from which content is allowed to be embedded in posts like with OEmbed. All sub-domains of the listed domains are allowed as well.'] = 'Liosta sgaraichte le cromagan de dh’àrainnean on a dh’fhaodar susbaint a leabachadh am broinn phostaichean le OEmbed. Thèid cead a thoirt dhan a h-uile fo-àrainn dhe na h-àrainnean air an liosta cuideachd.';
$a->strings['Block public'] = 'Bac inntrigeadh poblach';
$a->strings['Check to block public access to all otherwise public personal pages on this site unless you are currently logged in.'] = 'Cuir cromag ris a bhacadh inntrigeadh poblach air a h-uile duilleag phearsanta a bhiodh poblach air an làrach seo ach dhan fheadhainn a bhios air a clàradh a-staigh.';
$a->strings['Force publish'] = 'Spàrr foillseachadh';
$a->strings['Check to force all profiles on this site to be listed in the site directory.'] = 'Cuir cromag ris a sparradh air a h-uile pròifil air an làrach seo gun nochd iad ann an eòlaire na làraich.';
$a->strings['Enabling this may violate privacy laws like the GDPR'] = 'Ma chuireas tu seo an comas, dh’fhaoidte gum bris thu laghan mar an GDPR';
$a->strings['Global directory URL'] = 'URL an eòlaire cho-naisgte';
$a->strings['URL to the global directory. If this is not set, the global directory is completely unavailable to the application.'] = 'URL dhan eòlaire cho-naisgte. Mura deach seo a shuidheachadh, cha bhi an t-eòlaire uile-choitcheann ri fhaighinn idir dhan aplacaid.';
$a->strings['Private posts by default for new users'] = 'Postaichean prìobhaideach ’na bhun-roghainn do chleachdaichean ùra';
$a->strings['Set default post permissions for all new members to the default privacy circle rather than public.'] = 'Suidhichidh seo ceadan phostaichean nam ball ùra air a’ chearcall phrìobhaideach gu bunaiteach seach air a’ chearcall phoblach.';
$a->strings['Don\'t include post content in email notifications'] = 'Na gabh a-staigh susbaint nam post ann am brathan puist-d';
$a->strings['Don\'t include the content of a post/comment/private message/etc. in the email notifications that are sent out from this site, as a privacy measure.'] = 'Na gabh a-staigh susbaint puist/beachd/teachdaireachd prìobhaidich/msaa. sna brathan puist-d a thèid a chur on làrach seo, a chùm prìobhaideachd.';
$a->strings['Disallow public access to addons listed in the apps menu.'] = 'Na ceadaich inntrigeadh poblach dha na tuilleadain a tha air an liosta ann an clàr-taice nan aplacaidean.';
$a->strings['Checking this box will restrict addons listed in the apps menu to members only.'] = 'Ma tha cromag sa bhogsa seo, bidh an t-inntrigeadh air na tuilleadain a chithear ann an clàr-taice nan aplacaidean cuingichte air na buill a-mhàin.';
$a->strings['Don\'t embed private images in posts'] = 'Na leabaich dealbhan prìobhaideach ann am postaichean';
$a->strings['Don\'t replace locally-hosted private photos in posts with an embedded copy of the image. This means that contacts who receive posts containing private photos will have to authenticate and load each image, which may take a while.'] = 'Na cuir lethbhreac leabaichte dhen dealbh an àite dhealbhan prìobhaideach ann am postaichean a tha ’gan òstadh gu h-ionadail. Is ciall dha seo gum feum an luchd-aithne a gheibh postaichean sa bheil dealbhan prìobhaideach ùghdarrachadh is gach dealbh a luchdadh fa leth agus dh’fhaoidte gun doir sin greis.';
$a->strings['Explicit Content'] = 'Susbaint inbheach';
$a->strings['Set this to announce that your node is used mostly for explicit content that might not be suited for minors. This information will be published in the node information and might be used, e.g. by the global directory, to filter your node from listings of nodes to join. Additionally a note about this will be shown at the user registration page.'] = 'Suidhich seo a dh’innse gu bheil an nòd agad ’ga chleachdadh airson susbaint inbheach gu h-àraidh is nach eil e iomchaidh do mhion-aoisich ’s dòcha. Thèid am fiosrachadh seo fhoillseachadh ann am fiosrachadh an nòid agus gabhaidh a chleachdadh m.e. leis an eòlaire cho-naisgte airson an nòd agad a chriathradh air falbh o liosta nan nòdan a chùm ballrachd ùir. A bharrachd air sin, thèid nòta a shealltainn aig duilleag clàradh nan cleachdaichean.';
$a->strings['Cache contact avatars'] = 'Cuir avataran an luchd-aithne dhan tasgadan';
$a->strings['Locally store the avatar pictures of the contacts. This uses a lot of storage space but it increases the performance.'] = 'Stòr dealbhan avatar an luchd-aithne gu h-ionadail. Cleachdaidh seo tòrr àite san stòras ach cuiridh e ris an dèanadas.';
$a->strings['Allow Users to set remote_self'] = 'Leig le cleachdaichean remote_self a shuidheachadh';
$a->strings['With checking this, every user is allowed to mark every contact as a remote_self in the repair contact dialog. Setting this flag on a contact causes mirroring every posting of that contact in the users stream.'] = 'Ma chuireas tu cromag ris, faodaidh gach cleachdaiche neach-aithne sam bith a chomharrachadh mar remote_self ann an còmhradh càradh an luchd-aithne. Nuair a thèid a’ bhratach seo a chur ri neach-aithne, thèid a h-uile post an neach-aithne sin sgàthanachadh ann an sruth a’ chlechdaiche.';
$a->strings['Enable multiple registrations'] = 'Cuir clàradh iomadach an comas';
$a->strings['Enable users to register additional accounts for use as pages.'] = 'Bheir seo an comas dha na cleachdaichean gun clàraich iad cunntasan a bharrachd airson an cleachdadh ’nan duilleagan.';
$a->strings['Enable OpenID'] = 'Cuir OpenID an comas';
$a->strings['Enable OpenID support for registration and logins.'] = 'Cuir an comas taic dha OpenID airson clàradh is clàradh a-steach.';
$a->strings['Email administrators on new registration'] = 'Cuir puist-d dha na rianairean do chlàraidhean ùra';
$a->strings['If enabled and the system is set to an open registration, an email for each new registration is sent to the administrators.'] = 'Nuair a bhios seo an comas agus an siostam air a shuidheachadh air clàraidhean fosgailte, thèid post-d a chur dha na rianairean do gach clàradh ùr.';
$a->strings['Community pages for visitors'] = 'Duilleagan coimhearsnachd do dh’aoighean';
$a->strings['Which community pages should be available for visitors. Local users always see both pages.'] = 'Dè na duilleagan coimhearsnachd a chì aoighean. Chì na cleachdaichean ionadail an dà dhuilleag an-còmhnaidh.';
$a->strings['Posts per user on community page'] = 'Postaichean gach cleachdaiche air duilleag na coimhearsnachd';
$a->strings['Enable Mail support'] = 'Cuir taic ri post-d an comas';
$a->strings['Enable built-in mail support to poll IMAP folders and to reply via mail.'] = 'Cuir an comas an taic ri post-d a thig ’na bhroinn airson pasganan IMAP a cheasnachadh agus freagairt le post-d.';
$a->strings['Mail support can\'t be enabled because the PHP IMAP module is not installed.'] = 'Chan urrainn dhuinn an taic ri post-d a chur an comas air sgàth ’s nach deach am mòideal IMAP aig PHP a stàladh.';
$a->strings['Diaspora support can\'t be enabled because Friendica was installed into a sub directory.'] = 'Chan urrainn dhuinn an taic ri diaspora* a chur an comas on a chaidh Friendica a stàladh ann am fo-phasgan.';
$a->strings['Enable Diaspora support'] = 'Cuir taic ri diaspora* an comas';
$a->strings['Enable built-in Diaspora network compatibility for communicating with diaspora servers.'] = 'Cuir an comas a’ cho-chòrdalachd lìonraidh le diaspora* a thig ’na bhroinn airson conaltradh le frithealaichean diaspora*.';
$a->strings['Verify SSL'] = 'Dearbh SSL';
$a->strings['If you wish, you can turn on strict certificate checking. This will mean you cannot connect (at all) to self-signed SSL sites.'] = 'Ma thogras tu, ’s urrainn dhut sgrùdadh teann nan teisteanasan a chur an comas. Is ciall dha seo nach urrainn dhut ceangal idir le làraichean le SSL a chaidh fhèin-shoidhneadh.';
$a->strings['Proxy user'] = 'Cleachdaiche a’ phrogsaidh';
$a->strings['User name for the proxy server.'] = 'Ainm-cleachdaiche dhan fhrithealaiche progsaidh.';
$a->strings['Proxy URL'] = 'URL a’ phrogsaidh';
$a->strings['If you want to use a proxy server that Friendica should use to connect to the network, put the URL of the proxy here.'] = 'Ma tha thu airson ’s gun cleachd Friendica frithealaiche progsaidh airson ceangal ris an lìonra, cuir URL a’ phrogsaidh an-seo.';
$a->strings['Network timeout'] = 'Crìoch-ùine an lìonraidh';
$a->strings['Value is in seconds. Set to 0 for unlimited (not recommended).'] = 'Luach ann an diogan. Is ciall dha 0 nach fhalbh an ùine air idir (cha mholamaid seo).';
$a->strings['Maximum Load Average'] = 'Eallach as motha sa chuibheas';
$a->strings['Maximum system load before delivery and poll processes are deferred - default %d.'] = 'Eallach as motha air an t-siostam mus dèid dàil a chur air an lìbhrigeadh is air pròiseasadh cunbhalach – ’s e %d a tha sa bhun-roghainn.';
$a->strings['Minimal Memory'] = 'A’ chuimhne as lugha';
$a->strings['Minimal free memory in MB for the worker. Needs access to /proc/meminfo - default 0 (deactivated).'] = 'A’ chuimhne shaor as lugha ann an MB dhan obraiche. Bidh feum air inntrigeadh dha /proc/meminfo – is 0 a’ bhun-roghainn (à gnìomh).';
$a->strings['Periodically optimize tables'] = 'Pisich na clàran o àm gu àm';
$a->strings['Periodically optimize tables like the cache and the workerqueue'] = 'Pisich clàran mar an tasgadan is an ciutha-obrach gu cunbhalach';
$a->strings['Discover followers/followings from contacts'] = 'Lorg dàimhean leantainn on luchd-aithne';
$a->strings['If enabled, contacts are checked for their followers and following contacts.'] = 'Ma tha seo an comas, thèid sùil a thoirt airson luchd-leantainn an luchd-aithne is an fheadhainn air a leanas iad.';
$a->strings['None - deactivated'] = 'Chan eil gin – à comas';
$a->strings['Local contacts - contacts of our local contacts are discovered for their followers/followings.'] = 'Luchd-aithne ionadail – thèid dàimhean leantainn an luchd-aithne ionadail a lorg.';
$a->strings['Interactors - contacts of our local contacts and contacts who interacted on locally visible postings are discovered for their followers/followings.'] = 'Luchd co-luadair – thèid dàimhean leantainn an luchd-aithne ionadail agus aig an fheadhainn a rinn co-luadar le postaichean poblach a lorg.';
$a->strings['Synchronize the contacts with the directory server'] = 'Sioncronaich an luchd-aithne le frithealaiche an eòlaire';
$a->strings['if enabled, the system will check periodically for new contacts on the defined directory server.'] = 'Ma tha seo an comas, bheir an siostam sùil gu cunbhalach airson luchd-aithne ùr air frithealaiche an eòlaire a chaidh a mhìneachadh.';
$a->strings['Discover contacts from other servers'] = 'Lorg luchd-aithne o fhrithealaichean eile';
$a->strings['Days between requery'] = 'Làithean eadar ceasnachaidhean';
$a->strings['Search the local directory'] = 'Lorg san eòlaire ionadail';
$a->strings['Search the local directory instead of the global directory. When searching locally, every search will be executed on the global directory in the background. This improves the search results when the search is repeated.'] = 'Lorg san eòlaire ionadail seach san eòlaire cho-naisgte. Nuair a nì thu lorg gu h-ionadail, thèid gach lorg a ghnìomhachadh san eòlaire cho-naisgte sa chùlaibh. Cuiridh seo piseach air na toraidhean luirg nuair a nithear an t-aon lorg a-rithist.';
$a->strings['Publish server information'] = 'Foillsich fiosrachadh an fhrithealaiche';
$a->strings['If enabled, general server and usage data will be published. The data contains the name and version of the server, number of users with public profiles, number of posts and the activated protocols and connectors. See <a href="http://the-federation.info/">the-federation.info</a> for details.'] = 'Ma tha seo an comas, thèid dàta coitcheann mun fhrithealaiche ’s cleachdadh fhoillseachadh. Gabhaidh an dàta a-staigh ainm is tionndadh an fhrithealaiche, an àireamh de chleachdaichean le pròifilean poblach, an àireamh de phostaichean agus na pròtacalan is ceangladairean an comas. Faic <a href="http://the-federation.info/">the-federation.info</a> airson barrachd fiosrachaidh.';
$a->strings['Check upstream version'] = 'Cuir sùil air tionndadh an upstream';
$a->strings['Enables checking for new Friendica versions at github. If there is a new version, you will be informed in the admin panel overview.'] = 'Cuiridh seo an comas gun dèid sùil a chur airson tionndaidhean dhe Friendica air GitHub. Nuair a bhios tionndadh ùr an, thèid innse dhut air foir-shealladh panail na rianachd.';
$a->strings['Suppress Tags'] = 'Mùch tagaichean';
$a->strings['Suppress showing a list of hashtags at the end of the posting.'] = 'Mùch sealladh liosta nan tagaichean hais air deireadh nam post.';
$a->strings['Clean database'] = 'Sgioblaich an stòr-dàta';
$a->strings['Remove old remote items, orphaned database records and old content from some other helper tables.'] = 'Thoir air falbh nithean cèine, reacordan stòir-dhàta a tha ’nan dìlleachdanan agus seann-susbaint eile o chuid a chlàran-taice eile.';
$a->strings['Lifespan of remote items'] = 'Faid-bheatha nan nithean cèine';
$a->strings['When the database cleanup is enabled, this defines the days after which remote items will be deleted. Own items, and marked or filed items are always kept. 0 disables this behaviour.'] = 'Nuair a bhios sgioblachadh an stòir-dhàta an comas, mìnichidh seo na làithean mus dèid nithean cèine a sguabadh às. Thèid na nithean againn fhèin ’s na nithean a chaidh a chomharrachadh no fhaidhleadh a chumail an-còmhnaidh. Cuiridh 0 an giùlan seo à comas.';
$a->strings['Lifespan of unclaimed items'] = 'Faid-bheatha nan nithean gun tagairt';
$a->strings['When the database cleanup is enabled, this defines the days after which unclaimed remote items (mostly content from the relay) will be deleted. Default value is 90 days. Defaults to the general lifespan value of remote items if set to 0.'] = 'Nuair a bhios sgioblachadh an stòir-dhàta an comas, mìnichidh seo na làithean mus dèid nithean gun tagairt (seo susbaint on ath-sheachadan mar as trice) a sguabadh às. ’S e 90 latha a tha sa bhun-roghainn. Ma shuidhicheas tu seo air 0, thèid luach faid-bheatha nan nithean cèine a chleachdadh mar bhun-roghainn.';
$a->strings['Lifespan of raw conversation data'] = 'Faid-bheatha dàta amh nan còmhraidhean';
$a->strings['Maximum numbers of comments per post'] = 'An àireamh as motha de bheachdan ri post';
$a->strings['How much comments should be shown for each post? Default value is 100.'] = 'Co mheud beachd a thèid a shealltainn do gach post? ’S e 100 a tha sa bhun-roghainn.';
$a->strings['Maximum numbers of comments per post on the display page'] = 'An àireamh as motha de bheachdan ri post air duilleag na sealltainn';
$a->strings['How many comments should be shown on the single view for each post? Default value is 1000.'] = 'Co mheud beachd a thèid a shealltainn do gach post nuair a sheallar post fa leth? ’S e 1000 a tha sa bhun-roghainn.';
$a->strings['Temp path'] = 'Slighe shealadach';
$a->strings['If you have a restricted system where the webserver can\'t access the system temp path, enter another path here.'] = 'Ma tha siostam cuingichte agad far nach urrainn dhan fhrithealaiche-lìn slighe temp an t-siostaim inntrigeadh, cuir a-steach slighe eile an-seo.';
$a->strings['Only search in tags'] = 'Na lorg ach sna tagaichean';
$a->strings['On large systems the text search can slow down the system extremely.'] = 'Air siostaman mòra, ’s urrainn dhan lorg teacsa maille mhòr a chur air an t-siostam.';
$a->strings['Generate counts per contact circle when calculating network count'] = 'Cruthaich cunntasan do chearcallan luchd-aithne nuair a thèid cunntas an lìonraidh àireamhachadh';
$a->strings['On systems with users that heavily use contact circles the query can be very expensive.'] = 'Air siostaman far an bheil tòrr chearcallan luchd-aithne ’gan cleachdadh, faodaidh a’ cheist seo a bhith glè dhaor.';
$a->strings['Maximum number of parallel workers'] = 'An àireamh as motha de dh’obraichean co-shìnte';
$a->strings['On shared hosters set this to %d. On larger systems, values of %d are great. Default value is %d.'] = 'Suidhich seo air %d air òstairean co-roinnte. Air siostaman nas motha, bidh luach %d math. Is %d a’ bhun-roghainn.';
$a->strings['Enable fastlane'] = 'Cuir fastlane an comas';
$a->strings['When enabed, the fastlane mechanism starts an additional worker if processes with higher priority are blocked by processes of lower priority.'] = 'Nuair a bhios seo an comas, cuiridh am fastlane obraiche a bharrachd air dòigh ma tha pròiseas air a bheil prìomhachas as àirde ’ga bhacadh le pròiseasan air a bheil prìomhachas ìosal.';
$a->strings['Direct relay transfer'] = 'Tar-chur ath-sheachadain dìreach';
$a->strings['Enables the direct transfer to other servers without using the relay servers'] = 'Cuiridh seo an comas an tar-chur dìreach gu frithealaichean eile às aonais nam frithealaichean ath-sheachadain';
$a->strings['Relay scope'] = 'Sgòp an ath-sheachadain';
$a->strings['Can be "all" or "tags". "all" means that every public post should be received. "tags" means that only posts with selected tags should be received.'] = 'Tha taghadh eadar “na h-uile” is “tagaichean” agad. Is ciall dha “na h-uile” gun dèid gach post poblach fhaighinn. Is ciall dha “tagaichean” nach dèid ach postaichean le tagaichean sònraichte fhaighinn.';
$a->strings['Disabled'] = 'À comas';
$a->strings['all'] = 'na h-uile';
$a->strings['tags'] = 'tagaichean';
$a->strings['Server tags'] = 'Tagaichean an fhrithealaiche';
$a->strings['Comma separated list of tags for the "tags" subscription.'] = 'Liosta de thagaichean airson fo-sgrìobhadh nan “tagaichean”, sgaraichte le cromagan.';
$a->strings['Deny Server tags'] = 'Tagaichean an fhrithealaiche ’gan diùltadh';
$a->strings['Comma separated list of tags that are rejected.'] = 'Liosta da thagaichean a tha ’gan diùltadh, sgaraichte le cromagan.';
$a->strings['Allow user tags'] = 'Ceadaich tagaichean chleachdaichean';
$a->strings['If enabled, the tags from the saved searches will used for the "tags" subscription in addition to the "relay_server_tags".'] = 'Ma tha seo an comas, thèid na tagaichean o na lorgan air an sàbhaladh a chleachdadh airson fo-sgrìobhadh nan “tagaichean” a bharrachd air na “relay_server_tags”.';
$a->strings['Start Relocation'] = 'Tòisich air an imrich';
$a->strings['Storage backend, %s is invalid.'] = 'Backend an stòrais, tha %s mì-dhligheach.';
$a->strings['Storage backend %s error: %s'] = 'Mearachd backend an stòrais %s: %s';
$a->strings['Invalid storage backend setting value.'] = 'Luach roghainne mì-dhligheach air backend an stòrais.';
$a->strings['Current Storage Backend'] = 'Backend làithreach an stòrais';
$a->strings['Storage Configuration'] = 'Rèiteachadh an stòrais';
$a->strings['Storage'] = 'An stòras';
$a->strings['Save & Use storage backend'] = 'Sàbhail ⁊ cleachd backend an stòrais';
$a->strings['Use storage backend'] = 'Cleachd backend an stòrais';
$a->strings['Save & Reload'] = 'Sàbhail ⁊ ath-luchdaich';
$a->strings['This backend doesn\'t have custom settings'] = 'Chan eil roghainnean gnàthaichte aig a’ backend seo';
$a->strings['Changing the current backend is prohibited because it is set by an environment variable'] = 'Chan fhaod thu am backend làithreach atharrachadh on a chaidh a shuidheachadh le caochladair àrainne';
$a->strings['Database (legacy)'] = 'Stòr-dàta (dìleabach)';
$a->strings['Template engine (%s) error: %s'] = 'Mearachd einnsean teamplaide (%s): %s';
$a->strings['Your DB still runs with MyISAM tables. You should change the engine type to InnoDB. As Friendica will use InnoDB only features in the future, you should change this! See <a href="%s">here</a> for a guide that may be helpful converting the table engines. You may also use the command <tt>php bin/console.php dbstructure toinnodb</tt> of your Friendica installation for an automatic conversion.<br />'] = 'Tha an stòr-dàta agad a’ cleachdadh clàran MyISAM fhathast. Bu chòir dhut seòrsa an einnsein atharrachadh gu InnoDB. Air sgàth ’s gun cleachd Friendica gleusan InnoDB sònraichte san àm ri teachd, bu chòir dhut seo atharrachadh! Faic <a href="%s">an treòir</a> a tha cuideachail airson einnseanan nan clàran iompachadh. ’S urrainn dhut cuideachd an àithne <tt>php bin/console.php dbstructure toinnodb</tt> aig an stàladh agad dhe Friendica a chleachdadh airson iompachadh fèin-obrachail.<br />';
$a->strings['Your DB still runs with InnoDB tables in the Antelope file format. You should change the file format to Barracuda. Friendica is using features that are not provided by the Antelope format. See <a href="%s">here</a> for a guide that may be helpful converting the table engines. You may also use the command <tt>php bin/console.php dbstructure toinnodb</tt> of your Friendica installation for an automatic conversion.<br />'] = 'Tha an stòr-dàta agad a’ cleachdadh clàran InnoDB san fhòrmat faidhle Antelope fhathast. Bu chòir dhut fòrmat nam faidhlichean atharrachadh gu Barracuda. Tha Friendica a’ cleachdadh gleusan nach solair fòrmat Antelope. Faic <a href="%s">an treòir</a> a tha cuideachail airson einnseanan nan clàran iompachadh. ’S urrainn dhut cuideachd an àithne <tt>php bin/console.php dbstructure toinnodb</tt> aig an stàladh agad dhe Friendica a chleachdadh airson iompachadh fèin-obrachail.<br />';
$a->strings['Your table_definition_cache is too low (%d). This can lead to the database error "Prepared statement needs to be re-prepared". Please set it at least to %d. See <a href="%s">here</a> for more information.<br />'] = 'Tha an table_definition_cache agad ro ìosal (%d). Dh’fhaoidte gun adhbharaich seo mearachd “Prepared statement needs to be re-prepared” an stòir-dhàta. Suidhich air %d e air a char as lugha. Seall <a href="%s">an-seo</a> airson barrachd fiosrachaidh.<br />';
$a->strings['There is a new version of Friendica available for download. Your current version is %1$s, upstream version is %2$s'] = 'Tha tionndadh ùr dhe Friendica ri fhaighinn airson luchdadh a-nuas. ’S e %1$s a tha san tionndadh làithreach agad, ’S e %2$s a tha san tionndadh upstream';
$a->strings['The database update failed. Please run "php bin/console.php dbstructure update" from the command line and have a look at the errors that might appear.'] = 'Dh’fhàillig le ùrachadh an stòir-dhàta. Ruith “php bin/console.php dbstructure update” on loidhne-àithne is thoir sùil air na mearachdan a nochdas ma dh’fhaoidte.';
$a->strings['The last update failed. Please run "php bin/console.php dbstructure update" from the command line and have a look at the errors that might appear. (Some of the errors are possibly inside the logfile.)'] = 'Dh’fhàillig leis an ùrachadh mu dheireadh. Ruith “php bin/console.php dbstructure update” on loidhne-àithne is thoir sùil air na mearachdan a nochdas ma dh’fhaoidte. (Faodaidh cuid dhe na mearachdan nochdadh ann am faidhle an loga.)';
$a->strings['The system.url entry is missing. This is a low level setting and can lead to unexpected behavior. Please add a valid entry as soon as possible in the config file or per console command!'] = 'Tha innteart system.url a dhìth. Seo suidheachadh air ìre ìosal agus dh’fhaoidte gun adhbharaich seo giùlan air nach robh dùil. Cuir innteart dligheach ris cho luath ’s a ghabhas san fhaidhle config no air an loidhne-àithne!';
$a->strings['The worker was never executed. Please check your database structure!'] = 'Cha deach an obair seo a dhèanamh a-riamh. Thoir sùil air structar an stòir-dhàta agad!';
$a->strings['The last worker execution was on %s UTC. This is older than one hour. Please check your crontab settings.'] = 'Chaidh a obair a dhèanamh aig %s UTC an turas mu dheireadh. Tha seo nas fhaide air ais na uair. Thoir sùil air roghainnean a’ crontab agad.';
$a->strings['Friendica\'s configuration now is stored in config/local.config.php, please copy config/local-sample.config.php and move your config from <code>.htconfig.php</code>. See <a href="%s">the Config help page</a> for help with the transition.'] = 'Tha rèiteachadh Friendica ’ga stòradh ann an config/local.config.php a-nis, dèan lethbhreac dhe config/local-sample.config.php is gluais an rèiteachadh agad o <code>.htconfig.php</code>. Faic <a href="%s">duilleag taic an rèiteachaidh</a> airson cuideachadh leis a’ ghluasad.';
$a->strings['Friendica\'s configuration now is stored in config/local.config.php, please copy config/local-sample.config.php and move your config from <code>config/local.ini.php</code>. See <a href="%s">the Config help page</a> for help with the transition.'] = 'Tha rèiteachadh Friendica ’ga stòradh ann an config/local.config.php a-nis, dèan lethbhreac dhe config/local-sample.config.php is gluais an rèiteachadh agad o <code>config/local.ini.php</code>. Faic <a href="%s">duilleag taic an rèiteachaidh</a> airson cuideachadh leis a’ ghluasad.';
$a->strings['<a href="%s">%s</a> is not reachable on your system. This is a severe configuration issue that prevents server to server communication. See <a href="%s">the installation page</a> for help.'] = 'Cha ghabh <a href="%s">%s</a> ruigsinn on t-siostam agad. Seo droch-dhuilgheadas leis an rèiteachadh nach leig leis an fhrithealaiche conaltradh le frithealaichean eile. Faic <a href="%s">duilleag an stàlaidh</a> airson cuideachadh.';
$a->strings['Friendica\'s system.basepath was updated from \'%s\' to \'%s\'. Please remove the system.basepath from your db to avoid differences.'] = 'Chaidh an system.basepath aig Friendica ùrachadh o “%s” gu “%s”. Thoir air falbh system.basepath on stòr-dàta agad ach nach biodh diofar eatorra.';
$a->strings['Friendica\'s current system.basepath \'%s\' is wrong and the config file \'%s\' isn\'t used.'] = 'Tha an system.basepath làithreach “%s” aig Friendica ceàrr is chan eil am faidhle rèiteachaidh “%s” ’ga chleachdadh.';
$a->strings['Friendica\'s current system.basepath \'%s\' is not equal to the config file \'%s\'. Please fix your configuration.'] = 'Chan eil an system.basepath làithreach “%s” co-ionnan ris an fhaidhle rèiteachaidh “%s”. Càirich an rèiteachadh agad.';
$a->strings['Message queues'] = 'Ciuthan theachdaireachdan';
$a->strings['Server Settings'] = 'Roghainnean an fhrithealaiche';
$a->strings['Version'] = 'Tionndadh';
$a->strings['Active addons'] = 'Tuilleadain ghnìomhach';
$a->strings['Theme %s disabled.'] = 'Chaidh an t-ùrlar %s a chur à comas.';
$a->strings['Theme %s successfully enabled.'] = 'Chaidh an t-ùrlar %s a chur an comas.';
$a->strings['Theme %s failed to install.'] = 'Dh’fhàillig le stàladh an ùrlair %s.';
$a->strings['Screenshot'] = 'Glacadh-sgrìn';
$a->strings['Themes'] = 'Ùrlaran';
$a->strings['Unknown theme.'] = 'Ùrlar nach aithne dhuinn.';
$a->strings['Themes reloaded'] = 'Chaidh na h-ùrlaran ath-luchdadh';
$a->strings['Reload active themes'] = 'Ath-luchdaich na h-ùrlaran gnìomhach';
$a->strings['No themes found on the system. They should be placed in %1$s'] = 'Cha deach ùrlar a lorg air an t-siostam. Bu chòir dhut an cur am broinn %1$s';
$a->strings['[Experimental]'] = '[Deuchainneil]';
$a->strings['[Unsupported]'] = '[Chan eil taic ris]';
$a->strings['Display Terms of Service'] = 'Seall teirmichean na seirbheise';
$a->strings['Enable the Terms of Service page. If this is enabled a link to the terms will be added to the registration form and the general information page.'] = 'Cuir an comas duilleag teirmichean na seirbheise. Ma tha seo an comas, thèid ceangal dha na teirmichean a chur ris an fhoirm clàraidh is ri duilleag an fhiosrachaidh choitchinn.';
$a->strings['Display Privacy Statement'] = 'Seall an aithris prìobhaideachd';
$a->strings['Show some informations regarding the needed information to operate the node according e.g. to <a href="%s" target="_blank" rel="noopener noreferrer">EU-GDPR</a>.'] = 'Seall fiosrachadh a thaobh an fhiosrachaidh riatanaich ach an obraich an nòd, can a-rèir <a href="%s" target="_blank" rel="noopener noreferrer">EU-GDPR</a>.';
$a->strings['Privacy Statement Preview'] = 'Ro-shealladh air an aithris prìobhaideachd';
$a->strings['The Terms of Service'] = 'Teirmichean na seirbheise';
$a->strings['Enter the Terms of Service for your node here. You can use BBCode. Headers of sections should be [h2] and below.'] = 'Cuir a-steach teirmichean seirbheis an nòid agad an-seo. ’S urrainn dhut BBCode a chleachdadh. Bu chòir dha cheann-sgrìobhaidhean nan earrannan a bhith ’nan [h2] is nas ìsle.';
$a->strings['The rules'] = 'Na riaghailtean';
$a->strings['Enter your system rules here. Each line represents one rule.'] = 'Cuir a-steach riaghailtean an t-siostaim agad an-seo. Riochdaichidh gach loidhne riaghailt.';
$a->strings['API endpoint %s %s is not implemented but might be in the future.'] = 'Cha deach puing-dheiridh %s %s an API prògramachadh ach ’s dòcha gun dèid san àm ri teachd.';
$a->strings['Missing parameters'] = 'Paramadairean a dhìth';
$a->strings['Only starting posts can be bookmarked'] = 'Cha ghabh ach postaichean-toisich a chur ris na comharran-lìn';
$a->strings['Only starting posts can be muted'] = 'Cha ghabh ach postaichean-toisich a mhùchadh';
$a->strings['Posts from %s can\'t be shared'] = 'Cha ghabh postaichean o %s a co-roinneadh';
$a->strings['Only starting posts can be unbookmarked'] = 'Cha ghabh ach postaichean-toisich a thoirt air falbh o na comharran-lìn';
$a->strings['Only starting posts can be unmuted'] = 'Cha ghabh ach postaichean-toisich a dhì-mhùchadh';
$a->strings['Posts from %s can\'t be unshared'] = 'Cha ghabh sgur de cho-roinneadh phostaichean o %s';
$a->strings['Contact not found'] = 'Cha deach an neach-aithne a lorg';
$a->strings['No installed applications.'] = 'Cha deach aplacaid a stàladh.';
$a->strings['Applications'] = 'Aplacaidean';
$a->strings['Item was not found.'] = 'Cha deach an nì a lorg.';
$a->strings['Please login to continue.'] = 'Clàraich a-steach airson leantainn air adhart.';
$a->strings['You don\'t have access to administration pages.'] = 'Chan eil inntrigeadh agad air duilleagan na rianachd.';
$a->strings['Submanaged account can\'t access the administration pages. Please log back in as the main account.'] = 'Chan urrainn dhan cunntas ’ga fho-stiùireadh duilleagan na rianachd inntrigeadh. Clàraich a-steach leis a’ phrìomh-chunntas.';
$a->strings['Overview'] = 'Foir-shealladh';
$a->strings['Configuration'] = 'Rèiteachadh';
$a->strings['Additional features'] = 'Gleusan a bharrachd';
$a->strings['Database'] = 'Stòr-dàta';
$a->strings['DB updates'] = 'Ùrachaidhean an stòir-dhàta';
$a->strings['Inspect Deferred Workers'] = 'Sgrùd na h-obraichean dàilichte';
$a->strings['Inspect worker Queue'] = 'Sgrùd ciutha nan obraichean';
$a->strings['Diagnostics'] = 'Diagnosachd';
$a->strings['PHP Info'] = 'Fiosrachadh PHP';
$a->strings['probe address'] = 'sgrùd an seòladh';
$a->strings['check webfinger'] = 'thoir sùil air webfinger';
$a->strings['Babel'] = 'Babel';
$a->strings['ActivityPub Conversion'] = 'Iompachadh ActivityPub';
$a->strings['Addon Features'] = 'Gleusan tuilleadain';
$a->strings['User registrations waiting for confirmation'] = 'Clàraichean chleachdaichean a’ feitheamh air dearbhadh';
$a->strings['Too Many Requests'] = 'Cus iarrtasan';
$a->strings['Daily posting limit of %d post reached. The post was rejected.'] = [
	0 => 'Ràinig thu a’ chrìoch de %d phost gach latha. Chaidh am post a dhiùltadh.',
	1 => 'Ràinig thu a’ chrìoch de %d phost gach latha. Chaidh am post a dhiùltadh.',
	2 => 'Ràinig thu a’ chrìoch de %d postaichean gach latha. Chaidh am post a dhiùltadh.',
	3 => 'Ràinig thu a’ chrìoch de %d post gach latha. Chaidh am post a dhiùltadh.',
];
$a->strings['Weekly posting limit of %d post reached. The post was rejected.'] = [
	0 => 'Ràinig thu a’ chrìoch de %d phost gach seachdain. Chaidh am post a dhiùltadh.',
	1 => 'Ràinig thu a’ chrìoch de %d phost gach seachdain. Chaidh am post a dhiùltadh.',
	2 => 'Ràinig thu a’ chrìoch de %d postaichean gach seachdain. Chaidh am post a dhiùltadh.',
	3 => 'Ràinig thu a’ chrìoch de %d post gach seachdain. Chaidh am post a dhiùltadh.',
];
$a->strings['Monthly posting limit of %d post reached. The post was rejected.'] = [
	0 => 'Ràinig thu a’ chrìoch de %d phost gach mìos. Chaidh am post a dhiùltadh.',
	1 => 'Ràinig thu a’ chrìoch de %d phost gach mìos. Chaidh am post a dhiùltadh.',
	2 => 'Ràinig thu a’ chrìoch de %d postaichean gach mìos. Chaidh am post a dhiùltadh.',
	3 => 'Ràinig thu a’ chrìoch de %d post gach mìos. Chaidh am post a dhiùltadh.',
];
$a->strings['Users'] = 'Cleachdaichean';
$a->strings['Tools'] = 'Innealan';
$a->strings['Contact Blocklist'] = 'Liosta-bhacaidh an luchd-aithne';
$a->strings['Server Blocklist'] = 'Liosta-bhacaidh an fhrithealaiche';
$a->strings['Delete Item'] = 'Sguab às an nì';
$a->strings['Item Source'] = 'Tùs an nì';
$a->strings['Profile Details'] = 'Fiosrachadh na pròifil';
$a->strings['Conversations started'] = 'Còmhraidhean air an tòiseachadh';
$a->strings['Only You Can See This'] = 'Chan fhaic ach thu fhèin seo';
$a->strings['Scheduled Posts'] = 'Postaichean air an sgeideal';
$a->strings['Posts that are scheduled for publishing'] = 'Postaichean a tha air an sgeideal airson foillseachadh';
$a->strings['Tips for New Members'] = 'Gliocasan dha na buill ùra';
$a->strings['People Search - %s'] = 'Lorg daoine – %s';
$a->strings['Group Search - %s'] = 'Lorg sna buidhnean – %s';
$a->strings['No matches'] = 'Gun mhaids';
$a->strings['%d result was filtered out because your node blocks the domain it is registered on. You can review the list of domains your node is currently blocking in the <a href="/friendica">About page</a>.'] = [
	0 => 'Chaidh %d toradh a chriathradh air falbh on a tha an nòd agad a’ bacadh na h-àrainne air a bheil e clàraichte. Chì thu liosta nan àrainnean a tha ’gam bacadh leis an nòd agad air <a href="/friendica">an duilleag “Mu dheidhinn”</a>.',
	1 => 'Chaidh %d thoradh a chriathradh air falbh on a tha an nòd agad a’ bacadh na h-àrainne air a bheil iad clàraichte. Chì thu liosta nan àrainnean a tha ’gam bacadh leis an nòd agad air <a href="/friendica">an duilleag “Mu dheidhinn”</a>.',
	2 => 'Chaidh %d toraidhean a chriathradh air falbh on a tha an nòd agad a’ bacadh na h-àrainne air a bheil iad clàraichte. Chì thu liosta nan àrainnean a tha ’gam bacadh leis an nòd agad air <a href="/friendica">an duilleag “Mu dheidhinn”</a>.',
	3 => 'Chaidh %d toradh a chriathradh air falbh on a tha an nòd agad a’ bacadh na h-àrainne air a bheil iad clàraichte. Chì thu liosta nan àrainnean a tha ’gam bacadh leis an nòd agad air <a href="/friendica">an duilleag “Mu dheidhinn”</a>.',
];
$a->strings['Account'] = 'Cunntas';
$a->strings['Two-factor authentication'] = 'Dearbhadh dà-cheumnach';
$a->strings['Display'] = 'Sealladh';
$a->strings['Social Networks'] = 'Lìonraidhean sòisealta';
$a->strings['Manage Accounts'] = 'Stiùir na cunntasan';
$a->strings['Connected apps'] = 'Aplacaidean ceangailte';
$a->strings['Export personal data'] = 'Às-phortaich an dàta pearsanta';
$a->strings['Remove account'] = 'Thoir air falbh an cunntas';
$a->strings['This page is missing a url parameter.'] = 'Tha paramadair URL a dhìth air an duilleag seo.';
$a->strings['The post was created'] = 'Chaidh am post a chruthachadh';
$a->strings['Invalid Request'] = 'Iarrtas mì-dhligheach';
$a->strings['Event id is missing.'] = 'Tha ID an tachartais a dhìth.';
$a->strings['Failed to remove event'] = 'Cha deach leinn an tachartas a thoirt air falbh';
$a->strings['Event can not end before it has started.'] = 'Chan urrainn do thachartas crìochnachadh mus tòisich e.';
$a->strings['Event title and start time are required.'] = 'Tha feum air tiotal is àm tòiseachaidh an tachartais.';
$a->strings['Starting date and Title are required.'] = 'Tha feum air àm tòiseachaidh is tiotal.';
$a->strings['Event Starts:'] = 'Tòisichidh an tachartas:';
$a->strings['Required'] = 'Riatanach';
$a->strings['Finish date/time is not known or not relevant'] = 'Chan eil fhios dè an t-àm crìochnachaidh no chan eil e iomchaidh';
$a->strings['Event Finishes:'] = 'Thig an tachartas gu crìoch:';
$a->strings['Title (BBCode not allowed)'] = 'Tiotal (chan eil BBCode ceadaichte)';
$a->strings['Description (BBCode allowed)'] = 'Tuairisgeul (tha BBCode ceadaichte)';
$a->strings['Location (BBCode not allowed)'] = 'Ionad (chan eil BBCode ceadaichte)';
$a->strings['Share this event'] = 'Co-roinn an tachartas seo';
$a->strings['Basic'] = 'Bunasach';
$a->strings['This calendar format is not supported'] = 'Chan eil taic ri fòrmat a’ mhìosachain seo';
$a->strings['No exportable data found'] = 'Cha deach dàta a ghabhas às-phortadh a lorg';
$a->strings['calendar'] = 'mìosachan';
$a->strings['Events'] = 'Tachartasan';
$a->strings['View'] = 'Seall';
$a->strings['Create New Event'] = 'Cruthaich tachartas ùr';
$a->strings['list'] = 'liosta';
$a->strings['Could not create circle.'] = 'Cha b’ urrainn dhuinn an cearcall a chruthachadh.';
$a->strings['Circle not found.'] = 'Cha deach an cearcall a lorg.';
$a->strings['Circle name was not changed.'] = 'Cha deach ainm a’ chearcaill atharrachadh.';
$a->strings['Unknown circle.'] = 'Cearcall nach aithne dhuinn.';
$a->strings['Contact not found.'] = 'Cha deach an neach-aithne a lorg.';
$a->strings['Invalid contact.'] = 'Neach-aithne mì-dhligheach.';
$a->strings['Contact is deleted.'] = 'Chaidh an neach-aithne a sguabadh às.';
$a->strings['Unable to add the contact to the circle.'] = 'Cha deach leinn an neach-aithne a chur ris a’ chearcall.';
$a->strings['Contact successfully added to circle.'] = 'Chaidh an neach-aithne a chur ris a’ chearcall.';
$a->strings['Unable to remove the contact from the circle.'] = 'Cha deach leinn an neach-aithne a thoirt air falbh on chearcall.';
$a->strings['Contact successfully removed from circle.'] = 'Chaidh an neach-aithne a thoirt air falbh on chearcall.';
$a->strings['Bad request.'] = 'Droch-iarrtas.';
$a->strings['Save Circle'] = 'Sàbhail an cearcall';
$a->strings['Filter'] = 'Criathrag';
$a->strings['Create a circle of contacts/friends.'] = 'Cruthaich cearcall luchd-aithne/charaidean.';
$a->strings['Unable to remove circle.'] = 'Cha deach leinn an cearcall a thoirt air falbh.';
$a->strings['Delete Circle'] = 'Sguab às an cearcall';
$a->strings['Edit Circle Name'] = 'Deasaich ainm a’ chearcaill';
$a->strings['Members'] = 'Buill';
$a->strings['Circle is empty'] = 'Tha an cearcall falamh';
$a->strings['Remove contact from circle'] = 'Thoir air falbh an neach-aithne on chearcall';
$a->strings['Click on a contact to add or remove.'] = 'Briog air neach-aithne gus a chur ris no a thoirt air falbh.';
$a->strings['Add contact to circle'] = 'Cuir an neach-aithne ris a’ chearcall';
$a->strings['%d contact edited.'] = [
	0 => 'Chaidh %d neach-aithne a dheasachadh.',
	1 => 'Chaidh %d luchd-aithne a dheasachadh.',
	2 => 'Chaidh %d luchd-aithne a dheasachadh.',
	3 => 'Chaidh %d luchd-aithne a dheasachadh.',
];
$a->strings['Show all contacts'] = 'Seall an luchd-aithne gu lèir';
$a->strings['Pending'] = 'Ri dhèiligeadh';
$a->strings['Only show pending contacts'] = 'Na seall ach an luchd-aithne ri dhèiligeadh';
$a->strings['Blocked'] = '’Ga bhacadh';
$a->strings['Only show blocked contacts'] = 'Na seall ach an luchd-aithne bacte';
$a->strings['Ignored'] = '’Ga leigeil seachad';
$a->strings['Only show ignored contacts'] = 'Na seall ach an luchd-aithne ’gan leigeil seachad';
$a->strings['Collapsed'] = '’Ga cho-theannachadh';
$a->strings['Only show collapsed contacts'] = 'Na seall ach an luchd-aithne co-theannaichte';
$a->strings['Archived'] = 'San tasg-lann';
$a->strings['Only show archived contacts'] = 'Na seall ach an luchd-aithne san tasg-lann';
$a->strings['Hidden'] = 'Falaichte';
$a->strings['Only show hidden contacts'] = 'Na seall ach an luchd-aithne falaichte';
$a->strings['Organize your contact circles'] = 'Cuir rian air cearcallan an luchd-aithne agad';
$a->strings['Search your contacts'] = 'Lorg san luchd-aithne agad';
$a->strings['Results for: %s'] = 'Toraidhean airson: %s';
$a->strings['Update'] = 'Ùraich';
$a->strings['Unblock'] = 'Dì-bhac';
$a->strings['Unignore'] = 'Na leig seachad tuilleadh';
$a->strings['Uncollapse'] = 'Na co-theannaich tuilleadh';
$a->strings['Batch Actions'] = 'Gnìomhan ’nan grunnan';
$a->strings['Conversations started by this contact'] = 'Na còmhraidhean a thòisich an neach-aithne seo';
$a->strings['Posts and Comments'] = 'Postaichean ’s beachdan';
$a->strings['Individual Posts and Replies'] = 'Postaichean ’s freagairtean fa leth';
$a->strings['Posts containing media objects'] = 'Postaichean sa bheil nithean meadhain';
$a->strings['View all known contacts'] = 'Seall a h-uile neach-aithne as aithne dhut';
$a->strings['Advanced Contact Settings'] = 'Roghainnean adhartach an luchd-aithne';
$a->strings['Mutual Friendship'] = 'Co-dhàimh';
$a->strings['is a fan of yours'] = 'dealasach ort';
$a->strings['you are a fan of'] = 'tha thu dealasach air';
$a->strings['Pending outgoing contact request'] = 'Iarrtas neach-aithne a-mach gun dèiligeadh';
$a->strings['Pending incoming contact request'] = 'Iarrtas neach-aithne a-steach ri dhèiligeadh';
$a->strings['Visit %s\'s profile [%s]'] = 'Tadhail air a’ phròifil aig %s [%s]';
$a->strings['Contact update failed.'] = 'Dh’fhàillig ùrachadh an neach-aithne.';
$a->strings['Return to contact editor'] = 'Air ais gu deasaiche an neach-aithne';
$a->strings['Name'] = 'Ainm';
$a->strings['Account Nickname'] = 'Far-ainm a’ chunntais';
$a->strings['Account URL'] = 'URL a’ chunntais';
$a->strings['Poll/Feed URL'] = 'URL a’ chunntais-bheachd/an inbhir';
$a->strings['New photo from this URL'] = 'Dealbh ùr on URL seo';
$a->strings['No known contacts.'] = 'Chan eil luchd-aithne ann.';
$a->strings['No common contacts.'] = 'Chan eil neach-aithne an cumantas ann.';
$a->strings['Follower (%s)'] = [
	0 => 'Neach-leantainn (%s)',
	1 => 'Luchd-leantainn (%s)',
	2 => 'Luchd-leantainn (%s)',
	3 => 'Luchd-leantainn (%s)',
];
$a->strings['Following (%s)'] = [
	0 => 'A’ leantainn (%s)',
	1 => 'A’ leantainn (%s)',
	2 => 'A’ leantainn (%s)',
	3 => 'A’ leantainn (%s)',
];
$a->strings['Mutual friend (%s)'] = [
	0 => 'Co-dhàimh (%s)',
	1 => 'Co-dhàimhean (%s)',
	2 => 'Co-dhàimhean (%s)',
	3 => 'Co-dhàimhean (%s)',
];
$a->strings['These contacts both follow and are followed by <strong>%s</strong>.'] = 'Tha an luchd-aithne seo an dà chuid a’ leantainn ’s ’gan leantainn le <strong>%s</strong>.';
$a->strings['Common contact (%s)'] = [
	0 => 'Neach-aithne an cumantas (%s)',
	1 => 'Luchd-aithne an cumantas (%s)',
	2 => 'Luchd-aithne an cumantas (%s)',
	3 => 'Luchd-aithne an cumantas (%s)',
];
$a->strings['Both <strong>%s</strong> and yourself have publicly interacted with these contacts (follow, comment or likes on public posts).'] = 'Rinn thu fhèin agus <strong>%s</strong> co-luadar gu poblach leis an luchd-aithne seo (leantainn, beachd air no gur toigh leibh post poblach).';
$a->strings['Contact (%s)'] = [
	0 => 'Neach-aithne (%s)',
	1 => 'Luchd-aithne (%s)',
	2 => 'Luchd-aithne (%s)',
	3 => 'Luchd-aithne (%s)',
];
$a->strings['Access denied.'] = 'Chaidh inntrigeadh a dhiùltadh.';
$a->strings['Submit Request'] = 'Cuir an t-iarrtas a-null';
$a->strings['You already added this contact.'] = 'Chuir thu an neach-aithne seo ris mu thràth.';
$a->strings['The network type couldn\'t be detected. Contact can\'t be added.'] = 'Cha do mhothaich sinn do sheòrsa an lìonraidh. Cha b’ urrainn dhuinn an neach-aithne a chur ris.';
$a->strings['Diaspora support isn\'t enabled. Contact can\'t be added.'] = 'Chan eil taic ri diaspora* an comas. Cha b’ urrainn dhuinn an neach-aithne a chur ris.';
$a->strings['Please answer the following:'] = 'Freagair seo:';
$a->strings['Your Identity Address:'] = 'Seòladh do dhearbh-aithne:';
$a->strings['Profile URL'] = 'URL na pròifile';
$a->strings['Tags:'] = 'Tagaichean:';
$a->strings['%s knows you'] = 'Is aithne dha %s thu';
$a->strings['Add a personal note:'] = 'Cuir nòta pearsanta ris:';
$a->strings['Posts and Replies'] = 'Postaichean ’s freagairtean';
$a->strings['The contact could not be added.'] = 'Cha b’ urrainn dhuinn an neach-aithne a chur ris.';
$a->strings['Invalid request.'] = 'Iarrtas mì-dhligheach.';
$a->strings['No keywords to match. Please add keywords to your profile.'] = 'Chan eil faclan-luirg rim maidseadh ann. Cuir faclan-luirg ris a’ phròifil agad.';
$a->strings['Profile Match'] = 'Maidseadh na pròifile';
$a->strings['Failed to update contact record.'] = 'Cha b’ urrainn dhuinn clàr an neach-aithne ùrachadh.';
$a->strings['Contact has been unblocked'] = 'Chaidh an neach-aithne a dhì-bhacadh';
$a->strings['Contact has been blocked'] = 'Chaidh an neach-aithne a bhacadh';
$a->strings['Contact has been unignored'] = 'Chan eil an neach-aithne ’ga leigeil seachad tuilleadh';
$a->strings['Contact has been ignored'] = 'Tha an neach-aithne ’ga leigeil seachad';
$a->strings['Contact has been uncollapsed'] = 'Chan eil an neach-aithne ’ga cho-theannachadh tuilleadh';
$a->strings['Contact has been collapsed'] = 'Tha an neach-aithne ’ga cho-theannachadh';
$a->strings['You are mutual friends with %s'] = 'Tha co-dhàimh eadar thu fhèin is %s a-nis';
$a->strings['You are sharing with %s'] = 'Tha thu a’ co-roinneadh le %s';
$a->strings['%s is sharing with you'] = 'Tha %s a’ co-roinneadh leat';
$a->strings['Private communications are not available for this contact.'] = 'Chan eil conaltradh prìobhaideach ri fhaighinn dhan neach-aithne seo.';
$a->strings['Never'] = 'Chan ann idir';
$a->strings['(Update was not successful)'] = '(Cha deach leis an ùrachadh)';
$a->strings['(Update was successful)'] = '(Chaidh leis an ùrachadh)';
$a->strings['Suggest friends'] = 'Mol caraidean';
$a->strings['Network type: %s'] = 'Seòrsa an lìonraidh: %s';
$a->strings['Communications lost with this contact!'] = 'Chaidh an conaltradh leis an neach-aithne seo a chall!';
$a->strings['Fetch further information for feeds'] = 'Faigh barrachd fiosrachaidh dha na h-inbhirean';
$a->strings['Fetch information like preview pictures, title and teaser from the feed item. You can activate this if the feed doesn\'t contain much text. Keywords are taken from the meta header in the feed item and are posted as hash tags.'] = 'Faigh fiosrachadh mar dhealbhan ro-sheallaidh, tiotal is tàladh o nì an inbhir. ’S urrainn dhut seo a chur an comas mur eil cus teacsa san inbhir. Thèid faclan-luirg a thogail o bhann-cinn nì an inbhir agus am postadh ’nan tagaichean hais.';
$a->strings['Fetch information'] = 'Faigh am fiosrachadh';
$a->strings['Fetch keywords'] = 'Faigh na faclan-luirg';
$a->strings['Fetch information and keywords'] = 'Faigh am fiosrachadh ’s na faclan-luirg';
$a->strings['No mirroring'] = 'Gun sgàthanachadh';
$a->strings['Mirror as my own posting'] = 'Sgàthanaich ’na phost leam fhìn';
$a->strings['Native reshare'] = 'Co-roinneadh tùsail';
$a->strings['Contact Information / Notes'] = 'Fiosrachadh an neach-aithne / Nòtaichean';
$a->strings['Contact Settings'] = 'Roghainnean an neach-aithne';
$a->strings['Contact'] = 'Neach-aithne';
$a->strings['Their personal note'] = 'An nòta pearsanta aca';
$a->strings['Edit contact notes'] = 'Deasaich notaichean an neach-aithne';
$a->strings['Block/Unblock contact'] = 'Bac/Dì-bhac an neach-aithne';
$a->strings['Ignore contact'] = 'Leig seachad an neach-aithne';
$a->strings['View conversations'] = 'Seall na còmhraidhean';
$a->strings['Last update:'] = 'An t-ùrachadh mu dheireadh:';
$a->strings['Update public posts'] = 'Ùraich na postaichean poblach';
$a->strings['Update now'] = 'Ùraich an-dràsta';
$a->strings['Awaiting connection acknowledge'] = 'A’ feitheamh air aithneachadh a’ cheangail';
$a->strings['Currently blocked'] = '’Ga bhacadh an-dràsta';
$a->strings['Currently ignored'] = '’Ga leigeil seachad an-dràsta';
$a->strings['Currently collapsed'] = '’Ga cho-theannachadh an-dràsta';
$a->strings['Currently archived'] = 'San tasg-lann an-dràsta';
$a->strings['Hide this contact from others'] = 'Falaich an neach-aithne seo o chàch';
$a->strings['Replies/likes to your public posts <strong>may</strong> still be visible'] = '<strong>Dh’fhaoidte</strong> gum faicear freagairtean/gur toigh le daoine na postaichean poblach agad fhathast';
$a->strings['Notification for new posts'] = 'Brathan air postaichean ùra';
$a->strings['Send a notification of every new post of this contact'] = 'Cuir brath airson gach post ùr aig an neach-aithne seo';
$a->strings['Keyword Deny List'] = 'Liosta-dhiùltaidh nam facal-luirg';
$a->strings['Comma separated list of keywords that should not be converted to hashtags, when "Fetch information and keywords" is selected'] = 'Liosta sgaraichte le cromagan de dh’fhaclan-luirg nach dèid iompachadh gu tagaichean tais nuair a bhios “Faigh am fiosrachadh ’s na faclan-luirg” air a thaghadh';
$a->strings['Actions'] = 'Gnìomhan';
$a->strings['Status'] = 'Staid';
$a->strings['Mirror postings from this contact'] = 'Sgàthanaich na postaichean on neach-aithne seo';
$a->strings['Mark this contact as remote_self, this will cause friendica to repost new entries from this contact.'] = 'Cuir comharra remote_self ris an neach-aithne seo ach an ath-phostaich Friendica nithean ùra on neach-aithne seo.';
$a->strings['Refetch contact data'] = 'Faigh dàta an neach-aithne a-rithist';
$a->strings['Toggle Blocked status'] = 'Toglaich stad a’ bhacaidh';
$a->strings['Toggle Ignored status'] = 'Toglaich staid na leigeil seachad';
$a->strings['Toggle Collapsed status'] = 'Toglaich staid a’ cho-theannachaidh';
$a->strings['Revoke Follow'] = 'Cùl-ghairm an leantainn';
$a->strings['Revoke the follow from this contact'] = 'Thoir air an neach-aithne seo nach lean iad ort tuilleadh';
$a->strings['Bad Request.'] = 'Droch-iarrtas.';
$a->strings['Contact is being deleted.'] = 'Tha an neach-aithne ’ga sguabadh às.';
$a->strings['Follow was successfully revoked.'] = 'Chaidh an leantainn a chùl-ghairm.';
$a->strings['Do you really want to revoke this contact\'s follow? This cannot be undone and they will have to manually follow you back again.'] = 'A bheil thu cinnteach nach eil thu ag iarraidh gun lean an neach-aithne seo ort tuilleadh? Cha ghabh seo a neo-dhèanamh is feumaidh iad leantainn ort a làimh às ùr.';
$a->strings['No suggestions available. If this is a new site, please try again in 24 hours.'] = 'Chan eil moladh sam bith ann. Mas e làrach ùr a th’ ann, feuch ris a-rithist an ceann 24 uair a thìde.';
$a->strings['You aren\'t following this contact.'] = 'Chan eil thu a’ leantainn air an neach-aithne seo.';
$a->strings['Unfollowing is currently not supported by your network.'] = 'Cha chuir an lìonra agad taic ri sgur de leantainn air an àm seo.';
$a->strings['Disconnect/Unfollow'] = 'Dì-cheangail/Na lean tuilleadh';
$a->strings['Contact was successfully unfollowed'] = 'Chan eil thu a’ leantainn air an neach-aithne tuilleadh';
$a->strings['Unable to unfollow this contact, please contact your administrator'] = 'Cha deach leinn an neach-aithne a thoirt air falbh on fheadhainn air a leanas tu, cuir fios gun rianaire agad';
$a->strings['No results.'] = 'Chan eil toradh ann.';
$a->strings['This community stream shows all public posts received by this node. They may not reflect the opinions of this node’s users.'] = 'Seallaidh sruthadh na coimhearsnachd gach post poblach a fhuair an nòd seo. Dh’fhaoidte nach eil iad a’ riochdachadh beachdan luchd-cleachdaidh an nòid seo.';
$a->strings['Community option not available.'] = 'Chan eil roghainn na coimhearsnachd ri fhaighinn.';
$a->strings['Not available.'] = 'Chan eil seo ri fhaighinn.';
$a->strings['No such circle'] = 'Chan eil an cearcall seo ann';
$a->strings['Circle: %s'] = 'Cearcall: %s';
$a->strings['Include'] = 'Gabh a-staigh';
$a->strings['Hide'] = 'Falaich';
$a->strings['Credits'] = 'Urram';
$a->strings['Friendica is a community project, that would not be possible without the help of many people. Here is a list of those who have contributed to the code or the translation of Friendica. Thank you all!'] = '’S e pròiseact coimhearsnachd a th’ ann am Friendica is cha ghabhadh a thoirt gu buil às aonais taic o iomadh daoine. Seo liosta dhen fheadhainn a chuir ri chòd no ri eadar-theangachadh Friendica. Mòran taing dhuibh uile!';
$a->strings['Formatted'] = 'Fòrmataichte';
$a->strings['Activity'] = 'Gnìomhachd';
$a->strings['Object data'] = 'Dàta oibseict';
$a->strings['Result Item'] = 'Nì toraidh';
$a->strings['Error'] = [
	0 => 'Mearachd',
	1 => 'Mearachdan',
	2 => 'Mearachdan',
	3 => 'Mearachdan',
];
$a->strings['Source activity'] = 'Gnìomhachd bun-tùis';
$a->strings['Source input'] = 'Ion-chur bun-tùis';
$a->strings['BBCode::toPlaintext'] = 'BBCode::toPlaintext';
$a->strings['BBCode::convert (raw HTML)'] = 'BBCode::convert (HTML amh)';
$a->strings['BBCode::convert (hex)'] = 'BBCode::convert (sia-dheicheach)';
$a->strings['BBCode::convert'] = 'BBCode::convert';
$a->strings['BBCode::convert => HTML::toBBCode'] = 'BBCode::convert => HTML::toBBCode';
$a->strings['BBCode::toMarkdown'] = 'BBCode::toMarkdown';
$a->strings['BBCode::toMarkdown => Markdown::convert (raw HTML)'] = 'BBCode::toMarkdown => Markdown::convert (HTML amh)';
$a->strings['BBCode::toMarkdown => Markdown::convert'] = 'BBCode::toMarkdown => Markdown::convert';
$a->strings['BBCode::toMarkdown => Markdown::toBBCode'] = 'BBCode::toMarkdown => Markdown::toBBCode';
$a->strings['BBCode::toMarkdown =>  Markdown::convert => HTML::toBBCode'] = 'BBCode::toMarkdown =>  Markdown::convert => HTML::toBBCode';
$a->strings['Item Body'] = 'Bodhaig nì';
$a->strings['Item Tags'] = 'Tagaichean nì';
$a->strings['PageInfo::appendToBody'] = 'PageInfo::appendToBody';
$a->strings['PageInfo::appendToBody => BBCode::convert (raw HTML)'] = 'PageInfo::appendToBody => BBCode::convert (HTML amh)';
$a->strings['PageInfo::appendToBody => BBCode::convert'] = 'PageInfo::appendToBody => BBCode::convert';
$a->strings['Source input (Diaspora format)'] = 'Ion-chur bun-tùis (fòrmat diaspora*)';
$a->strings['Source input (Markdown)'] = 'Ion-chur bun-tùis (Markdown)';
$a->strings['Markdown::convert (raw HTML)'] = 'Markdown::convert (HTML amh)';
$a->strings['Markdown::convert'] = 'Markdown::convert';
$a->strings['Markdown::toBBCode'] = 'Markdown::toBBCode';
$a->strings['Raw HTML input'] = 'Ion-chur HTML amh';
$a->strings['HTML Input'] = 'Ion-chur HTML';
$a->strings['HTML Purified (raw)'] = 'HTML air a ghlanadh (amh)';
$a->strings['HTML Purified (hex)'] = 'HTML air a ghlanadh (sia-dheicheach)';
$a->strings['HTML Purified'] = 'HTML air a ghlanadh';
$a->strings['HTML::toBBCode'] = 'HTML::toBBCode';
$a->strings['HTML::toBBCode => BBCode::convert'] = 'HTML::toBBCode => BBCode::convert';
$a->strings['HTML::toBBCode => BBCode::convert (raw HTML)'] = 'HTML::toBBCode => BBCode::convert (HTML amh)';
$a->strings['HTML::toBBCode => BBCode::toPlaintext'] = 'HTML::toBBCode => BBCode::toPlaintext';
$a->strings['HTML::toMarkdown'] = 'HTML::toMarkdown';
$a->strings['HTML::toPlaintext'] = 'HTML::toPlaintext';
$a->strings['HTML::toPlaintext (compact)'] = 'HTML::toPlaintext (dùmhlaichte)';
$a->strings['Decoded post'] = 'Post air a dhì-chòdachadh';
$a->strings['Post array before expand entities'] = 'Arraigh a’ phuist ro leudachadh nan eintiteasan';
$a->strings['Babel Diagnostic'] = 'Diagnosachd Babel';
$a->strings['Source text'] = 'Teacsa tùsail';
$a->strings['BBCode'] = 'BBCode';
$a->strings['Markdown'] = 'Markdown';
$a->strings['HTML'] = 'HTML';
$a->strings['Twitter Source / Tweet URL (requires API key)'] = 'Bun-tùs Twitter / URL a’ tweet (feum air iuchair API)';
$a->strings['You must be logged in to use this module'] = 'Feumaidh tu clàradh a-steach mus urrainn dhut am mòideal seo a chleachdadh';
$a->strings['Source URL'] = 'URL an tùis';
$a->strings['Time Conversion'] = 'Iompachadh na h-ama';
$a->strings['Friendica provides this service for sharing events with other networks and friends in unknown timezones.'] = 'Tha Friendica a’ solar na seirbheise seo airson tachartasan a cho-roinneadh le lìonraidhean eile ’s le caraidean mur eil fios dè an roinnean-tìde.';
$a->strings['UTC time: %s'] = 'Àm UTC: %s';
$a->strings['Current timezone: %s'] = 'An roinn-tìde làithreach: %s';
$a->strings['Converted localtime: %s'] = 'An t-àm ionadail iompaichte: %s';
$a->strings['Please select your timezone:'] = 'Tagh an roinn-tìde agad:';
$a->strings['Only logged in users are permitted to perform a probing.'] = 'Chan fhaod ach cleachdaichean air an clàradh a-steach sgrùdadh a dhèanamh.';
$a->strings['Probe Diagnostic'] = 'Diagnosachd sgrùdaidh';
$a->strings['Output'] = 'Às-chur';
$a->strings['Lookup address'] = 'Rannsaich an seòladh';
$a->strings['Webfinger Diagnostic'] = 'Diagnosachd Webfinger';
$a->strings['Lookup address:'] = 'Rannsaich an seòladh:';
$a->strings['No entries (some entries may be hidden).'] = 'Gun innteart (’s dòcha gu bheil cuid a dh’innteartan falaichte).';
$a->strings['Find on this site'] = 'Lorg air an làrach seo';
$a->strings['Results for:'] = 'Toraidhean airson:';
$a->strings['Site Directory'] = 'Eòlaire na làraich';
$a->strings['Item was not deleted'] = 'Cha deach an nì a sguabadh às';
$a->strings['Item was not removed'] = 'Cha deach nì a thoirt air falbh';
$a->strings['- select -'] = '– tagh –';
$a->strings['Suggested contact not found.'] = 'Cha deach an neach-aithne molta a lorg.';
$a->strings['Friend suggestion sent.'] = 'Chaidh moladh caraid a chur.';
$a->strings['Suggest Friends'] = 'Mol caraidean';
$a->strings['Suggest a friend for %s'] = 'Mol caraid dha %s';
$a->strings['Installed addons/apps:'] = 'Aplacaidean/tuilleadain stàlaichte:';
$a->strings['No installed addons/apps'] = 'Cha deach aplacaid/tuilleadan a stàladh';
$a->strings['Read about the <a href="%1$s/tos">Terms of Service</a> of this node.'] = 'Leugh <a href="%1$s/tos">teirmichean seirbheise</a> an nòd seo.';
$a->strings['On this server the following remote servers are blocked.'] = 'Seo a frithealaichean cèine a tha ’gam bacadh leis an fhrithealaiche seo.';
$a->strings['Reason for the block'] = 'Adhbhar a’ bhacaidh';
$a->strings['Download this list in CSV format'] = 'Luchdaich a-nuas an liosta seo san fhòrmat CSV';
$a->strings['This is Friendica, version %s that is running at the web location %s. The database version is %s, the post update version is %s.'] = 'Seo Friendica tionndadh %s a tha a’ ruith air an ionad-lìn %s. Is %s tionndadh an stòir-dhàta agus %s tionndadh ùrachadh nam post.';
$a->strings['Please visit <a href="https://friendi.ca">Friendi.ca</a> to learn more about the Friendica project.'] = 'Tadhail air <a href="https://friendi.ca">Friendi.ca</a> airson barrachd fiosrachaidh mu phròiseact Friendica.';
$a->strings['Bug reports and issues: please visit'] = 'Aithrisean air bugaichean is duilgheadasan: tadhail air';
$a->strings['the bugtracker at github'] = 'tracaiche nam bugaichean air GitHub';
$a->strings['Suggestions, praise, etc. - please email "info" at "friendi - dot - ca'] = 'Airson beachdan, molaidhean is mssa. – cuir post-d gu “info” aig “friendi – dot – ca';
$a->strings['No profile'] = 'Chan eil pròifil ann';
$a->strings['Method Not Allowed.'] = 'Chan eil am modh ceadaichte.';
$a->strings['Help:'] = 'Cobhair:';
$a->strings['Welcome to %s'] = 'Fàilte gu %s';
$a->strings['Friendica Communications Server - Setup'] = 'Frithealaiche conaltradh Friendica – Suidheachadh';
$a->strings['System check'] = 'Dearbhadh an t-siostaim';
$a->strings['Requirement not satisfied'] = 'Tha riatanas nach deach a choileanadh';
$a->strings['Optional requirement not satisfied'] = 'Tha riatanas roghainneil nach deach a choileanadh';
$a->strings['OK'] = 'Ceart ma-thà';
$a->strings['Next'] = 'Air adhart';
$a->strings['Check again'] = 'Sgrùd a-rithist';
$a->strings['Base settings'] = 'Roghainnean bunasach';
$a->strings['Base path to installation'] = 'An t-slighe bhunasach dhan stàladh';
$a->strings['If the system cannot detect the correct path to your installation, enter the correct path here. This setting should only be set if you are using a restricted system and symbolic links to your webroot.'] = 'Mur aithnich an siostam an t-slighe cheart dhan stàladh agad, cuir a-steach an t-slighe cheart an-seo. Cha bu chòir dhut seo a shuidheachadh ach ma tha thu a’ cleachdadh siostam cuingichte agus ceanglaichean samhlachail gun fheumh-lìn agad.';
$a->strings['The Friendica system URL'] = 'URL siostam Friendica';
$a->strings['Overwrite this field in case the system URL determination isn\'t right, otherwise leave it as is.'] = 'Tar-sgrìobh an raon seo mura deach URL an t-siostaim aithneachadh mar bu chòir. Ma chaidh, fàg e mar a tha e.';
$a->strings['Database connection'] = 'Ceangal stòir-dhàta';
$a->strings['In order to install Friendica we need to know how to connect to your database.'] = 'Airson Friendica a stàladh, feumaidh fios a bhith againn air mar a nì sinn ceangal dhan stòr-dàta agad.';
$a->strings['Please contact your hosting provider or site administrator if you have questions about these settings.'] = 'Cuir fios gu solaraiche an òstaidh no rianaire na làraich agad ma tha ceist agad mu na roghainnean seo.';
$a->strings['The database you specify below should already exist. If it does not, please create it before continuing.'] = 'Bu chòir dhan stòr-dàta a shònraicheas tu a bhith ann mu thràth. Mur eil, cruthaich e mus lean thu air adhart.';
$a->strings['Database Server Name'] = 'Ainm frithealaiche an stòir-dhàta';
$a->strings['Database Login Name'] = 'Ainm clàraidh a-steach an stòir-dhàta';
$a->strings['Database Login Password'] = 'Facal-faire clàradh a-steach an stòir-dhàta';
$a->strings['For security reasons the password must not be empty'] = 'Air adhbharan tèarainteachd, chan fhaod am facal-faire a bhith falamh';
$a->strings['Database Name'] = 'Ainm an stòir-dhàta';
$a->strings['Please select a default timezone for your website'] = 'Tagh roinn-tìde bhunaiteach dhan làrach-lìn agad';
$a->strings['Site settings'] = 'Roghainnean na làraich';
$a->strings['Site administrator email address'] = 'An seòladh puist-d aig rianaire na làraich';
$a->strings['Your account email address must match this in order to use the web admin panel.'] = 'Feumaidh seòladh puist-d a’ chunntais agad a bhith co-ionnan ri seo ach an urrainn dhut panail-lìn na rianachd a chleachdadh.';
$a->strings['System Language:'] = 'Cànan an t-siostaim:';
$a->strings['Set the default language for your Friendica installation interface and to send emails.'] = 'Suidhich an cànan bunaiteach dhan eadar-aghaidh stàladh Friendica agad is do na puist-d a thèid a chur.';
$a->strings['Your Friendica site database has been installed.'] = 'Chaidh stòr-dàta na làraich Friendica agad a stàladh.';
$a->strings['Installation finished'] = 'Tha an stàladh deiseil';
$a->strings['<h1>What next</h1>'] = '<h1>Dè a-nis?</h1>';
$a->strings['IMPORTANT: You will need to [manually] setup a scheduled task for the worker.'] = 'CUDROMACH: Feumaidh gu saothair dhan obraiche a chur air an sgeideal [a làimh].';
$a->strings['Go to your new Friendica node <a href="%s/register">registration page</a> and register as new user. Remember to use the same email you have entered as administrator email. This will allow you to enter the site admin panel.'] = 'Tadhail air <a href="%s/register">duilleag a’ chlàraidh</a> aig an nòd Friendica ùr agad agus clàraich mar cleachdaiche ùr. Thoir an aire gun cleachd thu an aon seòladh puist-d ’s a chuir thu a-steach mar phost-d an rianaire. Bheir seo inntrigeadh do phanail na rianachd dhut.';
$a->strings['Total invitation limit exceeded.'] = 'Chaidh thu thairis air crìoch nan cuiridhean iomlan.';
$a->strings['%s : Not a valid email address.'] = '%s : Chan e seòladh puist-d dligheach a tha seo.';
$a->strings['Please join us on Friendica'] = 'Thig cuide rinn air Friendica';
$a->strings['Invitation limit exceeded. Please contact your site administrator.'] = 'Chaidh thu thairis air crìoch nan cuiridhean. Cuir fios gu rianaire na làraich agad.';
$a->strings['%s : Message delivery failed.'] = '%s : Dh’fhàillig libhrigeadh na teachdaireachd.';
$a->strings['%d message sent.'] = [
	0 => 'Chaidh %d teachdaireachd a chur.',
	1 => 'Chaidh %d theachdaireachd a chur.',
	2 => 'Chaidh %d teachdaireachdan a chur.',
	3 => 'Chaidh %d teachdaireachd a chur.',
];
$a->strings['You have no more invitations available'] = 'Chan eil barrachd cuiridhean ri fhaighinn dhut';
$a->strings['Visit %s for a list of public sites that you can join. Friendica members on other sites can all connect with each other, as well as with members of many other social networks.'] = 'Tadhail air %s airson liosta de làraichean poblach far an urrainn dhut ballrachd fhaighinn. ’S urrainn dhan a h-uile ball Friendica air làraichean eile ceangal ri chèile agus ri buill iomadh lìonra sòisealta eile.';
$a->strings['To accept this invitation, please visit and register at %s or any other public Friendica website.'] = 'Airson gabhail ris a’ chuireadh seo, tadhail air %s is clàraich air no air làrach-lìn Friendica poblach sam bith eile.';
$a->strings['Friendica sites all inter-connect to create a huge privacy-enhanced social web that is owned and controlled by its members. They can also connect with many traditional social networks. See %s for a list of alternate Friendica sites you can join.'] = 'Tha na làraichean Friendica uile co-naisgte ri chèile ach an cruthaich iad lìon sòisealta mòr aig a bheil prìobhaideachd phisichte ’s a tha fo smachd nam ball aige fhèin. ’S urrainn dhaibh ceangal a dhèanamh ri iomadh lìonra sòisealta tradaiseanta. Faic %s airson liosta de làraichean Friendica eile far an urrainn dhut ballrachd fhaighinn.';
$a->strings['Our apologies. This system is not currently configured to connect with other public sites or invite members.'] = 'Tha sinn duilich. Cha deach an siostam rèiteachadh aig an àm seo airson ceangal ri làraichean poblach eile no cuiridhean ballrachd a chur.';
$a->strings['Friendica sites all inter-connect to create a huge privacy-enhanced social web that is owned and controlled by its members. They can also connect with many traditional social networks.'] = 'Tha na làraichean Friendica uile co-naisgte ri chèile ach an cruthaich iad lìon sòisealta mòr aig a bheil prìobhaideachd phisichte ’s a tha fo smachd nam ball aige fhèin. ’S urrainn dhaibh ceangal a dhèanamh ri iomadh lìonra sòisealta tradaiseanta.';
$a->strings['To accept this invitation, please visit and register at %s.'] = 'Airson gabhail ris a’ chuireadh seo, tadhail air %s is clàraich ann.';
$a->strings['Send invitations'] = 'Cuir cuiridhean';
$a->strings['Enter email addresses, one per line:'] = 'Cuir seòlaidhean puist-d a-steach, gach fear air loidhne fa leth:';
$a->strings['You are cordially invited to join me and other close friends on Friendica - and help us to create a better social web.'] = 'Tha fàilte chridheil romhad airson tighinn cruinn còmhla rium-sa is dlùth-charaidean eile air Friendica – agus airson ar cuideachadh ach an cruthaich sinn lìon sòisealta nas fheàrr.';
$a->strings['You will need to supply this invitation code: $invite_code'] = 'Bidh agad ris an còd cuiridh seo a sholar: $invite_code';
$a->strings['Once you have registered, please connect with me via my profile page at:'] = 'Nuair a bhios tu air do chlàradh, dèan ceangal rium le duilleag na pròifil agam air:';
$a->strings['For more information about the Friendica project and why we feel it is important, please visit http://friendi.ca'] = 'Airson barrachd fiosrachaidh mu phròiseact Friendica ’s carson a tha sinn dhen bheachd gu bheil e cudromach, tadhail air http://friendi.ca';
$a->strings['Please enter a post body.'] = 'Cuir a-steach bodhaig puist.';
$a->strings['This feature is only available with the frio theme.'] = 'Chan eil an gleus seo ri fhaighinn ach leis an ùrlar frio.';
$a->strings['Compose new personal note'] = 'Sgrìobh nòta pearsanta ùr';
$a->strings['Compose new post'] = 'Sgrìobh post ùr';
$a->strings['Visibility'] = 'Faicsinneachd';
$a->strings['Clear the location'] = 'Thoir an t-ionad air falbh';
$a->strings['Location services are unavailable on your device'] = 'Chan eil seirbheisean ionaid ri fhaighinn air an uidheam agad';
$a->strings['Location services are disabled. Please check the website\'s permissions on your device'] = 'Tha seirbheisean ionaid à comas. Thoir sùil air ceadan na làraich-lìn air an uidheam agad';
$a->strings['You can make this page always open when you use the New Post button in the <a href="/settings/display">Theme Customization settings</a>.'] = '’S urrainn dhut suidheachadh gum fosgail an duilleag seo an-còmhnaidh nuair a chleachdas tu am putan “Post ùr” ann an <a href="/settings/display">Roghainnean gnàthaichte an ùrlair</a>.';
$a->strings['The feed for this item is unavailable.'] = 'Chan eil inbhir ri fhaighinn dhan nì seo.';
$a->strings['Unable to follow this item.'] = 'Cha ghabh leantainn air an nì seo.';
$a->strings['System down for maintenance'] = 'Tha an siostam dheth a chùm obrach-glèidhidh';
$a->strings['This Friendica node is currently in maintenance mode, either automatically because it is self-updating or manually by the node administrator. This condition should be temporary, please come back in a few minutes.'] = 'Chaidh an nòd Friendica seo a chur sa mhodh obrach-glèidhidh, gu fèin-obrachail on a tha e ’ga ùrachadh fhèin no a làimh le rianaire an nòid. Cha bu chòir dhan staid seo a bhith air ach rè seal, till an ceann corra mionaid.';
$a->strings['A Decentralized Social Network'] = 'Lìonra sòisealta sgaoilte';
$a->strings['You need to be logged in to access this page.'] = 'Feumaidh tu clàradh a-steach mus fhaigh thu cothrom air an duilleag seo.';
$a->strings['Files'] = 'Faidhlichean';
$a->strings['Upload'] = 'Luchdaich suas';
$a->strings['Sorry, maybe your upload is bigger than the PHP configuration allows'] = 'Tha sinn duilich a dh’fhaoidte gu bheil an luchdadh suas agad nas motha na tha ceadaichte leis an rèiteachadh PHP';
$a->strings['Or - did you try to upload an empty file?'] = 'Air neo – an do dh’fheuch thu ri faidhle falamh a luchdadh suas?';
$a->strings['File exceeds size limit of %s'] = 'Tha am faidhle nas motha na tha ceadaichte dhe %s';
$a->strings['File upload failed.'] = 'Dh’fhàillig luchdadh suas an fhaidhle.';
$a->strings['Unable to process image.'] = 'Cha b’ urrainn dhuinn an dealbh a phròiseasadh.';
$a->strings['Image upload failed.'] = 'Dh’fhàillig le luchdadh suas an deilbh.';
$a->strings['List of all users'] = 'Liosta nan cleachdaichean uile';
$a->strings['Active'] = 'Gnìomhach';
$a->strings['List of active accounts'] = 'Liosta nan cunntasan gnìomhach';
$a->strings['List of pending registrations'] = 'Liosta nan clàraidhean rin dèiligeadh';
$a->strings['List of blocked users'] = 'Liosta nan cleachdaichean a chaidh a bhacadh';
$a->strings['Deleted'] = 'Air a sguabadh às';
$a->strings['List of pending user deletions'] = 'Liosta nan cleachdaichean rin sguabadh às';
$a->strings['Normal Account Page'] = 'Duilleag àbhaisteach a’ chunntais';
$a->strings['Soapbox Page'] = 'Duilleag cùbaid deasbaid';
$a->strings['Public Group'] = 'Buidheann poblach';
$a->strings['Automatic Friend Page'] = 'Duilleag caraide fhèin-obrachail';
$a->strings['Private Group'] = 'Buidheann prìobhaideach';
$a->strings['Personal Page'] = 'Duilleag phearsanta';
$a->strings['Organisation Page'] = 'Duilleag buidhinn';
$a->strings['News Page'] = 'Duilleag naidheachdan';
$a->strings['Community Group'] = 'Buidheann coimhearsnachd';
$a->strings['You can\'t block a local contact, please block the user instead'] = 'Chan urrainn dhut neach-aithne ionadail a bhacadh, bac an cleachdaiche ’na àite';
$a->strings['%s contact unblocked'] = [
	0 => 'Chaidh %s neach-aithne a dhì-bhacadh',
	1 => 'Chaidh %s luchd-aithne a dhì-bhacadh',
	2 => 'Chaidh %s luchd-aithne a dhì-bhacadh',
	3 => 'Chaidh %s luchd-aithne a dhì-bhacadh',
];
$a->strings['Remote Contact Blocklist'] = 'Liosta bacadh luchd-aithne cèin';
$a->strings['This page allows you to prevent any message from a remote contact to reach your node.'] = 'Leigidh an duilleag seo leat gum bac thu teachdaireachd sam bith o neach-aithne cèin o ruigsinn an nòid agad.';
$a->strings['Block Remote Contact'] = 'Bac an neach-aithne cèin';
$a->strings['select all'] = 'tagh a h-uile';
$a->strings['select none'] = 'na tagh gin';
$a->strings['No remote contact is blocked from this node.'] = 'Cha deach neach-aithne cèin a bhacadh on nòd seo.';
$a->strings['Blocked Remote Contacts'] = 'Luchd-aithne cèin air am bacadh';
$a->strings['Block New Remote Contact'] = 'Bac neach-aithne cèin ùr';
$a->strings['Photo'] = 'Dealbh';
$a->strings['Reason'] = 'Adhbhar';
$a->strings['%s total blocked contact'] = [
	0 => 'Chaidh %s neach-aithne a bhacadh gu h-iomlan',
	1 => 'Chaidh %s luchd-aithne a bhacadh gu h-iomlan',
	2 => 'Chaidh %s luchd-aithne a bhacadh gu h-iomlan',
	3 => 'Chaidh %s luchd-aithne a bhacadh gu h-iomlan',
];
$a->strings['URL of the remote contact to block.'] = 'URL an neach-aithne chèin ri bhacadh.';
$a->strings['Also purge contact'] = 'Purgaidich an neach-aithne cuideachd';
$a->strings['Removes all content related to this contact from the node. Keeps the contact record. This action cannot be undone.'] = 'Bheir seo air falbh susbaint sam bith a tha co-cheangailte ris an neach-aithne seo on nòd. Cumaidh seo clàr an neach-aithne. Cha ghabh an gnìomh seo a neo-dhèanamh.';
$a->strings['Block Reason'] = 'Adhbhar a’ bhacaidh';
$a->strings['Server domain pattern added to the blocklist.'] = 'Chaidh pàtran àrainn fhrithealaichean a chur ris an liosta-bhacaidh.';
$a->strings['%s server scheduled to be purged.'] = [
	0 => 'Chaidh %s fhrithealaiche a chur air sgeideal a’ phurgaideachaidh.',
	1 => 'Chaidh %s fhrithealaiche a chur air sgeideal a’ phurgaideachaidh.',
	2 => 'Chaidh %s frithealaichean a chur air sgeideal a’ phurgaideachaidh.',
	3 => 'Chaidh %s frithealaiche a chur air sgeideal a’ phurgaideachaidh.',
];
$a->strings['← Return to the list'] = '← Air ais dhan liosta';
$a->strings['Block A New Server Domain Pattern'] = 'Bac pàtran àrainne fhrithealaichean ùr';
$a->strings['<p>The server domain pattern syntax is case-insensitive shell wildcard, comprising the following special characters:</p>
<ul>
	<li><code>*</code>: Any number of characters</li>
	<li><code>?</code>: Any single character</li>
</ul>'] = '<p>Chan eil aire air litrichean mòra is beaga aig pàtran àrainne fhrithealaichean is tha e ’na shaorag slige leis na caractaran sònraichte seo:</p>
<ul>
	<li><code>*</code>: Uiread sam bith de charactaran</li>
	<li><code>?</code>: Aon charactar</li>
</ul>';
$a->strings['Check pattern'] = 'Thoir sùil air a’ phàtran';
$a->strings['Matching known servers'] = 'A’ maidseadh nam frithealaichean as aithne dhuinn';
$a->strings['Server Name'] = 'Ainm an fhrithealaiche';
$a->strings['Server Domain'] = 'Àrainn an fhrithealaiche';
$a->strings['Known Contacts'] = 'An luchd-aithne as aithne dhuinn';
$a->strings['%d known server'] = [
	0 => '%d fhrithealaiche as aithne dhuinn',
	1 => '%d fhrithealaiche as aithne dhuinn',
	2 => '%d frithealaichean as aithne dhuinn',
	3 => '%d frithealaiche as aithne dhuinn',
];
$a->strings['Add pattern to the blocklist'] = 'Cuir am pàtran ris an liosta-bhacaidh';
$a->strings['Server Domain Pattern'] = 'Pàtran àrainne fhrithealaichean';
$a->strings['The domain pattern of the new server to add to the blocklist. Do not include the protocol.'] = 'Am pàtran àrainne fhrithealaichean ùr airson a chur ris an liosta-bhacaidh. Na gabh a-staigh am pròtacal.';
$a->strings['Purge server'] = 'Purgaidich am frithealaiche';
$a->strings['Also purges all the locally stored content authored by the known contacts registered on that server. Keeps the contacts and the server records. This action cannot be undone.'] = [
	0 => 'Nì seo purgaideachadh air gach susbaint cuideachd a chaidh a stòradh gu h-ionadail ’s a chaidh a sgrìobhadh leis an luchd-aithne as aithne dhuinn a tha clàraichte air an fhrithealaiche ud. Cumaidh seo luchd-aithne is clàran an fhrithealaiche. Cha ghabh seo a neo-dhèanamh.',
	1 => 'Nì seo purgaideachadh air gach susbaint cuideachd a chaidh a stòradh gu h-ionadail ’s a chaidh a sgrìobhadh leis an luchd-aithne as aithne dhuinn a tha clàraichte air na frithealaichean ud. Cumaidh seo luchd-aithne is clàran nam frithealaichean. Cha ghabh seo a neo-dhèanamh.',
	2 => 'Nì seo purgaideachadh air gach susbaint cuideachd a chaidh a stòradh gu h-ionadail ’s a chaidh a sgrìobhadh leis an luchd-aithne as aithne dhuinn a tha clàraichte air na frithealaichean ud. Cumaidh seo luchd-aithne is clàran nam frithealaichean. Cha ghabh seo a neo-dhèanamh.',
	3 => 'Nì seo purgaideachadh air gach susbaint cuideachd a chaidh a stòradh gu h-ionadail ’s a chaidh a sgrìobhadh leis an luchd-aithne as aithne dhuinn a tha clàraichte air na frithealaichean ud. Cumaidh seo luchd-aithne is clàran nam frithealaichean. Cha ghabh seo a neo-dhèanamh.',
];
$a->strings['Block reason'] = 'Adhbhar a’ bhacaidh';
$a->strings['The reason why you blocked this server domain pattern. This reason will be shown publicly in the server information page.'] = 'An t-adhbhar air an do bhac thu am pàtran àrainne fhrithealaichean seo. Thèid an t-adhbhar a shealltainn gu poblach air duilleag fiosrachadh an fhrithealaiche.';
$a->strings['Error importing pattern file'] = 'Mearachd ag ion-phortadh faidhle nam pàtranan';
$a->strings['Local blocklist replaced with the provided file.'] = 'Chaidh am faidhle a sholair thu a chur an àite na liosta-bacaidh ionadail.';
$a->strings['%d pattern was added to the local blocklist.'] = [
	0 => 'Chaidh %d phàtran a chur ris an liosta-bhacaidh ionadail.',
	1 => 'Chaidh %d phàtran a chur ris an liosta-bhacaidh ionadail.',
	2 => 'Chaidh %d pàtranan a chur ris an liosta-bhacaidh ionadail.',
	3 => 'Chaidh %d pàtran a chur ris an liosta-bhacaidh ionadail.',
];
$a->strings['No pattern was added to the local blocklist.'] = 'Cha deach pàtran sam bith a chur ris an liosta-bhacaidh ionadail.';
$a->strings['Import a Server Domain Pattern Blocklist'] = 'Ion-phortaich liosta-bhacaidh le pàtrain àrainne fhrithealaichean';
$a->strings['<p>This file can be downloaded from the <code>/friendica</code> path of any Friendica server.</p>'] = '<p>Gabhaidh am faidhle seo a luchdadh a-nuas on t-slighe <code>/friendica</code> aig frithealaiche Friendica sam bith.</p>';
$a->strings['Upload file'] = 'Luchdaich suas faidhle';
$a->strings['Patterns to import'] = 'Na pàtranan rin ion-phortadh';
$a->strings['Domain Pattern'] = 'Pàtran àrainne';
$a->strings['Import Mode'] = 'Modh an ion-phortaidh';
$a->strings['Import Patterns'] = 'Ion-phortaich na pàtranan';
$a->strings['%d total pattern'] = [
	0 => '%d phàtran gu h-iomlan',
	1 => '%d phàtran gu h-iomlan',
	2 => '%d pàtranan gu h-iomlan',
	3 => '%d pàtran gu h-iomlan',
];
$a->strings['Server domain pattern blocklist CSV file'] = 'Faidhle CSV sa bheil liosta-bhacaidh le pàtrain àrainne fhrithealaichean';
$a->strings['Append'] = 'Cuir ris';
$a->strings['Imports patterns from the file that weren\'t already existing in the current blocklist.'] = 'Nì seo ion-phortadh dhe na pàtranan san fhaidhle nach eil air an liosta-bhacaidh làithreach fhathast.';
$a->strings['Replace'] = 'Cuir ’na àite';
$a->strings['Replaces the current blocklist by the imported patterns.'] = 'Cuiridh seo na pàtranan air an ion-phortadh an àite na liosta-bacaidh làithrich.';
$a->strings['Blocked server domain pattern'] = 'Pàtran àrainne fhrithealaichean a chaidh a bhacadh';
$a->strings['Delete server domain pattern'] = 'Sguab às am pàtran àrainne fhrithealaichean';
$a->strings['Check to delete this entry from the blocklist'] = 'Cuir cromag ris a sguabadh às an nì seo on liosta-bhacaidh';
$a->strings['Server Domain Pattern Blocklist'] = 'Liosta-bhacaidh le pàtrain àrainne fhrithealaichean';
$a->strings['This page can be used to define a blocklist of server domain patterns from the federated network that are not allowed to interact with your node. For each domain pattern you should also provide the reason why you block it.'] = '’S urrainn dhut an duilleag seo a chleachdadh airson liosta-bhacaidh le pàtrain àrainne fhrithealaichean on lìonra cho-naisgte a mhìneachadh nach fhaod gnìomh a ghabhail leis an nòd agad. Airson gach pàtran àrainne fhrithealaichean, bu chòir dhut an t-adhbhar a thoirt seachad air an do bhac thu e.';
$a->strings['The list of blocked server domain patterns will be made publically available on the <a href="/friendica">/friendica</a> page so that your users and people investigating communication problems can find the reason easily.'] = 'Thèid liosta nam pàtran àrainne fhrithealaichean foillseachadh gu poblach air an duilleag <a href="/friendica">/friendica</a> ach am faigh an luchd-cleachdaidh agad ’s na daoine a tha a’ sgrùdadh duilgheadasan conaltraidh a-mach gun duilgheadas dè as adhbhar.';
$a->strings['Import server domain pattern blocklist'] = 'Ion-phortaich liosta-bhacaidh le pàtrain àrainne fhrithealaichean';
$a->strings['Add new entry to the blocklist'] = 'Cuir nì ùr ris an liosta-bhacaidh';
$a->strings['Save changes to the blocklist'] = 'Sàbhail na h-atharraichean air an liosta-bhacaidh';
$a->strings['Current Entries in the Blocklist'] = 'Na nithean làithreach air an liosta-bhacaidh';
$a->strings['Delete entry from the blocklist'] = 'Sguab às an nì on liosta-bhacaidh';
$a->strings['Delete entry from the blocklist?'] = 'A bheil thu airson an nì seo a sguabadh às on liosta-bhacaidh?';
$a->strings['Item marked for deletion.'] = 'Chaidh an nì a chomharrachadh a chùm sguabaidh às.';
$a->strings['Delete this Item'] = 'Sguab às an nì seo';
$a->strings['On this page you can delete an item from your node. If the item is a top level posting, the entire thread will be deleted.'] = 'Air an duilleag seo, ’s urrainn dhut nì a sguabadh às on nòd agad. Mas e post ciad ìre a tha san nì, thèid an snàithlean gu lèir a sguabadh às.';
$a->strings['You need to know the GUID of the item. You can find it e.g. by looking at the display URL. The last part of http://example.com/display/123456 is the GUID, here 123456.'] = 'Feumaidh tu a bhith eòlach air GUID an nì. Gheibh thu lorg air m. e. a’ coimhead air URL an t-seallaidh. ’S e a’ phàirt mu dheireadh aig http://example.com/display/123456 a sa san GUID, ’s e 123456 a th’ ann an-seo.';
$a->strings['GUID'] = 'GUID';
$a->strings['The GUID of the item you want to delete.'] = 'GUID an nì a tha thu airson sguabadh às.';
$a->strings['Item Id'] = 'ID an nì';
$a->strings['Item URI'] = 'URI an nì';
$a->strings['Terms'] = 'Briathran';
$a->strings['Tag'] = 'Taga';
$a->strings['Type'] = 'Seòrsa';
$a->strings['Term'] = 'Briathar';
$a->strings['URL'] = 'URL';
$a->strings['Implicit Mention'] = 'Iomradh fillte';
$a->strings['Item not found'] = 'Cha deach an nì a lorg';
$a->strings['No source recorded'] = 'Cha deach tùs a chlàradh';
$a->strings['Please make sure the <code>debug.store_source</code> config key is set in <code>config/local.config.php</code> for future items to have sources.'] = 'Dèan cinnteach gun deach iuchair rèiteachaidh <code>debug.store_source</code> a shuidheachadh ann an <code>config/local.config.php</code> ach am bi tùsan aig na nithean ri teachd.';
$a->strings['Item Guid'] = 'GUID an nì';
$a->strings['Contact not found or their server is already blocked on this node.'] = 'Cha deach an neach-aithne a lorg no chaidh am frithealaiche aca a bhacadh air an nòd seo mu thràth.';
$a->strings['Please login to access this page.'] = 'Clàraich a-steach airson an duilleag seo inntrigeadh.';
$a->strings['Create Moderation Report'] = 'Cruthaich gearan maorsainneachd';
$a->strings['Pick Contact'] = 'Tagh neach-aithne';
$a->strings['Please enter below the contact address or profile URL you would like to create a moderation report about.'] = 'Cuir a-steach seòladh no URL pròifil neach-aithne gu h-ìosal airson gearan maorsainneachd a chruthachadh mu dhèidhinn.';
$a->strings['Contact address/URL'] = 'Seòladh/URL an neach-aithne';
$a->strings['Pick Category'] = 'Tagh roinn-seòrsa';
$a->strings['Please pick below the category of your report.'] = 'Tagh roinn-seòrsa a’ ghearain agad.';
$a->strings['Spam'] = 'Spama';
$a->strings['This contact is publishing many repeated/overly long posts/replies or advertising their product/websites in otherwise irrelevant conversations.'] = 'Tha an neach-aithne seo a’ foillseachadh iomadh post/freagairt a tha ro fhada no ’gam foillseachadh a-rithist ’s a-rithist no tha e a’ dèanamh sanasachd air a’ bhathar/làrach-lìn aca nach eil buntainneachd dha na còmhraidhean.';
$a->strings['Illegal Content'] = 'Susbaint mhì-laghail';
$a->strings['This contact is publishing content that is considered illegal in this node\'s hosting juridiction.'] = 'Tha an neach-aithne seo a’ foillseachadh susbaint a thathar a’ meas gu bheil e mì-laghail san uachdranas far a bheil an nòd ’ga òstadh.';
$a->strings['Community Safety'] = 'Sàbhailteachd na coimhearsnachd';
$a->strings['This contact aggravated you or other people, by being provocative or insensitive, intentionally or not. This includes disclosing people\'s private information (doxxing), posting threats or offensive pictures in posts or replies.'] = 'Bhuair an neach-aithne seo thu no daoine eile on a tha e dìorrasach no neo-mhothachail ge b’ e a bheil e mar sin a dh’aona-ghnothaich no gun iarraidh. Gabhaidh seo a-staigh foillseachadh fiosrachadh prìobhaideach càich (doxxing), postadh bhagairtean no dealbhan frionasach ann am postaichean is freagairtean.';
$a->strings['Unwanted Content/Behavior'] = 'Susbaint/Giùlan gun iarraidh';
$a->strings['This contact has repeatedly published content irrelevant to the node\'s theme or is openly criticizing the node\'s administration/moderation without directly engaging with the relevant people for example or repeatedly nitpicking on a sensitive topic.'] = 'Dh’fhoillsich an neach-aithne seo iomadh susbaint nach eil buntainneach do chuspair an nòid no a tha a’ càineadh rianachd/maorsainneachd an nòid gu fosgailte gun a bhith a’ bruidhinn ris na daoine iomchaidh fhèin, mar eisimpleir, no a tha rag-fhoghlamach mu chuspair frionasach.';
$a->strings['Rules Violation'] = 'Briseadh riaghailt';
$a->strings['This contact violated one or more rules of this node. You will be able to pick which one(s) in the next step.'] = 'Bris an neach-aithne seo riaghailt no dhà dhen nòd seo. ’S urrainn dhut na riaghailtean a chaidh a bhriseadh a thaghadh san ath-cheum.';
$a->strings['Please elaborate below why you submitted this report. The more details you provide, the better your report can be handled.'] = 'Innis dhuinn carson a chuir thu a-null an gearan seo. Mar as mionaidiche am fiosrachadh a bheir thu dhuinn ’s ann as fhasa a bhios e dhuinn dèiligeadh ris a’ ghearan agad.';
$a->strings['Additional Information'] = 'Barrachd fiosrachaidh';
$a->strings['Please provide any additional information relevant to this particular report. You will be able to attach posts by this contact in the next step, but any context is welcome.'] = 'Thoir barrachd fiosrachaidh dhuinn a tha buntainneach dhan ghearan shònraichte seo. ’S urrainn dhut postaichean leis an neach-aithne seo a cheangal ris san ath-cheum ach cuiridh sinn fàilte do cho-theacsa sam bith.';
$a->strings['Pick Rules'] = 'Tagh riaghailtean';
$a->strings['Please pick below the node rules you believe this contact violated.'] = 'Tagh riaghailtean an nòid gu h-ìosal a shaoileas tu gun deach am briseadh leis an neach-aithne seo.';
$a->strings['Pick Posts'] = 'Tagh postaichean';
$a->strings['Please optionally pick posts to attach to your report.'] = 'Ma thogras tu, tagh postaichean ri cheangal ris a’ ghearan agad.';
$a->strings['Submit Report'] = 'Cuir an gearan a-null';
$a->strings['Further Action'] = 'Gnìomhan eile';
$a->strings['You can also perform one of the following action on the contact you reported:'] = '’S urrainn dhut fear de na gnìomhan seo a ghabhail cuideachd air an neach-aithne a rinn thu gearan air:';
$a->strings['Nothing'] = 'Na dèan dad';
$a->strings['Collapse contact'] = 'Co-theannaich an neach-aithne';
$a->strings['Their posts and replies will keep appearing in your Network page but their content will be collapsed by default.'] = 'Nochdaidh na postaichean ’s freagairtean air duilleag an lìonraidh agad fhathast ach bidh an t-susbaint aca co-theannaichte a ghnàth.';
$a->strings['Their posts won\'t appear in your Network page anymore, but their replies can appear in forum threads. They still can follow you.'] = 'Cha nochd na postaichean ’s freagairtean air duilleag an lìonraidh agad tuilleadh ach dh’fhaoidte gun nochd na freagairtean aca ann an snàithleanan fòraim. Faodaidh iad do leantainn fhathast.';
$a->strings['Block contact'] = 'Bac an neach-aithne';
$a->strings['Their posts won\'t appear in your Network page anymore, but their replies can appear in forum threads, with their content collapsed by default. They cannot follow you but still can have access to your public posts by other means.'] = 'Cha nochd na postaichean ’s freagairtean air duilleag an lìonraidh agad tuilleadh ach dh’fhaoidte gun nochd na freagairtean aca ann an snàithleanan fòraim leis an t-susbaint aca co-theannaichte a ghnàth. Chan fhaod iad do leantainn tuilleadh ach dh’fhaoidte gum faigh iad cothrom air na postaichean poblach agad fhathast air dòighean eile.';
$a->strings['Forward report'] = 'Sìn air adhart an gearan';
$a->strings['Would you ike to forward this report to the remote server?'] = 'A bheil thu airson an gearan seo a shìneadh air adhart dhan fhrithealaiche chèin?';
$a->strings['1. Pick a contact'] = '1. Tagh neach-aithne';
$a->strings['2. Pick a category'] = '2. Tagh roinn-seòrsa';
$a->strings['2a. Pick rules'] = '2a. Tagh riaghailtean';
$a->strings['2b. Add comment'] = '2b. Cuir beachd ris';
$a->strings['3. Pick posts'] = '3. Tagh postaichean';
$a->strings['Registered users'] = 'Cleachdaichean clàraichte';
$a->strings['Pending registrations'] = 'Clàraidhean rin dèiligeadh';
$a->strings['%s user blocked'] = [
	0 => 'Chaidh %s chleachdaiche a bhacadh',
	1 => 'Chaidh %s chleachdaiche a bhacadh',
	2 => 'Chaidh %s cleachdaichean a bhacadh',
	3 => 'Chaidh %s cleachdaiche a bhacadh',
];
$a->strings['You can\'t remove yourself'] = 'Chan urrainn dhut thu fhèin a thoirt air falbh';
$a->strings['%s user deleted'] = [
	0 => 'Chaidh %s chleachdaiche a sguabadh às',
	1 => 'Chaidh %s chleachdaiche a sguabadh às',
	2 => 'Chaidh %s cleachdaichean a sguabadh às',
	3 => 'Chaidh %s cleachdaiche a sguabadh às',
];
$a->strings['User "%s" deleted'] = 'Chaidh an cleachdaiche “%s” a sguabadh às';
$a->strings['User "%s" blocked'] = 'Chaidh an chleachdaiche “%s” a bhacadh';
$a->strings['Register date'] = 'Ceann-là a’ chlàraidh';
$a->strings['Last login'] = 'An clàradh a-steach mu dheireadh';
$a->strings['Last public item'] = 'An nì poblach mu dheireadh';
$a->strings['Active Accounts'] = 'Cunntasan gnìomhach';
$a->strings['User blocked'] = 'Chaidh an cleachdaiche a bhacadh';
$a->strings['Site admin'] = 'Rianaire na làraich';
$a->strings['Account expired'] = 'Dh’fhalbh an ùine air a’ chunntas';
$a->strings['Create a new user'] = 'Cruthaich cleachdaiche ùr';
$a->strings['Selected users will be deleted!\n\nEverything these users had posted on this site will be permanently deleted!\n\nAre you sure?'] = 'Thèid na cleachdaichean a thagh thu a sguabadh às!\n\nThèid a h-uile càil a phostaich na cleachdaichean seo air an làrach seo a sguabadh às gu buan!\n\nA bheil thu cinnteach?';
$a->strings['The user {0} will be deleted!\n\nEverything this user has posted on this site will be permanently deleted!\n\nAre you sure?'] = 'Thèid an cleachdaiche {0} a sguabadh às!\n\nThèid a h-uile càil a phostaich an cleachdaiche seo air an làrach seo a sguabadh às gu buan!\n\nA bheil thu cinnteach?';
$a->strings['%s user unblocked'] = [
	0 => 'Chaidh %s chleachdaiche a dhì-bhacadh',
	1 => 'Chaidh %s chleachdaiche a dhì-bhacadh',
	2 => 'Chaidh %s cleachdaichean a dhì-bhacadh',
	3 => 'Chaidh %s cleachdaiche a dhì-bhacadh',
];
$a->strings['User "%s" unblocked'] = 'Chaidh an cleachdaiche “%s” a dhì-bhacadh';
$a->strings['Blocked Users'] = 'Cleachdaichean bacte';
$a->strings['New User'] = 'Cleachdaiche ùr';
$a->strings['Add User'] = 'Cuir cleachdaiche ris';
$a->strings['Name of the new user.'] = 'Ainm a’ chleachdaiche ùir.';
$a->strings['Nickname'] = 'Far-ainm';
$a->strings['Nickname of the new user.'] = 'Far-ainm a’ chleachdaiche ùir.';
$a->strings['Email address of the new user.'] = 'Seòladh puist-d a’ chleachdaiche ùir.';
$a->strings['Users awaiting permanent deletion'] = 'Cleachdaichean a’ feitheamh air sguabadh às buan';
$a->strings['Permanent deletion'] = 'Sguabadh às buan';
$a->strings['User waiting for permanent deletion'] = 'Cleachdaiche a’ feitheamh sguabadh às buan';
$a->strings['%s user approved'] = [
	0 => 'Fhuair %s chleachdaiche aonta',
	1 => 'Fhuair %s chleachdaiche aonta',
	2 => 'Fhuair %s cleachdaichean aonta',
	3 => 'Fhuair %s cleachdaiche aonta',
];
$a->strings['%s registration revoked'] = [
	0 => 'Chaidh %s chlàradh a chùl-ghairm',
	1 => 'Chaidh %s chlàradh a chùl-ghairm',
	2 => 'Chaidh %s clàraidhean a chùl-ghairm',
	3 => 'Chaidh %s clàradh a chùl-ghairm',
];
$a->strings['Account approved.'] = 'Air aontachadh ris a’ chunntas.';
$a->strings['Registration revoked'] = 'Chaidh an clàradh a chùl-ghairm';
$a->strings['User registrations awaiting review'] = 'Clàraichean chleachdaichean a’ feitheamh air lèirmheas';
$a->strings['Request date'] = 'Cuin a chaidh iarraidh';
$a->strings['No registrations.'] = 'Chan eil clàradh ann.';
$a->strings['Note from the user'] = 'Nòta on chleachdaiche';
$a->strings['Deny'] = 'Diùlt';
$a->strings['Show Ignored Requests'] = 'Seall na h-iarrtasan a leig thu seachad';
$a->strings['Hide Ignored Requests'] = 'Falaich na h-iarrtasan a leig thu seachad';
$a->strings['Notification type:'] = 'Seòrsa a’ bhratha:';
$a->strings['Suggested by:'] = '’Ga mholadh le:';
$a->strings['Claims to be known to you: '] = 'A’ tagradh gur aithne dhut e: ';
$a->strings['Shall your connection be bidirectional or not?'] = 'A bheil thu airson co-dhàimh a chruthachadh?';
$a->strings['Accepting %s as a friend allows %s to subscribe to your posts, and you will also receive updates from them in your news feed.'] = 'Ma ghabhas tu ri %s ’nad charaid, faodaidh %s fo-sgrìobhadh air na postaichean agad agus gheibh thu na naidheachdan uapa-san cuideachd.';
$a->strings['Accepting %s as a subscriber allows them to subscribe to your posts, but you will not receive updates from them in your news feed.'] = 'Ma ghabhas tu ri %s mar fo-sgrìobhadh, faodaidh iad fo-sgrìobhadh air na postaichean agad ach chan fhaigh thu na naidheachdan uapa-san.';
$a->strings['Friend'] = 'Caraid';
$a->strings['Subscriber'] = 'Fo-sgrìobhadh';
$a->strings['No introductions.'] = 'Chan eil cur an aithne ann.';
$a->strings['No more %s notifications.'] = 'Chan eil brath %s ann tuilleadh.';
$a->strings['You must be logged in to show this page.'] = 'Feumaidh tu clàradh a-steach mus urrainn dhut an duilleag seo a shealltainn.';
$a->strings['Network Notifications'] = 'Brathan lìonraidh';
$a->strings['System Notifications'] = 'Brathan an t-siostaim';
$a->strings['Personal Notifications'] = 'Brathan pearsanta';
$a->strings['Home Notifications'] = 'Brathan na dachaighe';
$a->strings['Show unread'] = 'Seall an fheadhainn gun leughadh';
$a->strings['{0} requested registration'] = 'Dh’iarr {0} clàradh';
$a->strings['{0} and %d others requested registration'] = 'Dh’iarr {0} ’s %d eile clàradh';
$a->strings['Authorize application connection'] = 'Ùghdarraich ceangal aplacaide';
$a->strings['Do you want to authorize this application to access your posts and contacts, and/or create new posts for you?'] = 'A bheil thu airson cead a thoirt dhan aplacaid seo airson na postaichean ’s an luchd-aithne agad inntrigeadh agus/no postaichean ùra a chruthachadh às do leth?';
$a->strings['Unsupported or missing response type'] = 'Seòrsa freagairte gun taic ris no a dhìth';
$a->strings['Incomplete request data'] = 'Dàta iarrtais neo-choileanta';
$a->strings['Please copy the following authentication code into your application and close this window: %s'] = 'Cuir lethbhreac dhen chòd dearbhaidh seo san aplacaid agad is dùin an uinneag seo: %s';
$a->strings['Invalid data or unknown client'] = 'Dàta mì-dhligheach no cliant nach aithne dhuinn';
$a->strings['Unsupported or missing grant type'] = 'Seòrsa ceadachaidh gun taic ris no a dhìth';
$a->strings['Subscribing to contacts'] = 'Fo-sgrìobhadh air luchd-aithne';
$a->strings['No contact provided.'] = 'Cha deach neach-aithne a thoirt seachad.';
$a->strings['Couldn\'t fetch information for contact.'] = 'Cha d’ fhuair sinn grèim air an fhiosrachadh conaltraidh.';
$a->strings['Couldn\'t fetch friends for contact.'] = 'Cha d’ fhuair sinn grèim air caraidean a chùm conaltraidh.';
$a->strings['Couldn\'t fetch following contacts.'] = 'Cha d’ fhuair sinn grèim air an luchd-aithne a leanas.';
$a->strings['Couldn\'t fetch remote profile.'] = 'Cha d’ fhuair sinn grèim air a’ phròifil chèin.';
$a->strings['Unsupported network'] = 'Lìonra ris nach eil taic';
$a->strings['Done'] = 'Deiseil';
$a->strings['success'] = 'chaidh leis';
$a->strings['failed'] = 'dh’fhàillig leis';
$a->strings['ignored'] = 'chaidh a leigeil seachad';
$a->strings['Keep this window open until done.'] = 'Cùm an uinneag seo fosgailte gus am bi e deiseil.';
$a->strings['The Photo is not available.'] = 'Chan eil an dealbh ri fhaighinn.';
$a->strings['The Photo with id %s is not available.'] = 'Chan eil an dealbh air a bheil an id %s ri fhaighinn.';
$a->strings['Invalid external resource with url %s.'] = 'Goireas mì-dhligheach air an taobh a-muigh leis an url %s.';
$a->strings['Invalid photo with id %s.'] = 'Dealbh mì-dhligheach air a bheil an id %s.';
$a->strings['Post not found.'] = 'Cha deach am post a lorg.';
$a->strings['Edit post'] = 'Deasaich am post';
$a->strings['web link'] = 'ceangal-lìn';
$a->strings['Insert video link'] = 'Cuir a-steach ceangal video';
$a->strings['video link'] = 'ceangal video';
$a->strings['Insert audio link'] = 'Cuir a-steach ceangal fuaime';
$a->strings['audio link'] = 'ceangal fuaime';
$a->strings['Remove Item Tag'] = 'Thoir air falbh taga an nì';
$a->strings['Select a tag to remove: '] = 'Tagh taga gus a thoirt air falbh: ';
$a->strings['Remove'] = 'Thoir air falbh';
$a->strings['Wrong type "%s", expected one of: %s'] = 'Seòrsa “%s” ceàrr, an dùil air fear dhen fheadhainn seo: %s';
$a->strings['Model not found'] = 'Cha deach am modail a lorg';
$a->strings['Unlisted'] = 'Falaichte o liostaichean';
$a->strings['Remote privacy information not available.'] = 'Chan eil fiosrachadh cèin na prìobhaideachd ri làimh.';
$a->strings['Visible to:'] = 'Ri fhaicinn do:';
$a->strings['Collection (%s)'] = 'Cruinneachadh (%s)';
$a->strings['Followers (%s)'] = 'Luchd-leantainn (%s)';
$a->strings['%d more'] = '%d eile';
$a->strings['No contacts.'] = 'Chan eil neach-aithne ann.';
$a->strings['%s\'s posts'] = 'Na postaichean aig %s';
$a->strings['%s\'s comments'] = 'Na beachdan aig %s';
$a->strings['%s\'s timeline'] = 'An loidhne-ama aig %s';
$a->strings['Image exceeds size limit of %s'] = 'Tha an dealbh nas motha na tha ceadaichte dhe %s';
$a->strings['Image upload didn\'t complete, please try again'] = 'Cha deach luchdadh suas an deilbh a choileanadh, feuch ris a-rithist';
$a->strings['Image file is missing'] = 'Tha faidhle an deilbh a dhìth';
$a->strings['Server can\'t accept new file upload at this time, please contact your administrator'] = 'Cha ghabh am frithealaiche ri luchdadh suas deilbh ùir aig an àm seo, cuir fios gun rianaire agad';
$a->strings['Image file is empty.'] = 'Tha faidhle an deilbh falamh.';
$a->strings['View Album'] = 'Seall an t-albam';
$a->strings['Profile not found.'] = 'Cha deach a’ phròifil a lorg.';
$a->strings['You\'re currently viewing your profile as <b>%s</b> <a href="%s" class="btn btn-sm pull-right">Cancel</a>'] = 'Tha thu a’ sealltainn air a’ phròifil agad mar <b>%s</b> <a href="%s" class="btn btn-sm pull-right">Sguir dheth</a>';
$a->strings['Full Name:'] = 'An t-ainm slàn:';
$a->strings['Member since:'] = 'Ball o chionn:';
$a->strings['j F, Y'] = 'j F Y';
$a->strings['j F'] = 'j F';
$a->strings['Birthday:'] = 'Co-là breith:';
$a->strings['Age: '] = 'Aois: ';
$a->strings['%d year old'] = [
	0 => '%d bhliadhna a dh\'aois',
	1 => '%d bhliadhna a dh’aois',
	2 => '%d bliadhnaichean a dh’aois',
	3 => '%d bliadhna a dh’aois',
];
$a->strings['Description:'] = 'Tuairisgeul:';
$a->strings['Groups:'] = 'Buidhnean:';
$a->strings['View profile as:'] = 'Seall a’ phròifil mar:';
$a->strings['View as'] = 'Seall mar';
$a->strings['Profile unavailable.'] = 'Chan eil a’ phròifil ri fhaighinn.';
$a->strings['Invalid locator'] = 'Lorgaire mì-dhligheach';
$a->strings['The provided profile link doesn\'t seem to be valid'] = 'Chan eil coltas dligheach air ceangal na pròifil a chaidh a sholar';
$a->strings['This site has exceeded the number of allowed daily account registrations. Please try again tomorrow.'] = 'Chlàradh na tha ceadaichte de chunntasan ùra air an làrach seo an-diugh. Feuch ris a-rithist a-màireach.';
$a->strings['Import'] = 'Ion-phortaich';
$a->strings['Your registration is pending approval by the site owner.'] = 'Tha an clàradh agad a’ feitheamh air aontachadh o shealbhadair na làraich.';
$a->strings['You must be logged in to use this module.'] = 'Feumaidh tu clàradh a-steach mus urrainn dhut am mòideal seo a chleachdadh.';
$a->strings['Relocate message has been send to your contacts'] = 'Chaidh teachdaireachd mun imrich a chur dhan luchd-aithne agad';
$a->strings['Account for a regular personal profile that requires manual approval of "Friends" and "Followers".'] = 'Cunntas do phròifil phearsanta àbhaisteach a dh’iarras aontachadh a làimh air “Caraidean” is “Luchd-leantainn”.';
$a->strings['Requires manual approval of contact requests.'] = 'Feumaidh tu aontachadh ri iarrtasan luchd-aithne a làimh.';
$a->strings['Your profile will also be published in the global friendica directories (e.g. <a href="%s">%s</a>).'] = 'Thèid a’ phròifil agad fhoillseachadh sna h-eòlairean cho-naisgte aig Friendica cuideachd (m.e. <a href="%s">%s</a>).';
$a->strings['Allow your profile to be searchable globally?'] = 'An gabh a’ phròifil agad a lorg gu co-naisgte?';
$a->strings['Your contacts may write posts on your profile wall. These posts will be distributed to your contacts'] = '’S urrainn dhan luchd-aithne agad postaichean a sgrìobhadh air balla do phròifile. Thèid na postaichean sin a sgaoileadh dhan luchd-aithne agad';
$a->strings['Expire starred posts'] = 'Falbhaidh an ùine air postaichean le rionnag riutha';
$a->strings['Starring posts keeps them from being expired. That behaviour is overwritten by this setting.'] = 'Nuair a bhios rionnag ri post, chan fhalbh an ùine orra. Sgrìobhaidh an roghainn seo thairis air a’ ghiùlan sin.';
$a->strings['You receive an introduction'] = 'Fhuair thu cur an aithne';
$a->strings['Your introductions are confirmed'] = 'Chaidh na cuir an aithne agad a dhearbhadh';
$a->strings['Someone liked your content'] = '’S toigh le cuideigin an t-susbaint agad';
$a->strings['Someone shared your content'] = 'Cho-roinn cuideigin an t-susbaint agad';
$a->strings['Someone commented in a thread where you interacted'] = 'Chuir cuideigin beachd ri snàithlean san do rinn thu co-luadar';
$a->strings['Relocate'] = 'Imrich';
$a->strings['Resend relocate message to contacts'] = 'Cuir teachdaireachd mun imrich dhan neach-aithne';
$a->strings['Addon Settings'] = 'Roghainnean nan tuilleadan';
$a->strings['No Addon settings configured'] = 'Cha deach roghainnean tuilleadain a rèiteachadh';
$a->strings['Failed to connect with email account using the settings provided.'] = 'Cha deach leinn ceangal a dhèanamh leis a’ chunntas puist-d a’ cleachdadh nan roghainnean a chaidh a thoirt seachad.';
$a->strings['Diaspora (Socialhome, Hubzilla)'] = 'Diaspora* (Socialhome, Hubzilla)';
$a->strings['Email access is disabled on this site.'] = 'Tha an t-inntrigeadh le post-d à comas dhan làrach seo.';
$a->strings['None'] = 'Chan eil gin';
$a->strings['General Social Media Settings'] = 'Roghainnean coitcheann nam meadhanan sòisealta';
$a->strings['Followed content scope'] = 'Farsaingeachd na susbainte air a leanas tu';
$a->strings['By default, conversations in which your follows participated but didn\'t start will be shown in your timeline. You can turn this behavior off, or expand it to the conversations in which your follows liked a post.'] = 'Nochdaidh na còmhraidhean sa ghabh an fheadhainn air a leanas tu pàirt ach nach do thòisich iad fhèin air an loidhne-ama agad a ghnàth. ’S urrainn dhut seo a chur dheth no a leudachadh ach an nochd na còmhraidhean far an toigh leis an fheadhainn air a leanas tu post.';
$a->strings['Only conversations my follows started'] = 'Na còmhraidhean a thòisich cuideigin air a leanas mi a-mhàin';
$a->strings['Conversations my follows started or commented on (default)'] = 'Na còmhraidhean a thòisich cuideigin air a leanas mi no a chuir iad beachd riutha (bun-roghainn)';
$a->strings['Any conversation my follows interacted with, including likes'] = 'Còmhradh sam bith leis an do rinn cuideigin air a leanas mi co-luadar, a’ gabhail a-staigh nas toigh leotha';
$a->strings['Enable intelligent shortening'] = 'Cuir an giorrachadh tapaidh an comas';
$a->strings['Normally the system tries to find the best link to add to shortened posts. If disabled, every shortened post will always point to the original friendica post.'] = 'Mar as àbhaist, feuchaidh an siostam gun dèid an ceangal as fheàrr a lorg gus a chur ri postaichean giorraichte. Ma tha seo à comas, tomhaidh gach post giorraichte ris a’ phost tùsail air friendica an-còmhnaidh.';
$a->strings['Enable simple text shortening'] = 'Cuir an comas giorrachadh teacsa sìmplidh';
$a->strings['Normally the system shortens posts at the next line feed. If this option is enabled then the system will shorten the text at the maximum character limit.'] = 'Mar as àbhaist, giorraichidh an siostam na postaichean aig an ath earrann. Ma tha an roghainn seo an comas, giorraichidh an siostam an teacsa aig crìoch nan caractaran ceadaichte.';
$a->strings['Attach the link title'] = 'Cuir tiotal a’ cheangail ris';
$a->strings['When activated, the title of the attached link will be added as a title on posts to Diaspora. This is mostly helpful with "remote-self" contacts that share feed content.'] = 'Nuair a bhios seo an gnìomh, thèid tiotal a’ cheangail a chur ris mar tiotal air postaichean gu diaspora*. Tha seo as fheumaile dhan luchd-aithne “remote-self” a cho-roinneas susbaint inbhir.';
$a->strings['When activated, added links at the end of the post react the same way as added links in the web interface.'] = 'Nuair a bhios seo an gnìomh, bidh an t-aon ghiùlan aig ceanglaichean a thèid a chur ri bonn puist ’s a tha aig ceanglaichean a thèid a chur ris san eadar-aghaidh-lìn.';
$a->strings['Email/Mailbox Setup'] = 'Suidheachadh a’ phuist-d/a’ bhogsa-phuist';
$a->strings['If you wish to communicate with email contacts using this service (optional), please specify how to connect to your mailbox.'] = 'Ma tha thu airson an t-seirbheis seo a chleachdadh airson conaltradh le luchd-aithne air a’ post-d (gu roghainneil), sònraich an dòigh air a nì thu ceangal leis a’ bhogsa-phuist agad.';
$a->strings['Last successful email check:'] = 'An turas mu dheireadh a chaidh leinn sùil a thoirt air a’ phost-d:';
$a->strings['IMAP server name:'] = 'Ainm frithealaiche IMAP:';
$a->strings['IMAP port:'] = 'Port IMAP:';
$a->strings['Security:'] = 'Tèarainteachd:';
$a->strings['Email login name:'] = 'Ainm clàradh a-steach a’ phuist-d:';
$a->strings['Email password:'] = 'Facal-faire a’ phuist-d:';
$a->strings['Reply-to address:'] = 'An seòladh Freagairt-gu:';
$a->strings['Send public posts to all email contacts:'] = 'Cuir postaichean poblach dhan a h-uile neach-aithne puist-d:';
$a->strings['Action after import:'] = 'Gnìomh às dèid an ion-phortaidh:';
$a->strings['Move to folder'] = 'Gluais gu pasgan';
$a->strings['Move to folder:'] = 'Gluais gu pasgan:';
$a->strings['Your legacy ActivityPub/GNU Social account'] = 'An cunntas ActivityPub/GNU Social dìleabach agad';
$a->strings['If you enter your old account name from an ActivityPub based system or your GNU Social/Statusnet account name here (in the format user@domain.tld), your contacts will be added automatically. The field will be emptied when done.'] = 'Ma chuireas tu ainm seann-chunntais ris o shiostam stèidhichte air ActivityPub no ainm do chunntais GNU Social/Statusnet an-seo (san fhòrmat cleachdaiche@àrainn.tld), thèid an luchd-aithne agad a chur ris gu fèin-obrachail. Thèid an raon fhalamhachadh nuair a bhios sin deiseil.';
$a->strings['Delegation successfully granted.'] = 'Chaidh neach-ionaid a dhèanamh dheth.';
$a->strings['Delegation successfully revoked.'] = 'Chaidh ceadan neach-ionaid a thoirt air falbh.';
$a->strings['Delegated administrators can view but not change delegation permissions.'] = 'Chì rianairean a tha ’nan luchd-ionaid na ceadan ach chan urrainn dhaibh an atharrachadh.';
$a->strings['Delegate user not found.'] = 'Cha deach cleachdaiche an neach-ionaid a lorg.';
$a->strings['Register additional accounts that are automatically connected to your existing account so you can manage them from this account.'] = 'Clàraich cunntasan a bharrachd a thèid a cho-cheangal ris a’ chunntas làithreach agad ach an urrainn dhut an stiùireadh on chunntas seo.';
$a->strings['Delegates'] = 'Luchd-ionaid';
$a->strings['Delegates are able to manage all aspects of this account/page except for basic account settings. Please do not delegate your personal account to anybody that you do not trust completely.'] = '’S urrainn dhan luchd-ionaid nì sam bith mun chunntas/duilleag seo a stiùireadh ach roghainnean bunaiteach a’ chunntais. Na dèan neach-ionaid dhan chunntas phearsanta agad de dhuine sam bith anns nach eil làn-earbsa agad.';
$a->strings['Existing Page Delegates'] = 'Luchd-ionaid làithreach na duilleige';
$a->strings['Potential Delegates'] = 'Tagraichean luchd-ionaid';
$a->strings['The theme you chose isn\'t available.'] = 'Chan eil an t-ùrlar a thagh thu ri fhaighinn.';
$a->strings['General Theme Settings'] = 'Roghainnean coitcheann an ùrlair';
$a->strings['Custom Theme Settings'] = 'Roghainnean gnàthaichte an ùrlair';
$a->strings['Theme settings'] = 'Roghainnean an ùrlair';
$a->strings['Display Theme:'] = 'Ùrlar taisbeanaidh:';
$a->strings['Mobile Theme:'] = 'Ùrlar mobile:';
$a->strings['Display the Dislike feature'] = 'Seall an gleus “Cha toigh leam seo”';
$a->strings['Display the Dislike button and dislike reactions on posts and comments.'] = 'Seall am putan “Cha toigh leam seo” agus freagairtean “Cha toigh leam seo” air postaichean is beachdan.';
$a->strings['Display the resharer'] = 'Seall cò rinn an co-roinneadh';
$a->strings['Display the first resharer as icon and text on a reshared item.'] = 'Seall a’ chiad neach a rinn co-roinneadh ’na ìomhaigheag agus teacsa air an nì a chaidh a cho-roinneadh.';
$a->strings['Additional Features'] = 'Gleusan a bharrachd';
$a->strings['Connected Apps'] = 'Aplacaidean ceangailte';
$a->strings['Remove authorization'] = 'Thoir an t-ùghdarrachadh air falbh';
$a->strings['[Friendica System Notify]'] = '[Brath siostam Friendica]';
$a->strings['User deleted their account'] = 'Sguab an cleachdaiche às an cunntas aca';
$a->strings['On your Friendica node an user deleted their account. Please ensure that their data is removed from the backups.'] = 'Sguab cleachdaiche às an cunntas aca air an nòd Friendica agad. Dèan cinnteach gun dèid an dàta aca a thoirt air falbh o na lethbhreacan-glèidhidh.';
$a->strings['The user id is %d'] = '’S e %d ID a’ chleachdaiche';
$a->strings['Remove My Account'] = 'Thoir air falbh an cunntas agam';
$a->strings['This will completely remove your account. Once this has been done it is not recoverable.'] = 'Bheir seo air falbh an cunntas agad gu tur. Nuair a bhios sin air a thachairt, cha ghabh aiseag.';
$a->strings['Please enter your password for verification:'] = 'Cuir a-steach am facal-faire agad airson a dhearbhadh:';
$a->strings['Manage app-specific passwords'] = 'Stiùir na faclan-faire sònraichte do dh’aplacaidean';
$a->strings['Manage trusted browsers'] = 'Stiùir brabhsairean earbsach';
$a->strings['At the time of registration, and for providing communications between the user account and their contacts, the user has to provide a display name (pen name), an username (nickname) and a working email address. The names will be accessible on the profile page of the account by any visitor of the page, even if other profile details are not displayed. The email address will only be used to send the user notifications about interactions, but wont be visibly displayed. The listing of an account in the node\'s user directory or the global user directory is optional and can be controlled in the user settings, it is not necessary for communication.'] = 'Aig àm a’ chlàraidh agus a chùm conaltraidh eadar cunntas a’ chleachdaiche ’s an luchd-aithne aca, feumaidh an cleachdaiche ainm taisbeanaidh (ainm-pinn), ainm-cleachdaiche (far-ainm) agus seòladh puist-d a tha ag obair a thoirt seachad. Gabhaidh na h-ainmean inntrigeadh air duilleag pròifil a’ chunntais le duine sam bith a thadhlas air an duilleag, fiù mura dèid fiosrachadh eile na pròifil a shealltainn. Cha dèid an seòladh puist-d a chleachdadh ach airson brathan a chur dhan chleachdaiche mu co-luadar agus cha dèid a shealltainn gu poblach. Tha cur a’ chunntais ri liosta nan cleachdaichean ann an eòlaire an nòid no san eòlaire cho-naisgte roghainneil agus gabhaidh sin a shuidheachadh ann an roghainnean a’ chleachdaiche; chan eil e riatanach dhan chonaltradh.';
$a->strings['The requested item doesn\'t exist or has been deleted.'] = 'Chan eil am an nì a dh’iarr thu ann no chaidh a sguabadh às.';
$a->strings['You are now logged in as %s'] = 'Tha thu air do chlàradh a-steach mar %s';
$a->strings['Switch between your accounts'] = 'Geàrr leum eadar na cunntasan agad';
$a->strings['Manage your accounts'] = 'Stiùirich na cunntasan agad';
$a->strings['Toggle between different identities or community/group pages which share your account details or which you have been granted "manage" permissions'] = 'Geàrr leum eadar dearbh-aithnean no duilleagan coimhearsnachd/buidhinn a tha a’ co-roinneadh fiosrachadh a’ chunntais agad no a fhuair thu ceadan “stiùir” dhaibh';
$a->strings['Select an identity to manage: '] = 'Tagh dearbh-aithne ri stiùireadh: ';
$a->strings['User imports on closed servers can only be done by an administrator.'] = 'Chan fhaod ach rianairean cleachdaichean ion-phortadh gu frithealaichean dùinte.';
$a->strings['Move account'] = 'Imrich an cunntas';
$a->strings['You can import an account from another Friendica server.'] = '’S urrainn dhut cunntas ion-phortadh o fhrithealaiche Friendica eile.';
$a->strings['You need to export your account from the old server and upload it here. We will recreate your old account here with all your contacts. We will try also to inform your friends that you moved here.'] = 'Feumaidh tu an cunntas agad às-phortadh on t-seann-fhrithealaiche ’s a luchdadh suas an-seo. Ath-chruthaichidh sinn an seann-chunntas agad an-seo leis an luchd-aithne gu lèir agad. Feuchaidh sinn cuideachd gun leig sinn fios dha do charaidean gun do dh’imrich thu an-seo.';
$a->strings['This feature is experimental. We can\'t import contacts from the OStatus network (GNU Social/Statusnet) or from Diaspora'] = 'Chan e ach gleus deuchainneil a tha seo. Chan urrainn dhuinn luchd-aithne ion-phortadh on lìonra OStatus (GNU Social/Statusnet) no o dhiaspora*';
$a->strings['Account file'] = 'Faidhle a’ chunntais';
$a->strings['To export your account, go to "Settings->Export your personal data" and select "Export account"'] = 'Airson an cunntas agad às-phortadh, tadhail air “Roghainnean” -> “Às-phortaich an dàta pearsanta agad” agus tagh “Às-phortaich an cunntas”';
$a->strings['Error decoding account file'] = 'Mearachd le dì-chòdachadh faidhle a’ chunntais';
$a->strings['Error! No version data in file! This is not a Friendica account file?'] = 'Mearachd! Chan eil dàta mun tionndadh san fhaidhle! Nach e faidhle cunntas Friendica a th’ ann?';
$a->strings['User \'%s\' already exists on this server!'] = 'Tha an cleachdaiche “%s” air an fhrithealaiche seo mu thràth!';
$a->strings['User creation error'] = 'Mearachd a’ cruthachadh a’ chleachdaiche';
$a->strings['%d contact not imported'] = [
	0 => 'Tha %d neach-aithne nach deach ion-phortadh',
	1 => 'Tha %d luchd-aithne nach deach ion-phortadh',
	2 => 'Tha %d luchd-aithne nach deach ion-phortadh',
	3 => 'Tha %d luchd-aithne nach deach ion-phortadh',
];
$a->strings['User profile creation error'] = 'Mearachd a’ cruthachadh pròifil a’ chleachdaiche';
$a->strings['Done. You can now login with your username and password'] = 'Deiseil. ’S urrainn dhut clàradh a-steach leis an ainm-chleachdaiche ’s fhacal-fhaire agad a-nis';
$a->strings['Getting Started'] = 'Toiseach tòiseachaidh';
$a->strings['On your <em>Quick Start</em> page - find a brief introduction to your profile and network tabs, make some new connections, and find some groups to join.'] = 'Air an duilleag <em>grad-tòiseachaidh</em> agad – gheibh thu facal-toisich air tabaichean na pròifile ’s an lìonraidh agad, ’s urrainn dhut dàimhean ùra a stèidheachadh is gheibh thu lorg air buidhnean ùra airson ballrachd fhaighinn annta.';
$a->strings['Enter your email access information on your Connector Settings page if you wish to import and interact with friends or mailing lists from your email INBOX'] = 'Cuir a-steach fiosrachadh inntrigidh dhan phost-d agad air duilleag roghainnean nan ceangladairean agad ma tha thu airson ion-phortadh is co-luadar le caraidean no liostaichean-phuist o BHOGSA a-STEACH a’ phuist-d agad';
$a->strings['{0} has started following you'] = 'Tha {0} a’ leantainn ort a-nis';
$a->strings['%s liked %s\'s post'] = 'Is toigh le %s am post aig %s';
$a->strings['%s disliked %s\'s post'] = 'Cha toigh le %s am post aig %s';
$a->strings['%s is attending %s\'s event'] = 'Bidh %s an làthair aig an tachartas aig %s';
$a->strings['%s is not attending %s\'s event'] = 'Cha bhi %s an làthair aig an tachartas aig %s';
$a->strings['%s may attending %s\'s event'] = '’S dòcha gum bi %s an làthair aig an tachartas aig %s';
$a->strings['%1$s has started following you'] = 'Tha %1$s a’ leantainn ort a-nis';
$a->strings['%1$s liked your comment on %2$s'] = '’S toigh le %1$s do bheachd air %2$s';
$a->strings['%1$s liked your post %2$s'] = 'Is toigh le %1$s am post %2$s';
$a->strings['%1$s disliked your comment on %2$s'] = 'Cha toigh le %1$s do bheachd air %2$s';
$a->strings['%1$s disliked your post %2$s'] = 'Cha toigh le %1$s am post %2$s';
$a->strings['%1$s shared your comment %2$s'] = 'Cho-roinn %1$s do bheachd %2$s';
$a->strings['%1$s shared your post %2$s'] = 'Cho-roinn %1$s am post agad %2$s';
$a->strings['%1$s shared the post %2$s from %3$s'] = 'Cho-roinn %1$s am post %2$s o %3$s';
$a->strings['%1$s shared a post from %3$s'] = 'Cho-roinn %1$s post o %3$s';
$a->strings['%1$s shared the post %2$s'] = 'Cho-roinn %1$s am post %2$s';
$a->strings['%1$s shared a post'] = 'Cho-roinn %1$s post';
$a->strings['%1$s wants to attend your event %2$s'] = 'Bu mhath le %1$s a bhith an làthair aig an tachartas %2$s agad';
$a->strings['%1$s does not want to attend your event %2$s'] = 'Cha bu mhath le %1$s a bhith an làthair aig an tachartas %2$s agad';
$a->strings['%1$s maybe wants to attend your event %2$s'] = '’S dòcha gum bu mhath le %1$s a bhith an làthair aig an tachartas %2$s agad';
$a->strings['[Friendica:Notify]'] = '[Friendica:Brath]';
$a->strings['%1$s sent you a new private message at %2$s.'] = 'Chuir %1$s teachdaireachd phrìobhaideach ùr thugad aig %2$s.';
$a->strings['a private message'] = 'teachdaireachd phrìobhaideach';
$a->strings['%1$s sent you %2$s.'] = 'Chuir %1$s %2$s thugad.';
$a->strings['Please visit %s to view and/or reply to your private messages.'] = 'Tadhail air %s a shealltainn agus/no a’ freagairt dha na teachdaireachdan prìobhaideach agad.';
$a->strings['%s commented on an item/conversation you have been following.'] = 'Chuir %s beachd ri nì/còmhradh air a bheil thu a’ leantainn.';
$a->strings['Please visit %s to view and/or reply to the conversation.'] = 'Tadhail air %s a shealltainn agus/no a’ freagairt dhan chòmhradh.';
$a->strings['%1$s posted to your profile wall at %2$s'] = 'Chuir %1$s post ri balla na pròifil agad aig %2$s';
$a->strings['%1$s posted to [url=%2$s]your wall[/url]'] = 'Chuir %1$s post ris [url=%2$s]a’ bhalla agad[/url]';
$a->strings['%s Introduction received'] = '%s Fhuair thu cur an aithne';
$a->strings['You\'ve received an introduction from \'%1$s\' at %2$s'] = 'Fhuair thu cur an aithne o “%1$s” aig %2$s';
$a->strings['You\'ve received [url=%1$s]an introduction[/url] from %2$s.'] = 'Fhuair thu [url=%1$s]cur an aithne[/url] o %2$s.';
$a->strings['Please visit %s to approve or reject the introduction.'] = 'Tadhail air %s a ghabhail ris no a dhiùltadh a’ chuir an aithne.';
$a->strings['\'%1$s\' has chosen to accept you a fan, which restricts some forms of communication - such as private messaging and some profile interactions. If this is a celebrity or community page, these settings were applied automatically.'] = 'Ghabh “%1$s” riut ’nad dhealasach is cuingichidh sin an conaltradh – can teachdaireachdan prìobhaideach is cuid dhen cho-luadar air a’ phròifil. Mas e duilleag cuideigin chliùitich no duilleag coimhearsnachd a th’ ann, chaidh na roghainnean seo a chur an sàs gu fèin-obrachail.';
$a->strings['%s %s shared a new post'] = 'Cho-roinn %s%s post ùr';
$a->strings['Private Message'] = 'Teachdaireachd phrìobhaideach';
$a->strings['Public Message'] = 'Teachdaireachd phoblach';
$a->strings['Unlisted Message'] = 'Teachdaireachd fhalaichte o liostaichean';
$a->strings['This entry was edited'] = 'Chaidh an nì seo a dheasachadh';
$a->strings['Connector Message'] = 'Teachdaireachd ceangladair';
$a->strings['Edit'] = 'Deasaich';
$a->strings['Delete globally'] = 'Sguab às sa cho-nasgadh';
$a->strings['Remove locally'] = 'Thoir air falbh gu h-ionadail';
$a->strings['Block %s'] = 'Bac %s';
$a->strings['Save to folder'] = 'Sàbhail gu pasgan';
$a->strings['I will attend'] = 'Bidh mi an làthair';
$a->strings['I will not attend'] = 'Cha bhi mi ann';
$a->strings['I might attend'] = 'Dh’fhaoidte gum bi mi an làthair';
$a->strings['Ignore thread'] = 'Leig seachad an snàithlean';
$a->strings['Unignore thread'] = 'Na leig seachad an snàithlean tuilleadh';
$a->strings['Toggle ignore status'] = 'Toglaich staid na leigeil seachad';
$a->strings['Add star'] = 'Cuir rionnag ris';
$a->strings['Remove star'] = 'Thoir an rionnag air falbh';
$a->strings['Toggle star status'] = 'Toglaich staid na rionnaige';
$a->strings['Pin'] = 'Prìnich';
$a->strings['Unpin'] = 'Dì-phrìnich';
$a->strings['Toggle pin status'] = 'Toglaich staid a’ phrìneachaidh';
$a->strings['Pinned'] = 'Prìnichte';
$a->strings['Add tag'] = 'Cuir taga ris';
$a->strings['Quote share this'] = 'Co-roinn seo le iomradh';
$a->strings['Quote Share'] = 'Iomradh';
$a->strings['Reshare this'] = 'Co-roinn seo às ùr';
$a->strings['Reshare'] = 'Co-roinn';
$a->strings['Cancel your Reshare'] = 'Sguir dhen cho-roinneadh agad';
$a->strings['Unshare'] = 'Na co-roinn';
$a->strings['%s (Received %s)'] = '%s (air fhaighinn %s)';
$a->strings['Comment this item on your system'] = 'Thoir beachd ris an nì seo san t-siostam agad';
$a->strings['Remote comment'] = 'Beachd cèin';
$a->strings['Share via ...'] = 'Co-roinn slighe…';
$a->strings['Share via external services'] = 'Co-roinn slighe seirbheise cèine';
$a->strings['to'] = 'gu';
$a->strings['via'] = 'slighe';
$a->strings['Wall-to-Wall'] = 'Balla gu balla';
$a->strings['via Wall-To-Wall:'] = 'slighe balla bu balla:';
$a->strings['Reply to %s'] = 'Freagair gu %s';
$a->strings['More'] = 'Barrachd';
$a->strings['%d comment'] = [
	0 => '%d bheachd',
	1 => '%d bheachd',
	2 => '%d beachdan',
	3 => '%d beachd',
];
$a->strings['Show more'] = 'Seall barrachd dheth';
$a->strings['Show fewer'] = 'Seall nas lugha dheth';
$a->strings['Reshared by: %s'] = '’Ga cho-roinneadh le: %s ';
$a->strings['Viewed by: %s'] = 'Air a choimhead air le: %s';
$a->strings['Liked by: %s'] = '’S toil le %s seo';
$a->strings['Disliked by: %s'] = 'Cha toil le %s seo';
$a->strings['Attended by: %s'] = 'An làthair: %s';
$a->strings['Maybe attended by: %s'] = 'An làthair ’s dòcha: %s';
$a->strings['Not attended by: %s'] = 'Gun a bhith an làthair: %s';
$a->strings['Reacted with %s by: %s'] = 'Chaidh freagairt le %s le: %s';
$a->strings['(no subject)'] = '(gun chuspair)';
$a->strings['The folder %s must be writable by webserver.'] = 'Ceumaidh cead sgrìobhaidh dhan phasgan %s a bhith aig an fhrithealaiche-lìn.';
$a->strings['Login failed.'] = 'Dh’fhàillig leis a’ chlàradh a-steach.';
$a->strings['Login failed. Please check your credentials.'] = 'Dh’fhàillig leis a’ chlàradh a-steach. Thoir sùil air an teisteas agad.';
$a->strings['Welcome %s'] = 'Fàilte ort, %s';
$a->strings['Please upload a profile photo.'] = 'An luchdaich thu suas dealbh pròifile?';
$a->strings['OpenWebAuth: %1$s welcomes %2$s'] = 'OpenWebAuth: Tha %1$s a’ cur fàilte air %2$s';
$a->strings['Friendica Notification'] = 'Brath Friendica';
$a->strings['%1$s, %2$s Administrator'] = '%1$s, rianaire %2$s';
$a->strings['%s Administrator'] = 'Rianaire %s';
$a->strings['thanks'] = 'mòran taing';
$a->strings['YYYY-MM-DD or MM-DD'] = 'YYYY-MM-DD no MM-DD';
$a->strings['Time zone: <strong>%s</strong> <a href="%s">Change in Settings</a>'] = 'Roinn-tìde: <strong>%s</strong> <a href="%s">Atharraich i sna roghainnean</a>';
$a->strings['never'] = 'chan ann idir';
$a->strings['less than a second ago'] = 'nas lugha na diog air ais';
$a->strings['year'] = 'bhliadhna';
$a->strings['years'] = 'bliadhna(ichean)';
$a->strings['months'] = 'mìos(an)';
$a->strings['weeks'] = 'seachdain(ean)';
$a->strings['days'] = 'là(ithean)';
$a->strings['hour'] = 'uair a thìde';
$a->strings['hours'] = 'uair(ean) a thìde';
$a->strings['minute'] = 'mhionaid';
$a->strings['minutes'] = 'mionaid(ean)';
$a->strings['second'] = 'dhiog';
$a->strings['seconds'] = 'diog(an)';
$a->strings['in %1$d %2$s'] = 'an ceann %1$d %2$s';
$a->strings['%1$d %2$s ago'] = '%1$d %2$s air ais';
$a->strings['Notification from Friendica'] = 'Brath o Friendica';
$a->strings['Empty Post'] = 'Post falamh';
$a->strings['default'] = 'bunaiteach';
$a->strings['Variations'] = 'Fiamhan';
$a->strings['Note'] = 'An aire';
$a->strings['Check image permissions if all users are allowed to see the image'] = 'Thoir sùil air ceadan an deilbh ma dh’fhaodas a h-uile cleachdaiche an dealbh fhaicinn';
$a->strings['Blue'] = 'Gorm';
$a->strings['Red'] = 'Dearg';
$a->strings['Purple'] = 'Purpaidh';
$a->strings['Green'] = 'Uaine';
$a->strings['Pink'] = 'Pinc';
$a->strings['Copy or paste schemestring'] = 'Dèan lethbhreac no cuir ann sreang sgeama';
$a->strings['You can copy this string to share your theme with others. Pasting here applies the schemestring'] = '’S urrainn dhut lethbhreac dhen t-sreang seo a dhèanamh airson an t-ùrlar agad a cho-roinneadh le càch. Nuair a chuireas tu rud ann an-seo, thèid sreang an sgeama a chur an sàs';
$a->strings['Navigation bar background color'] = 'Dath cùlaibh bàr na seòladaireachd';
$a->strings['Navigation bar icon color '] = 'Dath ìomhaigheagan bàr na seòladaireachd ';
$a->strings['Link color'] = 'Dath nan ceanglaichean';
$a->strings['Set the background color'] = 'Suidhich dath a’ chùlaibh';
$a->strings['Content background opacity'] = 'Trìd-dhoilleireachd cùlaibh na susbainte';
$a->strings['Set the background image'] = 'Suidhich dealbh a’ chùlaibh';
$a->strings['Background image style'] = 'Stoidhle dealbh a’ chùlaibh';
$a->strings['Always open Compose page'] = 'Fosgail duilleag an sgrìobhaidh an-còmhnaidh';
$a->strings['Leave background image and color empty for theme defaults'] = 'Fàg dealbh ’s dath a’ chùlaibh bàn do bhun-roghainnean an ùrlair';
$a->strings['Custom'] = 'Gnàthaichte';
$a->strings['Quick Start'] = 'Grad-tòiseachadh';
