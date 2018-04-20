<?php

namespace ImageView\View;

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

        $request = new ServerRequest();
        $response = new Response();
        $this->View = new HtmlToImageView($request, $response);
        $this->View->setLayoutPath('img');
    }

    /**
     * Tests render method
     *
     * @throws \Exception
     */
    public function testRender()
    {
        $request = new ServerRequest();
        $response = new Response();
        $this->View = $this->getMockBuilder('HtmlToImageView\View\HtmlToImageView')
            ->setConstructorArgs([
                $request,
                $response,
                null,
                [
                    'imageConfig' => [
                        'binary' => '/bin/echo'
                    ]
                ]
            ])
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
     * Tests output method when no data returned
     */
    public function testOutputNoData()
    {
        $request = new ServerRequest();
        $response = new Response();
        $this->View = $this->getMockBuilder('HtmlToImageView\View\HtmlToImageView')
            ->setConstructorArgs([
                $request,
                $response,
                null,
                [
                    'imageConfig' => [
                        'binary' => '/bin/echo'
                    ]
                ]
            ])
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
            ->setConstructorArgs([
                $request,
                $response,
                null,
                [
                    'imageConfig' => [
                        'binary' => '/bin/echo'
                    ]
                ]
            ])
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
            ->with('/usr/bin/wkhtmltoimage --format \'jpg\' --quiet - -', 'html')
            ->will($this->returnValue([
                'stdout' => $expected = 'output',
            ]));

        $result = $this->View->output('html');

        $this->assertEquals($expected, $result);
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

        $this->View = new HtmlToImageView($request, $response, null, [
            'imageConfig' => [
                'binary' => '/bin/echo'
            ]
        ]);
        $result = $method->invokeArgs($this->View, []);
        $expected = "/bin/echo --format 'jpg' --quiet - -";
        $this->assertEquals($expected, $result);

        $this->View = new HtmlToImageView($request, $response, null, [
            'imageConfig' => [
                'binary' => '/bin/sh',
                'options' => [
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
            ]
        ]);
        $result = $method->invokeArgs($this->View, []);
        $expected = "/bin/sh --crop-w '100' --crop-h '200' --crop-x '300' --crop-y '400' --width '500' --height '600' --format 'png' --quality '50' --zoom '1.5' --quiet - -";
        $this->assertEquals($expected, $result);

        $this->View = new HtmlToImageView($request, $response, null, [
            'imageConfig' => [
                'binary' => '/bin/nonexisting',
            ]
        ]);
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

        $this->View = new HtmlToImageView($request, $response, null, [
            'imageConfig' => [
                'binary' => '/bin/echo'
            ]
        ]);

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
