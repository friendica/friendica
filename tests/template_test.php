<?php
/**
 * this file contains tests for the template engine
 *
 * @package test.util
 */

/** required, it is the file under test */
require_once('include/template_processor.php');
require_once('include/text.php');

class TemplateMockApp {
	public $theme_info=array();
}

if(!function_exists('current_theme')) {
function current_theme() {
	return 'clean';
}
}

if(!function_exists('x')) {
function x($s,$k = NULL) {
	return false;
}
}

if(!function_exists('get_app')) {
function get_app() {
	return new TemplateMockApp();
}
}

/**
 * TestCase for the template engine
 *
 * @author Alexander Kampmann
 * @package test.util
 */
class TemplateTest extends PHPUnit_Framework_TestCase {

	public function setUp() {
		global $t;
		$t=new Template;
	}

	public function testListToShort() {
		@list($first, $second)=array('first');

		$this->assertTrue(is_null($second));
	}

	public function testSimpleVariableString() {
		$tpl='Hello $name!';

		$text=replace_macros($tpl, array('$name'=>'Anna'));

		$this->assertEquals('Hello Anna!', $text);
	}

	public function testSimpleVariableInt() {
		$tpl='There are $num new messages!';

		$text=replace_macros($tpl, array('$num'=>172));

		$this->assertEquals('There are 172 new messages!', $text);
	}

	public function testConditionalElse() {
		$tpl='There{{ if $num!=1 }} are $num new messages{{ else }} is 1 new message{{ endif }}!';

		$text1=replace_macros($tpl, array('$num'=>1));
		$text22=replace_macros($tpl, array('$num'=>22));

		$this->assertEquals('There is 1 new message!', $text1);
		$this->assertEquals('There are 22 new messages!', $text22);
	}

	public function testConditionalNoElse() {
		$tpl='{{ if $num!=0 }}There are $num new messages!{{ endif }}';

		$text0=replace_macros($tpl, array('$num'=>0));
		$text22=replace_macros($tpl, array('$num'=>22));

		$this->assertEquals('', $text0);
		$this->assertEquals('There are 22 new messages!', $text22);
	}

	public function testConditionalFail() {
		$tpl='There {{ if $num!=1 }} are $num new messages{{ else }} is 1 new message{{ endif }}!';

		$text1=replace_macros($tpl, array());

		//$this->assertEquals('There is 1 new message!', $text1);
	}

	public function testSimpleFor() {
		$tpl='{{ for $messages as $message }} $message {{ endfor }}';

		$text=replace_macros($tpl, array('$messages'=>array('message 1', 'message 2')));

		$this->assertEquals(' message 1  message 2 ', $text);
	}

	public function testFor() {
		$tpl='{{ for $messages as $message }} from: $message.from to $message.to {{ endfor }}';

		$text=replace_macros($tpl, array('$messages'=>array(array('from'=>'Mike', 'to'=>'Alex'), array('from'=>'Alex', 'to'=>'Mike'))));

		$this->assertEquals(' from: Mike to Alex  from: Alex to Mike ', $text);
	}
	
	public function testKeyedFor() {
		$tpl='{{ for $messages as $from=>$to }} from: $from to $to {{ endfor }}';
	
		$text=replace_macros($tpl, array('$messages'=>array('Mike'=>'Alex', 'Sven'=>'Mike')));
	
		$this->assertEquals(' from: Mike to Alex  from: Sven to Mike ', $text);
	}

	public function testForEmpty() {
		$tpl='messages: {{for $messages as $message}} from: $message.from to $message.to  {{ endfor }}';

		$text=replace_macros($tpl, array('$messages'=>array()));

		$this->assertEquals('messages: ', $text);
	}

	public function testForWrongType() {
		$tpl='messages: {{for $messages as $message}} from: $message.from to $message.to  {{ endfor }}';

		$text=replace_macros($tpl, array('$messages'=>11));

		$this->assertEquals('messages: ', $text);
	}

	public function testForConditional() {
		$tpl='new messages: {{for $messages as $message}}{{ if $message.new }} $message.text{{endif}}{{ endfor }}';

		$text=replace_macros($tpl, array('$messages'=>array(
				array('new'=>true, 'text'=>'new message'),
				array('new'=>false, 'text'=>'old message'))));

		$this->assertEquals('new messages:  new message', $text);
	}
	
	public function testConditionalFor() {
		$tpl='{{ if $enabled }}new messages:{{for $messages as $message}} $message.text{{ endfor }}{{endif}}';
	
		$text=replace_macros($tpl, array('$enabled'=>true, 
				'$messages'=>array(
				array('new'=>true, 'text'=>'new message'),
				array('new'=>false, 'text'=>'old message'))));
	
		$this->assertEquals('new messages: new message old message', $text);
	}

	public function testFantasy() {
		$tpl='Fantasy: {{fantasy $messages}}';

		$text=replace_macros($tpl, array('$messages'=>'no no'));

		$this->assertEquals('Fantasy: {{fantasy no no}}', $text);
	}

	public function testInc() {
		$tpl='{{inc field_input.tpl with $field=$myvar}}{{ endinc }}';

		$text=replace_macros($tpl, array('$myvar'=>array('myfield', 'label', 'value', 'help')));

		$this->assertEquals("	\n"
				."	<div class='field input'>\n"
				."		<label for='id_myfield'>label</label>\n"
				."		<input name='myfield' id='id_myfield' value=\"value\">\n"
				."		<span class='field_help'>help</span>\n"
				."	</div>\n", $text);
	}

	public function testIncNoVar() {
		$tpl='{{inc field_input.tpl }}{{ endinc }}';

		$text=replace_macros($tpl, array('$field'=>array('myfield', 'label', 'value', 'help')));

		$this->assertEquals("	\n	<div class='field input'>\n		<label for='id_myfield'>label</label>\n"
				."		<input name='myfield' id='id_myfield' value=\"value\">\n"
				."		<span class='field_help'>help</span>\n"
				."	</div>\n", $text);
	}
	
	public function testDoubleUse() {
		$tpl='Hello $name! {{ if $enabled }} I love you! {{ endif }}';
	
		$text=replace_macros($tpl, array('$name'=>'Anna', '$enabled'=>false));
	
		$this->assertEquals('Hello Anna! ', $text);
		
		$tpl='Hey $name! {{ if $enabled }} I hate you! {{ endif }}';
		
		$text=replace_macros($tpl, array('$name'=>'Max', '$enabled'=>true));
		
		$this->assertEquals('Hey Max!  I hate you! ', $text);
	}
	
	public function testIncDouble() {
		$tpl='{{inc field_input.tpl with $field=$var1}}{{ endinc }}'
		.'{{inc field_input.tpl with $field=$var2}}{{ endinc }}';
	
		$text=replace_macros($tpl, array('$var1'=>array('myfield', 'label', 'value', 'help'), 
				'$var2'=>array('myfield2', 'label2', 'value2', 'help2')));
		
		$this->assertEquals("	\n"
				."	<div class='field input'>\n"
				."		<label for='id_myfield'>label</label>\n"
				."		<input name='myfield' id='id_myfield' value=\"value\">\n"
				."		<span class='field_help'>help</span>\n"
				."	</div>\n"
				."	\n"
				."	<div class='field input'>\n"
				."		<label for='id_myfield2'>label2</label>\n"
				."		<input name='myfield2' id='id_myfield2' value=\"value2\">\n"
				."		<span class='field_help'>help2</span>\n"
				."	</div>\n", $text);
	}
}