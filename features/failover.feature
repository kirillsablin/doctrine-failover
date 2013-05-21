Feature: failover
  In order to increase availability of system
  As doctrine client
  I need to failover and failback integrated into doctrine DBAL

  Scenario: normal connection
    Given main db is online
    And failover status is clean
    When I connect to db
    Then main db should be used

  Scenario: failed connection to main
    Given main db is offline
    And failover status is clean
    When I connect to db
    Then failover status should be set to use reserve and dont retry until some time in future
    And reserve db should be used

  Scenario: don't retry until
    Given main db is online
    And failover status is dont retry until future
    When I connect to db
    Then reserve db should be used


