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

class ScilabCaseStudyProposalApprovalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scilab_case_study_proposal_approval_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    $proposal_id = \Drupal::routeMatch()->getParameter('proposal_id');
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
    $form['contributor_contact_no'] = [
      '#title' => t('Contact No.'),
      '#type' => 'item',
      '#markup' => $proposal_data->contact_no,
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
      '#title' => t('Scilab Version used'),
    ];
    $form['date_of_proposal'] = [
      '#type' => 'textfield',
      '#title' => t('Date of Proposal'),
      '#default_value' => date('d/m/Y', $proposal_data->creation_date),
      '#disabled' => TRUE,
    ];
    $form['expected_completion_date'] = [
      '#type' => 'textfield',
      '#title' => t('Expected Date of Completion'),
      '#default_value' => date('d/m/Y', $proposal_data->expected_date_of_completion),
      '#disabled' => TRUE,
    ];
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
    $form['approval'] = [
      '#type' => 'radios',
      '#title' => t('Select an action for the case study'),
      '#options' => [
        '1' => 'Approve',
        '2' => 'Disapprove',
      ],
      '#required' => TRUE,
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => t('Reason for disapproval'),
      '#attributes' => [
        'placeholder' => t('Enter reason for disapproval in minimum 30 characters '),
        'cols' => 50,
        'rows' => 4,
      ],
      '#states' => [
        'visible' => [
          ':input[name="approval"]' => [
            'value' => '2'
            ]
          ],
          'required' => [
          ':input[name="approval"]' => [
            'value' => '2'
            ]
          ]
        ],
    ];
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

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // if ($form_state->getValue(['approval']) == 2) {
    //   if ($form_state->getValue(['message']) == '') {
    //     $form_state->setErrorByName('message', t('Reason for disapproval could not be empty'));
    //   } //$form_state['values']['message'] == ''
    // } //$form_state['values']['approval'] == 2
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    $proposal_id = \Drupal::routeMatch()->getParameter('proposal_id');
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
    if ($form_state->getValue(['approval']) == 1) {
      $query = "UPDATE case_study_proposal SET approver_uid = :uid, approval_date = :date, approval_status = 1 WHERE id = :proposal_id";
      $args = [
        ":uid" => $user->id(),
        ":date" => time(),
        ":proposal_id" => $proposal_id,
      ];
      \Drupal::database()->query($query, $args);
      /* sending email */
      $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
      $email_to = $user_data->getEmail();
      $config = \Drupal::config('scilab_case_study.settings');
      $from = $config->get('case_study_from_email') ?? '';
      $bcc = $config->get('case_study_emails') ?? '';
      $cc = $config->get('case_study_cc_emails') ?? '';

      $params['case_study_proposal_approved']['proposal_id'] = $proposal_id;
      $params['case_study_proposal_approved']['user_id'] = $proposal_data->uid;
      $params['case_study_proposal_approved']['headers'] = [
        'From' => $from,
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
        'Content-Transfer-Encoding' => '8Bit',
        'X-Mailer' => 'Drupal',
        'Cc' => $cc,
        'Bcc' => $bcc,
      ];
       $langcode = $user->getPreferredLangcode();
    if (!\Drupal::service('plugin.manager.mail')->mail('scilab_case_study', 'case_study_proposal_approved', $email_to, $langcode, $params, $form, TRUE)) {
      \Drupal::messenger()->addError('Error sending email message.');
    }

     $msg = \Drupal::messenger()->addStatus('Case Study with the proposal No. ' . $proposal_id . ' has been approved and the user is notified of the approval via email.');
      $response = new RedirectResponse(Url::fromRoute('scilab_case_study.proposal_pending')->toString());
         $response->send();
        //drupal_goto('circuit-simulation-project/manage-proposal');
        return $msg;
    } //$form_state['values']['approval'] == 1
    else {
      if ($form_state->getValue(['approval']) == 2) {
        $query = "UPDATE case_study_proposal SET approver_uid = :uid, approval_date = :date, approval_status = 2, dissapproval_reason = :dissapproval_reason WHERE id = :proposal_id";
        $args = [
          ":uid" => $user->id(),
          ":date" => time(),
          ":dissapproval_reason" => $form_state->getValue(['message']),
          ":proposal_id" => $proposal_id,
        ];
        $result = \Drupal::database()->query($query, $args);
        /* sending email */
        $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
        $email_to = $user_data->getEmail();
        $config = \Drupal::config('scilab_case_study.settings');
        $from = $config->get('case_study_from_email') ?? '';
        $bcc = $config->get('case_study_emails') ?? '';
        $cc = $config->get('case_study_cc_emails') ?? '';
        $params['case_study_proposal_disapproved']['proposal_id'] = $proposal_id;
        $params['case_study_proposal_disapproved']['user_id'] = $proposal_data->uid;
        $params['case_study_proposal_disapproved']['headers'] = [
          'From' => $from,
          'MIME-Version' => '1.0',
          'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
          'Content-Transfer-Encoding' => '8Bit',
          'X-Mailer' => 'Drupal',
          'Cc' => $cc,
          'Bcc' => $bcc,
        ];
        $langcode = $user->getPreferredLangcode();
    if (!\Drupal::service('plugin.manager.mail')->mail('scilab_case_study', 'case_study_proposal_disapproved', $email_to, $langcode, $params, $form, TRUE)) {
      \Drupal::messenger()->addError('Error sending email message.');
    }
        $msg = \Drupal::messenger()->addError('Case Study with the proposal No. ' . $proposal_id . ' is dis-approved and the useris notified of the dis-approval via email.');
        $response = new RedirectResponse(Url::fromRoute('scilab_case_study.proposal_pending')->toString());
         $response->send();
        //drupal_goto('circuit-simulation-project/manage-proposal');
        return $msg;
      }
    } //$form_state['values']['approval'] == 2
  }

}
