<?php

namespace ImageView\View;

use Cake\Core\Configure;
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

    public function testRender()
    {
        $this->markTestIncomplete();
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
