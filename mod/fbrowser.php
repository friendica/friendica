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
	
	//echo "<pre>"; var_dump($a->argv); killme();	
	
	switch($a->argv[1]){
		case "image":
			$path = array( array($a->get_baseurl()."/fbrowser/image/", t("Photos")));
			$albums = false;
			$sql_extra = "";
			$sql_extra2 = " ORDER BY created DESC LIMIT 0, 10";
			
			if ($a->argc==2){
				$albums = q("SELECT distinct(`album`) AS `album` FROM `photo` WHERE `uid` = %d ",
					intval(local_user())
				);
				// anon functions only from 5.3.0... meglio tardi che mai..
				function folder1($el){return array(bin2hex($el['album']),$el['album']);}	
				$albums = array_map( "folder1" , $albums);
				
			}
			
			$album = "";
			if ($a->argc==3){
				$album = hex2bin($a->argv[2]);
				$sql_extra = sprintf("AND `album` = '%s' ",dbesc($album));
				$sql_extra2 = "";
				$path[]=array($a->get_baseurl()."/fbrowser/image/".$a->argv[2]."/", $album);
			}
				
			$r = q("SELECT `resource-id`, `id`, `filename`, type, min(`scale`) AS `hiq`,max(`scale`) AS `loq`, `desc`  
					FROM `photo` WHERE `uid` = %d $sql_extra
					GROUP BY `resource-id` $sql_extra2",
				intval(local_user())					
			);
			
			function files1($rr){ 
				global $a;
				$types = Photo::supportedTypes();
				$ext = $types[$rr['type']];
				return array( 
					$a->get_baseurl() . '/photo/' . $rr['resource-id'] . '-' . $rr['hiq'] . '.' .$ext, 
					template_escape($rr['filename']), 
					$a->get_baseurl() . '/photo/' . $rr['resource-id'] . '-' . $rr['loq'] . '.'. $ext
				);
			}
			$files = array_map("files1", $r);
			
			$tpl = get_markup_template("filebrowser.tpl");
			echo replace_macros($tpl, array(
				'$type' => 'image',
				'$baseurl' => $a->get_baseurl(),
				'$path' => $path,
				'$folders' => $albums,
				'$files' =>$files,
			));
				
				
			break;
		case "file":
			if ($a->argc==2){
				$files = q("SELECT id, filename, filetype FROM `attach` WHERE `uid` = %d ",
					intval(local_user())
				);
				
				function files2($rr){ global $a; 
					list($m1,$m2) = explode("/",$rr['filetype']);
					$filetype = ( (file_exists("images/icons/$m1.png"))?$m1:"zip");
					return array( $a->get_baseurl() . '/attach/' . $rr['id'], template_escape($rr['filename']), $a->get_baseurl() . '/images/icons/16/' . $filetype . '.png'); 
				}
				$files = array_map("files2", $files);
				//echo "<pre>"; var_dump($files); killme();
			
							
				$tpl = get_markup_template("filebrowser.tpl");
				echo replace_macros($tpl, array(
					'$type' => 'file',
					'$baseurl' => $a->get_baseurl(),
					'$path' => array( array($a->get_baseurl()."/fbrowser/image/", t("Files")) ),
					'$folders' => false,
					'$files' =>$files,
				));
				
			}
		
			break;
	}
	

	killme();
	
}
