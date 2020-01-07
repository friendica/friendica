<?php
/**
 * @package		Friendica\modules
 * @subpackage	FileBrowser
 * @author		Fabio Comuni <fabrixxm@kirgroup.com>
 */

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\Registry\DI;
use Friendica\Registry\App as A;
use Friendica\Util\Images;
use Friendica\Util\Strings;

/**
 * @param App $a
 * @return string
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function fbrowser_content(App $a)
{
	if (!local_user()) {
		exit();
	}

	if ($a->argc == 1) {
		exit();
	}

	// Needed to match the correct template in a module that uses a different theme than the user/site/default
	$theme = Strings::sanitizeFilePathItem($_GET['theme'] ?? null);
	if ($theme && is_file("view/theme/$theme/config.php")) {
		$a->setCurrentTheme($theme);
	}

	$template_file = "filebrowser.tpl";

	$o = '';

	switch ($a->argv[1]) {
		case "image":
			$path = [["", L10n::t("Photos")]];
			$albums = false;
			$sql_extra = "";
			$sql_extra2 = " ORDER BY created DESC LIMIT 0, 10";

			if ($a->argc==2) {
				$albums = q("SELECT distinct(`album`) AS `album` FROM `photo` WHERE `uid` = %d AND `album` != '%s' AND `album` != '%s' ",
					intval(local_user()),
					DBA::escape('Contact Photos'),
					DBA::escape(L10n::t('Contact Photos'))
				);

				function _map_folder1($el)
				{
					return [bin2hex($el['album']),$el['album']];
				};

				$albums = array_map("_map_folder1", $albums);
			}

			if ($a->argc == 3) {
				$album = hex2bin($a->argv[2]);
				$sql_extra = sprintf("AND `album` = '%s' ", DBA::escape($album));
				$sql_extra2 = "";
				$path[] = [$a->argv[2], $album];
			}

			$r = q("SELECT `resource-id`, ANY_VALUE(`id`) AS `id`, ANY_VALUE(`filename`) AS `filename`, ANY_VALUE(`type`) AS `type`,
					min(`scale`) AS `hiq`, max(`scale`) AS `loq`, ANY_VALUE(`desc`) AS `desc`, ANY_VALUE(`created`) AS `created`
					FROM `photo` WHERE `uid` = %d $sql_extra AND `album` != '%s' AND `album` != '%s'
					GROUP BY `resource-id` $sql_extra2",
				intval(local_user()),
				DBA::escape('Contact Photos'),
				DBA::escape(L10n::t('Contact Photos'))
			);

			function _map_files1($rr)
			{
				$a = DI::app();
				$types = Images::supportedTypes();
				$ext = $types[$rr['type']];
				$filename_e = $rr['filename'];

				// Take the largest picture that is smaller or equal 640 pixels
				$p = q("SELECT `scale` FROM `photo` WHERE `resource-id` = '%s' AND `height` <= 640 AND `width` <= 640 ORDER BY `resource-id`, `scale` LIMIT 1",
					DBA::escape($rr['resource-id']));
				if ($p) {
					$scale = $p[0]["scale"];
				} else {
					$scale = $rr['loq'];
				}

				return [
					A::baseUrl() . '/photos/' . $a->user['nickname'] . '/image/' . $rr['resource-id'],
					$filename_e,
					A::baseUrl() . '/photo/' . $rr['resource-id'] . '-' . $scale . '.' . $ext
				];
			}
			$files = array_map("_map_files1", $r);

			$tpl = Renderer::getMarkupTemplate($template_file);

			$o =  Renderer::replaceMacros($tpl, [
				'$type'     => 'image',
				'$path'     => $path,
				'$folders'  => $albums,
				'$files'    => $files,
				'$cancel'   => L10n::t('Cancel'),
				'$nickname' => $a->user['nickname'],
				'$upload'   => L10n::t('Upload')
			]);

			break;
		case "file":
			if ($a->argc==2) {
				$files = q("SELECT `id`, `filename`, `filetype` FROM `attach` WHERE `uid` = %d ",
					intval(local_user())
				);

				function _map_files2($rr)
				{
					list($m1, $m2) = explode("/", $rr['filetype']);
					$filetype = ( (file_exists("images/icons/$m1.png"))?$m1:"zip");
					$filename_e = $rr['filename'];

					return [A::baseUrl() . '/attach/' . $rr['id'], $filename_e, A::baseUrl() . '/images/icons/16/' . $filetype . '.png'];
				}
				$files = array_map("_map_files2", $files);


				$tpl = Renderer::getMarkupTemplate($template_file);
				$o = Renderer::replaceMacros($tpl, [
					'$type'     => 'file',
					'$path'     => [ [ "", L10n::t("Files")] ],
					'$folders'  => false,
					'$files'    => $files,
					'$cancel'   => L10n::t('Cancel'),
					'$nickname' => $a->user['nickname'],
					'$upload'   => L10n::t('Upload')
				]);
			}

			break;
	}

	if (!empty($_GET['mode'])) {
		return $o;
	} else {
		echo $o;
		exit();
	}
}
