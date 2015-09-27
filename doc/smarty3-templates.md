Friendica Templating Documentation
==================================

* [Home](help)

Friendica uses [Smarty 3](http://www.smarty.net/) as PHP templating engine. The main templates are found in

		/view/templates

theme authors may overwrite the default templates by putting a files with the same name into the

		/view/themes/$themename/templates

directory.

Templates that are only used by addons shall be placed in the

		/addon/$addonname/templates

directory.

To render a template use the function *get_markup_template* to load the template and *replace_macros* to replace the macros/variables in the just loaded template file.

		$tpl = get_markup_template('install_settings.tpl');
        $o .= replace_macros($tpl, array( ... ));

the array consists of an association of an identifier and the value for that identifier, i.e.

		'$title' => $install_title,

where the value may as well be an array by its own.

Form Templates
--------------

To guarantee a consistent look and feel for input forms, i.e. in the settings sections, there are templates for the basic form fields. They are initialized with an array of data, depending on the tyle of the field.

All of these take an array for holding the values, i.e. for an one line text input field, which is required and should be used to type email addesses use something along

		'$adminmail' => array('adminmail', t('Site administrator email address'), $adminmail, t('Your account email address must match this in order to use the web admin panel.'), 'required', '', 'email'),

To evaluate the input value, you can then use the $_POST array, more precisely the $_POST['adminemail'] variable.

Listed below are the template file names, the general purpose of the template and their field parameters.

### field_checkbox.tpl

A checkbox. If the checkbox is checked its value is **1**. Field parameter:

0. Name of the checkbox,
1. Label for the checkbox,
2. State checked? if true then the checkbox will be marked as checked,
3. Help text for the checkbox.

### field_combobox.tpl

A combobox, combining a pull down selection and a textual input field. Field parameter:

0. Name of the combobox,
1. Label for the combobox,
2. Current value of the variable,
3. Help text for the combobox,
4. Array holding the possible values for the textual input,
5. Array holding the possible values for the pull down selection.

### field_custom.tpl

A customizeable template to include a custom element in the form with the usual surroundings, Field parameter:

0. Name of the field,
1. Label for the field,
2. the field,
3. Help text for the field.

### field_input.tpl

A single line input field for textual input. Field parameter:

0. Name of the field,
1. Label for the input box,
2. Current value of the variable,
3. Help text for the input box,
4. if set to "required" modern browser will check that this input box is filled when submitting the form,
5. if set to "autofocus" modern browser will put the cursur into this box once the page is loaded,
6. if set to "email" or "url" modern browser will check that the filled in value corresponds to an email address or URL.

### field_intcheckbox.tpl

A checkbox (see above) but you can define the value of it. Field parameter:

0. Name of the checkbox,
1. Label for the checkbox,
2. State checked? if true then the checkbox will be marked as checked,
3. Value of the checkbox,
4. Help text for the checkbox.

### field_openid.tpl

An input box (see above) but prepared for special CSS styling for openID input. Field parameter:

0. Name of the field,
1. Label for the input box,
2. Current value of the variable,
3. Help text for the input field.

### field_password.tpl

A single line input field (see above) for textual input. The characters typed in will not be shown by the browser. Field parameter:

0. Name of the field,
1. Label for the field,
2. Value for the field, e.g. the old password,
3. Help text for the input field,
4. if set to "required" modern browser will check that this field is filled out,
5. if set to "autofocus" modern browser will put the cursor automatically into this input field.

### field_radio.tpl

A radio button. Field parameter:

0. Name of the radio button,
1. Label for the radio button,
2. Current value of the variable,
3. Help text for the button,
4. if set, the radio button will be checked.

### field_richtext.tpl

A multi-line input field for *rich* textual content. Field parameter:

0. Name of the input field,
1. Label for the input box,
2. Current text for the box,
3. Help text for the input box.

### field_select.tpl

A drop down selection box. Field parameter:

0. Name of the field,
1. Label of the selection box,
2. Current selected value,
3. Help text for the selection box,
4. Array holding the possible values of the selection drop down.

### field_select_raw.tpl

A drop down selection box (see above) but you have to prepare the values yourself. Field parameter:

0. Name of the field,
1. Label of the selection box,
2. Current selected value,
3. Help text for the selection box,
4. Possible values of the selection drop down.

### field_textarea.tpl

A multi-line input field for (plain) textual content. Field parameter:

0. Name of the input field,
1. Label for the input box,
2. Current text for the box,
3. Help text for the input box.

### field_yesno.tpl

A button that has two states *yes* or *no*. Field parameter:

0. Name of the input field,
1. Label for the button,
2. Current value,
3. Help text for the button
4. if set to an array of two values, these two will be used, otherwise "off" and "on".
