<?php

namespace Drupal\kcvv_vv;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Class KvccVoetbalVlaanderenApiService.
 */
class KvccVoetbalVlaanderenApiService implements KvccVoetbalVlaanderenApiServiceInterface {

  /**
   * General URL of the VoetbalVlaanderen API.
   */
  const API_URL = 'https://datalake-prod2018.rbfa.be/graphql';

  /**
   * Operation to retrieve team players (with stats).
   */
  const TEAMPLAYERS_OPERATION = 'GetTeamMembers';

  /**
   * Sha256 hash for retrieving team players (with stats).
   */
  const TEAMPLAYERS_HASH = 'cbbaa32e4580c9385409c85464d2578c88f1c051f6e3c55a2e5a47f11dce9e64';

  /**
   * GuzzleHttp\ClientInterface definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new KvccVoetbalVlaanderenApiService object.
   */
  public function __construct(ClientInterface $http_client,
                              EntityTypeManagerInterface $entityTypeManager) {
    $this->httpClient = $http_client;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function syncPlayersAllTeams() {
    $count = 0;
    // Query with entity_type.manager (The way to go)
    $query = $this->entityTypeManager->getStorage('node');
    $query_result = $query->getQuery()
      ->condition('status', 1)
      ->condition('type', 'team')
      ->condition('field_vv_id', NULL, '<>')
      ->execute();

    if (!$query_result) {
      return $count;
    }

    $teams = Node::loadMultiple($query_result);

    foreach ($teams as $team) {
      $players = $team->get('field_players');
      if (!$players) {
        return $count;
      }

      $players_vv = $this->fetchTeamPlayerStats($team->get('field_vv_id')->value)->players;
      if (!$players_vv) {
        return $count;
      }

      foreach ($players as $player) {
        $player_object = $player->get('entity')->getValue();
        // Find matching player in API result.
        $player_object_vv = $this->getPlayerByFirstOrLastName($players_vv, $player_object->get('field_firstname')->value, $player_object->get('field_lastname')->value);

        if (!$player_object_vv) {
          return $count;
        }

        // Let's go syncing.
        $player_object->set('field_vv_id', $player_object_vv->id);
        $player_object->set('field_stats_games', $player_object_vv->statistics->numberOfMatches);
        $player_object->set('field_stats_goals', $player_object_vv->statistics->numberOfGoals);
        $player_object->save();
        $count++;
      }
    }

    return $count;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchTeamPlayerStats($team) {
    $url = URL::fromUri(self::API_URL, [
      'query' => [
        'operationName' => self::TEAMPLAYERS_OPERATION,
        'variables' => json_encode([
          'teamId' => $team,
          'language' => 'nl',
        ]),
        'extensions' => json_encode([
          'persistedQuery' => [
            'version' => 1,
            'sha256Hash' => self::TEAMPLAYERS_HASH,
          ],
        ]),
      ],
    ]);

    $data = $this->fetchResource($url);

    if (!isset($data->data)) {
      return [];
    }

    if (!isset($data->data->teamMembers)) {
      return [];
    }

    return $data->data->teamMembers;
  }

  /**
   * Fetch a GraphQL API resource and return the decoded json result.
   *
   * @param \Drupal\Core\Url $url
   *   Endpoint-specific URL of the VV API.
   *
   * @return array
   *   Decoded json.
   */
  protected function fetchResource(Url $url) {
    try {
      $response = $this->httpClient->get($url->toString(), ['headers' => ['Content-type' => 'application/json']]);
      $data = (string) $response->getBody();
    }
    catch (RequestException $e) {
      watchdog_exception('kcvv_vv', "Error fetching GraphQL @url", ['@url' => $url->toString()]);
    }

    return json_decode($data);
  }

  /**
   * Search the API results for a given firstname and lastname.
   *
   * @param array $array
   *   Array of results to search in.
   * @param string $firstname
   *   First name to look for.
   * @param string $lastname
   *   Last name to look for.
   *
   * @return object|null
   *   Player object if found or NULL if no player with that name found.
   */
  protected function getPlayerByFirstOrLastName(array $array, $firstname, $lastname) {
    foreach ($array as $row) {
      if ($row->lastName == $lastname && $row->firstName == $firstname) {
        return $row;
      }
    }
    return NULL;
  }

}
