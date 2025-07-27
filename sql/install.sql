-- Journey Campaigns Table
CREATE TABLE IF NOT EXISTS `civicrm_journey_campaigns` (
                                                         `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `configuration` longtext,
  `status` enum('draft','active','paused','completed','archived') DEFAULT 'draft',
  `created_date` datetime NOT NULL,
  `modified_date` datetime,
  `activated_date` datetime,
  `created_id` int(10) unsigned,
  PRIMARY KEY (`id`),
  KEY `FK_journey_campaigns_created_id` (`created_id`),
  KEY `idx_journey_status` (`status`),
  CONSTRAINT `FK_journey_campaigns_created_id`
  FOREIGN KEY (`created_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE SET NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Journey Steps Table
CREATE TABLE IF NOT EXISTS `civicrm_journey_steps` (
                                                     `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `journey_id` int(10) unsigned NOT NULL,
  `step_type` enum('entry','email','sms','wait','condition','action','exit') NOT NULL,
  `name` varchar(255) NOT NULL,
  `configuration` longtext,
  `position_x` decimal(10,2) DEFAULT 0,
  `position_y` decimal(10,2) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `FK_journey_steps_journey_id` (`journey_id`),
  KEY `idx_step_type` (`step_type`),
  CONSTRAINT `FK_journey_steps_journey_id`
  FOREIGN KEY (`journey_id`) REFERENCES `civicrm_journey_campaigns` (`id`) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Journey Conditions Table
CREATE TABLE IF NOT EXISTS `civicrm_journey_conditions` (
                                                          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `step_id` int(10) unsigned NOT NULL,
  `condition_type` enum('contact_field','activity','contribution','membership','event','custom') NOT NULL,
  `field_name` varchar(255),
  `operator` enum('equals','not_equals','contains','not_contains','greater_than','less_than','is_null','is_not_null') NOT NULL,
  `value` text,
  `logic_operator` enum('AND','OR') DEFAULT 'AND',
  `sort_order` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `FK_journey_conditions_step_id` (`step_id`),
  CONSTRAINT `FK_journey_conditions_step_id`
  FOREIGN KEY (`step_id`) REFERENCES `civicrm_journey_steps` (`id`) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Journey Participants Table
CREATE TABLE IF NOT EXISTS `civicrm_journey_participants` (
                                                            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `journey_id` int(10) unsigned NOT NULL,
  `contact_id` int(10) unsigned NOT NULL,
  `current_step_id` int(10) unsigned,
  `status` enum('active','completed','paused','exited','error') DEFAULT 'active',
  `entered_date` datetime NOT NULL,
  `completed_date` datetime,
  `last_action_date` datetime,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_journey_contact` (`journey_id`, `contact_id`),
  KEY `FK_journey_participants_journey_id` (`journey_id`),
  KEY `FK_journey_participants_contact_id` (`contact_id`),
  KEY `FK_journey_participants_current_step_id` (`current_step_id`),
  KEY `idx_participant_status` (`status`),
  CONSTRAINT `FK_journey_participants_journey_id`
  FOREIGN KEY (`journey_id`) REFERENCES `civicrm_journey_campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_journey_participants_contact_id`
  FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_journey_participants_current_step_id`
  FOREIGN KEY (`current_step_id`) REFERENCES `civicrm_journey_steps` (`id`) ON DELETE SET NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Journey Analytics Table
CREATE TABLE IF NOT EXISTS `civicrm_journey_analytics` (
                                                         `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `journey_id` int(10) unsigned NOT NULL,
  `step_id` int(10) unsigned,
  `contact_id` int(10) unsigned NOT NULL,
  `event_type` enum('entered','completed','email_sent','email_opened','email_clicked','bounced','unsubscribed','converted') NOT NULL,
  `event_data` longtext,
  `event_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_journey_analytics_journey_id` (`journey_id`),
  KEY `FK_journey_analytics_step_id` (`step_id`),
  KEY `FK_journey_analytics_contact_id` (`contact_id`),
  KEY `idx_event_type_date` (`event_type`, `event_date`),
  CONSTRAINT `FK_journey_analytics_journey_id`
  FOREIGN KEY (`journey_id`) REFERENCES `civicrm_journey_campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_journey_analytics_step_id`
  FOREIGN KEY (`step_id`) REFERENCES `civicrm_journey_steps` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_journey_analytics_contact_id`
  FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email Templates Integration Table
CREATE TABLE IF NOT EXISTS `civicrm_journey_email_templates` (
                                                               `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `step_id` int(10) unsigned NOT NULL,
  `mosaico_template_id` int(10) unsigned,
  `subject` varchar(255),
  `html_content` longtext,
  `text_content` longtext,
  `personalization_rules` longtext,
  `ab_test_config` longtext,
  PRIMARY KEY (`id`),
  KEY `FK_journey_email_templates_step_id` (`step_id`),
  CONSTRAINT `FK_journey_email_templates_step_id`
  FOREIGN KEY (`step_id`) REFERENCES `civicrm_journey_steps` (`id`) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
