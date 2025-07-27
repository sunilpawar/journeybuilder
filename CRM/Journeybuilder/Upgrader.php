<?php

use CRM_Journeybuilder_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Journeybuilder_Upgrader extends CRM_Extension_Upgrader_Base {

  /**
   * Installation step - run the SQL install script
   */
  public function install() {
    $this->executeSqlFile('sql/install.sql');
    
    // Create scheduled job for journey processing
    $this->createScheduledJob();
    
    // Add navigation menu items
    $this->addNavigationMenuItems();
  }

  /**
   * Uninstallation step - clean up database
   */
  public function uninstall() {
    // Remove scheduled job
    $this->removeScheduledJob();
    
    // Remove navigation menu items
    $this->removeNavigationMenuItems();
    
    // Note: We don't drop tables on uninstall to preserve data
    // Users can manually drop tables if they want to completely remove data
  }

  /**
   * Enable step - enable the scheduled job
   */
  public function enable() {
    $this->toggleScheduledJob(TRUE);
  }

  /**
   * Disable step - disable the scheduled job
   */
  public function disable() {
    $this->toggleScheduledJob(FALSE);
  }

  /**
   * Upgrade to version 1.1 - Add indexes for better performance
   */
  public function upgrade_1001() {
    $this->ctx->log->info('Applying Journey Builder update 1.1');
    
    // Add performance indexes
    CRM_Core_DAO::executeQuery("
      ALTER TABLE civicrm_journey_analytics 
      ADD INDEX idx_journey_contact_date (journey_id, contact_id, event_date),
      ADD INDEX idx_event_type_date (event_type, event_date)
    ");
    
    CRM_Core_DAO::executeQuery("
      ALTER TABLE civicrm_journey_participants
      ADD INDEX idx_journey_status_date (journey_id, status, last_action_date)
    ");
    
    return TRUE;
  }

  /**
   * Upgrade to version 1.2 - Add connection tracking
   */
  public function upgrade_1002() {
    $this->ctx->log->info('Applying Journey Builder update 1.2');
    
    // Add connections table for better step flow tracking
    CRM_Core_DAO::executeQuery("
      CREATE TABLE IF NOT EXISTS `civicrm_journey_connections` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `journey_id` int(10) unsigned NOT NULL,
        `from_step_id` int(10) unsigned NOT NULL,
        `to_step_id` int(10) unsigned NOT NULL,
        `condition_type` enum('default','condition_true','condition_false','percentage') DEFAULT 'default',
        `condition_value` text,
        `percentage` decimal(5,2) DEFAULT NULL,
        `sort_order` int(11) DEFAULT 0,
        PRIMARY KEY (`id`),
        KEY `FK_journey_connections_journey_id` (`journey_id`),
        KEY `FK_journey_connections_from_step_id` (`from_step_id`),
        KEY `FK_journey_connections_to_step_id` (`to_step_id`),
        CONSTRAINT `FK_journey_connections_journey_id`
          FOREIGN KEY (`journey_id`) REFERENCES `civicrm_journey_campaigns` (`id`) ON DELETE CASCADE,
        CONSTRAINT `FK_journey_connections_from_step_id`
          FOREIGN KEY (`from_step_id`) REFERENCES `civicrm_journey_steps` (`id`) ON DELETE CASCADE,
        CONSTRAINT `FK_journey_connections_to_step_id`
          FOREIGN KEY (`to_step_id`) REFERENCES `civicrm_journey_steps` (`id`) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    return TRUE;
  }

  /**
   * Create the scheduled job for journey processing
   */
  private function createScheduledJob() {
    try {
      civicrm_api3('Job', 'create', [
        'sequential' => 1,
        'name' => 'Process Journey Steps',
        'description' => 'Processes active journey participants and executes their next steps',
        'run_frequency' => 'Every',
        'frequency_unit' => 'minute',
        'frequency_interval' => 5,
        'api_entity' => 'Journey',
        'api_action' => 'process',
        'parameters' => '',
        'is_active' => 1,
      ]);
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Failed to create Journey processing job: ' . $e->getMessage());
    }
  }

  /**
   * Remove the scheduled job
   */
  private function removeScheduledJob() {
    try {
      $job = civicrm_api3('Job', 'get', [
        'sequential' => 1,
        'name' => 'Process Journey Steps',
        'api_entity' => 'Journey',
        'api_action' => 'process',
      ]);
      
      if (!empty($job['values'])) {
        foreach ($job['values'] as $jobItem) {
          civicrm_api3('Job', 'delete', ['id' => $jobItem['id']]);
        }
      }
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Failed to remove Journey processing job: ' . $e->getMessage());
    }
  }

  /**
   * Toggle the scheduled job on/off
   */
  private function toggleScheduledJob($enable = TRUE) {
    try {
      $job = civicrm_api3('Job', 'get', [
        'sequential' => 1,
        'name' => 'Process Journey Steps',
        'api_entity' => 'Journey',
        'api_action' => 'process',
      ]);
      
      if (!empty($job['values'])) {
        foreach ($job['values'] as $jobItem) {
          civicrm_api3('Job', 'create', [
            'id' => $jobItem['id'],
            'is_active' => $enable ? 1 : 0,
          ]);
        }
      }
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Failed to toggle Journey processing job: ' . $e->getMessage());
    }
  }

  /**
   * Add navigation menu items
   */
  private function addNavigationMenuItems() {
    try {
      // Add main menu item
      $mainMenu = civicrm_api3('Navigation', 'create', [
        'sequential' => 1,
        'name' => 'Journey Builder',
        'label' => E::ts('Journey Builder'),
        'url' => 'civicrm/journey/list',
        'parent_id' => $this->getMarketingMenuId(),
        'permission' => 'administer CiviCRM',
        'is_active' => 1,
        'weight' => 10,
      ]);
      
      $mainMenuId = $mainMenu['id'];
      
      // Add sub-menu items
      civicrm_api3('Navigation', 'create', [
        'sequential' => 1,
        'name' => 'Journey List',
        'label' => E::ts('Journey List'),
        'url' => 'civicrm/journey/list',
        'parent_id' => $mainMenuId,
        'permission' => 'administer CiviCRM',
        'is_active' => 1,
        'weight' => 1,
      ]);
      
      civicrm_api3('Navigation', 'create', [
        'sequential' => 1,
        'name' => 'Create Journey',
        'label' => E::ts('Create Journey'),
        'url' => 'civicrm/journey/builder',
        'parent_id' => $mainMenuId,
        'permission' => 'administer CiviCRM',
        'is_active' => 1,
        'weight' => 2,
      ]);
      
      civicrm_api3('Navigation', 'create', [
        'sequential' => 1,
        'name' => 'Journey Analytics',
        'label' => E::ts('Journey Analytics'),
        'url' => 'civicrm/journey/analytics',
        'parent_id' => $mainMenuId,
        'permission' => 'administer CiviCRM',
        'is_active' => 1,
        'weight' => 3,
      ]);
      
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Failed to create navigation menu items: ' . $e->getMessage());
    }
  }

  /**
   * Remove navigation menu items
   */
  private function removeNavigationMenuItems() {
    try {
      $menuItems = civicrm_api3('Navigation', 'get', [
        'sequential' => 1,
        'name' => ['IN' => ['Journey Builder', 'Journey List', 'Create Journey', 'Journey Analytics']],
      ]);
      
      foreach ($menuItems['values'] as $item) {
        civicrm_api3('Navigation', 'delete', ['id' => $item['id']]);
      }
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Failed to remove navigation menu items: ' . $e->getMessage());
    }
  }

  /**
   * Get the Marketing menu ID, create if it doesn't exist
   */
  private function getMarketingMenuId() {
    try {
      $marketing = civicrm_api3('Navigation', 'get', [
        'sequential' => 1,
        'name' => 'Marketing',
      ]);
      
      if (!empty($marketing['values'])) {
        return $marketing['values'][0]['id'];
      }
      
      // Create Marketing menu if it doesn't exist
      $result = civicrm_api3('Navigation', 'create', [
        'sequential' => 1,
        'name' => 'Marketing',
        'label' => E::ts('Marketing'),
        'permission' => 'administer CiviCRM',
        'is_active' => 1,
        'weight' => 100,
      ]);
      
      return $result['id'];
      
    } catch (Exception $e) {
      // Fallback to Administer menu
      try {
        $administer = civicrm_api3('Navigation', 'get', [
          'sequential' => 1,
          'name' => 'Administer',
        ]);
        
        if (!empty($administer['values'])) {
          return $administer['values'][0]['id'];
        }
      } catch (Exception $e2) {
        // Ultimate fallback
        return 1;
      }
    }
    
    return 1;
  }

  /**
   * Add custom permissions
   */
  public function postInstall() {
    // This would add custom permissions if needed
    // For now, we're using existing CiviCRM permissions
  }

}