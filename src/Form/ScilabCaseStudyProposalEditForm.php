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

class ScilabCaseStudyProposalEditForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scilab_case_study_proposal_edit_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    $proposal_id = \Drupal::routeMatch()->getParameter('proposal_id');
    //var_dump($proposal_id);die;
    $service = \Drupal::service('case_study_global');
    $query = \Drupal::database()->select('case_study_proposal');
    $query->fields('case_study_proposal');
    $query->condition('id', $proposal_id);
    $proposal_q = $query->execute();
    $proposal_data = $proposal_q->fetchObject();
    //var_dump($proposal_data);die;
    
      if (!$proposal_data) {
       $msg = \Drupal::messenger()->addError(t('Invalid proposal. Please try again.'));
        $response = new RedirectResponse(Url::fromRoute('scilab_case_study.proposal_all')->toString());
         $response->send();
        //drupal_goto('circuit-simulation-project/manage-proposal');
        return $msg;
      } //$proposal_data = $proposal_q->fetchObject()
      
    $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
    $form['name_title'] = [
      '#type' => 'select',
      '#title' => t('Title'),
      '#options' => [
        'Dr' => 'Dr',
        'Prof' => 'Prof',
        'Mr' => 'Mr',
        'Ms' => 'Ms',
      ],
      '#required' => TRUE,
      '#default_value' => $proposal_data->name_title,
    ];
    $form['contributor_name'] = [
      '#type' => 'textfield',
      '#title' => t('Name of the Proposer'),
      //'#size' => 30,
      '#maxlength' => 50,
      '#required' => TRUE,
      '#default_value' => $proposal_data->contributor_name,
    ];
    $form['student_email_id'] = [
      '#type' => 'item',
      '#title' => t('Email'),
      '#markup' => $user_data->getEmail(),
    ];
    $form['university'] = [
      '#type' => 'textfield',
      '#title' => t('University/Institute'),
      //'#size' => 200,
      '#maxlength' => 200,
      '#required' => TRUE,
      '#default_value' => $proposal_data->university,
    ];
    $form['institute'] = [
      '#type' => 'textfield',
      '#title' => t('Institute'),
      //'#size' => 80,
      '#maxlength' => 200,
      '#required' => TRUE,
      '#default_value' => $proposal_data->institute,
    ];
    $form['faculty_name'] = [
      '#type' => 'textfield',
      '#title' => t('Name of the Faculty'),
      //'#size' => 50,
      '#maxlength' => 50,
      '#validated' => TRUE,
      '#default_value' => $proposal_data->faculty_name,
    ];
    $form['faculty_department'] = [
      '#type' => 'textfield',
      '#title' => t('Department of the Faculty'),
      //'#size' => 50,
      '#maxlength' => 50,
      '#validated' => TRUE,
      '#default_value' => $proposal_data->faculty_department,
    ];
    $form['faculty_email'] = [
      '#type' => 'textfield',
      '#title' => t('Email id of the Faculty'),
      //'#size' => 255,
      '#maxlength' => 255,
      '#validated' => TRUE,
      '#default_value' => $proposal_data->faculty_email,
    ];
    $form['country'] = [
      '#type' => 'select',
      '#title' => t('Country'),
      '#options' => [
        'India' => 'India',
        'Others' => 'Others',
      ],
      '#default_value' => $proposal_data->country,
      '#required' => TRUE,
      '#tree' => TRUE,
      '#validated' => TRUE,
    ];
    $form['other_country'] = [
      '#type' => 'textfield',
      '#title' => t('Other than India'),
      //'#size' => 100,
      '#default_value' => $proposal_data->country,
      '#attributes' => [
        'placeholder' => t('Enter your country name')
        ],
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['other_state'] = [
      '#type' => 'textfield',
      '#title' => t('State other than India'),
      //'#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your state/region name')
        ],
      '#default_value' => $proposal_data->state,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['other_city'] = [
      '#type' => 'textfield',
      '#title' => t('City other than India'),
      //'#size' => 100,
      '#attributes' => [
        'placeholder' => t('Enter your city name')
        ],
      '#default_value' => $proposal_data->city,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'Others'
            ]
          ]
        ],
    ];
    $form['all_state'] = [
      '#type' => 'select',
      '#title' => t('State'),
      '#options' => $service->_cs_list_of_states(),
      '#default_value' => $proposal_data->state,
      '#validated' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'India'
            ]
          ]
        ],
    ];
    $form['city'] = [
      '#type' => 'select',
      '#title' => t('City'),
      '#options' => $service->_cs_list_of_cities(),
      '#default_value' => $proposal_data->city,
      '#states' => [
        'visible' => [
          ':input[name="country"]' => [
            'value' => 'India'
            ]
          ]
        ],
    ];
    $form['pincode'] = [
      '#type' => 'textfield',
      '#title' => t('Pincode'),
      //'#size' => 30,
      '#maxlength' => 6,
      '#default_value' => $proposal_data->pincode,
      '#attributes' => [
        'placeholder' => 'Insert pincode of your city/ village....'
        ],
    ];
    $form['project_title'] = [
      '#type' => 'textfield',
      '#title' => t('Title of the Case Study Project'),
      //'#size' => 300,
      '#maxlength' => 350,
      '#required' => TRUE,
      '#default_value' => $proposal_data->project_title,
    ];
    $form['operating_system'] = [
      '#type' => 'textfield',
      '#title' => t('Operating System'),
      '#default_value' => $proposal_data->operating_system,
    ];
    $form['scilab_version'] = [
      '#type' => 'textfield',
      '#title' => t('Scilab Version used'),
      '#default_value' => $proposal_data->scilab_version,
    ];
    $form['date_of_proposal'] = [
      '#type' => 'textfield',
      '#title' => t('Date of Proposal'),
      '#default_value' => date('d/m/Y', $proposal_data->creation_date),
      '#disabled' => TRUE,
    ];
    $form['delete_proposal'] = [
      '#type' => 'checkbox',
      '#title' => t('Delete Proposal'),
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

  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $user = \Drupal::currentUser();
    /* get current proposal */
    $service = \Drupal::service('case_study_global');
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
    /* delete proposal */
    if ($form_state->getValue(['delete_proposal']) == 1) {
      /* sending email */
      $user_data = \Drupal::entityTypeManager()->getStorage('user')->load($proposal_data->uid);
      $email_to = $user_data->getEmail();
        $config = \Drupal::config('scilab_case_study.settings');
        $from = $config->get('case_study_from_email') ?? '';
        $bcc = $config->get('case_study_emails') ?? '';
        $cc = $config->get('case_study_cc_emails') ?? '';
        $params['case_study_proposal_deleted']['proposal_id'] = $proposal_id;
        $params['case_study_proposal_deleted']['user_id'] = $proposal_data->uid;
        $params['case_study_proposal_deleted']['headers'] = [
          'From' => $from,
          'MIME-Version' => '1.0',
          'Content-Type' => 'text/plain; charset=UTF-8; format=flowed; delsp=yes',
          'Content-Transfer-Encoding' => '8Bit',
          'X-Mailer' => 'Drupal',
          'Cc' => $cc,
          'Bcc' => $bcc,
        ];
        $langcode = $user->getPreferredLangcode();
    if (!\Drupal::service('plugin.manager.mail')->mail('scilab_case_study', 'case_study_proposal_deleted', $email_to, $langcode, $params, $form, TRUE)) {
      \Drupal::messenger()->addError('Error sending email message.');
    }

      if ($service->rrmdir_project($proposal_id) == TRUE) {
        $query = \Drupal::database()->delete('case_study_proposal');
        $query->condition('id', $proposal_id);
        $num_deleted = $query->execute();
        \Drupal::messenger()->addStatus(t('Proposal Deleted'));
        $response = new RedirectResponse(Url::fromRoute('scilab_case_study.proposal_all')->toString());
         $response->send();
        return;
      } //rrmdir_project($proposal_id) == TRUE
    } //$form_state['values']['delete_proposal'] == 1
    /* update proposal */
    $v = $form_state->getValues();
    $project_title = $v['project_title'];
    $proposar_name = $v['name_title'] . ' ' . $v['contributor_name'];
    $university = $v['university'];
    $directory_names = $service->_cs_dir_name($project_title, $proposar_name);
    if ($service->CS_RenameDir($proposal_id, $directory_names)) {
      $directory_name = $directory_names;
    } //LM_RenameDir($proposal_id, $directory_names)
    else {
      return;
    }
    $str = substr($proposal_data->samplefilepath, strrpos($proposal_data->samplefilepath, '/'));
    $resource_file = ltrim($str, '/');
    $samplefilepath = $directory_name . '/' . $resource_file;
    $query = "UPDATE case_study_proposal SET
				name_title=:name_title,
				contributor_name=:contributor_name,
				university=:university,
				institute=:institute,
				faculty_name = :faculty_name,
				faculty_department = :faculty_department,
				faculty_email = :faculty_email,
				city=:city,
				pincode=:pincode,
				state=:state,
				project_title=:project_title,
                operating_system=:operating_system,
                scilab_version=:scilab_version,
				directory_name=:directory_name,
                samplefilepath = :samplefilepath
				WHERE id=:proposal_id";
    $args = [
      ':name_title' => $v['name_title'],
      ':contributor_name' => $v['contributor_name'],
      ':university' => $v['university'],
      ":institute" => $v['institute'],
      ":faculty_name" => $v['faculty_name'],
      ":faculty_department" => $v['faculty_department'],
      ":faculty_email" => $v['faculty_email'],
      ':city' => $v['city'],
      ':pincode' => $v['pincode'],
      ':state' => $v['all_state'],
      ':project_title' => $project_title,
      ':operating_system' => $v['operating_system'],
      ':scilab_version' => $v['scilab_version'],
      ':directory_name' => $directory_name,
      ':samplefilepath' => $samplefilepath,
      ':proposal_id' => $proposal_id,
    ];
    $result = \Drupal::database()->query($query, $args);
    \Drupal::messenger()->addStatus(t('Proposal Updated'));
  }

}
