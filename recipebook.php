<?php
namespace Grav\Plugin;

use \PDO;
use \DirectoryIterator;

use Grav\Common\Page\Page;
use Grav\Common\Page\Pages;
// use Grav\Common\User\User;
use Grav\Plugin\Login\Login;
use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;

require 'classes/Recipe.php';

// the 'use' keyword here works better
// also drop the .php extension
// include 'classes\recipe.php';

/**
 * Class RecipebookPlugin
 * @package Grav\Plugin
 */
class RecipebookPlugin extends Plugin
{
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
            'onPluginsInitialized'     => ['onPluginsInitialized', 1]
            , 'onTwigTemplatePaths'    => ['onTwigTemplatePaths', 0]
            , 'onTask.recipebook.new'  => ['newRecipe']
            , 'onTask.recipebook.edit' => ['editRecipe']
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
        $view_len = strlen($this->config->get('plugins.recipebook.route_view'));
        $edit_len = strlen($this->config->get('plugins.recipebook.route_edit'));

        if(
            $uri->path()                          == $this->config->get('plugins.recipebook.route_new')
            or $uri->path()                       == $this->config->get('plugins.recipebook.route_list')
            or substr($uri->path(), 0, $view_len) == $this->config->get('plugins.recipebook.route_view')
            or substr($uri->path(), 0, $edit_len) == $this->config->get('plugins.recipebook.route_edit')
        ) {
            $this->enable([
                'onPageInitialized' => ['onPageInitialized', 1]
            ]);

            $this->init_db();
        }

        return;
    }

    public function init_db()
    {
        // if(!file_exists(DATA_DIR . "/recipebook.db")) {
        //     $this->grav['debugger']->addMessage('Recipebook database not found. Building a new one...');

        //     $this->db = new PDO('sqlite:' . DATA_DIR . 'recipebook.db');
        //     $this->db->exec($this->queries['build_db']);
        // }

        try {
            $this->db = new PDO('sqlite:' . DATA_DIR . 'recipebook.db');
        } catch(Exception $e) {
            $this->grav['debugger']->addMessage($e);
            return false;
        }

        return true;
    }

    public function onPageInitialized()
    {
        $uri  = $this->grav['uri'];
        $page = $this->grav['page'];

        if(!$page) {
            return;
        }

        // page merging should be done here

        $page     = new Page;
        $view_len = strlen($this->config->get('plugins.recipebook.route_view'));
        $edit_len = strlen($this->config->get('plugins.recipebook.route_edit'));

        if ( $uri->path() == $this->config->get('plugins.recipebook.route_list') ) {
            $page->init(new \SplFileInfo(__DIR__ . "/pages/recipebook.md"));

            $this->getRecipes();
        }

        else if( $uri->path() == $this->config->get('plugins.recipebook.route_new') ) {
            $page->init(new \SplFileInfo(__DIR__ . "/pages/new_recipe.md"));
        }

        else if( substr($uri->path(), 0, $view_len) == $this->config->get('plugins.recipebook.route_view') ) {
            $page->init(new \SplFileInfo(__DIR__ . "/pages/recipe.md"));
            $id = substr($uri->path(), $view_len + 1);
            $this->getRecipe($id);
        }

        else if( substr($uri->path(), 0, $edit_len) == $this->config->get('plugins.recipebook.route_edit') ) {
            $page->init(new \SplFileInfo(__DIR__ . "/pages/edit_recipe.md"));
            $id = substr($uri->path(), $edit_len+1);
            $this->getRecipe($id);
        }
        
        $page->parent($this->grav['pages']->find($this->config->get('plugins.recipebook.route_list')));
        $page->slug(basename($uri->path()));
        $page->route($uri->path());

        $this->grav['pages']->addPage($page, $uri->path());

        unset($this->grav['page']);
        $this->grav['page'] = $page;

        $this->grav['debugger']->addMessage($page->active());
        $this->grav['debugger']->addMessage($this->grav['pages']->routes());
    }
    
    public function getRecipes()
    {
        $this->grav['debugger']->addMessage('Getting recipe listing');
        
        $ret  = array();
        $stmt = $this->db->prepare("select uuid, name from recipes order by name;");
        $stmt->execute();

        $ret = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->grav['twig']->twig_vars['recipes'] = $ret;
        $this->grav['debugger']->addMessage('Retrieved ' . count($ret) . ' recipes');
    }

    public function getRecipe($id)
    {
        $this->grav['debugger']->addMessage('Getting recipe: ' . $id);
        $recipe = new Recipe($this->db, $id);
        $this->grav['debugger']->addMessage('Got recipe: ' . $recipe->name);
        $this->grav['twig']->twig_vars['recipe'] = $recipe->jsonSerialize();
    }

    public function onTwigTemplatePaths()
    {
        $twig = $this->grav['twig'];
        $twig->twig_paths[] = __DIR__ . '/templates';
    }

    public function newRecipe()
    {
        $user = $this->grav['user']->username;

        // set the base recipe values
        $recipe = new Recipe($this->db);
        $recipe->set_user($user);
        $recipe->build_from_post($_POST);
        $recipe->save_recipe();

        $redirect_route = $this->config->get('plugins.recipebook.route_view') . '/' . $uuid;
        $this->grav->redirect($redirect_route, 302);
    }

    public function editRecipe() 
    {
        $path     = $this->grav['uri']->path();
        $edit_len = strlen($this->config->get('plugins.recipebook.route_edit'));
        $uuid     = substr($this->grav['uri']->path(), $edit_len+1, strlen($path));
        $user     = $this->grav['user']->username;

        // set the base recipe values
        $recipe = new Recipe($this->db, $uuid);
        $recipe->set_user($user);
        $recipe->build_from_post($_POST);

        // allow the recipe object to handle the db stuff
        $recipe->update_recipe();

        // redirect to the recipe view page
        $redirect_route = $this->config->get('plugins.recipebook.route_view') . '/' . $uuid;
        $this->grav->redirect($redirect_route, 302);

        return;
    }
}
