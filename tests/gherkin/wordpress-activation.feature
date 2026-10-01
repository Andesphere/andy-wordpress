Feature: WordPress clearly connects an Andy Agent
  Scenario: Activation offers the remaining connection task
    Given an administrator activates Andy Partner without an Agent ID
    Then a dismissible notice links directly to Andy Partner settings
    And settings prominently show the unconnected state
    And the required external-service disclosure is visible before enablement

  Scenario Outline: Account links preserve WordPress context
    Given the WordPress administrator uses <language>
    When the administrator opens new-account or existing-account setup
    Then the Andy entry carries the supported owner locale and public home URL
    And it retains existing acquisition attribution and the explicit settings return
    And no key, admin nonce, token or Agent ID is included in the outbound URL
    Examples:
      | language |
      | English  |
      | Spanish  |
      | German   |

  Scenario: Unsafe local URL metadata keeps account entry usable
    Given the public home or settings URL contains credentials or an unexpected query
    When the administrator opens account setup
    Then the unsafe URL hint is omitted
    And the fixed Andy account destination and plugin attribution remain usable

  Scenario: Access eligibility and widget enablement remain separate
    Given the administrator pastes an Agent ID
    When the existing access check succeeds
    Then settings show access allowed and the current widget enabled state
    And settings preserve the saved ID and enablement value
    And access success does not claim an observed public widget connection

  Scenario: A changed ID has no inherited access result
    Given access was allowed for a saved Agent ID
    When the administrator edits the Agent ID
    Then access is unchecked for the edited ID
    And a late reply for the previous ID cannot replace that state

  Scenario: Lower-role users cannot change connection settings
    Given a signed-in user cannot manage WordPress options
    When the user attempts to change the Agent ID or widget enablement
    Then existing capability and nonce enforcement rejects the write
