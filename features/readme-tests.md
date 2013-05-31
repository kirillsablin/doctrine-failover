Testing
=======

Behat was chosen as testing framework for functional tests.

How to run tests
----------------

1. Install mysql sandbox (mysqlsandbox.net)
2. Create two nodes with circular replication inside sandbox
    make_replication_sandbox --circular=2 <version>
3. Write your path to just created sandbox dir to behat.yml
4. Fix other params at behat.yml
5. run behat :)

There are also PHPUnit tests inside tests dir

You have to run both behat and phpunit tests in order to validate modifications
