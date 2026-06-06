<?php

$addon = rex_addon::get('knowledgebase');
echo rex_view::title($addon->i18n('knowledgebase_title'));
rex_be_controller::includeCurrentPageSubPath();