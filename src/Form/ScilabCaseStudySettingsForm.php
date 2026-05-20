<?php

namespace Drupal\scilab_case_study\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class ScilabCaseStudySettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scilab_case_study_settings_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    
   // Use the ConfigFactory to load configuration.
$config = \Drupal::config('scilab_case_study.settings');

// Email fields.
$form['emails'] = [
  '#type' => 'textfield',
  '#title' => $this->t('(Bcc) Notification emails'),
  '#description' => $this->t('Specify email addresses for the Bcc option of the mail system, comma-separated.'),
  '#size' => 50,
  '#maxlength' => 255,
  '#required' => TRUE,
  '#default_value' => $config->get('case_study_emails') ?? '',
];

$form['cc_emails'] = [
  '#type' => 'textfield',
  '#title' => $this->t('(Cc) Notification emails'),
  '#description' => $this->t('Specify email addresses for the Cc option of the mail system, comma-separated.'),
  '#size' => 50,
  '#maxlength' => 255,
  '#required' => TRUE,
  '#default_value' => $config->get('case_study_cc_emails') ?? '',
];

$form['from_email'] = [
  '#type' => 'textfield',
  '#title' => $this->t('Outgoing from email address'),
  '#description' => $this->t('Email address to be displayed in the "From" field of all outgoing messages.'),
  '#size' => 50,
  '#maxlength' => 255,
  '#required' => TRUE,
  '#default_value' => $config->get('case_study_from_email') ?? '',
];

// File extension fields.
$form['extensions'] = [
  '#type' => 'details',
  '#title' => $this->t('Allowed file extensions'),
  '#open' => TRUE,
];

$form['extensions']['resource_upload'] = [
  '#type' => 'textfield',
  '#title' => $this->t('Allowed file extensions for uploading resource files'),
  '#description' => $this->t('A comma-separated list (WITHOUT SPACES) of file extensions permitted for upload to the server.'),
  '#size' => 50,
  '#maxlength' => 255,
  '#required' => TRUE,
  '#default_value' => $config->get('resource_upload_extensions') ?? '',
];

$form['extensions']['abstract_upload'] = [
  '#type' => 'textfield',
  '#title' => $this->t('Allowed file extensions for abstracts'),
  '#description' => $this->t('A comma-separated list (WITHOUT SPACES) of PDF file extensions permitted for upload to the server.'),
  '#size' => 50,
  '#maxlength' => 255,
  '#required' => TRUE,
  '#default_value' => $config->get('case_study_abstract_upload_extensions') ?? '',
];

$form['extensions']['case_study_upload'] = [
  '#type' => 'textfield',
  '#title' => $this->t('Allowed extensions for project files'),
  '#description' => $this->t('A comma-separated list (WITHOUT SPACES) of file extensions permitted for upload to the server.'),
  '#size' => 50,
  '#maxlength' => 255,
  '#required' => TRUE,
  '#default_value' => $config->get('case_study_project_files_extensions') ?? '',
];

$form['extensions']['case_study_final_report'] = [
  '#type' => 'textfield',
  '#title' => $this->t('Allowed extensions for Final report in Case directory submission'),
  '#description' => $this->t('A comma-separated list (WITHOUT SPACES) of file extensions permitted for upload to the server.'),
  '#size' => 50,
  '#maxlength' => 255,
  '#required' => TRUE,
  '#default_value' => $config->get('case_study_final_report_extensions') ?? '',
];

$form['extensions']['list_of_available_projects_file'] = [
  '#type' => 'textfield',
  '#title' => $this->t('Allowed file extensions for available projects list'),
  '#description' => $this->t('A comma-separated list (WITHOUT SPACES) of file extensions permitted for upload to the server.'),
  '#size' => 50,
  '#maxlength' => 255,
  '#required' => TRUE,
  '#default_value' => $config->get('list_of_available_projects_file') ?? '',
];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    return;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
  // Load the config object for your module.
  $config = \Drupal::configFactory()->getEditable('scilab_case_study.settings');

  // Set each value from the form to the config.
  $config
    ->set('case_study_emails', $form_state->getValue('emails'))
    ->set('case_study_cc_emails', $form_state->getValue('cc_emails'))
    ->set('case_study_from_email', $form_state->getValue('from_email'))
    ->set('resource_upload_extensions', $form_state->getValue('resource_upload'))
    ->set('case_study_abstract_upload_extensions', $form_state->getValue('abstract_upload'))
    ->set('case_study_project_files_extensions', $form_state->getValue('case_study_upload'))
    ->set('case_study_final_report_extensions', $form_state->getValue('case_study_final_report'))
    ->set('list_of_available_projects_file', $form_state->getValue('list_of_available_projects_file'))
    ->save();

  // Display a success message.
  $this->messenger()->addStatus($this->t('Settings updated.'));
}

}
