<?php

namespace Drupal\scilab_case_study\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Database\Database;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class ScilabCaseStudyProposalStatusForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scilab_case_study_proposal_status_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    $proposal_id = \Drupal::routeMatch()->getParameter('proposal_id');
    $query = \Drupal::database()->select('case_study_proposal');
    $query->fields('case_study_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    $query_abstract = \Drupal::database()->select('case_study_submitted_abstracts_file');
    $query_abstract->fields('case_study_submitted_abstracts_file');
    $query_abstract->condition('proposal_id', $proposal_id);
    $query_abstract->condition('filetype', 'A');
    $query_abstract_pdf = $query_abstract->execute()->fetchObject();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        /* everything ok */
      } //$proposal_data = $proposal_q->fetchObject()
      else {
        $msg = \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
        $response = new RedirectResponse(Url::fromRoute('scilab_case_study.proposal_all')->toString());
         $response->send();
        //drupal_goto('circuit-simulation-project/manage-proposal');
        return $msg;
      }
    } //$proposal_q
    else {
      $msg = \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
      $response = new RedirectResponse(Url::fromRoute('scilab_case_study.proposal_all')->toString());
       $response->send();
      return $msg;
    }
    if ($proposal_data->faculty_name == '') {
      $faculty_name = 'NA';
    }
    else {
      $faculty_name = $proposal_data->faculty_name;
    }
    if ($proposal_data->faculty_department == '') {
      $faculty_department = 'NA';
    }
    else {
      $faculty_department = $proposal_data->faculty_department;
    }
    if ($proposal_data->faculty_email == '') {
      $faculty_email = 'NA';
    }
    else {
      $faculty_email = $proposal_data->faculty_email;
    }
   $form['contributor_name'] = [
    		'#type' => 'item',
    		'#markup' => Link::fromTextAndUrl(
  $proposal_data->name_title . ' ' . $proposal_data->contributor_name,
  Url::fromRoute('entity.user.canonical', ['user' => $proposal_data->uid])
)->toString(),
    		'#title' => t('Student name')
    	];

    $form['student_email_id'] = [
      '#title' => t('Student Email'),
      '#type' => 'item',
      '#markup' => \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid)->getEmail(),
      '#title' => t('Email'),
    ];
    $form['contact_no'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->contact_no,
      '#title' => t('Contact no'),
    ];
    $form['university'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->university,
      '#title' => t('University'),
    ];
    $form['institute'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->institute,
      '#title' => t('Institute'),
    ];
    $form['how_did_you_know_about_project'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->how_did_you_know_about_project,
      '#title' => t('How did you know about the project'),
    ];
    $form['faculty_name'] = [
      '#type' => 'item',
      '#markup' => $faculty_name,
      '#title' => t('Name of the faculty'),
    ];
    $form['faculty_department'] = [
      '#type' => 'item',
      '#markup' => $faculty_department,
      '#title' => t('Department of the faculty'),
    ];
    $form['faculty_email'] = [
      '#type' => 'item',
      '#markup' => $faculty_email,
      '#title' => t('Email of the faculty'),
    ];
    $form['country'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->country,
      '#title' => t('Country'),
    ];
    $form['all_state'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->state,
      '#title' => t('State'),
    ];
    $form['city'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->city,
      '#title' => t('City'),
    ];
    $form['pincode'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->pincode,
      '#title' => t('Pincode/Postal code'),
    ];
    $form['project_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->project_title,
      '#title' => t('Title of the Case Study Project'),
    ];
    $form['operating_system'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->operating_system,
      '#title' => t('Operating System'),
    ];
    $form['scilab_version'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->scilab_version,
      '#title' => t('Version used'),
    ];
    /************************** reference link filter *******************/
    /*    $url = '~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i';
    $reference = preg_replace($url, '<a href="$0" target="_blank" title="$0">$0</a>', $proposal_data->reference);*/
    /******************************/
    /*$form['reference'] = array(
    '#type' => 'item',
    '#markup' => $reference,
    '#title' => t('References')
    );*/
    if (($proposal_data->samplefilepath != "") && ($proposal_data->samplefilepath != 'NULL')) {
      $str = substr($proposal_data->samplefilepath, strrpos($proposal_data->samplefilepath, '/'));
      $resource_file = ltrim($str, '/');

      $resource_file_link = Link::fromTextAndUrl(
  $resource_file,
  Url::fromUri('internal:/case-study-project/download/proposal-abstract/' . $proposal_id)
)->toString();
      $form['abstract_file_path'] = array(
                  '#type' => 'item',
                  '#title' => t('Abstract file '),
                  '#markup' => $resource_file_link,
              );

    } //$proposal_data->user_defined_compound_filepath != ""
    else {
      $form['abstract_file_path'] = [
        '#type' => 'item',
        '#title' => t('Abstract file '),
        '#markup' => "Not uploaded<br><br>",
      ];
    }
    $proposal_status = '';
    switch ($proposal_data->approval_status) {
      case 0:
        $proposal_status = t('Pending');
        break;
      case 1:
        $proposal_status = t('Approved');
        break;
      case 2:
        $proposal_status = t('Dis-approved');
        break;
      case 3:
        $proposal_status = t('Completed');
        break;
      case 5:
        $approval_status = t('On Hold');
        break;
      default:
        $proposal_status = t('Unkown');
        break;
    }
    $form['proposal_status'] = [
      '#type' => 'item',
      '#markup' => $proposal_status,
      '#title' => t('Proposal Status'),
    ];
    if ($proposal_data->approval_status == 0) {
      
      $form['approve'] = [
  '#type' => 'link',
  '#title' => Link::fromTextAndUrl(
    $this->t('Click here'),
    Url::fromUri('internal:/case-study-project/manage-proposal/approve/' . $proposal_id)
  ),
  // '#attributes' => [
  //   'class' => ['button'], // Optional: Add button styling
  // ],
];

    } //$proposal_data->approval_status == 0
    if ($proposal_data->approval_status == 1) {
      $form['completed'] = [
        '#type' => 'checkbox',
        '#title' => t('Completed'),
        '#description' => t('Check if user has provided all the required files and pdfs.'),
      ];
    } //$proposal_data->approval_status == 1
    if ($proposal_data->approval_status == 2) {
      $form['message'] = [
        '#type' => 'item',
        '#markup' => $proposal_data->message,
        '#title' => t('Reason for disapproval'),
      ];
    } //$proposal_data->approval_status == 2
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    $form['cancel'] = array(
    		'#type' => 'item',
    		'#markup' => Link::fromTextAndUrl(
  t('Cancel'),
  Url::fromUri('internal:/case-study-project/manage-proposal/all')
)->toString()
    	);

    return $form;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    $service = \Drupal::service('case_study_global');
    $proposal_id = \Drupal::routeMatch()->getParameter('proposal_id');
    
    //$proposal_q = db_query("SELECT * FROM {case_study_proposal} WHERE id = %d", $proposal_id);
    $query = \Drupal::database()->select('case_study_proposal');
    $query->fields('case_study_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    if ($proposal_q) {
      if ($proposal_data = $proposal_q->fetchObject()) {
        /* everything ok */
      } //$proposal_data = $proposal_q->fetchObject()
      else {
        $msg = \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
        $response = new RedirectResponse(Url::fromRoute('scilab_case_study.proposal_pending')->toString());
         $response->send();
        //drupal_goto('circuit-simulation-project/manage-proposal');
        return $msg;
      }
    } //$proposal_q
    else {
      $msg = \Drupal::messenger()->addError(t('Invalid proposal selected. Please try again.'));
      $response = new RedirectResponse(Url::fromRoute('scilab_case_study.proposal_pending')->toString());
       $response->send();
      return $msg;
    }
    /* set the book status to completed */
    if ($form_state->getValue(['completed']) == 1) {
      $up_query = "UPDATE case_study_proposal SET approval_status = :approval_status , actual_completion_date = :expected_completion_date WHERE id = :proposal_id";
      $args = [
        ":approval_status" => '3',
        ":proposal_id" => $proposal_id,
        ":expected_completion_date" => time(),
      ];
      $result = \Drupal::database()->query($up_query, $args);
      $service->CreateReadmeFileCaseStudyProject($proposal_id);
      if (!$result) {
        \Drupal::messenger()->addError('Error in update status');
        return;
      } //!$result
      /* sending email */
      $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
      $email_to = $user_data->getEmail();
        $config = \Drupal::config('scilab_case_study.settings');
        $from = $config->get('case_study_from_email') ?? '';
        $bcc = $config->get('case_study_emails') ?? '';
        $cc = $config->get('case_study_cc_emails') ?? '';
        $params['case_study_proposal_completed']['proposal_id'] = $proposal_id;
        $params['case_study_proposal_completed']['user_id'] = $proposal_data->uid;
        $params['case_study_proposal_completed']['headers'] = [
          'From' => $from,
          'MIME-Version' => '1.0',
          'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
          'Content-Transfer-Encoding' => '8Bit',
          'X-Mailer' => 'Drupal',
          'Cc' => $cc,
          'Bcc' => $bcc,
        ];
        $langcode = $user->getPreferredLangcode();
    if (!\Drupal::service('plugin.manager.mail')->mail('scilab_case_study', 'case_study_proposal_completed', $email_to, $langcode, $params, $form, TRUE)) {
      \Drupal::messenger()->addError('Error sending email message.');
    }

      \Drupal::messenger()->addStatus('Scilab Case Study proposal has been marked as completed. User has been notified of the completion.');
    
   $response = new RedirectResponse(Url::fromRoute('scilab_case_study.proposal_pending')->toString());
         $response->send();
        //drupal_goto('circuit-simulation-project/manage-proposal');
        return $msg;
    }
  }

}
