<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ExpectationException;
use Carbon\Carbon;
use features\Extensions\Assertion\AssertionContext;
use Medology\Behat\Mink\UsesFlexibleContext;
use Medology\Behat\Mink\UsesWebDownloadContext;
use Medology\Behat\TypeCaster;
use Medology\Behat\UsesStoreContext;
use Medology\Spinner;

class FeatureContext implements Context
{
    // Depends
    use AssertionContext;
    use TypeCaster;
    use UsesFlexibleContext;
    use UsesStoreContext;
    use UsesWebDownloadContext;

    /**
     * Starts the WebDriver session before each scenario.
     *
     * Mink no longer automatically starts the WebDriver session automatically, so we do that here.
     *
     * @BeforeScenario
     */
    public function beforeScenario(BeforeScenarioScope $scope): void
    {
        // Browser setup
        $this->gatherFlexibleContext($scope);
        $this->startWebDriverSession();
    }

    /**
     * Places an object with the given structure into the store.
     *
     * @Given the following is stored as :key:
     *
     * @param string    $key        the key to put the object into the store under
     * @param TableNode $attributes the attributes of the object to create
     */
    public function putStoreStep($key, TableNode $attributes): void
    {
        $this->storeContext->set($key, (object) ($attributes->getRowsHash()));
    }

    /**
     * Places the given arbitrary value into the store.
     *
     * @Given the value :value is stored as :key
     * @Given the following string is stored as :key:
     *
     * @param mixed  $value the value to put into the store
     * @param string $key   the key to put the value into the store under
     */
    public function putSingleStoreStep($value, $key): void
    {
        if ($value instanceof PyStringNode) {
            $value = $value->getRaw();
        }

        $this->storeContext->set($key, $value);
    }

    /**
     * Clears the Carbon test now value.
     *
     * @Given the datetime is :value
     */
    public function setTestNow(string $value): void
    {
        Carbon::setTestNow($value);
    }

    /**
     * Clears the Carbon test now value.
     *
     * @BeforeScenario
     */
    public function clearCarbonTestNow(): void
    {
        Carbon::setTestNow(null);
    }

    /**
     * Decreases the default timeout for the sake of testing failing assertions more quickly.
     *
     * @BeforeSuite
     */
    public static function setSpinnerTimeout(): void
    {
        Spinner::$default_timeout = 5;
    }

    /**
     * Waits a specific amount of time, and then visits the specified path.
     *
     * @Given I will be on :path in :timeout seconds
     *
     * @param string $path    the path to visit
     * @param int    $timeout the time to wait before visiting the path
     */
    public function visitPathDelayed($path, $timeout): void
    {
        $path = json_encode($path); // Quick and painless quotation wrapping + escaping.
        $timeout *= 1000;

        $this->flexibleContext->getSession()->executeScript(
            <<<JS
            window.setTimeout(function() {
                window.location = $path;
            }, $timeout);
JS
        );
    }

    /**
     * Causes a prompt/alert to pop up in the browser, and stores its return value in the store.
     *
     * @Given /^there is an? (?P<type>alert|confirm|prompt) containing (?P<text>".+")$/
     *
     * @param string $type whether to create an alert, confirmation dialog, or prompt
     * @param string $text the text to show in the popup
     */
    public function ensureAlertExists($type, $text): void
    {
        $text = json_encode($text); // Free character escaping, quoting, etc.

        $this->flexibleContext->getSession()->executeScript("{$type}_result = $type($text)");
    }

    /**
     * Asserts that the prompt from ensureAlertExists returns the correct value.
     *
     * @Then /^the (?P<type>alert|confirm|prompt) should return (?P<value>.+)$/
     *
     * @param string $type   the type of popup to check results for
     * @param mixed  $result the expected result
     *
     * @throws ExpectationException if the actual result does not match the expected results
     */
    public function assertAlertResult($type, $result): void
    {
        $actual = $this->flexibleContext->getSession()->evaluateScript("{$type}_result");

        if ($actual !== $result) {
            $expected = json_encode($result);
            $actual = json_encode($actual);

            throw new ExpectationException("Expected $expected, got $actual", $this->flexibleContext->getSession());
        }
    }

    /**
     * Asserts that an image finished loading.
     *
     * @Then I should see :imgSrc image in :locator
     *
     * @param string $imgSrc  The source of the image
     * @param string $locator The id of the image tag
     *
     * @throws ExpectationException If the <img> tag is not found
     * @throws ExpectationException If the image is not loaded
     */
    public function assertImageLoaded($imgSrc, $locator): void
    {
        $session = $this->flexibleContext->getSession();
        $image = $session->getPage()->find('css', "img#$locator");

        if (!$image) {
            throw new ExpectationException("Expected an img tag with id '$locator'. Found none!", $session);
        }

        if (!$this->webDownloadContext->checkImageLoaded($image->getXpath(), $imgSrc)) {
            throw new ExpectationException("Expected img '$locator' to load. Instead it did not!", $session);
        }
    }

    /**
     * Asserts that an image did NOT load.
     *
     * @Then I should not see an image in :locator
     *
     * @param string $locator The id of the image tag
     *
     * @throws ExpectationException If the <img> tag is not found
     * @throws ExpectationException If the image is loaded
     */
    public function assertImageNotLoaded($locator): void
    {
        $image = $this->flexibleContext->getSession()->getPage()->find('css', "img#$locator");

        if (!$image) {
            throw new ExpectationException("Expected an img tag with id '$locator'. Found none!", $this->flexibleContext->getSession());
        }

        if ($this->webDownloadContext->checkImageLoaded($image->getXpath())) {
            throw new ExpectationException("Expected img '$locator' to not load. Instead it did load!", $this->flexibleContext->getSession());
        }
    }

    /**
     * Starts the WebDriver session if it is not already started.
     */
    protected function startWebDriverSession(): void
    {
        $minkSession = $this->flexibleContext->getSession();
        if (!$minkSession->isStarted()) {
            $minkSession->start();
        }
    }
}
