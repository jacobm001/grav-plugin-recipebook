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

            $this->get_queries();
            $this->init_db();
        }

        return;
    }

    public function init_db()
    {
        if(!file_exists(DATA_DIR . "/recipebook.db")) {
            $this->grav['debugger']->addMessage('Recipebook database not found. Building a new one...');

            $this->db = new PDO('sqlite:' . DATA_DIR . 'recipebook.db');
            $this->db->exec($this->queries['build_db']);
        }

        try {
            $this->db = new PDO('sqlite:' . DATA_DIR . 'recipebook.db');
        } catch(Exception $e) {
            $this->grav['debugger']->addMessage($e);
            return false;
        }

        return true;
    }

    public function get_queries()
    {
        $dir = new DirectoryIterator(__DIR__ . "/queries");
        $this->queries = [];

        foreach($dir as $fileinfo) {
            if(!$fileinfo->isDir()) {
                $name = $fileinfo->getBasename('.sql');
                $text = file_get_contents($fileinfo->getPathName());

                $this->queries[$name] = $text;
            }
        }
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
        $stmt = $this->db->prepare($this->queries['get_recipes']);
        $stmt->execute();

        $ret = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->grav['twig']->twig_vars['recipes'] = $ret;
        $this->grav['debugger']->addMessage('Finished getting listing');
    }

    public function getRecipe($id)
    {
        $this->grav['debugger']->addMessage('Getting recipe: ' . $id);

        $stmt = $this->db->prepare($this->queries['get_recipe']);

        $stmt->bindParam(1, $id);
        $stmt->execute();

        $ret = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $ret['ingredients'] = explode('||', $ret['ingredients']);
        $ret['tags']        = explode('||', $ret['tags']);

        $this->grav['twig']->twig_vars['recipe'] = $ret;
    }

    public function onTwigTemplatePaths()
    {
        $twig = $this->grav['twig'];
        $twig->twig_paths[] = __DIR__ . '/templates';
    }

    public function newRecipe()
    {
        $uuid = uniqid();
        $user = $this->grav['user']->username;

        // build base recipe
        $stmt = $this->db->prepare($this->queries['new_recipe']);
        $stmt->bindParam(':uuid'      , $uuid);
        $stmt->bindParam(':user'      , $user);
        $stmt->bindParam(':name'      , $_POST['name']);
        $stmt->bindParam(':notes'     , $_POST['notes']);
        $stmt->bindParam(':yields'    , $_POST['yields']);
        $stmt->bindParam(':directions', $_POST['directions']);
        $stmt->execute();

        // add each tag
        $tags      = explode(',', $_POST['tags']);
        $tag_query = $this->queries['new_recipe_tag'];
        foreach($tags as $tag) {
            $submit_tag = trim($tag);

            $stmt = $this->db->prepare($tag_query);
            $stmt->bindParam(':uuid', $uuid);
            $stmt->bindParam(':tag', $submit_tag);
            $stmt->execute();
        }

        //add each ingredient
        $ingredients       = explode("\n", $_POST['ingredients']);
        $ingredients_query = $this->queries['new_recipe_ingr'];
        foreach($ingredients as $ingredient) {
            $submit_ingr = trim(trim($ingredient), "- ");

            $stmt = $this->db->prepare($ingredients_query);
            $stmt->bindParam(':uuid'      , $uuid);
            $stmt->bindParam(':ingredient', $submit_ingr);
            $stmt->execute();
        }

        $redirect_route = $this->config->get('plugins.recipebook.route_view') . '/' . $uuid;
        $this->grav->redirect($redirect_route, 302);
    }

    public function editRecipeBase($uuid)
    {
        // update the base recipe
        $stmt = $this->db->prepare($this->queries['edit_recipe']);
        
        $stmt->bindParam(':uuid', $uuid);
        $stmt->bindParam(':name', $_POST['name']);
        $stmt->bindParam(':notes', $_POST['notes']);
        $stmt->bindParam(':yields', $_POST['yields']);
        $stmt->bindParam(':directions', $_POST['directions']);
        $stmt->execute();

        return;
    }

    public function editRecipeIngredients($uuid)
    {
        // delete ingredients
        $stmt = $this->db->prepare($this->queries['delete_ingredients']);
        $stmt->bindParam(':uuid', $uuid);
        $stmt->execute();

         //add each ingredient
        $ingredients       = explode("\n", $_POST['ingredients']);
        $ingredients_query = $this->queries['new_recipe_ingr'];
        foreach($ingredients as $ingredient) {
            $submit_ingr = trim(trim($ingredient), "- ");

            if ( strcmp($submit_ingr, '') != 0 ) {
                $stmt = $this->db->prepare($ingredients_query);
                $stmt->bindParam(':uuid'      , $uuid);
                $stmt->bindParam(':ingredient', $submit_ingr);
                $stmt->execute();
            }
        }
    }

    public function editRecipeTags($uuid)
    {
        // delete tags
        $stmt = $this->db->prepare($this->queries['delete_tags']);
        $stmt->bindParam(':uuid', $uuid);
        $stmt->execute();

        // add each tag
        $tags      = explode(',', $_POST['tags']);
        $tag_query = $this->queries['new_recipe_tag'];
        foreach($tags as $tag) {
            $submit_tag = trim($tag);

            $stmt = $this->db->prepare($tag_query);
            $stmt->bindParam(':uuid', $uuid);
            $stmt->bindParam(':tag', $submit_tag);
            $stmt->execute();
        }
        return;
    }

    public function editRecipe() 
    {
        $path     = $this->grav['uri']->path();
        $edit_len = strlen($this->config->get('plugins.recipebook.route_edit'));
        $uuid     = substr($this->grav['uri']->path(), $edit_len+1, strlen($path));

        $user = $this->grav['user']->username;

        $this->editRecipeBase($uuid);
        $this->editRecipeIngredients($uuid);
        $this->editRecipeTags($uuid);

        $redirect_route = $this->config->get('plugins.recipebook.route_view') . '/' . $uuid;
        $this->grav->redirect($redirect_route, 302);

        return;
    }
}
