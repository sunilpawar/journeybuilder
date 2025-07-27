<?php

class CRM_Journeybuilder_API_Journey {

  /**
   * Create or update a journey
   */
  public static function save($params) {
    $journeyId = $params['id'] ?? NULL;

    if ($journeyId) {
      // Update existing journey
      $dao = new CRM_Core_DAO();
      $dao->query("
        UPDATE civicrm_journey_campaigns
        SET name = %1, description = %2, configuration = %3, modified_date = NOW()
        WHERE id = %4
      ", [
        1 => [$params['name'], 'String'],
        2 => [$params['description'], 'String'],
        3 => [json_encode($params['configuration']), 'String'],
        4 => [$journeyId, 'Positive']
      ]);
    } else {
      // Create new journey
      $dao = new CRM_Core_DAO();
      $dao->query("
        INSERT INTO civicrm_journey_campaigns
        (name, description, configuration, status, created_date, created_id)
        VALUES (%1, %2, %3, 'draft', NOW(), %4)
      ", [
        1 => [$params['name'], 'String'],
        2 => [$params['description'], 'String'],
        3 => [json_encode($params['configuration']), 'String'],
        4 => [CRM_Core_Session::getLoggedInContactID(), 'Positive']
      ]);
      $journeyId = CRM_Core_DAO::singleValueQuery("SELECT LAST_INSERT_ID()");
    }

    // Save journey steps
    if (!empty($params['steps'])) {
      self::saveJourneySteps($journeyId, $params['steps']);
    }

    return ['journey_id' => $journeyId];
  }

  /**
   * Save journey steps
   */
  private static function saveJourneySteps($journeyId, $steps) {
    // Delete existing steps
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_journey_steps WHERE journey_id = %1", [
      1 => [$journeyId, 'Positive']
    ]);

    // Insert new steps
    foreach ($steps as $index => $step) {
      CRM_Core_DAO::executeQuery("
        INSERT INTO civicrm_journey_steps
        (journey_id, step_type, name, configuration, position_x, position_y, sort_order)
        VALUES (%1, %2, %3, %4, %5, %6, %7)
      ", [
        1 => [$journeyId, 'Positive'],
        2 => [$step['type'], 'String'],
        3 => [$step['name'], 'String'],
        4 => [json_encode($step['configuration']), 'String'],
        5 => [$step['position']['x'], 'Float'],
        6 => [$step['position']['y'], 'Float'],
        7 => [$index, 'Positive']
      ]);
    }
  }

  /**
   * Activate a journey
   */
  public static function activate($journeyId) {
    // Validate journey before activation
    $validation = self::validateJourney($journeyId);
    if (!$validation['valid']) {
      return ['error' => $validation['errors']];
    }

    // Update status to active
    CRM_Core_DAO::executeQuery("
      UPDATE civicrm_journey_campaigns
      SET status = 'active', activated_date = NOW()
      WHERE id = %1
    ", [1 => [$journeyId, 'Positive']]);

    // Initialize journey participants based on entry criteria
    self::initializeJourneyParticipants($journeyId);

    return ['success' => TRUE];
  }

  /**
   * Validate journey configuration
   */
  private static function validateJourney($journeyId) {
    $errors = [];
    $valid = TRUE;

    // Check for entry point
    $entrySteps = CRM_Core_DAO::singleValueQuery("
      SELECT COUNT(*) FROM civicrm_journey_steps
      WHERE journey_id = %1 AND step_type = 'entry'
    ", [1 => [$journeyId, 'Positive']]);

    if ($entrySteps == 0) {
      $errors[] = 'Journey must have at least one entry point';
      $valid = FALSE;
    }

    // Check for disconnected steps
    // Implementation would check journey flow connectivity

    return ['valid' => $valid, 'errors' => $errors];
  }

  /**
   * Initialize journey participants
   */
  private static function initializeJourneyParticipants($journeyId) {
    // Get journey entry criteria
    $journey = CRM_Core_DAO::executeQuery("
      SELECT configuration FROM civicrm_journey_campaigns WHERE id = %1
    ", [1 => [$journeyId, 'Positive']]);

    if ($journey->fetch()) {
      $config = json_decode($journey->configuration, TRUE);
      $entryCriteria = $config['entry_criteria'] ?? [];

      // Build contact query based on entry criteria
      $contactIds = self::getContactsForEntry($entryCriteria);

      // Add contacts to journey
      foreach ($contactIds as $contactId) {
        self::addContactToJourney($journeyId, $contactId);
      }
    }
  }

  /**
   * Get contacts matching entry criteria
   */
  private static function getContactsForEntry($criteria) {
    $contactIds = [];

    // Implementation would build dynamic query based on criteria
    // For now, return empty array

    return $contactIds;
  }

  /**
   * Add contact to journey
   */
  private static function addContactToJourney($journeyId, $contactId) {
    // Get first step of journey
    $firstStep = CRM_Core_DAO::singleValueQuery("
      SELECT id FROM civicrm_journey_steps
      WHERE journey_id = %1 AND step_type = 'entry'
      ORDER BY sort_order LIMIT 1
    ", [1 => [$journeyId, 'Positive']]);

    if ($firstStep) {
      CRM_Core_DAO::executeQuery("
        INSERT INTO civicrm_journey_participants
        (journey_id, contact_id, current_step_id, status, entered_date)
        VALUES (%1, %2, %3, 'active', NOW())
        ON DUPLICATE KEY UPDATE current_step_id = %3, status = 'active'
      ", [
        1 => [$journeyId, 'Positive'],
        2 => [$contactId, 'Positive'],
        3 => [$firstStep, 'Positive']
      ]);
    }
  }
}
