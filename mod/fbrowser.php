<?php
/**
 * @package		Friendica\modules
 * @subpackage	FileBrowser
 * @author		Fabio Comuni <fabrixxm@kirgroup.com>
 */

require_once('include/Photo.php');

/**
 * @param App $a
 */
function fbrowser_content($a){

	if (!local_user())
		killme();

	if ($a->argc==1)
		killme();

	$template_file = "filebrowser.tpl";
	$mode = "";
	if (x($_GET,'mode')) {
		$template_file = "filebrowser_plain.tpl";
		$mode  = "?mode=".$_GET['mode'];
	}

	//echo "<pre>"; var_dump($a->argv); killme();

	switch($a->argv[1]){
		case "image":
			$path = array( array("", t("Photos")));
			$albums = false;
			$sql_extra = "";
			$sql_extra2 = " ORDER BY created DESC LIMIT 0, 10";

			if ($a->argc==2){
				$albums = q("SELECT distinct(`album`) AS `album` FROM `photo` WHERE `uid` = %d AND `album` != '%s' AND `album` != '%s' ",
					intval(local_user()),
					dbesc('Contact Photos'),
					dbesc( t('Contact Photos'))
				);

				function _map_folder1($el){return array(bin2hex($el['album']),$el['album']);};
				$albums = array_map( "_map_folder1" , $albums);

			}

			$album = "";
			if ($a->argc==3){
				$album = hex2bin($a->argv[2]);
				$sql_extra = sprintf("AND `album` = '%s' ",dbesc($album));
				$sql_extra2 = "";
				$path[]=array($a->argv[2], $album);
			}

			$r = q("SELECT `resource-id`, `id`, `filename`, type, min(`scale`) AS `hiq`,max(`scale`) AS `loq`, `desc`
					FROM `photo` WHERE `uid` = %d $sql_extra AND `album` != '%s' AND `album` != '%s'
					GROUP BY `resource-id` $sql_extra2",
				intval(local_user()),
				dbesc('Contact Photos'),
				dbesc( t('Contact Photos'))
			);

			function _map_files1($rr){
				global $a;
				$types = Photo::supportedTypes();
				$ext = $types[$rr['type']];

				if($a->theme['template_engine'] === 'internal') {
					$filename_e = template_escape($rr['filename']);
				}
				else {
					$filename_e = $rr['filename'];
				}

				return array(
					$a->get_baseurl() . '/photos/' . $a->user['nickname'] . '/image/' . $rr['resource-id'],
					$filename_e,
					$a->get_baseurl() . '/photo/' . $rr['resource-id'] . '-' . $rr['loq'] . '.'. $ext
				);
			}
			$files = array_map("_map_files1", $r);

			$tpl = get_markup_template($template_file);

			$o =  replace_macros($tpl, array(
				'$type' => 'image',
				'$baseurl' => $a->get_baseurl(),
				'$path' => $path,
				'$folders' => $albums,
				'$files' =>$files,
				'$cancel' => t('Cancel'),
				'$nickname' => $a->user['nickname'],
			));


			break;
		case "file":
			if ($a->argc==2){
				$files = q("SELECT `id`, `filename`, `filetype` FROM `attach` WHERE `uid` = %d ",
					intval(local_user())
				);

				function _map_files2($rr){ global $a;
					list($m1,$m2) = explode("/",$rr['filetype']);
					$filetype = ( (file_exists("images/icons/$m1.png"))?$m1:"zip");

					if($a->theme['template_engine'] === 'internal') {
						$filename_e = template_escape($rr['filename']);
					}
					else {
						$filename_e = $rr['filename'];
					}

					return array( $a->get_baseurl() . '/attach/' . $rr['id'], $filename_e, $a->get_baseurl() . '/images/icons/16/' . $filetype . '.png');
				}
				$files = array_map("_map_files2", $files);


				$tpl = get_markup_template($template_file);
				$o = replace_macros($tpl, array(
					'$type' => 'file',
					'$baseurl' => $a->get_baseurl(),
					'$path' => array( array( "", t("Files")) ),
					'$folders' => false,
					'$files' =>$files,
					'$cancel' => t('Cancel'),
					'$nickname' => $a->user['nickname'],
				));

			}

			break;
	}

	if (x($_GET,'mode')) {
		return $o;
	} else {
		echo $o;
		killme();
	}


}
