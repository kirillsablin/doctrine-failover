Feature: failover
  In order to increase availability of system
  As doctrine client
  I need failover and failback integrated into doctrine DBAL

  Scenario: normal connection
    Given main db is online
    And failover status is clean
    When I connect to db
    Then main db should be used

  Scenario: failed connection to main
    Given main db is offline
    And failover status is clean
    When I connect to db
    Then failover status should be set to dont retry until some time in future
    And reserve db should be used
    And failover event should be dispatched

  Scenario: don't retry until
    Given main db is online
    And failover status is dont retry until future
    When I connect to db
    Then reserve db should be used

  Scenario: don't retry until period is exceeded and replication is online
    Given main db is online
    And failover status is dont retry until some time in past
    When I connect to db
    Then main db should be used
    And failover status should be cleaned
    And failback event should be dispatched

  Scenario: don't retry until period exceeded but not all is ok with main db
    Given main db is online
    And failover status is dont retry until some time in past
    But replication from reserve to main is offline
    When I connect to db
    Then failover status should be set to dont retry until some time in future
    And reserve db should be used
    And no failover events should be dispatched





