<?php
/**
 * PHP 5.3
 *
 * 42Viral(tm) : The 42Viral Project (http://42viral.org)
 * Copyright 2009-2012, 42 North Group Inc. (http://42northgroup.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2009-2012, 42 North Group Inc. (http://42northgroup.com)
 * @link          http://42viral.org 42Viral(tm)
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @package 42viral\Content\Post
 */

App::uses('AppController', 'Controller');
/**
 * Provides controll logic for managing blog post actions
 * @author Jason D Snider <jason.snider@42viral.org>
 * @author Lyubomir R Dimov <lubo.dimov@42viral.org>
 * @author Zubin Khavarian (https://github.com/zubinkhavarian)
 * @package 42viral\Content\Post
 */
class PostsController extends AppController {

    /**
     * Models this controller uses
     *
     * @var array
     * @access public
     */
    public $uses = array(
        'Content.Blog',
        'Content.Post',
        'Person',
        'Profile'
    );

    /**
     * Helpers
     * @var array
     * @access public
     */
    public $helpers = array(
        'Profile',
        'Tags.TagCloud',
        'Asset'
    );

    /**
     * Components
     * @access public
     * @var array
     */
    public $components = array();

    /**
     * beforeFilter
     * @access public
     */
    public function beforeFilter(){
        parent::beforeFilter();
        $this->auth(array('short_cut', 'view'));
    }

    /**
     * Removes a post
     *
     * @param $id ID of the post which we want ot delete
     * @access public
     */
    public function delete($id){

        if($this->Post->delete($id)){
            $this->Session->setFlash(__('Your post has been removed'), 'success');
            $this->redirect($this->referer());
        }else{
           $this->Session->setFlash(__('There was a problem removing your post'), 'error');
           $this->redirect($this->referer());
        }

    }

    /**
     * Creates a post or blog entry
     *
     * @access public
     * @param $blogId ID of the blog for which we are creating a post
     *
     */
    public function create($blogId = null)
    {
        $canCreateBlogs = false;

        if(is_null($blogId)){

            if ($this->Acl->check($this->Session->read('Auth.User.username'), 'Blogs-create', '*')) {
                $canCreateBlogs = true;
            }

            //Fetch all the blogs created by the loggedin user
            $myBlogs = $this->Blog->find('all',
                    array(
                        'conditions'=>array('Blog.created_person_id'=>$this->Session->read('Auth.User.id')),
                        'contain'=>array()));

            //Fetch all blogs that have been marked as publicly postable
            $publicBlogs = $this->Blog->find('all',
                    array(
                        'conditions'=>array('Blog.post_access'=>'public'),
                        'contain'=>array()));

            $this->set('myBlogs', $myBlogs);
            $this->set('publicBlogs', $publicBlogs);

        }else{

            if(!empty($this->data)){

                if($this->Post->save($this->data)){
                    $this->Session->setFlash(__('You have successfully posted to your blog'), 'success');
                    $this->redirect("/posts/edit/{$this->Post->id}");
                }else{
                    $this->Session->setFlash(__('There was a problem posting to your blog'), 'error');
                }

            }

        }

        $this->set('title_for_layout', 'Post to a Blog');
        $this->set('canCreateBlogs', $canCreateBlogs);
    }

