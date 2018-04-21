<?php

namespace ImageView\View;

use Cake\Core\Configure;
use Cake\Core\Exception\Exception;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use HtmlToImageView\View\HtmlToImageView;

/**
 * HtmlToImageView Test
 *
 * @property HtmlToImageView View
 */
class HtmlToImageViewTest extends TestCase
{

    public $autoFixtures = false;

    /**
     * setup callback
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        Configure::write('HtmlToImageView.binary', '/bin/echo');
        $request = new ServerRequest();
        $response = new Response();
        $this->View = new HtmlToImageView($request, $response);
    }

    /**
     * Tests constructor
     */
    public function testConstructor()
    {
        $request = new ServerRequest();
        $response = new Response();
        $this->View = new HtmlToImageView($request, $response);
        $this->assertEquals('img', $this->View->subDir);
        $this->assertEquals('img', $this->View->getLayoutPath());

        $this->View = new HtmlToImageView($request, $response, null, ['templatePath' => 'Error']);
        $this->assertNull($this->View->subDir);
        $this->assertNull($this->View->getLayoutPath());
        $this->assertEquals('text/html', $this->View->response->getType());
    }

    /**
     * Tests render method
     *
     * @throws \Exception
     */
    public function testRender()
    {
        $request = new ServerRequest();
        $response = new Response(['type' => 'image/jpeg']);
        $this->View = $this->getMockBuilder('HtmlToImageView\View\HtmlToImageView')
            ->setConstructorArgs([$request, $response])
            ->setMethods(['output'])
            ->getMock();

        $this->View
            ->expects($this->once())
            ->method('output')
            ->with('')
            ->will($this->returnValue($expected = 'output'));

        $result = $this->View->render(false, false);

        $this->assertEquals($expected, $result);
    }

    /**
     * Tests output method
     */
    public function testOutput()
    {
        $request = new ServerRequest();
        $response = new Response();
        $this->View = $this->getMockBuilder('HtmlToImageView\View\HtmlToImageView')
            ->setConstructorArgs([$request, $response])
            ->setMethods(['_exec'])
            ->getMock();

        $this->View
            ->expects($this->once())
            ->method('_exec')
            ->with('/bin/echo --format \'jpg\' --quiet - -', 'html')
            ->will($this->returnValue([
                'stdout' => $expected = 'output',
            ]));

        $result = $this->View->output('html');

        $this->assertEquals($expected, $result);
    }

    /**
     * Tests output method when no data returned
     */
    public function testOutputNoData()
    {
        $request = new ServerRequest();
        $response = new Response();
        $this->View = $this->getMockBuilder('HtmlToImageView\View\HtmlToImageView')
            ->setConstructorArgs([$request, $response])
            ->setMethods(['_exec'])
            ->getMock();

        $this->View
            ->expects($this->once())
            ->method('_exec')
            ->with('/bin/echo --format \'jpg\' --quiet - -', 'html')
            ->will($this->returnValue([
                'stderr' => '',
                'stdout' => '',
            ]));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("wkhtmltoimage didn't return any data");

        $this->View->output('html');
    }

    /**
     * Tests output method when error returned
     */
    public function testOutputError()
    {
        $request = new ServerRequest();
        $response = new Response();
        $this->View = $this->getMockBuilder('HtmlToImageView\View\HtmlToImageView')
            ->setConstructorArgs([$request, $response])
            ->setMethods(['_exec'])
            ->getMock();

        $this->View
            ->expects($this->once())
            ->method('_exec')
            ->with('/bin/echo --format \'jpg\' --quiet - -', 'html')
            ->will($this->returnValue([
                'stderr' => 'wrong',
            ]));

        $this->expectException(Exception::class);
        $this->expectExceptionMessageRegExp('/System error "wrong" when executing command/');

        $this->View->output('html');
    }

    /**
     * Tests that the view generates the right command
     *
     * @throws \ReflectionException
     */
    public function testGetCommand()
    {
        $request = new ServerRequest();
        $response = new Response();

        $class = new \ReflectionClass('HtmlToImageView\View\HtmlToImageView');
        $method = $class->getMethod('_getCommand');
        $method->setAccessible(true);

        $this->View = new HtmlToImageView($request, $response);
        $result = $method->invokeArgs($this->View, []);
        $expected = "/bin/echo --format 'jpg' --quiet - -";
        $this->assertEquals($expected, $result);

        Configure::write('HtmlToImageView.binary', '/bin/sh');
        $this->View = new HtmlToImageView($request, $response, null, [
            'imageOptions' => [
                'crop-w' => 100,
                'crop-h' => 200,
                'crop-x' => 300,
                'crop-y' => 400,
                'width' => 500,
                'height' => 600,
                'format' => 'png',
                'quality' => 50,
                'zoom' => 1.5,
            ]
        ]);
        $result = $method->invokeArgs($this->View, []);
        $expected = "/bin/sh --crop-w '100' --crop-h '200' --crop-x '300' --crop-y '400' --width '500' --height '600' --format 'png' --quality '50' --zoom '1.5' --quiet - -";
        $this->assertEquals($expected, $result);

        Configure::write('HtmlToImageView.binary', '/bin/nonexisting');
        $this->View = new HtmlToImageView($request, $response);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('wkhtmltoimage binary is not found or not executable: /bin/nonexisting');
        $method->invokeArgs($this->View, []);
    }

    /**
     * Tests _exec method
     *
     * @throws \ReflectionException
     */
    public function testExec()
    {
        $request = new ServerRequest();
        $response = new Response();

        $class = new \ReflectionClass('HtmlToImageView\View\HtmlToImageView');
        $method = $class->getMethod('_exec');
        $method->setAccessible(true);

        $this->View = new HtmlToImageView($request, $response);

        $result = $method->invokeArgs($this->View, ['/bin/echo test', 'test']);
        $expected = [
            'stdout' => "test\n",
            'stderr' => '',
            'return' => 0,
        ];
        $this->assertEquals($expected, $result);

        $result = $method->invokeArgs($this->View, ['/bin/nonexisting test', 'test']);
        $expected = [
            'stdout' => '',
            'stderr' => "sh: 1: /bin/nonexisting: not found\n",
            'return' => 127,
        ];
        $this->assertEquals($expected, $result);
    }
}
