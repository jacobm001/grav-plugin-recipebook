<?php
namespace Grav\Plugin;

use \DirectoryIterator;

use Grav\Common\Grav;
use Grav\Common\Page\Page;
use Grav\Common\Page\Pages;
// use Grav\Common\User\User;
// use Grav\Plugin\Login\Login;
use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;
use Grav\Framework\Flex\Interfaces\FlexObjectInterface;

// the 'use' keyword here works better
// also drop the .php extension
// include 'classes\recipe.php';

/**
 * Class RecipebookPlugin
 * @package Grav\Plugin
 */
class RecipebookPlugin extends Plugin
{

    public $features = [
        'blueprints' => 100,
    ];

    public function onPluginsInitialized()
    {
        if ($this->isAdmin()) {
            return;
        }

        $this->enable([
            'onTwigInitialized' => ['onTwigInitialized', 0]
        ]);
    }

    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents()
    {
        return [
            'onTwigTemplatePaths' => ['onTwigTemplatePaths', 0]
            , 'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }

    public function onTwigInitialized(Event $e)
    {
        $this->grav['twig']->twig()->addFilter(
            new \Twig_SimpleFilter('vulgarize', [$this, 'vulgarizeString'])
        );
    }

    public function vulgarizeString($string)
    {
        $search = array(
            '1/2', '1/4', '3/4', '1/7', '1/9', '1/10', '1/3', '2/3', '1/5'
            , '2/5', '3/5', '4/5', '1/6', '5/6', '1/8', '3/8', '5/8', '7/8'
        );

        $replace = array(
            '½', '¼', '¾', '⅐', '⅑', '⅒', '⅓', '⅔', '⅕', '⅖', '⅗', '⅘'
            , '⅙', '⅚', '⅛', '⅜', '⅝', '⅞'
        );

        return mb_convert_encoding(str_replace($search, $replace, $string), 'UTF-8');
    }

    public function onTwigTemplatePaths()
    {
        $twig = $this->grav['twig'];
        $twig->twig_paths[] = __DIR__ . '/templates';
    }
}
