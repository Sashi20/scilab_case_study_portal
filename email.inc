<?php
/**
 * Implementation of hook_mail().
 */
function scilab_case_study_mail($key, &$message, $params)
{
    global $user;
    $language = $message['language'];
    //$language = user_preferred_language($user);
    switch ($key) {
        case 'case_study_proposal_received':
            /* initializing data */
            $query = db_select('case_study_proposal');
            $query->fields('case_study_proposal');
            $query->condition('id', $params['case_study_proposal_received']['result1']);
            $query->range(0, 1);
            $proposal_data = $query->execute()->fetchObject();
            $user = user_load($params['case_study_proposal_received']['user_id']);
            $message['headers'] = $params['case_study_proposal_received']['headers'];
            $message['subject'] = t('[!site_name][Case Study Project] Your Case Study Project proposal has been received', array(
                '!site_name' => variable_get('site_name', ''),
            ), array(
                'language' => $language->language,
            ));
            $message['body'] = array(
                'body' => t('
Dear ' . $proposal_data->contributor_name . ',

We have received your Case Study Project proposal with the following details:

Full Name: ' . $proposal_data->name_title . ' ' . $proposal_data->contributor_name . '
Email: ' . $user->mail . '
University/Institute: ' . $proposal_data->university . '
City: ' . $proposal_data->city . '
State: ' . $proposal_data->state . '
Country: ' . $proposal_data->country . '
Project Title: ' . $proposal_data->project_title . '
Date of Proposal: ' . date('d/m/Y', $proposal_data->creation_date) . '
Expected Date of Completion: ' . date('d/m/Y', $proposal_data->expected_date_of_completion) . '

Your proposal is under review. You will soon receive an email when same has been approved/disapproved.

Best Wishes,
!site_name Team,
FOSSEE, IIT Bombay', array(
                    '!site_name' => variable_get('site_name', ''),
                    '!user_name' => $user->name,
                ), array(
                    'language' => $language->language,
                )),
            );
            break;
            case 'gui_hackathon_final_submission':
            /* initializing data */
            $query = db_select('gui_hackathon_final_submission');
            $query->fields('gui_hackathon_final_submission');
            $query->condition('id', $params['gui_hackathon_final_submission']['submission_id']);
            $query->range(0, 1);
            $proposal_data = $query->execute()->fetchObject();
            $user = user_load($params['gui_hackathon_final_submission']['user_id']);
            $message['headers'] = $params['gui_hackathon_final_submission']['headers'];
            $message['subject'] = t('[!site_name][GUI Hackathon] Your final submission has been received', array(
                '!site_name' => variable_get('site_name', ''),
            ), array(
                'language' => $language->language,
            ));
            $message['body'] = array(
                'body' => t('
Dear ' . $proposal_data->contributor_name . ',

We have received your final submission for GUI Hackathon with the following details:

Full Name: ' . $proposal_data->contributor_name . '
Email: ' . $user->mail . '
Project Title: ' . $proposal_data->project_title . '
Revision: ' . $proposal_data->revision . '

You will soon receive an email when same has been approved/disapproved.

Best Wishes,
!site_name Team,
FOSSEE, IIT Bombay', array(
                    '!site_name' => variable_get('site_name', ''),
                    '!user_name' => $user->name,
                ), array(
                    'language' => $language->language,
                )),
            );
            break;
        case 'case_study_proposal_disapproved':
            /* initializing data */
            $query = db_select('case_study_proposal');
            $query->fields('case_study_proposal');
            $query->condition('id', $params['case_study_proposal_disapproved']['proposal_id']);
            $query->range(0, 1);
            $proposal_data = $query->execute()->fetchObject();
            $user_data = user_load($params['case_study_proposal_disapproved']['user_id']);
            //$user = user_load($params['case_study_proposal']['name']);
            $message['headers'] = $params['case_study_proposal_disapproved']['headers'];
            $message['subject'] = t('[!site_name][Case Study Project] Your Case Study Project proposal has been disapproved', array(
                '!site_name' => variable_get('site_name', ''),
            ), array(
                'language' => $language->language,
            ));
            $message['body'] = array(
                'body' => t('
Dear ' . $proposal_data->contributor_name . ',

We regret to inform you that your Case Study proposal with following details have been disapproved:

Full Name: ' . $proposal_data->name_title . ' ' . $proposal_data->contributor_name . '
Email: ' . $user_data->mail . '
University/Institute: ' . $proposal_data->university . '
City: ' . $proposal_data->city . '
State: ' . $proposal_data->state . '
Country: ' . $proposal_data->country . '
Project Title: ' . $proposal_data->project_title . '
Date of Proposal: ' . date('d/m/Y', $proposal_data->creation_date) . '
Expected Date of Completion: ' . date('d/m/Y', $proposal_data->expected_date_of_completion) . '

Reason for rejection: ' . $proposal_data->dissapproval_reason . '


You are welcome to submit a new proposal.

Best Wishes,
!site_name Team,
FOSSEE, IIT Bombay', array(
                    '!site_name' => variable_get('site_name', ''),
                    '!user_name' => $user_data->name,
                ), array(
                    'language' => $language->language,
                )),
            );
            break;
        case 'case_study_proposal_approved':
            /* initializing data */
            $url = 'https://scilab.in/case-study-project/abstract-code';
            $query = db_select('case_study_proposal');
            $query->fields('case_study_proposal');
            $query->condition('id', $params['case_study_proposal_approved']['proposal_id']);
            $query->range(0, 1);
            $proposal_data = $query->execute()->fetchObject();
            $user_data = user_load($params['case_study_proposal_approved']['user_id']);
            $message['headers'] = $params['case_study_proposal_approved']['headers'];
            $message['subject'] = t('[!site_name][Case Study Project] Your Case Study Project proposal has been approved', array(
                '!site_name' => variable_get('site_name', ''),
            ), array(
                'language' => $language->language,
            ));
            $message['body'] = array(
                'body' => t('
Dear ' . $proposal_data->contributor_name . ',

Your Case Study Project proposal with the following details has been approved:

Full Name: ' . $proposal_data->name_title . ' ' . $proposal_data->contributor_name . '
Email: ' . $user_data->mail . '
University/Institute: ' . $proposal_data->university . '
City: ' . $proposal_data->city . '
State: ' . $proposal_data->state . '
Country: ' . $proposal_data->country . '
Project Title: ' . $proposal_data->project_title . '
Date of Proposal: ' . date('d/m/Y', $proposal_data->creation_date) . '
Expected Date of Completion: ' . date('d/m/Y', $proposal_data->expected_date_of_completion) . '


You can upload your project files at: ' . $url . '

Best Wishes,
!site_name Team,
FOSSEE, IIT Bombay', array(
                    '!site_name' => variable_get('site_name', ''),
                    '!user_name' => $user_data->name,
                ), array(
                    'language' => $language->language,
                )),
            );
            break;
        case 'case_study_proposal_completed':
            /* initializing data */
            $url = 'https://scilab.in/case-study-project/completed-case-studies';
            $query = db_select('case_study_proposal');
            $query->fields('case_study_proposal');
            $query->condition('id', $params['case_study_proposal_completed']['proposal_id']);
            $query->range(0, 1);
            $proposal_data = $query->execute()->fetchObject();
            $user_data = user_load($params['case_study_proposal_completed']['user_id']);
            $message['headers'] = $params['case_study_proposal_completed']['headers'];
            $message['subject'] = t('[!site_name][Case Study Project] Your case study project has been approved and marked as completed', array(
                '!site_name' => variable_get('site_name', ''),
            ), array(
                'language' => $language->language,
            ));
            $message['body'] = array(
                'body' => t('
Dear ' . $proposal_data->contributor_name . ',

We are glad to inform that your files are approved and the Case Study Project with the following details is marked Completed. The case study is published at ' . $url . '

Full Name: ' . $proposal_data->name_title . ' ' . $proposal_data->contributor_name . '
Email: ' . $user_data->mail . '
University/Institute: ' . $proposal_data->university . '
City: ' . $proposal_data->city . '
State: ' . $proposal_data->state . '
Country: ' . $proposal_data->country . '
Project Title: ' . $proposal_data->project_title . '

Best Wishes,
!site_name Team,
FOSSEE, IIT Bombay', array(
                    '!site_name' => variable_get('site_name', ''),
                    '!user_name' => $user_data->name,
                ), array(
                    'language' => $language->language,
                )),
            );
            break;
        case 'case_study_proposal_send_reminder_mail':
            /* initializing data */
            $query = db_select('case_study_proposal');
            $query->fields('case_study_proposal');
            $query->condition('id', $params['case_study_proposal_send_reminder_mail']['proposal_id']);
            $query->range(0, 1);
            $proposal_data = $query->execute()->fetchObject();
            $user_data = user_load($params['case_study_proposal_send_reminder_mail']['user_id']);
            $message['headers'] = $params['case_study_proposal_send_reminder_mail']['headers'];
            $message['subject'] = t('[!site_name][Case Study Project] Completion date of your proposed Case Study is approaching', array(
                '!site_name' => variable_get('site_name', ''),
            ), array(
                'language' => $language->language,
            ));
            $message['body'] = array(
                'body' => t('
Dear ' . $proposal_data->contributor_name . ',

This is a gentle reminder regarding the proposed case study project. The submission deadline is approaching, and we kindly request you to complete your submission before the deadline.

Please note that once the deadline passes, the interface will no longer accept submissions. Ensure you submit your work on time to avoid any inconvenience.

Best Wishes,

!site_name Team,
FOSSEE, IIT Bombay', array(
                    '!site_name' => variable_get('site_name', ''),
                    '!user_name' => $user_data->name,
                ), array(
                    'language' => $language->language,
                )),
            );
            break;
        case 'case_study_proposal_deleted':
            /* initializing data */
            $query = db_select('case_study_proposal');
            $query->fields('case_study_proposal');
            $query->condition('id', $params['case_study_proposal_deleted']['proposal_id']);
            $query->range(0, 1);
            $proposal_data = $query->execute()->fetchObject();
            $user_data = user_load($params['case_study_proposal_deleted']['user_id']);
            $message['headers'] = $params['case_study_proposal_deleted']['headers'];
            $message['subject'] = t('[!site_name][Case Study Project] Your Case Study Project proposal has been deleted', array(
                '!site_name' => variable_get('site_name', ''),
            ), array(
                'language' => $language->language,
            ));
            $message['body'] = array(
                'body' => t('
Dear ' . $proposal_data->contributor_name . ',

We regret to inform that your Case Study Project proposal with the following details has been deleted:

Full Name: ' . $proposal_data->name_title . ' ' . $proposal_data->contributor_name . '
Email: ' . $user_data->mail . '
University/Institute: ' . $proposal_data->university . '
City: ' . $proposal_data->city . '
State: ' . $proposal_data->state . '
Country: ' . $proposal_data->country . '
Project Title: ' . $proposal_data->project_title . '

You can propose a new Case Study project.

Best Wishes,
!site_name Team,
FOSSEE, IIT Bombay', array(
                    '!site_name' => variable_get('site_name', ''),
                    '!user_name' => $user_data->name,
                ), array(
                    'language' => $language->language,
                )),
            );
            break;
        /**************************************************************/
        case 'abstract_uploaded':
            // $solution_q = db_query("SELECT * FROM {case_study_solution} WHERE id = %d LIMIT 1", $params['abstract_uploaded']['solution_id']);
            $query = db_select('case_study_proposal');
            $query->fields('case_study_proposal');
            $query->condition('id', $params['abstract_uploaded']['proposal_id']);
            $query->range(0, 1);
            $proposal_data = $query->execute()->fetchObject();

            $user = user_load($params['abstract_uploaded']['user_id']);
            $message['subject'] = t('[!site_name][Case Study Project] You have uploaded  Case Study project files', array(
                '!site_name' => variable_get('site_name', ''),
            ), array(
                'language' => $language->language,
            ));
            $message['headers'] = $params['abstract_uploaded']['headers'];
            $message['body'] = array(
                'body' => t('
Dear ' . $proposal_data->contributor_name . ',

You have uploaded the following Abstract and Project files:

Project Title: ' . $proposal_data->project_title . '


The abstract and project files are under review. You will be notified when it has been approved.

Best Wishes,
!site_name Team,
FOSSEE, IIT Bombay', array(
                    '!site_name' => variable_get('site_name', ''),
                    '!user_name' => $user->name,
                ), array(
                    'language' => $language->language,
                )),
            );
            break;
