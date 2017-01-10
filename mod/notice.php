<?php
	/* identi.ca -> friendica items permanent-url compatibility */
	
	function notice_init(App &$a){
		$id = $a->argv[1];
		$r = q("SELECT user.nickname FROM user LEFT JOIN item ON item.uid=user.uid WHERE item.id=%d",
				intval($id)
				);
		if (dbm::is_result($r)){
			$nick = $r[0]['nickname'];
			$url = App::get_baseurl()."/display/$nick/$id";
			goaway($url);
		} else {
			$a->error = 404;
			notice( t('Item not found.') . EOL);

		}
		return;

	}
