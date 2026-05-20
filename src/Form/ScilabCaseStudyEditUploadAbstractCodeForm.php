<?php

namespace Drupal\scilab_case_study\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Database\Database;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Mail\MailManager;

class ScilabCaseStudyEditUploadAbstractCodeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scilab_case_study_edit_upload_abstract_code_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $service = \Drupal::service('case_study_global');
    $form['#attributes'] = ['enctype' => "multipart/form-data"];
    /* get current proposal */
    $proposal_id = \Drupal::routeMatch()->getParameter('proposal_id');
    //$proposal_id = (int) arg(3);
    $uid = $user->uid;
    $query = \Drupal::database()->select('case_study_proposal');
    $query->fields('case_study_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    if(!$proposal_data){
      $msg = \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
      $response = new RedirectResponse(Url::fromRoute('scilab_case_study.proposal_edit_file_all')->toString());
         $response->send();
         return $msg;
    }
    
    $query = \Drupal::database()->select('case_study_submitted_abstracts');
    $query->fields('case_study_submitted_abstracts');
    $query->condition('proposal_id', $proposal_data->id);
    $abstracts_q = $query->execute()->fetchObject();
    $form['project_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->project_title,
      '#title' => t('Title of the Case Study Project'),
    ];
    $form['contributor_name'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->contributor_name,
      '#title' => t('Contributor Name'),
    ];
    $existing_uploaded_A_file = $service->default_value_for_uploaded_files("A", $proposal_data->id);
    if (!$existing_uploaded_A_file) {
      $existing_uploaded_A_file = new \stdClass();
      $existing_uploaded_A_file->filename = "No file uploaded";
    } //!$existing_uploaded_S_file
    $form['upload_case_study_final_report'] = [
      '#type' => 'file',
      '#title' => t('Upload the Final Report'),
      '#description' => t('<span style="color:red;">Current File :</span> ' . $existing_uploaded_A_file->filename . '<br />Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . t('<span style="color:red;">Allowed file extensions: ') . \Drupal::config('scilab_case_study.settings')->get('case_study_final_report_extensions') . '</span>',
    ];
$existing_uploaded_S_file = $service->default_value_for_uploaded_files("S", $proposal_data->id);
    if (!$existing_uploaded_S_file) {
      $existing_uploaded_S_file = new \stdClass();
      $existing_uploaded_S_file->filename = "No file uploaded";
    } //!$existing_uploaded_S_file
    $form['upload_case_study_developed_process'] = [
      '#type' => 'file',
      '#title' => t('Upload the Case Directory'),
      '#description' => t('<span style="color:red;">Current File :</span> ' . $existing_uploaded_S_file->filename . '<br />Separate filenames with underscore. No spaces or any special characters allowed in filename.') . '<br />' . t('<span style="color:red;">Allowed file extensions: ') . \Drupal::config('scilab_case_study.settings')->get('case_study_project_files_extensions') . '</span>',
    ];

    $form['prop_id'] = [
      '#type' => 'hidden',
      '#value' => $proposal_data->id,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    $form['cancel'] = array(
    		'#type' => 'item',
    		'#markup' => Link::fromTextAndUrl(
  'Cancel',
  Url::fromUri('internal:/case-study-project/manage-proposal/edit-upload-file')
)->toString()
    	);

    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
$service = \Drupal::service('case_study_global');
    // if (!($_FILES['files']['name']['upload_case_study_abstract']) {
    //   \Drupal::messenger()->addError('No files uploaded');
    //   return;
    // }
    // elseif (!$_FILES['files']['name']['upload_case_study_developed_process'])){

    // }
    if (isset($_FILES['files'])) {
      /* check for valid filename extensions */
      if ($_FILES['files']['name']['upload_case_study_final_report'] || $_FILES['files']['name']['upload_case_study_developed_process']) {
        foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {
          if ($file_name) {
            /* checking file type */
            if (strstr($file_form_name, 'upload_case_study_developed_process')) {
              $file_type = 'S';
            }
            else {
              if (strstr($file_form_name, 'upload_case_study_final_report')) {
                $file_type = 'A';
              }
            }
            $allowed_extensions_str = '';
            switch ($file_type) {
              case 'S':
                $allowed_extensions_str = \Drupal::config('scilab_case_study.settings')->get('case_study_project_files_extensions');
                break;
              case 'A':
                $allowed_extensions_str = \Drupal::config('scilab_case_study.settings')->get('case_study_final_report_extensions');
                break;
            } //$file_type
            $allowed_extensions = explode(',', $allowed_extensions_str);
            $tmp_ext = explode('.', strtolower($_FILES['files']['name'][$file_form_name]));
            $temp_extension = end($tmp_ext);
            if (!in_array($temp_extension, $allowed_extensions)) {
              $form_state->setErrorByName($file_form_name, t('Only file with ' . $allowed_extensions_str . ' extensions can be uploaded.'));
            }
            if ($_FILES['files']['size'][$file_form_name] <= 0) {
              $form_state->setErrorByName($file_form_name, t('File size cannot be zero.'));
            }
            /* check if valid file name */
            if (!$service->scilab_case_study_check_valid_filename($_FILES['files']['name'][$file_form_name])) {
              $form_state->setErrorByName($file_form_name, t('Invalid file name specified. Only alphabets and numbers are allowed as a valid filename.'));
            }
          } //$file_name
        } //$_FILES['files']['name'] as $file_form_name => $file_name
      } //$_FILES['files']['name'] as $file_form_name => $file_name
    }
    // else {
    //   \Drupal::messenger()->addError('No files uploaded');
    //   return $form_state;
    // }

  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $service = \Drupal::service('case_study_global');
    $v = $form_state->getValues();
    $root_path = $service->scilab_case_study_path();
    $query = \Drupal::database()->select('case_study_proposal');
    $query->fields('case_study_proposal');
    $query->condition('id', $v['prop_id']);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    $proposal_id = $proposal_data->id;
    if (!$proposal_data) {
      $msg = \Drupal::messenger()->addError('Invalid Proposal');
      $response = new RedirectResponse(Url::fromRoute('scilab_case_study.proposal_edit_file_all')->toString());
  
  // Send the redirect response
  $response->send();
      return $msg;
    } //!$proposal_data
    $proposal_id = $proposal_data->id;
    $proposal_directory = $proposal_data->directory_name;
    /* create proposal folder if not present */
    //$dest_path = $proposal_directory . '/';
    $dest_path = $proposal_directory .  '/project_files/';
    $proposal_id = $proposal_data->id;
    foreach ($_FILES['files']['name'] as $file_form_name => $file_name) {

      if ($file_name) {
        /* uploading file */
        /* checking file type */
        if (strstr($file_form_name, 'upload_case_study_final_report')) {
          $file_type = 'A';
          $abs_file_name = $_FILES['files']['name'][$file_form_name];
        }
        else {
          $abs_file_name = "Not updated";
        }
        if (strstr($file_form_name, 'upload_case_study_developed_process')) {
          $file_type = 'S';
          $proj_file_name = $_FILES['files']['name'][$file_form_name];
        }
        else {
          $proj_file_name = "Not updated";
        }
        if (file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])) {
              //unlink($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);
              move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name]);
              \Drupal::messenger()->addError(t("File @filename already exists hence overwirtten the exisitng file ", [
                '@filename' => $_FILES['files']['name'][$file_form_name]
                ]));
            } //file_exists($root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
					/* uploading file */
            else {
              if (move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])) {
                /* for uploaded files making an entry in the database */
                $query_ab_f = "SELECT * FROM case_study_submitted_abstracts_file WHERE proposal_id = :proposal_id AND filetype = 
				:filetype";
                $args_ab_f = [
                  ":proposal_id" => $proposal_id,
                  ":filetype" => $file_type,
                ];
                $query_ab_f_result = \Drupal::database()->query($query_ab_f, $args_ab_f)->fetchObject();
                if (!$query_ab_f_result) {
                  $query = "INSERT INTO case_study_submitted_abstracts_file (submitted_abstract_id, proposal_id, uid, approvar_uid, filename, filepath, filemime, filesize, filetype, timestamp)
          VALUES (:submitted_abstract_id, :proposal_id, :uid, :approvar_uid, :filename, :filepath, :filemime, :filesize, :filetype, :timestamp)";
                  $args = [
                    ":submitted_abstract_id" => $submitted_abstract_id,
                    ":proposal_id" => $proposal_id,
                    ":uid" => $user->id(),
                    ":approvar_uid" => 0,
                    ":filename" => $_FILES['files']['name'][$file_form_name],
                    ":filepath" => $_FILES['files']['name'][$file_form_name],
                    ":filemime" => mime_content_type($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]),
                    ":filesize" => filesize($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]),
                    ":filetype" => $file_type,
                    ":timestamp" => time(),
                  ];
                 // var_dump($args);die;
                 $insert_files = Database::getConnection()->insert('case_study_submitted_abstracts_file')->fields($args)->execute();
                  \Drupal::messenger()->addStatus($file_name . ' uploaded successfully.');
                } //!$query_ab_f_result
                else {
                  unlink($root_path . $dest_path . $query_ab_f_result->filename);
                  $query = "UPDATE case_study_submitted_abstracts_file SET filename = :filename, filepath=:filepath, filemime=:filemime, filesize=:filesize, timestamp=:timestamp WHERE proposal_id = :proposal_id AND filetype = :filetype";
                  $args = [
                    ":filename" => $_FILES['files']['name'][$file_form_name],
                    ":filepath" => $file_path . $_FILES['files']['name'][$file_form_name],
                    ":filemime" => mime_content_type($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]),
                    ":filesize" => filesize($root_path . $dest_path . $_FILES['files']['name'][$file_form_name]),
                    ":timestamp" => time(),
                    ":proposal_id" => $proposal_id,
                    ":filetype" => $file_type,
                  ];
                  \Drupal::database()->query($query, $args);
                  \Drupal::messenger()->addStatus($file_name . ' file updated successfully.');
                }
              } //move_uploaded_file($_FILES['files']['tmp_name'][$file_form_name], $root_path . $dest_path . $_FILES['files']['name'][$file_form_name])
              else {
                \Drupal::messenger()->addError('Error uploading file : ' . $dest_path . $file_name);
              }
            }
      }
    } //$_FILES['files']['name'] as $file_form_name => $file_name
    /* sending email */
    $email_to = $user->getEmail();
     $from = \Drupal::config('scilab_case_study.settings')->get('case_study_from_email');
    $bcc = \Drupal::config('scilab_case_study.settings')->get('case_study_emails');
    $cc = \Drupal::config('scilab_case_study.settings')->get('case_study_cc_emails');

    $params['abstract_edit_file_uploaded']['proposal_id'] = $proposal_id;
    $params['abstract_edit_file_uploaded']['abs_file'] = $abs_file_name;
    $params['abstract_edit_file_uploaded']['proj_file'] = $proj_file_name;
    $params['abstract_edit_file_uploaded']['headers'] = [
      'From' => $from,
      'MIME-Version' => '1.0',
      'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
      'Content-Transfer-Encoding' => '8Bit',
      'X-Mailer' => 'Drupal',
      'Cc' => $cc,
      'Bcc' => $bcc,
    ];
    $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();

$mailManager = \Drupal::service('plugin.manager.mail');

$result = $mailManager->mail(
  'scilab_case_study',
  'abstract_edit_file_uploaded',
  $email_to,
  $langcode,
  $params,
  $from,
  TRUE
);

if (!$result['result']) {
  \Drupal::messenger()->addError(t('Error sending email message.'));
}
    $response = new RedirectResponse(Url::fromRoute('scilab_case_study.proposal_edit_file_all')->toString());
  }

}
