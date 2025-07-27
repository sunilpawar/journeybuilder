<?php

use CRM_Journeybuilder_ExtensionUtil as E;

/**
 * Class CRM_Journeybuilder_BAO_JourneyConnection
 *
 * This class represents a connection in the journey builder.
 * It extends the DAO class for JourneyConnection and provides additional functionality.
 */
class CRM_Journeybuilder_BAO_JourneyConnection extends CRM_Journeybuilder_DAO_JourneyConnection {

  public static function conditionType() {
    return [
      'default' => 'Default',
      'condition_true' => 'Condition True',
      'condition_false' => 'Condition False',
      'percentage' => 'Percentage',
    ];
  }
}
