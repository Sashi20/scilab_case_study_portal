<?php

namespace Drupal\scilab_case_study\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Database\Database;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ScilabCaseStudyRunForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scilab_case_study_run_form';
  }

 function _list_of_case_study() {
  $case_study_titles = [
    '0' => $this->t('Please select...')
  ];

  // Database query to get approved case studies
  $query = Database::getConnection()->select('case_study_proposal', 'csp');
  $query->fields('csp', ['id', 'project_title', 'name_title', 'contributor_name']);
  $query->condition('approval_status', 3);
  $query->orderBy('project_title', 'ASC');

  $results = $query->execute();

  foreach ($results as $row) {
    $case_study_titles[$row->id] = $this->t(
      '@project_title (Proposed by @name_title @contributor_name)',
      [
        '@project_title' => $row->project_title,
        '@name_title' => $row->name_title,
        '@contributor_name' => $row->contributor_name,
      ]
    );
  }

  return $case_study_titles;
}

function _case_study_information($proposal_id) {
  $query = Database::getConnection()->select('case_study_proposal', 'csp');
  $query->fields('csp');
  $query->condition('id', $proposal_id);
  $query->condition('approval_status', 3);

  $case_study_data = $query->execute()->fetchObject();

  return $case_study_data ?: $this->t('Not found');
}
function _case_study_details($case_study_default_value) {
  $case_study_details = $this->_case_study_information($case_study_default_value);
  if ($case_study_default_value != 0 && $case_study_details) {
    // Process the reference link if available.
    $reference = !empty($case_study_details->reference)
      ? preg_replace(
          '~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i',
          '<a href="$0" target="_blank" title="$0">$0</a>',
          $case_study_details->reference
        )
      : 'Not provided';

    // Generate the title link using Url and Link.
    $title_url = Url::fromUri('internal:/case-study-project/full-download/project/' . $case_study_default_value);
    $title_link = Link::fromTextAndUrl($case_study_details->project_title, $title_url)->toString();

    // Build the markup.
    $markup = '<span style="color: rgb(128, 0, 0);"><strong>About the Case Study</strong></span><br /><ul>';
    $markup .= '<li><strong>Contributor Name:</strong> ' . $case_study_details->name_title . ' ' . $case_study_details->contributor_name . '</li>';
    $markup .= '<li><strong>Title of the Case Study:</strong> ' . $title_link . '</li>';
    $markup .= '<li><strong>University:</strong> ' . $case_study_details->university . '</li>';
    $markup .= '<li><strong>Operating System:</strong> ' . $case_study_details->operating_system . '</li>';
    $markup .= '<li><strong>Scilab Version:</strong> ' . $case_study_details->scilab_version . '</li>';
    if (!empty($case_study_details->project_guide_name)) {
      $markup .= '<li><strong>Project Guide Name:</strong> ' . $case_study_details->project_guide_name . '</li>';
    }
    $markup .= '<li><strong>Reference:</strong> ' . $reference . '</li>';
    $markup .= '</ul>';

    return $markup;
  }

  return 'No details available.';
}

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $options_first = $this->_list_of_case_study();
    $url_case_study_id = \Drupal::routeMatch()->getParameter('proposal_id');
    $case_study_data = $this->_case_study_information($url_case_study_id);
    if ($case_study_data == 'Not found') {
      $url_case_study_id = '';
    } //$case_study_data == 'Not found'
    if (!$url_case_study_id) {
      $selected = !$form_state->getValue(['case_study']) ? $form_state->getValue(['case_study']) : key($options_first);
    } //!$url_case_study_id
    elseif ($url_case_study_id == '') {
      $selected = 0;
    } //$url_case_study_id == ''
    else {
      $selected = $url_case_study_id;
    }
    $form = [];
    $form['case_study'] = [
      '#type' => 'select',
      '#title' => t('Title of the case study'),
      '#options' => $this->_list_of_case_study(),
      '#default_value' => $selected,
      '#ajax' => [
        'callback' => '::ajaxProjectDetailsCallback',
        'wrapper' => 'ajax_selected_case_study'
        ],
      // '#ajax' => [
      //   'callback' => 'case_study_project_details_callback'
      //   ],
    ];
    $form['update_case_study'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'ajax_selected_case_study'],
      '#states' => [
        'invisible' => [
          ':input[name="case_study"]' => [
            'value' => 0
            ]
          ]
        ],
    ];
$case_study_default_value = $form_state->getValue('case_study') ?: $selected;
    $form['update_case_study']['cs_details'] = [
      '#type' => 'markup',
      '#markup' => $this->_case_study_details($case_study_default_value),
    ];
     $form['update_case_study']['download_abstract'] = [
      '#type' => 'item',
      '#markup' => Link::fromTextAndUrl('Download Abstract', Url::fromUri('internal:/case-study-project/download/final-report/' . $case_study_default_value))->toString() .
              '<br>' .
              Link::fromTextAndUrl('Download Case Study', Url::fromUri('internal:/case-study-project/full-download/project/' . $case_study_default_value))->toString(),
    ];
    return $form;
  }
public function ajaxProjectDetailsCallback(array &$form, FormStateInterface $form_state) {
return $form['update_case_study'];
}
  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
  }

}
