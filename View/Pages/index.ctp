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
 */

App::uses('Scrub', 'Lib');
?>
<h1><?php echo $title_for_layout; ?></h1>
<div class="row">
    <div class="two-thirds column alpha">
        <div id="ResultsPage">
            <?php if(empty($pages)): ?>
                <div class="no-results">
                    <div class="no-results-message">
                        <?php echo __("I'm sorry, there are no results to display."); ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php foreach($pages as $page): ?>
                <div class="result">
                    <div class="result-left">
                        <?php echo Inflector::humanize($page['Page']['object_type']); ?>
                    </div>
                    <div class="result-right">

                        <strong><?php echo $this->Html->link($page['Page']['title'], $page['Page']['url']); ?> </strong>

                        <div class="tease">
                            <?php
                            switch($page['Page']['syntax']):
                                case 'markdown':
                                    //echo Scrub::htmlMedia(
                                    echo Scrub::noHtml(
                                            Utility::markdown(
                                                $this->Text->truncate(
                                                        $page['Page']['body'], 180, array('html' => true))));
                                break;

                                default:
                                    echo Scrub::noHtml(
                                        $this->Text->truncate(
                                                $page['Page']['body'], 180, array('html' => true)));
                                break;
                            endswitch;
                            ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php echo $this->element('paginate'); ?>
    </div>
    <div class="one-third column omega">
        <?php echo $this->element('Navigation' . DS . 'menus', array('section'=>'page')); ?>
    </div>
</div>