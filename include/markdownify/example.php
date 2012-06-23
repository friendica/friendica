<?php
	error_reporting(E_ALL);
	if (!empty($_POST['input'])) {
		include 'markdownify_extra.php';
		if (!isset($_POST['leap'])) {
			$leap = MDFY_LINKS_EACH_PARAGRAPH;
		} else {
			$leap = $_POST['leap'];
		}
		
		if (!isset($_POST['keepHTML'])) {
			$keephtml = MDFY_KEEPHTML;
		} else {
			$keephtml = $_POST['keepHTML'];
		}
		if (!empty($_POST['extra'])) {
			$md = new Markdownify_Extra($leap, MDFY_BODYWIDTH, $keephtml);
		} else {
			$md = new Markdownify($leap, MDFY_BODYWIDTH, $keephtml);
		}
		if (ini_get('magic_quotes_gpc')) {
			$_POST['input'] = stripslashes($_POST['input']);
		}
		$output = $md->parseString($_POST['input']);
	} else {
		$_POST['input'] = '';
	}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US" lang="en-US">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<title>HTML to Markdown Converter</title>
	</head>
	<body>
		<?php if (empty($_POST['input'])): ?>
		<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
			<fieldset>
				<legend>HTML Input</legend>
				<textarea style="width:100%;" cols="85" rows="40" name="input"><?php echo htmlspecialchars($_POST['input'], ENT_NOQUOTES, 'UTF-8'); ?></textarea>
			</fieldset>
			<label for="extra">Markdownify Extra: <input name="extra" checked="checked" id="extra" type="checkbox" value="1"  /></label>
			<label for="leap">Links after each block elem: <input name="leap" id="leap" type="checkbox" value="1"  /></label>
			<label for="keepHTML">keep HTML: <input name="keepHTML" id="keepHTML" type="checkbox" value="1" checked="checked" /></label>
			<input type="submit" name="submit" value="submit" />
		</form>
		<?php else: ?>
		<h1 style="text-align:right;"><a href="<?php echo $_SERVER['PHP_SELF']; ?>">BACK</a></h1>
		<pre><?php echo htmlspecialchars($output, ENT_NOQUOTES, 'UTF-8'); ?></pre>
		<?php endif; ?>
	</body>
</html>