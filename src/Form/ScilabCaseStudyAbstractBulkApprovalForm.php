<?php

namespace Drupal\scilab_case_study\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Database\Database;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ScilabCaseStudyAbstractBulkApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scilab_case_study_abstract_bulk_approval_form';
  }
  function _bulk_list_of_case_study_project() {
  $project_titles = [
    0 => 'Please select...'
  ];

  // Access the database connection.
  $connection = Database::getConnection();

  // Build the query.
  $query = $connection->select('case_study_proposal', 'p')
    ->fields('p')
    ->condition('is_submitted', 1)
    ->condition('approval_status', 1)
    ->orderBy('project_title', 'ASC');

  // Execute the query and fetch the results.
  $results = $query->execute();

  foreach ($results as $record) {
    $project_titles[$record->id] = $record->project_title . ' (Proposed by ' . $record->contributor_name . ')';
  }

  return $project_titles;
}
function _bulk_list_case_study_actions() {
  // Define the actions as an associative array.
  $case_study_actions = [
    0 => 'Please select...',
    1 => 'Approve Entire Case Study Project',
    2 => 'Resubmit Project files',
    3 => 'Disapprove Entire Case Study Project (This will delete Case Study Project)',
    // Uncomment the following line if needed in the future:
    // 4 => 'Delete Entire Case Study Project Including Proposal',
  ];

  return $case_study_actions;
}
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $options_first = $this->_bulk_list_of_case_study_project();
    $selected = !$form_state->getValue(['case_study_project']) ? $form_state->getValue(['case_study_project']) : key($options_first);
    $form = [];
    $form['case_study_project'] = [
      '#type' => 'select',
      '#title' => t('Title of the Case Study Project'),
      '#options' => $this->_bulk_list_of_case_study_project(),
      '#default_value' => $selected,
      '#ajax' => [
        'callback' => '::ajax_bulk_case_study_abstract_details_callback',
        'wrapper' => 'ajax_selected_case_study'
        ],
    ];
    $form['update_case_study_details'] = [
  '#type' => 'container',
  '#attributes' => ['id' => 'ajax_selected_case_study'],
  '#states' => [
    'visible' => [
      ':input[name="case_study_project"]' => ['!value' => 0]
    ],
  ],
];
    $form['update_case_study_details']['cs_details'] = [
      '#type' => 'markup',
      '#markup' => $this->_case_study_details($form_state->getValue('case_study_project')),

    ];
    $form['update_case_study_details']['case_study_actions'] = [
      '#type' => 'select',
      '#title' => t('Please select action for Case Study Project'),
      '#options' => $this->_bulk_list_case_study_actions(),
      '#default_value' => 0,

    ];
    $form['update_case_study_details']['message'] = [
      '#type' => 'textarea',
      '#title' => t('Specify the reason for resubmission/ disapproval.'),
      '#states' => [
        'visible' => [
          [':input[name="case_study_actions"]' => ['value' => 3]],
          [':input[name="case_study_actions"]' => ['value' => 2]]
        ],
        'required' => [
          [':input[name="case_study_actions"]' => ['value' => 3]],
          [':input[name="case_study_actions"]' => ['value' => 2]]
        ]
        ],
    ];
    $form['update_case_study_details']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    return $form;
  }

  function ajax_bulk_case_study_abstract_details_callback(array &$form, FormStateInterface $form_state) {
    return $form['update_case_study_details'];
}

  function _case_study_details($case_study_proposal_id) {
//var_dump($case_study_proposal_id);die;
  $return_html = '';

  // Get the proposal details.
  $connection = Database::getConnection();

  $query_pro = $connection->select('case_study_proposal', 'p')
    ->fields('p')
    ->condition('id', $case_study_proposal_id);
  $abstracts_pro = $query_pro->execute()->fetchObject();

  // Get the abstract PDF file.
  $query_pdf = $connection->select('case_study_submitted_abstracts_file', 'f')
    ->fields('f')
    ->condition('proposal_id', $case_study_proposal_id)
    ->condition('filetype', 'A');
  $abstracts_pdf = $query_pdf->execute()->fetchObject();

  $abstract_filename = 'File not uploaded';
  if ($abstracts_pdf && !empty($abstracts_pdf->filename) && $abstracts_pdf->filename !== 'NULL') {
    $abstract_filename = $abstracts_pdf->filename;
  }

  // Get the circuit simulation process file.
  $query_process = $connection->select('case_study_submitted_abstracts_file', 'f')
    ->fields('f')
    ->condition('proposal_id', $case_study_proposal_id)
    ->condition('filetype', 'S');
  $abstracts_query_process = $query_process->execute()->fetchObject();

  $abstracts_query_process_filename = 'File not uploaded';
  if ($abstracts_query_process && !empty($abstracts_query_process->filename) && $abstracts_query_process->filename !== 'NULL') {
    $abstracts_query_process_filename = $abstracts_query_process->filename;
  } 

  // Get additional abstract submission details.
  $query = $connection->select('case_study_submitted_abstracts', 'a')
    ->fields('a')
    ->condition('proposal_id', $case_study_proposal_id);
  $abstracts_q = $query->execute()->fetchObject();

  if ($abstracts_q && $abstracts_q->is_submitted == 0) {
    // Optional message if the abstract is not submitted.
    // drupal_set_message($this->t('Abstract is not submitted yet.'), 'error');
  }

  // Download link for the Case Study Project.
  $download_url = Url::fromUri('internal:/case-study-project/full-download/project/' . $case_study_proposal_id);
  $download_case_study = Link::fromTextAndUrl('Download Case Study Project', $download_url)->toString();

  // Build the HTML output.
  $return_html .= '<strong>Contirbutor Name:</strong><br />' . htmlspecialchars($abstracts_pro->name_title . ' ' . $abstracts_pro->contributor_name) . '<br /><br />';
  $return_html .= '<strong>Title of the Case Study Project:</strong><br />' . $abstracts_pro->project_title . '<br /><br />';
  $return_html .= '<strong>Uploaded an abstract (brief outline) of the project:</strong><br />' . $abstract_filename . '<br /><br />';
  $return_html .= '<strong>Uploaded Case study files:</strong><br />' . $abstracts_query_process_filename . '<br /><br />';
  $return_html .= $download_case_study;

  return $return_html;
}
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $service = \Drupal::service('case_study_global');
    $user = \Drupal::currentUser();
    $msg = '';
    $root_path = $service->scilab_case_study_path();
    
    if (\Drupal::currentUser()->hasPermission('Case Study bulk manage abstract')) {
      
      $query = \Drupal::database()->select('case_study_proposal');
      $query->fields('case_study_proposal');
      $query->condition('id', $form_state->getValue(['case_study_project']));
      $user_query = $query->execute();
      $user_info = $user_query->fetchObject();
      $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($user_info->uid);
      if ($form_state->getValue(['case_study_actions']) == 1) {
        // approving entire project //
        //var_dump($form_state->getValue(['case_study_project']));die;
        $query = \Drupal::database()->select('case_study_submitted_abstracts');
        $query->fields('case_study_submitted_abstracts');
        $query->condition('proposal_id', $form_state->getValue(['case_study_project']));
        $abstracts_q = $query->execute();
        //var_dump($abstracts_q);die;
        //$experiment_list = '';
        while ($abstract_data = $abstracts_q->fetchObject()) {
          \Drupal::database()->query("UPDATE case_study_submitted_abstracts SET abstract_approval_status = 1, is_submitted = 1, approver_uid = :approver_uid WHERE id = :id", [
            ':approver_uid' => $user->id(),
            ':id' => $abstract_data->id,
          ]);
          \Drupal::database()->query("UPDATE case_study_submitted_abstracts_file SET file_approval_status = 1, approvar_uid = :approver_uid WHERE submitted_abstract_id = :submitted_abstract_id", [
            ':approver_uid' => $user->id(),
            ':submitted_abstract_id' => $abstract_data->id,
          ]);
        } //$abstract_data = $abstracts_q->fetchObject()

        $email_to = $user_data->getEmail();

    $from = \Drupal::config('scilab_case_study.settings')->get('case_study_from_email');
    $bcc = \Drupal::config('scilab_case_study.settings')->get('case_study_emails');
    $cc = \Drupal::config('scilab_case_study.settings')->get('case_study_cc_emails');

    $params['solution_approved']['proposal_id'] = $form_state->getValue(['case_study_project']);
    //$params['solution_approved']['submitted_abstract_id'] = $submitted_abstract_id;
    $params['solution_approved']['user_id'] = $user_info->uid;
    $params['solution_approved']['headers'] = [
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

$result = $mailManager->mail('scilab_case_study','solution_approved',$email_to, $langcode, $params, $from, TRUE);

if (!$result['result']) {
  \Drupal::messenger()->addError(t('Error sending email.'));
}
else {
  \Drupal::messenger()->addStatus(t('Mail sent successfully.'));
}
        \Drupal::messenger()->addStatus(t('Approved Case Study Project. Use the checkbox below to publish this case study on the completed case studies page.'));
        $response = new RedirectResponse(Url::fromUri('internal:/case-study-project/manage-proposal/status/' . $user_info->id)->toString());
              $response->send();
      } //$form_state['values']['case_study_actions'] == 1
      elseif ($form_state->getValue(['case_study_actions']) == 2) {
        //pending review entire project 
        $query = \Drupal::database()->select('case_study_submitted_abstracts');
        $query->fields('case_study_submitted_abstracts');
        $query->condition('proposal_id', $form_state->getValue(['case_study_project']));
        $abstracts_q = $query->execute();
        $experiment_list = '';
        while ($abstract_data = $abstracts_q->fetchObject()) {
          \Drupal::database()->query("UPDATE case_study_submitted_abstracts SET abstract_approval_status = 0, is_submitted = 0, approver_uid = :approver_uid WHERE id = :id", [
            ':approver_uid' => $user->id(),
            ':id' => $abstract_data->id,
          ]);
          \Drupal::database()->query("UPDATE case_study_proposal SET is_submitted = 0, approver_uid = :approver_uid WHERE id = :id", [
            ':approver_uid' => $user->id(),
            ':id' => $abstract_data->proposal_id,
          ]);
          \Drupal::database()->query("UPDATE case_study_submitted_abstracts_file SET file_approval_status = 0, approvar_uid = :approver_uid WHERE submitted_abstract_id = :submitted_abstract_id", [
            ':approver_uid' => $user->id(),
            ':submitted_abstract_id' => $abstract_data->id,
          ]);
        } //$abstract_data = $abstracts_q->fetchObject()
        $email_to = $user_data->getEmail();

    $from = \Drupal::config('scilab_case_study.settings')->get('case_study_from_email');
    $bcc = \Drupal::config('scilab_case_study.settings')->get('case_study_emails');
    $cc = \Drupal::config('scilab_case_study.settings')->get('case_study_cc_emails');

    $params['solution_resubmission']['proposal_id'] = $form_state->getValue(['case_study_project']);
    $params['solution_resubmission']['disapproval_message'] = $form_state->getValue(['message']);
    //$params['solution_approved']['submitted_abstract_id'] = $submitted_abstract_id;
    $params['solution_resubmission']['user_id'] = $user_info->uid;
    $params['solution_resubmission']['headers'] = [
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

$result = $mailManager->mail('scilab_case_study','solution_resubmission',$email_to, $langcode, $params, $from, TRUE);

if (!$result['result']) {
  \Drupal::messenger()->addError(t('Error sending email.'));
}
else {
  \Drupal::messenger()->addStatus(t('Mail sent successfully.'));
}
        \Drupal::messenger()->addStatus(t('Files marked for resubmission'));
      
      }
      				elseif ($form_state->getValue(['case_study_actions']) == 3) //disapprove and delete entire Case Study Project
      				{
      					// if (strlen(trim($form_state['values']['message'])) <= 30)
      					// {
      					// 	form_set_error('message', t(''));
      					// 	$msg = \Drupal::messenger()->addError("Please mention the reason for disapproval. Minimum 30 character required");
      					// 	return $msg;
      					// } //strlen(trim($form_state['values']['message'])) <= 30
      					if (!\Drupal::currentUser()->hasPermission('Case Study bulk delete abstract'))
      					{
      						$msg = \Drupal::messenger()->addError(t('You do not have permission to Bulk Dis-Approved and Deleted Entire Lab.'));
      						return $msg;
      					} //!user_access('case_study bulk delete code')
      					else if ($service->scilab_case_study_abstract_delete_project($form_state->getValue(['case_study_project']))) //////
      					{
                  
      						//\Drupal::messenger()->addStatus(t('Dis-Approved and Deleted Entire Case Study Project.'));
                  $email_to = $user_data->getEmail();

    $from = \Drupal::config('scilab_case_study.settings')->get('case_study_from_email');
    $bcc = \Drupal::config('scilab_case_study.settings')->get('case_study_emails');
    $cc = \Drupal::config('scilab_case_study.settings')->get('case_study_cc_emails');
//var_dump($user_info);die;
$proposal_id = $user_info->id;
    $params['solution_disapproved']['proposal_id'] = $proposal_id;;
    $params['solution_disapproved']['disapproval_message'] = $form_state->getValue(['message']);
    //$params['solution_approved']['submitted_abstract_id'] = $submitted_abstract_id;
    $params['solution_disapproved']['user_id'] = $user_info->uid;
    $params['solution_disapproved']['headers'] = [
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

$result = $mailManager->mail('scilab_case_study','solution_disapproved',$email_to, $langcode, $params, $from, TRUE);

if (!$result['result']) {
  \Drupal::messenger()->addError(t('Error sending email.'));
}
else {
  \Drupal::messenger()->addStatus(t('Mail sent successfully.'));
}
\Drupal::database()->delete('case_study_submitted_abstracts_file')->condition('proposal_id', $proposal_id)->execute();
                  \Drupal::database()->delete('case_study_submitted_abstracts')->condition('proposal_id', $proposal_id)->execute();
                  \Drupal::database()->delete('case_study_proposal')->condition('id', $proposal_id)->execute();
        \Drupal::messenger()->addStatus(t('Dis-Approved and Deleted Entire Case Study Project.'));
                }
      					else
      					{
      						\Drupal::messenger()->addError(t('Error Dis-Approving and Deleting Entire Case Study Project.'));
      					}
      					// email 
      }
      //return $msg;
    }
  }

}
