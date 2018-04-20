<?php
namespace HtmlToImageView\View;

use Cake\Core\Exception\Exception;
use Cake\Event\EventManager;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Utility\Hash;
use Cake\View\View;

/**
 * HtmlToImageView
 *
 * @link https://book.cakephp.org/3.0/en/views.html#the-app-view
 */
class HtmlToImageView extends View
{

    /**
     * Path to the wkhtmltoimage executable binary
     *
     * @var string
     */
    protected $_binary = '/usr/bin/wkhtmltoimage';

    /**
     * Flag to indicate if the environment is windows
     *
     * @var bool
     */
    protected $_windowsEnvironment;

    /**
     * The subdirectory. Image views are always in img.
     *
     * @var string|null
     */
    public $subDir = 'img';

    /**
     * The name of the layouts sub folder containing layouts for this View.
     *
     * @var string|null
     */
    public $layoutPath = 'img';

    /**
     * List of image config collected from the associated controller.
     *
     * @var array
     */
    public $imageConfig = [];

    /**
     * Constructor
     *
     * @param \Cake\Http\ServerRequest $request Request instance.
     * @param \Cake\Http\Response $response Response instance.
     * @param \Cake\Event\EventManager $eventManager Event manager instance.
     * @param array $viewOptions View options. See View::$_passedVars for list of
     *   options which get set as class properties.
     *
     * @throws \Cake\Core\Exception\Exception
     */
    public function __construct(
        ServerRequest $request = null,
        Response $response = null,
        EventManager $eventManager = null,
        array $viewOptions = []
    ) {
        $this->_passedVars[] = 'imageConfig';

        parent::__construct($request, $response, $eventManager, $viewOptions);

        if (Hash::get($viewOptions, 'templatePath') == 'Error') {
            $this->subDir = null;
            $this->layoutPath = null;
            $this->response = $this->response->withType('html');

            return;
        }

        if ($binary = Hash::get($this->imageConfig, 'binary')) {
            $this->_binary = $binary;
        }

        if (!Hash::get($this->imageConfig, 'options.format')) {
            $this->imageConfig['options']['format'] = $this->request->getParam('_ext', 'jpg');
        }

        $this->response = $this->response->withType($this->imageConfig['options']['format']);
    }

    /**
     * Render an image view.
     *
     * @param string $view The view being rendered.
     * @param string $layout The layout being rendered.
     * @return string The rendered view.
     * @throws \Exception
     */
    public function render($view = null, $layout = null)
    {
        $content = parent::render($view, $layout);

        if ($this->response->getType() === 'text/html') {
            return $content;
        }

        $this->Blocks->set('content', $this->output($content));

        return $this->Blocks->get('content');
    }

    /**
     * Generates image from html
     *
     * @param string $html Input html
     * @return string Raw image data
     */
    public function output($html)
    {
        $command = $this->_getCommand();
        $content = $this->_exec($command, $html);

        if ($error = Hash::get($content, 'stderr')) {
            throw new Exception(sprintf(
                'System error "%s" when executing command "%s". Try using the binary provided on http://wkhtmltopdf.org/downloads.html',
                $error,
                $command
            ));
        }

        if (!$output = Hash::get($content, 'stdout')) {
            throw new Exception("wkhtmltoimage didn't return any data");
        }

        return $output;
    }

    /**
     * Execute the WkHtmlToImage command to render image
     *
     * @param string $cmd the command to execute
     * @param string $input Html to pass to wkhtmltoimage
     * @return array the result of running the command to generate the image
     */
    protected function _exec($cmd, $input)
    {
        $result = ['stdout' => '', 'stderr' => '', 'return' => ''];

        $process = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        fwrite($pipes[0], $input);
        fclose($pipes[0]);

        $result['stdout'] = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $result['stderr'] = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $result['return'] = proc_close($process);

        return $result;
    }

    /**
     * Get the command to render an image
     *
     * @return string the command for generating the image
     * @throws \Cake\Core\Exception\Exception
     */
    protected function _getCommand()
    {
        if (!is_executable($this->_binary)) {
            throw new Exception(sprintf('wkhtmltoimage binary is not found or not executable: %s', $this->_binary));
        }

        $options = (array)Hash::get($this->imageConfig, 'options', []);

        $options = array_intersect_key(
            $options,
            array_flip(['crop-w', 'crop-h', 'crop-x', 'crop-y', 'width', 'height', 'format', 'quality', 'zoom'])
        );
        $options['quiet'] = true;

        if ($this->_windowsEnvironment) {
            $command = '"' . $this->_binary . '"';
        } else {
            $command = $this->_binary;
        }

        foreach ($options as $key => $value) {
            if (empty($value)) {
                continue;
            } elseif (is_array($value)) {
                foreach ($value as $k => $v) {
                    $command .= sprintf(' --%s %s %s', $key, escapeshellarg($k), escapeshellarg($v));
                }
            } elseif ($value === true) {
                $command .= ' --' . $key;
            } else {
                $command .= sprintf(' --%s %s', $key, escapeshellarg($value));
            }
        }
        $command .= " - -";

        if ($this->_windowsEnvironment) {
            $command = '"' . $command . '"';
        }

        return $command;
    }
}
