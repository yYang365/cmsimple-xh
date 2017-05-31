<?php

/**
 * Testing the CoreTextFileEdit class.
 *
 * @category  Testing
 * @package   XH
 * @author    The CMSimple_XH developers <devs@cmsimple-xh.org>
 * @copyright 2013-2017 The CMSimple_XH developers <http://cmsimple-xh.org/?The_Team>
 * @license   http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link      http://cmsimple-xh.org/
 */

namespace XH;

use org\bovigo\vfs\vfsStreamWrapper;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStream;

/**
 * A test case for the CoreTextFileEdit class.
 *
 * @category Testing
 * @package  XH
 * @author   The CMSimple_XH developers <devs@cmsimple-xh.org>
 * @license  http://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 * @link     http://cmsimple-xh.org/
 * @since    1.6
 */
class CoreTextFileEditTest extends TestCase
{
    private $subject;

    private $testFile;

    public function setUp()
    {
        global $pth, $sn, $file, $_XH_csrfProtection;

        $this->setConstant('CMSIMPLE_URL', 'http://example.com/xh/');
        vfsStreamWrapper::register();
        vfsStreamWrapper::setRoot(new vfsStreamDirectory('test'));
        $this->testFile = vfsStream::url('test/template.htm');
        file_put_contents($this->testFile, '<html>');
        $file = 'template';
        $sn = '/xh/';
        $pth['file']['template'] = $this->testFile;
        $_XH_csrfProtection = $this->getMockBuilder('XH\CSRFProtection')
            ->disableOriginalConstructor()->getMock();
        $this->setUpLocalization();
        $this->subject = new CoreTextFileEdit();
    }

    private function setUpLocalization()
    {
        global $tx;

        $tx = array(
            'action' => array(
                'save' => 'save'
            ),
            'filetype' => array(
                'template' => 'template'
            ),
            'message' => array(
                'saved' => 'Saved %s'
            )
        );
    }

    public function testFormAttributes()
    {
        $this->assertXPath(
            '//form[@method="post" and @action="/xh/"]',
            $this->subject->form()
        );
    }

    public function testFormContainsTextarea()
    {
        $this->assertXPathContains(
            '//form/textarea[@name="text" and @class="xh_file_edit"]',
            '<html>',
            $this->subject->form()
        );
    }

    public function testFormContainsSubmitButton()
    {
        $this->assertXPath(
            '//form//input[@type="submit" and @class="submit" and @value="Save"]',
            $this->subject->form()
        );
    }

    public function testFormContainsFileInput()
    {
        global $file;

        $this->assertXPath(
            sprintf('//input[@type="hidden" and @name="file" and @value="%s"]', $file),
            $this->subject->form()
        );
    }

    public function testFormContainsActionInput()
    {
        $this->assertXPath(
            '//input[@type="hidden" and @name="action" and @value="save"]',
            $this->subject->form()
        );
    }

    public function testSuccessMessage()
    {
        $_GET['xh_success'] = 'template';
        $this->assertXPathContains(
            '//p[@class="xh_success"]',
            'Saved Template',
            $this->subject->form()
        );
    }

    public function testSubmit()
    {
        $headerSpy = $this->getFunctionMock('header', $this->subject);
        $headerSpy->expects($this->once())->with(
            $this->equalTo(
                'Location: ' . CMSIMPLE_URL
                . '?file=template&action=edit&xh_success=template'
            )
        );
        $exitSpy = $this->getFunctionMock('XH_exit', $this->subject);
        $exitSpy->expects($this->once());
        $_POST = array('text' => '</html>');
        $this->subject->submit();
    }

    public function testSubmitCantSave()
    {
        $writeFileStub = $this->getFunctionMock('XH_writeFile', $this->subject);
        $writeFileStub->expects($this->once())->will($this->returnValue(false));
        $eSpy = $this->getFunctionMock('e', $this->subject);
        $eSpy->expects($this->once())->with(
            $this->equalTo('cntsave'),
            $this->equalTo('file'),
            $this->equalTo($this->testFile)
        );
        $_POST = array('text' => '</html>');
        $this->subject->submit();
    }
}
