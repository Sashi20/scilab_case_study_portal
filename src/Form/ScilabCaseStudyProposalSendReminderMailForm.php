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

class ScilabCaseStudyProposalSendReminderMailForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scilab_case_study_proposal_send_reminder_mail_form';
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
    $form['project_title'] = [
      '#type' => 'item',
      '#markup' => $proposal_data->project_title,
      '#title' => t('Title of the Case Study Project'),
    ];
    $form['expected_completion_date'] = [
      '#type' => 'textfield',
      '#title' => t('Expected Date of Completion'),
      '#default_value' => date('d/m/Y', $proposal_data->expected_date_of_completion),
      '#disabled' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Click to send mail to the contributor'),
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
    /* get current proposal */
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
    /* set the book status to completed */
    /* sending email */
    $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
      $email_to = $user_data->getEmail();
        $config = \Drupal::config('scilab_case_study.settings');
        $from = $config->get('case_study_from_email') ?? '';
        $bcc = $config->get('case_study_emails') ?? '';
        $cc = $config->get('case_study_cc_emails') ?? '';
        $params['case_study_proposal_send_reminder_mail']['proposal_id'] = $proposal_id;
        $params['case_study_proposal_send_reminder_mail']['user_id'] = $proposal_data->uid;
        $params['case_study_proposal_send_reminder_mail']['headers'] = [
          'From' => $from,
          'MIME-Version' => '1.0',
          'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
          'Content-Transfer-Encoding' => '8Bit',
          'X-Mailer' => 'Drupal',
          'Cc' => $cc,
          'Bcc' => $bcc,
        ];
        $langcode = $user->getPreferredLangcode();
    if (!\Drupal::service('plugin.manager.mail')->mail('scilab_case_study', 'case_study_proposal_send_reminder_mail', $email_to, $langcode, $params, $form, TRUE)) {
      \Drupal::messenger()->addError('Error sending email message.');
    }
    $msg = \Drupal::messenger()->addStatus('The user has been notified about the last date of submission.');
    $response = new RedirectResponse(Url::fromRoute('scilab_case_study.proposal_all')->toString());
       $response->send();
      return $msg;

  }

}
