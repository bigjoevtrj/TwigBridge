<?php

/**
 * This file is part of the TwigBridge package.
 *
 * @copyright Robert Crowe <hello@vivalacrowe.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TwigBridge;

use Illuminate\Foundation\Application;
use Twig_Environment;

/**
 * TwigBridge deals with creating an instance of Twig.
 */
class TwigBridge
{
    /**
     * @var string TwigBridge version
     */
    const VERSION = '0.6.0';

    /**
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Create a new instance.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get Twig template extension.
     *
     * @return string
     */
    public function getExtension()
    {
        return $this->app['config']->get('twigbridge::twig.extension');
    }

    /**
     * Get extensions that Twig should load.
     *
     * @return array
     */
    public function getExtensions()
    {
        return $this->app['twig.extensions'];
    }

    /**
     * Get options passed to Twig_Environment.
     *
     * @return array
     */
    public function getTwigOptions()
    {
        return $this->app['twig.options'];
    }

    /**
     * Get the lexer for Twig to use.
     *
     * @param \Twig_Environment $twig
     *
     * @return \TwigBridge\Twig\Lexer
     */
    public function getLexer(Twig_Environment $twig)
    {
        $delimiters = $this->app['config']->get('twigbridge::twig.delimiters');

        $lexer = new Twig\Lexer(
            $delimiters['tag_comment'],
            $delimiters['tag_block'],
            $delimiters['tag_variable']
        );

        return $lexer->getLexer($twig);
    }

    /**
     * Gets an instance of Twig that can be used to render a view.
     *
     * @return \Twig_Environment
     */
    public function getTwig()
    {
        $twig = new Twig_Environment($this->app['twig.loader'], $this->getTwigOptions());

        // Load extensions
        foreach ($this->app['twig.extensions'] as $extension) {
            // Get an instance of the extension
            // Support for string, closure and an object
            if (is_string($extension)) {
                $extension = new $extension($this->app, $twig);
            } elseif (is_callable($extension)) {
                $extension = $extension($this->app, $twig);
            } elseif (!is_object($extension)) {
                throw new InvalidArgumentException('Incorrect extension type');
            }

            // Add extension to twig
            $twig->addExtension($extension);
        }

        $this->app['events']->fire('twigbridge.twig', array('twig' => $twig));

        // Set template tags
        $twig->setLexer($this->getLexer($twig));

        return $twig;
    }
}
