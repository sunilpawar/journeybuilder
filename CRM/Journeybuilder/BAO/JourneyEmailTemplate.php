<?php

use CRM_Journeybuilder_ExtensionUtil as E;

/**
 * Business Access Object for Journey Email Template entity.
 */
class CRM_Journeybuilder_BAO_JourneyEmailTemplate extends CRM_Journeybuilder_DAO_JourneyEmailTemplate {

  /**
   * Create a new JourneyEmailTemplate based on array-data
   */
  public static function create($params) {
    $className = 'CRM_Journeybuilder_DAO_JourneyEmailTemplate';
    $entityName = 'JourneyEmailTemplate';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

  /**
   * Get email template for a step
   */
  public static function getStepEmailTemplate($stepId) {
    $template = new CRM_Journeybuilder_DAO_JourneyEmailTemplate();
    $template->step_id = $stepId;

    if ($template->find(TRUE)) {
      return [
        'id' => $template->id,
        'step_id' => $template->step_id,
        'mosaico_template_id' => $template->mosaico_template_id,
        'subject' => $template->subject,
        'html_content' => $template->html_content,
        'text_content' => $template->text_content,
        'personalization_rules' => json_decode($template->personalization_rules, TRUE) ?: [],
        'ab_test_config' => json_decode($template->ab_test_config, TRUE) ?: []
      ];
    }

    return NULL;
  }

  /**
   * Save email template for a step
   */
  public static function saveStepEmailTemplate($stepId, $templateData) {
    $template = new CRM_Journeybuilder_DAO_JourneyEmailTemplate();
    $template->step_id = $stepId;

    if ($template->find(TRUE)) {
      // Update existing template
      $template->copyValues($templateData);
    }
    else {
      // Create new template
      $template = new CRM_Journeybuilder_DAO_JourneyEmailTemplate();
      $template->step_id = $stepId;
      $template->copyValues($templateData);
    }

    // Encode JSON fields
    if (isset($templateData['personalization_rules'])) {
      $template->personalization_rules = json_encode($templateData['personalization_rules']);
    }
    if (isset($templateData['ab_test_config'])) {
      $template->ab_test_config = json_encode($templateData['ab_test_config']);
    }

    $template->save();
    return $template->id;
  }

  /**
   * Render email template with personalization
   */
  public static function renderTemplate($stepId, $contactId, $variant = 'A') {
    $template = self::getStepEmailTemplate($stepId);
    if (!$template) {
      throw new Exception('Email template not found for step');
    }

    // Get contact data for personalization
    $contact = civicrm_api3('Contact', 'get', [
      'id' => $contactId,
      'return' => ['display_name', 'first_name', 'last_name', 'email', 'phone']
    ]);
    $contactData = $contact['values'][$contactId] ?? [];

    // Handle A/B testing
    if (!empty($template['ab_test_config']) && $template['ab_test_config']['enabled']) {
      $template = self::getABTestVariant($template, $variant);
    }

    // Personalize subject
    $subject = self::personalizeContent($template['subject'], $contactData, $template['personalization_rules']);

    // Personalize HTML content
    $htmlContent = $template['html_content'];
    if ($template['mosaico_template_id']) {
      $htmlContent = self::renderMosaicoTemplate($template['mosaico_template_id'], $contactData);
    }
    $htmlContent = self::personalizeContent($htmlContent, $contactData, $template['personalization_rules']);

    // Personalize text content
    $textContent = self::personalizeContent($template['text_content'], $contactData, $template['personalization_rules']);

    return [
      'subject' => $subject,
      'html_content' => $htmlContent,
      'text_content' => $textContent,
      'template_id' => $template['id']
    ];
  }

  /**
   * Get A/B test variant
   */
  private static function getABTestVariant($template, $variant) {
    $abConfig = $template['ab_test_config'];

    if ($variant === 'B' && !empty($abConfig['variant_b'])) {
      // Override with variant B content
      if (!empty($abConfig['variant_b']['subject'])) {
        $template['subject'] = $abConfig['variant_b']['subject'];
      }
      if (!empty($abConfig['variant_b']['html_content'])) {
        $template['html_content'] = $abConfig['variant_b']['html_content'];
      }
      if (!empty($abConfig['variant_b']['text_content'])) {
        $template['text_content'] = $abConfig['variant_b']['text_content'];
      }
    }

    return $template;
  }

  /**
   * Personalize content with contact data
   */
  private static function personalizeContent($content, $contactData, $personalizationRules) {
    if (empty($content)) {
      return $content;
    }

    // Basic token replacement
    $tokens = [
      '{contact.display_name}' => $contactData['display_name'] ?? '',
      '{contact.first_name}' => $contactData['first_name'] ?? '',
      '{contact.last_name}' => $contactData['last_name'] ?? '',
      '{contact.email}' => $contactData['email'] ?? '',
      '{contact.phone}' => $contactData['phone'] ?? '',
    ];

    // Apply personalization rules
    if (!empty($personalizationRules)) {
      foreach ($personalizationRules as $rule) {
        if ($rule['type'] === 'conditional_content') {
          $content = self::applyConditionalContent($content, $contactData, $rule);
        }
        elseif ($rule['type'] === 'dynamic_content') {
          $content = self::applyDynamicContent($content, $contactData, $rule);
        }
      }
    }

    // Replace tokens
    foreach ($tokens as $token => $value) {
      $content = str_replace($token, $value, $content);
    }

    return $content;
  }

  /**
   * Apply conditional content rules
   */
  private static function applyConditionalContent($content, $contactData, $rule) {
    $condition = $rule['condition'] ?? [];
    $field = $condition['field'] ?? '';
    $operator = $condition['operator'] ?? 'equals';
    $value = $condition['value'] ?? '';

    $fieldValue = $contactData[$field] ?? '';
    $matches = FALSE;

    switch ($operator) {
      case 'equals':
        $matches = $fieldValue == $value;
        break;
      case 'not_equals':
        $matches = $fieldValue != $value;
        break;
      case 'contains':
        $matches = strpos($fieldValue, $value) !== FALSE;
        break;
      case 'is_empty':
        $matches = empty($fieldValue);
        break;
      case 'is_not_empty':
        $matches = !empty($fieldValue);
        break;
    }

    $token = $rule['token'] ?? '';
    $replacement = $matches ? ($rule['true_content'] ?? '') : ($rule['false_content'] ?? '');

    return str_replace($token, $replacement, $content);
  }

  /**
   * Apply dynamic content rules
   */
  private static function applyDynamicContent($content, $contactData, $rule) {
    $token = $rule['token'] ?? '';
    $dynamicContent = '';

    switch ($rule['content_type']) {
      case 'recent_contributions':
        $dynamicContent = self::getRecentContributions($contactData['id'] ?? 0, $rule['limit'] ?? 3);
        break;
      case 'upcoming_events':
        $dynamicContent = self::getUpcomingEvents($contactData['id'] ?? 0, $rule['limit'] ?? 3);
        break;
      case 'membership_info':
        $dynamicContent = self::getMembershipInfo($contactData['id'] ?? 0);
        break;
    }

    return str_replace($token, $dynamicContent, $content);
  }

  /**
   * Render Mosaico template
   */
  private static function renderMosaicoTemplate($templateId, $contactData) {
    try {
      // This would integrate with Mosaico extension
      // For now, return a placeholder
      return '<p>Mosaico template ' . $templateId . ' would be rendered here with contact data.</p>';
    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Error rendering Mosaico template: ' . $e->getMessage());
      return '<p>Error rendering email template</p>';
    }
  }

  /**
   * Get recent contributions for dynamic content
   */
  private static function getRecentContributions($contactId, $limit = 3) {
    if (!$contactId) {
      return '';
    }

    try {
      $contributions = civicrm_api3('Contribution', 'get', [
        'contact_id' => $contactId,
        'contribution_status_id' => 1,
        'options' => [
          'limit' => $limit,
          'sort' => 'receive_date DESC'
        ],
        'return' => ['total_amount', 'receive_date', 'financial_type_id']
      ]);

      $html = '<ul>';
      foreach ($contributions['values'] as $contribution) {
        $html .= '<li>$' . number_format($contribution['total_amount'], 2) .
          ' on ' . date('M j, Y', strtotime($contribution['receive_date'])) . '</li>';
      }
      $html .= '</ul>';

      return $html;
    }
    catch (Exception $e) {
      return '';
    }
  }

  /**
   * Get upcoming events for dynamic content
   */
  private static function getUpcomingEvents($contactId, $limit = 3) {
    if (!$contactId) {
      return '';
    }

    try {
      $participants = civicrm_api3('Participant', 'get', [
        'contact_id' => $contactId,
        'options' => [
          'limit' => $limit,
          'sort' => 'event_id.start_date ASC'
        ],
        'return' => ['event_id.title', 'event_id.start_date', 'event_id.event_type_id']
      ]);

      $html = '<ul>';
      foreach ($participants['values'] as $participant) {
        $html .= '<li>' . $participant['event_id.title'] .
          ' on ' . date('M j, Y', strtotime($participant['event_id.start_date'])) . '</li>';
      }
      $html .= '</ul>';

      return $html;
    }
    catch (Exception $e) {
      return '';
    }
  }

  /**
   * Get membership info for dynamic content
   */
  private static function getMembershipInfo($contactId) {
    if (!$contactId) {
      return '';
    }

    try {
      $memberships = civicrm_api3('Membership', 'get', [
        'contact_id' => $contactId,
        'is_current_member' => 1,
        'return' => ['membership_type_id', 'status_id', 'end_date']
      ]);

      if (empty($memberships['values'])) {
        return 'No current membership';
      }

      $membership = reset($memberships['values']);
      return 'Your membership expires on ' . date('M j, Y', strtotime($membership['end_date']));
    }
    catch (Exception $e) {
      return '';
    }
  }

  /**
   * Send email using template
   */
  public static function sendTemplateEmail($stepId, $contactId, $variant = 'A') {
    try {
      $renderedTemplate = self::renderTemplate($stepId, $contactId, $variant);

      // Get contact email
      $contact = civicrm_api3('Contact', 'get', [
        'id' => $contactId,
        'return' => ['email', 'display_name']
      ]);

      $contactData = $contact['values'][$contactId] ?? [];
      if (empty($contactData['email'])) {
        throw new Exception('Contact has no email address');
      }

      // Send email via CiviCRM
      $result = civicrm_api3('Email', 'send', [
        'to' => $contactData['email'],
        'subject' => $renderedTemplate['subject'],
        'html' => $renderedTemplate['html_content'],
        'text' => $renderedTemplate['text_content'],
      ]);

      return $result['id'] ?? TRUE;
    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Error sending template email: ' . $e->getMessage());
      throw $e;
    }
  }

  /**
   * Clone email template
   */
  public static function cloneTemplate($originalStepId, $newStepId) {
    $originalTemplate = self::getStepEmailTemplate($originalStepId);
    if (!$originalTemplate) {
      return NULL;
    }

    unset($originalTemplate['id']);
    $originalTemplate['step_id'] = $newStepId;

    return self::saveStepEmailTemplate($newStepId, $originalTemplate);
  }

  /**
   * Delete email template
   */
  public static function deleteTemplate($stepId) {
    $template = new CRM_Journeybuilder_DAO_JourneyEmailTemplate();
    $template->step_id = $stepId;
    if ($template->find(TRUE)) {
      $template->delete();
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get available Mosaico templates
   */
  public static function getMosaicoTemplates() {
    $templates = [];
    try {
      $result = civicrm_api3('MosaicoTemplate', 'get', [
        'sequential' => 1,
        'options' => ['limit' => 100]
      ]);
      foreach ($result['values'] as $template) {
        $templates[] = [
          'id' => $template['id'],
          'title' => $template['title'],
          'thumbnail' => $template['thumbnail'] ?? '',
          'category' => $template['category'] ?? 'general'
        ];
      }
    }
    catch (Exception $e) {
      // Mosaico extension might not be installed
      CRM_Core_Error::debug_log_message('Could not load Mosaico templates: ' . $e->getMessage());
    }
    return $templates;
  }

}
