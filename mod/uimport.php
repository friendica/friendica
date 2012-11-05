<?php
/**
 * View for user import
 */

require_once("include/uimport.php");

function uimport_post(&$a) {
	switch($a->config['register_policy']) {
        case REGISTER_OPEN:
            $blocked = 0;
            $verified = 1;
            break;

        case REGISTER_APPROVE:
            $blocked = 1;
            $verified = 0;
            break;

        default:
        case REGISTER_CLOSED:
            if((! x($_SESSION,'authenticated') && (! x($_SESSION,'administrator')))) {
                notice( t('Permission denied.') . EOL );
                return;
            }
            $blocked = 1;
            $verified = 0;
            break;
	}
    
    if (x($_FILES,'accountfile')){
        // TODO: pass $blocked / $verified, send email to admin on REGISTER_APPROVE
        import_account($a, $_FILES['accountfile']);
        return;
    }
}

function uimport_content(&$a) {
    $tpl = get_markup_template("uimport.tpl");
    return replace_macros($tpl, array(
        '$regbutt' => t('Import'),
        '$import' => array(
            'title' => t("Move account"),
            'text' => t("You can move here an account from another Friendica server. <br>
                            You need to export your account form the old server and upload it here. We will create here your old account with all your contacts. We will try also to inform you friends that you moved here.<br>
                            <b>This feature is experimental. We can't move here contacts from ostatus network (statusnet/identi.ca) or from diaspora"),
            'field' => array('accountfile', t('Account file'),'<input id="id_accountfile" name="accountfile" type="file">', t('To export your accont, go to "Settings->Export your porsonal data" and select "Export account"')),
        ),  
    ));
}
