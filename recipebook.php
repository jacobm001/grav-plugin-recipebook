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

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized()
    {
        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
            return;
        }

        $uri = $this->grav['uri'];
        $len = strlen('/recipes/');


        if (substr($uri->path(), 0, $len) == '/recipes/') {
            $this->enable(['onPageInitialized' => ['onPageInitialized', 0]]);
        }

        return;
    }

    public function onPageInitialized()
    {
        $this->grav['debugger']->addMessage('Making a page!');

        $uri  = $this->grav['uri'];
        $page = $this->grav['page'];

        $page = new Page;
        $page->init(new \SplFileInfo(__DIR__ . "/templates/flex/recipes/object/default.html.twig"));
        $page->parent($this->grav['pages']->find('/recipes'));
        $page->slug(basename($uri->path()));
        $page->route($uri->path());

        $id     = substr($uri->path(), strlen('/plugins/'));
        $object = Grav::instance()->get('flex')->getObject($id, 'recipes');

        $block = $object->render('default', ['my_variable' => true]);
        $page->setRawContent($block);

        $this->grav['debugger']->addMessage('Object Id: ' . $id);
        $this->grav['debugger']->addMessage('Object: ' . $object);

        $this->grav['pages']->addPage($page, $uri->path());

        unset($this->grav['page']);
        $this->grav['page'] = $page;

        return;
    }

    public function onTwigTemplatePaths()
    {
        $twig = $this->grav['twig'];
        $twig->twig_paths[] = __DIR__ . '/templates';
    }
}