    /**
     * Creates a post or blog entry
     * @access public
     * @param string $id
     */
    public function edit($id)
    {
        if(!empty($this->data)){

            //If we are saving as Markdown, don't allow any HTML
            if($this->data['Post']['syntax']=='markdown'){
                $this->Post->Behaviors->attach(
                        'Scrubable',
                        array('Filters'=>array(
                                    'trim'=>'*',
                                    'safe' => array('body'),
                                    'noHTML'=>array(
                                        'canonical',
                                        'title',
                                        'description',
                                        'id',
                                        'keywords',
                                        'short_cut',
                                        'syntax'
                                    ),
                                )
                            )
                        );
            }

            if($this->Post->saveAll($this->data)){
                $this->Session->setFlash(__('You have successfully posted to your blog'), 'success');
            }else{
                $this->Session->setFlash(__('There was a problem posting to your blog'), 'error');
            }
        }

        //Now that we have saved the data, grab the latest copy and repopulate the page
        $this->data = $this->Post->find('first', array(
			'conditions' => array(
                'or' => array(
                    'Post.id' => $id,
                    'Post.slug' => $id,
                    'Post.short_cut' => $id
                )
            ),
            'contain' => array(
                'CreatedPerson' => array(
                    'Profile' => array()
                ),
                'Sitemap',
                'Tag'
            )
        ));

        $this->set('statuses', $this->Blog->listPublicationStatus());

        //Check the custom directory for a custom page. A custom page will still use any body content for completing
        //searches and building results page. However, when the page is rendered, it will pull the custom content
        //instead of the content in the database. This is handy when needing to build a page that is mor complecated
        //than Markdown, HTMLPurifier, the WYSIWYG editor will allow. Examples include needing PHP and/or JavaScript
        $themePath = ROOT . DS . APP_DIR . DS . 'View' . DS . 'Themed' . DS
                . Configure::write('Theme.set', 'Default') . DS;

        $unthemedPath = ROOT . DS . APP_DIR . DS . 'View' . DS;
        $relativeCustomPath = '42viral' . DS . 'Posts' . DS . 'Custom' . DS;

        $themed = $themePath . $relativeCustomPath;
        $unthemed = $unthemedPath . $relativeCustomPath;

        $paths = array();

        if(is_dir($themed)){
            foreach($this->File->scan($themed) as $key => $value){
                if(is_file($themed . $value . '.ctp')){
                    $paths[$key] = $value;
                }
            }
        }

        if(is_dir($unthemed)){
            foreach($this->File->scan($unthemed) as $key => $value){
                if(is_file($unthemed . $value . '.ctp')){
                    $paths[$key] = $value;
                }
            }
        }

        $this->set('customFiles', $paths);
        $this->set('title_for_layout', "Edit {$this->data['Post']['title']}");
    }

    /**
     * Redirect short links to their proper url
     *
     * @access public
     * @param string $shortCut
     *
     */
    public function short_cut($shortCut) {

        $post = $this->Post->find('first', array(
        	'conditions' => array(
                'or' => array(
                    'Post.id' => $shortCut,
                    'Post.slug' => $shortCut,
                    'Post.short_cut' => $shortCut
                )
            ),
            'contain' => array()
        ));

        //Avoid Google duplication penalties by using a 301 redirect
        $this->redirect($post['Post']['canonical'], 301);
    }

    /**
     * Displays a blog post
     *
     * @param string $slug
     * @access public
     *
     */
    public function view($slug) {
        $mine = false;

        $post = $this->Post->find('first', array(
			'conditions' => array(
                'or' => array(
                    'Post.id' => $slug,
                    'Post.slug' => $slug,
                    'Post.short_cut' => $slug
                )
            ),
    		'contain' => array(
                'Conversation' => array(
                    'CreatedPerson' => array(
                        'Profile' => array()
                    )
                ),
                'CreatedPerson' => array(
                    'Profile' => array()
                )
            )
        ));

        if(empty($post)){
           $this->redirect('/', '404');
        }

        //Add a comment
        if($this->data){

            if($this->Conversation->save($this->data)){
                $this->Session->setFlash(_('Your comment has been saved') ,'success');
                $this->redirect($this->referer());
            }else{
                $this->Session->setFlash(_('Your comment could not be saved') ,'error');
            }
        }

        //Build a user profile for use in the elements. The view must recive an array of $userProfile
        $userProfile['Person'] = $post['CreatedPerson'];
        $userProfile['Profile'] = $post['CreatedPerson']['Profile'];
        $this->set('userProfile', $userProfile);
        $this->set('menuPerson', $userProfile);

        $this->set('title_for_layout', $post['Post']['title']);
        $this->set('canonical_for_layout', $post['Post']['canonical']);

        $this->set('post', $post);

        if($this->Session->read('Auth.User.id') == $post['Post']['created_person_id']){
            $mine = true;
        }

        $this->set('mine', $mine);

        $this->set('tags', $this->Post->Tagged->find('cloud', array('limit' => 10)));
    }
}
