<?php

namespace Medology\Behat\Mink;

use Behat\Behat\Context\Context;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use WebDriver\Exception\NoAlertOpenError;

/**
 * A context for handling JavaScript alerts. Based on a gist by Benjamin Lazarecki with improvements.
 *
 * @see https://gist.github.com/blazarecki/2888851
 */
class AlertContext implements Context
{
    use UsesFlexibleContext;

    /**
     * Clears out any alerts or prompts that may be open.
     *
     * @AfterScenario @clearAlertsWhenFinished
     * @Given there are no alerts on the page
     *
     * @throws UnsupportedDriverActionException if the current driver does not support cancelling the alert
     */
    public function clearAlerts(): void
    {
        if (!$this->flexibleContext->getSession()->getDriver()->isStarted()) {
            return;
        }

        try {
            $this->cancelAlert();
        } catch (NoAlertOpenError $e) {
            // Ok, no alert was open anyway.
        }
    }

    /**
     * Confirms the current JavaScript alert.
     *
     * @When /^(?:|I )confirm the alert$/
     *
     * @throws UnsupportedDriverActionException if the current driver does not support confirming the alert
     */
    public function confirmAlert(): void
    {
        $this->flexibleContext->assertSelenium2Driver('Confirm Alert')->getWebDriverSession()->accept_alert();
    }

    /**
     * Cancels the current JavaScript alert.
     *
     * @When   /^(?:|I )cancel the alert$/
     *
     * @throws UnsupportedDriverActionException if the current driver does not support cancelling the alert
     */
    public function cancelAlert(): void
    {
        $this->flexibleContext->assertSelenium2Driver('Cancel Alert')->getWebDriverSession()->dismiss_alert();
    }

    /**
     * Asserts that the current JavaScript alert contains the given text.
     *
     * @Then   /^(?:|I )should see an alert containing "(?P<expected>[^"]*)"$/
     *
     * @param string $expected the expected text
     *
     * @throws ExpectationException             if the given text is not present in the current alert
     * @throws UnsupportedDriverActionException if the current driver does not support asserting the alert message
     */
    public function assertAlertMessage(string $expected): void
    {
        $driver = $this->flexibleContext->assertSelenium2Driver('Assert Alert');
        $session = $this->flexibleContext->getSession();

        try {
            $actual = $driver->getWebDriverSession()->getAlert_text();
        } catch (NoAlertOpenError $e) {
            throw new ExpectationException('No alert is open', $session);
        }

        if (strpos($actual, $expected) === false) {
            throw new ExpectationException("Text '$expected' not found in alert", $session);
        }
    }

    /**
     * Fills in the given text to the current JavaScript prompt.
     *
     * @When   /^(?:|I )fill "(?P<message>[^"]*)" into the prompt$/
     *
     * @param string $message the text to fill in
     *
     * @throws UnsupportedDriverActionException if the current driver does not support setting the alert text
     */
    public function setAlertText(string $message): void
    {
        $this->flexibleContext->assertSelenium2Driver('Set Alert')->getWebDriverSession()
            ->postAlert_text(['text' => $message]);
    }
}
