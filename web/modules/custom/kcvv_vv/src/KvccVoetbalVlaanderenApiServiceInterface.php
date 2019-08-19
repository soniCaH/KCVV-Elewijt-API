<?php

namespace Drupal\kcvv_vv;

/**
 * Interface KvccVoetbalVlaanderenApiServiceInterface.
 */
interface KvccVoetbalVlaanderenApiServiceInterface {

  /**
   * Retrieve the player statistics from a given team.
   *
   * @param string $teamcode
   *   The VoetbalVlaanderen team ID.
   *
   * @return array
   *   Decoded json.
   */
  public function fetchTeamPlayerStats($teamcode);

  /**
   * Iterate over all "team" nodes and sync players attached to it.
   *
   * @return int
   *   Number of players affected.
   */
  public function syncPlayersAllTeams();

}
