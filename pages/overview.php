<?php

$addon = rex_addon::get('knowledgebase');

echo rex_view::info($addon->i18n('knowledgebase_overview_intro'));

$content = '<div class="row">';
$content .= '<div class="col-sm-6">';
$content .= '<div class="panel panel-default"><div class="panel-body">';
$content .= '<h3>' . rex_escape($addon->i18n('knowledgebase_overview_bases')) . '</h3>';
$content .= '<p>' . rex_escape($addon->i18n('knowledgebase_overview_bases_text')) . '</p>';
$content .= '<p><a class="btn btn-primary" href="' . rex_url::backendPage('knowledgebase/bases') . '">' . rex_escape($addon->i18n('knowledgebase_manage_bases')) . '</a></p>';
$content .= '</div></div>';
$content .= '</div>';
$content .= '<div class="col-sm-6">';
$content .= '<div class="panel panel-default"><div class="panel-body">';
$content .= '<h3>' . rex_escape($addon->i18n('knowledgebase_overview_articles')) . '</h3>';
$content .= '<p>' . rex_escape($addon->i18n('knowledgebase_overview_articles_text')) . '</p>';
$content .= '<p><a class="btn btn-primary" href="' . rex_url::backendPage('knowledgebase/articles') . '">' . rex_escape($addon->i18n('knowledgebase_manage_articles')) . '</a></p>';
$content .= '</div></div>';
$content .= '</div>';
$content .= '</div>';

$fragment = new rex_fragment();
$fragment->setVar('title', $addon->i18n('knowledgebase_overview_title'), false);
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');