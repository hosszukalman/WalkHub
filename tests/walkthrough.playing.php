<?php
/**
 * @file
 * Test Walkthrough playing processes.
 */


require_once './vendor/autoload.php';
require_once './wt_selenium_testcase.inc';

class WalkthroughPlaying extends WalkhubSeleniumTestCase {

  public function provider() {
    // scan walkthroughs/ directory
    $allcontent = scandir('./walkthroughs');
    $relevantcontent = array_diff($allcontent, array('..', '.'));
    $converted = array_map(function ($item) {
      return array($item);
    }, $relevantcontent);
    return array_values($converted);
  }

  /**
   * @dataProvider provider
   */
  public function testWalkthrough($filename) {
    $test = file_get_contents("walkthroughs/{$filename}");

    $title = $this->randomString();

    $this->adminLogin();

    mock($this->byLinkText('Import Walkthrough'))->click();

    mock($this->byId('edit-selenium-code'))->value($test);
    mock($this->byId('edit-next'))->click();

    mock($this->byId('edit-title'))->value($title);
    mock($this->byId('edit-body'))->value($this->randomString());
    mock($this->byId('edit-save'))->click();

    $this->assertEquals($title, mock($this->byId('page-title'))->text());

    mock($this->byLinkText('Start walkthrough'))->click();
    $wu = new PHPUnit_Extensions_Selenium2TestCase_WaitUntil($this);
    $self = $this;
    $wu->run(function () use ($self) {
      return ($item = $self->byXPath('//button[@type=\'button\']')) ? $item : NULL;
    }, 30000);
    mock($this->byXPath("//button[@type='button']"))->click();

    $this->frame($this->byCssSelector('#ui-id-2'));

    $steps = $this->numberOfSteps($test);
    for ($i = 0; $i <= $steps; $i++) {
      mock($this->byCssSelector('.wtbubble-next'))->click();
    }

    $finish_button = $this->byLinkText('Finish');
    $this->assertTrue((bool)$finish_button, 'Walkthrough did not finish correctly');
  }

  protected function numberOfSteps($test) {
    $doc = new DOMDocument();
    $doc->loadHTML($test);
    $xpath = new DOMXpath($doc);
    $elements = $xpath->query('//tbody/tr');
    return $elements ? $elements->length-2 : NULL;
  }
}

