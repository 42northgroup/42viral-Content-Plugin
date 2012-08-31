<?php
/**
 * Routes configuration
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different urls to chosen controllers and their actions (functions).
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Config
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

Router::connect('/', array('controller' => 'pages', 'action' => 'display', 'home'));

//A convenience route for accessing admin sections
Router::connect('/admin', array('prefix'=>'admin', 'controller' => 'admin', 'action' => 'index'));

//Extra pretty URLs for content actions
Router::connect('/page', array('plugin'=>'content', 'controller' => 'pages', 'action' => 'index'));
Router::connect('/page/:slug', array('plugin'=>'content', 'controller' => 'pages', 'action' => 'view'), array('pass' => array('slug')));

Router::connect('/blog', array('plugin'=>'content', 'controller' => 'blogs', 'action' => 'index'));
Router::connect('/blog/:slug', array('plugin'=>'content', 'controller' => 'blogs', 'action' => 'view'), array('pass' => array('slug')));

Router::connect('/post', array('plugin'=>'content', 'controller' => 'posts', 'action' => 'index'));
Router::connect('/post/:slug', array('plugin'=>'content', 'controller' => 'posts', 'action' => 'view'), array('pass' => array('slug')));