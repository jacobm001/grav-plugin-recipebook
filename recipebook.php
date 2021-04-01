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
            'onPluginsInitialized'     => ['onPluginsInitialized', 0]
            , 'onTwigTemplatePaths'    => ['onTwigTemplatePaths', 0]
        ];
    }

    public function onTwigTemplatePaths()
    {
        $twig = $this->grav['twig'];
        $twig->twig_paths[] = __DIR__ . '/templates';
    }
}
