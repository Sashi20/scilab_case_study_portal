<?php
// $Id$
function gui_hackathon_settings_form($form, $form_state)
{
	$form['emails'] = array(
		'#type' => 'textfield',
		'#title' => t('(Bcc) Notification emails'),
		'#description' => t('Specify emails id for Bcc option of mail system with comma separated'),
		'#size' => 50,
		'#maxlength' => 255,
		'#required' => TRUE,
		'#default_value' => variable_get('gui_hackathon_emails', '')
	);
	$form['cc_emails'] = array(
		'#type' => 'textfield',
		'#title' => t('(Cc) Notification emails'),
		'#description' => t('Specify emails id for Cc option of mail system with comma separated'),
		'#size' => 50,
		'#maxlength' => 255,
		'#required' => TRUE,
		'#default_value' => variable_get('gui_hackathon_cc_emails', '')
	);
	$form['from_email'] = array(
		'#type' => 'textfield',
		'#title' => t('Outgoing from email address'),
		'#description' => t('Email address to be display in the from field of all outgoing messages'),
		'#size' => 50,
		'#maxlength' => 255,
		'#required' => TRUE,
		'#default_value' => variable_get('gui_hackathon_from_email', '')
	);
	$form['extensions']['final_submission'] = array(
		'#type' => 'textfield',
		'#title' => t('Allowed file extensions for uploading resource files'),
		'#description' => t('A comma separated list WITHOUT SPACE of source file extensions that are permitted to be uploaded on the server'),
		'#size' => 50,
		'#maxlength' => 255,
		'#required' => TRUE,
		'#default_value' => variable_get('final_submission_extensions', '')
	);
	$form['extensions']['gui_hackathon_start_date'] = array(
        '#type' => 'textfield',
        '#title' => t('GUI Hackathon start date'),
        '#description' => t('For eg: 2026-07-31 23:59:59.0, enter in this format'),
        '#size' => 50,
        '#maxlength' => 255,
        '#required' => TRUE,
        '#default_value' => variable_get('gui_hackathon_start_date', '')
    );
    $form['extensions']['gui_hackathon_last_date'] = array(
        '#type' => 'textfield',
        '#title' => t('GUI Hackathon Last date'),
        '#description' => t('For eg: 2026-07-31 23:59:59.0, enter in this format'),
        '#size' => 50,
        '#maxlength' => 255,
        '#required' => TRUE,
        '#default_value' => variable_get('gui_hackathon_last_date', '')
    );
	
	$form['submit'] = array(
		'#type' => 'submit',
		'#value' => t('Submit')
	);
	return $form;
}
function gui_hackathon_settings_form_validate($form, &$form_state)
{
	return;
}
function gui_hackathon_settings_form_submit($form, &$form_state)
{
	variable_set('gui_hackathon_emails', $form_state['values']['emails']);
	variable_set('gui_hackathon_cc_emails', $form_state['values']['cc_emails']);
	variable_set('gui_hackathon_from_email', $form_state['values']['from_email']);
	variable_set('final_submission_extensions', $form_state['values']['final_submission']);
	variable_set('gui_hackathon_start_date', $form_state['values']['gui_hackathon_start_date']);
	variable_set('gui_hackathon_last_date', $form_state['values']['gui_hackathon_last_date']);
	drupal_set_message(t('Settings updated'), 'status');
}