/**************************************************************/
        case 'abstract_edit_file_uploaded':
            // $solution_q = db_query("SELECT * FROM {case_study_solution} WHERE id = %d LIMIT 1", $params['abstract_uploaded']['solution_id']);
            $query = db_select('case_study_proposal');
            $query->fields('case_study_proposal');
            $query->condition('id', $params['abstract_edit_file_uploaded']['proposal_id']);
            $query->range(0, 1);
            $proposal_data = $query->execute()->fetchObject();

            $user = user_load($params['abstract_edit_file_uploaded']['user_id']);
            $abs_file_name = $params['abstract_edit_file_uploaded']['abs_file'];
            $proj_file_name = $params['abstract_edit_file_uploaded']['proj_file'];

            $message['subject'] = t('[!site_name][Case Study Project] You have updated project files/abstract of a case study', array(
                '!site_name' => variable_get('site_name', ''),
            ), array(
                'language' => $language->language,
            ));
            $message['headers'] = $params['abstract_edit_file_uploaded']['headers'];
            $message['body'] = array(
                'body' => t('
Dear ' . $proposal_data->contributor_name . ',

You have updated the abstract/ project files for the following case study:

Project Title: ' . $proposal_data->project_title . '

Best Wishes,
!site_name Team,
FOSSEE, IIT Bombay', array(
                    '!site_name' => variable_get('site_name', ''),
                    '!user_name' => $user->name,
                ), array(
                    'language' => $language->language,
                )),
            );
            break;
        case 'standard':
            $message['subject'] = $params['standard']['subject'];
            $message['body'] = $params['standard']['body'];
            $message['headers'] = $params['standard']['headers'];
            break;
    } //$key
}
